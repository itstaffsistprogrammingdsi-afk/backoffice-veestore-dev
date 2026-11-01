<?php

namespace App\Domain\Analytics\Actions;

use App\Models\Analytics\StockRecommendation;
use App\Models\User;

class ReviewStockRecommendation
{
    public function handle(StockRecommendation $recommendation, array $data, User $reviewer): StockRecommendation
    {
        $recommendation->update([
            'status' => $data['status'],
            'review_notes' => $data['review_notes'] ?? null,
            'reviewed_by' => $reviewer->getKey(),
            'reviewed_at' => now(),
        ]);

        return $recommendation->refresh();
    }
}
