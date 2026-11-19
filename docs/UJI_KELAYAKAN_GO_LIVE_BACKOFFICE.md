# Uji Kelayakan Go-Live Immostore Backoffice

**Tanggal penyusunan:** 12 November 2026  
**Status dokumen:** Working paper audit dan decision gate  
**Keputusan awal:** **BELUM LAYAK SECARA BUKTI**. Keputusan ini bukan kesimpulan bahwa seluruh kontrol gagal. Persetujuan go-live belum dapat diberikan sampai bukti produksi, hasil uji end-to-end, uji kapasitas, rekonsiliasi data, serta uji pemulihan bencana tersedia dan lulus.

## 1. Ringkasan eksekutif

Audit ini menilai apakah Immostore Backoffice dapat dijadikan sistem utama bisnis retail tanpa menimbulkan risiko material berupa kehilangan uang, selisih stok, fraud, salah saji laporan, kegagalan layanan, atau pelanggaran data pribadi. Pendekatan audit menggabungkan walkthrough proses, pemeriksaan konfigurasi dan kode, inspeksi bukti operasional, sampling transaksi, rekonsiliasi independen, pengujian hak akses, uji negatif, uji beban, serta simulasi gangguan dan pemulihan.

Repository menunjukkan aplikasi Laravel 13 dengan modul master data, stok gudang dan toko, transfer, order dan retur cabang, e-commerce, promo dan voucher, loyalty, monitoring, laporan, RBAC, audit log, serta integrasi Doku dan Biteship. Pengujian otomatis memberi indikasi positif atas sebagian alur aplikasi. Indikasi tersebut belum menggantikan bukti kontrol produksi. README masih berisi dokumentasi template Materio, dan repository yang diperiksa belum membuktikan adanya kebijakan MFA, runbook backup dan disaster recovery, hasil restore, baseline kapasitas, SOP change management, data retention, atau matriks segregasi tugas yang telah disetujui.

Prinsip keputusan audit:

1. Klaim tanpa bukti diberi status **Belum Terbukti**, bukan lulus.
2. Bukti screenshot saja tidak cukup untuk kontrol transaksi. Auditor meminta data sumber, log, dan rekonsiliasi.
3. Kontrol harus diuji dengan user dan data yang menyerupai produksi, termasuk skenario gagal dan transaksi bersamaan.
4. Temuan Kritis menahan go-live sampai ditutup dan diuji ulang.
5. Hasil tes otomatis hanya mendukung penilaian desain aplikasi. Efektivitas operasional tetap harus dibuktikan.

## 2. Konteks dan asumsi

| Elemen | Asumsi kerja yang dipakai | Bukti yang masih harus diberikan |
|---|---|---|
| Sistem | Immostore Backoffice, buatan internal, berbasis Laravel 13 dan PostgreSQL | Pemilik produk, diagram arsitektur, daftar komponen dan versi produksi |
| Lini bisnis | Retail multi-kategori dengan gudang, outlet, e-commerce, loyalty, promo dan voucher | Kategori barang, aturan batch/serial/expiry per kategori |
| Skala | Belum diketahui | Jumlah outlet, gudang, SKU aktif, transaksi harian dan puncak, user konkuren, pertumbuhan 24 bulan |
| Channel | Toko fisik dan e-commerce. Marketplace belum terbukti | Daftar channel aktif, pemilik integrasi dan volume per channel |
| Integrasi | Doku dan Biteship terlihat pada konfigurasi aplikasi. POS, ERP/akuntansi, marketplace, e-faktur belum terbukti | Diagram interface, kontrak API, SLA, retry policy, daftar credential owner dan hasil rekonsiliasi |
| Status | Diasumsikan UAT atau pre go-live | Tanggal go-live, hasil pilot, cutover plan, rollback plan dan sign-off UAT |
| Regulasi | UU PDP, kewajiban perpajakan Indonesia dan kebijakan akuntansi perusahaan | Data mapping PDP, dasar pemrosesan, retention schedule, kebijakan PSAK dan pajak yang disetujui |

Lima pertanyaan konteks yang harus dijawab pada rapat pembukaan, tanpa menunda pelaksanaan checklist:

1. Berapa outlet, gudang, SKU aktif, transaksi rata-rata dan puncak per jam, serta user konkuren yang harus ditanggung pada hari pertama dan 24 bulan berikutnya?
2. Sistem mana yang menjadi source of truth untuk stok, harga, customer, penjualan, hutang, jurnal dan pajak, serta bagaimana arah sinkronisasi masing-masing data?
3. Integrasi mana yang sudah aktif di produksi, termasuk POS, marketplace, ERP, Doku, Biteship dan e-faktur, beserta SLA dan pemilik operasionalnya?
4. Apakah data produksi sudah dimigrasikan atau akan dimigrasikan, dan berapa ambang toleransi selisih per record, kuantitas dan nilai rupiah?
5. Berapa RTO, RPO, waktu pemulihan manual maksimum, tanggal cutover dan kondisi yang memicu rollback?

## 3. Daftar pertanyaan audit

Status bukti untuk setiap baris diisi dengan **Lulus**, **Gagal**, **Belum Terbukti**, atau **Tidak Relevan**. Status Tidak Relevan wajib disertai alasan dan persetujuan process owner.

### A. Supply Chain, bobot 20%

