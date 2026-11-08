<?php

namespace App\Services;

use App\Mail\StoreOrderStatusMail;
use App\Models\StoreOrder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Throwable;

class StoreOrderNotifier
{
    public function requested(StoreOrder $order): bool
    {
        $recipients = $this->backofficeRecipients();

        if ($recipients === []) {
            return false;
        }

        return $this->sendOnce(
            $order,
            'requested',
            $recipients,
            'requested'
        );
    }

    public function statusChanged(StoreOrder $order, string $status, array $orderNumbers = []): bool
    {
        $recipients = $this->storeRecipients($order);

        if ($recipients === []) {
            return false;
        }

        return $this->sendOnce(
            $order,
            'status-'.$status,
            $recipients,
            $status,
            $orderNumbers
        );
    }

    private function sendOnce(
        StoreOrder $order,
        string $key,
        array $recipients,
        string $status,
        array $orderNumbers = []
    ): bool {
        $cacheKey = 'store-order-mail:'.$order->getKey().':'.$key;

        if (! Cache::add($cacheKey, true, now()->addDays(30))) {
            return false;
        }

        try {
            Mail::to($recipients)->send(new StoreOrderStatusMail($order, $status, $orderNumbers));

            return true;
        } catch (Throwable $exception) {
            Cache::forget($cacheKey);

            report($exception);

            return false;
        }
    }

    /** @return array<int, string> */
    private function backofficeRecipients(): array
    {
        $configured = config('backoffice.order_notifications.backoffice_emails', []);

        if (is_string($configured)) {
            $configured = explode(',', $configured);
        }

        return $this->validEmails($configured);
    }

    /** @return array<int, string> */
    private function storeRecipients(StoreOrder $order): array
    {
        $order->loadMissing('store');

        return $this->validEmails([$order->store?->contact_email]);
    }

    /** @return array<int, string> */
    private function validEmails(array $emails): array
    {
        return collect($emails)
            ->map(fn ($email) => trim((string) $email))
            ->filter(fn (string $email) => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->unique()
            ->values()
            ->all();
    }
}
