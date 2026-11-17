# Audit Gap Laporan Ekosistem Retail

**Tanggal pemeriksaan:** 12 November 2026  
**Sistem yang diperiksa:** Immostore Backoffice, Veestore POS, WMS, dan e-commerce Vee Store  
**Jenis pemeriksaan:** Inventarisasi laporan dan analisis gap lintas sistem  
**Status dokumen:** Hasil audit read-only

## 1. Kesimpulan eksekutif

Ekosistem saat ini sudah memiliki cakupan laporan operasional yang luas, terutama untuk stok, opname, transaksi, pembayaran, fulfillment, promo, loyalty, aktivitas pengguna, dan aktivitas gudang.

Namun, sistem belum lengkap sebagai management control system dan financial control system. Gap terbesar terdapat pada:

1. Rekonsiliasi stok lintas sistem.
2. Rekonsiliasi payment sampai settlement bank.
3. Valuasi persediaan dan rekonsiliasi ke akuntansi.
4. Siklus pembelian dan three-way matching.
5. Jurnal, General Ledger, pajak, dan e-faktur.
6. Segregation of Duties dan review akses berkala.
7. Pelaporan backup, restore, dan disaster recovery.
8. KPI strategis inventory, supplier, e-commerce, dan promo.

Report yang ada saat ini lebih kuat untuk memantau aktivitas operasional dibanding membuktikan kelengkapan finansial, rekonsiliasi antar sistem, atau efektivitas kontrol internal.

## 2. Cakupan dan keterbatasan pemeriksaan

| Sistem | Status pemeriksaan | Keterangan |
|---|---|---|
| Immostore Backoffice | Berhasil diperiksa | Pemeriksaan repository, route, controller, menu laporan, ekspor, dan pengujian otomatis |
| Veestore POS | Berhasil diperiksa | Login dan inventarisasi menu operasional serta laporan berhasil dilakukan |
| E-commerce Vee Store | Berhasil diperiksa | Kanal publik berhasil diperiksa; tidak tersedia modul laporan internal pada halaman publik |
| WMS | Belum sepenuhnya berhasil | Halaman login dapat dibuka, tetapi autentikasi berhenti pada status `Memproses`; menu internal belum dapat diverifikasi langsung |

Penilaian laporan WMS sementara menggunakan data dan modul WMS yang ditarik ke Immostore Backoffice. Kesimpulan mengenai ketiadaan report di WMS harus divalidasi ulang setelah login WMS berhasil.

## 3. Laporan yang sudah tersedia

### 3.1 Penjualan dan pembayaran

- Omzet per cabang.
- Sample, gimmick, dan transaksi gratis.
- Kontrol margin harga.
- Estimasi laba rugi.
- Catatan piutang dan hutang.
- Produk terlaris dan fast moving.
- Pemasukan dan pembayaran real-time.
- Metode pembayaran dan kembalian.
- EDC BCA.
- Transaksi menggantung.
- Anomali transaksi.
- Pembayaran e-commerce.
- SLA pembayaran.
- Shift kasir.
- Pembelian per shift.
- Void dan cancel.
- Rekonsiliasi nilai order terhadap record payment.

### 3.2 Pelanggan, loyalty, dan promo

- CRM.
- Demografi pelanggan.
- Preferensi pelanggan.
- Riwayat pelanggan.
- Loyalty point dan voucher per akun.
- Promo, point, referral, dan voucher.
- Penggunaan promo.
- Sinkronisasi batas diskon.

### 3.3 Stok dan gudang

- Stok per cabang.
- Stok warehouse.
- Mutasi stok WMS.
- Barang keluar dan masuk.
- Selisih dan adjustment stock opname.
- Stock opname toko dan WMS.
- Barang hilang dan rusak.
- Quality control dan barang cacat.
- Stok minimum toko dan gudang.
- Physical stock.
- FIFO dan FEFO.
- Product aging.
- Slow moving dan kedaluwarsa.
- Tracking barang.
- Anomali item.
- Supplier insight untuk kerusakan dan retur.

