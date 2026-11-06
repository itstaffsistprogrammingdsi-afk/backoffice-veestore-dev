import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import dictionary from './en.json';
import { createTranslator, type Locale, type TemplateMatch, type TranslationEntry } from './translator';

export type { Locale } from './translator';

export const currentLocale = ref<Locale>('id');

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
  { id: 'Belum tercatat', en: 'Not Recorded' },
  { id: 'Sudah tercatat', en: 'Recorded' },
  { id: 'Tercatat sebagian', en: 'Partially Recorded' },
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

const englishToIndonesian: Record<string, string> = {
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
};

const buildEntries = (): TranslationEntry[] => {
  const entries: TranslationEntry[] = [];

  for (const [source, translation] of Object.entries(dictionary as Record<string, string>)) {
    entries.push({ source, translation });
  }

  for (const [key, labels] of Object.entries(databaseStatusLabels)) {
    entries.push({ source: key, translation: labels.en, reverse: labels.id });
    entries.push({ source: labels.id, translation: labels.en, reverse: labels.id });
  }

  for (const labels of tableLabelTranslations) {
    entries.push({ source: labels.id, translation: labels.en, reverse: labels.id });
  }

  for (const alias of tableTranslationAliases) {
    entries.push({ source: alias.source, translation: alias.en, reverse: alias.id });
  }

  return entries;
};

const translator = createTranslator(
  buildEntries(),
  Object.entries(englishToIndonesian).map(([source, translation]) => ({ source, translation })),
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

const shouldSkip = (element: Element | null) => {
  if (!element) return false;
  if (skippedTags.has(element.tagName)) return true;

  return Boolean(element.closest('[data-no-translate], .notranslate, [translate="no"]'));
};

let observer: MutationObserver | null = null;
let scheduled = false;

const translateAttributes = (element: Element, target: Locale) => {
  if (shouldSkip(element)) return;

  for (const name of attributeNames) {
    const value = element.getAttribute(name);

    if (!value) continue;

    const translated = translator.translate(value, target);

    if (translated && translated !== value) {
      element.setAttribute(name, translated);
    }
  }
};

const writeSegment = (
  group: Text[],
  segment: string,
  anchor: Text | null,
  fallbackParent: Node | null,
) => {
  if (group.length > 0) {
    group[0].nodeValue = segment;

    for (let index = 1; index < group.length; index += 1) {
      group[index].nodeValue = '';
    }

    return;
  }

  if (!segment) return;

  const parent = anchor?.parentNode ?? fallbackParent;

  if (!parent) return;

  parent.insertBefore(document.createTextNode(segment), anchor);
};

const alignCaptures = (nodes: Text[], captures: string[]) => {
  const indexes: number[] = [];
  let cursor = 0;

  for (const capture of captures) {
    let found = -1;

    for (let index = cursor; index < nodes.length; index += 1) {
      if (normalize(nodes[index].nodeValue ?? '') === capture) {
        found = index;
        break;
      }
    }

    if (found === -1) return null;

    indexes.push(found);
    cursor = found + 1;
  }

  return indexes;
};

const applyMatch = (nodes: Text[], dynamicIndexes: number[], match: TemplateMatch) => {
  const groups: Text[][] = [];
  const fallbackParent = nodes[0]?.parentNode ?? null;
  let start = 0;

  for (const index of dynamicIndexes) {
    groups.push(nodes.slice(start, index));
    start = index + 1;
  }

  groups.push(nodes.slice(start));

  for (let index = 0; index < groups.length; index += 1) {
    const anchor = dynamicIndexes[index] !== undefined
      ? nodes[dynamicIndexes[index]]
      : null;

    writeSegment(groups[index], match.segments[index] ?? '', anchor, fallbackParent);
  }

  for (let index = 0; index < dynamicIndexes.length; index += 1) {
    if (!match.translateFlags[index]) continue;

    const node = nodes[dynamicIndexes[index]];
    const value = match.values[index];

    if (!node || normalize(node.nodeValue ?? '') === value) continue;

    const raw = node.nodeValue ?? '';
    const leading = raw.match(/^\s*/)?.[0] ?? '';
    const trailing = raw.match(/\s*$/)?.[0] ?? '';

    node.nodeValue = `${leading}${value}${trailing}`;
  }
};

const translateTextNode = (node: Text, target: Locale) => {
  const raw = node.nodeValue ?? '';
  const content = raw.trim();
  const translated = translator.translate(content, target);

  if (!translated || translated === content) return;

  const leading = raw.match(/^\s*/)?.[0] ?? '';
  const trailing = raw.match(/\s*$/)?.[0] ?? '';

  node.nodeValue = `${leading}${translated}${trailing}`;
};

const translateRun = (nodes: Text[], target: Locale) => {
  if (nodes.length === 1) {
    translateTextNode(nodes[0], target);

    return;
  }

  const raw = nodes.map((node) => node.nodeValue ?? '').join('');
  const content = normalize(raw);

  if (!content) return;

  const match = translator.match(content, target);

  if (match) {
    const dynamicIndexes = alignCaptures(nodes, match.captures);

    if (dynamicIndexes) {
      applyMatch(nodes, dynamicIndexes, match);

      return;
    }
  }

  for (const node of nodes) {
    translateTextNode(node, target);
  }
};

const translateElement = (element: Element, target: Locale) => {
  if (shouldSkip(element)) return;

  translateAttributes(element, target);

  let run: Text[] = [];

  const flush = () => {
    if (run.length > 0) translateRun(run, target);

    run = [];
  };

  for (const child of Array.from(element.childNodes)) {
    if (child.nodeType === Node.TEXT_NODE) {
      run.push(child as Text);

      continue;
    }

    flush();

    if (child.nodeType === Node.ELEMENT_NODE) {
      const childElement = child as Element;

      if (!skippedTags.has(childElement.tagName)) {
        translateElement(childElement, target);
      }
    }
  }

  flush();
};

const translateTitle = (target: Locale) => {
  const match = document.title.match(/^(.*?)(\s+-\s+VeeStore)$/);

  if (!match) return;

  const translated = translator.translate(match[1], target);

  if (translated) {
    document.title = `${translated}${match[2]}`;
  }
};

const translateDocument = () => {
  observer?.disconnect();
  translateElement(document.body, currentLocale.value);
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
