<?php

namespace Tests\Feature\Operations;

use App\Mail\BirthdayGreetingMail;
use App\Models\Customer;
use App\Models\Voucher;
use App\Models\VoucherCode;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class BirthdayVoucherGreetingTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    /** @var array<int, string|null> */
    protected $connectionsToTransact = [null, 'immostore_backoffice'];

    public function test_demographics_page_exposes_active_vouchers(): void
    {
        $voucher = $this->voucher(['distribution_type' => 'public']);

        $this->actingAs($this->admin())
            ->get(route('reports.crm.demographics'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/CrmDemographics')
                ->has('birthdayGreeting.vouchers')
                ->where('birthdayGreeting.vouchers', fn ($vouchers) => collect($vouchers)
                    ->contains(fn (array $item) => $item['id'] === $voucher->id)));
    }

    public function test_public_voucher_is_attached_to_the_birthday_email(): void
    {
        Mail::fake();

        $customer = $this->birthdayCustomer();
        $voucher = $this->voucher(['distribution_type' => 'public']);

        $this->actingAs($this->admin())
            ->post(route('operations.customers.demographics.birthday-greeting'), [
                'customer_ids' => [$customer->id],
                'subject' => 'Selamat Ulang Tahun, {nama}!',
                'message' => 'Halo {nama}!',
                'voucher_id' => $voucher->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertSent(BirthdayGreetingMail::class, function (BirthdayGreetingMail $mail) use ($customer, $voucher) {
            return $mail->hasTo($customer->email)
                && $mail->voucher['code'] === $voucher->code
                && $mail->voucher['name'] === $voucher->name
                && str_contains($mail->voucher['value_label'], '50.000');
        });
    }

    public function test_unique_voucher_code_is_reserved_once_per_recipient(): void
    {
        Mail::fake();

        $voucher = $this->voucher([
            'distribution_type' => 'unique_code',
            'usage_limit' => 2,
        ]);

        $codes = collect([
            VoucherCode::query()->create([
                'voucher_id' => $voucher->id,
                'code' => 'BDAY-CODE-ONE',
                'is_active' => true,
                'expires_at' => now()->addMonth(),
            ]),
            VoucherCode::query()->create([
                'voucher_id' => $voucher->id,
                'code' => 'BDAY-CODE-TWO',
                'is_active' => true,
                'expires_at' => now()->addMonth(),
            ]),
        ]);

        $first = $this->birthdayCustomer();
        $second = $this->birthdayCustomer();

        $this->actingAs($this->admin())
            ->post(route('operations.customers.demographics.birthday-greeting'), [
                'customer_ids' => [$first->id, $second->id],
                'subject' => 'Selamat Ulang Tahun, {nama}!',
                'message' => 'Halo {nama}!',
                'voucher_id' => $voucher->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $sentCodes = [];

        Mail::assertSent(BirthdayGreetingMail::class, function (BirthdayGreetingMail $mail) use ($codes, &$sentCodes) {
            $sentCodes[] = $mail->voucher['code'];

            return $codes->pluck('code')->contains($mail->voucher['code']);
        });

        $this->assertCount(2, $sentCodes);
        $this->assertCount(2, array_unique($sentCodes));

        $deliveries = DB::connection('immostore_backoffice')
            ->table('birthday_voucher_deliveries')
            ->where('voucher_id', $voucher->id)
            ->get();

        $this->assertCount(2, $deliveries);
        $this->assertTrue($deliveries->every(fn ($row) => $row->sent_at !== null));
    }

    public function test_birthday_voucher_is_rejected_when_unique_codes_are_insufficient(): void
    {
        Mail::fake();

        $voucher = $this->voucher([
            'distribution_type' => 'unique_code',
            'usage_limit' => 1,
        ]);

        VoucherCode::query()->create([
            'voucher_id' => $voucher->id,
            'code' => 'BDAY-ONLY-ONE',
            'is_active' => true,
            'expires_at' => now()->addMonth(),
        ]);

        $customers = [$this->birthdayCustomer(), $this->birthdayCustomer()];

        $this->actingAs($this->admin())
            ->post(route('operations.customers.demographics.birthday-greeting'), [
                'customer_ids' => collect($customers)->pluck('id')->all(),
                'subject' => 'Selamat Ulang Tahun, {nama}!',
                'message' => 'Halo {nama}!',
                'voucher_id' => $voucher->id,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('voucher_id');

        Mail::assertNothingSent();
    }

    public function test_inactive_voucher_cannot_be_attached(): void
    {
        Mail::fake();

        $voucher = $this->voucher(['is_active' => false]);

        $this->actingAs($this->admin())
            ->post(route('operations.customers.demographics.birthday-greeting'), [
                'customer_ids' => [$this->birthdayCustomer()->id],
                'subject' => 'Selamat Ulang Tahun, {nama}!',
                'message' => 'Halo {nama}!',
                'voucher_id' => $voucher->id,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('voucher_id');

        Mail::assertNothingSent();
    }

    private function birthdayCustomer(array $attributes = []): Customer
    {
        return Customer::query()->create(array_merge([
            'name' => 'Customer Ulang Tahun',
            'email' => 'birthday-'.uniqid().'@example.test',
            'wa_number' => '628'.random_int(100000000, 999999999),
            'date_of_birth' => now()->startOfMonth()->addDays(9)->toDateString(),
        ], $attributes));
    }

    private function voucher(array $attributes = []): Voucher
    {
        return Voucher::query()->create(array_merge([
            'code' => 'VCR-BDAY-'.uniqid(),
            'name' => 'Voucher Ulang Tahun',
            'description' => 'Voucher spesial untuk pelanggan ulang tahun.',
            'type' => 'fixed',
            'value' => 50000,
            'min_purchase' => 100000,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
            'usage_count' => 0,
            'is_active' => true,
            'distribution_type' => 'public',
        ], $attributes));
    }
}
