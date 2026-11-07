<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class VoucherImageReader implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return implode(' ', [
            'Anda membaca gambar voucher promosi VeeStore dan mengekstrak datanya untuk formulir backoffice.',
            'Jawab hanya dengan data yang benar-benar terlihat pada gambar. Jangan mengarang nilai.',
            'name adalah judul atau nama promo yang paling menonjol pada gambar, tanpa kode voucher dan tanpa nominal diskon.',
            'description adalah kalimat promosi tambahan saja. Jangan masukkan nominal, tanggal, atau syarat ke description.',
            'min_purchase diisi dari teks minimal belanja atau minimal transaksi.',
            'max_discount diisi dari teks maksimal diskon atau batas potongan.',
            'Gunakan string kosong bila sebuah nilai tidak terbaca atau tidak ada pada gambar.',
            'type hanya boleh "fixed" untuk potongan nominal rupiah atau "percentage" untuk diskon persen.',
            'value berisi angka saja tanpa simbol mata uang. Contoh: 50000 atau 20.',
            'distribution_type hanya boleh "public" untuk satu kode voucher umum atau "unique_code" bila gambar menyebut kode unik per pelanggan.',
            'contexts hanya boleh berisi nilai dari daftar berikut: ecommerce_checkout, ecommerce_shipping, store_checkout, store_customer_return. Kosongkan bila tidak dapat ditentukan dari gambar.',
            'valid_from dan valid_until menggunakan format YYYY-MM-DD. Kosongkan bila tidak ada tanggal pada gambar.',
            'code adalah kode voucher yang tercetak pada gambar, bila ada.',
            'confidence berisi angka 0 sampai 100 yang menunjukkan seberapa yakin hasil pembacaan gambar.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->nullable()->description('Nama voucher yang tercetak.'),
            'description' => $schema->string()->nullable()->description('Deskripsi atau syarat singkat voucher.'),
            'code' => $schema->string()->nullable()->description('Kode voucher yang tercetak pada gambar.'),
            'type' => $schema->string()->nullable()->description('fixed atau percentage.'),
            'value' => $schema->string()->nullable()->description('Nilai diskon berupa angka tanpa simbol.'),
            'max_discount' => $schema->string()->nullable()->description('Batas maksimal diskon rupiah bila ada.'),
            'min_purchase' => $schema->string()->nullable()->description('Minimal belanja rupiah bila ada.'),
            'valid_from' => $schema->string()->nullable()->description('Tanggal mulai berlaku format YYYY-MM-DD.'),
            'valid_until' => $schema->string()->nullable()->description('Tanggal berakhir format YYYY-MM-DD.'),
            'usage_limit' => $schema->string()->nullable()->description('Batas jumlah pemakaian atau jumlah kode unik.'),
            'distribution_type' => $schema->string()->nullable()->description('public atau unique_code.'),
            'contexts' => $schema->array()->items($schema->string())->nullable()->description('Konteks pemakaian voucher.'),
            'confidence' => $schema->integer()->nullable()->description('Tingkat keyakinan pembacaan 0 sampai 100.'),
        ];
    }
}
