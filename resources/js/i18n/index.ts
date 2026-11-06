import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import dictionary from './en.json';

type Locale = 'id' | 'en';

export const currentLocale = ref<Locale>('id');

const idToEnglish = new Map<string, string>();
const exactOnlySources = new Set<string>();
const databaseStatusLabels: Record<string, { id: string; en: string }> = {
  active: { id: 'Aktif', en: 'Active' },
  all: { id: 'Semua', en: 'All' },
  all_failed: { id: 'Semua Gagal', en: 'All Failed' },
  all_passed: { id: 'Semua Lulus', en: 'All Passed' },
  approved: { id: 'Disetujui', en: 'Approved' },
  bad: { id: 'Buruk', en: 'Bad' },
  below: { id: 'Di Bawah Ambang', en: 'Below Threshold' },
  canceled: { id: 'Dibatalkan', en: 'Canceled' },
  cancelled: { id: 'Dibatalkan', en: 'Cancelled' },
  capture: { id: 'Capture', en: 'Capture' },
  close: { id: 'Ditutup', en: 'Closed' },
  completed: { id: 'Selesai', en: 'Completed' },
  consumed: { id: 'Terpakai', en: 'Consumed' },
  critical: { id: 'Kritis', en: 'Critical' },
  delivered: { id: 'Terkirim', en: 'Delivered' },
  draft: { id: 'Draf', en: 'Draft' },
  expired: { id: 'Kedaluwarsa', en: 'Expired' },
  extra: { id: 'Tambahan', en: 'Extra' },
  failed: { id: 'Gagal', en: 'Failed' },
  good: { id: 'Baik', en: 'Good' },
  good_after_repair: { id: 'Baik Setelah Perbaikan', en: 'Good After Repair' },
  in_progress: { id: 'Sedang Berjalan', en: 'In Progress' },
  in_transit: { id: 'Dalam Perjalanan', en: 'In Transit' },
  inactive: { id: 'Tidak Aktif', en: 'Inactive' },
  match: { id: 'Cocok', en: 'Match' },
  missing: { id: 'Hilang', en: 'Missing' },
  missing_awb: { id: 'AWB Hilang', en: 'Missing AWB' },
  missing_delivery_order: { id: 'Surat Jalan Hilang', en: 'Missing Delivery Order' },
  missing_shipping: { id: 'Pengiriman Hilang', en: 'Missing Shipping' },
  mismatch: { id: 'Tidak Cocok', en: 'Mismatch' },
  near: { id: 'Mendekati Ambang', en: 'Near Threshold' },
  no_barrier: { id: 'Tanpa Barrier', en: 'No Barrier' },
  no_expiry: { id: 'Tanpa Kedaluwarsa', en: 'No Expiry' },
  non_active: { id: 'Tidak Aktif', en: 'Non-active' },
  not_recorded: { id: 'Belum Tercatat', en: 'Not Recorded' },
  not_paid: { id: 'Belum Lunas', en: 'Not Paid' },
  on_hold: { id: 'Ditahan', en: 'On Hold' },
  open: { id: 'Terbuka', en: 'Open' },
  other: { id: 'Lainnya', en: 'Other' },
  packing: { id: 'Dikemas', en: 'Packing' },
  packing_completed: { id: 'Selesai Dikemas', en: 'Packing Completed' },
  paid: { id: 'Lunas', en: 'Paid' },
  partial: { id: 'Sebagian', en: 'Partial' },
  partial_approved: { id: 'Disetujui Sebagian', en: 'Partially Approved' },
  partial_passed: { id: 'Lulus Sebagian', en: 'Partially Passed' },
  partial_received: { id: 'Diterima Sebagian', en: 'Partially Received' },
  pending: { id: 'Menunggu', en: 'Pending' },
  pending_approval: { id: 'Menunggu Persetujuan', en: 'Pending Approval' },
  processing: { id: 'Diproses', en: 'Processing' },
  processed: { id: 'Diproses', en: 'Processed' },
  printed: { id: 'Dicetak', en: 'Printed' },
  qc_pending: { id: 'QC Menunggu', en: 'QC Pending' },
  queued: { id: 'Dalam Antrean', en: 'Queued' },
  recorded: { id: 'Tercatat', en: 'Recorded' },
  received: { id: 'Diterima', en: 'Received' },
  rejected: { id: 'Ditolak', en: 'Rejected' },
  released: { id: 'Dirilis', en: 'Released' },
  refunded: { id: 'Dana Dikembalikan', en: 'Refunded' },
  requested: { id: 'Diajukan', en: 'Requested' },
  reserved: { id: 'Dicadangkan', en: 'Reserved' },
  release_pending: { id: 'Menunggu Rilis', en: 'Release Pending' },
  ready_to_ship: { id: 'Siap Dikirim', en: 'Ready to Ship' },
  safe: { id: 'Aman', en: 'Safe' },
  settlement: { id: 'Penyelesaian', en: 'Settlement' },
  shipped: { id: 'Dikirim', en: 'Shipped' },
  store_display: { id: 'Display Toko', en: 'Store Display' },
  store_stock: { id: 'Stok Toko', en: 'Store Stock' },
  success: { id: 'Berhasil', en: 'Success' },
  unmatched: { id: 'Tidak Cocok', en: 'Unmatched' },
  unused: { id: 'Belum Digunakan', en: 'Unused' },
  used: { id: 'Digunakan', en: 'Used' },
  warning: { id: 'Peringatan', en: 'Warning' },
  voided: { id: 'Dibatalkan', en: 'Voided' },
  warehouse_stock: { id: 'Stok Gudang', en: 'Warehouse Stock' }
};
const tableLabelTranslations: Array<{ id: string; en: string }> = [
  { id: 'E-commerce', en: 'Ecommerce' },
  { id: 'Pemusnahan', en: 'Destroy' },
  { id: 'Transfer', en: 'Transfer' },
  { id: 'Retur ke Supplier', en: 'Supplier Return' },
  { id: 'Toko', en: 'Store' },
  { id: 'Keranjang Toko', en: 'Store Cart' },
  { id: 'Retur Toko', en: 'Store Return' },
  { id: 'Lainnya', en: 'Other' },
  { id: 'Pemasok', en: 'Supplier' },
  { id: 'Gudang', en: 'Warehouse' },
  { id: 'Ruangan', en: 'Room' },
  { id: 'Display Toko', en: 'Store Display' },
  { id: 'Rak', en: 'Rack' },
  { id: 'Menunggu penerimaan', en: 'Pending receipt' },
  { id: 'Menunggu pembayaran', en: 'Pending payment' },
  { id: 'Sudah dibayar', en: 'Paid' },
  { id: 'Sedang dikemas', en: 'Being packed' },
  { id: 'Selesai diterima', en: 'Received' },
  { id: 'Diterima gudang', en: 'Received at warehouse' },
  { id: 'Menunggu persetujuan', en: 'Pending approval' },
  { id: 'Menunggu approval', en: 'Pending approval' },
  { id: 'Semua status', en: 'All statuses' },
  { id: 'Disetujui sebagian', en: 'Partially approved' },
  { id: 'Packing selesai', en: 'Packing completed' },
  { id: 'Lolos Sebagian', en: 'Partially Passed' },
  { id: 'Semua Lolos QC', en: 'All QC Passed' },
  { id: 'Semua Gagal QC', en: 'All QC Failed' },
  { id: 'QC Sebagian', en: 'Partial QC' },
  { id: 'Ditolak QC', en: 'QC Rejected' },
  { id: 'Belum tercatat', en: 'Not recorded' },
  { id: 'Sudah tercatat', en: 'Recorded' },
  { id: 'Tercatat sebagian', en: 'Partially recorded' },
  { id: 'Tanpa barrier', en: 'No barrier' },
  { id: 'Di bawah barrier', en: 'Below barrier' },
  { id: 'Mendekati barrier', en: 'Near barrier' },
  { id: 'Di bawah threshold', en: 'Below threshold' },
  { id: 'Tanpa expiry', en: 'No expiry' },
  { id: 'Tidak ada stok', en: 'No stock' },
  { id: 'Lambat Bergerak', en: 'Slow Moving' },
  { id: 'Pergerakan Cepat', en: 'Fast Moving' },
  { id: 'Normal', en: 'Normal' },
  { id: 'Tanpa kurir', en: 'No courier' },
  { id: 'Tanpa invoice/DO', en: 'No invoice/delivery note' },
  { id: 'Tidak ada invoice/DO', en: 'No invoice/delivery note' },
  { id: 'QC selesai', en: 'QC completed' },
  { id: 'Belum QC', en: 'QC pending' },
  { id: 'Resi tersedia', en: 'Tracking number available' },
  { id: 'Stok toko: Belum tercatat', en: 'Store stock: Not recorded' },
  { id: 'Stok toko: Sudah tercatat', en: 'Store stock: Recorded' },
  { id: 'Stok toko: Tercatat sebagian', en: 'Store stock: Partially recorded' },
  { id: 'Diperiksa', en: 'Checked' },
  { id: 'Sumber', en: 'Source' },
  { id: 'Tujuan', en: 'Destination' },
  { id: 'Toko tujuan', en: 'Destination store' },
  { id: 'Kurir', en: 'Courier' },
  { id: 'QC oleh', en: 'QC by' },
  { id: 'Outbound oleh', en: 'Outbound by' },
  { id: 'Shipment', en: 'Shipment' },
  { id: 'Qty header', en: 'Header quantity' },
  { id: 'Jumlah cetak', en: 'Print count' },
  { id: 'Metode nota', en: 'Note method' },
  { id: 'Serial RFID', en: 'RFID serial' },
  { id: 'Releaser', en: 'Releaser' },
  { id: 'Via Email', en: 'Via email' },
  { id: 'Langsung', en: 'Direct' },
  { id: 'Dapatkan', en: 'Earn' },
  { id: 'Tukarkan', en: 'Redeem' },
  { id: 'Penyesuaian', en: 'Adjustment' },
  { id: 'Pendaftaran Referral', en: 'Referral Registration' },
  { id: 'Transaksi Pertama Referral', en: 'Referral First Transaction' },
  { id: 'Pembalikan Void', en: 'Void Reversal' },
  { id: 'Barang Masuk Gudang', en: 'Inbound Warehouse' },
  { id: 'Di Gudang', en: 'In Warehouse' },
  { id: 'Barang Keluar Gudang', en: 'Outbound Warehouse' },
  { id: 'Barang Masuk Toko', en: 'Inbound Store' },
  { id: 'Supplier', en: 'Supplier' },
  { id: 'Retur Store', en: 'Store Return' },
  { id: 'Transfer Warehouse', en: 'Warehouse Transfer' },
  { id: 'Pesanan Toko', en: 'Store Order' },
  { id: 'Reservasi', en: 'Reservation' },
  { id: 'Terjual', en: 'Sold' },
  { id: 'Stok Gudang', en: 'Warehouse Stock' },
  { id: 'Stok Toko', en: 'Store Stock' },
  { id: 'Sebelumnya', en: 'Previous' },
  { id: 'Berikutnya', en: 'Next' }
];
const tableTranslationAliases: Array<{ source: string; id: string; en: string }> = [
  { source: 'without Couriers', id: 'Tanpa kurir', en: 'No courier' },
  { source: 'not yet QC', id: 'Belum QC', en: 'QC pending' },
  { source: 'QC Completed', id: 'QC selesai', en: 'QC completed' },
  { source: 'Sudah Recorded', id: 'Sudah tercatat', en: 'Recorded' },
  { source: 'Store stock: Not Recorded', id: 'Stok toko: Belum tercatat', en: 'Store stock: Not recorded' },
  { source: 'Store stock: Sudah Recorded', id: 'Stok toko: Sudah tercatat', en: 'Store stock: Recorded' },
  { source: 'Stock Warehouse', id: 'Stok Gudang', en: 'Warehouse Stock' },
  { source: 'Stock Store', id: 'Stok Toko', en: 'Store Stock' },
  { source: 'Display Store', id: 'Display Toko', en: 'Store Display' },
  { source: 'Expire', id: 'Kedaluwarsa', en: 'Expired' },
  { source: 'earn', id: 'Dapatkan', en: 'Earn' },
  { source: 'redeem', id: 'Tukarkan', en: 'Redeem' },
  { source: 'expire', id: 'Kedaluwarsa', en: 'Expire' },
  { source: 'adjust', id: 'Penyesuaian', en: 'Adjustment' },
  { source: 'referral_register', id: 'Pendaftaran Referral', en: 'Referral Registration' },
  { source: 'referral_first_tx', id: 'Transaksi Pertama Referral', en: 'Referral First Transaction' },
  { source: 'void_reversal', id: 'Pembalikan Void', en: 'Void Reversal' }
];
const englishToIndonesian = new Map<string, string>(
  Object.entries({
    Amount: 'Jumlah',
    Approval: 'Persetujuan',
    Approved: 'Disetujui',
    'Audit Center': 'Pusat Audit',
    Available: 'Tersedia',
    Balance: 'Saldo',
    Brand: 'Merek',
    Channel: 'Kanal',
    'Command Center': 'Pusat Komando',
    Customer: 'Customer',
    'Customer Demographics': 'Demografi Customer',
    'Customer Loyalty Point Reward': 'Hadiah Poin Loyalitas Customer',
    'Customer Loyalty Points Report': 'Laporan Poin Loyalitas Customer',
    'Customer Membership': 'Keanggotaan Customer',
    'Customer Point': 'Poin Customer',
    'Customer Product Preference': 'Preferensi Produk Customer',
    'Customer Profile': 'Profil Customer',
    'Customer Profiles': 'Profil Customer',
    'Customers & Visits': 'Customer dan Kunjungan',
    Detail: 'Detail',
    'Download PNG': 'Unduh PNG',
    'Download SVG': 'Unduh SVG',
    Edit: 'Ubah',
    Ecommerce: 'E-commerce',
    'Employee Shift Attendance': 'Absensi Shift Karyawan',
    Expired: 'Kedaluwarsa',
    'Fast Moving': 'Pergerakan Cepat',
    Gender: 'Jenis Kelamin',
    Good: 'Baik',
    'History Customer': 'Riwayat Customer',
    'History Customer Ecommerce': 'Riwayat Customer E-commerce',
    Inbound: 'Barang Masuk',
    Item: 'Barang',
    Management: 'Manajemen',
    'Master Data': 'Data Master',
    Membership: 'Keanggotaan',
    'Membership Tier': 'Tingkat Keanggotaan',
    Monitoring: 'Monitoring',
    Name: 'Nama',
    Notes: 'Catatan',
    Order: 'Pesanan',
    'Order ID': 'ID Pesanan',
    'Order number...': 'Nomor pesanan...',
    Outbound: 'Barang Keluar',
    Parent: 'Induk',
    Pending: 'Menunggu',
    Percentage: 'Persentase',
    'Physical Stocks': 'Stok Fisik',
    'Print Index': 'Indeks Cetak',
    'Product Aging': 'Umur Produk',
    'Product Code': 'Kode Produk',
    'Product Discounts': 'Diskon Produk',
    'Product Name': 'Nama Produk',
    'Product Slowmoving': 'Produk Lambat Bergerak',
    'Promo Setup': 'Pengaturan Promo',
    Promos: 'Promo',
    Qty: 'Jumlah',
    Quantity: 'Jumlah',
    Receive: 'Terima',
    Received: 'Diterima',
    Reject: 'Tolak',
    Rejected: 'Ditolak',
    'Report Voucher': 'Laporan Voucher',
    Reports: 'Laporan',
    Request: 'Permintaan',
    'Request Label': 'Permintaan Label',
    'Request Voucher': 'Permintaan Voucher',
    Reserved: 'Dicadangkan',
    Return: 'Retur',
    SKU: 'SKU',
    'Slow Deadstock': 'Stok Mati Lambat',
    'Slow Moving': 'Lambat Bergerak',
    Source: 'Sumber',
    Status: 'Status',
    Store: 'Toko',
    Stores: 'Toko',
    Supplier: 'Pemasok',
    'Supplier Insight': 'Insight Pemasok',
    'Supplier Products': 'Produk Pemasok',
    Threshold: 'Ambang Batas',
    Tier: 'Tingkat',
    Total: 'Total',
    'Total Customer': 'Total Customer',
    'Total Inbound': 'Total Masuk',
    'Total Outbound': 'Total Barang Keluar',
    'Total Spending': 'Total Belanja',
    Type: 'Tipe',
    Updated: 'Diperbarui',
    'User Monitoring': 'Monitoring Pengguna',
    Value: 'Nilai',
    'Use your email or username and account password.': 'Gunakan email atau username dan password akun Anda.',
    'Voucher Usage': 'Penggunaan Voucher',
    Vouchers: 'Voucher',
    WA: 'WA',
    Warehouse: 'Gudang',
    'Warehouse Inbound': 'Barang Masuk Gudang',
    'Warehouse Outbound': 'Barang Keluar Gudang',
    'Warehouse Stock Barriers': 'Barrier Stok Gudang'
  })
);
const attributeNames = ['placeholder', 'title', 'aria-label', 'alt', 'data-label'];
const skippedTags = new Set(['SCRIPT', 'STYLE', 'CODE', 'PRE', 'TEXTAREA']);
const observerConfig: MutationObserverInit = {
  childList: true,
  subtree: true,
  characterData: true,
  attributes: true,
  attributeFilter: attributeNames
};

