<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak Label {{ $requestNumber }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 24px; background: #f3f5f8; color: #16202a; font-family: Inter, Arial, sans-serif; }
        .toolbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; max-width: 980px; margin: 0 auto 18px; padding: 14px 18px; border: 1px solid #d6dde6; border-radius: 12px; background: #fff; }
        .toolbar h1 { margin: 0; font-size: 18px; }
        .toolbar p { margin: 4px 0 0; color: #5f6f80; font-size: 12px; }
        .toolbar button { padding: 9px 16px; border: 0; border-radius: 8px; background: #1f6feb; color: #fff; font-weight: 700; cursor: pointer; }
        .print-note { margin: 0 auto 18px; max-width: 980px; padding: 10px 14px; border-radius: 8px; background: #fff8e1; color: #7a5b00; font-size: 12px; }
        .sheet { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; max-width: 980px; margin: 0 auto; }
        .label { display: flex; gap: 12px; min-height: 176px; padding: 12px; border: 1px dashed #aab7c5; border-radius: 10px; background: #fff; page-break-inside: avoid; }
        .label-qr { flex: 0 0 116px; display: grid; place-items: center; }
        .label-qr svg { width: 116px; height: 116px; }
        .label-copy { min-width: 0; display: flex; flex-direction: column; gap: 3px; }
        .label-store { color: #5f6f80; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; }
        .label-product { margin: 2px 0; font-size: 14px; font-weight: 800; line-height: 1.25; }
        .label-code { color: #334155; font-size: 12px; font-weight: 700; }
        .label-meta { color: #5f6f80; font-size: 11px; line-height: 1.35; }
        .label-index { margin-top: auto; padding-top: 6px; border-top: 1px solid #e2e8f0; color: #0f172a; font-size: 12px; font-weight: 800; }
        .label-index span { color: #1f6feb; }
        @media print {
            body { padding: 0; background: #fff; }
            .toolbar, .print-note { display: none; }
            .sheet { max-width: none; }
            .label { border: 1px solid #999; }
        }
        @media (max-width: 720px) {
            .sheet { grid-template-columns: 1fr; }
            .toolbar { align-items: flex-start; flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div>
            <h1>Label Cetak {{ $requestNumber }}</h1>
            <p>
                {{ $labelRequest->store_name }} · Disetujui {{ $labelRequest->approved_by_name ?? '-' }}
                · Dicetak oleh {{ $printedBy }} · {{ $printedAt->format('d-m-Y H:i') }}
            </p>
        </div>
        <button type="button" onclick="window.print()">Cetak Sekarang</button>
    </div>

    <p class="print-note">
        Setiap kali tombol cetak dijalankan dari Master Data &amp; Report, nomor cetakan bertambah dan tercatat pada laporan cetak ulang label.
        Cetakan saat ini: ke-{{ $printCount }}.
    </p>

    <div class="sheet">
        @foreach ($items as $item)
            <article class="label">
                <div class="label-qr">{!! $item['qr_svg'] !!}</div>
                <div class="label-copy">
                    <span class="label-store">{{ $labelRequest->store_name }}</span>
                    <strong class="label-product">{{ $item['product_name'] ?: 'Produk' }}</strong>
                    <span class="label-code">{{ $item['product_code'] ?: '-' }}</span>
                    <span class="label-meta">Kode: {{ $item['barcode_value'] }}</span>
                    <span class="label-meta">Status item: {{ $item['item_status'] ?: '-' }}</span>
                    <span class="label-meta">Alasan: {{ $labelRequest->reason ?: '-' }}</span>
                    <span class="label-index">Cetakan ke-{{ $item['print_index'] }}</span>
                </div>
            </article>
        @endforeach
    </div>
</body>
</html>