| ID | Pertanyaan | Tujuan / Risiko | Bukti yang diminta | Metode uji | Kriteria lulus | Risiko |
|---|---|---|---|---|---|---|
| SC-01 | Tunjukkan satu populasi PR selama 30 hari dan telusuri bagaimana PR yang disetujui berubah menjadi PO tanpa perubahan barang, kuantitas, pemasok, harga, pajak, atau termin yang tidak diotorisasi. | PO fiktif, mark-up, pembelian di luar kebutuhan | Ekspor PR dan PO, approval log, audit trail before/after, matriks wewenang | Sampling minimal 25 transaksi termasuk 5 nilai terbesar | Seluruh perubahan material memiliki approver sah; tidak ada PO tanpa PR kecuali exception terdokumentasi | Kritis |
| SC-02 | Demonstrasikan three-way matching PO, goods receipt, dan invoice ketika kuantitas dan harga berbeda. Berapa toleransi nominal dan persentasenya? | Pembayaran barang tidak diterima atau harga berlebih | PO, GR, invoice, konfigurasi toleransi, blocked invoice log | Demo positif dan negatif, sampling 25 invoice | Invoice di luar toleransi otomatis diblokir; pelepasan membutuhkan approver independen dan alasan | Kritis |
| SC-03 | Bagaimana sistem menentukan approval PO berdasarkan nilai agregat, split PO, kategori barang, dan hubungan user dengan supplier? | Pemecahan transaksi untuk melewati limit approval | Matriks DOA, konfigurasi workflow, log PO berurutan per pembuat | Uji teknis 4 batas nilai dan skenario split | Limit diterapkan server-side; split terdeteksi atau direview; pembuat tidak dapat menyetujui sendiri | Kritis |
| SC-04 | Siapa yang dapat membuat atau mengubah rekening bank, NPWP, termin, lead time, dan harga kontrak supplier, serta bagaimana perubahan diverifikasi? | Supplier palsu dan pengalihan pembayaran | Daftar role, change log, bukti callback bank, daftar supplier duplikat | Sampling 20 supplier baru/berubah | Maker-checker aktif; rekening baru diverifikasi independen; duplikat terdeteksi | Kritis |
| SC-05 | Untuk penerimaan parsial dan over-receipt, tunjukkan bagaimana sisa PO, backorder, toleransi, lot, serial, expiry, dan lokasi penyimpanan diperbarui. | Stok dan hutang salah, barang tidak terlacak | PO parsial, GR detail, kartu stok, invoice dan log exception | Demo dua penerimaan parsial dan over-receipt | Tidak ada kuantitas ganda; over-receipt di luar toleransi ditolak; sisa PO akurat | Tinggi |
| SC-06 | Bagaimana selisih rusak, kurang, lebih, dan salah SKU saat inbound dicatat, diotorisasi, dan ditagihkan kembali ke supplier? | Kerugian tidak tertagih dan stok salah | Berita acara, foto, claim note, debit note, status penyelesaian | Sampling seluruh exception material 90 hari | Setiap exception punya owner, nilai, bukti, approval dan penyelesaian; stok layak/rusak terpisah | Tinggi |
| SC-07 | Demonstrasikan retur ke supplier dari permintaan sampai barang keluar, credit note, pengurangan hutang, dan penutupan kasus. | Barang keluar tanpa kredit atau retur fiktif | Return order, outbound, credit note, jurnal, audit log | Telusur dua arah untuk 15 retur | Kuantitas dan nilai cocok pada semua tahap; retur tidak dapat diselesaikan tanpa dokumen pengiriman | Tinggi |
| SC-08 | Untuk transfer antar gudang/outlet, kapan kepemilikan stok berpindah dan bagaimana stok in-transit, serah terima parsial, rusak, hilang, atau terlambat dicatat? | Stok ganda, stok hilang, fraud transfer | Transfer order, scan dispatch/receive, user/time stamp, in-transit aging | Demo normal, parsial, rusak dan tidak diterima | Stok sumber berkurang sekali; tujuan bertambah setelah penerimaan; selisih menjadi exception terbuka | Kritis |
| SC-09 | Bagaimana rekomendasi replenishment menghitung lead time, safety stock, minimum, maksimum, musim, promo, open PO, in-transit, dan reservation? | Stockout atau overstock | Formula, parameter, histori demand, hasil rekomendasi dan override log | Hitung ulang 20 SKU dari data mentah | Hasil sama dengan perhitungan independen dalam toleransi yang disetujui; override tercatat | Tinggi |
| SC-10 | Tunjukkan evaluasi supplier berdasarkan on-time delivery, fill rate, reject rate, price variance dan penyelesaian klaim. Bagaimana supplier buruk dibatasi? | Ketergantungan pada supplier buruk dan biaya tersembunyi | Scorecard 6 bulan, sumber data, notulen review, blacklist | Reperformance KPI 10 supplier terbesar | KPI dapat ditelusuri ke transaksi; supplier di bawah ambang memiliki action plan dan approval lanjutan | Sedang |

### B. Stok, bobot 25%

