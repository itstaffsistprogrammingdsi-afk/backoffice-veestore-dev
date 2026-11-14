<?php

namespace App\Ai\Support;

class ToolCallLogger
{
    /** @var list<array{name: string, arguments: array, summary: string, duration_ms: int}> */
    private array $calls = [];

    /** @var list<array<string, mixed>> */
    private array $evidence = [];

    /**
     * @param  array<string, mixed>  $evidence
     */
    public function record(string $name, array $arguments, string $summary, int $durationMs, array $evidence = []): void
    {
        $this->calls[] = [
            'name' => $name,
            'arguments' => $this->redact($arguments),
            'summary' => $summary,
            'duration_ms' => $durationMs,
        ];

        if ($evidence !== []) {
            $this->evidence[] = [
                'tool' => $name,
                ...$evidence,
            ];
        }
    }

    /**
     * @return list<array{name: string, arguments: array, summary: string, duration_ms: int}>
     */
    public function calls(): array
    {
        return $this->calls;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function evidence(): array
    {
        return $this->evidence;
    }

    public function status(): ?string
    {
        foreach (array_reverse($this->evidence) as $item) {
            $status = $item['answer_status'] ?? null;

            if (is_string($status) && $status !== '') {
                return $status;
            }
        }

        return null;
    }

    public function reset(): void
    {
        $this->calls = [];
        $this->evidence = [];
    }

    private function redact(array $data): array
    {
        $sensitive = config('assistant.sensitive_columns', []);

        foreach ($data as $key => $value) {
            $normalized = strtolower((string) $key);

            foreach ($sensitive as $needle) {
                if (str_contains($normalized, $needle)) {
                    $data[$key] = '[REDACTED]';

                    continue 2;
                }
            }

            if (is_array($value)) {
                $data[$key] = $this->redact($value);
            } elseif (is_string($value) && strlen($value) > 500) {
                $data[$key] = substr($value, 0, 500).'...';
            }
        }

        return $data;
    }
}