### 3.4 Pesanan, retur, dan fulfillment

- Pesanan cabang.
- Order handled.
- Fulfillment dan SLA.
- Inbound dan QC history pada POS.
- Retur toko dan pelanggan.
- Transfer antar toko.
- Riwayat transaksi POS.
- X-report dan laporan sesi kasir.

### 3.5 Audit dan akses

- Akses pengguna harian.
- Audit pengguna gudang.
- Audit center.
- Riwayat perubahan master.
- Penyesuaian harga overnight.
- Cetak ulang label.
- Kualitas data.

## 4. Laporan wajib yang belum tersedia

| ID | Prioritas | Laporan yang belum tersedia | Isi minimum | Risiko jika tidak tersedia |
|---|---|---|---|---|
| GAP-01 | Kritis | Rekonsiliasi stok lintas sistem | Per SKU dan lokasi: WMS on-hand, POS on-hand, e-commerce available, reserved, in-transit, selisih, umur selisih, sumber transaksi terakhir | Overselling, stok ganda, selisih tidak terdeteksi, laporan stok tidak dapat dipercaya |
| GAP-02 | Kritis | Rekonsiliasi settlement payment ke bank | Order, payment gateway, settlement bank, MDR, fee, refund, chargeback, nilai bruto/neto, tanggal settlement, exception aging | Pendapatan hilang, settlement kurang, refund tidak terkendali |
| GAP-03 | Kritis | Rekonsiliasi persediaan ke akuntansi | Kuantitas, nilai FIFO/average, COGS, adjustment, write-off, retur, saldo subledger, saldo jurnal | Salah saji persediaan, COGS, margin, dan laporan keuangan |
| GAP-04 | Kritis | Jurnal dan rekonsiliasi General Ledger | Penjualan, pajak, COGS, inventory, kas, piutang, hutang, voucher, payment fee, jurnal, control total | Posting ganda, jurnal hilang, debit/kredit tidak seimbang |
| GAP-05 | Kritis | Three-way matching pembelian | PR, PO, penerimaan, invoice supplier, tolerance, blocked invoice, approval, pembayaran | Pembayaran barang yang belum diterima, harga berlebih, supplier fraud |
| GAP-06 | Kritis | Pajak dan e-faktur | DPP, PPN keluaran/masukan, retur, pembatalan, nomor faktur, status e-faktur, rekonsiliasi jurnal | Sanksi pajak dan salah saji kewajiban pajak |
| GAP-07 | Kritis | Segregation of Duties dan access certification | Konflik maker-approver, privileged user, akun yatim, akses lintas outlet, dormant access, hasil review | Fraud dan perubahan transaksi tanpa kontrol independen |
| GAP-08 | Kritis | Backup, restore, dan disaster recovery | Status backup, umur backup, lokasi salinan, hasil restore, RTO/RPO aktual, kegagalan job, tanggal DR test | Kehilangan data dan kegagalan pemulihan layanan |

## 5. Laporan yang tersedia tetapi belum memadai

### 5.1 Payment Reconciliation

Backoffice telah mencocokkan nilai order dengan record payment. Cakupan tersebut belum sampai ke penerimaan dana yang sebenarnya.

Komponen yang belum tersedia:

- Settlement bank.
- MDR dan payment fee.
- Nilai bruto dan neto.
- Doku disbursement.
- Refund dan chargeback.
- Settlement terlambat.
- Selisih bank terhadap sistem.
- PIC dan aging untuk exception.

**Status:** tersedia sebagian.

### 5.2 Laba Rugi

Laporan laba rugi menghitung net sales, base price, gross profit, beban operasional manual, dan net profit. Beban operasional disimpan sebagai data manual dalam `app_settings`.

Komponen yang belum tersedia:

- Jurnal akuntansi aktual.
- Payment fee.
- Ongkir dan subsidi ongkir.
- Pajak.
- Penyusutan.
- Provision barang rusak atau kedaluwarsa.
- Alokasi biaya per outlet.
- Rekonsiliasi General Ledger.
- Approval dan audit trail perubahan beban.

**Status:** estimasi laba operasional, belum laporan laba rugi akuntansi.

### 5.3 Piutang dan Hutang

Laporan tersedia, tetapi sumbernya berupa input manual, bukan hasil invoice, payment, credit note, atau jurnal transaksi.

Komponen yang belum tersedia:

- Aging AP per supplier.
- Aging AR per customer dan channel.
- Payment term dari transaksi sumber.
- Credit note dan debit note.
- Rekonsiliasi pembayaran.
- Overdue escalation.
- DSO dan DPO.
- Invoice yang belum dipasangkan.

**Status:** tersedia sebagian dan belum dapat menjadi subledger keuangan.

### 5.4 Supplier Performance

Supplier Insight sudah mengukur penerimaan, barang rusak, retur, produk bermasalah, damage rate, dan return rate.

Komponen yang belum tersedia:

- On-time delivery.
- In-full delivery.
- OTIF.
- Lead time aktual terhadap kontrak.
- Fill rate.
- Purchase price variance.
- Kepatuhan harga kontrak.
- Invoice discrepancy.
- Claim recovery.
- Supplier scorecard gabungan.

**Status:** tersedia untuk kualitas barang, belum memadai untuk kinerja komersial dan SLA supplier.

### 5.5 Inventory Management KPI

Sistem sudah memiliki laporan stok, mutasi, aging, slow moving, FIFO/FEFO, barang rusak, dan opname.

KPI yang belum tersedia sebagai laporan manajemen terintegrasi:

- Inventory turnover.
- GMROI.
- Days of inventory.
- Sell-through rate.
- Stockout rate.
- Fill rate.
- Shrinkage rate.
- Lost sales akibat stockout.
- Excess stock value.
- Provision near-expiry dan dead stock.
- Forecast accuracy.
- Replenishment accuracy.

**Status:** data operasional tersedia, KPI strategis belum lengkap.

### 5.6 E-commerce Analytics

Backoffice telah mempunyai data order, payment, customer, dan fulfillment e-commerce. Kanal e-commerce publik tidak menyediakan modul laporan internal pada halaman yang diperiksa.

Komponen yang belum tersedia:

- Product view ke add-to-cart.
- Cart abandonment.
- Checkout conversion.
- Payment failure funnel.
- Conversion rate per channel.
- Repeat purchase rate.
- Customer acquisition cost.
- Customer lifetime value.
- Cohort retention.
- Promo attribution.
- Return dan refund rate.
- Revenue per visitor.
- Pencarian tanpa hasil.

**Status:** laporan transaksi tersedia, laporan funnel dan pertumbuhan belum tersedia.

### 5.7 Fulfillment dan Kurir

Fulfillment control telah memantau handler, status, AWB, dan order yang melewati SLA dasar.

Komponen yang belum tersedia:

- SLA pickup kurir.
- SLA delivery.
- First-attempt delivery success.
- Failed delivery.
- Return-to-origin.
- Biaya kirim aktual terhadap biaya yang dibebankan.
- Selisih berat dan dimensi.
- Performa per kurir.
- Claim kurir.
- Order cycle time dari paid sampai delivered.

**Status:** kontrol fulfillment tersedia sebagian, carrier performance belum lengkap.

### 5.8 Promo Profitability

Sistem telah memiliki penggunaan promo, voucher, loyalty, referral, dan sinkronisasi batas diskon.

Komponen yang belum tersedia:

- Incremental sales.
- Margin setelah promo.
- Promo ROI.
- Cannibalization.
- Customer baru yang benar-benar aktif.
- Repeat order setelah promo.
- Promo abuse.
- Discount leakage.
- Beban promo per pihak.
- Break-even promo.

