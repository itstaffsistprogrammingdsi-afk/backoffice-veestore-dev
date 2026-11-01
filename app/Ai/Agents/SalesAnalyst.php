<?php

namespace App\Ai\Agents;

use App\Ai\Tools\LatestForecastSummary;
use App\Ai\Tools\StockRiskRecommendations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\ProviderTool;
use Stringable;

class SalesAnalyst implements Agent, HasTools
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return implode(' ', [
            'Anda adalah analis penjualan dan stok untuk backoffice VeeStore.',
            'Jawab dalam Bahasa Indonesia yang jelas dan ringkas.',
            'Gunakan tool yang tersedia untuk mengambil data forecast dan rekomendasi restock.',
            'Jangan mengarang angka, nama toko, atau nama produk.',
            'Sebutkan periode training dan model run yang dipakai.',
            'Jika data belum tersedia atau tidak cukup, katakan dengan jujur.',
            'Fokus pada keputusan operasional: produk mana yang perlu restock, di toko mana, dan seberapa mendesak.',
        ]);
    }

    /**
     * @return list<Agent|Tool|ProviderTool>
     */
    public function tools(): iterable
    {
        return [
            app(LatestForecastSummary::class),
            app(StockRiskRecommendations::class),
        ];
    }
}
