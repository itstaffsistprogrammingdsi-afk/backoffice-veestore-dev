import english from '../../../lang/en.json';
import indonesian from '../../../lang/id.json';

export type AppLocale = 'id' | 'en';

declare global {
    interface Window {
        __VEESTORE_LOCALE__?: AppLocale;
    }
}

const localeTerms: Record<AppLocale, Record<string, string>> = {
    id: {
        ...(indonesian as Record<string, string>),
    },
    en: english as Record<string, string>,
};

const commonLocaleTerms: Record<AppLocale, Record<string, string>> = {
    id: {
        add: 'Tambah', adding: 'Menambahkan', all: 'Semua', analytics: 'Analitik', approve: 'Setujui', approval: 'Persetujuan',
        approved: 'Disetujui', available: 'Tersedia', back: 'Kembali', cancel: 'Batal', cancelled: 'Dibatalkan', category: 'Kategori',
        change: 'Ubah', click: 'Klik', code: 'Kode', complete: 'Selesai', completed: 'Selesai', condition: 'Kondisi',
        customer: 'Pelanggan', customers: 'Pelanggan', date: 'Tanggal', day: 'Hari', days: 'Hari', delete: 'Hapus',
        description: 'Deskripsi', demographics: 'Demografi', detail: 'Detail', details: 'Rincian', discount: 'Diskon',
        display: 'Display', document: 'Dokumen', download: 'Unduh', edit: 'Edit', ecommerce: 'E-commerce', email: 'Email',
        export: 'Ekspor', failed: 'Gagal', filter: 'Filter', filters: 'Filter', from: 'Dari', good: 'Baik', group: 'Grup',
        history: 'Riwayat', inbound: 'Inbound', inactive: 'Tidak aktif', invitee: 'Penerima undangan', item: 'Barang',
        items: 'Barang', label: 'Label', layout: 'Tata letak', location: 'Lokasi', login: 'Masuk', logout: 'Keluar',
        loyalty: 'Loyalitas', low: 'Rendah', master: 'Master', menu: 'Menu', missing: 'Hilang', month: 'Bulan', name: 'Nama',
        no: 'Tidak', note: 'Catatan', order: 'Pesanan', orders: 'Pesanan', operations: 'Operasional', outbound: 'Outbound',
        paid: 'Lunas', partner: 'Mitra', partners: 'Mitra', payment: 'Pembayaran', payments: 'Pembayaran', pending: 'Menunggu',
        permission: 'Izin', point: 'Poin', points: 'Poin', preference: 'Preferensi', product: 'Produk', products: 'Produk',
        quantity: 'Kuantitas', rate: 'Kurs', report: 'Laporan', reports: 'Laporan', request: 'Permintaan', reset: 'Reset',
        reward: 'Reward', role: 'Peran', save: 'Simpan', search: 'Cari', sign: 'Masuk', status: 'Status', stock: 'Stok',
        store: 'Toko', stores: 'Toko', supplier: 'Pemasok', suppliers: 'Pemasok', today: 'Hari ini', to: 'Ke', total: 'Total',
        transaction: 'Transaksi', transactions: 'Transaksi', type: 'Jenis', update: 'Perbarui', user: 'Pengguna', users: 'Pengguna',
        warehouse: 'Gudang', warehouses: 'Gudang', voucher: 'Voucher', vouchers: 'Voucher',
        account: 'Akun', access: 'Akses', active: 'Aktif', activity: 'Aktivitas', age: 'Usia', branch: 'Cabang',
        business: 'Usaha', center: 'Pusat', choose: 'Pilih', close: 'Tutup', current: 'Saat ini', gender: 'Gender',
        help: 'Bantuan', hour: 'Jam', hours: 'Jam', main: 'Utama', male: 'Laki-laki', female: 'Perempuan',
        next: 'Berikutnya', previous: 'Sebelumnya', queue: 'Antrean', source: 'Sumber', destination: 'Tujuan',
        received: 'Diterima', recorded: 'Tercatat', released: 'Dirilis', remaining: 'Sisa', show: 'Tampilkan',
        submitted: 'Diajukan', threshold: 'Ambang batas', until: 'Sampai', used: 'Digunakan', view: 'Lihat', yes: 'Ya',
        no_results: 'Tidak ada hasil',
    },
    en: {
        akses: 'Access', aktif: 'Active', alasan: 'Reason', akun: 'Account', analitik: 'Analytics', area: 'Area',
        barang: 'Item', baris: 'Rows', bantuan: 'Help', belum: 'Not yet', buka: 'Open', bulan: 'Month', cabang: 'Store',
        cari: 'Search', customer: 'Customer', data: 'Data', dari: 'From', dengan: 'With', deskripsi: 'Description',
        diterima: 'Received', ditolak: 'Rejected', disetujui: 'Approved', digunakan: 'Used', dokumen: 'Document',
        ekspor: 'Export', filter: 'Filter', gudang: 'Warehouse', hari: 'Day', harga: 'Price', jumlah: 'Quantity',
        kanal: 'Channel', keluar: 'Out', kembali: 'Back', kategori: 'Category', kelola: 'Manage', lengkap: 'Complete',
        lainnya: 'Other', laporan: 'Report', lokasi: 'Location', masuk: 'In', menampilkan: 'Showing', menunggu: 'Pending',
        nama: 'Name', nilai: 'Value', nomor: 'Number', pemasok: 'Supplier', pembayaran: 'Payment', pelanggan: 'Customer',
        pengguna: 'User', penjualan: 'Sales', perbarui: 'Update', permintaan: 'Request', pesanan: 'Order', pilih: 'Select',
        produk: 'Product', proses: 'Process', sampai: 'To', selesai: 'Completed', semua: 'All', status: 'Status',
        stok: 'Stock', tambah: 'Add', tanggal: 'Date', toko: 'Store', total: 'Total', transaksi: 'Transaction',
        tujuan: 'Destination', ubah: 'Change', unduh: 'Download', untuk: 'For', wilayah: 'Region', ya: 'Yes', tidak: 'No',
        terakhir: 'Last', retur: 'Return', terlambat: 'Overdue', tercatat: 'Recorded', tersedia: 'Available',
        ada: 'Available', adalah: 'Is', agar: 'So that', agenda: 'Plan', aktivitas: 'Activity', akurat: 'Accurate',
        alokasi: 'Allocation', ambang: 'Threshold', analisis: 'Analysis', angka: 'Figures', antrean: 'Queue',
        arahkan: 'Hover', asal: 'Source', atas: 'Above', atur: 'Set', baru: 'New', batas: 'Limit', bawah: 'Below',
        berdasarkan: 'Based on', berhasil: 'Successful', berikut: 'Next', berikutnya: 'Next', beberapa: 'Several',
        belanja: 'Spending', benar: 'Actual', berakhir: 'Ended', berbeda: 'Different', bersih: 'Net', bersihkan: 'Clear',
        bisa: 'Can', buat: 'Create', bukan: 'Not', cara: 'How', cek: 'Check', cepat: 'Quick', cocok: 'Matching',
        contoh: 'Example', cukup: 'Enough', dalam: 'In', dapat: 'Can', dasar: 'Base', definisi: 'Definition',
        dahulu: 'First', daftar: 'List', datanya: 'Its data', didukung: 'Supported',
        di: 'In', dibaca: 'Readable', dibatalkan: 'Cancelled', dibayar: 'Paid', dibeli: 'Purchased', diberikan: 'Given',
        dibuat: 'Created', dibuka: 'Opened', dicek: 'Checked', dicetak: 'Printed', dihitung: 'Calculated',
        diisi: 'Filled', dikelola: 'Managed', dikurangi: 'Reduced', dilepas: 'Released', dilihat: 'Viewed', dimensi: 'Dimension',
        diminta: 'Requested', dimuat: 'Loaded', dipakai: 'Used', dipantau: 'Monitored', dipecah: 'Split', diperbaiki: 'Repaired',
        diperbarui: 'Updated', dipesan: 'Ordered', dipilih: 'Selected', dipisahkan: 'Separated', diproses: 'Processed',
        direservasi: 'Reserved', dirilis: 'Released', disimpan: 'Saved', ditahan: 'Held', ditampilkan: 'Shown',
        ditandai: 'Marked', ditentukan: 'Set', diterapkan: 'Applied', ditindaklanjuti: 'Followed up', diubah: 'Changed',
        domain: 'Domain', dominan: 'Dominant', gender: 'Gender', grafik: 'Chart', gunakan: 'Use',
        halaman: 'Page', hasil: 'Results', hingga: 'Through', hubungi: 'Contact', hubungkan: 'Connect', jalan: 'Delivery note',
        jenis: 'Type', jika: 'If', karena: 'Because', kata: 'Term', kecamatan: 'District', kedaluwarsa: 'Expired',
        kekurangan: 'Shortage', kelengkapan: 'Completeness', kelompok: 'Group', kelurahan: 'Village', kemungkinan: 'May',
        kendala: 'Issue', keputusan: 'Decision', kerja: 'Work', ketersediaan: 'Availability', ketidaksesuaian: 'Discrepancy',
        kirim: 'Send', klik: 'Select', kolom: 'Column', kontak: 'Contact', konten: 'Content', kontribusi: 'Contribution',
        kosong: 'Empty', kota: 'City', kotor: 'Gross', kumpulan: 'Collection', kunci: 'Key', kunjungan: 'Visit',
        kurang: 'Less', kurir: 'Courier', kursor: 'Pointer', kustom: 'Custom', lanjut: 'Continue',
        langsung: 'Directly', lembur: 'Overtime', lengkapi: 'Complete', melewati: 'Exceeds', melihat: 'View',
        memakai: 'Use', membaca: 'Read', membeli: 'Buy', memiliki: 'Have', meminta: 'Request', memperbarui: 'Update',
        memuat: 'Loading', menahan: 'Hold', mendekati: 'Near', mengambil: 'Retrieve', menghitung: 'Calculate',
        mengikuti: 'Follow', mengisi: 'Fill', mengurangi: 'Reduce', menipis: 'Low', menjadi: 'Become', menjawab: 'Answer',
        menukar: 'Redeem', menunjukkan: 'Show', menyebutkan: 'Mention', menyentuh: 'Reach',
        menyesuaikan: 'Adjust', menyiapkan: 'Prepare', menyumbang: 'Contribute', mereservasi: 'Reserve', mereset: 'Reset',
        metrik: 'Metric', minimum: 'Minimum', misalnya: 'For example', modul: 'Module', mudah: 'Easy', mulai: 'Start',
        mutasi: 'Transaction', namanya: 'Its name', navigasi: 'Navigation', negara: 'Country', nominal: 'Amount',
        omset: 'Revenue', omzet: 'Revenue', otomatis: 'Automatically', opsi: 'Options', pada: 'During', paling: 'Most',
        pantau: 'Monitor', pemakaian: 'Usage', pemasukan: 'Income', pembagian: 'Allocation',
        pembeli: 'Buyers', pembelian: 'Purchase', pemenuhan: 'Fulfillment', peminta: 'Requester',
        penambahan: 'Addition', pencarian: 'Search', penerima: 'Recipient', penerimaan: 'Receipt', penggunaan: 'Use',
        pengiriman: 'Shipment', pengunjung: 'Visitors', penjumlahan: 'Sum', penuh: 'Full', penyesuaian: 'Adjustment',
        perbandingan: 'Comparison', percakapan: 'Conversation', performa: 'Performance', pergerakan: 'Movement',
        periksa: 'Review', perlu: 'Needs', persentase: 'Percentage', persetujuan: 'Approval', pertama: 'First',
        pertanyaan: 'Questions', perubahan: 'Changes', pihak: 'Party', pilihan: 'Options', pindah: 'Move', pola: 'Pattern',
        posisi: 'Position', pratinjau: 'Preview', prioritas: 'Priority', provinsi: 'Province', punya: 'Have',
        pusat: 'Center', rata: 'Average', realtime: 'Real-time', redeem: 'Redeem', referensi: 'Reference', registrasi: 'Registration',
        relasi: 'Relationship', rendah: 'Low', rentang: 'Range', replenishment: 'Replenishment', rincian: 'Details',
        ringkas: 'Summary', ringkasan: 'Summary', risiko: 'Risk', rusak: 'Damaged', saat: 'When', saldo: 'Balance',
        sama: 'Same', sapaan: 'Greeting', saran: 'Recommendation', sebagian: 'Partial', sebelum: 'Before', sedang: 'Currently',
        segera: 'Soon', segmen: 'Segment', segmentasi: 'Segmentation', sekaligus: 'At once', sekarang: 'Now', sekitar: 'About',
        selisih: 'Difference', seluruh: 'All', sepenuhnya: 'Fully', seperti: 'Such as', sering: 'Often', serta: 'And',
        sesuai: 'Matches', sesuatu: 'Something', setelah: 'After', setiap: 'Each', siap: 'Ready', sistem: 'System',
        sisa: 'Remaining', sumber: 'Source', surat: 'Delivery note', subjek: 'Subject', sudah: 'Already', tambahan: 'Additional',
        tambahkan: 'Add', tampilan: 'View', tanpa: 'Without', tanya: 'Ask', tata: 'Layout', telusuri: 'Explore',
        tempat: 'Place', tentang: 'About', terapkan: 'Apply', teratas: 'Top', terbanyak: 'Most', terbaru: 'Latest',
        terbesar: 'Largest', terdaftar: 'Registered', terdampak: 'Affected', tergantung: 'Depends', terhadap: 'Against',
        terhitung: 'Counted', terikat: 'Linked', terjual: 'Sold', terkait: 'Related', terkini: 'Current', terkunci: 'Locked',
        terlama: 'Longest', terlaris: 'Best-selling', terlebih: 'First', termasuk: 'Including', termuda: 'Youngest',
        tersebut: 'That', tertinggi: 'Highest', tertua: 'Oldest', tertukar: 'Mixed up', tetap: 'Still', tetapi: 'But',
        tim: 'Team', tindak: 'Action', tinggi: 'High', tinjau: 'Review', tipe: 'Type', tolak: 'Reject', toleransi: 'Tolerance',
        tren: 'Trend', tukar: 'Exchange', tutup: 'Close', uang: 'Money', ulang: 'Repeat', umur: 'Age', unit: 'Unit',
        unik: 'Unique', urut: 'Order', urutkan: 'Sort', usaha: 'Business', usia: 'Age', utama: 'Main', waktu: 'Time',
        warna: 'Color', yang: 'That',
    },
};