const normalize = (value: string) => value.replace(/\s+/g, ' ').trim();

for (const [source, translation] of Object.entries(dictionary as Record<string, string>)) {
  if (source === translation) continue;

  idToEnglish.set(normalize(source), translation);

  if (!englishToIndonesian.has(normalize(translation))) {
    englishToIndonesian.set(normalize(translation), source);
  }
}

for (const [raw, labels] of Object.entries(databaseStatusLabels)) {
  exactOnlySources.add(normalize(raw));
  idToEnglish.set(normalize(raw), labels.en);
  idToEnglish.set(normalize(labels.id), labels.en);
  englishToIndonesian.set(normalize(raw), labels.id);
  englishToIndonesian.set(normalize(labels.en), labels.id);
}

for (const labels of tableLabelTranslations) {
  idToEnglish.set(normalize(labels.id), labels.en);
  idToEnglish.set(normalize(labels.en), labels.en);
  englishToIndonesian.set(normalize(labels.id), labels.id);
  englishToIndonesian.set(normalize(labels.en), labels.id);
}

for (const alias of tableTranslationAliases) {
  idToEnglish.set(normalize(alias.source), alias.en);
  englishToIndonesian.set(normalize(alias.source), alias.id);
}

let observer: MutationObserver | null = null;
let scheduled = false;
const partialEntries = new Map<Locale, Array<[string, string]>>();
const translationCache = new Map<string, string | null>();

