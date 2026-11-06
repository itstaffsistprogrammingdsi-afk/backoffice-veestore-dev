<?php

namespace Tests\Feature\BranchOperations;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class BranchOperationsPagesTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_branch_operation_pages_use_inertia(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('branch-operations.request-label.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('BranchOperations/LabelPrintRequests')
                ->has('rows')
                ->has('detailItems')
                ->has('stores')
                ->has('users')
                ->has('statuses')
                ->has('canDecide')
                ->has('canPrint')
                ->has('filters')
                ->has('urls'));

        $this->actingAs($admin)
            ->get(route('branch-operations.request-voucher.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('BranchOperations/VoucherRequests')
                ->has('rows')
                ->has('detailItems')
                ->has('stores')
                ->has('statuses')
                ->has('filters')
                ->has('urls'));
    }

    public function test_label_reprint_requires_approval_and_records_print_history(): void
    {
        $admin = $this->admin();
        $storeItem = DB::table('store_items')->first();

        $this->assertNotNull($storeItem);

        $requestId = (string) Str::uuid();

        DB::table('store_label_print_requests')->insert([
            'id' => $requestId,
            'store_id' => $storeItem->store_id,
            'requested_by' => $admin->id,
            'status' => 'pending',
            'reason' => 'Label rusak untuk uji cetak ulang',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('store_label_print_request_items')->insert([
            'id' => (string) Str::uuid(),
            'label_print_request_id' => $requestId,
            'item_id' => $storeItem->item_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $requestRow = DB::table('store_label_print_requests')
            ->where('id', $requestId)
            ->first();

        $this->assertNotNull($requestRow);
        $this->assertSame('pending', $requestRow->status);

        $this->actingAs($admin)
            ->put(route('management.request-labels.approve', ['id' => $requestRow->id]))
            ->assertRedirect(route('management.request-labels.index'));

        $this->actingAs($admin)
            ->post(route('branch-operations.request-label.print', ['id' => $requestRow->id]))
            ->assertOk()
            ->assertSee('Cetakan ke-', false);

        $this->assertSame(1, (int) DB::table('store_label_print_histories')
            ->where('label_print_request_id', $requestRow->id)
            ->where('item_id', $storeItem->item_id)
            ->max('print_index'));

        $this->actingAs($admin)
            ->post(route('branch-operations.request-label.print', ['id' => $requestRow->id]))
            ->assertOk()
            ->assertSee('Cetakan ke-2', false);

        $this->assertSame(2, (int) DB::table('store_label_print_histories')
            ->where('label_print_request_id', $requestRow->id)
            ->where('item_id', $storeItem->item_id)
            ->max('print_index'));

        $this->assertSame('printed', DB::table('store_label_print_requests')->where('id', $requestRow->id)->value('status'));
    }
}
