# Alert stok barrier

Pemantau berjalan setiap menit untuk barrier Store dan Warehouse. Produk masuk alert saat stok tersedia (`stok fisik - reservasi`, minimum 0) sama dengan atau lebih rendah dari ambang barrier setelah toleransi. Alert internal tampil di dropdown notifikasi backoffice dan mengarah ke halaman stok terkait.

Satu digest email per jenis lokasi dikirim untuk produk yang baru menyentuh ambang. Stok yang tetap rendah tidak mengirim email berulang. Setelah stok kembali di atas ambang, status alert selesai; penurunan berikutnya membuat alert baru. Email memuat maksimal 200 rincian produk per jenis lokasi, sedangkan hitungan alert internal tetap mencakup semua produk.

## Pengaturan

1. Email otomatis ditujukan kepada user backoffice aktif yang memiliki akses ke halaman barrier yang memicu alert. Alamat tambahan bisa diatur melalui environment; beberapa alamat dipisahkan koma:

   ```dotenv
   STOCK_BARRIER_ALERT_EMAILS=logistik@example.com,store-manager@example.com
   ```

2. Pastikan `MAIL_MAILER`, host, kredensial SMTP, dan `MAIL_FROM_ADDRESS` sudah mengarah ke layanan email yang aktif. Setelah mengubah environment, jalankan `php artisan config:cache` jika deployment memakai config cache.

3. Terapkan tabel status dan outbox pada database backoffice:

   ```bash
   php artisan migrate --database=immostore_backoffice --path=database/migrations/2026_11_04_000002_create_stock_barrier_alert_tables.php --force
   ```

4. Pastikan scheduler Laravel dipanggil setiap menit oleh cron di server aplikasi:

   ```cron
   * * * * * cd /var/www/immostore-backoffice && php artisan schedule:run >> /dev/null 2>&1
   ```

Email yang gagal disimpan di outbox dan dicoba ulang paling cepat lima menit kemudian. Jalankan `php artisan stock-barriers:monitor` untuk pemindaian manual; command yang sama dipakai oleh scheduler.