const shouldSkip = (element: Element | null) => {
  if (!element) return false;
  if (skippedTags.has(element.tagName)) return true;

  return Boolean(element.closest('[data-no-translate], .notranslate, [translate="no"]'));
};

const escapeRegExp = (value: string) => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

const mapFor = (target: Locale) => (target === 'en' ? idToEnglish : englishToIndonesian);

const entriesFor = (target: Locale) => {
  const cached = partialEntries.get(target);

  if (cached) return cached;

  const entries = [...mapFor(target).entries()]
    .filter(([source, replacement]) => source.length > 1 && source !== replacement && !exactOnlySources.has(source))
    .sort(([left], [right]) => right.length - left.length);

  partialEntries.set(target, entries);

  return entries;
};

const listingUnits: Record<string, { id: string; en: string }> = {
  baris: { id: 'baris', en: 'rows' },
  customer: { id: 'customer', en: 'customers' },
  data: { id: 'data', en: 'records' },
  export: { id: 'export', en: 'exports' },
  order: { id: 'order', en: 'orders' },
  retur: { id: 'retur', en: 'returns' },
  transaksi: { id: 'transaksi', en: 'transactions' },
  user: { id: 'user', en: 'users' }
};

const translateListing = (value: string, target: Locale) => {
  if (target === 'en') {
    const match = value.match(
      /^Menampilkan\s+(.+?)\s*-\s*(.+?)\s+dari\s+(.+?)\s+(baris|customer|data|export|order|retur|transaksi|user)$/i
    );

    if (!match) return undefined;

    return `Showing ${match[1]} - ${match[2]} of ${match[3]} ${listingUnits[match[4].toLowerCase()].en}`;
  }

  const match = value.match(
    /^Showing\s+(.+?)\s*-\s*(.+?)\s+of\s+(.+?)\s+(rows|customers|records|exports|orders|returns|transactions|users)$/i
  );

  if (!match) return undefined;

  const unit = Object.values(listingUnits).find(entry => entry.en === match[4].toLowerCase());

  return unit ? `Menampilkan ${match[1]} - ${match[2]} dari ${match[3]} ${unit.id}` : undefined;
};

