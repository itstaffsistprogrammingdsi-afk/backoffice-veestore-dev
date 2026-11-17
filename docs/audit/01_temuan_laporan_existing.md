# Temuan Audit Laporan yang Sudah Ada

**Tanggal audit:** 12 November 2026  
**Metode:** pembacaan route, controller, query, ekspor, view, permission, dan hitung ulang read-only pada database operasional `veestore_dev1` serta database backoffice `veestore_bo_dev`  
**Sampel periode:** 24 Oktober 2026, 27 Oktober 2026, 4 November 2026, dan rentang 1-30 November 2026  
**Batas:** tidak ada perubahan kode, skema, atau data pada tahap ini. Semua angka adalah hasil query read-only pada snapshot 12 November 2026.

## 1. Ringkasan

Dari 176 route laporan/monitoring, 145 route tidak memiliki middleware permission. Beberapa laporan finansial dan data pelanggan dapat diakses oleh semua user yang sudah login. Di sisi perhitungan, ditemukan:

- Total pembayaran e-commerce tidak masuk ke "Total Dibayar" pada laporan Pemasukan & Pembayaran karena `paid_amount` e-commerce selalu 0.
- Dua laporan memakai definisi omzet berbeda: satu memakai nilai pembayaran termasuk ongkir dan payment fee, satu memakai nilai produk setelah diskon.
- Laporan voucher toko hilang seluruhnya saat filter tanggal dipakai.
- Riwayat pelanggan e-commerce memakai tabel yang tidak ada sehingga selalu kosong.
- CRM mengeluarkan order berstatus `packing` dari belanja pelanggan, padahal pembayarannya lunas.
- Laporan laba rugi memakai harga `base` sebagai HPP, padahal tidak ada data HPP dan 1.443 dari 1.444 produk memiliki `base = store`.

## 2. Tabel temuan

