# Immostore AI Analytics Service

Backend Python ini merupakan bagian dari repository `backoffice-immostore`, tetapi tetap berjalan sebagai service terpisah. Python membaca data operasional dari `veestore_dev` dan menulis hasil ke tabel `analytics_*` pada `veestore_bo_dev`.

## Menjalankan lokal

Dari folder utama backoffice, jalankan `start-ai.cmd`. Service tersedia pada `http://127.0.0.1:8001` dan health check pada `http://127.0.0.1:8001/health`.

Perintah manual:

```powershell
cd services\ai-analytics
.\.venv\Scripts\python.exe -m uvicorn app.main:app --host 127.0.0.1 --port 8001
```

Menjalankan forecast tanpa API:

```powershell
cd services\ai-analytics
.\.venv\Scripts\python.exe -m jobs.run_forecast
```

Endpoint `POST /api/v1/jobs/forecast` membutuhkan header `X-API-Token`. Endpoint tersebut disiapkan untuk job internal, bukan akses browser publik.

## Jadwal yang disarankan

Jalankan batch setiap hari setelah transaksi hari sebelumnya selesai. Model hanya menerbitkan forecast untuk seri produk yang memenuhi syarat minimum kualitas data. Riwayat model dan jumlah output dicatat di `analytics_model_runs`.