| ID | Pertanyaan | Tujuan / Risiko | Bukti yang diminta | Metode uji | Kriteria lulus | Risiko |
|---|---|---|---|---|---|---|
| ST-01 | Pada satu SKU yang dijual serentak di POS dan e-commerce, tunjukkan waktu update on-hand, reserved, available dan in-transit di semua channel. | Overselling dan stok kanal tidak konsisten | Event log, API log, snapshot stok bertimestamp | Uji transaksi bersamaan minimal 50 iterasi | Tidak terjadi oversell; latency sesuai SLA; conflict ditangani atomik dan dapat direkonsiliasi | Kritis |
| ST-02 | Ambil 30 SKU berisiko tinggi dan buktikan rumus saldo awal + seluruh masuk - seluruh keluar = saldo akhir per lokasi, kondisi, lot dan serial. | Mutasi hilang atau saldo direkayasa | Kartu stok lengkap, tabel transaksi sumber, hasil query independen | Reperformance dan vouching dua arah | Selisih kuantitas dan nilai nol, atau berada dalam toleransi formal dengan penjelasan | Kritis |
| ST-03 | Daftarkan seluruh jenis mutasi dan buktikan setiap jenis memiliki source document, reference unik, user, timestamp, reason code, dan reversal yang terkontrol. | Adjustment tanpa jejak dan double posting | Data dictionary mutasi, contoh setiap jenis, constraint database | Inspeksi desain dan sampling | Tidak ada mutasi tanpa referensi; reversal tidak menghapus histori; reference mencegah posting ganda | Kritis |
| ST-04 | Demonstrasikan stock opname dari freeze/cutoff, count blind, recount, selisih, approval, adjustment sampai jurnal. | Manipulasi hasil hitung dan selisih stok | Instruksi opname, count sheet, variance report, approval dan jurnal | Observasi opname dan uji 25 SKU | Counter tidak melihat saldo teoritis; selisih di atas ambang direcount; approver independen | Kritis |
| ST-05 | Siapa yang dapat membuat adjustment stok dan dapatkah orang yang sama menginput, menyetujui, dan mengeksekusinya? | Pencurian ditutupi dengan adjustment | Role-permission export, workflow, log 90 hari | Uji akses dengan akun maker dan approver | Segregasi tugas efektif; self-approval ditolak server-side; alasan dan bukti wajib | Kritis |
| ST-06 | Metode valuasi apa yang dipakai per kategori, bagaimana landed cost dialokasikan, dan bagaimana retur serta koreksi backdated memengaruhi COGS? | Salah saji persediaan dan margin | Kebijakan FIFO/average, layer cost, jurnal, rekonsiliasi GL | Hitung ulang 20 SKU bernilai terbesar | Metode konsisten dengan kebijakan akuntansi; nilai subledger sama dengan GL dalam toleransi | Kritis |
| ST-07 | Untuk barang batch, serial, atau expiry, demonstrasikan uniqueness, traceability pemasok-ke-pelanggan, FEFO, quarantine, dan recall. | Penjualan barang kedaluwarsa dan recall gagal | Batch/serial ledger, expiry alert, recall report | Trace satu batch dua arah dan uji penjualan expired | Serial unik; barang expired/quarantine tidak dapat dijual; seluruh penerima batch dapat diidentifikasi | Kritis |
| ST-08 | Apakah negative stock dilarang? Jika exception diperbolehkan, siapa yang mengotorisasi, berapa batasnya, dan bagaimana penyelesaiannya dipantau? | COGS dan availability salah, fraud | Konfigurasi, daftar negative stock harian, approval dan aging | Uji penjualan stok nol dan analisis populasi | Default ditolak; exception terbatas, disetujui, berumur pendek dan ditutup dengan root-cause | Kritis |
| ST-09 | Bagaimana dead stock, slow moving, near expiry, stockout, shrinkage, minimum dan maksimum dihitung, termasuk ambang dan exclusion? | Modal kerja terkunci dan write-off terlambat | Definisi KPI, laporan aging, parameter, notulen tindak lanjut | Reperformance 20 SKU dan review tren | Rumus terdokumentasi, data lengkap, owner serta action date tersedia untuk exception | Tinggi |
| ST-10 | Tunjukkan kontrol concurrency saat dua proses mereservasi stok terakhir serta mekanisme pelepasan reservation yang kedaluwarsa atau order batal. | Overselling dan stok terblokir permanen | Source log, reservation table, timeout job, failed job | Uji race condition, crash sebelum commit dan retry | Satu transaksi menang secara deterministik; tidak ada reservation yatim; retry idempotent | Kritis |

### C. Operasional, bobot 20%

