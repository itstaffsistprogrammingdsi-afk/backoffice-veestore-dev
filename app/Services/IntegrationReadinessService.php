<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class IntegrationReadinessService
{
    public function all(): array
    {
        return [
            'doku' => $this->provider('doku'),
            'biteship' => $this->provider('biteship'),
        ];
    }

    public function provider(string $key): array
    {
        $config = (array) config("integrations.{$key}", []);
        $required = (array) ($config['required'] ?? []);

        $checks = [];

        foreach ($required as $field) {
            $checks[$field] = filled($config[$field] ?? null);
        }

        $missing = array_keys(array_filter($checks, fn (bool $configured): bool => ! $configured));
        $configured = $missing === [];
        $enabled = (bool) ($config['enabled'] ?? false);

        $status = match (true) {
            ! $enabled => 'disabled',
            ! $configured => 'missing_credentials',
            default => 'ready',
        };

        return [
            'key' => $key,
            'label' => (string) ($config['label'] ?? ucfirst($key)),
            'enabled' => $enabled,
            'configured' => $configured,
            'status' => $status,
            'status_label' => match ($status) {
                'ready' => 'Siap',
                'missing_credentials' => 'Kredensial belum lengkap',
                default => 'Belum diaktifkan',
            },
            'environment' => (string) ($config['environment'] ?? 'sandbox'),
            'base_url' => (string) ($config['base_url'] ?? '-'),
            'webhook_url' => $this->webhookUrl($key, $config),
            'docs_url' => (string) ($config['docs_url'] ?? ''),
            'checks' => $checks,
            'missing' => $missing,
            'activity' => $key === 'doku' ? $this->dokuActivity() : $this->biteshipActivity(),
        ];
    }

    private function webhookUrl(string $key, array $config): ?string
    {
        $configured = trim((string) ($config['webhook_url'] ?? ''));

        if ($configured !== '') {
            return $configured;
        }

        $appUrl = rtrim((string) config('app.url', ''), '/');

        if ($appUrl === '') {
            return null;
        }

        return match ($key) {
            'doku' => $appUrl.'/api/v1/payments/doku/notification',
            'biteship' => $appUrl.'/api/v1/shipments/biteship/webhook',
            default => null,
        };
    }

    private function dokuActivity(): array
    {
        $since = now()->subDays(30);

        $query = DB::table('payment_details as detail')
            ->join('payment_methods as method', 'method.id', '=', 'detail.payment_method_id')
            ->where('method.name', 'ILIKE', '%doku%');

        $lastAt = (clone $query)->max('detail.created_at');
        $total = (clone $query)->where('detail.created_at', '>=', $since)->count();
        $success = (clone $query)->where('detail.created_at', '>=', $since)->where('detail.status', 'success')->count();

        return [
            'label' => 'Transaksi Doku',
            'last_activity_at' => $lastAt,
            'total_last_30_days' => (int) $total,
            'success_last_30_days' => (int) $success,
            'failed_last_30_days' => max(0, (int) $total - (int) $success),
        ];
    }

    private function biteshipActivity(): array
    {
        $since = now()->subDays(30);

        $query = DB::table('order_shipments')
            ->where('provider', 'biteship');

        $lastAt = (clone $query)->max('updated_at');
        $total = (clone $query)->where('updated_at', '>=', $since)->count();
        $failed = (clone $query)->where('updated_at', '>=', $since)->where('integration_status', 'failed')->count();
        $pending = (clone $query)->where('updated_at', '>=', $since)->whereIn('integration_status', ['creating', 'unknown'])->count();

        return [
            'label' => 'Pengiriman Biteship',
            'last_activity_at' => $lastAt,
            'total_last_30_days' => (int) $total,
            'success_last_30_days' => max(0, (int) $total - (int) $failed),
            'failed_last_30_days' => (int) $failed,
            'pending_last_30_days' => (int) $pending,
        ];
    }
}
