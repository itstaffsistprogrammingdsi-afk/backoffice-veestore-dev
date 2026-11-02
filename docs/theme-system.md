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

Palet memakai biru royal yang lebih vivid sebagai aksen utama, dengan tint biru muda untuk state aktif. Tidak ada aksen pink atau ungu.

- Hero navy blue: `#1c2a6b`
- Blue accent: `#3b4cf0`
- Blue strong: `#2f3dd1`
- Blue soft: `#eef1ff`
- Eyebrow: `#b7c3ff`

| Token | Light | Dark |
|---|---|---|
| `--app-bg` | `#f7f8fc` | `#0d1322` |
| `--app-surface` | `#ffffff` | `#151c30` |
| `--app-surface-2` | `#f8f9ff` | `#1b2340` |
| `--app-ink` | `#171b26` | `#f5f7ff` |
| `--app-muted` | `#6b7280` | `#aab1c8` |
| `--app-border` | `#e5e7f0` | `#2a3455` |
| `--app-accent` | `#3b4cf0` | `#8fa2ff` |
| `--app-accent-strong` | `#2f3dd1` | `#8fa2ff` |
| `--app-hero-bg` | `#1c2a6b` | `#1a255c` |
| `--app-hero-eyebrow` | `#b7c3ff` | `#b7c3ff` |
| `--app-sidebar-bg` | `#ffffff` | `#0b1120` |
| `--app-sidebar-ink` | `#171b26` | `#eef1ff` |

## 4. Sidebar

Sidebar mengikuti dua mode:

- Light mode: permukaan putih, border abu kebiruan, item aktif memakai biru soft dan teks biru gelap.
- Dark mode: permukaan biru malam, teks terang, item aktif memakai biru medium dan aksen biru terang.

Kontras teks sidebar sudah dicek:

- Teks utama light mode di atas putih: 17.2:1.
- Teks muted light mode di atas putih: 4.83:1.
- Teks muted light mode di atas background: 4.56:1.
- Item aktif light mode: 6.89:1.
- Teks muted dark mode di atas permukaan gelap: 7.92:1.
- Item aktif dark mode: 6.26:1.

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
- Seluruh Master Data, termasuk Voucher Codes dan Voucher Print.
- Seluruh Monitoring.
- Dashboard.
- AI Analytics dan AI Assistant.

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
- `/reports` light dan dark.
- `/monitoring/stok-wms` light dan dark.
- `/dashboard` light dan dark.

Hasil: sidebar light memakai permukaan putih dan teks gelap, sidebar dark memakai biru malam dan teks terang, hero memakai navy blue, tombol primary memakai biru royal, dan heading pada hero tetap putih.

## 8. Validasi teknis

| Pemeriksaan | Hasil |
|---|---|
| `npm run typecheck` | Lulus |
| `npm run build:vite` | Berhasil |
| Full test suite | 50 lulus, 892 assertion |
| `composer lint` | Lulus |
| `composer analyse` | 0 error |
| `route:cache` | Berhasil |
| `view:cache` | Berhasil |

## 9. Langkah berikutnya

1. Tambahkan test visual otomatis untuk kedua tema bila pipeline screenshot tersedia.
2. Evaluasi kontras token setiap kali palet berubah.
3. Tambahkan preferensi tema per user jika dibutuhkan.