| ID | Pertanyaan | Tujuan / Risiko | Bukti yang diminta | Metode uji | Kriteria lulus | Risiko |
|---|---|---|---|---|---|---|
| OP-01 | Berapa SLA sinkronisasi POS ke backoffice dan bagaimana transaksi pending, gagal, duplikat, terlambat, atau out-of-order dideteksi serta diproses ulang? | Penjualan, kas dan stok tidak lengkap | Interface log 30 hari, dashboard queue, retry dan dead-letter log | Sampling seluruh status error dan replay terkontrol | Alert tepat waktu; retry idempotent; tidak ada duplikasi; backlog di bawah ambang SLA | Kritis |
| OP-02 | Saat outlet offline, data apa yang tetap dapat diproses, bagaimana nomor transaksi dibuat, dan bagaimana konflik harga, promo, stok serta refund diselesaikan saat reconnect? | Transaksi hilang, duplikat atau harga salah | Desain offline, local queue, conflict log, SOP outlet | Putus jaringan 30 menit lalu sinkronisasi | Semua transaksi terkirim sekali; urutan dan waktu asli terjaga; konflik masuk exception queue | Kritis |
| OP-03 | Tunjukkan proses perubahan harga dari usulan, approval, effective date, publikasi channel, verifikasi, sampai rollback. | Margin hilang akibat harga salah | Price change request, approval, sync status, audit trail | Uji harga masa depan, backdate, user tanpa wewenang | Maker-checker aktif; publish atomik atau terpantau; rollback dapat dilakukan dan tercatat | Kritis |
| OP-04 | Demonstrasikan kombinasi diskon, promo, voucher, loyalty, bundling, minimum belanja dan rounding. Aturan mana yang menang? | Discount leakage dan transaksi tak konsisten | Rule catalog, test matrix, transaksi aktual, change log | Boundary testing minimal 20 kombinasi | Prioritas eksplisit; cap diskon ditegakkan; hasil POS dan online sama | Tinggi |
| OP-05 | Untuk void, refund dan retur pelanggan, siapa yang boleh memulai dan menyetujui, apa batas waktu/nilai, dan bagaimana stok, kas, pajak, loyalty dan jurnal dipulihkan? | Penggelapan kas dan manipulasi stok | Log void/refund/retur, OTP/approval, receipt asli, jurnal | Sampling 25 transaksi termasuk setelah tutup shift | Referensi transaksi asli wajib; approval sesuai limit; seluruh dampak dibalik tepat satu kali | Kritis |
| OP-06 | Tunjukkan tutup shift dengan expected cash, actual cash, over/short, settlement EDC/QRIS, cash drop dan approval selisih. | Cash leakage dan settlement tidak cocok | Shift report, cash count, settlement bank, log reopening | Observasi satu shift dan rekonsiliasi 10 hari | Seluruh tender cocok; selisih di atas ambang disetujui dan diinvestigasi; shift terkunci | Kritis |
| OP-07 | Bagaimana sistem merekonsiliasi Doku, EDC, transfer, cash, voucher dan payment pending terhadap order, bank settlement, fee dan refund? | Pendapatan hilang atau payment ganda | Payment ledger, gateway report, bank statement, exception list | Rekonsiliasi populasi satu hari puncak dan 25 exception | Gross, fee, net dan status cocok; exception punya aging, owner dan penyelesaian | Kritis |
| OP-08 | Saat stok terakhir dipesan beberapa channel, bagaimana alokasi, reservation expiry, split fulfillment, substitution, cancel dan release stock bekerja? | Oversell, pembatalan pelanggan dan stok tertahan | Order timeline, reservation log, fulfillment event | Uji concurrent order dan pembatalan pada setiap tahap | Alokasi konsisten; release terjadi sekali; pelanggan dan channel menerima status yang sama | Kritis |
| OP-09 | Berapa throughput dan latency p95/p99 pada beban puncak untuk login, checkout, reservasi, posting mutasi, laporan dan webhook? | Sistem tidak dapat dipakai saat promo | Skenario load test, environment parity, hasil APM dan resource usage | Load, stress, soak dan failover test | Menangani minimal 1,5 kali proyeksi puncak; error rate dan latency di bawah SLA; tidak ada data corrupt | Kritis |
| OP-10 | Bagaimana order atau webhook yang gagal dipulihkan setelah timeout pada titik sebelum dan sesudah commit? | Status menggantung dan double processing | Idempotency key, retry log, outbox/dead-letter, runbook | Fault injection pada payment, shipment dan stock update | Retry aman; status akhir konsisten; operator dapat memulihkan tanpa edit database langsung | Kritis |

### D. Manajemen dan Kontrol Internal, bobot 20%