| ID | Laporan | Lokasi (file:baris) | Jenis | Kondisi | Bukti | Dampak | Perbaikan yang disarankan | Severity | Perlu keputusan process owner |
|---|---|---|---|---|---|---|---|---|---|
| F-01 | Laba Rugi | `app/Http/Controllers/Reports/ProfitLossController.php:52-121`; `routes/web.php:1139-1142` | Keamanan | `addExpense` dan `deleteExpense` tidak memakai permission atau `canManage`; semua user login bisa menambah/menghapus beban | Route hanya `auth`; tidak ada middleware permission di controller; `ReceivablePayable` punya `ensureCanManage`, `ProfitLoss` tidak | Beban operasional dan laba bersih dapat diubah siapa pun | Tambahkan permission khusus (`manage profit-loss-expenses`) dan role check; audit nilai lama | Kritis | Ya |
| F-02 | Laba Rugi | `app/Http/Controllers/Reports/ProfitLossController.php:27-41`; `resources/js/pages/Reports/ProfitLoss.vue:85,110`; `app/Domain/Analytics/Support/FinancialBreakdown.php:100-110` | Definisi | Laporan berjudul "Laba Rugi" dan memakai label "HPP / Harga Pokok Penjualan", tetapi `base_price` bukan HPP; tidak ada kolom cost di database | 1.443 dari 1.444 produk memiliki `base = store = ecommerce`; hanya 1 produk base di bawah store. `gross_profit = net_sales - base_price` | Laba kotor/laba bersih menyesatkan; pada data sampel laba kotor hampir selalu negatif sebesar diskon | Ganti label menjadi "Estimasi Laba Operasional" dan "Nilai Harga Dasar (bukan HPP)"; jangan sebut laba akuntansi sampai ada sumber HPP | Tinggi | Ya |
| F-03 | Pemasukan & Pembayaran | `app/Http/Controllers/Monitoring/PaymentIncomeRealtimeController.php:292-295,364-367,406-408`; `app/Exports/PaymentIncomeRealtimeExport.php:42-44` | Hitung | `total_paid` menjumlahkan `payments.paid_amount`, yang 0 untuk seluruh pembayaran e-commerce | 24 Okt 2026: `total_amount` Rp1.117.500; `total_paid` Rp129.000; pembayaran e-commerce Rp992.500 tidak masuk `paid_amount` | Ringkasan dan ekspor "Total Dibayar" understated besar; kas masuk e-commerce tidak terlihat | Gunakan `COALESCE(NULLIF(paid_amount,0), total_amount)` untuk e-commerce, atau tampilkan dua kolom: "Nilai Pembayaran" dan "Uang Diterima POS" | Tinggi | Tidak (bug teknis) |
| F-04 | Omset Kotor & Bersih | `app/Http/Controllers/Monitoring/OmsetKotorSalesController.php:358-371,540-567,770-771` | Hitung/Definisi | Omset bersih dihitung dari harga baris dikurangi diskon, bukan dari nilai pembayaran | 27 Okt 2026: laporan `net_sales` Rp82.000; total pembayaran POS Rp81.896; selisih Rp104 pada satu keranjang | Omset dan margin tidak sama dengan uang yang diterima; selisih kecil terakumulasi | Tambahkan kolom rekonsiliasi "Nilai Pembayaran" dan "Selisih"; jelaskan sumber omzet | Tinggi | Ya (pilih source of truth) |
| F-05 | Ringkasan Usaha vs Omset | `app/Domain/Analytics/Queries/BusinessOverviewQuery.php:594-665,669-766`; `app/Http/Controllers/Monitoring/OmsetKotorSalesController.php:540-567` | Definisi | Dua laporan memakai definisi "penjualan/omzet" berbeda | 24 Okt 2026: Ringkasan Usaha `revenue` Rp1.117.500 (termasuk ongkir Rp52.000 + payment fee Rp14.500); Omset Kotor `net_sales` Rp1.050.999,99; selisih Rp66.500,01 | Manajemen melihat dua angka penjualan berbeda untuk periode sama | Tetapkan definisi: "Penerimaan pembayaran (termasuk ongkir/fee)" vs "Omset produk"; tampilkan jembatan rekonsiliasi | Tinggi | Ya |
| F-06 | Omset Cabang / Omset Kotor / Laba Rugi | `app/Http/Controllers/Monitoring/OmsetCabangController.php:554,675`; `app/Http/Controllers/Monitoring/OmsetKotorSalesController.php:582,675,710` | Definisi | Tidak ada satu basis tanggal: Omset Cabang memakai `paid_at` untuk toko dan `eo.created_at` untuk e-commerce; Omset Kotor memakai `scd.created_at` (item masuk keranjang) dan `eod.created_at` | Kode: Omset Cabang store `COALESCE(paid_visit.paid_at,...)::date`; Omset Kotor store `scd.created_at::date`; e-commerce `eo.created_at::date`/`eod.created_at::date`. Laba Rugi memanggil Omset Kotor | Transaksi yang dibuat akhir bulan dan dibayar awal bulan masuk periode berbeda; laporan yang sama dapat tidak cocok | Tetapkan satu cutoff (tanggal pembayaran) atau tampilkan dua tanggal: tanggal transaksi dan tanggal pembayaran | Tinggi | Ya |
| F-07 | Omset Cabang | `app/Http/Controllers/Monitoring/OmsetCabangController.php:486-495,646-660` | Hitung | Filter store memakai `POSITION(store_id IN fulfillment_store_ids)`; satu order e-commerce dengan beberapa toko tetap satu baris dengan nilai penuh | `STRING_AGG(DISTINCT outbound.store_id)` menghasilkan daftar toko; filter salah satu ID akan menyertakan seluruh nilai order | Jika fulfillment multi-toko, omzet per cabang dapat dihitung ganda | Pecah alokasi qty/nilai per toko fulfillment atau tampilkan sebagai grup multi-toko tanpa filter per toko | Tinggi | Ya |
| F-08 | Omset Kotor & Bersih | `app/Http/Controllers/Monitoring/OmsetKotorSalesController.php:458-499,612-613,740-741` | Penyajian | Nama diskon/voucher diambil dari semua master aktif pada tanggal transaksi, bukan dari diskon/voucher yang benar-benar dipakai | 27 Okt 2026: keranjang memakai VCR-20261027-LZCSQQ, tetapi subquery voucher akan mengambil 7 voucher aktif; `voucher_usages` tidak dipakai | Pengguna salah menyimpulkan promo yang menghasilkan diskon | Join ke `store_carts.voucher_id`, `voucher_usages`, `store_cart_details.discount_id`, atau snapshot e-commerce | Sedang | Tidak (bug teknis) |
| F-09 | Voucher | `app/Http/Controllers/Reports/VoucherReportController.php:36-56,81-101` | Hitung | Filter tanggal memakai `whereDate('eo.created_at',...)` pada hasil LEFT JOIN e-commerce; baris store (eo NULL) ikut terbuang | 24 Okt 2026: tanpa filter tanggal muncul 2 pemakaian voucher store; dengan `start=end=2026-10-24` hasilnya 0 | Laporan voucher toko kosong setiap kali filter tanggal dipakai | Terapkan filter e-commerce di kondisi JOIN dan filter store di `store_carts.created_at`; hitung keduanya terpisah | Tinggi | Tidak (bug teknis) |
| F-10 | CRM / Riwayat Pelanggan / Omset Cabang CRM | `app/Http/Controllers/Reports/CrmController.php:638-649,761-763,1110-1112` | Hitung | `successStatuses()` tidak memuat status order `packing`, padahal order packing sudah dibayar | 7 order packing senilai Rp892.000 dari total order lunas Rp1.702.600 (52%) tidak masuk belanja pelanggan/omset CRM | Belanja pelanggan dan omzet e-commerce CRM understated besar | Tambahkan `packing` ke status lunas, atau gunakan status pembayaran sebagai sumber | Tinggi | Ya (definisi status) |
| F-11 | Riwayat Pelanggan | `app/Http/Controllers/Reports/HistoryCustomerController.php:109-116` | Hitung | Query e-commerce memakai tabel `ecommerce_payments` yang tidak ada | `information_schema` tidak memuat `ecommerce_payments`; `Schema::hasTable` selalu false | Kanal e-commerce pada Riwayat Pelanggan selalu kosong tanpa pesan | Ganti sumber ke `ecommerce_orders` join `payments`; beri label jika data belum tersedia | Tinggi | Tidak (bug teknis) |
| F-12 | Shift Kasir | `app/Http/Controllers/Monitoring/CashierShiftReportController.php:660-685,730-731,750-779` | Hitung | `omzet` memakai `cashier_session_payment_summaries` dan mengabaikan `cashier_sessions.total_sales`; filter tanggal memakai opened_at ATAU closed_at, daily grouping memakai opened_at | Sesi 20261024-001: `total_sales` 112.000 vs ringkasan pembayaran 108.000; 20261027-001: 36.896 vs 33.896; 20261027-002: 22.000 vs 19.000. 5 sesi tutup melewati tengah malam | Selisih omzet shift tidak terlihat; omzet harian bisa masuk ke hari buka, bukan hari tutup | Tampilkan kedua nilai dan exception; tetapkan atribusi shift lintas hari | Tinggi | Ya |
| F-13 | Transaksi Gantung | `app/Http/Controllers/Monitoring/PendingTransactionReportController.php:101,121,142,164,187,208,232` | Hitung/Definisi | Aging hanya menampilkan transaksi yang `created_at`-nya di dalam periode filter (default 30 hari) | Default `now()->subDays(30)`; transaksi pending yang lebih tua dari 30 hari tidak pernah muncul, justru exception paling tua hilang | Exception aging tidak lengkap | Pisahkan filter "tanggal kejadian" dari "umur minimum"; default tampilkan semua outstanding dengan opsi periode | Sedang | Ya |
| F-14 | Void & Pembatalan | `app/Http/Controllers/Monitoring/VoidCancelCustomerReportController.php:131-164,398-406` | Hitung/Penyajian | `total_voided_amount` menjumlahkan semua status; label status memeriksa `authorized`/`cancelled` yang tidak ada di enum | Enum `void_transaction_status`: `pending,approved,rejected,expired`; kode memeriksa `authorized` dan `cancelled` | "Total void" dapat memasukkan pengajuan yang ditolak/pending; label "Cancel" tidak pernah muncul | Jumlahkan hanya `approved`; samakan label dengan enum | Sedang | Ya |
| F-15 | Kontrol Margin Harga | `app/Http/Controllers/Reports/PriceMarginController.php:118-121,132-137` | Hitung/Definisi | Rumus `(store-base)/base` adalah markup, bukan margin; UI menyebut "Margin" dan "Margin di bawah batas" | Rumus kode; margin seharusnya `(harga-jual - cost)/harga-jual` | Ambang margin salah; produk bisa dinyatakan aman/pelanggaran dengan angka berbeda | Ganti label menjadi "Markup" atau ubah rumus margin; jelaskan basis | Sedang | Ya |
| F-16 | Stok Produk (Physical Stock) | `app/Http/Controllers/Monitoring/PhysicalStockAccumulationController.php:296-299,399-400,355,457`; `resources/js/pages/Monitoring/Report.vue:1025` | Definisi | `total_nominal` menjumlahkan nilai stok toko pada harga `store` (harga jual) dan gudang pada harga `base` (harga dasar) | Kolom `price_type` per baris; total menggabungkan dua basis; label UI "Nilai Stok" | Total nilai persediaan bukan nilai cost dan bukan nilai jual; tidak bisa dipakai untuk valuasi | Tampilkan nilai pada basis yang sama (base/cost) atau dua kolom terpisah | Sedang | Ya |
| F-17 | Stok Opname, Selisih & Penyesuaian | `app/Http/Controllers/Monitoring/StockOpnameVarianceReportController.php:232-239,249` | Hitung | Nilai selisih opname memakai harga `base` terbaru, bukan harga pada tanggal opname | Subquery memilih `ROW_NUMBER() ... ORDER BY valid_from DESC` tanpa filter tanggal opname | Selisih historis berubah saat harga master berubah | Pilih harga yang valid pada `report_at` opname | Sedang | Ya |
| F-18 | Kualitas Data | `app/Http/Controllers/Monitoring/MonitoringCommandCenterController.php:680-689` | Hitung | `qualityCheck` menghitung seluruh baris termasuk yang soft-deleted | Customer tanpa wilayah lengkap: 17 baris semua, 16 baris aktif; 1 baris soft-deleted ikut terhitung | Angka kualitas data dan total bisa menyesatkan | Tambahkan `whereNull('deleted_at')` jika kolom ada | Sedang | Tidak (bug teknis) |
| F-19 | Promo, Poin, Referral & Voucher | `app/Http/Controllers/Monitoring/PromoUsageController.php:127-142,252-256,411-414` | Hitung/Definisi | Pemakaian promo e-commerce tidak memfilter status order; hanya e-commerce, promo toko tidak dihitung | 2 detail dari order `cancelled` dengan diskon Rp10.000 dari total diskon Rp50.000 ikut terhitung | Pemakaian promo overstated dan tidak lengkap lintas kanal | Default filter status lunas; tambahkan sumber promo toko atau beri label "E-commerce saja" | Sedang | Ya |
| F-20 | Semua laporan | `routes/web.php:271,640,1121`; `app/Support/Navigation.php:164-260`; `app/Http/Kernel.php:65` | Keamanan | 145 dari 176 route monitoring/reports tidak memakai middleware permission; banyak menu memakai permission `null` | Route hanya `auth`; contoh `PaymentIncomeRealtimeController`, `ProfitLossController`, `CrmController`, `AuditCenter`; `Navigation` mengizinkan item tanpa permission | Semua user login dapat membuka laporan finansial, PII pelanggan, dan audit log; row-level hanya diterapkan di beberapa laporan | Petakan permission per laporan; terapkan row-level store/warehouse; default deny | Tinggi | Ya |
| F-21 | Ekspor sensitif | `app/Http/Middleware/RecordBusinessAudit.php:20-22`; `app/Http/Controllers/Monitoring/OmsetKotorSalesController.php:313-356` | Keamanan | Middleware audit hanya mencatat POST/PUT/PATCH/DELETE; seluruh ekspor adalah GET | Kode early-return untuk method selain POST/PUT/PATCH/DELETE; route export GET | Ekspor data pelanggan/keuangan tidak punya jejak audit | Catat akses ekspor ke `business_audit_logs` (tanpa tabel baru) atau event audit yang sudah ada | Tinggi | Ya |
| F-22 | Ekspor | `app/Http/Controllers/Monitoring/OmsetKotorSalesController.php:339-356`; `app/Http/Controllers/Monitoring/PaymentIncomeRealtimeController.php:136-143`; `app/Http/Controllers/Monitoring/StorePaymentTransactionController.php:197-204`; `app/Services/ReportExportService.php:19-24` | Performa | Ekspor besar berjalan sinkron dan/atau tanpa batas; Omset Kotor memakai paginate 100.000 sehingga data di atas 100.000 baris terpotong diam-diam | `perPage: 100000` pada export; `->get()` tanpa limit pada ekspor lain; hanya 3 tipe yang masuk queue | Timeout/memory dan total ekspor tidak sama dengan total halaman | Wajibkan periode maksimum, queue untuk semua ekspor berat, dan streaming generator; jangan memakai paginate untuk ekspor | Tinggi | Ya |
| F-23 | Omset Kotor & Bersih | `app/Http/Controllers/Monitoring/OmsetKotorSalesController.php:32-62` | Penyajian | Tidak ada filter outlet/gudang; hanya channel, produk, customer, q, promo, kondisi | Filter yang tersedia di method `index`; tidak ada `store_id`/`warehouse_id` | Laporan tidak bisa dipakai untuk evaluasi cabang | Tambahkan filter outlet atau arahkan ke Omset Cabang | Sedang | Ya |
| F-24 | SLA Pembayaran | `app/Http/Controllers/Monitoring/PaymentSlaReportController.php:12-36,80-95` | Performa/Keamanan | Tidak ada filter periode; query memindai semua order pending; tidak ada permission | `base` hanya memfilter status, tanpa tanggal; route tanpa permission | Beban query tumbuh seiring data; laporan terbuka untuk semua user | Wajibkan periode/umur maksimum; tambahkan permission | Sedang | Ya |
| F-25 | Pemasukan & Pembayaran / Metode Pembayaran | `app/Http/Controllers/Monitoring/PaymentIncomeRealtimeController.php:256-266,331-338`; `app/Http/Controllers/Monitoring/StorePaymentTransactionController.php:294-308` | Hitung | Tidak ada `p.deleted_at` di query Pemasukan; e-commerce juga tidak memfilter status order | PaymentIncome store cuma `whereNull('s.deleted_at')`; e-commerce tanpa `whereNull('p.deleted_at')` dan tanpa status order | Pembayaran soft-deleted/order batal dapat ikut terhitung | Tambahkan `whereNull('p.deleted_at')` dan filter status order | Sedang | Tidak (bug teknis) |
| F-26 | Supplier Insight | `app/Http/Controllers/Monitoring/SupplierInsightController.php:140-160` | Hitung | QC terbaru tidak difilter `qc_type='inbound'`; QC outbound bisa dianggap kerusakan supplier | Subquery `latestQc` hanya memakai `MAX(performed_at)`; enum `qc_type` memiliki inbound/outbound | Damage rate supplier dapat salah bila ada QC outbound | Filter `qc_type='inbound'` atau jelaskan bahwa ini kondisi terakhir | Sedang | Ya |
| F-27 | Slow Moving & Kedaluwarsa | `app/Http/Controllers/Monitoring/ProductSlowmovingController.php:675-676` | Hitung | Penjualan toko hanya mengecualikan `void`, bukan `voided`/`gimmick` | Filter `whereNull(void_status)->orWhere(void_status,'!=','void')`; gimmick ikut terhitung sebagai penjualan | Qty terjual dan status slow moving dapat overstated | Samakan dengan `SaleConditionMetrics::eligibleSaleLineSql` | Sedang | Tidak (bug teknis) |
| F-28 | Riwayat Pelanggan | `app/Http/Controllers/Reports/HistoryCustomerController.php:99,168` | Penyajian/Performa | Riwayat dibatasi 300 (POS) dan 500 (e-commerce) baris tanpa indikasi total | `->limit(300)` dan `->limit(500)` | Riwayat pelanggan tidak lengkap; tidak ada pesan bahwa data dipotong | Gunakan pagination atau tampilkan "menampilkan 300 dari total" | Sedang | Ya |
| F-29 | Anomali Transaksi | `app/Http/Controllers/Monitoring/TransactionAnomalyReportController.php:154-161` | Hitung | Join `payments` ke `store_visit_payments` dapat menggandakan baris pembayaran bila satu payment ditautkan ke lebih dari satu kunjungan | Tidak ada unique `(visit_id,payment_id)`; summary `SUM(total_amount)` dihitung di atas hasil join | Total pembayaran/anomali dapat ganda secara struktural | Deduplikasi payment per ID sebelum join, atau tambahkan unique constraint oleh DBA | Sedang | Ya |
| F-30 | Omset Cabang | `app/Http/Controllers/Monitoring/OmsetCabangController.php:261-295` | Penyajian | Kolom "total_barang_diambil/keluar/diterima" untuk store memakai 1 per item, e-commerce memakai qty fulfillment; satu tabel mencampur definisi | Store `requested_qty=1`; e-commerce `approved_qty` dari outbound | Angka qty antar kanal tidak sebanding | Pisahkan metrik fulfillment e-commerce atau beri label per channel | Rendah | Ya |

