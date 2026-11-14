<?php

namespace App\Ai\Mcp;

use App\Ai\Security\AssistantAccessPolicy;
use App\Ai\Security\PiiRedactor;
use App\Ai\Support\AnswerStatus;
use App\Ai\Support\AssistantAuditLogger;
use App\Ai\Support\ToolCallLogger;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;
use Throwable;

abstract class AssistantTool implements McpTool, Tool
{
    abstract public function description(): string;

    /**
     * @return array<string, mixed>
     */
    abstract public function inputSchema(): array;

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    abstract protected function execute(array $arguments): array;

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    public function run(array $arguments): array
    {
        $startedAt = microtime(true);
        $policy = app(AssistantAccessPolicy::class);
        $audit = app(AssistantAuditLogger::class);
        $denied = $policy->authorizeTool($this->name(), $arguments);

        if ($denied !== null) {
            $audit->toolDecision(
                $this->name(),
                $arguments,
                false,
                $denied,
                ['answer_status' => AnswerStatus::FORBIDDEN],
                (int) round((microtime(true) - $startedAt) * 1000),
            );

            return [
                'error' => $denied,
                'answer_status' => AnswerStatus::FORBIDDEN,
            ];
        }

        try {
            $result = $this->execute($arguments);
        } catch (Throwable $exception) {
            report($exception);
            $result = [
                'error' => 'Tool gagal dijalankan. Coba persempit pertanyaan atau ulangi.',
                'answer_status' => AnswerStatus::INVALID,
            ];
        }

        $result = app(PiiRedactor::class)->redactToolResult($this->name(), $result, $arguments);

        $audit->toolDecision(
            $this->name(),
            $arguments,
            true,
            null,
            $result,
            (int) round((microtime(true) - $startedAt) * 1000),
        );

        return $result;
    }

    public function handle(Request $request): Stringable|string
    {
        $startedAt = microtime(true);
        $arguments = $request->all();
        $logger = app(ToolCallLogger::class);

        try {
            $result = $this->run($arguments);
            $logger->record(
                $this->name(),
                $arguments,
                $this->summarize($result),
                (int) round((microtime(true) - $startedAt) * 1000),
                $this->evidence($result),
            );

            return $this->encode($result);
        } catch (Throwable $exception) {
            report($exception);

            return $this->encode([
                'ok' => false,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    protected function evidence(array $result): array
    {
        return array_filter([
            'source_report' => $this->sourceReport(),
            'period' => $result['period'] ?? null,
            'snapshot_at' => $result['snapshot_at'] ?? $result['generated_at'] ?? null,
            'row_count' => $result['row_count'] ?? $result['count'] ?? null,
            'answer_status' => $result['answer_status'] ?? null,
        ], fn ($value) => $value !== null && $value !== []);
    }

    protected function sourceReport(): string
    {
        return match ($this->name()) {
            'business_overview' => 'Ringkasan Usaha',
            'product_sales' => 'Produk Terlaris (Fast Moving)',
            'stock_health' => 'Monitoring Stok, Stok Minimum, dan Produk Slow Moving',
            'operations_pipeline' => 'Operasional Pesanan, Retur, Inbound/Outbound, dan Opname',
            'promo_loyalty' => 'Promo, Voucher, dan Loyalty',
            'customer_insight' => 'CRM, Demografi Pelanggan, dan Preferensi Produk',
            'master_data' => 'Master Data',
            'system_map' => 'Peta Menu dan Fitur VeeStore',
            'data_schema' => 'Referensi Teknis Internal',
            'query_data' => 'Query Data Kustom',
            default => 'Data VeeStore',
        };
    }

    public function schema(JsonSchema $schema): array
    {
        $definition = $this->inputSchema();
        $properties = [];
        $required = $definition['required'] ?? [];

        foreach ($definition['properties'] ?? [] as $name => $property) {
            $type = $this->convertType($schema, $property);

            if (in_array($name, $required, true)) {
                $type->required();
            }

            $properties[$name] = $type;
        }

        return $properties;
    }

    protected function summarize(array $result): string
    {
        if (isset($result['row_count'])) {
            return 'row_count='.$result['row_count'];
        }

        if (isset($result['summary']) && is_array($result['summary'])) {
            return 'summary_keys='.implode(',', array_keys($result['summary']));
        }

        return 'keys='.implode(',', array_slice(array_keys($result), 0, 8));
    }

    protected function encode(array $result): string
    {
        return json_encode(
            ['ok' => ! isset($result['error']), 'tool' => $this->name(), 'data' => $result],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR
        ) ?: '{}';
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function convertType(JsonSchema $schema, array $definition): Type
    {
        $type = $definition['type'] ?? 'string';

        $instance = match ($type) {
            'integer' => $schema->integer(),
            'number' => $schema->number(),
            'boolean' => $schema->boolean(),
            'array' => $schema->array(),
            default => $schema->string(),
        };

        if (isset($definition['description'])) {
            $instance->description((string) $definition['description']);
        }

        if (isset($definition['title'])) {
            $instance->title((string) $definition['title']);
        }

        if (isset($definition['enum'])) {
            $instance->enum($definition['enum']);
        }

        if (array_key_exists('default', $definition)) {
            $value = $definition['default'];

            if ($type === 'integer' && is_int($value)) {
                $instance->default($value);
            } elseif ($type === 'number' && is_int($value)) {
                $instance->default($value);
            } elseif ($type === 'boolean' && is_bool($value)) {
                $instance->default($value);
            } elseif ($type === 'array' && is_array($value)) {
                $instance->default($value);
            } elseif ($type === 'string' && is_string($value)) {
                $instance->default($value);
            }
        }

        if (isset($definition['min']) && in_array($type, ['integer', 'number'], true)) {
            $instance->min((int) $definition['min']);
        }

        if (isset($definition['max']) && in_array($type, ['integer', 'number'], true)) {
            $instance->max((int) $definition['max']);
        }

        if (isset($definition['items']) && $type === 'array') {
            $instance->items($this->convertType($schema, $definition['items']));
        }

        return $instance;
    }
}