**Status:** usage monitoring tersedia, profitabilitas promo belum dapat dinilai.

## 6. Laporan manajemen yang direkomendasikan

| ID | Laporan | Isi utama | Manfaat |
|---|---|---|---|
| REC-01 | Daily Control Tower | Sales, payment exception, stock discrepancy, order backlog, failed integration, incident | Menyatukan exception utama untuk tindakan harian |
| REC-02 | Outlet Profitability | Penjualan, margin, beban, shrinkage, produktivitas, kontribusi laba | Menilai outlet yang sehat dan bermasalah |
| REC-03 | Product Profitability | Margin bersih setelah diskon, fee, retur, ongkir, write-off | Menghindari produk beromzet tinggi tetapi merugi |
| REC-04 | Customer LTV dan Churn | Nilai pelanggan, frequency, recency, repeat purchase, churn risk | Mengarahkan retention dan biaya akuisisi |
| REC-05 | Workforce Productivity | Sales per jam kerja, transaksi per kasir, void/refund rate, attendance variance | Menilai efektivitas penjadwalan dan risiko operasional |
| REC-06 | Integration Health | Latency, failed event, retry, dead-letter, duplicate event, last successful sync | Mendeteksi data antar sistem yang tidak sinkron |
| REC-07 | Exception Aging | Jenis exception, nilai, umur, PIC, target waktu, status | Memastikan exception tidak dibiarkan terbuka |
| REC-08 | Cutoff dan Late Posting | Transaksi terlambat, backdate, perubahan periode tertutup, dampak laporan | Menjaga integritas pelaporan periode |

## 7. Prioritas implementasi

### Fase 1: Pengendalian risiko kritis

1. Rekonsiliasi stok lintas sistem.
2. Rekonsiliasi payment sampai settlement bank.
3. Inventory valuation dan rekonsiliasi jurnal.
4. Three-way matching pembelian.
5. Pajak dan e-faktur.
6. Segregation of Duties dan access certification.
7. Backup, restore, dan DR dashboard.

### Fase 2: Pengendalian operasional dan modal kerja

1. Inventory KPI.
2. Supplier scorecard.
3. Exception aging.
4. Integration health.
5. Carrier performance.
6. Outlet dan product profitability.

### Fase 3: Pertumbuhan dan optimasi

1. E-commerce funnel.
2. Customer LTV dan cohort.
3. Promo profitability.
4. Workforce productivity.
5. Forecast dan replenishment accuracy.

## 8. Kriteria penerimaan report baru

Setiap report baru harus memenuhi ketentuan berikut:

1. Memiliki definisi field dan rumus yang disetujui process owner.
2. Menyebutkan source of truth setiap field.
3. Memiliki timestamp data terakhir dan SLA keterlambatan.
4. Dapat drill-down sampai transaksi sumber.
5. Memiliki filter periode, outlet, gudang, channel, dan status yang relevan.
6. Menghasilkan total yang sama antara halaman, detail, dan ekspor.
7. Menampilkan exception, nilai, umur, PIC, dan status penyelesaian.
8. Dilindungi row-level access dan permission yang sesuai.
9. Ekspor sensitif dicatat dalam audit log.
10. Memiliki pengujian rekonsiliasi, boundary date, timezone, duplicate, dan late-arriving transaction.

## 9. Keputusan audit

Ekosistem saat ini dapat digunakan untuk monitoring operasional dan kelanjutan UAT. Sistem belum cukup untuk menjadi sumber tunggal pengendalian finansial dan keputusan manajemen sampai laporan Kritis pada bagian 4 tersedia, direkonsiliasi, dan diuji dengan data lintas Backoffice, POS, WMS, e-commerce, payment gateway, bank, serta sistem akuntansi.

Validasi tambahan terhadap menu dan laporan internal WMS tetap diperlukan setelah kendala autentikasi diselesaikan.
