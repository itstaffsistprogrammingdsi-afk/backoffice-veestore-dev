@extends('layouts/contentNavbarLayout')

@section('title', 'Pengaturan Lokasi Warehouse')

@push('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tabulator-tables@6.3.0/dist/css/tabulator_bootstrap5.min.css" />
<style>
  .select2-container{width:100% !important}
  #locations-table .tabulator-header .tabulator-col .tabulator-col-title{font-weight:600}
  #locations-table .tabulator-cell .btn-group{white-space:nowrap}
  .badge-type{font-weight:600}
  .card-header .btn{box-shadow:none}
  #locations-table .tabulator-row{transition:background-color .15s ease, filter .15s ease, border-color .15s ease}
  #locations-table .tabulator-row:hover{filter:brightness(0.98)}
  #locations-table .tabulator-row.group-row{
    --h: 210;
    background-color: hsla(var(--h), 85%, 96%, 1) !important;
    border-left: 6px solid hsl(var(--h), 70%, 55%) !important;
  }
  .group-dot{
    display:none !important;
  }

  /*
   * Jangan paksa seluruh kolom masuk ke lebar layar.
   * Jika ruang tidak cukup, tabel menggunakan scroll horizontal.
   */
  #locations-table{
    width:100%;
  }

  #locations-table .tabulator-tableholder{
    overflow-x:auto !important;
  }

  #locations-table .tabulator-table{
    min-width:1120px;
  }

  #locations-table .tabulator-cell{
    vertical-align:middle;
  }

  /*
   * Nama lokasi dapat turun baris sehingga nama Warehouse tidak terpotong.
   */
  #locations-table .location-name-cell{
    display:flex;
    align-items:flex-start;
    width:100%;
    min-width:0;
    white-space:normal;
    overflow:visible;
    line-height:1.35;
  }

  #locations-table .location-name-text{
    display:block;
    min-width:0;
    white-space:normal;
    overflow-wrap:anywhere;
    word-break:break-word;
  }

  /*
   * Badge Warehouse boleh membesar dan turun baris.
   */
  #locations-table .warehouse-name-badge{
    display:inline-block;
    max-width:100%;
    white-space:normal;
    overflow-wrap:anywhere;
    word-break:break-word;
    line-height:1.25;
    text-align:left;
  }

  #locations-table .tabulator-col[tabulator-field="name"] .tabulator-col-title,
  #locations-table .tabulator-col[tabulator-field="warehouse_name"] .tabulator-col-title{
    overflow:visible;
    text-overflow:clip;
    white-space:nowrap;
  }

  /*
   * Tombol buka/tutup dibuat lebih besar dan jelas untuk pengguna awam.
   * - "Buka" = lihat isi lokasi
   * - "Tutup" = sembunyikan isi lokasi
   */
  #locations-table .tabulator-data-tree-control{
    display:inline-flex !important;
    align-items:center;
    justify-content:center;
    min-width:78px !important;
    height:34px !important;
    margin-right:8px !important;
    padding:0 !important;
    border:0 !important;
    background:transparent !important;
    box-shadow:none !important;
    vertical-align:middle;
    cursor:pointer;
    overflow:visible !important;
  }

  #locations-table .tree-chevron{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:.35rem;
    min-width:74px;
    height:32px;
    padding:0 .7rem;
    border-radius:999px;
    font-size:14px;
    font-weight:700;
    line-height:1;
    border:1px solid transparent;
    transition:
      color .15s ease,
      background-color .15s ease,
      border-color .15s ease,
      transform .15s ease,
      box-shadow .15s ease;
    white-space:nowrap;
  }

  #locations-table .tree-chevron i{
    font-size:22px;
    line-height:1;
  }

  #locations-table .tree-chevron-label{
    font-size:12px;
    letter-spacing:.1px;
  }

  #locations-table .tree-chevron-expand{
    color:#0b7a38;
    background:rgba(40,199,111,.12);
    border-color:rgba(40,199,111,.34);
  }

  #locations-table .tree-chevron-collapse{
    color:#4f51d7;
    background:rgba(105,108,255,.12);
    border-color:rgba(105,108,255,.30);
  }

  #locations-table .tabulator-data-tree-control:hover .tree-chevron{
    transform:translateY(-1px);
    box-shadow:0 4px 10px rgba(0,0,0,.08);
  }

  #locations-table .tabulator-data-tree-control:focus-visible{
    outline:2px solid rgba(105,108,255,.45) !important;
    outline-offset:2px;
    border-radius:999px;
  }

  /*
   * Garis hierarchy dibuat sedikit lebih jelas.
   */
  #locations-table .tabulator-data-tree-branch{
    border-left:2px solid rgba(105,108,255,.22) !important;
    border-bottom:2px solid rgba(105,108,255,.22) !important;
  }

  @media (max-width: 767.98px){
    #locations-table .tabulator-table{
      min-width:1040px;
    }

    #locations-table .tabulator-data-tree-control{
      min-width:66px !important;
      height:30px !important;
      margin-right:6px !important;
    }

    #locations-table .tree-chevron{
      min-width:62px;
      height:28px;
      padding:0 .55rem;
      gap:.25rem;
    }

    #locations-table .tree-chevron i{
      font-size:20px;
    }

    #locations-table .tree-chevron-label{
      font-size:11px;
    }
  }

  .location-help-card {
    background: linear-gradient(135deg, rgba(3,195,236,.10), rgba(105,108,255,.08));
  }

  .location-help-icon {
    display:flex;
    align-items:center;
    justify-content:center;
    width:42px;
    height:42px;
    flex:0 0 42px;
    border-radius:12px;
    background:rgba(3,195,236,.16);
    color:#03c3ec;
    font-size:24px;
  }

  .location-flow {
    align-items:center;
  }

  .flow-badge {
    display:inline-flex;
    align-items:center;
    padding:.4rem .7rem;
    border-radius:999px;
    font-size:.8rem;
    font-weight:600;
    border:1px solid transparent;
  }

  .flow-warehouse {
    color:#696cff;
    background:rgba(105,108,255,.12);
    border-color:rgba(105,108,255,.25);
  }

  .flow-room {
    color:#ff9f43;
    background:rgba(255,159,67,.13);
    border-color:rgba(255,159,67,.28);
  }

  .flow-rack {
    color:#697a8d;
    background:rgba(105,122,141,.13);
    border-color:rgba(105,122,141,.25);
  }

  .flow-layer {
    color:#28c76f;
    background:rgba(40,199,111,.13);
    border-color:rgba(40,199,111,.25);
  }

  .location-card-header {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:1rem;
    flex-wrap:wrap;
  }

  .location-card-actions {
    display:flex;
    align-items:center;
    gap:.5rem;
    flex-wrap:wrap;
  }

  .location-filter-form {
    display:flex;
    align-items:center;
    gap:.5rem;
    flex-wrap:wrap;
  }

  .location-search-group {
    width:240px;
  }

  .location-type-filter {
    width:180px;
  }

  .location-table-footer {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:1rem;
    flex-wrap:wrap;
  }

  #locations-table .tabulator-header {
    background:#f8f9fa;
  }

  #locations-table .tabulator-col-title {
    color:#566a7f;
  }

  #locations-table .tabulator-row {
    min-height:48px;
  }

  #locations-table .tabulator-cell {
    padding-top:.7rem;
    padding-bottom:.7rem;
  }

  #locations-table .action-buttons {
    display:flex;
    align-items:center;
    justify-content:center;
    gap:.35rem;
    flex-wrap:wrap;
  }

  #locations-table .action-buttons .btn {
    display:inline-flex;
    align-items:center;
    gap:.25rem;
    border-radius:.45rem !important;
  }

  @media (max-width: 767.98px) {
    .location-card-actions,
    .location-filter-form {
      width:100%;
    }

    .location-search-group,
    .location-type-filter {
      width:100%;
    }

    .location-filter-form .btn {
      flex:1;
    }

    .location-card-actions > .btn-primary {
      width:100%;
    }

    .location-flow .mdi-chevron-right {
      display:none;
    }
  }

  /*
   * Tampilan hierarchy untuk pengguna awam.
   */
  #locations-table .location-name-cell {
    align-items:center;
    gap:.15rem;
  }

  #locations-table .location-name-main {
    display:flex;
    align-items:center;
    gap:.45rem;
    min-width:0;
    flex-wrap:wrap;
  }

  #locations-table .location-type-label {
    display:inline-flex;
    align-items:center;
    gap:.25rem;
    padding:.2rem .5rem;
    border-radius:999px;
    font-size:.7rem;
    font-weight:700;
    line-height:1.2;
    white-space:nowrap;
  }

  #locations-table .location-type-label.type-warehouse {
    color:#5f61e6;
    background:rgba(105,108,255,.14);
  }

  #locations-table .location-type-label.type-room {
    color:#c77800;
    background:rgba(255,159,67,.16);
  }

  #locations-table .location-type-label.type-store-display {
    color:#0086a8;
    background:rgba(3,195,236,.14);
  }

  #locations-table .location-type-label.type-rack {
    color:#566a7f;
    background:rgba(105,122,141,.15);
  }

  #locations-table .location-type-label.type-staging {
    color:#6f42c1;
    background:rgba(111,66,193,.14);
  }

  #locations-table .location-type-label.type-layer {
    color:#1c9b55;
    background:rgba(40,199,111,.15);
  }

  #locations-table .tree-chevron {
    position:relative;
  }

  #locations-table .tree-chevron::after {
    position:absolute;
    left:26px;
    top:50%;
    transform:translateY(-50%);
    padding:.15rem .4rem;
    border-radius:5px;
    font-size:.66rem;
    font-weight:700;
    white-space:nowrap;
    opacity:0;
    pointer-events:none;
    transition:opacity .15s ease;
    z-index:20;
  }

  #locations-table .tree-chevron-expand::after {
    content:"Buka";
    color:#4f51d7;
    background:#eef0ff;
  }

  #locations-table .tree-chevron-collapse::after {
    content:"Tutup";
    color:#d14343;
    background:#fff0f0;
  }

  #locations-table .tabulator-data-tree-control:hover .tree-chevron::after {
    opacity:1;
  }

  .tree-guide-card {
    border:1px solid rgba(105,108,255,.18);
    background:#fafaff;
    border-radius:.75rem;
    padding:.85rem 1rem;
    margin-bottom:1rem;
  }

  .tree-guide-items {
    display:flex;
    flex-wrap:wrap;
    align-items:center;
    gap:.55rem;
  }

  .tree-guide-item {
    display:inline-flex;
    align-items:center;
    gap:.4rem;
    font-size:.8rem;
    color:#566a7f;
  }

  .tree-guide-arrow {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    width:24px;
    height:24px;
    border-radius:6px;
    background:rgba(105,108,255,.10);
    color:#696cff;
    font-size:18px;
  }

  @media (max-width: 767.98px) {
    #locations-table .tree-chevron::after {
      display:none;
    }
  }

  #create_name_display:disabled {
    color: #566a7f;
    background-color: #f5f5f9;
    opacity: 1;
    cursor: not-allowed;
    font-weight: 600;
  }



  /* =========================================================
   * POPUP NOTIFIKASI HASIL PROSES
   * ========================================================= */
  .location-notification-backdrop {
    position: fixed;
    inset: 0;
    /* Harus berada di atas navbar/sidebar maupun Bootstrap modal. */
    z-index: 2147483000;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgba(24, 22, 31, .58);
    backdrop-filter: blur(3px);
    -webkit-backdrop-filter: blur(3px);
  }

  .location-notification-backdrop.show {
    display: flex;
  }

  .location-notification-popup {
    width: min(460px, 100%);
    padding: 32px 34px 28px;
    border-radius: 18px;
    background: #fff;
    box-shadow: 0 24px 80px rgba(20, 20, 35, .32);
    text-align: center;
    animation: locationNotificationIn .22s ease-out;
  }

  .location-notification-icon {
    width: 74px;
    height: 74px;
    margin: 0 auto 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    color: #fff;
    font-size: 38px;
    line-height: 1;
  }

  .location-notification-popup.success .location-notification-icon {
    background: #28ad4f;
  }

  .location-notification-popup.error .location-notification-icon {
    background: #ea3d4f;
  }

  .location-notification-title {
    margin: 0 0 10px;
    color: #4e4c59;
    font-size: 26px;
    font-weight: 700;
  }

  .location-notification-message {
    margin: 0;
    color: #606575;
    font-size: 17px;
    line-height: 1.6;
    white-space: pre-line;
  }

  .location-notification-errors {
    max-height: 220px;
    margin: 14px 0 0;
    padding-left: 22px;
    overflow-y: auto;
    color: #606575;
    text-align: left;
    font-size: 15px;
    line-height: 1.55;
  }

  .location-notification-errors li + li {
    margin-top: 7px;
  }

  .location-notification-button {
    min-width: 130px;
    margin-top: 24px;
    padding: 10px 28px;
    border: 0;
    border-radius: 5px;
    background: #8447e6;
    box-shadow: 0 8px 18px rgba(132, 71, 230, .28);
    color: #fff;
    font-size: 17px;
    font-weight: 700;
    cursor: pointer;
    transition: background-color .15s ease, transform .15s ease;
  }

  .location-notification-button:hover,
  .location-notification-button:focus {
    background: #7339d4;
    color: #fff;
    outline: none;
    transform: translateY(-1px);
  }

  @keyframes locationNotificationIn {
    from {
      opacity: 0;
      transform: translateY(12px) scale(.96);
    }

    to {
      opacity: 1;
      transform: translateY(0) scale(1);
    }
  }

  @media (max-width: 575.98px) {
    .location-notification-popup {
      padding: 28px 22px 24px;
    }

    .location-notification-title {
      font-size: 23px;
    }

    .location-notification-message {
      font-size: 16px;
    }
  }

  /* =========================================================
   * TABEL STRUKTUR LOKASI
   * Mengikuti tampilan compact pada referensi: fokus pada tree,
   * identitas lokasi, dan aksi tanpa mengubah data/handler.
   * ========================================================= */
  #locations-table {
    overflow: hidden;
    border: 1px solid #dedcec !important;
    border-radius: 0 !important;
    background: #fcfbff;
    font-size: .86rem;
  }

  #locations-table .tabulator-tableholder {
    overflow-x: auto !important;
    background: #fcfbff;
  }

  #locations-table .tabulator-table {
    min-width: 1000px;
  }

  #locations-table .tabulator-header {
    min-height: 34px;
    border-bottom: 1px solid #dedcec;
    background: #e7e6f8;
  }

  #locations-table .tabulator-header .tabulator-col,
  #locations-table .tabulator-header .tabulator-frozen {
    min-height: 34px;
    border: 0;
    background: transparent;
  }

  #locations-table .tabulator-header .tabulator-col-content {
    padding: 10px 16px 9px;
  }

  #locations-table .tabulator-col-title {
    color: #252334;
    font-size: .75rem;
    font-weight: 500;
    letter-spacing: .01em;
    text-transform: uppercase;
  }

  #locations-table .tabulator-row,
  #locations-table .tabulator-row.tabulator-row-even,
  #locations-table .tabulator-row.group-row {
    min-height: 64px;
    border: 0 !important;
    border-bottom: 1px solid #e0deeb !important;
    border-left: 0 !important;
    background: #fcfbff !important;
  }

  #locations-table .tabulator-row:hover,
  #locations-table .tabulator-row.tabulator-selectable:hover {
    filter: none;
    background: #f7f5ff !important;
  }

  #locations-table .tabulator-cell {
    display: inline-flex;
    align-items: center;
    min-height: 64px;
    padding: 8px 16px;
    border: 0;
    color: #242231;
    white-space: normal;
    overflow: visible;
  }

  #locations-table .tabulator-cell.tabulator-frozen {
    background: inherit;
  }

  #locations-table .tabulator-data-tree-control {
    flex: 0 0 32px;
    min-width: 32px !important;
    width: 32px;
    height: 32px !important;
    margin: 0 8px 0 0 !important;
    border: 0 !important;
    background: transparent !important;
  }

  #locations-table .tree-chevron {
    width: 32px;
    min-width: 32px;
    height: 32px;
    padding: 0;
    border: 1px solid #706788;
    border-radius: 8px;
    color: #252334;
    background: #f8f7ff;
  }

  #locations-table .tree-chevron i {
    font-size: 20px;
  }

  #locations-table .tree-chevron-label,
  #locations-table .tree-chevron::after {
    display: none !important;
  }

  #locations-table .tree-chevron-expand,
  #locations-table .tree-chevron-collapse {
    color: #252334;
    background: #f8f7ff;
    border-color: #706788;
  }

  #locations-table .tabulator-data-tree-control:hover .tree-chevron {
    box-shadow: 0 2px 5px rgba(45, 38, 71, .12);
    transform: none;
  }

  #locations-table .tabulator-data-tree-branch {
    width: 21px;
    height: 32px;
    margin-right: 3px;
    border-color: #79738b !important;
    border-width: 0 0 1px 1px !important;
  }

  #locations-table .location-name-cell {
    align-items: center;
    gap: 0;
    min-width: 0;
  }

  #locations-table .location-name-main {
    flex: 1;
    flex-wrap: nowrap;
    align-items: center;
    gap: 9px;
    min-width: 0;
  }

  #locations-table .location-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 36px;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    color: #252334;
    background: #efeffb;
    font-size: 21px;
  }

  #locations-table .location-name-copy {
    display: flex;
    flex-direction: column;
    min-width: 0;
    line-height: 1.15;
  }

  #locations-table .location-type-label {
    display: block;
    margin: 0 0 3px;
    padding: 0;
    border-radius: 0;
    color: #696576 !important;
    background: transparent !important;
    font-size: .62rem;
    font-weight: 500;
    letter-spacing: .01em;
    text-transform: uppercase;
  }

  #locations-table .location-type-label i {
    display: none;
  }

  #locations-table .location-name-text {
    color: #17151f;
    font-size: .93rem;
    font-weight: 600 !important;
    line-height: 1.2;
  }

  #locations-table .location-code-subtitle {
    margin-top: 2px;
    color: #5d596c;
    font-size: .72rem;
  }

  #locations-table .location-code-badge,
  #locations-table .location-category-badge,
  #locations-table .warehouse-name-badge {
    display: inline-flex;
    align-items: center;
    max-width: 100%;
    padding: 5px 10px;
    border-radius: 9px;
    border: 0;
    color: #242231 !important;
    background: #efeff9 !important;
    box-shadow: none;
    font-size: .75rem;
    font-weight: 500;
    line-height: 1;
    white-space: normal;
  }

  #locations-table .warehouse-name-badge {
    color: #5d596c !important;
    background: transparent !important;
    padding-right: 0;
    padding-left: 0;
  }

  #locations-table .action-buttons {
    justify-content: flex-start;
    gap: 7px;
    flex-wrap: nowrap;
  }

  #locations-table .action-buttons .btn {
    min-height: 32px;
    padding: 5px 9px;
    border-radius: 8px !important;
    box-shadow: none;
    font-size: .76rem;
    font-weight: 500;
    line-height: 1.1;
  }

  #locations-table .action-buttons .location-new-button {
    min-width: 88px;
    border-color: #625a80;
    color: #332d4d;
    background: #fdfcff;
    text-transform: uppercase;
  }

  #locations-table .action-buttons .location-new-button:hover,
  #locations-table .action-buttons .location-new-button:focus {
    border-color: #625a80;
    color: #332d4d;
    background: #f2f0fc;
  }

  #locations-table .location-new-button .mdi-plus {
    font-size: 17px;
  }

  #locations-table .action-buttons .action-icon-button {
    width: 32px;
    padding: 5px;
    justify-content: center;
    border-color: #706788;
    color: #433b5d;
    background: #fdfcff;
  }

  #locations-table .action-buttons .action-edit-button {
    border-color: #5d91ad;
    color: #176080;
    background: #eaf5fa;
  }

  #locations-table .action-buttons .action-delete-button {
    border-color: #dfc0c5;
    color: #bc6670;
    background: #fbf0f1;
  }

  #locations-table .location-action-menu {
    min-width: 165px;
    padding: 5px;
    border-color: #dedcec;
    border-radius: 8px;
    box-shadow: 0 8px 18px rgba(42, 34, 68, .16);
  }

  #locations-table .location-action-menu .dropdown-item {
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 7px 9px;
    border-radius: 5px;
    color: #292536;
    font-size: .78rem;
  }

  #locations-table .location-action-menu .dropdown-item:hover {
    background: #f0effa;
  }

  @media (max-width: 767.98px) {
    #locations-table .tabulator-table {
      min-width: 970px;
    }
  }