const statusTranslations: Record<AppLocale, Record<string, string>> = {
    id: {
        requested: 'Diajukan', pending: 'Menunggu', pending_approval: 'Menunggu Persetujuan',
        partial_approved: 'Disetujui Sebagian', approved: 'Disetujui', rejected: 'Ditolak',
        processing: 'Diproses', processed: 'Diproses', shipped: 'Dikirim', received: 'Diterima',
        partial_received: 'Diterima Sebagian', completed: 'Selesai', cancelled: 'Dibatalkan',
        canceled: 'Dibatalkan', paid: 'Lunas', failed: 'Gagal', refunded: 'Dikembalikan',
        qc_pending: 'Menunggu QC', release_pending: 'Menunggu Release', missing_awb: 'Belum Ada Resi',
        missing_delivery_order: 'Belum Ada Surat Jalan', all_passed: 'Lolos QC', partial_passed: 'Lolos QC Sebagian',
        all_failed: 'Gagal QC', not_recorded: 'Belum Tercatat', with_shipping: 'Ada Nomor Resi',
        without_shipping: 'Tanpa Nomor Resi', active: 'Aktif', inactive: 'Tidak Aktif', expired: 'Kedaluwarsa',
        below: 'Di Bawah Batas', near: 'Mendekati Batas', safe: 'Aman', no_barrier: 'Tanpa Batas Stok',
        store_stock: 'Stok Toko', warehouse_stock: 'Stok Gudang',
    },
    en: {
        requested: 'Requested', pending: 'Pending', pending_approval: 'Awaiting Approval',
        partial_approved: 'Partially Approved', approved: 'Approved', rejected: 'Rejected',
        processing: 'Processing', processed: 'Processed', shipped: 'Shipped', received: 'Received',
        partial_received: 'Partially Received', completed: 'Completed', cancelled: 'Cancelled',
        canceled: 'Cancelled', paid: 'Paid', failed: 'Failed', refunded: 'Refunded',
        qc_pending: 'Awaiting QC', release_pending: 'Awaiting Release', missing_awb: 'Missing Tracking Number',
        missing_delivery_order: 'Missing Delivery Note', all_passed: 'All Passed QC', partial_passed: 'Partially Passed QC',
        all_failed: 'Failed QC', not_recorded: 'Not Recorded', with_shipping: 'Tracking Number Available',
        without_shipping: 'No Tracking Number', active: 'Active', inactive: 'Inactive', expired: 'Expired',
        below: 'Below Threshold', near: 'Near Threshold', safe: 'Within Threshold', no_barrier: 'No Stock Threshold',
        store_stock: 'Store Stock', warehouse_stock: 'Warehouse Stock',
    },
};

