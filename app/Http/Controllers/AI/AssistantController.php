<?php

namespace App\Http\Controllers\AI;

use App\Ai\Agents\VeeAssistant;
use App\Ai\Support\ToolCallLogger;
use App\Http\Controllers\Controller;
use App\Http\Requests\AI\ChatRequest;
use App\Services\Assistant\AssistantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use RuntimeException;
use Throwable;

class AssistantController extends Controller
{
    private const SESSION_KEY = 'vee_assistant.messages';

    public function index(Request $request, AssistantContext $context)
    {
        return Inertia::render('Assistant/Index', $this->pagePayload($request, $context));
    }

    public function state(Request $request, AssistantContext $context): JsonResponse
    {
        return response()->json($this->pagePayload($request, $context));
    }

    public function chat(ChatRequest $request, AssistantContext $context): JsonResponse
    {
        $message = trim($request->validated('message'));
        $mode = (string) ($request->validated('mode') ?? $context->defaultMode());
        $history = $this->history($request);
        $logger = app(ToolCallLogger::class);

        try {
            $models = $context->modelsFor($mode);
            $response = null;
            $usedModel = null;
            $lastException = null;

            foreach ($models as $index => $model) {
                $logger->reset();

                try {
                    $response = (new VeeAssistant($model))->withMessages($history)->prompt($message, timeout: 120);
                    $usedModel = $model;

                    break;
                } catch (Throwable $exception) {
                    $lastException = $exception;

                    if ($index < count($models) - 1) {
                        Log::warning('VEE beralih ke model cadangan.', [
                            'model' => $model,
                            'exception' => $exception::class,
                        ]);
                    }
                }
            }

            if ($response === null) {
                throw $lastException ?? new RuntimeException('Tidak ada model asisten yang tersedia.');
            }

            $answer = trim((string) $response);
            $usage = [
                'input_tokens' => $response->usage->inputTokens ?? null,
                'output_tokens' => $response->usage->outputTokens ?? null,
            ];

            if ($answer === '' && $response->toolResults->isNotEmpty()) {
                try {
                    $finalResponse = (new VeeAssistant($usedModel))
                        ->withTools([])
                        ->withMessages($response->messages)
                        ->prompt(
                            "Pertanyaan pengguna: {$message}\n\n"
                            .'Susun jawaban akhir dalam Bahasa Indonesia hanya berdasarkan data tool pada riwayat di atas. '
                            .'Jangan memanggil tool. Jika sebagian data belum tersedia, sebutkan bagian yang belum bisa dijawab.',
                            timeout: 120,
                        );

                    $answer = trim((string) $finalResponse);
                    $usage['input_tokens'] = ($usage['input_tokens'] ?? 0) + ($finalResponse->usage->inputTokens ?? 0);
                    $usage['output_tokens'] = ($usage['output_tokens'] ?? 0) + ($finalResponse->usage->outputTokens ?? 0);
                } catch (Throwable $exception) {
                    Log::warning('VEE gagal menyusun jawaban akhir.', [
                        'model' => $usedModel,
                        'exception' => $exception::class,
                    ]);
                }
            }

            if ($answer === '') {
                $answer = $response->toolResults->isNotEmpty()
                    ? 'Data sudah ditemukan, tetapi VEE belum bisa menyusun ringkasan. Coba sebutkan periode, nama produk, atau nama toko yang lebih spesifik.'
                    : 'VEE belum bisa menyusun jawaban untuk pertanyaan ini. Coba tuliskan pertanyaan dengan periode, nama produk, atau nama toko yang lebih spesifik.';
            }

            $toolCalls = $logger->calls();
            $answer = $this->appendSources($answer, $toolCalls);

            $messages = $this->messages($request);
            $messages[] = $this->message('user', $message);
            $messages[] = $this->message('assistant', $answer, $toolCalls, $usage);

            $request->session()->put(self::SESSION_KEY, array_slice($messages, -30));

            return response()->json([
                'answer' => $answer,
                'messages' => array_slice($messages, -30),
                'tool_calls' => $toolCalls,
                'model' => $usedModel,
                'mode' => $mode,
                'usage' => $usage,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'error' => true,
                'message' => 'VEE belum dapat menghubungi model atau mengambil data. Periksa koneksi 9Router dan coba lagi.',
            ], 503);
        }
    }

    public function reset(Request $request): JsonResponse
    {
        $request->session()->forget(self::SESSION_KEY);
        app(ToolCallLogger::class)->reset();

        return response()->json(['ok' => true, 'messages' => []]);
    }