## 3. Bukti hitung ulang

### 3.1 Pemasukan & Pembayaran, 24 Oktober 2026

Query independen:

```sql
with store as (
  select p.total_amount, p.paid_amount, p.change_amount
  from store_visit_payments svp
  join payments p on p.id = svp.payment_id
  where p.status = 'paid'
    and p.deleted_at is null
    and coalesce(p.completed_at, p.created_at)::date = '2026-10-24'
), ecom as (
  select p.total_amount, p.paid_amount, p.change_amount
  from ecommerce_orders eo
  join payments p on p.id = eo.payment_id
  where p.status = 'paid'
    and p.deleted_at is null
    and coalesce(p.completed_at, p.created_at)::date = '2026-10-24'
)
select 'store' src, count(*) n, sum(total_amount) total, sum(paid_amount) paid, sum(change_amount) chg from store
union all
select 'ecommerce', count(*), sum(total_amount), sum(paid_amount), sum(change_amount) from ecom;
```

Hasil:

| Sumber | Transaksi | Total | Paid | Change |
|---|---:|---:|---:|---:|
| Store | 4 | 125.000 | 129.000 | 4.000 |
| E-commerce | 7 | 992.500 | 0 | 0 |
| Total | 11 | 1.117.500 | 129.000 | 4.000 |

