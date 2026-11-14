<?php

namespace App\Ai\Support;

use Carbon\CarbonImmutable;

class QuestionIntentGuard
{
    /**
     * @return array{intent: string, answer: string, answer_status: string, safe_actions: list<string>}|null
     */
    public function inspect(string $message): ?array
    {
        $message = trim($message);

        if ($message === '') {
            return null;
        }

        if ($this->asksForSchemaOrSecret($message)) {
            return $this->result(
                'schema_or_secret',
                'Saya tidak dapat menampilkan struktur database, nama tabel, password, token, kunci, atau data biometrik. '
                .'Saya bisa membantu menjawab pertanyaan bisnis dari data yang Anda berwenang lihat.',
                AnswerStatus::FORBIDDEN,
                ['Tanyakan metrik bisnis seperti omzet, stok, pesanan, atau pelanggan dalam bentuk ringkasan.']
            );
        }

        if ($this->asksForMassCustomerDetail($message)) {
            return $this->result(
                'mass_customer_detail',
                'Saya tidak menampilkan data pribadi pelanggan secara massal, termasuk nama, email, dan nomor HP. '
                .'Saya bisa menampilkan ringkasan jumlah, segmentasi, atau tren. Untuk detail, sebutkan satu pelanggan secara eksplisit.',
                AnswerStatus::FORBIDDEN,
                ['Tanyakan jumlah atau segmentasi pelanggan, misalnya per tier, provinsi, atau bulan registrasi.']
            );
        }

        if ($this->asksForDestructiveAction($message)) {
            return $this->result(
                'destructive_action',
                'Saya tidak dapat menghapus transaksi atau mengubah harga secara massal dari percakapan. '
                .'Gunakan menu void/reversal atau alur persetujuan perubahan harga agar jejak audit tetap utuh.',
                AnswerStatus::FORBIDDEN,
                ['Buka menu Void & Cancel untuk pembatalan transaksi.', 'Buka menu Harga Produk untuk perubahan harga dengan persetujuan.']
            );
        }

        $futurePeriod = $this->futurePeriod($message);

        if ($futurePeriod !== null) {
            return $this->result(
                'future_period',
                "Periode {$futurePeriod} belum terjadi, jadi belum ada data yang dapat dihitung. "
                .'Saya tidak akan menampilkannya sebagai nol tercatat.',
                AnswerStatus::NOT_YET_OCCURRED,
                ['Pilih periode yang sudah berjalan atau minta proyeksi secara terpisah.']
            );
        }

        if ($this->isAmbiguousStockQuestion($message)) {
            return $this->result(
                'ambiguous_stock',
                'Saya perlu satu klarifikasi: produk apa dan lokasi mana yang dimaksud? '
                .'Sebutkan nama atau SKU produk, lalu toko atau gudangnya.',
                AnswerStatus::NEEDS_CLARIFICATION,
                ['Contoh: "Stok produk X di Toko Y kemarin berapa?"']
            );
        }

        if ($this->isOutOfScope($message)) {
            return $this->result(
                'out_of_scope',
                'Pertanyaan itu di luar data retail VeeStore, dan saya tidak memiliki sumber eksternal yang sah untuk rekomendasi tempat makan. '
                .'Saya bisa membantu analisis penjualan, stok, pesanan, pelanggan, promo, atau operasional toko.',
                AnswerStatus::NOT_AVAILABLE,
                ['Tanyakan performa produk, stok, atau pelanggan di VeeStore.']
            );
        }

        if ($this->asksForPayroll($message)) {
            return $this->result(
                'payroll',
                'Data payroll dan slip gaji belum tersedia di VEE. '
                .'Saya tidak dapat menghitung atau menampilkan gaji dari data yang ada.',
                AnswerStatus::NOT_AVAILABLE,
                ['Gunakan laporan SDM atau sistem payroll yang menjadi sumber resmi perusahaan.']
            );
        }

        return null;
    }