</style>
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 py-3 mb-3">
    <div>
      <h4 class="mb-1">Pengaturan Lokasi Warehouse</h4>
      <div class="text-muted">
        Atur struktur lokasi dari Warehouse, Room, Rack, Staging, hingga Layer.
      </div>
    </div>
  </div>

  <div class="alert alert-info border-0 shadow-sm mb-4 location-help-card" role="alert">
    <div class="d-flex align-items-start gap-3">
      <div class="location-help-icon">
        <i class="mdi mdi-information-outline"></i>
      </div>

      <div class="flex-grow-1">
        <div class="fw-semibold mb-1">Cara membaca struktur lokasi</div>
        <div class="small mb-2">
          Klik <strong>panah kanan</strong> untuk membuka isi lokasi dan
          <strong>panah bawah</strong> untuk menutupnya.
        </div>

        <div class="d-flex flex-wrap gap-2 location-flow">
          <span class="flow-badge flow-warehouse">
            <i class="mdi mdi-warehouse me-1"></i> Warehouse
          </span>
          <i class="mdi mdi-chevron-right text-muted"></i>
          <span class="flow-badge flow-room">
            <i class="mdi mdi-door me-1"></i> Room
          </span>
          <i class="mdi mdi-chevron-right text-muted"></i>
          <span class="flow-badge flow-rack">
            <i class="mdi mdi-view-grid-outline me-1"></i> Rack / Staging
          </span>
          <i class="mdi mdi-chevron-right text-muted"></i>
          <span class="flow-badge flow-layer">
            <i class="mdi mdi-layers-outline me-1"></i> Layer
          </span>
        </div>
      </div>
    </div>
  </div>

  {{-- Notifikasi hasil proses ditampilkan melalui popup --}}

  <div class="card">
    <div class="card-header location-card-header">
      <div>
        <h5 class="mb-1">Daftar Struktur Lokasi</h5>
        <div class="small text-muted">
          Gunakan tombol di kolom Aksi untuk menambahkan lokasi turunan.
        </div>
      </div>

      <div class="location-card-actions">
        {{-- Filter --}}
        <form id="filterForm" action="{{ route('master-data.locations-warehouse.index') }}" method="GET" class="location-filter-form">
          <div class="input-group input-group-sm location-search-group">
            <span class="input-group-text">
              <i class="mdi mdi-magnify"></i>
            </span>
            <input
              type="text"
              name="q"
              value="{{ $q ?? '' }}"
              class="form-control"
              placeholder="Cari nama atau kode...">
          </div>

          <select name="type" class="form-select form-select-sm location-type-filter">
            <option value="">Semua Jenis Lokasi</option>
            @foreach($types as $t)
              @continue($t === 'store')
              <option value="{{ $t }}" @selected(($type ?? '')===$t)>{{ ucwords(str_replace('_',' ', $t)) }}</option>
            @endforeach
          </select>
          <button class="btn btn-outline-primary btn-sm" type="submit">
            <i class="mdi mdi-filter-outline me-1"></i> Terapkan
          </button>
          @if(($q ?? '')!=='' || ($type ?? '')!=='')
            <a href="{{ route('master-data.locations-warehouse.index') }}" class="btn btn-outline-secondary btn-sm">
              <i class="mdi mdi-refresh me-1"></i> Reset
            </a>
          @endif
        </form>

        {{-- Tambah top-level --}}
        @can('create master-data-locations-warehouse')
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreateLocation">
          <i class="mdi mdi-plus me-1"></i> Tambah Warehouse Location
        </button>
        @endcan
      </div>
    </div>

    <div class="px-4 pt-3">
      <div class="tree-guide-card">
        <div class="fw-semibold mb-2">
          <i class="mdi mdi-help-circle-outline me-1"></i>
          Cara menggunakan daftar lokasi
        </div>

        <div class="tree-guide-items">
          <span class="tree-guide-item">
            <span class="tree-guide-arrow">
              <i class="mdi mdi-chevron-right"></i>
            </span>
            Klik tombol <strong>Buka</strong> untuk melihat isi lokasi
          </span>

          <span class="tree-guide-item">
            <span class="tree-guide-arrow">
              <i class="mdi mdi-chevron-down"></i>
            </span>
            Klik tombol <strong>Tutup</strong> untuk menyembunyikan isi lokasi
          </span>

          <span class="tree-guide-item">
            <i class="mdi mdi-information-outline text-primary"></i>
            Urutan: Warehouse → Room → Rack/Staging → Layer
          </span>

          <span class="tree-guide-item">
            <i class="mdi mdi-view-agenda-outline text-primary"></i>
            Hanya satu Warehouse dapat dibuka dalam satu waktu
          </span>
        </div>
      </div>
    </div>

    {{-- Grid --}}
    <div class="table-responsive text-nowrap">
      <div id="locations-table" class="border rounded"></div>
    </div>

    <div class="card-footer location-table-footer">
      <div class="small text-muted">
        <i class="mdi mdi-lightbulb-outline me-1"></i>
        Klik tombol <strong>Buka</strong> atau <strong>Tutup</strong> di sebelah nama lokasi.
      </div>

      <div class="d-flex flex-wrap gap-2">
        <button id="btnExpandAll" class="btn btn-sm btn-outline-success">
          <i class="mdi mdi-unfold-more-horizontal me-1"></i>
          Buka Contoh Struktur
        </button>

        <button id="btnCollapseAll" class="btn btn-sm btn-outline-danger">
          <i class="mdi mdi-unfold-less-horizontal me-1"></i>
          Tutup Semua
        </button>
      </div>
    </div>
  </div>