    /**
     * @return array<string, mixed>
     */
    private function pagePayload(Request $request, AssistantContext $context): array
    {
        return [
            'messages' => $this->messages($request),
            'suggestions' => $context->suggestions(),
            'tools' => $context->tools(),
            'features' => $context->features(),
            'coverage' => $context->coverage(),
            'model' => $context->model(),
            'modes' => $context->modes(),
            'default_mode' => $context->defaultMode(),
            'urls' => [
                'index' => route('ai-assistant.index'),
                'state' => route('ai-assistant.state'),
                'chat' => route('ai-assistant.chat'),
                'reset' => route('ai-assistant.reset'),
                'mcp' => route('mcp.endpoint'),
            ],
        ];
    }

    /**
     * @return list<array{id: string, role: string, content: string, tools: array, usage: array|null, created_at: string}>
     */
    private function messages(Request $request): array
    {
        $messages = $request->session()->get(self::SESSION_KEY, []);

        return is_array($messages) ? $messages : [];
    }

    /**
     * @return list<array{role: string, content: string}>
     */
    private function history(Request $request): array
    {
        $limit = max(2, (int) config('assistant.limits.max_history', 10)) * 2;

        return collect($this->messages($request))
            ->slice(-$limit)
            ->filter(fn (array $message) => in_array($message['role'] ?? '', ['user', 'assistant'], true))
            ->map(fn (array $message) => [
                'role' => $message['role'],
                'content' => $message['content'],
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<array{name: string, arguments: array}>  $toolCalls
     */
    private function appendSources(string $answer, array $toolCalls): string
    {
        $sources = $this->sourceLabels($toolCalls);

        if ($sources === []) {
            return $answer;
        }

        return rtrim($answer)."\n\n**Sumber:** ".implode('; ', $sources).'.';
    }

    /**
     * @param  list<array{name: string, arguments: array}>  $toolCalls
     * @return list<string>
     */
    private function sourceLabels(array $toolCalls): array
    {
        $sources = [];

        foreach ($toolCalls as $call) {
            $arguments = $call['arguments'] ?? [];
            $focus = $arguments['focus'] ?? 'all';

            $labels = match ($call['name'] ?? '') {
                'stock_health' => ['Stok gudang operasional', 'Stok toko operasional'],
                'product_sales' => ['Penjualan produk POS dan ecommerce per toko'],
                'business_overview' => match ($focus) {
                    'sales' => ['Transaksi penjualan POS dan ecommerce'],
                    'orders' => ['Pesanan ecommerce, order toko, dan retur'],
                    'inventory' => ['Stok gudang operasional', 'Stok toko operasional'],
                    default => [
                        'Transaksi penjualan POS dan ecommerce',
                        'Pesanan ecommerce, order toko, dan retur',
                        'Stok gudang operasional',
                        'Stok toko operasional',
                    ],
                },
                'operations_pipeline' => match ($focus) {
                    'orders' => ['Antrean pesanan ecommerce dan order toko'],
                    'returns' => ['Retur operasional toko'],
                    'inbound_outbound' => ['Pergerakan inbound dan outbound gudang/toko'],
                    'opname' => ['Stok opname gudang dan toko'],
                    default => [
                        'Pesanan dan retur operasional',
                        'Pergerakan inbound/outbound gudang dan toko',
                        'Stok opname gudang dan toko',
                    ],
                },
                'master_data' => [$this->masterSource((string) ($arguments['entity'] ?? ''))],
                'customer_insight' => ['Data customer dan membership'],
                'promo_loyalty' => ['Data promo, voucher, poin, dan loyalty'],
                'system_map' => ['Peta menu dan fitur VeeStore'],
                'data_schema' => ['Referensi data VeeStore'],
                'query_data' => [($arguments['connection'] ?? 'operational') === 'backoffice'
                    ? 'Data backoffice VeeStore'
                    : 'Data operasional VeeStore'],
                default => [],
            };

            $sources = [...$sources, ...$labels];
        }

        return array_values(array_unique(array_filter($sources)));
    }

    private function masterSource(string $entity): string
    {
        return match ($entity) {
            'products', 'product_prices', 'categories', 'brands', 'units' => 'Master produk dan harga',
            'stores' => 'Master toko',
            'warehouses' => 'Master gudang',
            'customers', 'memberships' => 'Master customer dan membership',
            'suppliers' => 'Master supplier',
            'couriers' => 'Master kurir',
            'partners' => 'Master partner',
            'payment_methods' => 'Master metode pembayaran',
            'vouchers' => 'Master voucher',
            'users' => 'Master pengguna',
            default => 'Data master VeeStore',
        };
    }

    /**
     * @param  list<array<string, mixed>>  $tools
     * @param  array<string, mixed>|null  $usage
     * @return array<string, mixed>
     */
    private function message(string $role, string $content, array $tools = [], ?array $usage = null): array
    {
        return [
            'id' => (string) Str::uuid(),
            'role' => $role,
            'content' => $content,
            'tools' => $tools,
            'usage' => $usage,
            'created_at' => now()->toIso8601String(),
        ];
    }
}
