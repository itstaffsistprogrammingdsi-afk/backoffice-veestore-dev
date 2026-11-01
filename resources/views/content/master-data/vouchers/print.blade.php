<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cetak Voucher — {{ $voucher->name }}</title>
  <style>
    :root { --ink:#172033; --muted:#667085; --brand:#625bf6; --paper:#fff; --line:#dfe3ee; }
    * { box-sizing:border-box; }
    body { margin:0; background:#f4f6fb; color:var(--ink); font:14px/1.45 Arial, sans-serif; }
    .toolbar { max-width:1120px; margin:24px auto 12px; display:flex; justify-content:space-between; align-items:center; }
    .toolbar h1 { margin:0; font-size:20px; }.toolbar p { margin:3px 0 0; color:var(--muted); }
    button { border:0; background:var(--brand); color:#fff; padding:10px 16px; border-radius:7px; font-weight:700; cursor:pointer; }
    .sheet { max-width:1120px; margin:0 auto 30px; display:grid; grid-template-columns:repeat(2, 1fr); gap:16px; }
    .voucher { position:relative; overflow:hidden; min-height:250px; border:1px solid var(--line); border-radius:14px; background:var(--paper); box-shadow:0 5px 16px rgba(22,31,55,.06); }
    .voucher:before { content:""; position:absolute; top:-100px; right:-65px; width:230px; height:230px; background:radial-gradient(circle,rgba(98,91,246,.22),transparent 66%); }
    .head { position:relative; padding:18px 20px; color:#fff; background:linear-gradient(120deg,#4944d2,#756efb); }
    .brand { font-size:11px; font-weight:700; letter-spacing:1.2px; opacity:.84; }.name { margin-top:5px; max-width:86%; font-size:19px; font-weight:700; }
    .body { padding:18px 20px; }.description { min-height:34px; color:var(--muted); }
    .code-label { margin-top:16px; color:var(--muted); font-size:11px; font-weight:700; letter-spacing:.8px; }.code { margin-top:3px; font:700 24px/1.2 'Courier New',monospace; letter-spacing:1.1px; }
    .footer { display:flex; gap:12px; margin-top:18px; padding-top:12px; border-top:1px dashed var(--line); color:var(--muted); font-size:11px; }.footer div { flex:1; }.footer strong { display:block; margin-top:2px; color:var(--ink); font-size:12px; }
    @media print { @page { size:A4; margin:10mm; } body { background:#fff; } .toolbar { display:none; } .sheet { max-width:none; gap:8mm; } .voucher { break-inside:avoid; box-shadow:none; min-height:230px; } }
  </style>
</head>
<body>
  <div class="toolbar">
    <div><h1>Voucher siap dicetak</h1><p>{{ count($codes) }} unique code baru telah dibuat untuk {{ $voucher->name }}.</p></div>
    <button type="button" onclick="window.print()">Cetak sekarang</button>
  </div>
  <main class="sheet">
    @foreach($codes as $voucherCode)
    <article class="voucher">
      <header class="head"><div class="brand">VEESTORE VOUCHER</div><div class="name">{{ $voucher->name }}</div></header>
      <section class="body">
        <div class="description">{{ $voucher->description ?: 'Gunakan kode unik ini saat transaksi.' }}</div>
        <div class="code-label">KODE VOUCHER</div><div class="code">{{ $voucherCode->code }}</div>
        <div class="footer">
          <div>Berlaku dari<strong>{{ optional($voucher->valid_from)->format('d M Y H:i') ?: '-' }}</strong></div>
          <div>Berakhir pada<strong>{{ optional($voucherCode->expires_at)->format('d M Y H:i') ?: '-' }}</strong></div>
        </div>
      </section>
    </article>
    @endforeach
  </main>
  <script>window.addEventListener('load', function () { window.print(); });</script>
</body>
</html>
