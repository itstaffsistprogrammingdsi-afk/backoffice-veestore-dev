<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class StoreReturnRequested implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function broadcastOn(): array
    {
        return [new Channel('store-return-notifications')];
    }

    public function broadcastAs(): string
    {
        return 'store-return.requested';
    }

    public function broadcastWith(): array
    {
        $count = DB::table('store_returns')
            ->where('status', 'requested')
            ->count();

        return [
            'count' => $count,
            'title' => 'Retur Cabang: Requested',
            'subtitle' => "{$count} menunggu approval",
            'time' => now()->format('d M H:i'),
            'url' => route('reports.retur-cabang.index', ['status' => 'requested']),
        ];
    }
}
