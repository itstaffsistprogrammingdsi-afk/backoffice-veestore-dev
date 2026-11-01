<?php

namespace Tests\Feature\BranchOperations;

use Illuminate\Foundation\Testing\DatabaseTransactions;
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
}