const translateValue = (value: string, target: Locale) => {
  const normalized = normalize(value);

  if (!normalized) return undefined;

  const cacheKey = `${target}:${normalized}`;

  if (translationCache.has(cacheKey)) {
    return translationCache.get(cacheKey) ?? undefined;
  }

  const map = mapFor(target);
  const exact = map.get(normalized);

  if (exact) {
    translationCache.set(cacheKey, exact);

    return exact;
  }

  const listing = translateListing(normalized, target);

  if (listing) {
    translationCache.set(cacheKey, listing);

    return listing;
  }

  let translated = normalized;

  for (const [source, replacement] of entriesFor(target)) {
    const lowerTranslated = translated.toLocaleLowerCase();
    const lowerSource = source.toLocaleLowerCase();

    if (source === normalized || !lowerTranslated.includes(lowerSource)) continue;

    const lowerReplacement = replacement.toLocaleLowerCase();

    if (lowerReplacement.includes(lowerSource) && lowerTranslated.includes(lowerReplacement)) continue;

    const pattern = new RegExp(`(^|[^A-Za-z0-9À-ÿ])${escapeRegExp(source)}(?=$|[^A-Za-z0-9À-ÿ])`, 'gi');

    translated = translated.replace(pattern, (_, prefix: string) => `${prefix}${replacement}`);
  }

  const result = translated === normalized ? null : translated;
  translationCache.set(cacheKey, result);

  return result ?? undefined;
};

