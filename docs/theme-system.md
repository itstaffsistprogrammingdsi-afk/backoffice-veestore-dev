# Sistem Tema Light dan Dark

Status: implemented
Tanggal: 2026-11-01

## 1. Tujuan

Menyediakan dua tampilan, light mode dan dark mode, dengan perilaku yang konsisten di seluruh halaman Inertia.

## 2. Implementasi

File utama:

- `resources/css/theme.css`
- `resources/js/composables/useTheme.ts`
- `resources/js/layouts/AppLayout.vue`
- `resources/js/components/AuthShell.vue`
- `resources/views/app.blade.php`
- `resources/views/auth.blade.php`

Cara kerja:

1. Inline script di root view membaca `localStorage` dan `prefers-color-scheme` sebelum CSS dimuat, sehingga tidak ada flash tema yang salah.
2. `useTheme` menyimpan pilihan ke `localStorage` dengan key `veestore.theme`.
3. Toggle tersedia di topbar aplikasi dan halaman auth.
4. `data-theme` dipasang pada elemen `html`.
5. Semua warna memakai CSS variable `--app-*`.

## 3. Token warna

Palet diambil dari brand Veestore Official (`https://veestoreofficial.com/home`):

- Pink: `#c366a8`
- Pink soft: `#ff99d6`
- Blue: `#0060af`
- Blue deep: `#003a70`
- Mint: `#6ec5a1`
- Yellow: `#fcbd00`
- Orange: `#f15a22`

| Token | Light | Dark |
|---|---|---|
| `--app-bg` | `#f6f6f6` | `#0d1b2a` |
| `--app-surface` | `#ffffff` | `#12263a` |
| `--app-surface-2` | `#fafafa` | `#17304a` |
| `--app-ink` | `#2f2f2f` | `#f5f8fb` |
| `--app-muted` | `#6f6f6f` | `#a9b8c8` |
| `--app-border` | `#d9d9d9` | `#28445f` |
| `--app-accent` | `#c366a8` | `#ff99d6` |
| `--app-accent-strong` | `#9d3f86` | `#ff99d6` |
| `--app-hero-bg` | `#003a70` | `#003a70` |
| `--app-sidebar-bg` | `#ffffff` | `#0a1930` |
| `--app-sidebar-ink` | `#2f2f2f` | `#eaf2fb` |

## 4. Sidebar

Sidebar mengikuti dua mode:

- Light mode: permukaan putih, border abu muda, item aktif memakai pink soft dan teks pink gelap.
- Dark mode: permukaan biru malam, teks terang, item aktif memakai biru medium dan aksen pink soft.

Kontras teks sidebar sudah dicek:

- Teks utama light mode di atas putih: 13.39:1.
- Teks muted light mode di atas putih: 5.02:1.
- Teks muted dark mode di atas biru malam: 8.69:1.
- Item aktif dark mode: 6.02:1.

## 5. Cakupan

Sudah mendukung light dan dark:

- Shell utama `AppLayout`.
- Halaman auth.
- Halaman hub Operasional dan Laporan.
- AI Analytics dan AI Assistant.
- Settings, Loyalty, Management.
- Report Export.
- Branch Operations.
- Ecommerce.
- Seluruh Reports.
- Enam halaman Master Data sederhana.

Belum mendukung dark mode:

- Halaman Blade lama yang belum dimigrasikan: sebagian Master Data, Monitoring, dan Dashboard.

## 6. Aksesibilitas

- Toggle memakai `button` dengan `aria-label` dan `title`.
- Fokus keyboard terlihat.
- Kontras teks mengikuti token tema.
- Preferensi sistem dihormati saat belum ada pilihan manual.
- Pilihan manual disimpan dan menang atas preferensi sistem.

## 7. Validasi visual

Screenshot headless Chrome sudah dicek untuk:

- `/login` light dan dark.
- `/operations` light dan dark.
- `/reports/crm` light dan dark.
- `/master-data/partners` dark.

Hasil: sidebar light memakai permukaan putih dan teks gelap, sidebar dark memakai biru malam dan teks terang, hero memakai brand blue deep, tombol primary memakai brand pink, dan heading pada hero tetap putih.

## 8. Validasi teknis

| Pemeriksaan | Hasil |
|---|---|
| `npm run typecheck` | Lulus |
| `npm run build:vite` | Berhasil |
| Full test suite | 34 lulus, 495 assertion |
| `composer lint` | Lulus |
| `composer analyse` | 0 error |
| `route:cache` | Berhasil |
| `view:cache` | Berhasil |

## 9. Langkah berikutnya

1. Migrasikan halaman Blade yang tersisa agar dark mode berlaku menyeluruh.
2. Tambahkan test visual atau screenshot untuk kedua tema.
3. Evaluasi kontras token dengan alat contrast checker.
4. Tambahkan preferensi tema per user jika dibutuhkan.