| ID | Pertanyaan | Tujuan / Risiko | Bukti yang diminta | Metode uji | Kriteria lulus | Risiko |
|---|---|---|---|---|---|---|
| MG-01 | Ekspor seluruh user, role, permission, status, outlet/gudang, last login dan pemilik bisnis. Siapa mengesahkan akses tersebut? | Akses berlebih dan akun yatim | User access listing, approval tiket, HR roster | Rekonsiliasi populasi terhadap HR dan sampling 25 user | Seluruh user aktif punya owner dan kebutuhan; user keluar/nonaktif dicabut sesuai SLA | Kritis |
| MG-02 | Buktikan konflik SoD untuk supplier-rekening-pembayaran, harga-promo-penjualan, stok-adjustment-opname, dan user-role-audit. | Fraud tanpa deteksi | Matriks SoD, daftar konflik aktual, compensating control | Analisis permission efektif, bukan nama role | Tidak ada konflik kritis tanpa kontrol kompensasi yang disetujui dan dibuktikan | Kritis |
| MG-03 | Demonstrasikan review akses berkala dan emergency access, termasuk expiry otomatis serta review aktivitas setelah digunakan. | Privilege permanen dan penyalahgunaan admin | Attestation terakhir, daftar admin, break-glass log | Sampling seluruh privileged user | Review maksimal triwulanan; emergency access berbatas waktu dan direview independen | Tinggi |
| MG-04 | Apakah login dilindungi rate limit, lockout adaptif, MFA untuk user privileged, kebijakan password, session timeout dan revocation? | Account takeover | Konfigurasi auth, IdP/MFA report, session log, penetration test | Uji brute force terkontrol dan session misuse | MFA wajib untuk privileged; throttling efektif; session invalid setelah logout/reset; cookie aman | Kritis |
| MG-05 | Untuk perubahan data bisnis kritis, apakah audit trail merekam actor, waktu, IP/session, object, action, before/after, reason dan correlation ID? | Perubahan tidak dapat diinvestigasi | Business audit log, contoh 20 perubahan, skema dan retention | Vouching dari transaksi ke log dan sebaliknya | Field lengkap, waktu tersinkron, pencarian tersedia, log tidak dapat diubah user aplikasi | Kritis |
| MG-06 | Siapa yang dapat mengubah atau menghapus audit log, dan apakah perubahan pada storage log sendiri dimonitor secara independen? | Penghapusan jejak fraud | DB privileges, immutable/WORM config, SIEM alert, DBA activity | Review privilege dan simulasi tamper | Aplikasi hanya append; perubahan mendapat alert; admin aplikasi tidak dapat menghapus log | Kritis |
| MG-07 | Petakan data pribadi pelanggan, tujuan pemrosesan, dasar pemrosesan, lokasi penyimpanan, pihak ketiga, retention dan mekanisme hak subjek data. | Pelanggaran UU PDP dan kebocoran data | Data inventory, ROPA, consent/privacy notice, DPA vendor | Data flow walkthrough dan sampling delete/export request | Data minimum, terenkripsi saat transit/tersimpan sesuai klasifikasi, retention dan hak subjek berjalan | Kritis |
| MG-08 | Kapan backup terakhir berhasil, apakah backup terenkripsi dan terisolasi, dan kapan restore penuh terakhir diuji terhadap RTO/RPO? | Kehilangan data permanen | Backup job log 30 hari, immutable copy, restore report, checksum | Restore ke lingkungan terisolasi dan validasi transaksi | Restore lengkap mencapai RTO/RPO; integritas dan aplikasi terverifikasi; kegagalan backup memicu alert | Kritis |
| MG-09 | Demonstrasikan failover database, queue, storage, aplikasi dan dependensi eksternal. Siapa mengambil keputusan disaster declaration? | Downtime berkepanjangan dan split-brain | DR plan, contact tree, hasil exercise, failover/failback log | Tabletop dan technical failover | Peran jelas; failover dan failback selesai dalam RTO tanpa kehilangan melebihi RPO | Kritis |
| MG-10 | Tunjukkan satu perubahan produksi dari requirement, code review, test, approval, deployment, migration, monitoring sampai rollback. | Perubahan tidak sah atau merusak data | Tiket, PR, CI log, artifact hash, deployment log | Sampling 10 rilis termasuk emergency change | Segregasi dev-approver-deployer; test dan approval ada sebelum deploy; rollback telah diuji | Tinggi |
| MG-11 | Bagaimana secret, API key dan kredensial database disimpan, diputar, dibatasi per environment dan dicabut ketika personel keluar? | Kebocoran credential dan akses lintas lingkungan | Secret inventory, vault policy, rotation log, access log | Config review tanpa menyalin secret | Tidak ada secret di source/log; least privilege; rotasi dan revocation sesuai kebijakan | Kritis |
| MG-12 | Apakah dependensi, kerentanan, patch, SAST/DAST, penetration test, logging keamanan dan incident response dikelola dengan SLA? | Eksploitasi kerentanan yang diketahui | SBOM, scan report, pentest, ticket remediation, IR exercise | Review hasil terbaru dan retest sampel | Tidak ada critical/high overdue; temuan ditutup dan diuji ulang; incident drill dilakukan | Kritis |
| MG-13 | Bagaimana jurnal penjualan, pembelian, retur, COGS, inventory, hutang, piutang, voucher dan fee payment dibuat serta dicegah dari posting ganda? | Salah saji laporan keuangan | Mapping jurnal, batch log, idempotency/reference, GL extract | Reperformance 25 transaksi lintas jenis | Debit sama dengan kredit; mapping disetujui; reference unik; reversal dapat ditelusuri | Kritis |
| MG-14 | Bagaimana PPN, faktur pajak, pembulatan, retur pajak dan koreksi periode tertutup diterapkan dan direkonsiliasi? | Sanksi pajak dan laporan salah | Tax rule, e-faktur interface, tax report, reconciliation | Sampling lintas tarif dan periode | Perhitungan sesuai aturan berlaku; nomor dokumen unik; selisih dilacak dan diselesaikan | Kritis |
| MG-15 | Apakah dukungan operasional memiliki SLA, on-call, runbook, escalation, knowledge transfer, bus factor dan akses vendor yang terkontrol? | Ketergantungan individu dan pemulihan lambat | SLA, roster, runbook, incident history, vendor access log | Simulasi insiden tanpa developer utama | Tim dapat diagnosis dan pulih sesuai SLA; akses vendor time-bound dan diaudit | Tinggi |

### E. Laporan, bobot 15%

