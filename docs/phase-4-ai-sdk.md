# Fase 4: Integrasi Laravel AI SDK

Status: pilot berjalan
Tanggal: 2026-11-01
Branch: `chore/laravel-13-upgrade`

## 1. Tujuan

Fase 4 memasang `laravel/ai` sebagai lapisan orkestrasi AI, membuat satu agent internal, dan menghubungkannya ke data forecast serta rekomendasi restock melalui tool yang aman.

## 2. Dependency dan konfigurasi

Ditambahkan:

- `laravel/ai` 1.3.0
- `config/ai.php`

Environment baru:

```ini
AI_PROVIDER=openai
AI_MODEL=gpt-6.1-sol
OPENAI_API_KEY=
```

Untuk provider yang kompatibel dengan OpenAI:

```ini
AI_PROVIDER=openai-compatible
AI_MODEL=nama-model-lokal
OPENAI_COMPATIBLE_URL=http://127.0.0.1:11434/v1
OPENAI_COMPATIBLE_API_KEY=
```

Migration `agent_conversations` belum dijalankan karena pilot ini belum memakai penyimpanan percakapan. Agent berjalan stateless per pertanyaan.

## 3. Agent

`App\Ai\Agents\SalesAnalyst` adalah agent analis penjualan dan stok.

Aturan utama:

- Menjawab dalam Bahasa Indonesia.
- Wajib memakai tool untuk mengambil data.
- Tidak boleh mengarang angka, nama toko, atau nama produk.
- Harus menyebut periode training dan model run.
- Harus menyatakan jika data belum cukup.

## 4. Tool aman

Tool tidak memberikan akses SQL bebas. Keduanya memakai `AnalyticsDashboardQuery` yang sudah ada.

| Tool | Fungsi | Batas |
|---|---|---|
| `LatestForecastSummary` | Ringkasan model run terakhir, metrik, total unit, dan total omzet | Hanya data agregat |
| `StockRiskRecommendations` | Rekomendasi restock berstatus recommended | Filter risiko, maksimal 25 baris |

Data yang dikembalikan tidak memuat data pribadi pelanggan. Tool hanya membaca tabel analytics di database backoffice.

## 5. Endpoint dan UI

Route baru:

| Method | URI | Name |
|---|---|---|
| GET | `/ai-assistant` | `ai-assistant.index` |
| POST | `/ai-assistant/ask` | `ai-assistant.ask` |

Halaman Inertia: `resources/js/pages/Assistant/Index.vue`.

Fitur halaman:

- Ringkasan metrik model aktif.
- Konteks model dan periode training.
- Empat contoh pertanyaan.
- Form pertanyaan dengan validasi maksimal 1000 karakter.
- Tampilan jawaban dan pesan error.
- Menu sidebar `AI Assistant`.

Alur request:

```text
Browser -> POST /ai-assistant/ask
  -> AskAssistantRequest
  -> SalesAnalyst
  -> LatestForecastSummary / StockRiskRecommendations
  -> redirect ke /ai-assistant
  -> Inertia menampilkan answer
```

## 6. Penanganan kegagalan

Jika provider belum dikonfigurasi atau API key kosong, controller menangkap exception dan menampilkan pesan:

> Asisten AI belum dapat dihubungi. Periksa konfigurasi provider dan API key.

Exception tetap dicatat lewat `report()` tanpa menyimpan prompt ke log aplikasi.

## 7. Validasi

| Pemeriksaan | Hasil |
|---|---|
| `npm run typecheck` | Lulus |
| `npm run build:vite` | Berhasil, 637 modul |
| HTTP `/ai-assistant` | 200 |
| Inertia component | `Assistant/Index` |
| Props | `latestRun`, `summary`, `suggestions`, `urls`, `answer`, `prompt`, `error` |
| Test suite | 15 lulus setelah fase 5. |
| Test gagal | `QueuedReportExportTest`, karena `report_exports` belum ada. Diperbaiki pada fase 5. |
| Agent fake test | Lulus |
| `route:cache` | Berhasil |
| `view:cache` | Berhasil |

## 8. Catatan

- Agent belum diuji dengan provider nyata karena API key belum diisi.
- Tool saat ini hanya membaca hasil forecast. Belum ada query penjualan historis langsung.
- Penyimpanan conversation dan usage logging belum ada.
- Route memakai `auth` dan permission `view ai-analytics`. Rate limit 10 request per menit per user sudah aktif.
- Penggunaan provider berbayar harus dibatasi sebelum dibuka ke semua user.

## 9. Langkah berikutnya

1. Isi API key dan uji satu pertanyaan nyata di environment lokal.
2. Tambahkan permission khusus AI Assistant jika ingin memisahkan akses dari AI Analytics.
3. Rate limit per user sudah ditambahkan pada fase 5.
4. Tambahkan usage logging dan biaya per request.
5. Tambahkan conversation storage jika dibutuhkan.
6. Tambahkan embedding dan vector store untuk dokumen SOP atau katalog.
7. Tambahkan evaluasi kualitas jawaban dan akurasi forecast.