Laporan menampilkan `total_amount` Rp1.117.500 dan `total_paid` Rp129.000. Uang yang benar-benar diterima adalah Rp1.117.500 (POS Rp125.000 + e-commerce Rp992.500). Jadi label "Total Dibayar" tidak sama dengan uang masuk.

### 3.2 Omset Kotor & Bersih, 27 Oktober 2026

Pemanggilan `OmsetKotorSalesController::financialSummary('2026-10-27','2026-10-27','store')` menghasilkan:

| Metrik laporan | Nilai |
|---|---:|
| Gross sales | 87.000 |
| Voucher discount | 5.000 |
| Net sales | 82.000 |
| Base price | 87.000 |
| Gross profit | -5.000 |

Query pembayaran independen:

```sql
select count(distinct p.id) transactions,
       sum(p.total_amount) payment_total,
       sum(p.paid_amount) paid_total,
       sum(p.change_amount) change_total
from store_visit_payments svp
join payments p on p.id = svp.payment_id
where p.status = 'paid'
  and p.deleted_at is null
  and (p.completed_at at time zone 'Asia/Jakarta')::date = '2026-10-27';
```

Hasil: 6 transaksi, `payment_total` Rp81.896, `paid_total` Rp87.896, `change_total` Rp6.000. Selisih omzet bersih laporan terhadap nilai pembayaran: **Rp104**.