| ID | Pertanyaan | Tujuan / Risiko | Bukti yang diminta | Metode uji | Kriteria lulus | Risiko |
|---|---|---|---|---|---|---|
| LP-01 | Petakan laporan wajib untuk penjualan, stok, kartu stok, pembelian, hutang, piutang, margin, pajak dan laba rugi per outlet. Siapa pemilik definisinya? | Keputusan dibuat dari laporan tidak lengkap | Report catalog, data owner, definisi field dan SLA | Gap analysis terhadap kebutuhan finance/ops/audit | Seluruh laporan wajib tersedia atau memiliki kontrol pengganti yang disetujui | Tinggi |
| LP-02 | Untuk satu hari tertutup, rekonsiliasikan penjualan bruto, diskon, retur, pajak, net sales dan payment ke transaksi sumber, settlement dan jurnal. | Pendapatan salah saji | Raw transaction, report export, settlement, GL | Reperformance penuh satu hari puncak | Selisih nol atau dalam toleransi formal; seluruh selisih dapat dijelaskan per transaksi | Kritis |
| LP-03 | Rekonsiliasikan stok laporan, kartu stok, saldo database, hasil fisik dan jurnal inventory berdasarkan SKU-lokasi-kondisi. | Persediaan dan COGS salah | Lima sumber data bertimestamp sama | Sampling 30 SKU termasuk high value dan fast moving | Kuantitas dan nilai konsisten; cutoff dan exception terdokumentasi | Kritis |
| LP-04 | Untuk setiap laporan, berapa data latency, cutoff, timezone dan perlakuan late-arriving transaction atau backdate? | Dua laporan memberi angka berbeda | Data lineage, ETL schedule, timestamp sumber dan report | Uji transaksi dekat tengah malam dan late posting | Latency sesuai SLA; timezone konsisten Asia/Jakarta; restatement terkontrol | Tinggi |
| LP-05 | Dari KPI ringkas, bisakah user drill-down sampai transaksi sumber dan kembali memperoleh total yang sama? | KPI tidak dapat diverifikasi | Dashboard, detail report, transaction ID | Drill-down 15 angka lintas modul | Total detail sama dengan ringkasan; filter diwariskan; tidak ada baris tersembunyi tanpa alasan | Tinggi |
| LP-06 | Apakah filter outlet, gudang, channel, status, kategori, periode dan ekspor menghasilkan populasi yang sama dengan query independen? | Filter salah menyesatkan keputusan | Export CSV/XLSX, query source, filter definition | Boundary and combinatorial testing | Tidak ada missing/duplicate row; timezone dan inclusive date terdokumentasi | Tinggi |
| LP-07 | Hitung ulang inventory turnover, GMROI, stockout rate, shrinkage, fill rate, gross margin dan aging dari data mentah. | KPI manajemen salah definisi | KPI dictionary, formula, data source dan contoh | Reperformance 2 periode dan 5 outlet | Formula disetujui, denominator konsisten, exclusion transparan, hasil cocok | Tinggi |
| LP-08 | Siapa yang boleh melihat data profit, customer, supplier bank, payroll-like data atau lintas outlet, dan apakah ekspor tercatat? | Kebocoran data sensitif | Report permission matrix, export log, row-level scope | Uji akses lintas role/outlet dan direct URL | Unauthorized access ditolak server-side; scope baris efektif; ekspor sensitif diaudit | Kritis |
| LP-09 | Dapatkah audit eksternal memperoleh data lengkap, read-only, bertanggal, dengan data dictionary dan checksum tanpa akses produksi berlebih? | Audit tidak dapat memverifikasi angka | Audit export package, schema, checksum dan approval | Generate paket audit untuk satu periode | Paket reproducible, lengkap, immutable dan dapat direkonsiliasi ke laporan resmi | Sedang |

## 4. Skenario uji end-to-end wajib

Setiap skenario harus menyimpan ID transaksi di setiap sistem, timestamp, user, screenshot penting, log teknis, query rekonsiliasi, hasil aktual, status, defect ID dan retest evidence.

| ID | Skenario dan langkah kunci | Hasil yang diharapkan | Severity jika gagal |
|---|---|---|---|
| E2E-01 | Buat PR, approval, PO, terima 90 dari 100 unit, masukkan invoice 100 unit, lalu coba bayar. | Three-way match memblokir 10 unit yang belum diterima; hanya nilai sah dapat dibayar; seluruh status dan jurnal dapat ditelusuri. | Kritis |
| E2E-02 | Jual barang dengan cash dan Doku, lakukan retur parsial, refund, lalu coba void ulang transaksi yang sama. | Stok, payment, loyalty, pajak, revenue dan COGS terbalik tepat satu kali; double void ditolak; approval tercatat. | Kritis |
| E2E-03 | Transfer 20 unit antar gudang, terima 17 baik, 2 rusak dan 1 hilang setelah transit. | Sumber berkurang 20; tujuan bertambah 17 baik dan 2 rusak; 1 menjadi claim/variance terbuka; tidak ada stok ganda. | Kritis |
| E2E-04 | Lakukan blind stock opname, masukkan selisih besar, recount, approval dan adjustment. | Counter tidak melihat book stock; threshold memicu recount; adjustment hanya setelah approval independen; jurnal dan audit log lengkap. | Kritis |
| E2E-05 | User tanpa permission mencoba membuat harga/promo, memanggil endpoint langsung dan memanipulasi payload/ID outlet. | UI dan server menolak; tidak ada perubahan data; percobaan tercatat dan dapat di-alert. | Kritis |
| E2E-06 | Putus internet outlet selama 30 menit, lakukan penjualan dan retur, ubah harga pusat, kemudian reconnect. | Transaksi tersinkron sekali, konflik masuk exception workflow, nomor dan waktu asli tetap, saldo akhir benar. | Kritis |
| E2E-07 | Jalankan checkout POS dan e-commerce bersamaan untuk stok terakhir, dengan 50 iterasi paralel. | Maksimal satu reservation/penjualan berhasil; tidak ada negative stock atau double allocation; pihak kalah menerima status jelas. | Kritis |
| E2E-08 | Hentikan database/queue setelah payment sukses tetapi sebelum order update, kemudian jalankan retry. | Recovery idempotent; payment dan order konsisten; tidak ada charge atau posting ganda; alert dan runbook bekerja. | Kritis |
| E2E-09 | Restore backup terakhir ke lingkungan terisolasi, jalankan aplikasi, dan rekonsiliasi transaksi pada titik RPO. | Restore selesai dalam RTO; kehilangan tidak melebihi RPO; checksum, login, order, stok, payment dan laporan valid. | Kritis |
| E2E-10 | Jalankan load 1,5 kali proyeksi peak selama 2 jam, termasuk checkout, webhook, reservation, laporan dan ekspor. | Error dan latency di bawah SLA; queue pulih; resource tidak leak; data hasil uji tetap konsisten. | Kritis |
| E2E-11 | Ubah rekening supplier, buat PO dan invoice, lalu coba approve dan bayar dengan user yang sama. | Konflik SoD diblokir; perubahan rekening memerlukan verifikasi independen; aktivitas terlihat di audit log. | Kritis |
| E2E-12 | Ambil satu batch near-expiry, transfer ke toko, coba jual setelah expiry, lalu jalankan recall. | FEFO diprioritaskan, penjualan expired ditolak, lokasi dan pelanggan batch dapat ditelusuri penuh. | Kritis jika relevan |

