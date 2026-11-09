<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Services\BrandListingNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class BrandListingController extends Controller
{
    private const STATUSES = ['pending', 'approved', 'rejected'];

    private const STATUS_LABELS = [
        'pending' => 'Menunggu',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
    ];

    private const MARKER = 'brand_listing';

    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));
        $storeId = trim((string) $request->query('store_id', ''));
        $from = $request->query('from') ?: now()->startOfMonth()->toDateString();
        $to = $request->query('to') ?: now()->toDateString();

        if (! in_array($status, self::STATUSES, true)) {
            $status = '';
        }

        $baseQuery = $this->baseQuery($q, $status, $storeId, $from, $to);

        $summary = (clone $baseQuery)
            ->selectRaw("
                COUNT(*)::int as total,
                COUNT(*) FILTER (WHERE p.status = 'pending')::int as pending,
                COUNT(*) FILTER (WHERE p.status = 'approved')::int as approved,
                COUNT(*) FILTER (WHERE p.status = 'rejected')::int as rejected
            ")
            ->first();

        $rows = (clone $baseQuery)
            ->selectRaw("
                p.id,
                p.name as brand_name,
                p.status,
                p.created_at,
                detail.value->>'contact_name' as contact_name,
                detail.value->>'contact_phone' as contact_phone,
                detail.value->>'contact_email' as contact_email,
                detail.value->>'price_list_path' as price_list_path,
                detail.value->>'notes' as notes,
                detail.value->>'review_notes' as review_notes,
                detail.value->>'reviewed_at' as reviewed_at,
                store.name as store_name,
                requester.name as requested_by_name,
                reviewer.name as reviewed_by_name
            ")
            ->orderByRaw("CASE p.status WHEN 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('p.created_at')
            ->paginate(15)
            ->withQueryString()
            ->through(function ($row): array {
                $data = (array) $row;
                $data['status_label'] = self::STATUS_LABELS[$data['status']] ?? $data['status'];
                $data['price_list_url'] = $data['price_list_path']
                    ? route('management.brand-listings.download', ['id' => $data['id']])
                    : null;

                return $data;
            });

        $stores = DB::table('stores')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('Management/BrandListings', [
            'rows' => $rows,
            'summary' => [
                'total' => (int) ($summary->total ?? 0),
                'pending' => (int) ($summary->pending ?? 0),
                'approved' => (int) ($summary->approved ?? 0),
                'rejected' => (int) ($summary->rejected ?? 0),
            ],
            'stores' => $stores,
            'statusOptions' => collect(self::STATUSES)
                ->map(fn (string $value): array => [
                    'value' => $value,
                    'label' => self::STATUS_LABELS[$value],
                ])
                ->values()
                ->all(),
            'filters' => [
                'q' => $q,
                'status' => $status,
                'store_id' => $storeId,
                'from' => $from,
                'to' => $to,
            ],
            'canDecide' => $this->canDecide(),
            'urls' => [
                'index' => route('management.brand-listings.index'),
                'store' => route('management.brand-listings.store'),
                'approve' => route('management.brand-listings.approve', ['id' => '__ID__']),
                'reject' => route('management.brand-listings.reject', ['id' => '__ID__']),
            ],
        ]);
    }

    public function store(Request $request, BrandListingNotifier $notifier): RedirectResponse
    {
        $normalized = [
            'brand_name' => $this->normalizeText($request->input('brand_name')),
            'contact_name' => $this->normalizeText($request->input('contact_name')),
            'contact_phone' => $this->normalizeText($request->input('contact_phone')),
            'contact_email' => $this->normalizeEmail($request->input('contact_email')),
        ];

        $request->merge($normalized);

        $data = $request->validate([
            'store_id' => [
                'nullable',
                'uuid',
                Rule::exists('stores', 'id')->whereNull('deleted_at'),
            ],
            'brand_name' => ['required', 'string', 'max:150'],
            'contact_name' => ['nullable', 'string', 'max:150'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:150'],
            'price_list' => [
                'nullable',
                'file',
                'mimes:pdf,xls,xlsx,csv,jpg,jpeg,png,webp',
                'max:10240',
            ],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'store_id.exists' => 'Store yang dipilih tidak tersedia.',
            'brand_name.required' => 'Nama brand wajib diisi.',
            'price_list.mimes' => 'Lampiran harus berupa PDF, Excel, CSV, JPG, PNG, atau WEBP.',
            'price_list.max' => 'Ukuran lampiran maksimal 10 MB.',
        ]);

        $duplicate = $this->baseQuery('', '', '', null, null)
            ->whereRaw('LOWER(p.name) = ?', [mb_strtolower($data['brand_name'])])
            ->where('p.status', 'pending')
            ->when(
                ! empty($data['store_id']),
                fn ($query) => $query->whereRaw("detail.value->>'store_id' = ?", [$data['store_id']])
            )
            ->exists();

        if ($duplicate) {
            return back()
                ->withInput()
                ->withErrors([
                    'brand_name' => 'Brand ini sudah memiliki pengajuan yang masih menunggu.',
                ]);
        }

        $priceListPath = null;

        if ($request->hasFile('price_list')) {
            $priceListPath = $request->file('price_list')->store('brand-listings', 'public');
        }

        $id = (string) Str::uuid();
        $now = now();

        try {
            DB::transaction(function () use ($id, $now, $data, $priceListPath): void {
                DB::table('partners')->insert([
                    'id' => $id,
                    'name' => $data['brand_name'],
                    'status' => 'pending',
                    'billing_cycle' => self::MARKER,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $this->saveDetail($id, [
                    'store_id' => $data['store_id'] ?? null,
                    'requested_by' => auth()->id(),
                    'contact_name' => $data['contact_name'] ?? null,
                    'contact_phone' => $data['contact_phone'] ?? null,
                    'contact_email' => $data['contact_email'] ?? null,
                    'price_list_path' => $priceListPath,
                    'notes' => $data['notes'] ?? null,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'review_notes' => null,
                ], $now);
            });
        } catch (Throwable $exception) {
            if ($priceListPath) {
                Storage::disk('public')->delete($priceListPath);
            }

            report($exception);

            return back()
                ->withInput()
                ->withErrors(['brand_name' => 'Pengajuan gagal disimpan. Silakan coba kembali.']);
        }

        $notifier->submitted($id);

        return back()->with('success', 'Pengajuan listing brand berhasil dikirim.');
    }

    public function approve(Request $request, string $id, BrandListingNotifier $notifier): RedirectResponse
    {
        return $this->decide($request, $id, 'approved', $notifier);
    }

    public function reject(Request $request, string $id, BrandListingNotifier $notifier): RedirectResponse
    {
        return $this->decide($request, $id, 'rejected', $notifier);
    }

    public function download(string $id): StreamedResponse
    {
        $path = $this->detail($id)['price_list_path'] ?? null;

        abort_unless(is_string($path) && $path !== '', 404);
        abort_unless(Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->download($path, basename($path));
    }

    private function baseQuery(
        string $q,
        string $status,
        string $storeId,
        ?string $from,
        ?string $to
    ) {
        return DB::table('partners as p')
            ->join('app_settings as detail', 'detail.key', '=', DB::raw("'brand_listing:' || p.id::text"))
            ->leftJoin('stores as store', 'store.id', '=', DB::raw("(detail.value->>'store_id')::uuid"))
            ->leftJoin('users as requester', 'requester.id', '=', DB::raw("(detail.value->>'requested_by')::uuid"))
            ->leftJoin('users as reviewer', 'reviewer.id', '=', DB::raw("(detail.value->>'reviewed_by')::uuid"))
            ->where('p.billing_cycle', self::MARKER)
            ->whereNull('p.deleted_at')
            ->when($q !== '', function ($query) use ($q): void {
                $like = '%'.$q.'%';

                $query->where(function ($search) use ($like): void {
                    $search->where('p.name', 'ILIKE', $like)
                        ->orWhere('detail.value::text', 'ILIKE', $like)
                        ->orWhere('store.name', 'ILIKE', $like)
                        ->orWhere('requester.name', 'ILIKE', $like)
                        ->orWhere('reviewer.name', 'ILIKE', $like);
                });
            })
            ->when($status !== '', fn ($query) => $query->where('p.status', $status))
            ->when($storeId !== '', fn ($query) => $query->whereRaw("detail.value->>'store_id' = ?", [$storeId]))
            ->when($from, fn ($query) => $query->whereDate('p.created_at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('p.created_at', '<=', $to));
    }

    private function decide(Request $request, string $id, string $status, BrandListingNotifier $notifier): RedirectResponse
    {
        $this->ensureManagementAccess();

        $data = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $partner = DB::table('partners')
            ->where('id', $id)
            ->where('billing_cycle', self::MARKER)
            ->whereNull('deleted_at')
            ->first(['id', 'name', 'status']);

        if (! $partner) {
            return back()->with('error', 'Pengajuan listing brand tidak ditemukan.');
        }

        if ((string) $partner->status !== 'pending') {
            return back()->with('error', 'Hanya pengajuan yang masih menunggu yang dapat diproses.');
        }

        if ($status === 'rejected' && trim((string) ($data['review_notes'] ?? '')) === '') {
            return back()->withErrors([
                'review_notes' => 'Alasan penolakan wajib diisi.',
            ]);
        }

        $now = now();

        DB::transaction(function () use ($id, $partner, $status, $data, $now): void {
            DB::table('partners')
                ->where('id', $id)
                ->where('billing_cycle', self::MARKER)
                ->where('status', 'pending')
                ->update([
                    'status' => $status,
                    'updated_at' => $now,
                ]);

            $detail = $this->detail($id);
            $detail['reviewed_by'] = auth()->id();
            $detail['reviewed_at'] = $now->toIso8601String();
            $detail['review_notes'] = $data['review_notes'] ?? null;

            $this->saveDetail($id, $detail, $now);

            if ($status === 'approved') {
                $this->ensureBrandExists((string) $partner->name, $now);
            }
        });

        $notifier->decided($id, $status);

        return back()->with('success', $status === 'approved'
            ? 'Pengajuan listing brand disetujui dan brand ditambahkan ke master.'
            : 'Pengajuan listing brand ditolak.');
    }

    private function ensureBrandExists(string $name, $now): void
    {
        $exists = DB::table('brands')
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->whereNull('deleted_at')
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('brands')->insert([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function detail(string $id): array
    {
        $value = DB::table('app_settings')
            ->where('key', 'brand_listing:'.$id)
            ->value('value');

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return is_array($value) ? $value : [];
    }

    private function saveDetail(string $id, array $detail, $now): void
    {
        $key = 'brand_listing:'.$id;
        $value = json_encode($detail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        DB::table('app_settings')->updateOrInsert(
            ['key' => $key],
            [
                'value' => $value,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    private function canDecide(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        $managementRoles = ['superadmin', 'administrator', 'admin', 'management', 'manager'];

        return $user->getRoleNames()
            ->map(fn ($role) => preg_replace('/[^a-z0-9]/', '', strtolower($role)))
            ->contains(fn ($role) => in_array($role, $managementRoles, true));
    }

    private function ensureManagementAccess(): void
    {
        abort_unless($this->canDecide(), 403);
    }

    private function normalizeText(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : preg_replace('/\s+/u', ' ', $value);
    }

    private function normalizeEmail(mixed $value): ?string
    {
        $email = $this->normalizeText($value);

        return $email === null ? null : mb_strtolower($email, 'UTF-8');
    }
}
