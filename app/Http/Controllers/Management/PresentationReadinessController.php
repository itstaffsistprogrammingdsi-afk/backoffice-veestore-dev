<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PresentationReadinessController extends Controller
{
    private const DUMMY_KEYWORDS = [
        'mickey',
        'doraemon',
        'hello kitty',
        'kapibara',
        'my melody',
        'gantungan kunci',
        'dompet koin',
        'shincan',
        'barbie',
        'ultraman',
        'naruto',
        'spiderman',
        'dummy',
        'example',
        'test',
        'demo',
        'coba',
    ];

    private const CONFIRMATION = 'ARSIPKAN';

    public function index(): Response
    {
        $products = $this->dummyProducts();
        $stores = DB::table('stores')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'address', 'contact_email', 'created_at']);

        $storeGroups = $stores
            ->groupBy(fn ($store) => mb_strtolower(trim((string) $store->name)))
            ->filter(fn ($group) => $group->count() > 1)
            ->map(fn ($group, string $key) => [
                'key' => $key,
                'name' => $group->first()->name,
                'stores' => $group->values()->all(),
            ])
            ->values()
            ->all();

        $dummyStores = $stores
            ->filter(fn ($store) => $this->matchesDummyKeyword((string) $store->name))
            ->values()
            ->all();

        $warehouses = DB::table('warehouses')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'contact_email', 'created_at']);

        $dummyWarehouses = $warehouses
            ->filter(fn ($warehouse) => $this->matchesDummyKeyword((string) $warehouse->name))
            ->values()
            ->all();

        $noBrandProducts = DB::table('products')
            ->whereNull('deleted_at')
            ->whereNull('brand_id')
            ->count();

        return Inertia::render('Management/PresentationReadiness', [
            'products' => $products,
            'storeGroups' => $storeGroups,
            'dummyStores' => $dummyStores,
            'dummyWarehouses' => $dummyWarehouses,
            'summary' => [
                'dummy_products' => count($products),
                'duplicate_store_groups' => count($storeGroups),
                'dummy_stores' => count($dummyStores),
                'dummy_warehouses' => count($dummyWarehouses),
                'no_brand_products' => $noBrandProducts,
            ],
            'canArchive' => $this->canArchive(),
            'urls' => [
                'index' => route('management.presentation-readiness.index'),
                'archive_products' => route('management.presentation-readiness.archive-products'),
                'archive_stores' => route('management.presentation-readiness.archive-stores'),
                'archive_warehouses' => route('management.presentation-readiness.archive-warehouses'),
            ],
        ]);
    }

    public function archiveProducts(Request $request): RedirectResponse
    {
        $this->ensureManagementAccess();

        $data = $this->validateArchiveRequest($request);

        $matchedIds = DB::table('products')
            ->whereIn('id', $data['ids'])
            ->whereNull('deleted_at')
            ->where(function ($query): void {
                foreach (self::DUMMY_KEYWORDS as $keyword) {
                    $query->orWhereRaw('LOWER(name) LIKE ?', ['%'.$keyword.'%']);
                }
            })
            ->pluck('id')
            ->all();

        if ($matchedIds === []) {
            return back()->with('error', 'Tidak ada produk dummy yang dapat diarsipkan dari pilihan tersebut.');
        }

        DB::table('products')
            ->whereIn('id', $matchedIds)
            ->whereNull('deleted_at')
            ->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);

        return back()->with('success', count($matchedIds).' produk dummy diarsipkan.');
    }

    public function archiveStores(Request $request): RedirectResponse
    {
        $this->ensureManagementAccess();

        $data = $this->validateArchiveRequest($request);

        $stores = DB::table('stores')
            ->whereIn('id', $data['ids'])
            ->whereNull('deleted_at')
            ->get(['id', 'name']);

        $duplicateNames = DB::table('stores')
            ->whereNull('deleted_at')
            ->selectRaw('LOWER(TRIM(name)) as normalized_name, COUNT(*) as total')
            ->groupByRaw('LOWER(TRIM(name))')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('normalized_name')
            ->all();

        $ids = $stores
            ->filter(function ($store) use ($duplicateNames): bool {
                $normalized = mb_strtolower(trim((string) $store->name));

                return in_array($normalized, $duplicateNames, true)
                    || $this->matchesDummyKeyword((string) $store->name);
            })
            ->pluck('id')
            ->all();

        if ($ids === []) {
            return back()->with('error', 'Tidak ada toko dummy atau duplikat yang dapat diarsipkan dari pilihan tersebut.');
        }

        DB::table('stores')
            ->whereIn('id', $ids)
            ->whereNull('deleted_at')
            ->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);

        return back()->with('success', count($ids).' toko diarsipkan.');
    }

    public function archiveWarehouses(Request $request): RedirectResponse
    {
        $this->ensureManagementAccess();

        $data = $this->validateArchiveRequest($request);

        $matchedIds = DB::table('warehouses')
            ->whereIn('id', $data['ids'])
            ->whereNull('deleted_at')
            ->where(function ($query): void {
                foreach (self::DUMMY_KEYWORDS as $keyword) {
                    $query->orWhereRaw('LOWER(name) LIKE ?', ['%'.$keyword.'%']);
                }
            })
            ->pluck('id')
            ->all();

        if ($matchedIds === []) {
            return back()->with('error', 'Tidak ada gudang dummy yang dapat diarsipkan dari pilihan tersebut.');
        }

        DB::table('warehouses')
            ->whereIn('id', $matchedIds)
            ->whereNull('deleted_at')
            ->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);

        return back()->with('success', count($matchedIds).' gudang dummy diarsipkan.');
    }

    private function validateArchiveRequest(Request $request): array
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'uuid'],
            'confirmation' => ['required', 'string'],
        ], [
            'ids.required' => 'Pilih minimal satu data untuk diarsipkan.',
            'confirmation.required' => 'Ketik ARSIPKAN untuk melanjutkan.',
        ]);

        if (trim((string) $data['confirmation']) !== self::CONFIRMATION) {
            throw ValidationException::withMessages([
                'confirmation' => 'Konfirmasi tidak sesuai. Ketik ARSIPKAN untuk melanjutkan.',
            ]);
        }

        return $data;
    }

    private function dummyProducts(): array
    {
        $query = DB::table('products as product')
            ->leftJoin('brands as brand', 'brand.id', '=', 'product.brand_id')
            ->leftJoin('product_categories as category', 'category.id', '=', 'product.category_id')
            ->whereNull('product.deleted_at')
            ->where(function ($search): void {
                foreach (self::DUMMY_KEYWORDS as $keyword) {
                    $search->orWhereRaw('LOWER(product.name) LIKE ?', ['%'.$keyword.'%']);
                }
            })
            ->select([
                'product.id',
                'product.name',
                'product.code',
                'product.created_at',
                'brand.name as brand_name',
                'category.name as category_name',
            ])
            ->orderBy('product.name');

        return $query->get()->map(fn ($row) => (array) $row)->all();
    }

    private function matchesDummyKeyword(string $value): bool
    {
        $normalized = mb_strtolower($value);

        foreach (self::DUMMY_KEYWORDS as $keyword) {
            if (str_contains($normalized, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function canArchive(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->getRoleNames()
            ->map(fn ($role) => preg_replace('/[^a-z0-9]/', '', strtolower($role)))
            ->contains(fn ($role) => in_array($role, ['superadmin', 'administrator', 'admin', 'management'], true));
    }

    private function ensureManagementAccess(): void
    {
        abort_unless($this->canArchive(), 403);
    }
}
