<?php

namespace App\Services;

use App\Mail\BrandListingStatusMail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class BrandListingNotifier
{
    public function submitted(string $id): bool
    {
        $payload = $this->payload($id);

        if ($payload === null) {
            return false;
        }

        return $this->sendOnce($id, 'submitted', $this->backofficeRecipients(), $payload);
    }

    public function decided(string $id, string $status): bool
    {
        $payload = $this->payload($id);

        if ($payload === null) {
            return false;
        }

        $recipients = [];

        if (! empty($payload['requested_by'])) {
            $email = User::query()->whereKey($payload['requested_by'])->value('email');

            if ($email) $recipients[] = $email;
        }

        if (! empty($payload['store_id'])) {
            $email = DB::table('stores')->where('id', $payload['store_id'])->value('contact_email');

            if ($email) $recipients[] = $email;
        }

        if (! empty($payload['contact_email'])) {
            $recipients[] = $payload['contact_email'];
        }

        return $this->sendOnce($id, $status, $recipients, $payload);
    }

    private function sendOnce(string $id, string $status, array $recipients, array $payload): bool
    {
        $emails = collect($recipients)
            ->map(fn ($email) => trim((string) $email))
            ->filter(fn (string $email) => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->unique()
            ->values()
            ->all();

        if ($emails === []) {
            return false;
        }

        $cacheKey = 'brand-listing-mail:'.$id.':'.$status;

        if (! Cache::add($cacheKey, true, now()->addDays(30))) {
            return false;
        }

        try {
            Mail::to($emails)->send(new BrandListingStatusMail($payload, $status));

            return true;
        } catch (Throwable $exception) {
            Cache::forget($cacheKey);

            report($exception);

            return false;
        }
    }

    private function payload(string $id): ?array
    {
        $partner = DB::table('partners')
            ->where('id', $id)
            ->where('billing_cycle', 'brand_listing')
            ->whereNull('deleted_at')
            ->first(['id', 'name', 'status', 'created_at']);

        if (! $partner) {
            return null;
        }

        $value = DB::table('app_settings')->where('key', 'brand_listing:'.$id)->value('value');
        $detail = is_string($value) ? json_decode($value, true) : (is_array($value) ? $value : []);
        $detail = is_array($detail) ? $detail : [];

        $storeName = ! empty($detail['store_id'])
            ? DB::table('stores')->where('id', $detail['store_id'])->value('name')
            : null;
        $requesterName = ! empty($detail['requested_by'])
            ? User::query()->whereKey($detail['requested_by'])->value('name')
            : null;

        return [
            'id' => (string) $partner->id,
            'brand_name' => (string) $partner->name,
            'status' => (string) $partner->status,
            'store_id' => $detail['store_id'] ?? null,
            'store_name' => $storeName,
            'requested_by' => $detail['requested_by'] ?? null,
            'requested_by_name' => $requesterName,
            'contact_name' => $detail['contact_name'] ?? null,
            'contact_phone' => $detail['contact_phone'] ?? null,
            'contact_email' => $detail['contact_email'] ?? null,
            'notes' => $detail['notes'] ?? null,
            'review_notes' => $detail['review_notes'] ?? null,
            'created_at' => $partner->created_at,
        ];
    }

    private function backofficeRecipients(): array
    {
        $configured = config('backoffice.order_notifications.brand_listing_emails', []);

        if (is_string($configured)) {
            $configured = explode(',', $configured);
        }

        return is_array($configured) ? $configured : [];
    }
}