</div>

{{-- ========== MODAL: CREATE (Top-level) ========== --}}
<div class="modal fade" id="modalCreateLocation" tabindex="-1" aria-labelledby="modalCreateLocationLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
    <div class="modal-content">
      <form id="formCreateLocation" method="POST" action="{{ route('master-data.locations-warehouse.store') }}">
        @csrf
        <input type="hidden" name="_form" value="create">

        <div class="modal-header">
          <h5 class="modal-title" id="modalCreateLocationLabel"><i class="mdi mdi-map-plus me-1"></i> Tambah Lokasi Warehouse Utama</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Jenis Lokasi <span class="text-danger">*</span></label>
              <select name="type" id="create_type" class="form-select" required>
                <option value="warehouse" @selected(old('type','warehouse')==='warehouse')>Warehouse</option>
              </select>
              <div class="form-text">Warehouse adalah lokasi utama yang dapat memiliki Room.</div>
              @error('type') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label">Kode <span class="text-danger">*</span></label>
              <input type="text"
                     name="code"
                     class="form-control @error('code') @if(old('_form','create')==='create') is-invalid @endif @enderror"
                     required
                     value="{{ old('code') }}"
                     placeholder="Kode unik">
              @error('code')
                @if(old('_form','create')==='create')
                  <div class="invalid-feedback">{{ $message }}</div>
                @endif
              @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label">Nama Lokasi <span class="text-danger">*</span></label>

              {{-- Nilai asli yang dikirim ke controller --}}
              <input
                type="hidden"
                name="name"
                id="create_name"
                value="{{ old('name') }}">

              {{-- Tampilan nama otomatis dari Warehouse yang dipilih --}}
              <input
                type="text"
                id="create_name_display"
                class="form-control @error('name') is-invalid @enderror"
                value="{{ old('name') }}"
                placeholder="Pilih Warehouse terlebih dahulu"
                disabled>

              @error('name')
                <div class="invalid-feedback d-block">{{ $message }}</div>
              @else
                <div class="form-text">
                  Nama lokasi terisi otomatis sesuai Warehouse yang dipilih.
                </div>
              @enderror
            </div>

            {{-- Parent (hidden utk top-level) --}}
            <div class="col-md-6 d-none">
              <label class="form-label">Lokasi Induk</label>
              <select name="location_parent_id" id="create_parent" class="form-select js-select2-parent" data-placeholder="(Tanpa Lokasi Induk)">
                <option value="">(Tanpa Lokasi Induk)</option>
                @foreach($allParents as $p)
                  <option value="{{ $p->id }}"
                          data-type="{{ $p->type }}"
                          data-owner="{{ $p->owner_root_id ?? ($p->type==='warehouse' ? $p->id : '') }}"
                          data-owner-type="{{ $p->owner_root_type ?? ($p->type==='warehouse' ? $p->type : '') }}">
                    {{ strtoupper($p->type) }} — {{ $p->name }}
                  </option>
                @endforeach
              </select>
              <div class="form-text" id="create_parent_hint"></div>
            </div>

            {{-- Warehouse Ref --}}
            <div class="col-md-6" id="create_warehouse_group">
              <label class="form-label">Pilih Warehouse <span class="text-danger">*</span></label>
              <select name="warehouse_id" id="create_warehouse" class="form-select" required>
                <option value="">-- Pilih data Warehouse --</option>
                @foreach($warehouses as $w)
                  <option value="{{ $w->id }}" @selected(old('warehouse_id')==$w->id)>{{ $w->name }}</option>
                @endforeach
              </select>
              <div class="form-text">Pilih warehouse fisik yang akan dihubungkan dengan lokasi ini.</div>
              @error('warehouse_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- Store Ref (disembunyikan di halaman Warehouse) --}}
            <div class="col-md-6 d-none" id="create_store_group">
              <label class="form-label">Store Ref <span class="text-danger">*</span></label>
              <select name="store_id" id="create_store" class="form-select"></select>
              <div class="form-text">Hanya untuk halaman Location Store.</div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <span class="text-muted small me-auto d-none d-md-inline">Tip: <kbd>Alt</kbd> + <kbd>Enter</kbd> untuk Simpan</span>
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button id="btnCreateSave" type="submit" class="btn btn-primary" data-bs-toggle="tooltip" title="Simpan">
            <i class="mdi mdi-content-save-outline me-1"></i> Simpan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- ========== MODAL: EDIT ========== --}}
