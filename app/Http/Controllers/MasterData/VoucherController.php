<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Models\VoucherCode;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class VoucherController extends Controller
{
    private const PIVOT_SCHEMA = 'public';

    private const PIVOT_TABLE = 'voucher_context'; // public.voucher_context

    private const CONTEXTS = [
        'ecommerce_checkout',
        'ecommerce_shipping',
        'store_checkout',
        'store_customer_return',
    ];

    private const DISTRIBUTION_TYPES = ['public', 'unique_code'];

    public function __construct()
    {
        $this->middleware('permission:view master-data-vouchers')->only(['index', 'voucherCodes']);
        $this->middleware('permission:create master-data-vouchers')->only(['store']);
        $this->middleware('permission:update master-data-vouchers')->only(['update', 'printUniqueCodes', 'updateVoucherCodeStatus']);
        $this->middleware('permission:delete master-data-vouchers')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $q = trim($request->get('q', ''));

        $vouchers = Voucher::query()
            ->withCount(['voucherCodes', 'usages'])
            ->when($q, fn ($qq) => $qq->search($q))
            ->orderByDesc('created_at')
            ->paginate(10);

        $availableContexts = $this->getAvailableContexts();
        $voucherContextsMap = $this->getVoucherContextsMap($vouchers->pluck('id')->all());

        return Inertia::render('MasterData/SimpleCrud', [
            'title' => 'Vouchers',
            'description' => 'Kelola voucher dan konteks pemakaiannya.',
            'searchPlaceholder' => 'Cari voucher...',
            'items' => $vouchers,
            'columns' => [
                ['key' => 'code', 'label' => 'Kode'],
                ['key' => 'name', 'label' => 'Nama'],
                ['key' => 'type', 'label' => 'Tipe'],
                ['key' => 'value', 'label' => 'Nilai'],
                ['key' => 'valid_from', 'label' => 'Mulai'],
                ['key' => 'valid_until', 'label' => 'Selesai'],
                ['key' => 'is_active', 'label' => 'Aktif'],
            ],
            'fields' => [
                ['key' => 'code', 'label' => 'Kode Voucher', 'placeholder' => 'Kosongkan untuk generate otomatis'],
                ['key' => 'name', 'label' => 'Nama Voucher', 'required' => true],
                ['key' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea'],
                ['key' => 'type', 'label' => 'Tipe', 'type' => 'select', 'required' => true, 'options' => [['value' => 'fixed', 'label' => 'Fixed'], ['value' => 'percentage', 'label' => 'Percentage']]],
                ['key' => 'value', 'label' => 'Nilai', 'type' => 'number', 'required' => true],
                ['key' => 'max_discount', 'label' => 'Max Diskon', 'type' => 'number'],
                ['key' => 'min_purchase', 'label' => 'Min Belanja', 'type' => 'number'],
                ['key' => 'valid_from', 'label' => 'Mulai', 'type' => 'date'],
                ['key' => 'valid_until', 'label' => 'Selesai', 'type' => 'date'],
                ['key' => 'usage_limit', 'label' => 'Batas Pemakaian', 'type' => 'number'],
                ['key' => 'distribution_type', 'label' => 'Distribusi', 'type' => 'select', 'required' => true, 'options' => collect(self::DISTRIBUTION_TYPES)->map(fn ($value) => ['value' => $value, 'label' => $value])->values()->all()],
                ['key' => 'contexts', 'label' => 'Konteks', 'type' => 'multiselect', 'required' => true, 'options' => collect($availableContexts)->map(fn ($value) => ['value' => $value, 'label' => $value])->values()->all()],
                ['key' => 'is_active', 'label' => 'Aktif', 'type' => 'select', 'options' => [['value' => '1', 'label' => 'Ya'], ['value' => '0', 'label' => 'Tidak']]],
            ],
            'filters' => ['q' => $q],
            'rowActions' => [
                [
                    'label' => 'Lihat kode voucher',
                    'icon' => 'mdi-qrcode',
                    'url' => route('master-data.vouchers.codes', ['key' => '__ID__']),
                    'visibleWhen' => ['key' => 'distribution_type', 'equals' => 'unique_code'],
                ],
                [
                    'label' => 'Cetak voucher',
                    'icon' => 'mdi-printer-outline',
                    'url' => route('master-data.vouchers.print-codes', ['key' => '__ID__']),
                    'method' => 'post',
                    'prompt' => ['label' => 'Jumlah voucher yang dicetak (1-100)', 'key' => 'quantity', 'default' => 1, 'min' => 1, 'max' => 100],
                    'visibleWhen' => ['key' => 'distribution_type', 'equals' => 'unique_code'],
                ],
            ],
            'urls' => [
                'index' => route('master-data.vouchers.index'),
                'store' => route('master-data.vouchers.store'),
                'update' => route('master-data.vouchers.update', ['key' => '__ID__']),
                'destroy' => route('master-data.vouchers.destroy', ['key' => '__ID__']),
            ],
        ]);
    }

    public function store(Request $request)
    {
        // ✅ normalize contexts lebih dulu supaya validasi konsisten
        $request->merge([
            'contexts' => $this->normalizeContexts($request->input('contexts')),
        ]);

        $data = $this->validated($request);
        // Kode voucher master selalu dibuat oleh backend, bukan dari input form.
        $data['code'] = $this->generateVoucherCode();
        $data['is_active'] = $request->boolean('is_active');
        $data['valid_from'] = $this->parseDateTime($request->input('valid_from'));
        $data['valid_until'] = $this->parseDateTime($request->input('valid_until'));

        if (($data['type'] ?? null) === 'fixed') {
            $data['max_discount'] = null;
        }

        $data['usage_count'] = 0;

        $selected = (array) $request->input('contexts', []);
        $this->validateContextsAgainstAllowed($selected);

        DB::transaction(function () use ($data, $selected) {

            // ✅ jangan biarkan context "" masuk ke tabel vouchers
            // Kalau pivot ada, simpan di pivot saja
            if ($this->hasPivotTable()) {
                $data['context'] = null; // aman walau kolom enum
            } else {
                // fallback: jika kolom vouchers.context itu ENUM, dia hanya bisa 1 value
                // jadi ambil 1 context saja, dan kalau kosong -> NULL (bukan string kosong)
                $data['context'] = ! empty($selected) ? $selected[0] : null;
            }

            /** @var Voucher $voucher */
            $voucher = Voucher::create($data);

            // For unique-code distribution, usage_limit is also the initial
            // number of individual voucher codes to issue.
            if ($voucher->distribution_type === 'unique_code') {
                $this->generateVoucherCodes($voucher, (int) $voucher->usage_limit);
            }

            if ($this->hasPivotTable()) {
                $this->syncVoucherContexts($voucher->id, $selected);
            }
        });

        return redirect()->route('master-data.vouchers.index')
            ->with('success', 'Voucher berhasil dibuat.');
    }

    /** Return already-issued codes in a printer-friendly voucher sheet. */
    public function printUniqueCodes(Request $request, string $key)
    {
        $voucher = Str::isUuid($key)
            ? Voucher::where('id', $key)->firstOrFail()
            : Voucher::where('code', $key)->firstOrFail();

        abort_unless($voucher->distribution_type === 'unique_code', 422,
            'Cetak voucher hanya tersedia untuk tipe distribusi unique_code.');

        $payload = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ], [], ['quantity' => 'Jumlah voucher']);

        $codes = VoucherCode::where('voucher_id', $voucher->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->limit((int) $payload['quantity'])
            ->get();

        abort_if($codes->count() < (int) $payload['quantity'], 422,
            'Jumlah kode yang diminta melebihi kode voucher yang telah dibuat.');

        return Inertia::render('MasterData/VoucherPrint', [
            'voucher' => $voucher,
            'codes' => $codes,
            'urls' => ['index' => route('master-data.vouchers.index')],
        ]);
    }

    public function voucherCodes(Request $request, string $key)
    {
        $voucher = Str::isUuid($key)
            ? Voucher::where('id', $key)->firstOrFail()
            : Voucher::where('code', $key)->firstOrFail();

        abort_unless($voucher->distribution_type === 'unique_code', 404);

        $codes = VoucherCode::where('voucher_id', $voucher->id)
            ->withCount('usages')
            ->orderByDesc('created_at')
            ->paginate(50);

        $usedCodesCount = VoucherCode::where('voucher_id', $voucher->id)
            ->whereHas('usages')
            ->count();

        return Inertia::render('MasterData/VoucherCodes', [
            'voucher' => $voucher,
            'codes' => $codes,
            'usedCodesCount' => $usedCodesCount,
            'urls' => [
                'index' => route('master-data.vouchers.index'),
                'status' => route('master-data.vouchers.codes.status', ['key' => '__ID__', 'voucherCodeId' => '__CODE_ID__']),
            ],
        ]);
    }

    public function updateVoucherCodeStatus(Request $request, string $key, string $voucherCodeId)
    {
        $voucher = Str::isUuid($key)
            ? Voucher::where('id', $key)->firstOrFail()
            : Voucher::where('code', $key)->firstOrFail();

        abort_unless($voucher->distribution_type === 'unique_code', 404);

        $payload = $request->validate(['is_active' => ['required', 'boolean']]);
        VoucherCode::where('voucher_id', $voucher->id)
            ->where('id', $voucherCodeId)
            ->firstOrFail()
            ->update(['is_active' => (bool) $payload['is_active']]);

        return back()->with('success', 'Status kode voucher berhasil diupdate.');
    }

    public function update(Request $request, string $key)
    {
        $voucher = Str::isUuid($key)
            ? Voucher::where('id', $key)->firstOrFail()
            : Voucher::where('code', $key)->firstOrFail();

        // ✅ normalize contexts lebih dulu
        $request->merge([
            'contexts' => $this->normalizeContexts($request->input('contexts')),
        ]);

        $data = $this->validated($request, $voucher->id);
        // Kode bersifat immutable setelah voucher dibuat.
        unset($data['code']);
        $data['is_active'] = $request->boolean('is_active');
        $data['valid_from'] = $this->parseDateTime($request->input('valid_from'));
        $data['valid_until'] = $this->parseDateTime($request->input('valid_until'));

        if (($data['type'] ?? null) === 'fixed') {
            $data['max_discount'] = null;
        }

        $selected = (array) $request->input('contexts', []);
        $this->validateContextsAgainstAllowed($selected);

        DB::transaction(function () use ($voucher, $data, $selected) {

            if ($this->hasPivotTable()) {
                $data['context'] = null;
            } else {
                $data['context'] = ! empty($selected) ? $selected[0] : null;
            }

            $voucher->update($data);

            // Existing unique codes always inherit the master voucher expiry.
            // `valid_from` remains the master validity start because voucher_codes
            // only has an `expires_at` column in the current schema.
            VoucherCode::where('voucher_id', $voucher->id)->update([
                'expires_at' => $voucher->valid_until,
            ]);

            // Raising the limit issues only the additional codes required.
            // Existing/printed codes are intentionally never deleted when a
            // limit is lowered, preserving auditability and customer access.
            if ($voucher->distribution_type === 'unique_code' && $voucher->usage_limit) {
                $existingCodes = VoucherCode::where('voucher_id', $voucher->id)->count();
                $missingCodes = max(0, (int) $voucher->usage_limit - $existingCodes);
                if ($missingCodes > 0) {
                    $this->generateVoucherCodes($voucher, $missingCodes);
                }
            }

            if ($this->hasPivotTable()) {
                $this->syncVoucherContexts($voucher->id, $selected);
            }
        });

        return redirect()->route('master-data.vouchers.index')
            ->with('success', 'Voucher berhasil diupdate.');
    }

    public function destroy(string $key)
    {
        $voucher = Str::isUuid($key)
            ? Voucher::where('id', $key)->firstOrFail()
            : Voucher::where('code', $key)->firstOrFail();

        DB::transaction(function () use ($voucher) {
            if ($this->hasPivotTable()) {
                DB::table($this->pivotFqn())->where('voucher_id', $voucher->id)->delete();
            }
            $voucher->delete();
        });

        return redirect()->route('master-data.vouchers.index')
            ->with('success', 'Voucher berhasil dihapus.');
    }

    /* ======================= Validations ======================= */

    protected function validated(Request $request, $ignoreId = null): array
    {
        $typeRule = Rule::in(['fixed', 'percentage']);

        // allowed contexts (kalau ada)
        $allowed = $this->getAvailableContexts();

        return $request->validate([
            // Intentionally optional: it is ignored on update and generated server-side on create.
            'code' => ['nullable', 'string', 'max:100', Rule::unique('vouchers', 'code')->ignore($ignoreId)],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'type' => ['required', $typeRule],
            'value' => ['required', 'numeric', 'min:0'],
            // Batas maksimal diskon bersifat opsional, termasuk untuk voucher percentage.
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'min_purchase' => ['nullable', 'numeric', 'min:0'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'usage_limit' => [Rule::requiredIf(fn () => $request->input('distribution_type') === 'unique_code'), 'nullable', 'integer', 'min:1'],
            'distribution_type' => ['required', Rule::in(self::DISTRIBUTION_TYPES)],

            // ✅ checkbox: kalau tidak dicentang, field tidak terkirim -> tetap aman karena boolean() akan false
            'is_active' => ['sometimes', 'boolean'],

            // ✅ CONTEXT WAJIB
            'contexts' => ['required', 'array', 'min:1'],
            'contexts.*' => array_merge(
                ['required', 'string', 'distinct'],
                ! empty($allowed) ? [Rule::in($allowed)] : []
            ),
        ], [], [
            'code' => 'Kode',
            'name' => 'Nama',
            'type' => 'Tipe',
            'value' => 'Nilai',
            'max_discount' => 'Maks. Diskon',
            'min_purchase' => 'Min. Belanja',
            'valid_from' => 'Berlaku dari',
            'valid_until' => 'Berlaku sampai',
            'usage_limit' => 'Batas Penggunaan',
            'is_active' => 'Status',
            'distribution_type' => 'Tipe Distribusi',
            'contexts' => 'Context',
            'contexts.*' => 'Context',
        ]);
    }

    protected function parseDateTime(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }
        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /* ======================= Pivot Helpers ======================= */

    protected function pivotFqn(): string
    {
        return self::PIVOT_SCHEMA.'.'.self::PIVOT_TABLE;
    }

    protected function hasPivotTable(): bool
    {
        static $cached;
        if (! is_null($cached)) {
            return $cached;
        }

        $exists = DB::table('information_schema.tables')
            ->where('table_schema', self::PIVOT_SCHEMA)
            ->where('table_name', self::PIVOT_TABLE)
            ->exists();

        return $cached = (bool) $exists;
    }

    protected function getAvailableContexts(): array
    {
        // Values match enum public.voucher_context, not rows already saved in the DB.
        return self::CONTEXTS;
    }

    protected function normalizeContexts($input): array
    {
        // Select2 multiple -> array
        if (is_array($input)) {
            $arr = $input;
        } elseif (is_string($input)) {
            $arr = preg_split('/\s*,\s*/', $input, -1, PREG_SPLIT_NO_EMPTY);
        } else {
            $arr = [];
        }

        $out = [];
        foreach ($arr as $c) {
            if (! is_string($c)) {
                continue;
            }
            $val = trim($c);
            if ($val === '') {
                continue;
            }
            $out[] = $val;
        }

        return array_values(array_unique($out));
    }

    protected function validateContextsAgainstAllowed(array $selected): void
    {
        $allowed = $this->getAvailableContexts();

        // kalau master list kosong, tidak perlu cek allowed (anggap bebas)
        if (empty($allowed)) {
            return;
        }

        $diff = array_values(array_diff($selected, $allowed));
        if (! empty($diff)) {
            abort(422, 'Context tidak dikenali: '.implode(', ', $diff));
        }
    }

    protected function syncVoucherContexts(string $voucherId, array $contexts): void
    {
        DB::table($this->pivotFqn())->where('voucher_id', $voucherId)->delete();

        if (empty($contexts)) {
            return;
        }

        $now = now();
        $rows = array_map(fn ($c) => [
            'voucher_id' => $voucherId,
            'context' => $c,
            'created_at' => $now,
            'updated_at' => $now,
        ], $contexts);

        DB::table($this->pivotFqn())->insert($rows);
    }

    protected function getVoucherContextsMap(array $voucherIds): array
    {
        $map = [];
        if (empty($voucherIds)) {
            return $map;
        }

        if ($this->hasPivotTable()) {
            $rows = DB::table($this->pivotFqn())
                ->whereIn('voucher_id', $voucherIds)
                ->orderBy('context')
                ->get(['voucher_id', 'context']);

            foreach ($rows as $r) {
                $map[$r->voucher_id] ??= [];
                if (is_string($r->context) && trim($r->context) !== '') {
                    $map[$r->voucher_id][] = $r->context;
                }
            }

            foreach ($map as $k => $arr) {
                $map[$k] = array_values(array_unique($arr));
            }

            return $map;
        }

        // fallback: kolom enum single context
        $rows = DB::table('vouchers')->whereIn('id', $voucherIds)->get(['id', 'context']);
        foreach ($rows as $r) {
            $map[$r->id] = (isset($r->context) && is_string($r->context) && trim($r->context) !== '')
                ? [trim($r->context)]
                : [];
        }

        return $map;
    }

    protected function generateUniqueCode(string $voucherCode): string
    {
        $prefix = Str::upper(preg_replace('/[^A-Za-z0-9]/', '', $voucherCode));
        $prefix = Str::substr($prefix ?: 'VOUCHER', 0, 12);

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $code = $prefix.'-'.Str::upper(Str::random(8));
            if (! VoucherCode::where('code', $code)->exists()) {
                return $code;
            }
        }

        throw new \RuntimeException('Gagal membuat kode voucher unik. Silakan coba lagi.');
    }

    /**
     * Persist and return a requested number of individual, printable codes.
     */
    protected function generateVoucherCodes(Voucher $voucher, int $quantity): array
    {
        $codes = [];
        for ($i = 0; $i < $quantity; $i++) {
            $codes[] = VoucherCode::create([
                'voucher_id' => $voucher->id,
                'code' => $this->generateUniqueCode($voucher->code),
                // Individual codes start active and can be controlled separately.
                'is_active' => true,
                // Always inherit the master voucher expiry.
                'expires_at' => $voucher->valid_until,
            ]);
        }

        return $codes;
    }

    protected function generateVoucherCode(): string
    {
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $code = 'VCR-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
            if (! Voucher::where('code', $code)->exists()) {
                return $code;
            }
        }

        throw new \RuntimeException('Gagal membuat kode voucher otomatis. Silakan coba lagi.');
    }
}
