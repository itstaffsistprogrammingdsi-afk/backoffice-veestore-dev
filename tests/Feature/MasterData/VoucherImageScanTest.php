<?php

namespace Tests\Feature\MasterData;

use App\Ai\Agents\VoucherImageReader;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class VoucherImageScanTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_voucher_image_is_read_and_normalized_for_the_form(): void
    {
        Storage::fake('public');

        VoucherImageReader::fake([[
            'name' => 'Diskon Akhir Tahun',
            'description' => 'Nikmati diskon spesial akhir tahun.',
            'code' => 'YE2026',
            'type' => 'percentage',
            'value' => '20%',
            'max_discount' => 'Rp 100.000',
            'min_purchase' => 'Rp 250.000',
            'valid_from' => '1 Desember 2026',
            'valid_until' => '31 Desember 2026',
            'usage_limit' => '100',
            'distribution_type' => 'unique code',
            'contexts' => ['store checkout'],
            'confidence' => 92,
        ]]);

        $response = $this->actingAs($this->admin())
            ->postJson(route('master-data.vouchers.scan'), [
                'voucher_image' => UploadedFile::fake()->image('voucher.png')->size(200),
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Diskon Akhir Tahun')
            ->assertJsonPath('data.type', 'percentage')
            ->assertJsonPath('data.value', 20)
            ->assertJsonPath('data.max_discount', 100000)
            ->assertJsonPath('data.min_purchase', 250000)
            ->assertJsonPath('data.valid_from', '2026-12-01')
            ->assertJsonPath('data.valid_until', '2026-12-31')
            ->assertJsonPath('data.usage_limit', 100)
            ->assertJsonPath('data.distribution_type', 'unique_code')
            ->assertJsonPath('data.contexts', ['store_checkout'])
            ->assertJsonPath('data.confidence', 92);

        $this->assertIsArray($response->json('warnings'));

        VoucherImageReader::assertPrompted(fn ($prompt) => $prompt->contains('Baca gambar voucher'));
    }

    public function test_voucher_scan_rejects_non_image_upload(): void
    {
        VoucherImageReader::fake();

        $this->actingAs($this->admin())
            ->postJson(route('master-data.vouchers.scan'), [
                'voucher_image' => UploadedFile::fake()->create('voucher.pdf', 100, 'application/pdf'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('voucher_image');

        VoucherImageReader::assertNeverPrompted();
    }

    public function test_voucher_can_be_created_with_an_uploaded_image(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->post(route('master-data.vouchers.store'), [
                'voucher_image' => UploadedFile::fake()->image('voucher.jpg')->size(300),
                'name' => 'Voucher Belanja Hemat',
                'description' => 'Voucher dari gambar.',
                'type' => 'fixed',
                'value' => 25000,
                'min_purchase' => 100000,
                'valid_from' => now()->toDateString(),
                'valid_until' => now()->addMonth()->toDateString(),
                'distribution_type' => 'public',
                'contexts' => ['store_checkout'],
                'is_active' => '1',
            ])
            ->assertRedirect(route('master-data.vouchers.index'))
            ->assertSessionHas('success');

        $voucher = Voucher::query()->where('name', 'Voucher Belanja Hemat')->firstOrFail();

        $this->assertNotNull($voucher->voucher_image_path);
        $this->assertStringStartsWith('voucher-images/', $voucher->voucher_image_path);
        Storage::disk('public')->assertExists($voucher->voucher_image_path);
        $this->assertNotNull($voucher->voucher_image_url);
    }

    public function test_deleting_a_voucher_removes_its_image(): void
    {
        Storage::fake('public');

        $path = UploadedFile::fake()->image('voucher.png')->store('voucher-images', 'public');

        $voucher = Voucher::query()->create([
            'code' => 'VCR-IMAGE-'.uniqid(),
            'name' => 'Voucher Bergambar',
            'type' => 'fixed',
            'value' => 10000,
            'usage_count' => 0,
            'is_active' => true,
            'distribution_type' => 'public',
            'voucher_image_path' => $path,
        ]);

        $this->actingAs($this->admin())
            ->delete(route('master-data.vouchers.destroy', ['key' => $voucher->id]))
            ->assertRedirect(route('master-data.vouchers.index'));

        Storage::disk('public')->assertMissing($path);
    }
}