<div class="modal fade" id="modalEditLocation" tabindex="-1" aria-labelledby="modalEditLocationLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
    <div class="modal-content">
      <form id="formEditLocation" method="POST" action="#">
        @csrf @method('PUT')
        <input type="hidden" name="_form" value="edit">

        <div class="modal-header">
          <h5 class="modal-title" id="modalEditLocationLabel"><i class="mdi mdi-pencil-outline me-1"></i> Edit Lokasi</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Jenis Lokasi <span class="text-danger">*</span></label>
              <select name="type" id="edit_type" class="form-select @error('type') @if(old('_form')==='edit') is-invalid @endif @enderror" required>
                @foreach($types as $t)
                  @continue($t === 'store')
                  <option value="{{ $t }}">{{ ucwords(str_replace('_',' ', $t)) }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Kode <span class="text-danger">*</span></label>
              <input type="text" name="code" id="edit_code"
                     class="form-control @error('code') @if(old('_form')==='edit') is-invalid @endif @enderror"
                     value="{{ old('_form') === 'edit' ? old('code') : '' }}"
                     required>
              @if(old('_form') === 'edit')
                @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
              @endif
            </div>
            <div class="col-md-6">
              <label class="form-label">Nama Lokasi <span class="text-danger">*</span></label>
              <input
                type="text"
                name="name"
                id="edit_name"
                class="form-control @error('name') @if(old('_form')==='edit') is-invalid @endif @enderror"
                value="{{ old('_form') === 'edit' ? old('name') : '' }}"
                required>
              @if(old('_form') === 'edit')
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
              @endif
              <div class="form-text">
                Nama harus unik di Warehouse yang sama, tetapi boleh sama pada Warehouse berbeda.
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Lokasi Induk</label>
              <select name="location_parent_id" id="edit_parent"
                      class="form-select js-select2-parent @error('location_parent_id') @if(old('_form')==='edit') is-invalid @endif @enderror"
                      data-placeholder="(Tanpa Lokasi Induk)">
                <option value="">(Tanpa Lokasi Induk)</option>
                @foreach($allParents as $p)
                  <option value="{{ $p->id }}"
                          data-type="{{ $p->type }}"
                          data-owner="{{ $p->owner_root_id ?? ($p->type==='warehouse' ? $p->id : '') }}"
                          data-owner-type="{{ $p->owner_root_type ?? ($p->type==='warehouse' ? $p->type : '') }}">
                    {{ strtoupper($p->type) }} — {{ $p->name }}
                  </option>
                @endforeach
              </select>
              <div class="form-text" id="edit_parent_hint"></div>
            </div>

            {{-- Warehouse Ref saat edit --}}
            <div class="col-md-6" id="edit_warehouse_group" style="display:none;">
              <label class="form-label">Pilih Warehouse <span class="text-danger">*</span></label>
              <select name="warehouse_id" id="edit_warehouse"
                      class="form-select @error('warehouse_id') @if(old('_form')==='edit') is-invalid @endif @enderror">
                <option value="">-- Pilih data Warehouse --</option>
                @foreach($warehouses as $w)
                  <option value="{{ $w->id }}">{{ $w->name }}</option>
                @endforeach
              </select>
              @if(old('_form') === 'edit')
                @error('warehouse_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
              @endif
            </div>

            {{-- Store Ref saat edit (disembunyikan) --}}
            <div class="col-md-6 d-none" id="edit_store_group">
              <label class="form-label">Store Ref <span class="text-danger">*</span></label>
              <select name="store_id" id="edit_store" class="form-select"></select>
            </div>

            <input type="hidden" id="edit_id" name="id">
            <input type="hidden" id="edit_owner_root_id" name="owner_root_id">
          </div>
        </div>

        <div class="modal-footer">
          <span class="text-muted small me-auto d-none d-md-inline">Tip: <kbd>Alt</kbd> + <kbd>Enter</kbd> untuk Update</span>
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button id="btnEditUpdate" type="submit" class="btn btn-primary" data-bs-toggle="tooltip" title="Update">
            <i class="mdi mdi-content-save-outline me-1"></i> Update
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- ========== MODAL: QUICK ADD ========== --}}
<div class="modal fade" id="modalQuickAdd" tabindex="-1" aria-labelledby="modalQuickAddLabel" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content">
      <form id="formQuickAdd" method="POST" action="{{ route('master-data.locations-warehouse.store') }}">
        @csrf
        <input type="hidden" name="_form" value="quick_add">
        <input type="hidden" name="type" id="qa_type">
        <input type="hidden" name="location_parent_id" id="qa_parent_id">

        <div class="modal-header">
          <h5 class="modal-title" id="modalQuickAddLabel">Tambah</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div class="mb-2">
            <div class="small text-muted">Parent</div>
            <div id="qa_parent_info" class="fw-semibold"></div>
          </div>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Nama Lokasi <span class="text-danger">*</span></label>
              <input
                type="text"
                name="name"
                id="qa_name"
                class="form-control @error('name') @if(old('_form')==='quick_add') is-invalid @endif @enderror"
                required
                value="{{ old('_form')==='quick_add' ? old('name') : '' }}">

              @error('name')
                @if(old('_form')==='quick_add')
                  <div class="invalid-feedback">{{ $message }}</div>
                @endif
              @else
                <div class="form-text">
                  Nama tidak boleh sama dengan lokasi lain di Warehouse ini.
                </div>
              @enderror
            </div>
            <div class="col-12">
              <label class="form-label">Kode <span class="text-danger">*</span></label>
              <input type="text"
                     name="code"
                     id="qa_code"
                     class="form-control @error('code') @if(old('_form')==='quick_add') is-invalid @endif @enderror"
                     required
                     placeholder="Kode unik"
                     value="{{ old('_form')==='quick_add' ? old('code') : '' }}">
              @error('code')
                @if(old('_form')==='quick_add')
                  <div class="invalid-feedback">{{ $message }}</div>
                @endif
              @enderror
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-success"><i class="mdi mdi-content-save-outline me-1"></i> Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- ========== MODAL: SHOW QR ========== --}}
<div class="modal fade" id="modalShowQr" tabindex="-1" aria-labelledby="modalShowQrLabel" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalShowQrLabel"><i class="mdi mdi-qrcode me-1"></i> QR Lokasi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body d-flex flex-column align-items-center gap-2">
        <div id="qrMeta" class="text-center small text-muted"></div>
        <div class="border rounded p-2 bg-white">
          <img id="qrImg" alt="QR" style="width:260px;height:260px;display:block">
        </div>
        <a id="qrDownload" class="btn btn-outline-primary btn-sm" download>
          <i class="mdi mdi-download"></i> Unduh QR
        </a>
      </div>
    </div>
  </div>
</div>

{{-- FORM DELETE --}}
<form id="formDeleteLocation" method="POST" action="#" class="d-none">
  @csrf @method('DELETE')
</form>

{{-- Util kecil --}}
<script>
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el){ new bootstrap.Tooltip(el) });
  ['modalCreateLocation','modalEditLocation','modalQuickAdd'].forEach(function(id){
    var m=document.getElementById(id); if(!m) return;
    m.addEventListener('keydown',function(e){
      if(e.altKey && e.key==='Enter'){ e.preventDefault(); var f=m.querySelector('form'); if(f) f.requestSubmit(); }
    });
  });
});
</script>



{{-- =========================================================
     POPUP NOTIFIKASI HASIL SIMPAN / UPDATE / HAPUS
     ========================================================= --}}
