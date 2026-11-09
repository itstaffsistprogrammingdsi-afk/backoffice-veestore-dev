<?php

namespace Tests\Unit;

use App\Domain\Payments\Support\EdcMetadata;
use Tests\TestCase;

class EdcMetadataTest extends TestCase
{
    public function test_it_parses_common_edc_fields_from_payload(): void
    {
        $metadata = EdcMetadata::parse(json_encode([
            'order' => [
                'terminal_id' => 'A01',
            ],
            'transaction' => [
                'approval_code' => '123456',
                'batch_number' => 'BATCH-9',
            ],
            'card' => [
                'pan' => '4111 1111 1111 1234',
                'type' => 'CREDIT',
            ],
            'acquirer' => [
                'name' => 'BCA',
            ],
            'issuer' => [
                'name' => 'Bank BCA',
            ],
            'reference_number' => 'REF-001',
            'merchant_id' => 'MID-001',
        ]));

        $this->assertSame('A01', $metadata['terminal_id']);
        $this->assertSame('123456', $metadata['approval_code']);
        $this->assertSame('BATCH-9', $metadata['batch_number']);
        $this->assertSame('************1234', $metadata['card_number']);
        $this->assertSame('CREDIT', $metadata['card_type']);
        $this->assertSame('REF-001', $metadata['reference_number']);
        $this->assertSame('BCA', $metadata['acquirer']);
        $this->assertSame('Bank BCA', $metadata['issuer']);
        $this->assertSame('MID-001', $metadata['merchant_id']);
    }

    public function test_it_returns_empty_metadata_for_invalid_payload(): void
    {
        $metadata = EdcMetadata::parse('not-json');

        $this->assertNull($metadata['terminal_id']);
        $this->assertNull($metadata['approval_code']);
        $this->assertNull($metadata['card_number']);
    }

    public function test_it_uses_debit_and_credit_mdr_rates(): void
    {
        $this->assertSame((float) config('backoffice.edc_bca.credit_mdr_percent', 2.0), EdcMetadata::mdrRate('BCA CC'));
        $this->assertSame((float) config('backoffice.edc_bca.debit_mdr_percent', 1.0), EdcMetadata::mdrRate('BCA DEBIT'));
        $this->assertSame(2000.0, EdcMetadata::estimateMdr(100000, 'BCA CC'));
        $this->assertSame(99000.0, EdcMetadata::estimateSettlement(100000, 'BCA DEBIT'));
    }
}