### 3.3 Ringkasan Usaha vs Omset Kotor, 24 Oktober 2026

`BusinessOverviewQuery::build(['period'=>'custom','date_from'=>'2026-10-24','date_to'=>'2026-10-24'])`:

| Metrik | Nilai |
|---|---:|
| gross_revenue | 1.056.000 |
| net_revenue | 1.050.999,99 |
| revenue (pembayaran) | 1.117.500 |

`OmsetKotorSalesController::financialSummary('2026-10-24','2026-10-24','all')`:

| Metrik | Nilai |
|---|---:|
| gross_sales | 1.056.000 |
| net_sales | 1.050.999,99 |

Selisih `revenue` vs `net_sales`: **Rp66.500,01**. Rincian e-commerce hari itu: subtotal produk Rp926.000, ongkir Rp52.000, payment fee Rp14.500, grand total Rp992.500. Jadi perbedaan berasal dari ongkir dan payment fee.

### 3.4 Voucher, filter tanggal

`VoucherReportController::reportData()` dengan filter `start=2026-10-24&end=2026-10-24`:

| Filter | Jumlah voucher | Store used | E-commerce used |
|---|---:|---:|---:|
| Tanpa filter tanggal | 2 | 2 | 0 |
| 24 Oktober 2026 | 0 | 0 | 0 |
| 1-31 Oktober 2026 | 0 | 0 | 0 |