<div
  id="locationNotificationBackdrop"
  class="location-notification-backdrop"
  aria-hidden="true">

  <div
    id="locationNotificationPopup"
    class="location-notification-popup success"
    role="alertdialog"
    aria-modal="true"
    aria-labelledby="locationNotificationTitle"
    aria-describedby="locationNotificationMessage">

    <div class="location-notification-icon">
      <i id="locationNotificationIcon" class="mdi mdi-check"></i>
    </div>

    <h3
      id="locationNotificationTitle"
      class="location-notification-title">
      Berhasil
    </h3>

    <p
      id="locationNotificationMessage"
      class="location-notification-message">
    </p>

    <ul
      id="locationNotificationErrors"
      class="location-notification-errors d-none">
    </ul>

    <button
      type="button"
      id="locationNotificationClose"
      class="location-notification-button">
      OK
    </button>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  'use strict';

  const flashSuccess = @json(session('success'));
  const flashError = @json(session('error'));
  const validationErrors = @json($errors->all());
  const formSource = @json(old('_form', ''));

  const pendingStorageKey = 'warehouse_location_pending_notification';

  const backdrop =
    document.getElementById('locationNotificationBackdrop');
  const popup =
    document.getElementById('locationNotificationPopup');
  const icon =
    document.getElementById('locationNotificationIcon');
  const title =
    document.getElementById('locationNotificationTitle');
  const message =
    document.getElementById('locationNotificationMessage');
  const errorList =
    document.getElementById('locationNotificationErrors');
  const closeButton =
    document.getElementById('locationNotificationClose');

  /*
   * Notification markup starts in the page-content container. That container
   * has its own stacking context, allowing the fixed navbar to appear above
   * the backdrop. Move the overlay to body so it covers the entire app.
   */
  if (backdrop && backdrop.parentElement !== document.body) {
    document.body.appendChild(backdrop);
  }

  function typeLabel(type) {
    const labels = {
      warehouse: 'Warehouse',
      room: 'Room',
      store_display: 'Store Display',
      rack: 'Rack',
      staging: 'Staging',
      layer: 'Layer'
    };

    return labels[String(type || '').toLowerCase()] || 'Lokasi';
  }

  function savePendingNotification(form) {
    if (!form) {
      return;
    }

    const sourceInput = form.querySelector('[name="_form"]');
    const typeInput = form.querySelector('[name="type"]');
    const nameInput = form.querySelector('[name="name"]');

    const payload = {
      source: sourceInput ? sourceInput.value : '',
      type: typeInput ? typeInput.value : '',
      name: nameInput ? nameInput.value.trim() : '',
      createdAt: Date.now()
    };

    try {
      sessionStorage.setItem(
        pendingStorageKey,
        JSON.stringify(payload)
      );
    } catch (error) {
      // Abaikan apabila browser memblokir sessionStorage.
    }
  }

  function readPendingNotification() {
    try {
      const raw = sessionStorage.getItem(pendingStorageKey);

      if (!raw) {
        return null;
      }

      const payload = JSON.parse(raw);

      /*
       * Hapus data lama agar notifikasi tidak muncul pada kunjungan berikutnya.
       */
      if (
        !payload ||
        !payload.createdAt ||
        Date.now() - payload.createdAt > 120000
      ) {
        sessionStorage.removeItem(pendingStorageKey);
        return null;
      }

      return payload;
    } catch (error) {
      return null;
    }
  }

  function clearPendingNotification() {
    try {
      sessionStorage.removeItem(pendingStorageKey);
    } catch (error) {
      // Tidak perlu tindakan tambahan.
    }
  }

  /*
   * Simpan informasi sebelum form dikirim.
   * Ini memastikan Room, Rack, Staging, dan Layer tetap mempunyai popup
   * meskipun dibuat melalui modal Quick Add.
   */
  [
    'formCreateLocation',
    'formEditLocation',
    'formQuickAdd'
  ].forEach(function (formId) {
    const form = document.getElementById(formId);

    form?.addEventListener('submit', function () {
      savePendingNotification(form);
    });
  });

  function closeAllLocationModals() {
    document
      .querySelectorAll(
        '#modalCreateLocation.show, ' +
        '#modalEditLocation.show, ' +
        '#modalQuickAdd.show'
      )
      .forEach(function (modalElement) {
        if (typeof bootstrap !== 'undefined') {
          const modalInstance =
            bootstrap.Modal.getInstance(modalElement);

          if (modalInstance) {
            modalInstance.hide();
          }
        }

        modalElement.classList.remove('show');
        modalElement.style.display = 'none';
        modalElement.setAttribute('aria-hidden', 'true');
      });

    document
      .querySelectorAll('.modal-backdrop')
      .forEach(function (modalBackdrop) {
        modalBackdrop.remove();
      });

    document.body.classList.remove('modal-open');
    document.body.style.removeProperty('padding-right');
  }

  function showLocationNotification(
    notificationType,
    heading,
    detail,
    errors
  ) {
    if (
      !backdrop ||
      !popup ||
      !icon ||
      !title ||
      !message ||
      !errorList
    ) {
      return;
    }

    closeAllLocationModals();

    const isSuccess = notificationType === 'success';

    popup.classList.remove('success', 'error');
    popup.classList.add(isSuccess ? 'success' : 'error');

    icon.className = isSuccess
      ? 'mdi mdi-check'
      : 'mdi mdi-close';

    title.textContent =
      heading || (isSuccess ? 'Berhasil' : 'Gagal');

    message.textContent = detail || '';
    errorList.innerHTML = '';

    if (Array.isArray(errors) && errors.length > 0) {
      errors.forEach(function (errorMessage) {
        const item = document.createElement('li');
        item.textContent = errorMessage;
        errorList.appendChild(item);
      });

      errorList.classList.remove('d-none');
    } else {
      errorList.classList.add('d-none');
    }

    backdrop.classList.add('show');
    backdrop.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

    window.setTimeout(function () {
      closeButton?.focus();
    }, 100);
  }

  function closeLocationNotification() {
    if (!backdrop) {
      return;
    }

    backdrop.classList.remove('show');
    backdrop.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  closeButton?.addEventListener(
    'click',
    closeLocationNotification
  );

  backdrop?.addEventListener('click', function (event) {
    if (event.target === backdrop) {
      closeLocationNotification();
    }
  });

  document.addEventListener('keydown', function (event) {
    if (
      event.key === 'Escape' &&
      backdrop?.classList.contains('show')
    ) {
      closeLocationNotification();
    }
  });

  const pending = readPendingNotification();

  /*
   * Prioritas:
   * 1. Validation error
   * 2. Flash error
   * 3. Flash success
   * 4. Fallback pending Quick Add
   */
  if (
    Array.isArray(validationErrors) &&
    validationErrors.length > 0
  ) {
    clearPendingNotification();

    showLocationNotification(
      'error',
      formSource === 'edit'
        ? 'Gagal Memperbarui'
        : 'Gagal Menyimpan',
      'Data tidak dapat diproses karena:',
      validationErrors
    );

    return;
  }

  if (flashError) {
    clearPendingNotification();

    showLocationNotification(
      'error',
      'Gagal',
      flashError,
      []
    );

    return;
  }

  if (flashSuccess) {
    clearPendingNotification();

    showLocationNotification(
      'success',
      'Berhasil',
      flashSuccess,
      []
    );

    return;
  }

  /*
   * Fallback khusus setelah Quick Add berhasil.
   * Digunakan jika session flash dari controller tidak terbaca oleh view,
   * tetapi request berhasil kembali ke halaman index.
   */
  if (pending && pending.source === 'quick_add') {
    clearPendingNotification();

    const label = typeLabel(pending.type);
    const locationName = pending.name
      ? ' "' + pending.name + '"'
      : '';

    showLocationNotification(
      'success',
      'Berhasil',
      label + locationName + ' berhasil ditambahkan.',
      []
    );
  }
});
</script>

@endsection

