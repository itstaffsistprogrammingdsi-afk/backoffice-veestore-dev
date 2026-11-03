<?php

namespace App\Ai\Support;

class ToolCallLogger
{
    /** @var list<array{name: string, arguments: array, summary: string, duration_ms: int}> */
    private array $calls = [];

    public function record(string $name, array $arguments, string $summary, int $durationMs): void
    {
        $this->calls[] = [
            'name' => $name,
            'arguments' => $this->redact($arguments),
            'summary' => $summary,
            'duration_ms' => $durationMs,
        ];
    }

    /**
     * @return list<array{name: string, arguments: array, summary: string, duration_ms: int}>
     */
    public function calls(): array
    {
        return $this->calls;
    }

    public function reset(): void
    {
        $this->calls = [];
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
