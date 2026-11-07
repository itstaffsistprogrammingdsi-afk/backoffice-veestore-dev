<?php

namespace App\Http\Controllers;

use App\Services\BranchOperationNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationStatusController extends Controller
{
    public function summary(
        BranchOperationNotificationService $notificationService
    ): JsonResponse {
        try {
            return response()->json([
                'success' => true,
                'data' => $notificationService->summary(),
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data notifikasi.',
            ], 500);
        }
    }

    public function storeReturnStatus(
        BranchOperationNotificationService $notificationService
    ): JsonResponse {
        $summary = $notificationService->summary();
        $count = (int) ($summary['menu_counts']['branch-retur-cabang'] ?? 0);

        return response()->json([
            'success' => true,
            'data' => [
                'count' => $count,
                'has_notification' => $count > 0,
            ],
        ]);
    }

    public function markRead(
        Request $request,
        BranchOperationNotificationService $notificationService
    ): JsonResponse {
        $payload = $request->validate([
            'key' => ['required', 'string', 'max:100'],
        ]);

        $notificationService->markRead($payload['key']);

        return response()->json(['success' => true]);
    }

    /** Mark a notification as read before opening its internal destination. */
    public function open(
        Request $request,
        string $key,
        BranchOperationNotificationService $notificationService
    ): RedirectResponse {
        $notificationService->markRead($key);

        $redirect = (string) $request->query('redirect', route('reports.business-overview.index'));
        $parsed = parse_url($redirect);
        $isRelative = str_starts_with($redirect, '/');
        $isSameHost = isset($parsed['host']) && $parsed['host'] === $request->getHost();

        if (! $isRelative && ! $isSameHost) {
            $redirect = route('reports.business-overview.index');
        }

        return redirect()->to($redirect);
    }
}