    private function asksForSchemaOrSecret(string $message): bool
    {
        if (preg_match('/\bselect\s+\*\s+from\s+(users|customers)\b/i', $message)) {
            return true;
        }

        $secret = '/\b(password|kata sandi|token|secret|api[\s-]?key|private[\s-]?key|biometrik|face[\s-]?descriptor|struktur\s+(database|tabel|data)|skema\s+(database|tabel|data)|isi\s+database|semua\s+tabel|daftar\s+tabel|dump\s+database|sql\s+injection)\b/i';
        $request = '/(tampilkan|lihat|berikan|kirim|akses|ambil|bocorkan|dump|buka|cek|daftar|semua|isi|salin)/i';

        return (bool) preg_match($secret, $message) && (bool) preg_match($request, $message);
    }

    private function asksForMassCustomerDetail(string $message): bool
    {
        $mass = '/(semua|seluruh|daftar|data\s+lengkap|rincian|21)\s+(pelanggan|customer)|(pelanggan|customer)\s+(semua|lengkap|seluruh|massal)/i';
        $aggregate = '/(ulang\s+tahun|birthday|jumlah|berapa|total|segmentasi|demografi|provinsi|tier|membership|top|teratas|baru|registrasi|kanal|gender|usia|ringkasan)/i';

        return (bool) preg_match($mass, $message) && ! (bool) preg_match($aggregate, $message);
    }

    private function asksForDestructiveAction(string $message): bool
    {
        if (preg_match('/(hapus|delete|drop|truncate)\s+(semua\s+)?(transaksi|data|pesanan|invoice|pelanggan|customer)/i', $message)) {
            return true;
        }

        return (bool) preg_match('/(naikkan|ubah|update|ganti)\s+(semua\s+)?harga/i', $message);
    }

    private function asksForPayroll(string $message): bool
    {
        return (bool) preg_match('/\b(gaji|payroll|slip\s+gaji|upah)\b/i', $message);
    }

    private function isAmbiguousStockQuestion(string $message): bool
    {
        if (mb_strlen($message) > 60) {
            return false;
        }

        if (! preg_match('/\bstok\b/i', $message)) {
            return false;
        }

        if (! preg_match('/(brp|berapa|kmrn|kemarin|sekarang|saat\s+ini)/i', $message)) {
            return false;
        }

        return ! preg_match('/(produk|sku|barang|toko|gudang|cabang|semua|total|agregat|minimum|menipis|restock|kedaluwarsa|expired|aging|slow|per\s+lokasi|per\s+toko|per\s+gudang|ringkasan)/i', $message);
    }

    private function isOutOfScope(string $message): bool
    {
        return (bool) preg_match('/(tempat\s+makan|restoran|rumah\s+makan|kafe|kuliner|rekomendasi\s+makan|makan\s+enak)/i', $message);
    }

    private function futurePeriod(string $message): ?string
    {
        if (! preg_match('/\b(20\d{2}|21\d{2})\b/', $message, $matches)) {
            return null;
        }

        $year = (int) $matches[1];

        if ($year <= CarbonImmutable::now()->year) {
            return null;
        }

        $months = [
            'januari' => 'Januari', 'februari' => 'Februari', 'maret' => 'Maret', 'april' => 'April',
            'mei' => 'Mei', 'juni' => 'Juni', 'juli' => 'Juli', 'agustus' => 'Agustus',
            'september' => 'September', 'oktober' => 'Oktober', 'november' => 'November', 'desember' => 'Desember',
            'january' => 'Januari', 'february' => 'Februari', 'march' => 'Maret', 'may' => 'Mei',
            'june' => 'Juni', 'july' => 'Juli', 'august' => 'Agustus', 'october' => 'Oktober',
            'december' => 'Desember',
        ];

        $lower = strtolower($message);

        foreach ($months as $needle => $label) {
            if (str_contains($lower, $needle)) {
                return "{$label} {$year}";
            }
        }

        return "tahun {$year}";
    }

    /**
     * @param  list<string>  $safeActions
     * @return array{intent: string, answer: string, answer_status: string, safe_actions: list<string>}
     */
    private function result(string $intent, string $answer, string $status, array $safeActions): array
    {
        return [
            'intent' => $intent,
            'answer' => $answer,
            'answer_status' => $status,
            'safe_actions' => $safeActions,
        ];
    }
}