Penyebab: `whereDate('eo.created_at',...)` membuang baris `store_carts` yang tidak punya pasangan `ecommerce_orders`.

### 3.5 CRM, status packing

Query order e-commerce dengan pembayaran `paid`:

| Status order | Jumlah | Nilai grand total |
|---|---:|---:|
| packing | 7 | 892.000 |
| paid | 2 | 31.100 |
| received | 6 | 490.000 |
| shipped | 4 | 289.500 |
| Total | 19 | 1.702.600 |

`successStatuses()` memuat `paid`, `processed`, `shipped`, `received`, tetapi tidak `packing`. Akibatnya 892.000 (52,4%) tidak masuk agregasi CRM.

### 3.6 Shift kasir

| Sesi | `cashier_sessions.total_sales` | Jumlah ringkasan pembayaran | Selisih |
|---|---:|---:|---:|
| CS-VEES.1-20261024-001 | 112.000 | 108.000 | 4.000 |
| CS-VEES.1-20261027-001 | 36.896 | 33.896 | 3.000 |
| CS-VEES.1-20261027-002 | 22.000 | 19.000 | 3.000 |
| CS-VEES.10-20261104-001 | 27.000 | 27.000 | 0 |

Laporan memakai `COALESCE(pay_sum.total_payment_amount, cs.total_sales, 0)`, sehingga nilai 112.000/36.896/22.000 tidak pernah tampil dan selisih tidak menjadi exception.

