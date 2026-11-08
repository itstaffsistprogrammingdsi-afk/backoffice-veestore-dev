<?php

namespace Tests\Feature\Reports;

use App\Models\Customer;
use App\Models\Store;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class CustomerRegistrationReferralTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_customer_profiles_expose_registration_date_channel_and_store(): void
    {
        $suffix = Str::lower(Str::random(8));

        $store = Store::query()->create([
            'name' => 'Store Registrasi '.$suffix,
            'address' => 'Jl. Registrasi '.$suffix,
        ]);

        $customer = Customer::query()->create([
            'name' => 'Customer Registrasi '.$suffix,
            'email' => 'customer-'.$suffix.'@example.test',
            'wa_number' => '628'.random_int(100000000, 999999999),
            'registration_channel' => 'store',
            'registration_source_id' => $store->id,
        ]);

        $this->actingAs($this->admin())
            ->get(route('reports.crm.customer-profiles', ['q' => $customer->name]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/CrmCustomers')
                ->where('customers.data.0.registration_channel', 'store')
                ->where('customers.data.0.registration_source_name', $store->name)
                ->has('customers.data.0.created_at'));
    }

    public function test_referral_report_can_filter_by_referrer_registration_store(): void
    {
        $suffix = Str::lower(Str::random(8));

        $store = Store::query()->create([
            'name' => 'Store Referral '.$suffix,
            'address' => 'Jl. Referral '.$suffix,
        ]);

        $referrer = Customer::query()->create([
            'name' => 'Referrer '.$suffix,
            'wa_number' => '628'.random_int(100000000, 999999999),
            'registration_channel' => 'store',
            'registration_source_id' => $store->id,
        ]);

        $referee = Customer::query()->create([
            'name' => 'Referee '.$suffix,
            'wa_number' => '628'.random_int(100000000, 999999999),
        ]);

        DB::table('referrals')->insert([
            'id' => (string) Str::uuid(),
            'referrer_id' => $referrer->id,
            'referee_id' => $referee->id,
            'code_used' => 'REF-'.$suffix,
            'status' => 'registered',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->followingRedirects()
            ->get(route('monitoring.referral-score-report.index', ['store_id' => $store->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Monitoring/Report')
                ->where('data.filters.store_id', $store->id)
                ->has('data.stores')
                ->has('data.referralRows')
                ->where('data.referralRows.data.0.referrer_branch_name', $store->name));
    }
}
