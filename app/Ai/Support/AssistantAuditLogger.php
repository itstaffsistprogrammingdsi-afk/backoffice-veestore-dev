<?php

namespace App\Ai\Support;

use App\Ai\Security\AssistantAccessPolicy;
use App\Ai\Security\PiiRedactor;
use Illuminate\Support\Facades\Log;
use Throwable;

class AssistantAuditLogger
{
    public function __construct(
        private readonly AssistantAccessPolicy $policy,
        private readonly PiiRedactor $redactor,
    ) {}

    /**
     * @param  array<string, mixed>  $arguments
     * @param  array<string, mixed>  $result
     */
    public function toolDecision(
        string $tool,
        array $arguments,
        bool $allowed,
        ?string $reason = null,
        array $result = [],
        int $durationMs = 0,
    ): void {
        $this->write([
            'event' => 'tool.call',
            'decision' => $allowed ? 'allow' : 'deny',
            'reason' => $reason,
            'tool' => $tool,
            'arguments' => $this->redactor->redactArguments($tool, $arguments),
            'sensitive_fields_requested' => $this->sensitiveFields($arguments),
            'record_count' => $result['row_count'] ?? $result['count'] ?? null,
            'answer_status' => $result['answer_status'] ?? null,
            'duration_ms' => $durationMs,
        ]);
    }

    /**
     * @param  array<string, mixed>  $evidence
     * @param  list<array<string, mixed>>  $toolCalls
     */
    public function answer(
        string $answerStatus,
        array $toolCalls,
        array $evidence,
        int $durationMs,
        int $removedLinks = 0,
    ): void {
        $this->write([
            'event' => 'answer',
            'answer_status' => $answerStatus,
            'tools' => array_values(array_unique(array_map(
                fn (array $call): string => (string) ($call['name'] ?? ''),
                $toolCalls
            ))),
            'sources' => array_values(array_unique(array_map(
                fn (array $item): string => (string) ($item['source_report'] ?? ''),
                $evidence
            ))),
            'evidence' => $evidence,
            'removed_links' => $removedLinks,
            'duration_ms' => $durationMs,
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function guard(string $intent, string $answerStatus, array $context = []): void
    {
        $this->write([
            'event' => 'intent.guard',
            'intent' => $intent,
            'answer_status' => $answerStatus,
            'decision' => $context['decision'] ?? 'allow',
            'message_length' => $context['message_length'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function write(array $payload): void
    {
        $user = $this->policy->user();

        $context = [
            'user_id' => $user?->getKey(),
            'roles' => $this->policy->roles($user),
            'service_request' => $this->policy->isService(),
            ...$payload,
        ];

        try {
            $channel = config('assistant.audit.channel');

            if (is_string($channel) && $channel !== '') {
                Log::channel($channel)->info('assistant.audit', $context);

                return;
            }
        } catch (Throwable) {
            // Fall through to the default channel when a custom channel is unavailable.
        }

        Log::info('assistant.audit', $context);
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return list<string>
     */
    private function sensitiveFields(array $arguments): array
    {
        $fields = [];

        foreach ($arguments as $key => $value) {
            if (is_string($key) && preg_match('/(email|phone|wa_number|nama|name|alamat|address|nik|ktp|password|token|secret)/i', $key)) {
                $fields[] = $key;
            }

            if (is_array($value)) {
                $fields = [...$fields, ...$this->sensitiveFields($value)];
            }
        }

        return array_values(array_unique($fields));
    }
}