## 5. Matriks penilaian kesiapan

### Skala skor

| Skor | Definisi yang dapat dibuktikan |
|---|---|
| 1 | Kontrol tidak ada, gagal, atau risiko kritis terbuka tanpa mitigasi |
| 2 | Kontrol dirancang sebagian tetapi tidak konsisten, tidak terdokumentasi, atau bukti operasional lemah |
| 3 | Kontrol utama tersedia dan lulus UAT terbatas, tetapi masih ada gap tinggi atau bukti periode operasi belum cukup |
| 4 | Kontrol dirancang dan berjalan efektif, bukti lengkap, exception kecil memiliki action plan |
| 5 | Kontrol efektif dan terukur, dimonitor berkelanjutan, diuji negatif, dan menunjukkan perbaikan berbasis tren |

### Lembar skor

| Scope | Bobot | Skor 1-5 | Skor tertimbang | Temuan Kritis | Temuan Tinggi | Status bukti |
|---|---:|---:|---:|---:|---:|---|
| Supply Chain | 20% |  | `Skor x 20%` |  |  |  |
| Stok | 25% |  | `Skor x 25%` |  |  |  |
| Operasional | 20% |  | `Skor x 20%` |  |  |  |
| Manajemen dan Kontrol Internal | 20% |  | `Skor x 20%` |  |  |  |
| Laporan | 15% |  | `Skor x 15%` |  |  |  |
| **Total** | **100%** |  | **Jumlah skor tertimbang** |  |  |  |

Aturan pengisian:

- Skor scope tidak boleh melebihi 2 jika terdapat temuan Kritis terbuka pada scope tersebut.
- Skor scope tidak boleh melebihi 3 jika lebih dari 20% kontrol Tinggi berstatus Belum Terbukti.
- Bukti dari environment development tidak boleh menjadi satu-satunya dasar skor efektivitas produksi.
- Temuan yang hanya memiliki janji perbaikan tetap dihitung terbuka sampai retest lulus.

### Kriteria keputusan

| Keputusan | Kriteria |
|---|---|
| LAYAK | Skor total minimal 4,0, tidak ada temuan Kritis terbuka, seluruh E2E Kritis lulus, serta cutover dan rollback disetujui |
| LAYAK BERSYARAT | Skor 3,0 sampai 3,9 atau ada temuan Tinggi dengan mitigasi sementara, PIC, target, risiko residual dan persetujuan tertulis |
| BELUM LAYAK | Skor di bawah 3,0, ada temuan Kritis terbuka, atau bukti minimum untuk recovery, rekonsiliasi, akses dan integritas transaksi belum tersedia |

Keputusan tidak boleh dinaikkan hanya berdasarkan rata-rata skor apabila satu red flag masih aktif.

## 6. Template temuan audit

| Elemen | Isi wajib |
|---|---|
| ID dan judul | ID unik, scope, dan pernyataan risiko singkat |
| Kondisi | Fakta yang ditemukan, populasi, sampel, jumlah exception, nilai rupiah dan periode |
| Kriteria | Kebijakan, requirement, kontrak, regulasi, control objective atau expected design |
| Penyebab | Root cause yang telah divalidasi, bukan dugaan umum |
| Dampak | Dampak aktual dan potensial terhadap uang, stok, fraud, layanan, laporan dan kepatuhan |
| Tingkat risiko | Kritis, Tinggi, Sedang atau Rendah, disertai alasan likelihood dan impact |
| Rekomendasi | Perbaikan spesifik, owner kontrol, bukti penutupan dan mekanisme pencegahan berulang |
| Tanggapan manajemen | Setuju/tidak setuju, risiko residual dan dasar keputusan |
| PIC | Satu accountable owner, bukan nama tim saja |
| Target waktu | Tanggal spesifik dengan milestone jika diperlukan |
| Status dan retest | Open, in progress, ready for retest, closed; sertakan tanggal dan bukti retest |

Contoh format naratif:

> **Kondisi:** Dari 25 perubahan harga pada 1 sampai 31 Oktober 2026, 7 perubahan senilai potensi margin RpX dilakukan dan disetujui oleh user yang sama. **Kriteria:** Matriks SoD mensyaratkan maker dan approver berbeda untuk perubahan harga. **Penyebab:** Permission approval tergabung pada role supervisor dan endpoint tidak memblokir self-approval. **Dampak:** Harga dapat dimanipulasi tanpa deteksi dan menyebabkan kehilangan margin. **Rekomendasi:** Terapkan pencegahan self-approval server-side, pisahkan permission, alert perubahan material, dan review populasi historis. **PIC:** Head of Engineering dan Finance Controller. **Target:** tanggal spesifik. **Bukti tutup:** hasil negative test, role export, change log produksi dan review transaksi historis.

## 7. Red flag penghentian go-live

Go-live harus dihentikan apabila salah satu kondisi berikut belum ditutup:

1. Stok dapat menjadi negatif atau dialokasikan ganda tanpa kontrol exception yang disetujui.
2. Transaksi payment, order, mutasi stok atau jurnal dapat diposting ganda saat retry.
3. Selisih material antara stok, kartu stok, fisik, penjualan, settlement dan GL tidak dapat direkonsiliasi.
4. User dapat membuat sekaligus menyetujui supplier, rekening bank, pembayaran, harga, promo, adjustment atau refund material.
5. User tanpa wewenang dapat mengakses endpoint langsung atau melihat data lintas outlet.
6. Audit trail transaksi kritis tidak lengkap, dapat diubah, atau dapat dihapus oleh admin aplikasi.
7. MFA untuk privileged access tidak tersedia atau tidak diwajibkan tanpa kontrol kompensasi yang setara.
8. Backup belum pernah direstore secara penuh, atau hasil restore melampaui RTO/RPO.
9. Tidak ada cutover, rollback, reconciliation checkpoint dan accountable decision owner.
10. Uji beban tidak mencapai proyeksi puncak atau menimbulkan data corrupt, timeout massal atau backlog yang tidak pulih.
11. Mode offline atau gangguan integrasi menyebabkan transaksi hilang, duplikat atau tidak dapat direkonsiliasi.
12. Secret produksi terekspos di source, log, tiket, perangkat personal atau dibagi lintas environment.
13. Kerentanan critical/high yang dapat dieksploitasi pada jalur produksi belum ditutup atau dimitigasi.
14. Data pelanggan diproses tanpa inventory, akses minimum, retention, perlindungan, atau mekanisme respons insiden.
15. Perhitungan pajak, jurnal otomatis atau valuasi stok material belum disetujui Finance dan belum diuji ulang independen.

## 8. Bukti awal dari repository dan batas kesimpulan

| Observasi awal | Implikasi audit | Status |
|---|---|---|
| Tersedia RBAC berbasis `spatie/laravel-permission` dan banyak controller memakai permission middleware | Desain akses tersedia, tetapi perlu pengujian permission efektif, direct endpoint dan SoD terhadap user produksi | Belum Terbukti |
| Tersedia `business_audit_logs` dan laporan histori perubahan master | Indikasi audit trail tersedia, tetapi immutability, kelengkapan, retention dan akses DBA belum dibuktikan | Belum Terbukti |
| Reservation stok menggunakan transaksi database dan row locking pada service tertentu | Mengurangi race condition pada alur yang dicakup, tetapi concurrency lintas POS/e-commerce harus diuji nyata | Belum Terbukti |
| Ada laporan FIFO/FEFO, mutasi, opname, slow moving, loss/damage, payment dan command center | Cakupan monitoring luas, tetapi akurasi rumus dan rekonsiliasi sumber belum dibuktikan | Belum Terbukti |
| Ada pemeriksaan readiness Doku dan Biteship | Pemeriksaan konfigurasi bukan bukti webhook security, idempotency, SLA atau rekonsiliasi settlement | Belum Terbukti |
| Login meregenerasi session dan mencatat login/logout | Kontrol dasar ada, tetapi repository belum menunjukkan MFA dan route login tidak tampak memakai throttle khusus | Perlu tindakan prioritas |
| Konfigurasi session menyatakan encryption storage tidak aktif | Perlu risk assessment data dalam session dan bukti cookie/transport/security controls produksi | Perlu verifikasi |
| README masih berisi dokumentasi template Materio | Dokumentasi operasi, arsitektur, deployment, recovery dan ownership belum memadai untuk sistem utama | Tinggi |
| Pengujian otomatis aplikasi dijalankan pada 12 November 2026 dengan hasil 262 tes dan 3.917 assertion lulus | Mendukung desain kontrol yang diuji, tetapi tidak membuktikan environment produksi atau efektivitas operasional | Bukti pendukung |

## 9. Paket bukti minimum sebelum rapat keputusan

1. Diagram arsitektur dan data flow produksi, inventory sistem, interface, source of truth dan owner.
2. User-role-permission export produksi, matriks SoD, HR roster dan hasil access review terbaru.
3. Populasi 90 hari untuk PO, GR, invoice, payment, mutasi, adjustment, transfer, void, refund dan price change.
4. Rekonsiliasi satu hari puncak dan satu month-end antara transaksi, stok, payment settlement, pajak dan GL.
5. Hasil seluruh skenario E2E Kritis dengan transaction ID dan log teknis.
6. Laporan load/stress/soak test dengan kapasitas, latency p95/p99, error rate dan observasi resource.
7. Backup report 30 hari, bukti immutable/offsite, restore test, DR exercise dan pencapaian RTO/RPO.
8. Vulnerability scan, dependency scan, penetration test, remediation ticket dan retest.
9. SOP incident, release, emergency change, access, opname, refund, rekonsiliasi, cutover dan rollback.
10. Data inventory UU PDP, retention schedule, vendor DPA, incident response dan bukti pemenuhan hak subjek data.

## 10. Sign-off

| Peran | Nama | Keputusan | Tanggal | Catatan/risiko residual |
|---|---|---|---|---|
| Process Owner Supply Chain |  |  |  |  |
| Process Owner Inventory |  |  |  |  |
| Head of Retail Operations |  |  |  |  |
| Finance Controller |  |  |  |  |
| Information Security / DPO |  |  |  |  |
| Head of Engineering / IT |  |  |  |  |
| Internal Audit |  |  |  |  |
| Go-Live Decision Owner |  |  |  |  |