const translateText = (node: Text, target: Locale) => {
  if (shouldSkip(node.parentElement)) return;

  const raw = node.nodeValue ?? '';
  const content = raw.trim();
  const translated = translateValue(content, target);

  if (!translated) return;

  const leading = raw.match(/^\s*/)?.[0] ?? '';
  const trailing = raw.match(/\s*$/)?.[0] ?? '';

  node.nodeValue = `${leading}${translated}${trailing}`;
};

const translateAttributes = (element: Element, target: Locale) => {
  if (shouldSkip(element)) return;

  for (const name of attributeNames) {
    const value = element.getAttribute(name);

    if (!value) continue;

    const translated = translateValue(value, target);

    if (translated && translated !== value) {
      element.setAttribute(name, translated);
    }
  }
};

const translateRoot = (root: Node, target: Locale) => {
  const element = root instanceof Element ? root : null;

  if (element) translateAttributes(element, target);

  const walker = document.createTreeWalker(root, NodeFilter.SHOW_ELEMENT | NodeFilter.SHOW_TEXT, {
    acceptNode(node) {
      if (node.nodeType !== Node.ELEMENT_NODE) return NodeFilter.FILTER_ACCEPT;

      const current = node as Element;

      if (skippedTags.has(current.tagName) || shouldSkip(current)) {
        return NodeFilter.FILTER_REJECT;
      }

      return NodeFilter.FILTER_ACCEPT;
    }
  });

  let current = walker.nextNode();

  while (current) {
    if (current.nodeType === Node.ELEMENT_NODE) {
      translateAttributes(current as Element, target);
    } else {
      translateText(current as Text, target);
    }

    current = walker.nextNode();
  }
};

