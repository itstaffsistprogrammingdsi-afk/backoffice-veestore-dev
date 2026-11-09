<?php

namespace Tests\Feature\Management;

use App\Models\Store;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use App\Mail\BrandListingStatusMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class BrandListingTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_brand_listing_page_uses_inertia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('management.brand-listings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Management/BrandListings')
                ->has('rows')
                ->has('summary')
                ->has('stores')
                ->has('statusOptions')
                ->has('filters')
                ->where('canDecide', true)
                ->has('urls'));
    }

    public function test_brand_listing_can_be_submitted_with_price_list(): void
    {
        Storage::fake('public');
        Mail::fake();
        config(['backoffice.order_notifications.brand_listing_emails' => ['ho-brand@example.test']]);

        $store = $this->store(true);
        $brandName = 'Brand Uji '.Str::random(6);
        $file = UploadedFile::fake()->create('price-list.pdf', 120, 'application/pdf');

        $this->actingAs($this->admin())
            ->post(route('management.brand-listings.store'), [
                'store_id' => $store->id,
                'brand_name' => $brandName,
                'contact_name' => 'Kontak Brand',
                'contact_phone' => '08123456789',
                'contact_email' => 'brand@example.test',
                'price_list' => $file,
                'notes' => 'Brand baru untuk diuji.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $partner = DB::table('partners')->where('name', $brandName)->first();

        $this->assertNotNull($partner);
        $this->assertSame('pending', $partner->status);
        $this->assertSame('brand_listing', $partner->billing_cycle);

        $detail = json_decode((string) DB::table('app_settings')->where('key', 'brand_listing:'.$partner->id)->value('value'), true);

        $this->assertSame($store->id, $detail['store_id']);
        $this->assertSame('Kontak Brand', $detail['contact_name']);
        $this->assertNotNull($detail['price_list_path']);
        Storage::disk('public')->assertExists($detail['price_list_path']);

        Mail::assertSent(BrandListingStatusMail::class, function (BrandListingStatusMail $mail): bool {
            return $mail->hasTo('ho-brand@example.test') && $mail->status === 'submitted';
        });
    }

    public function test_brand_listing_can_be_approved_and_creates_brand(): void
    {
        Mail::fake();

        $brandName = 'Brand Approval '.Str::random(6);
        $id = $this->brandListing($brandName);

        $this->actingAs($this->admin())
            ->put(route('management.brand-listings.approve', ['id' => $id]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('approved', DB::table('partners')->where('id', $id)->value('status'));
        $this->assertTrue(DB::table('brands')->where('name', $brandName)->exists());

        Mail::assertSent(BrandListingStatusMail::class, function (BrandListingStatusMail $mail): bool {
            return $mail->status === 'approved';
        });
    }

    public function test_brand_listing_reject_requires_reason(): void
    {
        $id = $this->brandListing('Brand Reject '.Str::random(6));

        $this->actingAs($this->admin())
            ->put(route('management.brand-listings.reject', ['id' => $id]))
            ->assertSessionHasErrors('review_notes');

        $this->assertSame('pending', DB::table('partners')->where('id', $id)->value('status'));
    }

    private function brandListing(string $brandName): string
    {
        $id = (string) Str::uuid();
        $store = $this->store();
        $now = now();

        DB::table('partners')->insert([
            'id' => $id,
            'name' => $brandName,
            'status' => 'pending',
            'billing_cycle' => 'brand_listing',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('app_settings')->insert([
            'key' => 'brand_listing:'.$id,
            'value' => json_encode([
                'store_id' => $store->id,
                'requested_by' => $this->admin()->id,
                'contact_name' => 'Kontak',
                'contact_phone' => '08123456789',
                'contact_email' => 'brand-'.$id.'@example.test',
                'price_list_path' => null,
                'notes' => 'Catatan uji',
                'reviewed_by' => null,
                'reviewed_at' => null,
                'review_notes' => null,
            ]),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $id;
    }

    private function store(bool $withContact = false): Store
    {
        $suffix = Str::lower(Str::random(8));

        return Store::query()->create([
            'name' => 'Store Brand '.$suffix,
            'address' => 'Jl. Brand '.$suffix,
            'contact_email' => $withContact ? 'store-brand@example.test' : null,
        ]);
    }
}