@push('vendor-script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tabulator-tables@6.3.0/dist/js/tabulator.min.js"></script>

@endpush
@push('page-script')
<script>
(function($){
  'use strict';
  if(!$) return;

  /* ---------- Select2 ---------- */
  function initParentSelect2(modal){
    var $m = $(modal);

    $m.find('.js-select2-parent').each(function(){
      var $el = $(this);

      if($el.hasClass('select2-hidden-accessible')) return;

      $el.select2({
        theme: 'bootstrap-5',
        placeholder: $el.data('placeholder') || '(Tanpa Lokasi Induk)',
        allowClear: true,
        dropdownParent: $m,
        width: '100%'
      });
    });
  }

  initParentSelect2('#modalCreateLocation');
  initParentSelect2('#modalEditLocation');

  $('#modalCreateLocation').on('shown.bs.modal', function(){
    initParentSelect2(this);
    window.applyParentRules('#modalCreateLocation');
    syncCreateWarehouseName();
  });

  /*
   * Berlaku untuk tombol Batal, tombol X, klik backdrop,
   * dan penutupan modal melalui JavaScript.
   */
  $('#modalCreateLocation').on('hidden.bs.modal', function(){
    resetCreateWarehouseLocationForm();
  });

  $('#modalEditLocation').on('shown.bs.modal', function(){
    initParentSelect2(this);
    window.applyParentRules('#modalEditLocation');
    restoreEditValues();
  });

  /*
   * Semua modal selalu dibersihkan setelah ditutup.
   */
  $('#modalEditLocation').on('hidden.bs.modal', function(){
    resetEditLocationForm();
  });

  $('#modalQuickAdd').on('hidden.bs.modal', function(){
    resetQuickAddForm();
  });

  $('#modalShowQr').on('hidden.bs.modal', function(){
    resetQrModal();
  });

  /* ---------- Escape util ---------- */
  window._ = window._ || {};

  _.escape = _.escape || function(s){
    return String(s == null ? '' : s).replace(/[&<>"'`=\/]/g, function(c){
      return {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        '\'': '&#39;',
        '/': '&#x2F;',
        '`': '&#x60;',
        '=': '&#x3D;'
      }[c];
    });
  };

  /* ---------- Build URL ---------- */
  function buildTreeUrl(){
    var p = new URLSearchParams(),
        q = document.querySelector('input[name="q"]'),
        t = document.querySelector('select[name="type"]');

    if(q && q.value.trim() !== '') {
      p.set('q', q.value.trim());
    }

    if(t && t.value) {
      p.set('type', t.value);
    }

    return "{{ route('master-data.locations-warehouse.tree') }}" + (p.toString() ? ('?' + p.toString()) : '');
  }

  /* ---------- Badge warna ---------- */
  function typeColorClass(type){
    switch((type || '').toLowerCase()){
      case 'warehouse':      return 'bg-primary text-white';
      case 'store':          return 'bg-info text-white';
      case 'room':           return 'bg-warning text-dark';
      case 'store_display':  return 'bg-info text-white';
      case 'rack':           return 'bg-secondary text-white';
      case 'staging':        return 'bg-dark text-white';
      case 'layer':          return 'bg-success text-white';
      default:               return 'bg-light text-dark';
    }
  }

  function humanizeType(t){
    return String(t || '')
      .replace(/_/g, ' ')
      .replace(/\b\w/g, function(c){
        return c.toUpperCase();
      });
  }

  function typeLabelMeta(type){
    switch(String(type || '').toLowerCase()){
      case 'warehouse':
        return {
          label: 'Warehouse',
          icon: 'mdi-warehouse',
          cls: 'type-warehouse'
        };

      case 'room':
        return {
          label: 'Room',
          icon: 'mdi-door',
          cls: 'type-room'
        };

      case 'store_display':
        return {
          label: 'Store Display',
          icon: 'mdi-storefront-outline',
          cls: 'type-store-display'
        };

      case 'rack':
        return {
          label: 'Rack',
          icon: 'mdi-view-grid-outline',
          cls: 'type-rack'
        };

      case 'staging':
        return {
          label: 'Staging',
          icon: 'mdi-package-variant-closed',
          cls: 'type-staging'
        };

      case 'layer':
        return {
          label: 'Layer',
          icon: 'mdi-layers-outline',
          cls: 'type-layer'
        };

      default:
        return {
          label: humanizeType(type),
          icon: 'mdi-map-marker-outline',
          cls: 'type-rack'
        };
    }
  }

  function hueFromId(id){
    var s = String(id || '0'), h = 0;

    for(var i = 0; i < s.length; i++){
      h = (h * 31 + s.charCodeAt(i)) >>> 0;
    }

    return h % 360;
  }

  function applyGroupColorToEl(el, ownerId){
    var hue = hueFromId(ownerId || '0');
    el.style.setProperty('--h', hue);
    el.classList.add('group-row');
  }

  /* ---------- Restore select edit ---------- */
  function setSelectValue(selector, value){
    var el = document.querySelector(selector);
    if(!el) return;

    value = value || '';
    el.value = value;

    if(window.jQuery){
      jQuery(selector).val(value).trigger('change');
    }
  }

  function restoreEditValues(){
    if(window._editCurrentParentId !== undefined){
      setSelectValue('#edit_parent', window._editCurrentParentId || '');
    }

    if(window._editCurrentWarehouseId !== undefined){
      setSelectValue('#edit_warehouse', window._editCurrentWarehouseId || '');
    }
  }

  /*
   * Nama lokasi Warehouse mengikuti Warehouse fisik yang dipilih.
   * Input tampilan dibuat disabled, sedangkan nilai dikirim melalui hidden input.
   */
  function syncCreateWarehouseName(){
    var warehouseSelect = document.getElementById('create_warehouse');
    var hiddenName = document.getElementById('create_name');
    var displayName = document.getElementById('create_name_display');

    if(!warehouseSelect || !hiddenName || !displayName){
      return;
    }

    var selectedOption = warehouseSelect.options[
      warehouseSelect.selectedIndex
    ];

    var warehouseName = (
      warehouseSelect.value && selectedOption
    )
      ? String(selectedOption.text || '').trim()
      : '';

    hiddenName.value = warehouseName;
    displayName.value = warehouseName;
  }

  /*
   * Reset seluruh form Tambah Warehouse Location.
   * Dipanggil setiap modal ditutup agar ketika dibuka kembali,
   * tidak ada data lama yang masih tertinggal.
   */
  function resetCreateWarehouseLocationForm(){
    var form = document.getElementById('formCreateLocation');

    if(!form){
      return;
    }

    form.reset();
    form.classList.remove('was-validated');

    /*
     * Kembalikan jenis lokasi ke Warehouse.
     */
    var typeSelect = document.getElementById('create_type');

    if(typeSelect){
      typeSelect.value = 'warehouse';
    }

    /*
     * Reset Warehouse fisik.
     */
    var warehouseSelect = document.getElementById('create_warehouse');

    if(warehouseSelect){
      warehouseSelect.value = '';

      if(window.jQuery){
        jQuery(warehouseSelect).val('').trigger('change');
      }
    }

    /*
     * Reset parent dan Select2.
     */
    var parentSelect = document.getElementById('create_parent');

    if(parentSelect){
      parentSelect.value = '';

      if(
        window.jQuery
        && jQuery(parentSelect).hasClass('select2-hidden-accessible')
      ){
        jQuery(parentSelect).val('').trigger('change');
      }
    }

    /*
     * Reset referensi Store tersembunyi.
     */
    var storeSelect = document.getElementById('create_store');

    if(storeSelect){
      storeSelect.value = '';
    }

    /*
     * Reset kode.
     */
    var codeInput = form.querySelector('input[name="code"]');

    if(codeInput){
      codeInput.value = '';
    }

    /*
     * Reset nama otomatis dan hidden input.
     */
    var hiddenName = document.getElementById('create_name');
    var displayName = document.getElementById('create_name_display');

    if(hiddenName){
      hiddenName.value = '';
    }

    if(displayName){
      displayName.value = '';
    }

    /*
     * Hapus status validasi Bootstrap/Laravel.
     */
    form.querySelectorAll('.is-invalid, .is-valid').forEach(function(element){
      element.classList.remove('is-invalid', 'is-valid');
    });

    /*
     * Reset teks pesan validasi dinamis jika ada.
     */
    form.querySelectorAll('.invalid-feedback').forEach(function(element){
      element.style.display = '';
    });

    /*
     * Terapkan kembali aturan form ke kondisi awal.
     */
    if(window.applyParentRules){
      window.applyParentRules('#modalCreateLocation');
    }
  }


  /*
   * Menghapus status validasi dari sebuah form.
   */
  function clearFormValidation(form){
    if(!form){
      return;
    }

    form.classList.remove('was-validated');

    form.querySelectorAll('.is-invalid, .is-valid').forEach(function(element){
      element.classList.remove('is-invalid', 'is-valid');
    });
  }

  /*
   * Reset modal Tambah Room, Rack, Staging, Layer, dan Store Display.
   */
  function resetQuickAddForm(){
    var form = document.getElementById('formQuickAdd');

    if(!form){
      return;
    }

    form.reset();
    clearFormValidation(form);

    var typeInput = document.getElementById('qa_type');
    var parentInput = document.getElementById('qa_parent_id');
    var parentInfo = document.getElementById('qa_parent_info');
    var nameInput = document.getElementById('qa_name');
    var codeInput = document.getElementById('qa_code');
    var modalLabel = document.getElementById('modalQuickAddLabel');

    if(typeInput){
      typeInput.value = '';
    }

    if(parentInput){
      parentInput.value = '';
    }

    if(parentInfo){
      parentInfo.textContent = '';
    }

    if(nameInput){
      nameInput.value = '';
    }

    if(codeInput){
      codeInput.value = '';
    }

    if(modalLabel){
      modalLabel.textContent = 'Tambah';
    }
  }

  /*
   * Reset modal Edit setelah ditutup.
   */
  function resetEditLocationForm(){
    var form = document.getElementById('formEditLocation');

    if(!form){
      return;
    }

    form.reset();
    form.action = '#';
    clearFormValidation(form);

    var editId = document.getElementById('edit_id');
    var editName = document.getElementById('edit_name');
    var editCode = document.getElementById('edit_code');
    var editType = document.getElementById('edit_type');
    var editParent = document.getElementById('edit_parent');
    var editWarehouse = document.getElementById('edit_warehouse');
    var editStore = document.getElementById('edit_store');
    var parentHint = document.getElementById('edit_parent_hint');
    var editOwnerRootId = document.getElementById('edit_owner_root_id');

    if(editId){
      editId.value = '';
    }

    if(editName){
      editName.value = '';
    }

    if(editCode){
      editCode.value = '';
    }

    if(editType){
      editType.selectedIndex = 0;
    }

    if(editParent){
      editParent.value = '';

      if(
        window.jQuery
        && jQuery(editParent).hasClass('select2-hidden-accessible')
      ){
        jQuery(editParent).val('').trigger('change');
      }
    }

    if(editWarehouse){
      editWarehouse.value = '';
    }

    if(editStore){
      editStore.value = '';
    }

    if(parentHint){
      parentHint.textContent = '';
    }

    if(editOwnerRootId){
      editOwnerRootId.value = '';
    }

    window._editOwnerId = null;
    window._editCurrentParentId = '';
    window._editCurrentWarehouseId = '';
  }

  /*
   * Reset modal QR setelah ditutup.
   */
  function resetQrModal(){
    var image = document.getElementById('qrImg');
    var meta = document.getElementById('qrMeta');
    var download = document.getElementById('qrDownload');

    if(image){
      image.removeAttribute('src');
    }

    if(meta){
      meta.textContent = '';
    }

    if(download){
      download.removeAttribute('href');
      download.removeAttribute('download');
    }
  }

  /* ---------- Tabulator ---------- */
  var table = new Tabulator("#locations-table", {
    height: "520px",
    layout: "fitColumns",
    columnDefaults: {
      headerHozAlign: "left",
      vertAlign: "middle",
      resizable: true
    },
    ajaxURL: buildTreeUrl(),
    ajaxConfig: "GET",
    placeholder: "Belum ada data",
    dataTree: true,
    dataTreeChildField: "_children",
    dataTreeElementColumn: "name",
    // Setiap level anak digeser 40px agar garis hierarchy sub-sub lebih masuk.
    dataTreeChildIndent: 40,
    dataTreeStartExpanded: false,

    dataTreeExpandElement:
      '<span class="tree-chevron tree-chevron-expand" title="Buka isi lokasi" aria-label="Buka isi lokasi">' +
        '<i class="mdi mdi-chevron-right"></i>' +
      '</span>',

    dataTreeCollapseElement:
      '<span class="tree-chevron tree-chevron-collapse" title="Tutup isi lokasi" aria-label="Tutup isi lokasi">' +
        '<i class="mdi mdi-chevron-down"></i>' +
      '</span>',

    columns: [
      {
        title: "Name",
        field: "name",
        minWidth: 320,
        widthGrow: 3,
        variableHeight: true,
        formatter: function(cell){
          var row = cell.getRow().getData();
          var v = cell.getValue() || '';
          var meta = typeLabelMeta(row.type || '');

          return '' +
            '<div class="location-name-cell" title="' + _.escape(v) + '">' +
              '<span class="group-dot"></span>' +
              '<div class="location-name-main">' +
                '<span class="location-icon" aria-hidden="true">' +
                  '<i class="mdi ' + meta.icon + '"></i>' +
                '</span>' +
                '<span class="location-name-copy">' +
                  '<span class="location-type-label ' + meta.cls + '">' + _.escape(meta.label) + '</span>' +
                  '<span class="location-name-text">' + _.escape(v) + '</span>' +
                  (row.code ? '<span class="location-code-subtitle">' + _.escape(row.code) + '</span>' : '') +
                '</span>' +
              '</div>' +
            '</div>';
        }
      },
      {
        title: "ID",
        field: "code",
        minWidth: 110,
        widthGrow: 1,
        formatter: function(cell){
          var v = cell.getValue() || '';

          return '<span class="location-code-badge">' + _.escape(v) + '</span>';
        }
      },
      {
        title: "Category",
        field: "type",
        minWidth: 145,
        widthGrow: 1,
        formatter: function(cell){
          var t = cell.getValue() || '';
          var label = humanizeType(t);
          return '<span class="location-category-badge">' + _.escape(label) + '</span>';
        }
      },
      {
        title: "Location",
        field: "warehouse_name",
        minWidth: 170,
        widthGrow: 1,
        variableHeight: true,
        formatter: function(cell){
          var row = cell.getRow().getData();
          var warehouseName = row.warehouse_name || '';

          if(!warehouseName){
            return '<span class="text-muted">-</span>';
          }

          return '' +
            '<span class="badge bg-label-primary warehouse-name-badge" ' +
              'title="' + _.escape(warehouseName) + '">' +
              _.escape(warehouseName) +
            '</span>';
        }
      },
      {
        title: "Actions",
        field: "id",
        hozAlign: "center",
        width: 330,
        minWidth: 330,
        headerSort: false,
        formatter: function(cell){
          var row = cell.getRow().getData(),
              id = row.id,
              name = row.name || '',
              code = row.code || '',
              typeRaw = row.type || '';

          var type = String(typeRaw).toLowerCase();

          var updateUrl = "{{ route('master-data.locations-warehouse.update', ':id') }}".replace(':id', id);
          var deleteUrl = "{{ route('master-data.locations-warehouse.destroy', ':id') }}".replace(':id', id);

          var addOptions = '';

          if(type === 'warehouse'){
            @can('create master-data-locations-warehouse')
              addOptions =
              '<button type="button" class="dropdown-item act-add-child" data-next="room" data-parent-id="' + id + '" data-parent-name="' + _.escape(name) + '">' +
                '<i class="mdi mdi-plus-circle-outline"></i> Room' +
              '</button>';
              @else
              addOptions = '';
              @endcan
          } else if(type === 'room' || type === 'store_display'){
            @can('create master-data-locations-warehouse')
              addOptions =
              '<button type="button" class="dropdown-item act-add-child" data-next="rack" data-parent-id="' + id + '" data-parent-name="' + _.escape(name) + '">' +
                '<i class="mdi mdi-plus-circle-outline"></i> Rack' +
              '</button>' +
              '<button type="button" class="dropdown-item act-add-child" data-next="staging" data-parent-id="' + id + '" data-parent-name="' + _.escape(name) + '">' +
                '<i class="mdi mdi-plus-circle-outline"></i> Staging' +
              '</button>';
              @else
              addOptions = '';
              @endcan
          } else if(type === 'rack'){
            @can('create master-data-locations-warehouse')
              addOptions =
              '<button type="button" class="dropdown-item act-add-child" data-next="layer" data-parent-id="' + id + '" data-parent-name="' + _.escape(name) + '">' +
                '<i class="mdi mdi-plus-circle-outline"></i> Layer' +
              '</button>';
              @else
              addOptions = '';
              @endcan
          }

          var addBtn = addOptions
            ? '<div class="dropdown">' +
                '<button type="button" class="btn location-new-button dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">' +
                  '<i class="mdi mdi-plus"></i> New' +
                '</button>' +
                '<div class="dropdown-menu location-action-menu">' + addOptions + '</div>' +
              '</div>'
            : '';

          var parentId = row.location_parent_id || '';
          var ownerId = row.owner_root_id || (type === 'warehouse' ? id : '');
          var warehouseId = row.warehouse_id || '';

          var qrBtn = '';

          if(type !== 'warehouse' && type !== 'store'){
            var qrUrl = "{{ route('master-data.locations-warehouse.qr', ':id') }}".replace(':id', id);

            qrBtn = '<button type="button" class="btn action-icon-button act-qr" title="Lihat QR" ' +
                    'data-qr-url="' + qrUrl + '" data-name="' + _.escape(name) + '" data-code="' + _.escape(code) + '">' +
                      '<i class="mdi mdi-qrcode"></i>' +
                    '</button>';
          }

          return '' +
            '<div class="action-buttons" role="group">' +
              addBtn +
              qrBtn +

              @can('update master-data-locations-warehouse')
              '<button type="button" class="btn action-edit-button act-edit" title="Edit" ' +
                'data-id="' + id + '" ' +
                'data-name="' + _.escape(name) + '" ' +
                'data-code="' + _.escape(code) + '" ' +
                'data-type="' + _.escape(typeRaw) + '" ' +
                'data-parent-id="' + _.escape(parentId) + '" ' +
                'data-owner-id="' + _.escape(ownerId) + '" ' +
                'data-warehouse-id="' + _.escape(warehouseId) + '" ' +
                'data-update-url="' + updateUrl + '">' +
                  '<i class="mdi mdi-pencil-outline"></i><span>Edit</span>' +
              '</button>' +
              @endcan

              @can('delete master-data-locations-warehouse')
              '<button type="button" class="btn action-icon-button action-delete-button act-delete" title="Hapus" data-delete-url="' + deleteUrl + '">' +
                '<i class="mdi mdi-trash-can-outline"></i>' +
              '</button>' +
              @endcan
            '</div>';
        }
      }
    ],
    rowFormatter: function(row){
      var d = row.getData();
      var type = String(d.type || '').toLowerCase();
      var ownerId = d.owner_root_id || (type === 'warehouse' ? d.id : null);

      if(ownerId){
        applyGroupColorToEl(row.getElement(), ownerId);
      }
    },
    dataLoaded: function(){
      table.getRows().forEach(function(r){
        r.treeCollapse();
      });

      document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el){
        new bootstrap.Tooltip(el);
      });
    }
  });

  /*
  |--------------------------------------------------------------------------
  | Accordion Warehouse
  |--------------------------------------------------------------------------
  | Saat satu Warehouse utama dibuka, Warehouse utama lain otomatis ditutup.
  | Semua turunan pada Warehouse yang ditutup juga dikembalikan ke kondisi
  | tertutup agar pengguna tidak melihat banyak struktur terbuka sekaligus.
  */

  var isWarehouseAccordionRunning = false;

  function isRootWarehouseRow(row){
    if(!row) return false;

    var data = row.getData ? row.getData() : {};
    var type = String(data.type || '').toLowerCase();
    var parentId = data.location_parent_id || '';

    return type === 'warehouse' && !parentId;
  }

  function collapseTreeChildrenRecursively(row){
    if(!row) return;

    var children = row.getTreeChildren
      ? row.getTreeChildren()
      : [];

    children.forEach(function(childRow){
      collapseTreeChildrenRecursively(childRow);

      if(childRow.treeCollapse){
        childRow.treeCollapse();
      }
    });
  }

  function collapseTreeRecursively(row){
    if(!row) return;

    collapseTreeChildrenRecursively(row);

    if(row.treeCollapse){
      row.treeCollapse();
    }
  }

  table.on('dataTreeRowExpanded', function(row){
    if(isWarehouseAccordionRunning) return;
    if(!isRootWarehouseRow(row)) return;

    isWarehouseAccordionRunning = true;

    try {
      table.getRows().forEach(function(otherRow){
        if(otherRow === row) return;
        if(!isRootWarehouseRow(otherRow)) return;

        collapseTreeRecursively(otherRow);
      });
    } finally {
      isWarehouseAccordionRunning = false;
    }
  });

  /*
   * Menutup Warehouse utama juga menutup semua anaknya. Dengan begitu,
   * pembukaan Warehouse berikutnya selalu dimulai dari level Room saja.
   */
  table.on('dataTreeRowCollapsed', function(row){
    if(isWarehouseAccordionRunning) return;
    if(!isRootWarehouseRow(row)) return;

    collapseTreeChildrenRecursively(row);
  });

  /* ---------- Expand / Collapse ---------- */
  document.getElementById('btnExpandAll').addEventListener('click', function(){
    var firstWarehouse = table.getRows().find(function(row){
      return isRootWarehouseRow(row);
    });

    if(!firstWarehouse) return;

    collapseAllRootWarehouses();

    firstWarehouse.treeExpand();

    /*
     * Buka seluruh isi hanya pada Warehouse pertama.
     */
    expandTreeRecursively(firstWarehouse);
  });

  function expandTreeRecursively(row){
    if(!row) return;

    if(row.treeExpand){
      row.treeExpand();
    }

    var children = row.getTreeChildren
      ? row.getTreeChildren()
      : [];

    children.forEach(function(childRow){
      expandTreeRecursively(childRow);
    });
  }

  function collapseAllRootWarehouses(){
    isWarehouseAccordionRunning = true;

    try {
      table.getRows().forEach(function(row){
        if(isRootWarehouseRow(row)){
          collapseTreeRecursively(row);
        }
      });
    } finally {
      isWarehouseAccordionRunning = false;
    }
  }

  document.getElementById('btnCollapseAll').addEventListener('click', function(){
    collapseAllRootWarehouses();
  });

  /* ---------- Filter ---------- */
  var filterForm = document.getElementById('filterForm');

  if(filterForm){
    filterForm.addEventListener('submit', function(e){
      e.preventDefault();

      table.setData(buildTreeUrl()).then(function(){
        table.getRows().forEach(function(r){
          r.treeCollapse();
        });
      });
    });
  }

  /* ---------- Capture parent options ---------- */
  function captureOptions(select){
    var opts = [],
        options = select.querySelectorAll('option');

    for(var i = 0; i < options.length; i++){
      var o = options[i];

      opts.push({
        value: o.value,
        text: o.text,
        type: o.dataset.type || null,
        owner: o.dataset.owner || null,
        ownerType: o.dataset.ownerType || null
      });
    }

    return opts;
  }

  var $createParent = document.getElementById('create_parent'),
      $editParent = document.getElementById('edit_parent');

  var masterCreate = captureOptions($createParent),
      masterEdit = captureOptions($editParent);

  function rebuildOptions(select, master, allowedTypes){
    var allow = Array.isArray(allowedTypes)
      ? allowedTypes.map(function(s){
          return String(s || '').toLowerCase();
        })
      : null;

    var emptyOpt = master.find(function(o){
      return o.value === '';
    }) || null;

    var list = [];

    if(emptyOpt) {
      list.push(emptyOpt);
    }

    master.forEach(function(o){
      if(!o.value) return;
      if(!allow) return;

      if(allow.indexOf(String(o.type || '').toLowerCase()) > -1){
        list.push(o);
      }
    });

    select.innerHTML = '';

    list.forEach(function(it){
      var el = document.createElement('option');

      el.value = it.value;
      el.text = it.text;

      if(it.type) {
        el.dataset.type = it.type;
      }

      if(it.owner) {
        el.dataset.owner = it.owner;
      }

      if(it.ownerType) {
        el.dataset.ownerType = it.ownerType;
      }

      select.appendChild(el);
    });

    select.value = '';

    if(window.jQuery && $(select).hasClass('select2-hidden-accessible')){
      $(select).val('').trigger('change');
    }
  }

  function rebuildOptionsByOwner(select, master, allowedTypes, ownerId){
    var allow = Array.isArray(allowedTypes)
      ? allowedTypes.map(function(s){
          return String(s || '').toLowerCase();
        })
      : null;

    var emptyOpt = master.find(function(o){
      return o.value === '';
    }) || null;

    var list = [];

    if(emptyOpt) {
      list.push(emptyOpt);
    }

    master.forEach(function(o){
      if(!o.value) return;

      if(allow && allow.indexOf(String(o.type || '').toLowerCase()) === -1) return;
      if(ownerId && String(o.owner || '') !== String(ownerId)) return;

      list.push(o);
    });

    select.innerHTML = '';

    list.forEach(function(it){
      var el = document.createElement('option');

      el.value = it.value;
      el.text = it.text;

      if(it.type) {
        el.dataset.type = it.type;
      }

      if(it.owner) {
        el.dataset.owner = it.owner;
      }

      if(it.ownerType) {
        el.dataset.ownerType = it.ownerType;
      }

      select.appendChild(el);
    });

    select.value = '';

    if(window.jQuery && $(select).hasClass('select2-hidden-accessible')){
      $(select).val('').trigger('change');
    }
  }

  function toggleParentUI(modalSelector, show, hintText, required){
    var parentSelect = modalSelector === '#modalCreateLocation' ? $createParent : $editParent;
    var hintEl = document.querySelector(modalSelector === '#modalCreateLocation' ? '#create_parent_hint' : '#edit_parent_hint');
    var group = parentSelect.closest('.col-md-6') || parentSelect.parentElement;

    if(group) {
      group.style.display = show ? '' : 'none';
    }

    if(hintEl) {
      hintEl.textContent = show ? (hintText || '') : '';
    }

    parentSelect.disabled = !show;

    if(required && show) {
      parentSelect.setAttribute('required', 'required');
    } else {
      parentSelect.removeAttribute('required');
    }
  }

  function toggleWarehouseUI(modalSelector, show){
    var group = document.querySelector(modalSelector === '#modalCreateLocation' ? '#create_warehouse_group' : '#edit_warehouse_group');
    var select = document.querySelector(modalSelector === '#modalCreateLocation' ? '#create_warehouse' : '#edit_warehouse');

    if(!group || !select) return;

    group.style.display = show ? '' : 'none';

    select.disabled = !show;

    if(show){
      select.setAttribute('required', 'required');
    } else {
      select.removeAttribute('required');
      select.value = '';

      if(window.jQuery){
        jQuery(select).val('').trigger('change');
      }
    }
  }

  function toggleStoreUI(modalSelector, show){
    var group = document.querySelector(modalSelector === '#modalCreateLocation' ? '#create_store_group' : '#edit_store_group');
    var select = document.querySelector(modalSelector === '#modalCreateLocation' ? '#create_store' : '#edit_store');

    if(!group || !select) return;

    group.style.display = show ? '' : 'none';

    if(show){
      select.setAttribute('required', 'required');
    } else {
      select.removeAttribute('required');
      select.value = '';

      if(window.jQuery){
        jQuery(select).val('').trigger('change');
      }
    }
  }

  window._editOwnerId = null;
  window._editCurrentParentId = '';
  window._editCurrentWarehouseId = '';

  /* ---------- Parent Rules ---------- */
  window.applyParentRules = function(modalSelector){
    var typeEl = document.querySelector(modalSelector === '#modalCreateLocation' ? '#create_type' : '#edit_type');
    var parentEl = modalSelector === '#modalCreateLocation' ? $createParent : $editParent;
    var master = modalSelector === '#modalCreateLocation' ? masterCreate : masterEdit;
    var type = (typeEl ? typeEl.value : '').toLowerCase();

    if(type === 'warehouse'){
      rebuildOptions(parentEl, master, null);
      toggleParentUI(modalSelector, false, '', false);

    } else if(type === 'room'){
      rebuildOptions(parentEl, master, ['warehouse']);
      toggleParentUI(modalSelector, true, 'Pilih Warehouse yang menjadi lokasi induk.', true);

    } else if(type === 'store_display'){
      rebuildOptions(parentEl, master, ['warehouse']);
      toggleParentUI(modalSelector, true, 'Pilih Warehouse yang menjadi lokasi induk.', true);

    } else if(type === 'rack' || type === 'staging'){
      var ownerId = (modalSelector === '#modalEditLocation') ? (window._editOwnerId || null) : null;

      if(ownerId){
        rebuildOptionsByOwner(parentEl, master, ['room', 'store_display'], ownerId);
      } else {
        rebuildOptions(parentEl, master, ['room', 'store_display']);
      }

      toggleParentUI(modalSelector, true, 'Pilih Room atau Store Display sebagai lokasi induk.', true);

    } else if(type === 'layer'){
      var ownerId2 = (modalSelector === '#modalEditLocation') ? (window._editOwnerId || null) : null;

      if(ownerId2){
        rebuildOptionsByOwner(parentEl, master, ['rack', 'staging'], ownerId2);
      } else {
        rebuildOptions(parentEl, master, ['rack', 'staging']);
      }

      toggleParentUI(modalSelector, true, 'Pilih Rack atau Staging sebagai lokasi induk.', true);

    } else {
      rebuildOptions(parentEl, master, null);
      toggleParentUI(modalSelector, false, '', false);
    }

    toggleWarehouseUI(modalSelector, type === 'warehouse');
    toggleStoreUI(modalSelector, false);

    if(modalSelector === '#modalEditLocation'){
      restoreEditValues();
    }
  };

  document.getElementById('create_type').addEventListener('change', function(){
    window.applyParentRules('#modalCreateLocation');
  });

  var createWarehouseSelect = document.getElementById('create_warehouse');

  if(createWarehouseSelect){
    createWarehouseSelect.addEventListener('change', function(){
      syncCreateWarehouseName();
    });
  }

  /*
   * Isi kembali nama saat halaman terbuka ulang karena validasi gagal.
   */
  syncCreateWarehouseName();

  document.getElementById('edit_type').addEventListener('change', function(){
    window._editCurrentParentId = '';
    window._editCurrentWarehouseId = '';
    window.applyParentRules('#modalEditLocation');
  });

  /* ---------- Delegasi aksi ---------- */
  document.getElementById('locations-table').addEventListener('click', function(e){
    var target = e.target.closest('.act-edit,.act-delete,.act-add-child,.act-qr');

    if(!target) return;

    if(target.classList.contains('act-add-child')){
      var nextType = (target.dataset.next || '').toLowerCase();
      var parentId = target.dataset.parentId || '';
      var parentName = target.dataset.parentName || '';
      var label = (nextType === 'store_display')
        ? 'Store Display'
        : (nextType.charAt(0).toUpperCase() + nextType.slice(1));

      document.getElementById('modalQuickAddLabel').textContent = 'Tambah ' + label;
      document.getElementById('qa_type').value = nextType;
      document.getElementById('qa_parent_id').value = parentId;
      document.getElementById('qa_parent_info').textContent = parentName;
      /*
       * Setiap klik Tambah Room/Rack/Staging/Layer dimulai dari form kosong.
       * Data old() hanya dipakai saat modal otomatis dibuka karena validasi gagal.
       */
      document.getElementById('qa_name').value = '';
      document.getElementById('qa_code').value = '';

      new bootstrap.Modal(document.getElementById('modalQuickAdd')).show();
      return;
    }

    if(target.classList.contains('act-qr')){
      var url = target.dataset.qrUrl;
      var name = target.dataset.name || '';
      var code = target.dataset.code || '';
      var imgEl = document.getElementById('qrImg');
      var meta = document.getElementById('qrMeta');
      var dl = document.getElementById('qrDownload');

      imgEl.src = url;
      meta.textContent = (name ? name + ' ' : '') + (code ? '(' + code + ')' : '');
      dl.href = url;
      dl.download = (code ? ('QR-' + code) : 'QR-Location') + '.svg';

      new bootstrap.Modal(document.getElementById('modalShowQr')).show();
      return;
    }

    if(target.classList.contains('act-edit')){
      var formEdit = document.getElementById('formEditLocation');

      formEdit.action = target.dataset.updateUrl || '#';

      document.getElementById('edit_id').value = target.dataset.id || '';
      document.getElementById('edit_name').value = target.dataset.name || '';
      document.getElementById('edit_code').value = target.dataset.code || '';
      document.getElementById('edit_type').value = target.dataset.type || '';

      window._editOwnerId = target.dataset.ownerId || null;
      document.getElementById('edit_owner_root_id').value = target.dataset.ownerId || '';
      window._editCurrentParentId = target.dataset.parentId || '';
      window._editCurrentWarehouseId = target.dataset.warehouseId || '';

      window.applyParentRules('#modalEditLocation');
      restoreEditValues();

      new bootstrap.Modal(document.getElementById('modalEditLocation')).show();
      return;
    }

    if(target.classList.contains('act-delete')){
      if(!confirm('Yakin ingin menghapus lokasi ini? Data turunan dapat ikut terpengaruh.')) return;

      var formDel = document.getElementById('formDeleteLocation');
      formDel.action = target.dataset.deleteUrl || '#';
      formDel.submit();
    }
  });

})(window.jQuery);
</script>
@endpush
