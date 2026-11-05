<?php

namespace Tests\Unit;

use App\Domain\Ecommerce\Support\DokuPaymentChannelResolver;
use PHPUnit\Framework\TestCase;

class DokuPaymentChannelResolverTest extends TestCase
{
    public function test_it_resolves_qris_from_doku_payload(): void
    {
        $payload = json_encode([
            'service' => ['id' => 'QRIS', 'name' => 'QRIS'],
            'acquirer' => ['id' => '93600899', 'name' => 'DOKU'],
            'channel' => ['id' => 'QRIS_DOKU', 'name' => 'QRIS-DOKU'],
        ]);

        $this->assertSame(
            'DOKU - QRIS',
            DokuPaymentChannelResolver::resolve('doku_checkout', 'DOKU Checkout', null, $payload)
        );
    }

    public function test_it_resolves_virtual_account_and_emoney_channels(): void
    {
        $virtualAccount = json_encode([
            'service' => ['id' => 'VIRTUAL_ACCOUNT'],
            'acquirer' => ['id' => 'BCA', 'name' => 'BCA'],
            'channel' => ['id' => 'VIRTUAL_ACCOUNT_BCA'],
        ]);
        $emoney = json_encode([
            'service' => ['id' => 'EMONEY'],
            'acquirer' => ['id' => 'DANA'],
            'channel' => ['id' => 'EMONEY_DANA'],
        ]);

        $this->assertSame(
            'DOKU - BCA VA',
            DokuPaymentChannelResolver::resolve('doku_checkout', 'DOKU Checkout', null, $virtualAccount)
        );
        $this->assertSame(
            'DOKU - DANA',
            DokuPaymentChannelResolver::resolve('doku_checkout', 'DOKU Checkout', null, $emoney)
        );
    }

    public function test_it_uses_saved_detail_method_name_for_older_doku_payments(): void
    {
        $this->assertSame(
            'Doku - QRIS',
            DokuPaymentChannelResolver::resolve('doku_checkout', 'DOKU Checkout', 'Doku - QRIS', null)
        );
    }

    public function test_it_keeps_checkout_label_when_doku_channel_is_not_available_yet(): void
    {
        $this->assertSame(
            'DOKU Checkout',
            DokuPaymentChannelResolver::resolve('doku_checkout', 'DOKU Checkout', null, null)
        );
    }

    public function test_it_does_not_label_non_doku_payments_as_doku(): void
    {
        $payload = json_encode(['channel' => ['id' => 'QRIS_DOKU']]);

        $this->assertSame(
            'GoPay',
            DokuPaymentChannelResolver::resolve('gopay', 'GoPay', null, $payload)
        );
    }
}