const translateTitle = (target: Locale) => {
  const match = document.title.match(/^(.*?)(\s+-\s+VeeStore)$/);

  if (!match) return;

  const translated = translateValue(match[1], target);

  if (translated) {
    document.title = `${translated}${match[2]}`;
  }
};

const translateDocument = () => {
  observer?.disconnect();
  translateRoot(document.body, currentLocale.value);
  translateTitle(currentLocale.value);
  observer?.observe(document.body, observerConfig);
};

const scheduleTranslation = () => {
  if (scheduled) return;

  scheduled = true;

  window.requestAnimationFrame(() => {
    scheduled = false;
    translateDocument();
  });
};

const startObserver = () => {
  if (observer || typeof window === 'undefined') return;

  observer = new MutationObserver(scheduleTranslation);
  observer.observe(document.body, observerConfig);
};

export const setLocale = (next: Locale) => {
  currentLocale.value = next;
  document.documentElement.lang = next;
  translateDocument();
};

export const switchLocale = (next: string) => {
  if (next !== 'id' && next !== 'en') return;

  const target = next as Locale;
  const previous = currentLocale.value;

  if (target === previous) return;

  setLocale(target);

  router.post(
    `/language/${target}`,
    {},
    {
      only: ['locale'],
      preserveScroll: true,
      preserveState: true,
      onError: () => setLocale(previous),
      onCancel: () => setLocale(previous)
    }
  );
};

export const installLocaleTranslator = (initialLocale?: string) => {
  if (typeof window === 'undefined') return;

  startObserver();

  if (initialLocale === 'id' || initialLocale === 'en') {
    setLocale(initialLocale);
  }

  window.requestAnimationFrame(translateDocument);

  router.on('navigate', event => {
    const next = (event.detail.page.props as { locale?: string }).locale;

    if (next === 'id' || next === 'en') {
      setLocale(next);
    }

    window.requestAnimationFrame(translateDocument);
  });
};
