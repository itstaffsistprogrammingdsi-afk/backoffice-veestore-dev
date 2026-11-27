<?php

namespace App\Http\Controllers\AI;

use App\Ai\Agents\VeeAssistant;
use App\Ai\Security\AssistantAccessPolicy;
use App\Ai\Security\PiiRedactor;
use App\Ai\Support\AnswerStatus;
use App\Ai\Support\AssistantAuditLogger;
use App\Ai\Support\AssistantReferenceRegistry;
use App\Ai\Support\AssistantRouteRegistry;
use App\Ai\Support\QuestionIntentGuard;
use App\Ai\Support\ToolCallLogger;
use App\Http\Controllers\Controller;
use App\Http\Requests\AI\ChatRequest;
use App\Models\User;
use App\Services\Assistant\AssistantContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Ai\Streaming\Events\TextDelta;
use Laravel\Ai\Streaming\Events\ToolCall as ToolCallEvent;
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
        app(AssistantAccessPolicy::class)->markUserRequest();

        $message = trim($request->validated('message'));
        $mode = (string) ($request->validated('mode') ?? $context->defaultMode());
        $history = $this->history($request);
        $logger = app(ToolCallLogger::class);
        $startedAt = microtime(true);
        $intent = app(QuestionIntentGuard::class)->inspect($message);

        if ($intent !== null) {
            return response()->json($this->guardPayload($request, $message, $mode, $intent, $startedAt));
        }

        try {
            $models = $context->modelsFor($mode);
            $response = null;
            $usedModel = null;
            $lastException = null;
            $modelStartedAt = microtime(true);
            $stepTimeout = max(5, (int) config('assistant.limits.step_timeout_seconds', 45));
            $budgetMs = max(5000, (int) config('assistant.limits.fallback_budget_ms', 45000));
            $agentSteps = $mode === 'expert' ? (int) config('assistant.limits.max_steps_expert', 6) : null;
            $attempts = 0;

            foreach ($models as $index => $model) {
                $elapsedMs = (microtime(true) - $startedAt) * 1000;

                if ($index > 0 && $elapsedMs >= $budgetMs) {
                    Log::warning('VEE melewati model cadangan karena batas waktu.', [
                        'model' => $model,
                        'elapsed_ms' => (int) round($elapsedMs),
                    ]);

                    break;
                }

                $logger->reset();
                $attempts++;

                try {
                    $response = (new VeeAssistant($model, $mode === 'expert', $agentSteps))->withMessages($history)->prompt($message, timeout: $stepTimeout);
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

            $answer = $this->stripToolMarkup(trim((string) $response));
            $usage = [
                'input_tokens' => $response->usage->inputTokens ?? null,
                'output_tokens' => $response->usage->outputTokens ?? null,
            ];

            if ($answer === '' && $response->toolResults->isNotEmpty()) {
                try {
                    $finalResponse = (new VeeAssistant($usedModel))
                        ->withTools([])
                        ->prompt(
                            "Pertanyaan pengguna: {$message}\n\n"
                            .$this->toolResultsContext($response->toolResults)."\n\n"
                            .'Susun jawaban akhir dalam Bahasa Indonesia hanya berdasarkan data tool di atas. '
                            .'Jangan memanggil tool dan jangan menuliskan nama tool, SQL, atau markup protokol. '
                            .'Jika sebagian data belum tersedia, sebutkan bagian yang belum bisa dijawab.',
                            timeout: $stepTimeout,
                        );

                    $answer = $this->stripToolMarkup(trim((string) $finalResponse));
                    $usage['input_tokens'] = ($usage['input_tokens'] ?? 0) + ($finalResponse->usage->inputTokens ?? 0);
                    $usage['output_tokens'] = ($usage['output_tokens'] ?? 0) + ($finalResponse->usage->outputTokens ?? 0);
                } catch (Throwable $exception) {
                    Log::warning('VEE gagal menyusun jawaban akhir.', [
                        'model' => $usedModel,
                        'exception' => $exception::class,
                    ]);
                }
            }

            return response()->json($this->finish(
                $request,
                $message,
                $mode,
                $usedModel,
                $answer,
                $usage,
                $logger,
                $response->toolResults->isNotEmpty(),
                [
                    'model_ms' => (int) round((microtime(true) - $modelStartedAt) * 1000),
                    'llm_ms' => (int) round((microtime(true) - $modelStartedAt) * 1000),
                    'attempts' => $attempts,
                    'total_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                ],
            ));
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'error' => true,
                'message' => 'VEE belum dapat menghubungi model atau mengambil data. Periksa koneksi 9Router dan coba lagi.',
            ], 503);
        }
    }

    public function stream(ChatRequest $request, AssistantContext $context)
    {
        app(AssistantAccessPolicy::class)->markUserRequest();

        $message = trim($request->validated('message'));
        $mode = (string) ($request->validated('mode') ?? $context->defaultMode());
        $history = $this->history($request);
        $models = $context->modelsFor($mode);
        $logger = app(ToolCallLogger::class);
        $startedAt = microtime(true);
        $stepTimeout = max(5, (int) config('assistant.limits.step_timeout_seconds', 45));
        $agentSteps = $mode === 'expert' ? (int) config('assistant.limits.max_steps_expert', 6) : null;
        $intent = app(QuestionIntentGuard::class)->inspect($message);

        return response()->stream(function () use ($request, $message, $mode, $history, $models, $logger, $startedAt, $stepTimeout, $agentSteps, $intent): void {
            while (ob_get_level() > 0) {
                ob_end_flush();
            }

            $emit = function (string $event, array $payload): void {
                echo 'event: '.$event."\n";
                echo 'data: '.json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n\n";
                flush();
            };

            if ($intent !== null) {
                $payload = $this->guardPayload($request, $message, $mode, $intent, $startedAt);

                $emit('status', ['label' => 'Menjawab dari pustaka VEE', 'tool' => null]);
                $emit('token', ['text' => $payload['answer']]);
                $emit('done', $payload);

                return;
            }

            $emit('status', ['label' => 'Menyiapkan jawaban', 'tool' => null]);

            $stream = null;
            $usedModel = null;
            $attempts = 0;
            $emitted = false;

            foreach ($models as $model) {
                $logger->reset();
                $attempts++;

                try {
                    $candidate = (new VeeAssistant($model, $mode === 'expert', $agentSteps))->withMessages($history)->stream($message, timeout: $stepTimeout);

                    foreach ($candidate as $event) {
                        if ($event instanceof TextDelta) {
                            $emitted = true;
                            $emit('token', ['text' => $event->delta]);
                        } elseif ($event instanceof ToolCallEvent) {
                            $emit('status', [
                                'label' => 'Mengambil data: '.$event->toolCall->name,
                                'tool' => $event->toolCall->name,
                            ]);
                        }
                    }

                    $usedModel = $model;
                    $stream = $candidate;

                    break;
                } catch (Throwable $exception) {
                    if ($emitted) {
                        report($exception);

                        $emit('error', ['message' => 'Koneksi model terputus saat menjawab. Coba ulangi pertanyaan.']);

                        return;
                    }

                    report($exception);
                    $logger->reset();
                }
            }

            if ($stream === null) {
                $emit('error', ['message' => 'VEE belum dapat menghubungi model atau mengambil data. Periksa koneksi 9Router dan coba lagi.']);

                return;
            }

            $payload = $this->finish(
                $request,
                $message,
                $mode,
                $usedModel,
                $this->stripToolMarkup((string) $stream->text),
                [
                    'input_tokens' => $stream->usage?->inputTokens,
                    'output_tokens' => $stream->usage?->outputTokens,
                ],
                $logger,
                $logger->calls() !== [],
                [
                    'llm_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                    'attempts' => $attempts,
                    'total_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                ],
            );

            $emit('done', $payload);
        }, 200, [
            'Cache-Control' => 'no-cache, no-transform',
            'Content-Type' => 'text/event-stream',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * @param  array{intent: string, answer: string, answer_status: string, safe_actions: list<array<string, mixed>>}  $intent
     * @return array<string, mixed>
     */
    private function guardPayload(Request $request, string $message, string $mode, array $intent, float $startedAt): array
    {
        $sanitized = app(AssistantRouteRegistry::class)->sanitize($intent['answer'], $request->user());
        $answer = app(PiiRedactor::class)->redactText($sanitized['answer']) ?? $sanitized['answer'];

        $messages = $this->messages($request);
        $messages[] = $this->message('user', $message);
        $messages[] = $this->message('assistant', $answer);

        $request->session()->put(self::SESSION_KEY, array_slice($messages, -30));

        app(AssistantAuditLogger::class)->guard($intent['intent'], $intent['answer_status'], [
            'decision' => 'allow',
            'message_length' => mb_strlen($message),
        ]);
        app(AssistantAuditLogger::class)->answer(
            $intent['answer_status'],
            [],
            [],
            (int) round((microtime(true) - $startedAt) * 1000),
            $sanitized['removed'],
        );

        return [
            'answer' => $answer,
            'messages' => array_slice($messages, -30),
            'tool_calls' => [],
            'model' => null,
            'mode' => $mode,
            'usage' => ['input_tokens' => null, 'output_tokens' => null],
            'answer_status' => $intent['answer_status'],
            'evidence' => [],
            'safe_actions' => $intent['safe_actions'],
            'removed_links' => $sanitized['removed'],
            'timings' => ['total_ms' => (int) round((microtime(true) - $startedAt) * 1000)],
        ];
    }

    /**
     * @param  array{input_tokens: int|null, output_tokens: int|null}  $usage
     * @param  array<string, int>  $timings
     * @return array<string, mixed>
     */
    private function finish(
        Request $request,
        string $message,
        string $mode,
        ?string $usedModel,
        string $answer,
        array $usage,
        ToolCallLogger $logger,
        bool $hadToolResults,
        array $timings,
    ): array {
        $toolCalls = $this->attachReferences($logger->calls(), $request->user());
        $evidence = $logger->evidence();
        $status = $logger->status();

        if ($answer === '') {
            $answer = $hadToolResults
                ? 'Data sudah ditemukan, tetapi VEE belum bisa menyusun ringkasan. Coba sebutkan periode, nama produk, atau nama toko yang lebih spesifik.'
                : 'VEE belum bisa menyusun jawaban untuk pertanyaan ini. Coba tuliskan pertanyaan dengan periode, nama produk, atau nama toko yang lebih spesifik.';
            $status = $hadToolResults ? AnswerStatus::NO_DATA : AnswerStatus::NOT_AVAILABLE;
        }

        $status ??= $toolCalls === [] ? AnswerStatus::NOT_AVAILABLE : AnswerStatus::OK;

        $sanitized = app(AssistantRouteRegistry::class)->sanitize($answer, $request->user());
        $answer = app(PiiRedactor::class)->redactText($sanitized['answer']) ?? $sanitized['answer'];

        if ($status === AnswerStatus::NOT_YET_OCCURRED) {
            $answer = $this->futurePeriodAnswer($evidence);
        }

        if (! in_array($status, [AnswerStatus::FORBIDDEN, AnswerStatus::INVALID], true)) {
            $answer = $this->appendSources($answer, $toolCalls);
        }

        if ((bool) config('assistant.evidence.append_to_answer', true)
            && ! in_array($status, [AnswerStatus::FORBIDDEN, AnswerStatus::INVALID, AnswerStatus::NEEDS_CLARIFICATION], true)) {
            $answer .= $this->evidenceLine($evidence);
        }

        $messages = $this->messages($request);
        $messages[] = $this->message('user', $message);
        $messages[] = $this->message('assistant', $answer, $toolCalls, $usage);

        $request->session()->put(self::SESSION_KEY, array_slice($messages, -30));

        app(AssistantAuditLogger::class)->answer(
            $status,
            $toolCalls,
            $evidence,
            $timings['total_ms'],
            $sanitized['removed'],
        );

        return [
            'answer' => $answer,
            'messages' => array_slice($messages, -30),
            'tool_calls' => $toolCalls,
            'model' => $usedModel,
            'mode' => $mode,
            'usage' => $usage,
            'answer_status' => $status,
            'evidence' => $evidence,
            'safe_actions' => [],
            'removed_links' => $sanitized['removed'],
            'timings' => $timings,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $evidence
     */
    private function futurePeriodAnswer(array $evidence): string
    {
        $period = null;

        foreach ($evidence as $item) {
            $candidate = $item['period'] ?? null;

            if (is_array($candidate) && isset($candidate['date_from'])) {
                $period = trim((string) $candidate['date_from'].' sampai '.(string) ($candidate['date_to'] ?? $candidate['date_from']));

                break;
            }

            if (is_string($candidate) && $candidate !== '') {
                $period = $candidate;

                break;
            }
        }

        return 'Periode '.($period ?? 'yang diminta').' belum terjadi, jadi belum ada data yang dapat dihitung. '
            .'Saya tidak menampilkannya sebagai nol tercatat.';
    }

    /**
     * @param  list<array<string, mixed>>  $evidence
     */
    private function evidenceLine(array $evidence): string
    {
        if ($evidence === []) {
            return '';
        }

        $sources = [];
        $periods = [];
        $snapshots = [];

        foreach ($evidence as $item) {
            $source = trim((string) ($item['source_report'] ?? ''));

            if ($source !== '') {
                $sources[] = $source;
            }

            $period = $item['period'] ?? null;

            if (is_array($period) && isset($period['date_from'])) {
                $periods[] = trim((string) $period['date_from'].' sampai '.(string) ($period['date_to'] ?? $period['date_from']));
            } elseif (is_string($period) && $period !== '') {
                $periods[] = $period;
            }

            $snapshot = $item['snapshot_at'] ?? null;

            if (is_string($snapshot) && $snapshot !== '') {
                $snapshots[] = $this->formatSnapshot($snapshot);
            }
        }

        $sources = array_values(array_unique(array_filter($sources)));
        $periods = array_values(array_unique(array_filter($periods)));
        $snapshots = array_values(array_unique(array_filter($snapshots)));

        $parts = [];

        if ($sources !== []) {
            $parts[] = 'Sumber: '.implode(', ', $sources);
        }

        if ($periods !== []) {
            $parts[] = 'Periode: '.implode(', ', $periods);
        }

        if ($snapshots !== []) {
            $parts[] = 'Snapshot: '.implode(', ', $snapshots);
        }

        if ($parts === []) {
            return '';
        }

        return "\n\n**Konteks data:** ".implode('; ', $parts).'.';
    }

    private function formatSnapshot(string $value): string
    {
        try {
            return CarbonImmutable::parse($value)
                ->setTimezone((string) config('app.timezone'))
                ->toDateTimeString();
        } catch (Throwable) {
            return $value;
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
                'stream' => route('ai-assistant.stream'),
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
                'content' => $this->historyContent((string) $message['content']),
            ])
            ->values()
            ->all();
    }

    /**
     * Riwayat tidak perlu membawa baris Sumber dan Konteks data karena selalu
     * ditambahkan ulang pada jawaban baru dan hanya memperbesar prompt.
     */
    private function historyContent(string $content): string
    {
        $lines = preg_split('/\r?\n/', $content) ?: [];

        $kept = array_filter($lines, function (string $line): bool {
            $trimmed = trim($line);

            return ! str_starts_with($trimmed, '**Sumber:**')
                && ! str_starts_with($trimmed, '**Konteks data:**');
        });

        return trim(implode("\n", $kept));
    }

    /**
     * Saat model dihentikan di tengah langkah, sebagian model menuliskan
     * pemanggilan tool sebagai teks markup. Buang mulai penanda pertama.
     */
    private function stripToolMarkup(string $answer): string
    {
        $markers = ['<｜DSML｜', '<|DSML|', '<｜｜tool▁calls', '<tool_calls', '<function_call'];

        foreach ($markers as $marker) {
            $position = mb_strpos($answer, $marker);

            if ($position !== false) {
                $answer = mb_substr($answer, 0, $position);
            }
        }

        return trim($answer);
    }

    private function toolResultsContext(iterable $toolResults): string
    {
        $sections = [];

        foreach ($toolResults as $result) {
            if (! $result->successful()) {
                continue;
            }

            $arguments = json_encode($result->arguments, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';

            $sections[] = 'Tool '.$result->name.' ('.$arguments."):\n".mb_substr($result->text(), 0, 6000);
        }

        return $sections === []
            ? 'Tidak ada hasil tool yang berhasil.'
            : "Data tool:\n\n".implode("\n\n", $sections);
    }

    /**
     * @param  list<array{name: string, arguments: array}>  $toolCalls
     * @return list<array{name: string, arguments: array, references: list<array{label: string, url: string}>, url: string|null}>
     */
    private function attachReferences(array $toolCalls, ?User $user): array
    {
        $registry = app(AssistantReferenceRegistry::class);

        return array_map(function (array $call) use ($registry, $user): array {
            $references = $registry->forTool(
                (string) ($call['name'] ?? ''),
                (array) ($call['arguments'] ?? []),
                $user,
            );

            return [
                ...$call,
                'references' => $references,
                'url' => $references[0]['url'] ?? null,
            ];
        }, $toolCalls);
    }

    /**
     * @param  list<array{name: string, arguments: array, references?: list<array{label: string, url: string}>}>  $toolCalls
     */
    private function appendSources(string $answer, array $toolCalls): string
    {
        $sources = [];

        foreach ($toolCalls as $call) {
            $references = $call['references'] ?? [];

            if ($references !== []) {
                foreach ($references as $reference) {
                    $sources[] = '['.$reference['label'].']('.$reference['url'].')';
                }

                continue;
            }

            foreach ($this->sourceLabels([$call]) as $label) {
                $sources[] = $label;
            }
        }

        $sources = array_values(array_unique($sources));

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
                'query_data' => [match ($arguments['connection'] ?? 'operational') {
                    'backoffice' => 'Data backoffice VeeStore',
                    'pos' => 'Data POS VeeStore',
                    default => 'Data operasional VeeStore',
                }],
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
