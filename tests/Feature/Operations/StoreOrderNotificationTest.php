<?php

namespace Tests\Feature\Operations;

use App\Mail\StoreOrderStatusMail;
use App\Models\Store;
use App\Models\StoreOrder;
use App\Services\StoreOrderNotifier;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class StoreOrderNotificationTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_requested_order_email_is_sent_once_to_backoffice_recipients(): void
    {
        Mail::fake();
        config(['backoffice.order_notifications.backoffice_emails' => ['ho@example.test']]);

        $order = $this->storeOrder();

        $notifier = app(StoreOrderNotifier::class);

        $this->assertTrue($notifier->requested($order));
        $this->assertFalse($notifier->requested($order->fresh()));

        Mail::assertSent(StoreOrderStatusMail::class, 1);
        Mail::assertSent(StoreOrderStatusMail::class, function (StoreOrderStatusMail $mail): bool {
            return $mail->hasTo('ho@example.test') && $mail->status === 'requested';
        });
    }

    public function test_approved_order_email_is_sent_to_store_contact(): void
    {
        Mail::fake();

        $order = $this->storeOrder('toko@example.test');

        $sent = app(StoreOrderNotifier::class)->statusChanged($order, 'approved', [$order->order_number]);

        $this->assertTrue($sent);

        Mail::assertSent(StoreOrderStatusMail::class, function (StoreOrderStatusMail $mail): bool {
            return $mail->hasTo('toko@example.test')
                && $mail->status === 'approved'
                && $mail->orderNumbers !== [];
        });
    }

    public function test_reject_action_notifies_store_contact(): void
    {
        Mail::fake();

        $order = $this->storeOrder('toko-reject@example.test');

        $this->actingAs($this->admin())
            ->patch(route('operations.store-orders.reject', $order))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        Mail::assertSent(StoreOrderStatusMail::class, function (StoreOrderStatusMail $mail): bool {
            return $mail->hasTo('toko-reject@example.test') && $mail->status === 'rejected';
        });
    }

    private function storeOrder(?string $contactEmail = null): StoreOrder
    {
        $suffix = Str::lower(Str::random(8));

        $store = Store::query()->create([
            'name' => 'Store Notifikasi '.$suffix,
            'address' => 'Jl. Notifikasi '.$suffix,
            'contact_email' => $contactEmail,
        ]);

        return StoreOrder::query()->create([
            'order_number' => 'TEST-MAIL-'.Str::uuid(),
            'store_id' => $store->id,
            'status' => 'requested',
            'requested_at' => now(),
            'created_by' => $this->admin()->id,
            'date' => now()->toDateString(),
        ]);
    }
}
