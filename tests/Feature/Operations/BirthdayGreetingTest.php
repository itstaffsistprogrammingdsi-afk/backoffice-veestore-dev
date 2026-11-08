<?php

namespace Tests\Feature\Operations;

use App\Mail\BirthdayGreetingMail;
use App\Models\Customer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class BirthdayGreetingTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_operations_demographics_redirects_to_reports_demographics(): void
    {
        $this->actingAs($this->admin())
            ->get(route('operations.customers.demographics'))
            ->assertRedirect(route('reports.crm.demographics'));

        $this->actingAs($this->admin())
            ->get(route('reports.crm.demographics'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/CrmDemographics')
                ->has('birthdayGreeting.template.subject')
                ->has('birthdayGreeting.template.message')
                ->has('birthdayGreeting.template.placeholders')
                ->where('urls.birthdayGreeting', route('reports.crm.demographics.birthday-greeting')));
    }

    public function test_birthday_greeting_can_be_sent_from_operations_page(): void
    {
        Mail::fake();

        $customer = $this->birthdayCustomer();

        $this->actingAs($this->admin())
            ->post(route('operations.customers.demographics.birthday-greeting'), [
                'customer_ids' => [$customer->id],
                'subject' => 'Selamat Ulang Tahun, {nama}!',
                'message' => 'Halo {nama}, selamat ulang tahun di bulan {bulan}.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertSent(BirthdayGreetingMail::class, function (BirthdayGreetingMail $mail) use ($customer) {
            return $mail->hasTo($customer->email)
                && $mail->mailSubject === 'Selamat Ulang Tahun, '.$customer->name.'!'
                && str_contains($mail->mailBody, 'Halo '.$customer->name.', selamat ulang tahun di bulan ');
        });
    }

    public function test_birthday_greeting_can_be_sent_from_reports_page(): void
    {
        Mail::fake();

        $customer = $this->birthdayCustomer();

        $this->actingAs($this->admin())
            ->post(route('reports.crm.demographics.birthday-greeting'), [
                'customer_ids' => [$customer->id],
                'subject' => 'Selamat Ulang Tahun, {nama}!',
                'message' => 'Halo {nama}!',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertSent(BirthdayGreetingMail::class, fn (BirthdayGreetingMail $mail) => $mail->hasTo($customer->email));
    }

    public function test_birthday_greeting_skips_customers_without_email(): void
    {
        Mail::fake();

        $customer = $this->birthdayCustomer(['email' => null]);

        $this->actingAs($this->admin())
            ->post(route('operations.customers.demographics.birthday-greeting'), [
                'customer_ids' => [$customer->id],
                'subject' => 'Selamat Ulang Tahun, {nama}!',
                'message' => 'Halo {nama}!',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

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
}
