<?php

namespace App\Http\Controllers\AIAnalytics;

use App\Ai\Agents\SalesAnalyst;
use App\Domain\Analytics\Queries\AnalyticsDashboardQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\AIAnalytics\AskAssistantRequest;
use Inertia\Inertia;
use Throwable;

class AIAssistantController extends Controller
{
    public function index(AnalyticsDashboardQuery $query)
    {
        $latestRun = $query->latestCompletedRun();

        return Inertia::render('Assistant/Index', [
            'latestRun' => $latestRun,
            'summary' => $query->summary($latestRun),
            'suggestions' => [
                'Ringkas kondisi forecast terakhir.',
                'Toko mana yang paling berisiko kehabisan stok?',
                'Produk apa yang perlu restock paling banyak?',
                'Tampilkan rekomendasi dengan risiko kritis.',
            ],
            'prompt' => session('ai_prompt'),
            'answer' => session('answer'),
            'error' => session('ai_error'),
            'urls' => [
                'index' => route('ai-assistant.index'),
                'ask' => route('ai-assistant.ask'),
            ],
        ]);
    }

    public function ask(AskAssistantRequest $request)
    {
        $prompt = $request->validated('prompt');

        try {
            $answer = (string) (new SalesAnalyst)->prompt($prompt);

            return redirect()
                ->route('ai-assistant.index')
                ->with('answer', $answer)
                ->with('ai_prompt', $prompt);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('ai-assistant.index')
                ->with('ai_prompt', $prompt)
                ->with('ai_error', 'Asisten AI belum dapat dihubungi. Periksa konfigurasi provider dan API key.');
        }
    }
}
