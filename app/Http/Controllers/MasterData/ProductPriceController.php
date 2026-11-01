<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ProductPriceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view master-data-product-prices')->only(['index']);
        $this->middleware('permission:create master-data-product-prices')->only(['store', 'checkDuplicate']);
        $this->middleware('permission:update master-data-product-prices')->only(['update', 'checkDuplicate']);
        $this->middleware('permission:delete master-data-product-prices')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $priceType = $request->get('price_type');
        $from = $request->get('from');
        $to = $request->get('to');

        $today = Carbon::today()->toDateString();

        $displayTypes = ['base', 'store', 'ecommerce'];

        $activeRows = ProductPrice::query()
            ->select([
                'product_prices.id',
                'product_prices.product_id',
                'product_prices.price_type',
                'product_prices.price',
                'product_prices.valid_from',
                'product_prices.valid_to',
                'p.name as product_name',
                'p.code as product_code',
            ])
            ->join('products as p', 'p.id', '=', 'product_prices.product_id')
            ->whereDate('product_prices.valid_from', '<=', $today)
            ->where(function ($query) use ($today) {
                $query->whereNull('product_prices.valid_to')
                    ->orWhereDate('product_prices.valid_to', '>=', $today);
            })
            ->when($q, function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('p.name', 'ILIKE', "%{$q}%")
                        ->orWhere('p.code', 'ILIKE', "%{$q}%");
                });
            })
            ->when($priceType, function ($query) use ($priceType) {
                $query->where('product_prices.price_type', $priceType);
            })
            ->when($from, function ($query) use ($from) {
                $query->whereDate('product_prices.valid_from', '>=', $from);
            })
            ->when($to, function ($query) use ($to) {
                $query->whereDate('product_prices.valid_from', '<=', $to);
            })
            ->orderBy('p.name')
            ->orderBy('product_prices.price_type')
            ->orderByDesc('product_prices.valid_from')
            ->get();

        $groupedProducts = $activeRows
            ->groupBy('product_id')
            ->map(function ($items) use ($displayTypes) {
                $first = $items->first();

                $pricesByType = [];

                foreach ($displayTypes as $type) {
                    $pricesByType[$type] = $items
                        ->where('price_type', $type)
                        ->sortByDesc('valid_from')
                        ->first();
                }

                $basePrice = $pricesByType['base'] ?? null;
                $basePriceValue = $basePrice ? (float) $basePrice->price : 0;

                return (object) [
                    'product_id' => $first->product_id,
                    'product_name' => $first->product_name,
                    'product_code' => $first->product_code,
                    'base_price' => $basePriceValue,
                    'prices_by_type' => $pricesByType,
                    'active_count' => $items->count(),
                ];
            })
            ->values();

        $perPage = 10;
        $page = max((int) $request->get('page', 1), 1);

        $prices = new LengthAwarePaginator(
            $groupedProducts->forPage($page, $perPage)->values(),
            $groupedProducts->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $productIds = $groupedProducts->pluck('product_id')->all();

        $allPriceDetails = ProductPrice::query()
            ->with('product')
            ->whereIn('product_id', $productIds)
            ->orderBy('price_type')
            ->orderByDesc('valid_from')
            ->get()
            ->groupBy('product_id');

        $products = Product::orderBy('name')->get(['id', 'name', 'code']);

        $priceTypes = ProductPrice::enumValues() ?: $displayTypes;

        return Inertia::render('MasterData/ProductPrices', [
            'prices' => $prices,
            'products' => $products,
            'priceTypes' => $priceTypes,
            'displayTypes' => $displayTypes,
            'allPriceDetails' => $allPriceDetails,
            'filters' => [
                'q' => $q,
                'price_type' => $priceType,
                'from' => $from,
                'to' => $to,
            ],
            'urls' => [
                'index' => route('master-data.product-prices.index'),
                'store' => route('master-data.product-prices.store'),
                'update' => route('master-data.product-prices.update', ['product_price' => '__ID__']),
                'destroy' => route('master-data.product-prices.destroy', ['product_price' => '__ID__']),
            ],
        ]);
    }

    private function findPeriodConflict(array $data, ?string $ignoreId = null): ?ProductPrice
    {
        $newFrom = Carbon::parse($data['valid_from'])->toDateString();

        $newTo = ! empty($data['valid_to'])
            ? Carbon::parse($data['valid_to'])->toDateString()
            : '9999-12-31';

        return ProductPrice::query()
            ->with('product')
            ->where('product_id', $data['product_id'])
            ->where('price_type', $data['price_type'])
            ->when($ignoreId, function ($query) use ($ignoreId) {
                $query->where('id', '<>', $ignoreId);
            })
            ->whereDate('valid_from', '<=', $newTo)
            ->where(function ($query) use ($newFrom) {
                $query->whereNull('valid_to')
                    ->orWhereDate('valid_to', '>=', $newFrom);
            })
            ->orderByDesc('valid_from')
            ->first();
    }

    private function conflictMessage(ProductPrice $conflict): string
    {
        $product = $conflict->product
            ? trim(($conflict->product->name ?? '-').' ('.($conflict->product->code ?? '-').')')
            : '-';

        $from = $this->formatDateId($conflict->valid_from);
        $to = $this->formatDateId($conflict->valid_to);

        return "Tidak bisa disimpan. Produk {$product} dengan tipe {$conflict->price_type} sudah memiliki periode {$from} sampai {$to}. Pilih tanggal di luar periode tersebut.";
    }

    public function checkDuplicate(Request $request)
    {
        $priceTypes = ProductPrice::enumValues()
            ?: ['base', 'store', 'ecommerce'];

        /*
         * Form tambah dan edit sama-sama mengirim prices[base/store/ecommerce].
         * Jika id terisi berarti pemeriksaan dilakukan untuk edit.
         */
        if ($request->has('prices')) {
            $isEdit = $request->filled('id');

            $validFromRules = $isEdit
                ? ['required', 'date']
                : ['required', 'date', 'after_or_equal:today'];

            $data = $request->validate([
                'id' => ['nullable', 'uuid'],
                'product_id' => [
                    'required',
                    'uuid',
                    'exists:products,id',
                ],
                'valid_from' => $validFromRules,
                'valid_to' => [
                    'nullable',
                    'date',
                    'after_or_equal:valid_from',
                ],
                'prices' => ['required', 'array'],
                'prices.base' => [
                    'required',
                    'numeric',
                    'min:0',
                ],
                'prices.store' => [
                    'required',
                    'numeric',
                    'min:0',
                ],
                'prices.ecommerce' => [
                    'required',
                    'numeric',
                    'min:0',
                ],
            ], [], [
                'product_id' => 'produk',
                'valid_from' => 'berlaku dari',
                'valid_to' => 'expired / berlaku sampai',
                'prices.base' => 'harga base',
                'prices.store' => 'harga store',
                'prices.ecommerce' => 'harga ecommerce',
            ]);

            $ignoreIds = [];

            if ($isEdit) {
                $representative = ProductPrice::query()
                    ->whereKey($data['id'])
                    ->where('product_id', $data['product_id'])
                    ->first();

                if ($representative) {
                    $ignoreIds = $this->bulkEditRows(
                        $representative
                    )
                        ->pluck('id')
                        ->all();
                }
            }

            $newFrom = Carbon::parse(
                $data['valid_from']
            )->toDateString();

            $newTo = ! empty($data['valid_to'])
                ? Carbon::parse(
                    $data['valid_to']
                )->toDateString()
                : '9999-12-31';

            $conflicts = [];

            foreach (
                ['base', 'store', 'ecommerce'] as $type
            ) {
                if (! in_array($type, $priceTypes, true)) {
                    continue;
                }

                $conflict = ProductPrice::query()
                    ->with('product')
                    ->where('product_id', $data['product_id'])
                    ->where('price_type', $type)
                    ->when(
                        $ignoreIds !== [],
                        function ($query) use ($ignoreIds) {
                            $query->whereNotIn('id', $ignoreIds);
                        }
                    )
                    ->whereDate('valid_from', '<=', $newTo)
                    ->where(function ($query) use ($newFrom) {
                        $query->whereNull('valid_to')
                            ->orWhereDate(
                                'valid_to',
                                '>=',
                                $newFrom
                            );
                    })
                    ->orderByDesc('valid_from')
                    ->first();

                if ($conflict) {
                    $conflicts[] =
                        $this->conflictPayload($conflict);
                }
            }

            return response()->json([
                'blocked' => $conflicts !== [],
                'message' => $conflicts !== []
                    ? 'Satu atau beberapa tipe harga memiliki periode yang bentrok.'
                    : null,
                'conflicts' => $conflicts,
            ]);
        }

        $data = $request->validate([
            'id' => ['nullable', 'uuid'],
            'product_id' => [
                'required',
                'uuid',
                'exists:products,id',
            ],
            'price_type' => [
                'required',
                Rule::in($priceTypes),
            ],
            'valid_from' => ['required', 'date'],
            'valid_to' => [
                'nullable',
                'date',
                'after_or_equal:valid_from',
            ],
        ], [], [
            'product_id' => 'produk',
            'price_type' => 'tipe harga',
            'valid_from' => 'berlaku dari',
            'valid_to' => 'expired / berlaku sampai',
        ]);

        $conflict = $this->findPeriodConflict(
            $data,
            $data['id'] ?? null
        );

        return response()->json([
            'blocked' => (bool) $conflict,
            'message' => $conflict
                ? $this->conflictMessage($conflict)
                : null,
            'conflict' => $conflict
                ? $this->conflictPayload($conflict)
                : null,
            'conflicts' => $conflict
                ? [$this->conflictPayload($conflict)]
                : [],
        ]);
    }

    /**
     * Mengambil tiga harga yang berada dalam satu kelompok periode
     * dengan data perwakilan yang diklik.
     */
    private function bulkEditRows(
        ProductPrice $representative
    ) {
        return ProductPrice::query()
            ->where(
                'product_id',
                $representative->product_id
            )
            ->whereIn(
                'price_type',
                ['base', 'store', 'ecommerce']
            )
            ->whereDate(
                'valid_from',
                Carbon::parse(
                    $representative->valid_from
                )->toDateString()
            )
            ->when(
                $representative->valid_to,
                function ($query) use ($representative) {
                    $query->whereDate(
                        'valid_to',
                        Carbon::parse(
                            $representative->valid_to
                        )->toDateString()
                    );
                },
                function ($query) {
                    $query->whereNull('valid_to');
                }
            )
            ->get();
    }

    private function conflictPayload(ProductPrice $conflict): array
    {
        return [
            'id' => $conflict->id,
            'product_id' => $conflict->product_id,
            'product' => $conflict->product ? [
                'id' => $conflict->product->id,
                'name' => $conflict->product->name,
                'code' => $conflict->product->code,
            ] : null,
            'price_type' => $conflict->price_type,
            'price' => $conflict->price,
            'valid_from' => $conflict->valid_from
                ? Carbon::parse($conflict->valid_from)->format('Y-m-d')
                : null,
            'valid_to' => $conflict->valid_to
                ? Carbon::parse($conflict->valid_to)->format('Y-m-d')
                : null,
        ];
    }

    public function store(Request $request)
    {
        $priceTypes = ProductPrice::enumValues() ?: ['base', 'store', 'ecommerce'];
        $requiredTypes = ['base', 'store', 'ecommerce'];

        $data = $request->validate([
            'product_id' => ['required', 'uuid', 'exists:products,id'],
            'valid_from' => ['required', 'date', 'after_or_equal:today'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'prices' => ['required', 'array'],
            'prices.base' => ['required', 'numeric', 'min:0'],
            'prices.store' => ['required', 'numeric', 'min:0'],
            'prices.ecommerce' => ['required', 'numeric', 'min:0'],
        ], [
            'prices.base.required' => 'Harga Base wajib diisi.',
            'prices.store.required' => 'Harga Store wajib diisi.',
            'prices.ecommerce.required' => 'Harga Ecommerce wajib diisi.',
        ], [
            'product_id' => 'produk',
            'valid_from' => 'berlaku dari',
            'valid_to' => 'expired / berlaku sampai',
            'prices.base' => 'harga base',
            'prices.store' => 'harga store',
            'prices.ecommerce' => 'harga ecommerce',
        ]);

        $conflictMessages = [];

        foreach ($requiredTypes as $type) {
            if (! in_array($type, $priceTypes, true)) {
                continue;
            }

            $conflict = $this->findPeriodConflict([
                'product_id' => $data['product_id'],
                'price_type' => $type,
                'valid_from' => $data['valid_from'],
                'valid_to' => $data['valid_to'] ?? null,
            ]);

            if ($conflict) {
                $conflictMessages["prices.{$type}"] = $this->conflictMessage($conflict);
            }
        }

        if ($conflictMessages) {
            throw ValidationException::withMessages($conflictMessages);
        }

        DB::transaction(function () use ($data, $requiredTypes, $priceTypes) {
            foreach ($requiredTypes as $type) {
                if (! in_array($type, $priceTypes, true)) {
                    continue;
                }

                ProductPrice::create([
                    'product_id' => $data['product_id'],
                    'price_type' => $type,
                    'price' => $data['prices'][$type],
                    'valid_from' => $data['valid_from'],
                    'valid_to' => $data['valid_to'] ?? null,
                ]);
            }
        });

        return back()->with(
            'success',
            'Harga Base, Store, dan Ecommerce berhasil disimpan sekaligus.'
        );
    }

    public function update(
        Request $request,
        ProductPrice $product_price
    ) {
        $priceTypes = ProductPrice::enumValues()
            ?: ['base', 'store', 'ecommerce'];

        $requiredTypes = ['base', 'store', 'ecommerce'];
        $productId = (string) $product_price->product_id;

        $request->merge([
            'product_id' => $productId,
            '_from' => 'edit',
        ]);

        /*
         * Pada edit, tanggal lama tetap boleh digunakan walaupun
         * sudah sebelum hari ini. Tanggal juga boleh diganti selama
         * tidak bertabrakan dengan kelompok harga lainnya.
         */
        $data = $request->validate([
            'product_id' => [
                'required',
                'uuid',
                'exists:products,id',
            ],
            'valid_from' => [
                'required',
                'date',
            ],
            'valid_to' => [
                'nullable',
                'date',
                'after_or_equal:valid_from',
            ],
            'prices' => [
                'required',
                'array',
            ],
            'prices.base' => [
                'required',
                'numeric',
                'min:0',
            ],
            'prices.store' => [
                'required',
                'numeric',
                'min:0',
            ],
            'prices.ecommerce' => [
                'required',
                'numeric',
                'min:0',
            ],
        ], [
            'prices.base.required' => 'Harga Base wajib diisi.',
            'prices.store.required' => 'Harga Store wajib diisi.',
            'prices.ecommerce.required' => 'Harga Ecommerce wajib diisi.',
        ], [
            'product_id' => 'produk',
            'valid_from' => 'berlaku dari',
            'valid_to' => 'expired / berlaku sampai',
            'prices.base' => 'harga base',
            'prices.store' => 'harga store',
            'prices.ecommerce' => 'harga ecommerce',
        ]);

        $currentRows = $this->bulkEditRows(
            $product_price
        );

        $currentIds = $currentRows
            ->pluck('id')
            ->all();

        $newFrom = Carbon::parse(
            $data['valid_from']
        )->toDateString();

        $newTo = ! empty($data['valid_to'])
            ? Carbon::parse(
                $data['valid_to']
            )->toDateString()
            : '9999-12-31';

        $conflictMessages = [];

        foreach ($requiredTypes as $type) {
            if (! in_array($type, $priceTypes, true)) {
                continue;
            }

            $conflict = ProductPrice::query()
                ->with('product')
                ->where('product_id', $productId)
                ->where('price_type', $type)
                ->when(
                    $currentIds !== [],
                    function ($query) use ($currentIds) {
                        $query->whereNotIn(
                            'id',
                            $currentIds
                        );
                    }
                )
                ->whereDate('valid_from', '<=', $newTo)
                ->where(function ($query) use ($newFrom) {
                    $query->whereNull('valid_to')
                        ->orWhereDate(
                            'valid_to',
                            '>=',
                            $newFrom
                        );
                })
                ->orderByDesc('valid_from')
                ->first();

            if ($conflict) {
                $conflictMessages["prices.{$type}"] =
                    $this->conflictMessage($conflict);
            }
        }

        if ($conflictMessages !== []) {
            throw ValidationException::withMessages(
                $conflictMessages
            );
        }

        DB::transaction(
            function () use (
                $data,
                $requiredTypes,
                $priceTypes,
                $productId,
                $currentRows
            ): void {
                foreach ($requiredTypes as $type) {
                    if (! in_array($type, $priceTypes, true)) {
                        continue;
                    }

                    $price = $currentRows
                        ->firstWhere('price_type', $type);

                    $payload = [
                        'product_id' => $productId,
                        'price_type' => $type,
                        'price' => $data['prices'][$type],
                        'valid_from' => $data['valid_from'],
                        'valid_to' => $data['valid_to'] ?? null,
                    ];

                    if ($price) {
                        ProductPrice::query()
                            ->whereKey($price->id)
                            ->lockForUpdate()
                            ->firstOrFail()
                            ->update($payload);
                    } else {
                        ProductPrice::create($payload);
                    }
                }
            }
        );

        return back()->with(
            'success',
            'Harga Base, Store, dan Ecommerce berhasil diperbarui.'
        );
    }

    public function destroy(ProductPrice $product_price)
    {
        $productId = (string) $product_price->product_id;

        DB::transaction(function () use ($productId): void {
            ProductPrice::query()
                ->where('product_id', $productId)
                ->delete();
        });

        return back()->with(
            'success',
            'Seluruh data harga produk berhasil dihapus.'
        );
    }

    private function formatDateId($date): string
    {
        if (! $date) {
            return 'Tanpa batas';
        }

        return Carbon::parse($date)->format('d/m/Y');
    }
}