### 3.7 Promo usage, order batal

| Status order | Detail dengan diskon | Diskon |
|---|---:|---:|
| cancelled | 2 | 10.000 |
| packing | 2 | 20.000 |
| shipped | 2 | 20.000 |
| Total | 6 | 50.000 |

Laporan tidak memfilter status order, sehingga 20% nilai diskon berasal dari order batal.

### 3.8 Kualitas data

| Pemeriksaan | Semua baris | Hanya aktif | Selisih |
|---|---:|---:|---:|
| Customer tanpa wilayah lengkap | 17 | 16 | 1 |
| Store tanpa koordinat | 0 | 0 | 0 |
| Warehouse tanpa koordinat | 0 | 0 | 0 |

### 3.9 Nama voucher pada Omset Kotor

Pada 27 Oktober 2026, keranjang `3e414f26-5898-4064-8414-0cd2610dd9c1` memakai voucher `VCR-20261027-LZCSQQ` (Voucher Tahun Baru 2). Subquery `voucherNamesSql` tidak membaca `store_carts.voucher_id`/`voucher_usages`; ia mengambil semua voucher aktif pada tanggal itu. Jumlah voucher aktif pada 27 Oktober 2026: **7**. Jadi laporan berpotensi menampilkan 7 nama voucher untuk satu pemakaian.

## 4. Cakupan pemeriksaan laporan

