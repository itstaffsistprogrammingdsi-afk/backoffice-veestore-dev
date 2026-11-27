<?php

namespace App\Ai\Gateway;

use Generator;
use Laravel\Ai\Contracts\Providers\TextProvider;
use Laravel\Ai\Gateway\OpenAiCompatible\OpenAiCompatibleGateway;
use Laravel\Ai\Gateway\StepContext;
use Laravel\Ai\Gateway\StepResponse;
use Laravel\Ai\Gateway\TextGenerationOptions;

class NineRouterGateway extends OpenAiCompatibleGateway
{
    public function generateTextStep(
        TextProvider $provider,
        string $model,
        ?string $instructions,
        array $messages,
        array $tools,
        ?array $schema,
        ?TextGenerationOptions $options,
        ?int $timeout,
        StepContext $stepContext,
    ): StepResponse {
        [$tools, $instructions] = $this->applyFinalStepGuard($stepContext, $tools, $instructions);

        $body = $this->buildStepBody($provider, $model, $instructions, $messages, $tools, $schema, $options, $stepContext);

        $response = $this->withErrorHandling(
            $provider->name(),
            fn () => $this->client($provider, $timeout)->post('chat/completions', $body),
        );

        $data = $this->decodeBody((string) $response->body());

        $this->validateTextResponse($data);

        return $this->parseTextResponse($data, $provider, filled($schema))->withRawResponse($response);
    }

    public function generateStreamStep(
        string $invocationId,
        TextProvider $provider,
        string $model,
        ?string $instructions,
        array $messages,
        array $tools,
        ?array $schema,
        ?TextGenerationOptions $options,
        ?int $timeout,
        StepContext $stepContext,
    ): Generator {
        [$tools, $instructions] = $this->applyFinalStepGuard($stepContext, $tools, $instructions);

        return parent::generateStreamStep(
            $invocationId,
            $provider,
            $model,
            $instructions,
            $messages,
            $tools,
            $schema,
            $options,
            $timeout,
            $stepContext,
        );
    }

    /**
     * @param  array<int, mixed>  $tools
     * @return array{0: array<int, mixed>, 1: string|null}
     */
    private function applyFinalStepGuard(StepContext $stepContext, array $tools, ?string $instructions): array
    {
        if (! $stepContext->isFinalStep || $tools === []) {
            return [$tools, $instructions];
        }

        $nudge = 'Batas langkah tercapai. Tool tidak tersedia lagi. Susun jawaban akhir sekarang hanya dari data yang sudah tersedia, dan sebutkan bagian yang belum sempat diambil bila ada.';

        return [[], trim(($instructions ?? '').' '.$nudge)];
    }

    /**
     * 9Router can answer with a plain completion, a single SSE event, or a full
     * SSE delta stream depending on the upstream provider. All three shapes must
     * produce one complete completion body.
     *
     * @return array<string, mixed>
     */
    private function decodeBody(string $body): array
    {
        $body = trim($body);

        if ($body === '') {
            return [];
        }

        if (str_starts_with($body, 'data:')) {
            $chunks = $this->decodeEventStream($body);

            if ($chunks !== []) {
                return $this->mergeEventStream($chunks);
            }
        }

        return $this->decodeJson($body);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function decodeEventStream(string $body): array
    {
        $chunks = [];

        foreach (preg_split('/\r?\n/', $body) ?: [] as $line) {
            $line = trim($line);

            if (! str_starts_with($line, 'data:')) {
                continue;
            }

            $payload = trim(substr($line, 5));

            if ($payload === '' || $payload === '[DONE]') {
                continue;
            }

            $decoded = json_decode($payload, true);

            if (is_array($decoded) && isset($decoded['choices'])) {
                $chunks[] = $decoded;
            }
        }

        return $chunks;
    }

    /**
     * @param  list<array<string, mixed>>  $chunks
     * @return array<string, mixed>
     */
    private function mergeEventStream(array $chunks): array
    {
        $first = $chunks[0];

        if (isset($first['choices'][0]['message'])) {
            return $first;
        }

        $message = ['role' => 'assistant', 'content' => ''];
        $toolCalls = [];
        $reasoning = '';
        $finishReason = null;
        $usage = null;

        foreach ($chunks as $chunk) {
            $choice = $chunk['choices'][0] ?? [];
            $delta = $choice['delta'] ?? [];

            if (isset($delta['content']) && is_string($delta['content'])) {
                $message['content'] .= $delta['content'];
            }

            if (isset($delta['reasoning_content']) && is_string($delta['reasoning_content'])) {
                $reasoning .= $delta['reasoning_content'];
            }

            foreach ((array) ($delta['tool_calls'] ?? []) as $toolCall) {
                $index = (int) ($toolCall['index'] ?? count($toolCalls));

                $toolCalls[$index] ??= [
                    'id' => '',
                    'type' => 'function',
                    'function' => ['name' => '', 'arguments' => ''],
                ];

                if (! empty($toolCall['id'])) {
                    $toolCalls[$index]['id'] = $toolCall['id'];
                }

                if (isset($toolCall['function']['name'])) {
                    $toolCalls[$index]['function']['name'] .= $toolCall['function']['name'];
                }

                if (isset($toolCall['function']['arguments'])) {
                    $toolCalls[$index]['function']['arguments'] .= $toolCall['function']['arguments'];
                }
            }

            if (! empty($choice['finish_reason'])) {
                $finishReason = $choice['finish_reason'];
            }

            if (isset($chunk['usage']) && is_array($chunk['usage'])) {
                $usage = $chunk['usage'];
            }
        }

        if ($toolCalls !== []) {
            $message['tool_calls'] = array_values($toolCalls);
        }

        if ($reasoning !== '') {
            $message['reasoning_content'] = $reasoning;
        }

        $merged = $first;
        $merged['choices'] = [[
            'index' => 0,
            'message' => $message,
            'finish_reason' => $finishReason ?? ($toolCalls !== [] ? 'tool_calls' : 'stop'),
        ]];

        if ($usage !== null) {
            $merged['usage'] = $usage;
        }

        return $merged;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $body): array
    {
        $body = preg_replace('/\s*data:\s*\[DONE\]\s*$/i', '', $body) ?? $body;

        $decoded = json_decode(trim($body), true);

        if (is_array($decoded)) {
            return $decoded;
        }

        $start = strpos($body, '{');
        $end = strrpos($body, '}');

        if ($start !== false && $end !== false && $end > $start) {
            $decoded = json_decode(substr($body, $start, $end - $start + 1), true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }
}