const normalizeCase = (source: string, translated: string) => {
    if (source === source.toUpperCase()) return translated.toUpperCase();
    if (source[0] === source[0]?.toUpperCase()) return translated[0]?.toUpperCase() + translated.slice(1);

    return translated;
};

export const currentLocale = (): AppLocale => {
    if (typeof window === 'undefined') return 'id';

    return window.__VEESTORE_LOCALE__ === 'en' ? 'en' : 'id';
};

export const intlLocale = (): string => currentLocale() === 'en' ? 'en-US' : 'id-ID';

export const translate = (value: unknown): string => {
    if (typeof value !== 'string' || value.trim() === '') return String(value ?? '');

    const locale = currentLocale();
    const catalog = localeTerms[locale];
    const exact = catalog[value] ?? catalog[value.trim()];

    if (exact) return exact;

    const statusLabel = statusTranslations[locale][value.trim().toLocaleLowerCase('en-US')];
    if (statusLabel) return statusLabel;

    const terms = commonLocaleTerms[locale];
    const parts = value.match(/[\p{L}\p{N}]+|[^\p{L}\p{N}]+/gu) ?? [value];
    let changed = false;
    const translated = parts.map((part) => {
        if (!/[\p{L}]/u.test(part)) return part;

        const term = terms[part.toLocaleLowerCase('en-US')];
        if (!term) return part;

        changed = changed || term.toLocaleLowerCase('en-US') !== part.toLocaleLowerCase('en-US');

        return normalizeCase(part, term);
    }).join('');

    return changed ? translated : value;
};