| Kelompok laporan | Route/controller utama | Status | Catatan |
|---|---|---|---|
| Omzet per cabang | `OmsetCabangController` | Diperiksa + hitung ulang | Temuan F-06, F-07, F-30 |
| Omset kotor/bersih | `OmsetKotorSalesController` | Diperiksa + hitung ulang | Temuan F-04, F-08, F-23 |
| Sample, gimmick, gratis | `SampleGimmickReportController` + query domain | Diperiksa | Pemisahan gimmick dari omzet sudah diuji otomatis |
| Kontrol margin harga | `PriceMarginController` | Diperiksa | Temuan F-15 |
| Laba rugi | `ProfitLossController` | Diperiksa + hitung ulang | Temuan F-01, F-02 |
| Piutang & hutang manual | `ReceivablePayableController` | Diperiksa | Label manual sudah ada; tidak ada aging AP/AR nyata |
| Produk terlaris | `ProdukTerlarisController` | Diperiksa ringkas | Memakai qty penjualan; belum ada temuan material |
| Pemasukan & pembayaran | `PaymentIncomeRealtimeController` | Diperiksa + hitung ulang | Temuan F-03, F-25 |
| Metode pembayaran & kembalian | `StorePaymentTransactionController` | Diperiksa | Default semua status; perlu keputusan process owner |
| EDC BCA | `EdcBcaQuery` | Diperiksa | Sudah berlabel estimasi MDR; default status semua |
| Transaksi gantung | `PendingTransactionReportController` | Diperiksa | Temuan F-13 |
| Anomali transaksi | `TransactionAnomalyReportController` | Diperiksa | Temuan F-29 |
| Pembayaran e-commerce | `EcommercePaymentController` | Diperiksa | Fallback `paid_amount` sudah ada |
| SLA pembayaran | `PaymentSlaReportController` | Diperiksa | Temuan F-24 |
| Shift kasir | `CashierShiftReportController` | Diperiksa + hitung ulang | Temuan F-12 |
| Pembelian per shift | `ShiftPurchaseQuery` | Diperiksa | Row-level store sudah ada |
| Void & cancel | `VoidCancelCustomerReportController` | Diperiksa | Temuan F-14 |
| Rekonsiliasi order vs payment | `MonitoringCommandCenterController::paymentReconciliation` | Diperiksa | Selisih nominal 0 pada snapshot; status belum menyertakan `capture`/`settlement` |
| CRM | `CrmController` | Diperiksa | Temuan F-10 |
| Demografi pelanggan | `CustomerDemographicsQuery` | Diperiksa ringkas | Query agregat; tidak ada temuan material pada sampel |
| Preferensi pelanggan | `CustomerPreferenceController` | Diperiksa ringkas | Bergantung pada data transaksi yang sama |
| Riwayat pelanggan | `HistoryCustomerController` | Diperiksa | Temuan F-11, F-28 |
| Loyalty & voucher | `PromoLoyaltyReportController`, `VoucherReportController`, `PromoUsageController` | Diperiksa | Temuan F-09, F-19 |
| Stok per cabang / WMS | `StokPercabangController`, `StokWmsController` | Diperiksa | Snapshot stok dari item; belum ada temuan material |
| Mutasi stok WMS | `MutasiStockWmsController` | Diperiksa ringkas | Berbasis outbound WMS |
| Stok barang keluar/masuk | `StockMovementReportController` | Diperiksa ringkas | Menggabungkan inbound/outbound/transfer |
| Stok opname & selisih | `StockOpnameVarianceReportController`, `StoreStockOpnameReportController`, `AuditWmsOpnameController` | Diperiksa | Temuan F-17 |
| Barang hilang & rusak WMS | `WarehouseLossDamageReportController` | Diperiksa | Tidak ada row-level warehouse |
| Quality control | `MonitoringCommandCenterController::qualityControl` | Diperiksa | Membatasi 100 baris per sumber; summary dihitung dari baris yang sudah dibatasi |
| Stok minimum | `StoreStockBarrierController`, `WarehouseStockBarrierController` | Diperiksa ringkas | Pengaturan, bukan laporan historis |
| Stok produk / valuasi | `PhysicalStockAccumulationController` | Diperiksa | Temuan F-16 |
| FIFO/FEFO | `FifoFefoPriorityReportController` | Diperiksa ringkas | Prioritas berdasarkan tanggal |
| Product aging | `ProductAgingMonitoringController` | Diperiksa | Row-level store/warehouse sudah ada |
| Slow moving / kedaluwarsa | `ProductSlowmovingController` | Diperiksa | Temuan F-27 |
| Tracking barang | `TrackingBarangController` | Diperiksa ringkas | Berbasis histori item |
| Anomali item | `AnomaliItemMonitoringController` | Diperiksa ringkas | Berbasis status/lokasi item |
| Pesanan cabang | `OrderCabangController` | Diperiksa | Status dan qty order; tidak ada temuan material |
| Order handled | `OrderHandledController` | Diperiksa ringkas | Status order dan retur |
| Fulfillment & SLA | `MonitoringCommandCenterController::fulfillmentControl` | Diperiksa | SLA dasar 24 jam; tidak ada carrier SLA |
| Supplier insight | `SupplierInsightController` | Diperiksa | Temuan F-26 |
| Akses pengguna | `UserMonitoringController` | Diperiksa | Tidak ada permission/row-level |
| Audit pengguna gudang | `WarehouseUserAuditReportController` | Diperiksa ringkas | Union aktivitas gudang |
| Audit center | `MonitoringCommandCenterController::auditCenter` | Diperiksa | Audit log backoffice |
| Riwayat perubahan master | `MasterChangeHistoryReportController` | Diperiksa | Berbasis business_audit_logs |
| Penyesuaian harga overnight | `MasterChangeHistoryReportController::overnight` | Diperiksa | Filter jam 22-06 |
| Kualitas data | `MonitoringCommandCenterController::dataQuality` | Diperiksa | Temuan F-18 |
| Ekspor | `ReportExportController`, `ReportExportService`, seluruh `app/Exports` | Diperiksa | Temuan F-21, F-22 |

## 5. Keterbatasan

- Angka di atas berasal dari database pengembangan yang volumenya kecil. Temuan struktural (join, status, filter tanggal) tetap berlaku, tetapi besaran dampak produksi harus diukur ulang.
- WMS tidak diverifikasi lewat login. Modul WMS diverifikasi melalui tabel dan laporan backoffice yang membaca tabel WMS.
- Laporan yang membutuhkan data eksternal (bank, GL, pajak, backup) tidak dapat diuji karena datanya tidak ada.
- Tidak ada perubahan kode atau data pada tahap audit ini.
