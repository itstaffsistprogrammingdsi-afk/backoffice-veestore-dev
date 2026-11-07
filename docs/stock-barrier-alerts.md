# Alert stok barrier

Pemantau berjalan setiap menit untuk barrier Store dan Warehouse. Produk masuk alert saat stok tersedia (`stok fisik - reservasi`, minimum 0) sama dengan atau lebih rendah dari ambang barrier setelah toleransi. Alert internal tampil di dropdown notifikasi backoffice dan mengarah ke halaman stok terkait.

Satu digest email per jenis lokasi dikirim sesuai jadwal yang dipilih pada halaman **Settings → Barrier Alert Email** (`/settings/app/stock-barrier-alerts`):

- **Setiap ada SKU baru** — email segera dikirim saat ada produk baru menyentuh ambang. Stok yang tetap rendah tidak mengirim email berulang; alert baru dikirim lagi setelah stok pulih melewati ambang lalu turun kembali.
- **Setiap hari** — ringkasan semua produk yang masih menyentuh ambang dikirim sekali sehari pada waktu yang dipilih.
- **Setiap minggu** — ringkasan dikirim sekali seminggu pada hari dan waktu yang dipilih.
- **Setiap bulan** — ringkasan dikirim sekali sebulan pada tanggal (1–28) dan waktu yang dipilih.

Jadwal email hanya memengaruhi pengiriman email. Notifikasi internal di backoffice tetap diperbarui setiap menit. Email memuat maksimal 200 rincian produk per jenis lokasi, sedangkan hitungan alert internal tetap mencakup semua produk.

## Pengaturan

1. Buka halaman **Settings → Barrier Alert Email** di backoffice, pilih frekuensi, lalu atur waktu (serta hari atau tanggal bila diperlukan). Pengaturan disimpan di tabel `app_settings` dengan key `stock_barrier_alerts.email_schedule`.

2. Email otomatis ditujukan kepada user backoffice aktif yang memiliki akses ke halaman barrier yang memicu alert. Alamat tambahan bisa diatur melalui environment; beberapa alamat dipisahkan koma:

   ```dotenv
   STOCK_BARRIER_ALERT_EMAILS=logistik@example.com,store-manager@example.com
   ```

3. Pastikan `MAIL_MAILER`, host, kredensial SMTP, dan `MAIL_FROM_ADDRESS` sudah mengarah ke layanan email yang aktif. Setelah mengubah environment, jalankan `php artisan config:cache` jika deployment memakai config cache.

4. Terapkan tabel status dan outbox pada database backoffice:

   ```bash
   php artisan migrate --database=immostore_backoffice --path=database/migrations/2026_11_04_000002_create_stock_barrier_alert_tables.php --force
   ```

5. Pastikan scheduler Laravel dipanggil setiap menit oleh cron di server aplikasi:

   ```cron
   * * * * * cd /var/www/immostore-backoffice && php artisan schedule:run >> /dev/null 2>&1
   ```

Email yang gagal disimpan di outbox dan dicoba ulang paling cepat lima menit kemudian. Jalankan `php artisan stock-barriers:monitor` untuk pemindaian manual; command yang sama dipakai oleh scheduler.
