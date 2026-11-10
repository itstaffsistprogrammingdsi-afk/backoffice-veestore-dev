<?php

namespace Tests\Feature\BranchOperations;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class VoucherPromoApprovalTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_promo_request_is_visible_with_type_and_terms(): void
    {
        $fixture = $this->fixture();

        $this->actingAs($this->admin())
            ->get(route('branch-operations.request-voucher.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('BranchOperations/VoucherRequests')
                ->where('rows.data', fn ($rows) => collect($rows)->contains(
                    fn ($row) => $row['id'] === $fixture['request_id']
                        && $row['request_type'] === 'promo'
                        && (string) $row['voucher_type'] === 'percentage'
                )));
    }

    public function test_promo_request_approval_activates_voucher_and_returns_code(): void
    {
        $fixture = $this->fixture();

        $this->actingAs($this->admin())
            ->put(route('branch-operations.request-voucher.approve', $fixture['request_id']))
            ->assertRedirect(route('branch-operations.request-voucher.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', fn (string $message): bool => str_contains($message, $fixture['voucher_code']));

        $request = DB::table('store_voucher_requests')->where('id', $fixture['request_id'])->first();
        $this->assertSame('approved', (string) $request->status);
        $this->assertSame((string) $this->admin()->id, (string) $request->approved_by);
        $this->assertNotNull($request->approved_at);

        $voucher = DB::table('vouchers')->where('id', $fixture['voucher_id'])->first();
        $this->assertTrue((bool) $voucher->is_active);
    }

    public function test_promo_request_rejection_keeps_voucher_inactive(): void
    {
        $fixture = $this->fixture();

        $this->actingAs($this->admin())
            ->put(route('branch-operations.request-voucher.reject', $fixture['request_id']))
            ->assertRedirect(route('branch-operations.request-voucher.index'))
            ->assertSessionHasNoErrors();

        $request = DB::table('store_voucher_requests')->where('id', $fixture['request_id'])->first();
        $this->assertSame('rejected', (string) $request->status);

        $voucher = DB::table('vouchers')->where('id', $fixture['voucher_id'])->first();
        $this->assertFalse((bool) $voucher->is_active);
    }

    private function fixture(): array
    {
        $admin = $this->admin();
        $storeId = DB::table('stores')->whereNull('deleted_at')->value('id');
        $productId = DB::table('products')->whereNull('deleted_at')->value('id');

        $this->assertNotNull($storeId);
        $this->assertNotNull($productId);

        $requestId = (string) Str::uuid();
        $voucherId = (string) Str::uuid();
        $voucherCode = 'PRM-TEST-'.Str::upper(Str::random(5));
        $requestNumber = 'VPR-TEST-'.Str::upper(Str::random(5));

        DB::table('store_voucher_requests')->insert([
            'id' => $requestId,
            'request_number' => $requestNumber,
            'store_id' => $storeId,
            'request_date' => now(),
            'status' => 'pending',
            'notes' => 'Fixture promo after repair',
            'created_by' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('store_voucher_request_details')->insert([
            'id' => (string) Str::uuid(),
            'store_voucher_request_id' => $requestId,
            'product_id' => $productId,
            'item_id' => null,
            'created_at' => now(),
        ]);

        DB::table('vouchers')->insert([
            'id' => $voucherId,
            'code' => $voucherCode,
            'name' => 'Promo Test After Repair',
            'description' => 'Fixture voucher promo',
            'type' => 'percentage',
            'value' => 20,
            'max_discount' => 50000,
            'min_purchase' => 100000,
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'usage_limit' => 5,
            'usage_count' => 0,
            'is_active' => false,
            'distribution_type' => 'public',
            'context' => 'store_checkout',
            'store_voucher_request_id' => $requestId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'request_id' => $requestId,
            'voucher_id' => $voucherId,
            'voucher_code' => $voucherCode,
        ];
    }
}
