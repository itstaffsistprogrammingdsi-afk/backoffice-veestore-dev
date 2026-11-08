<?php

namespace Tests\Feature\MasterData;

use App\Models\Store;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class StoreUniquenessTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_store_name_must_be_unique_case_insensitively(): void
    {
        $suffix = Str::lower(Str::random(8));

        $this->actingAs($this->admin())
            ->post(route('master-data.stores.store'), $this->payload('Store Uji '.$suffix, $suffix))
            ->assertRedirect(route('master-data.stores.index'))
            ->assertSessionHasNoErrors();

        $this->actingAs($this->admin())
            ->post(route('master-data.stores.store'), $this->payload('  STORE UJI '.strtoupper($suffix).'  ', $suffix.'b'))
            ->assertSessionHasErrors('name', null, 'createStore');
    }

    public function test_store_name_update_rejects_duplicate_from_other_store(): void
    {
        $suffix = Str::lower(Str::random(8));

        $first = Store::query()->create([
            'name' => 'Store Pertama '.$suffix,
            'address' => 'Jl. Pertama '.$suffix,
        ]);

        $second = Store::query()->create([
            'name' => 'Store Kedua '.$suffix,
            'address' => 'Jl. Kedua '.$suffix,
        ]);

        $this->actingAs($this->admin())
            ->put(route('master-data.stores.update', $second), [
                'name' => 'store pertama '.$suffix,
                'address' => $second->address,
                'contact_name' => 'Kontak',
                'contact_phone' => '08123456789',
            ])
            ->assertSessionHasErrors('name', null, 'editStore');

        $this->assertNotSame($first->name, $second->refresh()->name);
    }

    private function payload(string $name, string $suffix): array
    {
        return [
            'name' => $name,
            'address' => 'Jl. Uji Unik '.$suffix,
            'postal_code' => '50123',
            'phone' => '08123456789',
            'contact_name' => 'Kontak Uji',
            'contact_phone' => '08123456789',
            'contact_email' => 'store-'.$suffix.'@example.test',
        ];
    }
}
