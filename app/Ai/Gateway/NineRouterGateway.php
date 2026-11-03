<?php

namespace App\Ai\Gateway;

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
        $body = $this->buildStepBody($provider, $model, $instructions, $messages, $tools, $schema, $options, $stepContext);

        $response = $this->withErrorHandling(
            $provider->name(),
            fn () => $this->client($provider, $timeout)->post('chat/completions', $body),
        );

        $data = $this->decodeBody((string) $response->body());

        $this->validateTextResponse($data);

        return $this->parseTextResponse($data, $provider, filled($schema))->withRawResponse($response);
    }

    /**
     * 9Router sometimes appends a trailing SSE terminator to a JSON response.
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
                    return $decoded;
                }
            }
        }

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
