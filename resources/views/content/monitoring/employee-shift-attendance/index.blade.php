@extends('layouts/contentNavbarLayout')

@section('title', 'Monitoring - Shift Kerja Karyawan')

@push('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush

@push('page-style')
<style>
  :root {
    --sk-primary: #5b5cf6;
    --sk-primary-dark: #4338ca;
    --sk-primary-soft: rgba(91, 92, 246, .12);
    --sk-success: #22c55e;
    --sk-success-soft: rgba(34, 197, 94, .14);
    --sk-info: #06b6d4;
    --sk-info-soft: rgba(6, 182, 212, .14);
    --sk-warning: #f59e0b;
    --sk-warning-soft: rgba(245, 158, 11, .16);
    --sk-danger: #ef4444;
    --sk-danger-soft: rgba(239, 68, 68, .14);
    --sk-purple: #8b5cf6;
    --sk-dark: #1e293b;
    --sk-muted: #64748b;
    --sk-border: #e5e7eb;
    --sk-body: #f6f7fb;
    --sk-card: #ffffff;
  }

  body {
    background:
      radial-gradient(circle at top left, rgba(91, 92, 246, .10), transparent 30%),
      radial-gradient(circle at top right, rgba(6, 182, 212, .10), transparent 28%),
      var(--sk-body);
  }

  .select2-container {
    width: 100% !important;
  }

  .select2-container--bootstrap-5 .select2-selection {
    min-height: 40px;
    border-radius: .75rem;
    border-color: #dbe1ea;
    box-shadow: none;
  }

  .select2-container--bootstrap-5.select2-container--focus .select2-selection,
  .form-control:focus,
  .form-select:focus {
    border-color: var(--sk-primary);
    box-shadow: 0 0 0 .18rem rgba(91, 92, 246, .14);
  }

  .form-control,
  .form-select {
    border-radius: .75rem;
    border-color: #dbe1ea;
  }

  .dashboard-hero {
    position: relative;
    overflow: hidden;
    border-radius: 1.4rem;
    color: #fff;
    background:
      radial-gradient(circle at 15% 20%, rgba(255,255,255,.30), transparent 25%),
      linear-gradient(135deg, #4f46e5 0%, #06b6d4 52%, #22c55e 120%);
    box-shadow: 0 1.25rem 2.5rem rgba(79, 70, 229, .22);
    border: 0;
  }

  .dashboard-hero::before {
    content: "";
    position: absolute;
    width: 260px;
    height: 260px;
    right: -80px;
    top: -100px;
    border-radius: 50%;
    background: rgba(255,255,255,.18);
  }

  .dashboard-hero::after {
    content: "";
    position: absolute;
    width: 180px;
    height: 180px;
    right: 120px;
    bottom: -120px;
    border-radius: 50%;
    background: rgba(255,255,255,.13);
  }

  .dashboard-hero > .d-flex {
    position: relative;
    z-index: 2;
  }

  .dashboard-hero h4,
  .dashboard-hero .text-muted,
  .dashboard-hero .text-muted.fw-light {
    color: #fff !important;
  }

  .hero-subtitle {
    color: rgba(255,255,255,.84) !important;
  }

  .hero-badge {
    border-radius: 999px;
    padding: .55rem .85rem;
    background: rgba(255,255,255,.18);
    color: #fff;
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.22);
    font-weight: 600;
    font-size: .78rem;
  }

  .live-dot {
    display: inline-block;
    width: .55rem;
    height: .55rem;
    border-radius: 999px;
    background: #22c55e;
    box-shadow: 0 0 0 .35rem rgba(34,197,94,.20);
    animation: pulseLive 1.4s infinite;
  }

  @keyframes pulseLive {
    0% { box-shadow: 0 0 0 0 rgba(34,197,94,.38); }
    70% { box-shadow: 0 0 0 .45rem rgba(34,197,94,0); }
    100% { box-shadow: 0 0 0 0 rgba(34,197,94,0); }
  }

  .card {
    border: 0;
    border-radius: 1.15rem;
    box-shadow: 0 .5rem 1.5rem rgba(15, 23, 42, .06);
  }

  .card-header {
    border-bottom: 1px solid rgba(226, 232, 240, .8);
    background: transparent;
    padding: 1.25rem 1.35rem;
  }

  .card-body {
    padding: 1.35rem;
  }

  .filter-box {
    background: linear-gradient(180deg, rgba(248, 250, 252, .98), rgba(255,255,255,.98));
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    padding: 1.15rem;
  }

  .filter-actions {
    display: flex;
    gap: .6rem;
    flex-wrap: wrap;
  }

  .btn {
    border-radius: .75rem;
    font-weight: 600;
  }

  .btn-primary {
    border-color: var(--sk-primary);
    background: linear-gradient(135deg, var(--sk-primary), var(--sk-purple));
    box-shadow: 0 .45rem 1rem rgba(91, 92, 246, .22);
  }

  .btn-primary:hover {
    border-color: var(--sk-primary-dark);
    background: linear-gradient(135deg, var(--sk-primary-dark), var(--sk-primary));
  }

  .btn-success {
    border-color: var(--sk-success);
    background: linear-gradient(135deg, var(--sk-success), #16a34a);
    box-shadow: 0 .45rem 1rem rgba(34, 197, 94, .18);
  }

  .btn-outline-secondary {
    background: #fff;
    border-color: #d7deea;
    color: #475569;
  }

  .btn-outline-secondary:hover {
    background: #f1f5f9;
    color: #334155;
  }

  .summary-card {
    position: relative;
    overflow: hidden;
    height: 100%;
    border: 1px solid rgba(226, 232, 240, .85);
    border-radius: 1.15rem;
    background: var(--sk-card);
    transition: .22s ease;
  }

  .summary-card::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 4px;
    background: linear-gradient(90deg, var(--sk-primary), var(--sk-info));
  }

  .summary-card::after {
    content: "";
    position: absolute;
    width: 90px;
    height: 90px;
    right: -36px;
    bottom: -36px;
    border-radius: 50%;
    background: var(--sk-primary-soft);
  }

  .summary-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 1rem 2rem rgba(15, 23, 42, .10);
  }

  .summary-card h4 {
    color: var(--sk-dark);
    font-weight: 800;
    letter-spacing: -.02em;
  }

  .summary-card .text-muted {
    color: var(--sk-muted) !important;
  }

  .summary-icon {
    width: 50px;
    height: 50px;
    border-radius: 1rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--sk-primary), var(--sk-primary-dark));
    color: #fff;
    font-size: 25px;
    flex-shrink: 0;
    box-shadow: 0 .65rem 1.25rem rgba(91, 92, 246, .22);
  }

  .summary-icon.success {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    box-shadow: 0 .65rem 1.25rem rgba(34, 197, 94, .22);
  }

  .summary-icon.info {
    background: linear-gradient(135deg, #06b6d4, #0284c7);
    box-shadow: 0 .65rem 1.25rem rgba(6, 182, 212, .22);
  }

  .summary-icon.warning {
    background: linear-gradient(135deg, #f59e0b, #f97316);
    box-shadow: 0 .65rem 1.25rem rgba(245, 158, 11, .22);
  }

  .summary-icon.danger {
    background: linear-gradient(135deg, #ec4899, #ef4444);
    box-shadow: 0 .65rem 1.25rem rgba(236, 72, 153, .22);
  }

  .chart-card {
    height: 100%;
    border: 1px solid rgba(226, 232, 240, .85);
    border-radius: 1.15rem;
    background: #fff;
    overflow: hidden;
  }

  .chart-card .card-header h5 {
    color: var(--sk-dark);
    font-weight: 800;
  }

  .chart-card .card-header small {
    color: var(--sk-muted) !important;
  }

  .chart-wrapper {
    position: relative;
    height: 330px;
    min-height: 330px;
    width: 100%;
  }

  .chart-wrapper-sm {
    position: relative;
    height: 295px;
    min-height: 295px;
    width: 100%;
  }

  .mini-list {
    max-height: 430px;
    overflow: auto;
    padding-top: .25rem;
  }

  .mini-list::-webkit-scrollbar {
    width: 6px;
  }

  .mini-list::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 999px;
  }

  .mini-list-item {
    position: relative;
    border: 1px solid transparent;
    border-radius: .95rem;
    padding: .85rem .9rem;
    margin-bottom: .65rem;
    background: linear-gradient(180deg, #ffffff, #f8fafc);
    transition: .18s ease;
  }

  .mini-list-item:hover {
    border-color: #dbeafe;
    transform: translateX(2px);
    box-shadow: 0 .5rem 1rem rgba(15, 23, 42, .06);
  }

  .table-responsive {
    border-radius: 1rem;
    border: 1px solid #e5e7eb;
  }

  .table {
    margin-bottom: 0;
  }

  .table th,
  .table td {
    vertical-align: middle;
  }

  .table-nowrap th,
  .table-nowrap td {
    white-space: nowrap;
  }

  .shift-table thead th {
    background: linear-gradient(180deg, #f8fafc, #eef2ff);
    color: #334155;
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .035em;
    font-weight: 800;
    border-bottom: 1px solid #dbe1ea;
  }

  .shift-table tbody td {
    color: #475569;
  }

  .shift-table tbody tr {
    transition: .16s ease;
  }

  .shift-table tbody tr:hover {
    background-color: rgba(91, 92, 246, .055);
  }

  .pagination-info {
    font-size: .875rem;
    color: var(--sk-muted);
  }

  .badge-soft-success {
    background: var(--sk-success-soft) !important;
    color: #15803d !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-danger {
    background: var(--sk-danger-soft) !important;
    color: #b91c1c !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-warning {
    background: var(--sk-warning-soft) !important;
    color: #b45309 !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-secondary {
    background: #f1f5f9 !important;
    color: #475569 !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-info {
    background: var(--sk-info-soft) !important;
    color: #0369a1 !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .row-late td {
    background-color: rgba(245, 158, 11, .08) !important;
  }

  .row-overtime td {
    background-color: rgba(6, 182, 212, .08) !important;
  }

  .row-late-overtime td {
    background-color: rgba(236, 72, 153, .08) !important;
  }

  .employee-col,
  .store-col,
  .note-col {
    min-width: 230px;
    white-space: normal !important;
  }


  .insight-card {
    position: relative;
    height: 100%;
    overflow: hidden;
    border: 1px solid rgba(226, 232, 240, .9);
    border-radius: 1.15rem;
    background: linear-gradient(180deg, #ffffff, #f8fafc);
  }

  .insight-card::before {
    content: "";
    position: absolute;
    inset: 0 auto 0 0;
    width: 5px;
    background: linear-gradient(180deg, var(--sk-primary), var(--sk-info));
  }

  .insight-value {
    color: var(--sk-dark);
    font-size: 1.65rem;
    font-weight: 850;
    line-height: 1.15;
    letter-spacing: -.03em;
  }

  .insight-label {
    color: var(--sk-muted);
    font-size: .78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .045em;
  }

  .insight-note {
    color: #64748b;
    font-size: .82rem;
  }

  .management-card {
    border: 1px solid rgba(226, 232, 240, .9);
    border-radius: 1.15rem;
    background: #fff;
    height: 100%;
  }

  .management-card .card-header h5 {
    color: var(--sk-dark);
    font-weight: 850;
  }

  .management-card .card-header small {
    color: var(--sk-muted) !important;
  }

  .metric-pill {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    border-radius: 999px;
    padding: .35rem .6rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #475569;
    font-size: .76rem;
    font-weight: 700;
    white-space: nowrap;
  }

  .metric-pill.warning {
    background: var(--sk-warning-soft);
    border-color: rgba(245, 158, 11, .25);
    color: #92400e;
  }

  .metric-pill.danger {
    background: var(--sk-danger-soft);
    border-color: rgba(239, 68, 68, .22);
    color: #991b1b;
  }

  .metric-pill.info {
    background: var(--sk-info-soft);
    border-color: rgba(6, 182, 212, .22);
    color: #075985;
  }

  .metric-pill.success {
    background: var(--sk-success-soft);
    border-color: rgba(34, 197, 94, .22);
    color: #166534;
  }

  .demography-list {
    display: grid;
    gap: .85rem;
  }

  .demography-item {
    border: 1px solid #e5e7eb;
    border-radius: 1rem;
    padding: .9rem;
    background: linear-gradient(180deg, #ffffff, #f8fafc);
  }

  .rank-badge {
    width: 32px;
    height: 32px;
    border-radius: .85rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: .8rem;
    font-weight: 850;
    background: linear-gradient(135deg, var(--sk-primary), var(--sk-purple));
    box-shadow: 0 .45rem .9rem rgba(91, 92, 246, .22);
    flex-shrink: 0;
  }

  .progress-thin {
    height: .45rem;
    border-radius: 999px;
    background-color: #e2e8f0;
    overflow: hidden;
  }

  .progress-thin .progress-bar {
    border-radius: 999px;
  }

  .risk-indicator {
    width: 10px;
    height: 10px;
    display: inline-block;
    border-radius: 999px;
    background: #22c55e;
    box-shadow: 0 0 0 4px rgba(34, 197, 94, .12);
  }

  .risk-indicator.medium {
    background: #f59e0b;
    box-shadow: 0 0 0 4px rgba(245, 158, 11, .14);
  }

  .risk-indicator.high {
    background: #ef4444;
    box-shadow: 0 0 0 4px rgba(239, 68, 68, .14);
  }


  .holding-schedule-card {
    border: 1px solid rgba(226, 232, 240, .95);
    border-radius: 1.15rem;
    background: #fff;
    height: 100%;
    overflow: hidden;
  }

  .holding-schedule-card .card-header h5 {
    color: var(--sk-dark);
    font-weight: 850;
  }

  .schedule-branch-card {
    border: 1px solid #e5e7eb;
    border-radius: 1rem;
    background: linear-gradient(180deg, #ffffff, #f8fafc);
    padding: 1rem;
    height: 100%;
  }

  .schedule-branch-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: .75rem;
    margin-bottom: .85rem;
  }

  .schedule-employee-list {
    display: grid;
    gap: .6rem;
    max-height: 420px;
    overflow: auto;
    padding-right: .25rem;
  }

  .schedule-employee-item {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: .75rem;
    border: 1px solid #e5e7eb;
    border-left-width: 5px;
    border-radius: .9rem;
    padding: .75rem;
    background: #fff;
  }

  .schedule-status-present { border-left-color: #22c55e; background: rgba(34, 197, 94, .045); }
  .schedule-status-scheduled { border-left-color: #64748b; background: rgba(100, 116, 139, .045); }
  .schedule-status-absent { border-left-color: #ef4444; background: rgba(239, 68, 68, .055); }
  .schedule-status-late { border-left-color: #f59e0b; background: rgba(245, 158, 11, .07); }
  .schedule-status-overtime { border-left-color: #06b6d4; background: rgba(6, 182, 212, .06); }
  .schedule-status-late_overtime { border-left-color: #8b5cf6; background: rgba(139, 92, 246, .07); }
  .schedule-status-not_clock_in,
  .schedule-status-not_clock_out { border-left-color: #ec4899; background: rgba(236, 72, 153, .06); }
  .schedule-status-day_off,
  .schedule-status-holiday { border-left-color: #94a3b8; background: rgba(148, 163, 184, .08); }

  .status-dot {
    width: .75rem;
    height: .75rem;
    border-radius: 999px;
    display: inline-block;
    flex-shrink: 0;
    box-shadow: 0 0 0 4px rgba(148, 163, 184, .14);
  }

  .status-dot.present { background: #22c55e; box-shadow: 0 0 0 4px rgba(34, 197, 94, .14); }
  .status-dot.scheduled { background: #64748b; }
  .status-dot.absent { background: #ef4444; box-shadow: 0 0 0 4px rgba(239, 68, 68, .14); }
  .status-dot.late { background: #f59e0b; box-shadow: 0 0 0 4px rgba(245, 158, 11, .16); }
  .status-dot.overtime { background: #06b6d4; box-shadow: 0 0 0 4px rgba(6, 182, 212, .14); }
  .status-dot.late_overtime { background: #8b5cf6; box-shadow: 0 0 0 4px rgba(139, 92, 246, .16); }
  .status-dot.not_clock_in,
  .status-dot.not_clock_out { background: #ec4899; box-shadow: 0 0 0 4px rgba(236, 72, 153, .14); }
  .status-dot.day_off,
  .status-dot.holiday { background: #94a3b8; }

  .status-legend {
    display: flex;
    gap: .55rem;
    flex-wrap: wrap;
    align-items: center;
  }

  .status-legend-item {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    font-size: .76rem;
    color: #475569;
    font-weight: 700;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 999px;
    padding: .35rem .6rem;
  }

  .schedule-date-chip {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    border-radius: 999px;
    padding: .35rem .55rem;
    font-size: .74rem;
    font-weight: 800;
    color: #334155;
    background: #eef2ff;
  }

  .schedule-calendar-wrapper {
    display: grid;
    gap: 1.25rem;
  }

  .schedule-calendar-branch {
    border: 1px solid #e5e7eb;
    border-radius: 1.1rem;
    background: linear-gradient(180deg, #ffffff, #f8fafc);
    overflow: hidden;
  }

  .schedule-calendar-branch-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem 1.1rem;
    border-bottom: 1px solid #e5e7eb;
    background: linear-gradient(180deg, #ffffff, #f8fafc);
  }

  .schedule-calendar-scroll {
    overflow-x: auto;
    overflow-y: hidden;
    padding: 1rem;
  }

  .schedule-calendar-scroll::-webkit-scrollbar {
    height: 8px;
  }

  .schedule-calendar-scroll::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 999px;
  }

  .schedule-calendar-scroll::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 999px;
  }

  .schedule-calendar-grid {
    display: grid;
    grid-auto-flow: column;
    grid-auto-columns: minmax(285px, 320px);
    gap: 1rem;
    align-items: stretch;
    min-height: 100%;
  }

  .schedule-day-card {
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    background: #fff;
    min-height: 360px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: 0 .45rem 1rem rgba(15, 23, 42, .045);
  }

  .schedule-day-header {
    padding: .9rem 1rem;
    background: linear-gradient(135deg, rgba(91, 92, 246, .10), rgba(6, 182, 212, .10));
    border-bottom: 1px solid #e2e8f0;
  }

  .schedule-day-number {
    width: 44px;
    height: 44px;
    border-radius: 1rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    background: linear-gradient(135deg, var(--sk-primary), var(--sk-purple));
    font-weight: 900;
    font-size: 1.05rem;
    box-shadow: 0 .55rem 1.1rem rgba(91, 92, 246, .22);
    flex-shrink: 0;
  }

  .schedule-day-body {
    display: grid;
    gap: .75rem;
    padding: .9rem;
    align-content: start;
  }

  .schedule-shift-block {
    border: 1px solid #e5e7eb;
    border-radius: .95rem;
    background: linear-gradient(180deg, #ffffff, #f8fafc);
    overflow: hidden;
  }

  .schedule-shift-header {
    display: flex;
    justify-content: space-between;
    gap: .75rem;
    align-items: center;
    padding: .7rem .8rem;
    border-bottom: 1px solid #e5e7eb;
    background: #f8fafc;
  }

  .schedule-shift-employees {
    display: grid;
    gap: .45rem;
    padding: .7rem;
  }

  .schedule-employee-chip {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: .6rem;
    border: 1px solid #e5e7eb;
    border-left-width: 5px;
    border-radius: .85rem;
    background: #fff;
    padding: .55rem .6rem;
  }

  .schedule-employee-chip.schedule-status-present { border-left-color: #22c55e; background: rgba(34, 197, 94, .045); }
  .schedule-employee-chip.schedule-status-scheduled { border-left-color: #64748b; background: rgba(100, 116, 139, .045); }
  .schedule-employee-chip.schedule-status-absent { border-left-color: #ef4444; background: rgba(239, 68, 68, .055); }
  .schedule-employee-chip.schedule-status-late { border-left-color: #f59e0b; background: rgba(245, 158, 11, .07); }
  .schedule-employee-chip.schedule-status-overtime { border-left-color: #06b6d4; background: rgba(6, 182, 212, .06); }
  .schedule-employee-chip.schedule-status-late_overtime { border-left-color: #8b5cf6; background: rgba(139, 92, 246, .07); }
  .schedule-employee-chip.schedule-status-not_clock_in,
  .schedule-employee-chip.schedule-status-not_clock_out { border-left-color: #ec4899; background: rgba(236, 72, 153, .06); }
  .schedule-employee-chip.schedule-status-day_off,
  .schedule-employee-chip.schedule-status-holiday { border-left-color: #94a3b8; background: rgba(148, 163, 184, .08); }

  .schedule-empty-day {
    border: 1px dashed #cbd5e1;
    border-radius: .95rem;
    padding: 1rem;
    text-align: center;
    color: #64748b;
    background: #f8fafc;
  }

  .shift-count-badge {
    border-radius: 999px;
    padding: .25rem .5rem;
    background: #eef2ff;
    color: #4338ca;
    font-size: .72rem;
    font-weight: 800;
    white-space: nowrap;
  }


  .schedule-day-card.compact {
    min-height: 245px;
  }

  .schedule-day-summary {
    display: grid;
    gap: .75rem;
    padding: .9rem;
  }

  .day-analysis-card {
    border: 1px solid #e5e7eb;
    border-radius: 1rem;
    background: linear-gradient(180deg, #ffffff, #f8fafc);
    padding: .85rem;
  }

  .day-analysis-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .6rem;
  }

  .day-analysis-item {
    border-radius: .9rem;
    padding: .7rem;
    border: 1px solid #e2e8f0;
    background: #fff;
  }

  .day-analysis-item .value {
    font-size: 1.3rem;
    font-weight: 900;
    line-height: 1;
    color: #1e293b;
  }

  .day-analysis-item .label {
    margin-top: .25rem;
    color: #64748b;
    font-size: .72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .035em;
  }

  .day-analysis-item.danger {
    background: rgba(239, 68, 68, .06);
    border-color: rgba(239, 68, 68, .18);
  }

  .day-analysis-item.warning {
    background: rgba(245, 158, 11, .08);
    border-color: rgba(245, 158, 11, .22);
  }

  .day-analysis-item.info {
    background: rgba(6, 182, 212, .07);
    border-color: rgba(6, 182, 212, .20);
  }

  .day-analysis-item.success {
    background: rgba(34, 197, 94, .07);
    border-color: rgba(34, 197, 94, .20);
  }

  .day-detail-button {
    width: 100%;
    justify-content: center;
    border-radius: .85rem;
    padding: .62rem .75rem;
    white-space: nowrap;
    box-shadow: 0 .55rem 1.05rem rgba(91, 92, 246, .18);
  }

  .schedule-modal-summary {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: .75rem;
    margin-bottom: 1rem;
  }

  .schedule-modal-summary-item {
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    background: #fff;
    padding: .85rem;
  }

  .schedule-modal-summary-item .value {
    color: #1e293b;
    font-size: 1.4rem;
    font-weight: 900;
    line-height: 1;
  }

  .schedule-modal-summary-item .label {
    color: #64748b;
    font-size: .72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-top: .35rem;
  }

  .schedule-modal-list {
    display: grid;
    gap: 1rem;
  }

  .schedule-modal-shift {
    border: 1px solid #e5e7eb;
    border-radius: 1.1rem;
    overflow: hidden;
    background: #fff;
    box-shadow: 0 .45rem 1rem rgba(15, 23, 42, .045);
  }

  .schedule-modal-shift-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: .85rem;
    padding: 1rem 1.1rem;
    background: linear-gradient(135deg, rgba(91, 92, 246, .09), rgba(6, 182, 212, .08));
    border-bottom: 1px solid #e5e7eb;
  }

  .schedule-modal-employees {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .75rem;
    padding: 1rem;
    max-height: 520px;
    overflow: auto;
  }

  .schedule-modal-employee {
    display: flex;
    justify-content: space-between;
    gap: .85rem;
    border: 1px solid #e5e7eb;
    border-left-width: 5px;
    border-radius: 1rem;
    padding: .85rem;
    background: #fff;
  }

  .schedule-modal-employee.schedule-status-present { border-left-color: #22c55e; background: rgba(34, 197, 94, .045); }
  .schedule-modal-employee.schedule-status-scheduled { border-left-color: #64748b; background: rgba(100, 116, 139, .045); }
  .schedule-modal-employee.schedule-status-absent { border-left-color: #ef4444; background: rgba(239, 68, 68, .055); }
  .schedule-modal-employee.schedule-status-late { border-left-color: #f59e0b; background: rgba(245, 158, 11, .07); }
  .schedule-modal-employee.schedule-status-overtime { border-left-color: #06b6d4; background: rgba(6, 182, 212, .06); }
  .schedule-modal-employee.schedule-status-late_overtime { border-left-color: #8b5cf6; background: rgba(139, 92, 246, .07); }
  .schedule-modal-employee.schedule-status-not_clock_in,
  .schedule-modal-employee.schedule-status-not_clock_out { border-left-color: #ec4899; background: rgba(236, 72, 153, .06); }
  .schedule-modal-employee.schedule-status-day_off,
  .schedule-modal-employee.schedule-status-holiday { border-left-color: #94a3b8; background: rgba(148, 163, 184, .08); }

  .schedule-modal-meta {
    display: flex;
    gap: .45rem;
    flex-wrap: wrap;
    margin-top: .45rem;
  }

  @media (max-width: 991.98px) {
    .schedule-modal-summary,
    .schedule-modal-employees {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 575.98px) {
    .day-analysis-grid {
      grid-template-columns: 1fr;
    }

    .schedule-modal-employee {
      flex-direction: column;
    }
  }


  @media (max-width: 767.98px) {
    .dashboard-hero {
      border-radius: 1rem;
    }

    .chart-wrapper,
    .chart-wrapper-sm {
      height: 260px;
      min-height: 260px;
    }

    .card-body {
      padding: 1rem;
    }
  }
</style>
@endpush

@php
  $summary = $summary ?? (object) [
    'total_attendance' => 0,
    'total_employee' => 0,
    'total_store' => 0,
    'total_late' => 0,
    'total_overtime' => 0,
    'total_not_clock_in' => 0,
    'total_not_clock_out' => 0,
    'total_late_minutes' => 0,
    'total_overtime_minutes' => 0,
    'total_work_minutes' => 0,
  ];

  $totalAttendance = (float) ($summary->total_attendance ?? 0);
  $totalEmployee = (float) ($summary->total_employee ?? 0);
  $totalStore = (float) ($summary->total_store ?? 0);
  $totalLate = (float) ($summary->total_late ?? 0);
  $totalOvertime = (float) ($summary->total_overtime ?? 0);
  $totalNotClockIn = (float) ($summary->total_not_clock_in ?? 0);
  $totalNotClockOut = (float) ($summary->total_not_clock_out ?? 0);
  $totalLateMinutes = (float) ($summary->total_late_minutes ?? 0);
  $totalOvertimeMinutes = (float) ($summary->total_overtime_minutes ?? 0);
  $totalWorkMinutes = (float) ($summary->total_work_minutes ?? 0);

  /**
   * Fallback variable chart/list.
   * Bagian ini hanya mencegah error undefined variable jika controller lama
   * belum mengirim semua data chart ke view.
   */
  $dailyLabels = collect($dailyLabels ?? []);
  $dailyAttendances = collect($dailyAttendances ?? []);
  $dailyLates = collect($dailyLates ?? []);
  $dailyOvertimes = collect($dailyOvertimes ?? []);

  $storeLabels = collect($storeLabels ?? []);
  $storeLates = collect($storeLates ?? []);
  $storeOvertimes = collect($storeOvertimes ?? []);

  $employeeLabels = collect($employeeLabels ?? []);
  $employeeLates = collect($employeeLates ?? []);
  $employeeOvertimes = collect($employeeOvertimes ?? []);

  $latestRows = collect($latestRows ?? []);

  $scheduleRows = collect($scheduleRows ?? []);
  $scheduleByStore = collect($scheduleByStore ?? []);

  if ($scheduleByStore->isEmpty() && $scheduleRows->isNotEmpty()) {
      $scheduleByStore = $scheduleRows
          ->groupBy(fn ($row) => (string) ($row->store_id ?? $row->store_name ?? 'unknown'))
          ->map(function ($items) {
              $first = $items->first();

              return (object) [
                  'store_id' => $first->store_id ?? null,
                  'store_name' => $first->store_name ?? 'Cabang Tidak Diketahui',
                  'store_code' => $first->store_code ?? null,
                  'total_schedule' => $items->count(),
                  'total_present' => $items->where('marker_status', 'present')->count(),
                  'total_scheduled' => $items->where('marker_status', 'scheduled')->count(),
                  'total_absent' => $items->where('marker_status', 'absent')->count(),
                  'total_late' => $items->whereIn('marker_status', ['late', 'late_overtime'])->count(),
                  'total_overtime' => $items->whereIn('marker_status', ['overtime', 'late_overtime'])->count(),
                  'total_incomplete_clock' => $items->whereIn('marker_status', ['not_clock_in', 'not_clock_out'])->count(),
                  'total_day_off' => $items->whereIn('marker_status', ['day_off', 'holiday'])->count(),
                  'items' => $items->values(),
              ];
          })
          ->values();
  }

  $scheduleCalendarByStore = collect($scheduleCalendarByStore ?? []);

  if ($scheduleCalendarByStore->isEmpty() && $scheduleRows->isNotEmpty()) {
      $scheduleCalendarByStore = $scheduleRows
          ->sortBy([
              ['store_name', 'asc'],
              ['schedule_date', 'asc'],
              ['shift_start_time', 'asc'],
              ['shift_name', 'asc'],
              ['employee_name', 'asc'],
          ])
          ->groupBy(fn ($row) => (string) ($row->store_id ?? $row->store_name ?? 'unknown'))
          ->map(function ($storeItems) {
              $firstStore = $storeItems->first();

              $dates = $storeItems
                  ->groupBy(fn ($row) => !empty($row->schedule_date) ? \Carbon\Carbon::parse($row->schedule_date)->toDateString() : 'tanpa-tanggal')
                  ->map(function ($dateItems, $dateKey) {
                      $firstDate = $dateItems->first();

                      $shifts = $dateItems
                          ->groupBy(function ($row) {
                              if ((bool) ($row->is_national_holiday ?? false)) {
                                  return 'holiday';
                              }

                              if ((bool) ($row->is_day_off ?? false)) {
                                  return 'day_off';
                              }

                              return (string) ($row->attendance_shift_id ?? $row->shift_name ?? 'tanpa-shift');
                          })
                          ->map(function ($shiftItems) {
                              $firstShift = $shiftItems->first();
                              $isHoliday = (bool) ($firstShift->is_national_holiday ?? false);
                              $isDayOff = (bool) ($firstShift->is_day_off ?? false);

                              return (object) [
                                  'shift_name' => $isHoliday
                                      ? 'Libur Nasional'
                                      : ($isDayOff ? 'Day Off / Libur' : ($firstShift->shift_name ?? 'Tanpa Shift')),
                                  'shift_code' => $firstShift->shift_code ?? null,
                                  'shift_start_time' => $firstShift->shift_start_time ?? null,
                                  'shift_end_time' => $firstShift->shift_end_time ?? null,
                                  'total_employee' => $shiftItems->count(),
                                  'items' => $shiftItems->sortBy('employee_name')->values(),
                              ];
                          })
                          ->values();

                      return (object) [
                          'date' => $dateKey,
                          'date_label' => !empty($firstDate->schedule_date)
                              ? \Carbon\Carbon::parse($firstDate->schedule_date)->translatedFormat('d F Y')
                              : 'Tanpa Tanggal',
                          'day_label' => !empty($firstDate->schedule_date)
                              ? \Carbon\Carbon::parse($firstDate->schedule_date)->translatedFormat('l')
                              : '-',
                          'day_number' => !empty($firstDate->schedule_date)
                              ? \Carbon\Carbon::parse($firstDate->schedule_date)->format('d')
                              : '-',
                          'total_employee' => $dateItems->count(),
                          'shifts' => $shifts,
                      ];
                  })
                  ->values();

              return (object) [
                  'store_id' => $firstStore->store_id ?? null,
                  'store_name' => $firstStore->store_name ?? 'Cabang Tidak Diketahui',
                  'store_code' => $firstStore->store_code ?? null,
                  'total_schedule' => $storeItems->count(),
                  'total_present' => $storeItems->where('marker_status', 'present')->count(),
                  'total_scheduled' => $storeItems->where('marker_status', 'scheduled')->count(),
                  'total_absent' => $storeItems->where('marker_status', 'absent')->count(),
                  'total_late' => $storeItems->whereIn('marker_status', ['late', 'late_overtime'])->count(),
                  'total_overtime' => $storeItems->whereIn('marker_status', ['overtime', 'late_overtime'])->count(),
                  'total_incomplete_clock' => $storeItems->whereIn('marker_status', ['not_clock_in', 'not_clock_out'])->count(),
                  'total_day_off' => $storeItems->whereIn('marker_status', ['day_off', 'holiday'])->count(),
                  'dates' => $dates,
              ];
          })
          ->values();
  }


  $scheduleMarkerLabel = function ($status) {
      return match((string) $status) {
          'present' => 'Hadir',
          'scheduled' => 'Terjadwal',
          'absent' => 'Tidak Masuk',
          'late' => 'Telat',
          'overtime' => 'Over Jam',
          'late_overtime' => 'Telat & Over',
          'not_clock_in' => 'Belum Clock In',
          'not_clock_out' => 'Belum Clock Out',
          'day_off' => 'Libur',
          'holiday' => 'Libur Nasional',
          default => 'Belum Ada Status',
      };
  };

  $scheduleMarkerBadge = function ($status) {
      return match((string) $status) {
          'present' => 'badge-soft-success',
          'scheduled' => 'badge-soft-secondary',
          'absent' => 'badge-soft-danger',
          'late' => 'badge-soft-warning',
          'overtime' => 'badge-soft-info',
          'late_overtime' => 'badge-soft-danger',
          'not_clock_in', 'not_clock_out' => 'badge-soft-danger',
          'day_off', 'holiday' => 'badge-soft-secondary',
          default => 'badge-soft-secondary',
      };
  };


  $safePercent = function ($value, $total, $decimals = 1) {
      $value = (float) $value;
      $total = (float) $total;

      if ($total <= 0) {
          return 0;
      }

      return round(($value / $total) * 100, $decimals);
  };

  $lateRate = $safePercent($totalLate, $totalAttendance);
  $overtimeRate = $safePercent($totalOvertime, $totalAttendance);
  $notClockInRate = $safePercent($totalNotClockIn, $totalAttendance);
  $notClockOutRate = $safePercent($totalNotClockOut, $totalAttendance);
  $completeClockRate = $safePercent(max($totalAttendance - $totalNotClockIn - $totalNotClockOut, 0), $totalAttendance);
  $avgLateMinutes = $totalLate > 0 ? round($totalLateMinutes / max($totalLate, 1), 1) : 0;
  $avgOvertimeMinutes = $totalOvertime > 0 ? round($totalOvertimeMinutes / max($totalOvertime, 1), 1) : 0;

  $rowsCollection = collect();

  if (isset($rows)) {
      if (is_object($rows) && method_exists($rows, 'items')) {
          $rowsCollection = collect($rows->items());
      } elseif ($rows instanceof \Illuminate\Support\Collection) {
          $rowsCollection = $rows;
      } else {
          $rowsCollection = collect($rows);
      }
  }

  $demographicRows = collect($clinicDemographics ?? []);

  if ($demographicRows->isEmpty() && $rowsCollection->isNotEmpty()) {
      $demographicRows = $rowsCollection
          ->groupBy(fn ($item) => (string) ($item->store_id ?? $item->store_name ?? 'unknown'))
          ->map(function ($items) use ($safePercent) {
              $first = $items->first();
              $total = $items->count();
              $late = $items->filter(fn ($item) => (bool) ($item->is_late ?? false))->count();
              $overtime = $items->filter(fn ($item) => (bool) ($item->is_overtime ?? false))->count();
              $notClockIn = $items->filter(fn ($item) => empty($item->clock_in_at))->count();
              $notClockOut = $items->filter(fn ($item) => empty($item->clock_out_at))->count();
              $uniqueEmployees = $items->pluck('user_id')->filter()->unique()->count();
              $lateMinutes = $items->sum(fn ($item) => (float) ($item->late_minutes ?? 0));
              $completeClock = max($total - $notClockIn - $notClockOut, 0);

              return (object) [
                  'store_name' => $first->store_name ?? 'Store Tidak Diketahui',
                  'store_code' => $first->store_code ?? null,
                  'total_attendance' => $total,
                  'total_employee' => $uniqueEmployees,
                  'total_late' => $late,
                  'late_minutes' => $lateMinutes,
                  'total_overtime' => $overtime,
                  'not_clock_in' => $notClockIn,
                  'not_clock_out' => $notClockOut,
                  'late_rate' => $safePercent($late, $total),
                  'complete_clock_rate' => $safePercent($completeClock, $total),
              ];
          })
          ->sortByDesc(fn ($item) => ((float) ($item->late_rate ?? 0) * 10) + (float) ($item->total_late ?? 0) + (float) ($item->total_overtime ?? 0))
          ->values();
  }

  $topLateEmployeeRows = collect($topLateEmployees ?? []);

  if ($topLateEmployeeRows->isEmpty() && $rowsCollection->isNotEmpty()) {
      $topLateEmployeeRows = $rowsCollection
          ->filter(fn ($item) => (bool) ($item->is_late ?? false))
          ->groupBy(fn ($item) => (string) ($item->user_id ?? $item->employee_name ?? 'unknown'))
          ->map(function ($items) use ($safePercent) {
              $first = $items->first();
              $total = $items->count();
              $late = $items->filter(fn ($item) => (bool) ($item->is_late ?? false))->count();
              $lateMinutes = $items->sum(fn ($item) => (float) ($item->late_minutes ?? 0));

              return (object) [
                  'employee_name' => $first->employee_name ?? 'Karyawan Tidak Diketahui',
                  'employee_email' => $first->employee_email ?? null,
                  'store_name' => $first->store_name ?? null,
                  'total_attendance' => $total,
                  'total_late' => $late,
                  'late_minutes' => $lateMinutes,
                  'late_rate' => $safePercent($late, $total),
              ];
          })
          ->sortByDesc(fn ($item) => ((float) ($item->total_late ?? 0) * 1000) + (float) ($item->late_minutes ?? 0))
          ->take(8)
          ->values();
  }

  $storeRiskRows = collect($storeRiskRows ?? []);

  if ($storeRiskRows->isEmpty()) {
      $storeRiskRows = $storeLabels->values()->map(function ($label, $index) use ($storeLates, $storeOvertimes) {
          $late = (int) ($storeLates[$index] ?? 0);
          $overtime = (int) ($storeOvertimes[$index] ?? 0);

          return (object) [
              'store_name' => $label ?: 'Store Tidak Diketahui',
              'total_late' => $late,
              'total_overtime' => $overtime,
              'risk_score' => ($late * 2) + $overtime,
          ];
      })->sortByDesc('risk_score')->take(8)->values();
  }

  $topClinicRisk = $demographicRows->first() ?? $storeRiskRows->first();
  $topLateEmployee = $topLateEmployeeRows->first();


  $exportQuery = request()->query();

  $hasFilter = request()->hasAny([
    'q',
    'store_id',
    'user_id',
    'shift_id',
    'status',
    'attendance_type',
    'from',
    'to',
    'per_page',
  ]);

  $formatDuration = function ($minutes) {
      $minutes = (int) $minutes;

      if ($minutes <= 0) {
          return '0 menit';
      }

      $hours = intdiv($minutes, 60);
      $remainingMinutes = $minutes % 60;

      if ($hours > 0 && $remainingMinutes > 0) {
          return $hours . ' jam ' . $remainingMinutes . ' menit';
      }

      if ($hours > 0) {
          return $hours . ' jam';
      }

      return $remainingMinutes . ' menit';
  };

  $statusBadge = function ($status) {
      return match(strtolower((string) ($status ?? ''))) {
          'present', 'completed', 'clocked_out' => 'badge-soft-success',
          'late' => 'badge-soft-warning',
          'absent' => 'badge-soft-danger',
          default => 'badge-soft-secondary',
      };
  };
@endphp

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  <div class="dashboard-hero p-4 mb-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <h4 class="mb-1">
          <span class="fw-light">Monitoring /</span> Shift Kerja Karyawan
        </h4>
        <div class="hero-subtitle">
          Dashboard monitoring absensi, shift kerja, keterlambatan, over jam, clock in, dan clock out karyawan per store.
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="hero-badge">
          <span class="live-dot me-1"></span>
          Live Data
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-calendar-range-outline me-1"></i>
          {{ \Carbon\Carbon::parse($from)->format('d M Y') }} - {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-clock-outline me-1"></i>
          Update: {{ now()->format('d-m-Y H:i:s') }}
        </span>
      </div>
    </div>
  </div>

  {{-- FILTER --}}
  <div class="card mb-4">
    <div class="card-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <h5 class="mb-1 fw-bold">Filter Shift Kerja Karyawan</h5>
        <small class="text-muted">
          Filter dapat digunakan untuk melihat data absensi berdasarkan periode, store, karyawan, shift, status, dan tipe absensi.
        </small>
      </div>

      <div class="d-flex gap-2 flex-wrap">
        <button type="button"
                class="btn btn-outline-secondary btn-sm"
                data-bs-toggle="collapse"
                data-bs-target="#filterShiftKaryawan">
          <i class="mdi mdi-filter-variant me-1"></i> Filter
        </button>

        <a href="{{ route('monitoring.employee-shift-attendance.index') }}"
           class="btn btn-outline-secondary btn-sm">
          <i class="mdi mdi-refresh me-1"></i> Reset
        </a>

        <a href="{{ route('monitoring.employee-shift-attendance.export', $exportQuery) }}"
           class="btn btn-success btn-sm">
          <i class="mdi mdi-file-excel-outline me-1"></i> Export Excel
        </a>
      </div>
    </div>

    <div class="card-body pt-0">
      <div class="collapse {{ $hasFilter ? 'show' : '' }}" id="filterShiftKaryawan">
        <div class="filter-box">
          <form method="GET"
                action="{{ route('monitoring.employee-shift-attendance.index') }}"
                class="row g-3">

            <div class="col-md-3">
              <label class="form-label">Dari</label>
              <input type="date"
                     name="from"
                     class="form-control"
                     value="{{ $from }}">
            </div>

            <div class="col-md-3">
              <label class="form-label">Sampai</label>
              <input type="date"
                     name="to"
                     class="form-control"
                     value="{{ $to }}">
            </div>

            <div class="col-md-3">
              <label class="form-label">Store</label>
              <select name="store_id"
                      class="form-select js-select2"
                      data-placeholder="Semua Store">
                <option value=""></option>
                @foreach($stores as $store)
                  <option value="{{ $store->id }}" @selected((string) $storeId === (string) $store->id)>
                    {{ $store->name }} @if($store->code) - {{ $store->code }} @endif
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Karyawan</label>
              <select name="user_id"
                      class="form-select js-select2"
                      data-placeholder="Semua Karyawan">
                <option value=""></option>
                @foreach($users as $user)
                  <option value="{{ $user->id }}" @selected((string) $userId === (string) $user->id)>
                    {{ $user->name }}
                    @if(isset($user->email) && $user->email)
                      - {{ $user->email }}
                    @endif
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Shift</label>
              <select name="shift_id"
                      class="form-select js-select2"
                      data-placeholder="Semua Shift">
                <option value=""></option>
                @foreach($shifts as $shift)
                  <option value="{{ $shift->id }}" @selected((string) $shiftId === (string) $shift->id)>
                    {{ $shift->store_name ?? '-' }} - {{ $shift->name }}
                    @if($shift->code)
                      ({{ $shift->code }})
                    @endif
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Status</label>
              <select name="status"
                      class="form-select js-select2"
                      data-placeholder="Semua Status">
                <option value=""></option>
                @foreach($statuses as $item)
                  <option value="{{ $item }}" @selected((string) $status === (string) $item)>
                    {{ ucfirst(str_replace('_', ' ', $item)) }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Tipe Absensi</label>
              <select name="attendance_type"
                      class="form-select js-select2"
                      data-placeholder="Semua Tipe">
                <option value=""></option>
                <option value="late" @selected($attendanceType === 'late')>Telat</option>
                <option value="overtime" @selected($attendanceType === 'overtime')>Over Jam</option>
                <option value="late_overtime" @selected($attendanceType === 'late_overtime')>Telat & Over Jam</option>
                <option value="not_clock_in" @selected($attendanceType === 'not_clock_in')>Belum Clock In</option>
                <option value="not_clock_out" @selected($attendanceType === 'not_clock_out')>Belum Clock Out</option>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Per Page</label>
              <select name="per_page" class="form-select">
                @foreach([10,25,50,100,200] as $size)
                  <option value="{{ $size }}" @selected((int) $perPage === $size)>
                    {{ $size }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-12">
              <label class="form-label">Cari</label>
              <input type="text"
                     name="q"
                     class="form-control"
                     value="{{ $q ?? '' }}"
                     placeholder="Cari store, kode store, karyawan, email, shift, status, atau catatan">
            </div>

            <div class="col-12">
              <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-sm">
                  <i class="mdi mdi-magnify me-1"></i> Tampilkan
                </button>

                <a href="{{ route('monitoring.employee-shift-attendance.export', $exportQuery) }}"
                   class="btn btn-success btn-sm">
                  <i class="mdi mdi-file-excel-outline me-1"></i> Export Excel
                </a>

                <a href="{{ route('monitoring.employee-shift-attendance.index') }}"
                   class="btn btn-outline-secondary btn-sm">
                  <i class="mdi mdi-refresh me-1"></i> Reset
                </a>
              </div>
            </div>

          </form>
        </div>
      </div>
    </div>
  </div>


  {{-- JADWAL SHIFT PER CABANG / HOLDING --}}
  <div class="card holding-schedule-card mb-4">
    <div class="card-header d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
      <div>
        <h5 class="mb-1">Kalender Jadwal Shift per Cabang</h5>
        <small class="text-muted">
          Tampilan kalender dibuat ringkas. Klik <b>Lihat Detail</b> untuk membuka modal berisi daftar karyawan lengkap di tanggal tersebut.
        </small>
      </div>

      <div class="status-legend">
        <span class="status-legend-item"><span class="status-dot present"></span> Hadir</span>
        <span class="status-legend-item"><span class="status-dot absent"></span> Tidak Masuk</span>
        <span class="status-legend-item"><span class="status-dot late"></span> Telat</span>
        <span class="status-legend-item"><span class="status-dot overtime"></span> Over Jam</span>
        <span class="status-legend-item"><span class="status-dot not_clock_in"></span> Clock Belum Lengkap</span>
        <span class="status-legend-item"><span class="status-dot scheduled"></span> Terjadwal</span>
      </div>
    </div>

    <div class="card-body">
      @if($scheduleCalendarByStore->count())
        <div class="schedule-calendar-wrapper">
          @foreach($scheduleCalendarByStore as $branchIndex => $branch)
            <div class="schedule-calendar-branch">
              <div class="schedule-calendar-branch-header">
                <div>
                  <div class="fw-bold text-dark">{{ $branch->store_name ?? 'Cabang Tidak Diketahui' }}</div>
                  @if(!empty($branch->store_code))
                    <div class="small text-muted">{{ $branch->store_code }}</div>
                  @endif
                  <div class="small text-muted mt-1">
                    Total {{ number_format((float) ($branch->total_schedule ?? 0), 0, ',', '.') }} jadwal sesuai filter periode.
                  </div>
                </div>

                <div class="d-flex gap-1 flex-wrap justify-content-end">
                  <span class="metric-pill success">{{ number_format((float) ($branch->total_present ?? 0), 0, ',', '.') }} hadir</span>
                  <span class="metric-pill danger">{{ number_format((float) ($branch->total_absent ?? 0), 0, ',', '.') }} tidak masuk</span>
                  <span class="metric-pill warning">{{ number_format((float) ($branch->total_late ?? 0), 0, ',', '.') }} telat</span>
                  <span class="metric-pill info">{{ number_format((float) ($branch->total_overtime ?? 0), 0, ',', '.') }} over</span>
                </div>
              </div>

              <div class="schedule-calendar-scroll">
                <div class="schedule-calendar-grid">
                  @foreach(($branch->dates ?? collect()) as $dayIndex => $day)
                    @php
                      $modalId = 'scheduleDetailModal_' . md5(($branch->store_id ?? $branchIndex) . '_' . ($day->date ?? $dayIndex));
                      $dayPresent = collect($day->shifts ?? [])->flatMap(fn ($shift) => collect($shift->items ?? []))->where('marker_status', 'present')->count();
                      $dayAbsent = collect($day->shifts ?? [])->flatMap(fn ($shift) => collect($shift->items ?? []))->where('marker_status', 'absent')->count();
                      $dayLate = collect($day->shifts ?? [])->flatMap(fn ($shift) => collect($shift->items ?? []))->whereIn('marker_status', ['late', 'late_overtime'])->count();
                      $dayOvertime = collect($day->shifts ?? [])->flatMap(fn ($shift) => collect($shift->items ?? []))->whereIn('marker_status', ['overtime', 'late_overtime'])->count();
                    @endphp

                    <div class="schedule-day-card compact">
                      <div class="schedule-day-header">
                        <div class="d-flex align-items-center gap-3">
                          <div class="schedule-day-number">{{ $day->day_number ?? '-' }}</div>
                          <div class="flex-grow-1">
                            <div class="fw-bold text-dark">{{ $day->date_label ?? '-' }}</div>
                            <div class="small text-muted">{{ $day->day_label ?? '-' }}</div>
                            <div class="small text-muted mt-1">
                              {{ number_format((float) ($day->total_employee ?? 0), 0, ',', '.') }} karyawan terjadwal
                            </div>
                          </div>
                        </div>

                        <div class="d-flex gap-1 flex-wrap mt-3">
                          @if($dayPresent > 0)
                            <span class="metric-pill success">{{ $dayPresent }} hadir</span>
                          @endif
                          @if($dayAbsent > 0)
                            <span class="metric-pill danger">{{ $dayAbsent }} tidak masuk</span>
                          @endif
                          @if($dayLate > 0)
                            <span class="metric-pill warning">{{ $dayLate }} telat</span>
                          @endif
                          @if($dayOvertime > 0)
                            <span class="metric-pill info">{{ $dayOvertime }} over</span>
                          @endif
                        </div>
                      </div>

                      <div class="schedule-day-summary">
                        @if(($day->shifts ?? collect())->count())
                          <div class="day-analysis-card">
                            <div class="fw-bold text-dark mb-2">Analisa Hari Ini</div>
                            <div class="day-analysis-grid">
                              <div class="day-analysis-item danger">
                                <div class="value">{{ number_format((float) $dayAbsent, 0, ',', '.') }}</div>
                                <div class="label">Tidak Masuk</div>
                              </div>
                              <div class="day-analysis-item warning">
                                <div class="value">{{ number_format((float) $dayLate, 0, ',', '.') }}</div>
                                <div class="label">Telat</div>
                              </div>
                              <div class="day-analysis-item info">
                                <div class="value">{{ number_format((float) $dayOvertime, 0, ',', '.') }}</div>
                                <div class="label">Over Jam</div>
                              </div>
                              <div class="day-analysis-item success">
                                <div class="value">{{ number_format((float) $dayPresent, 0, ',', '.') }}</div>
                                <div class="label">Hadir</div>
                              </div>
                            </div>
                            <div class="small text-muted mt-3">
                              Detail shift dan daftar karyawan hanya ditampilkan di modal agar kalender tetap ringkas.
                            </div>
                          </div>

                          <button type="button"
                                  class="btn btn-primary btn-sm day-detail-button"
                                  data-bs-toggle="modal"
                                  data-bs-target="#{{ $modalId }}">
                            <i class="mdi mdi-eye-outline me-1"></i> Lihat Detail Jadwal
                          </button>
                        @else
                          <div class="schedule-empty-day">
                            Tidak ada jadwal pada tanggal ini.
                          </div>
                        @endif
                      </div>
                    </div>

                    <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
                      <div class="modal-dialog modal-xl modal-dialog-scrollable">
                        <div class="modal-content">
                          <div class="modal-header">
                            <div>
                              <h5 class="modal-title mb-1">
                                Detail Jadwal - {{ $day->date_label ?? '-' }}
                              </h5>
                              <div class="small text-muted">
                                {{ $branch->store_name ?? 'Cabang Tidak Diketahui' }}
                                @if(!empty($branch->store_code))
                                  - {{ $branch->store_code }}
                                @endif
                                | {{ $day->day_label ?? '-' }}
                              </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>

                          <div class="modal-body">
                            <div class="schedule-modal-summary">
                              <div class="schedule-modal-summary-item">
                                <div class="value">{{ number_format((float) ($day->total_employee ?? 0), 0, ',', '.') }}</div>
                                <div class="label">Total Jadwal</div>
                              </div>
                              <div class="schedule-modal-summary-item">
                                <div class="value">{{ number_format((float) $dayPresent, 0, ',', '.') }}</div>
                                <div class="label">Hadir</div>
                              </div>
                              <div class="schedule-modal-summary-item">
                                <div class="value text-danger">{{ number_format((float) $dayAbsent, 0, ',', '.') }}</div>
                                <div class="label">Tidak Masuk</div>
                              </div>
                              <div class="schedule-modal-summary-item">
                                <div class="value text-warning">{{ number_format((float) $dayLate, 0, ',', '.') }}</div>
                                <div class="label">Telat</div>
                              </div>
                              <div class="schedule-modal-summary-item">
                                <div class="value text-info">{{ number_format((float) $dayOvertime, 0, ',', '.') }}</div>
                                <div class="label">Over Jam</div>
                              </div>
                            </div>

                            <div class="schedule-modal-list">
                              @forelse(($day->shifts ?? collect()) as $shiftGroup)
                                <div class="schedule-modal-shift">
                                  <div class="schedule-modal-shift-header">
                                    <div>
                                      <div class="fw-bold text-dark">{{ $shiftGroup->shift_name ?? 'Tanpa Shift' }}</div>
                                      <div class="small text-muted">
                                        @if(!empty($shiftGroup->shift_code))
                                          {{ $shiftGroup->shift_code }}
                                        @endif
                                        @if(!empty($shiftGroup->shift_start_time) || !empty($shiftGroup->shift_end_time))
                                          @if(!empty($shiftGroup->shift_code)) | @endif
                                          {{ $shiftGroup->shift_start_time ? \Carbon\Carbon::parse($shiftGroup->shift_start_time)->format('H:i') : '-' }}
                                          -
                                          {{ $shiftGroup->shift_end_time ? \Carbon\Carbon::parse($shiftGroup->shift_end_time)->format('H:i') : '-' }}
                                        @endif
                                      </div>
                                    </div>
                                    <span class="shift-count-badge">{{ number_format((float) ($shiftGroup->total_employee ?? 0), 0, ',', '.') }} orang</span>
                                  </div>

                                  <div class="schedule-modal-employees">
                                    @foreach(($shiftGroup->items ?? collect()) as $schedule)
                                      @php
                                        $markerStatus = (string) ($schedule->marker_status ?? 'scheduled');
                                        $markerBadge = $scheduleMarkerBadge($markerStatus);
                                        $markerLabel = $scheduleMarkerLabel($markerStatus);
                                      @endphp

                                      <div class="schedule-modal-employee schedule-status-{{ $markerStatus }}">
                                        <div class="d-flex gap-2 align-items-start">
                                          <span class="status-dot {{ $markerStatus }} mt-1"></span>
                                          <div>
                                            <div class="fw-semibold text-dark">{{ $schedule->employee_name ?? 'Karyawan Tidak Diketahui' }}</div>
                                            @if(!empty($schedule->employee_email))
                                              <div class="small text-muted">{{ $schedule->employee_email }}</div>
                                            @endif

                                            <div class="schedule-modal-meta">
                                              <span class="metric-pill">In: {{ $schedule->clock_in_at ? \Carbon\Carbon::parse($schedule->clock_in_at)->format('H:i') : '-' }}</span>
                                              <span class="metric-pill">Out: {{ $schedule->clock_out_at ? \Carbon\Carbon::parse($schedule->clock_out_at)->format('H:i') : '-' }}</span>
                                              @if((float) ($schedule->late_minutes ?? 0) > 0)
                                                <span class="metric-pill warning">Telat {{ number_format((float) $schedule->late_minutes, 0, ',', '.') }} menit</span>
                                              @endif
                                              @if((float) ($schedule->overtime_minutes ?? 0) > 0)
                                                <span class="metric-pill info">Over {{ number_format((float) $schedule->overtime_minutes, 0, ',', '.') }} menit</span>
                                              @endif
                                            </div>

                                            @if(!empty($schedule->notes))
                                              <div class="small text-muted mt-2">Catatan: {{ $schedule->notes }}</div>
                                            @endif
                                          </div>
                                        </div>

                                        <div class="text-end">
                                          <span class="badge {{ $markerBadge }}">{{ $markerLabel }}</span>
                                        </div>
                                      </div>
                                    @endforeach
                                  </div>
                                </div>
                              @empty
                                <div class="schedule-empty-day">
                                  Tidak ada detail jadwal pada tanggal ini.
                                </div>
                              @endforelse
                            </div>
                          </div>

                          <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                          </div>
                        </div>
                      </div>
                    </div>
                  @endforeach
                </div>
              </div>
            </div>
          @endforeach
        </div>
      @else
        <div class="text-center text-muted py-5">
          Jadwal shift belum tampil karena controller belum mengirim data <b>$scheduleRows</b>,
          atau data tidak sesuai filter tanggal/cabang/karyawan.
        </div>
      @endif
    </div>
  </div>


  {{-- SUMMARY --}}
  <div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center gap-3">
            <div>
              <div class="text-muted small">Total Absensi</div>
              <h4 class="mb-0">{{ number_format($totalAttendance, 0, ',', '.') }}</h4>
              <small class="text-muted">Semua data sesuai filter</small>
            </div>
            <div class="summary-icon">
              <i class="mdi mdi-calendar-check-outline"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center gap-3">
            <div>
              <div class="text-muted small">Total Karyawan</div>
              <h4 class="mb-0">{{ number_format($totalEmployee, 0, ',', '.') }}</h4>
              <small class="text-muted">Karyawan unik</small>
            </div>
            <div class="summary-icon success">
              <i class="mdi mdi-account-group-outline"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center gap-3">
            <div>
              <div class="text-muted small">Total Store</div>
              <h4 class="mb-0">{{ number_format($totalStore, 0, ',', '.') }}</h4>
              <small class="text-muted">Store unik</small>
            </div>
            <div class="summary-icon info">
              <i class="mdi mdi-store-outline"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center gap-3">
            <div>
              <div class="text-muted small">Total Telat</div>
              <h4 class="mb-0">{{ number_format($totalLate, 0, ',', '.') }}</h4>
              <small class="text-muted">Clock in melewati jadwal</small>
            </div>
            <div class="summary-icon warning">
              <i class="mdi mdi-clock-alert-outline"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center gap-3">
            <div>
              <div class="text-muted small">Total Over Jam</div>
              <h4 class="mb-0">{{ number_format($totalOvertime, 0, ',', '.') }}</h4>
              <small class="text-muted">Clock out melewati jadwal</small>
            </div>
            <div class="summary-icon danger">
              <i class="mdi mdi-clock-plus-outline"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center gap-3">
            <div>
              <div class="text-muted small">Belum Clock In</div>
              <h4 class="mb-0">{{ number_format($totalNotClockIn, 0, ',', '.') }}</h4>
              <small class="text-muted">Data tanpa clock in</small>
            </div>
            <div class="summary-icon warning">
              <i class="mdi mdi-login-variant"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center gap-3">
            <div>
              <div class="text-muted small">Belum Clock Out</div>
              <h4 class="mb-0">{{ number_format($totalNotClockOut, 0, ',', '.') }}</h4>
              <small class="text-muted">Data tanpa clock out</small>
            </div>
            <div class="summary-icon info">
              <i class="mdi mdi-logout-variant"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center gap-3">
            <div>
              <div class="text-muted small">Total Menit Telat</div>
              <h4 class="mb-0" style="font-size: 1.25rem;">{{ $formatDuration($totalLateMinutes) }}</h4>
              <small class="text-muted">Akumulasi keterlambatan</small>
            </div>
            <div class="summary-icon warning">
              <i class="mdi mdi-timer-alert-outline"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-6">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center gap-3">
            <div>
              <div class="text-muted small">Total Durasi Over Jam</div>
              <h4 class="mb-0">{{ $formatDuration($totalOvertimeMinutes) }}</h4>
              <small class="text-muted">Akumulasi menit over jam</small>
            </div>
            <div class="summary-icon danger">
              <i class="mdi mdi-timer-plus-outline"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-6">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center gap-3">
            <div>
              <div class="text-muted small">Total Durasi Kerja</div>
              <h4 class="mb-0">{{ $formatDuration($totalWorkMinutes) }}</h4>
              <small class="text-muted">Akumulasi jam kerja aktual</small>
            </div>
            <div class="summary-icon success">
              <i class="mdi mdi-briefcase-clock-outline"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>


  {{-- DEMOGRAFI & INSIGHT MANAGEMENT --}}
  <div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
      <div class="card insight-card">
        <div class="card-body ps-4">
          <div class="insight-label mb-2">Rasio Telat</div>
          <div class="insight-value">{{ number_format($lateRate, 1, ',', '.') }}%</div>
          <div class="insight-note mt-1">
            {{ number_format($totalLate, 0, ',', '.') }} dari {{ number_format($totalAttendance, 0, ',', '.') }} absensi.
          </div>
          <div class="progress-thin mt-3">
            <div class="progress-bar bg-warning" style="width: {{ min($lateRate, 100) }}%"></div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card insight-card">
        <div class="card-body ps-4">
          <div class="insight-label mb-2">Kelengkapan Clock</div>
          <div class="insight-value">{{ number_format($completeClockRate, 1, ',', '.') }}%</div>
          <div class="insight-note mt-1">
            Clock in/out lengkap, sisanya perlu follow up supervisor.
          </div>
          <div class="progress-thin mt-3">
            <div class="progress-bar bg-success" style="width: {{ min($completeClockRate, 100) }}%"></div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card insight-card">
        <div class="card-body ps-4">
          <div class="insight-label mb-2">Rata-rata Telat</div>
          <div class="insight-value">{{ number_format($avgLateMinutes, 1, ',', '.') }}</div>
          <div class="insight-note mt-1">
            menit per kejadian telat. Total: {{ $formatDuration($totalLateMinutes) }}.
          </div>
          <div class="d-flex gap-2 flex-wrap mt-3">
            <span class="metric-pill warning"><i class="mdi mdi-clock-alert-outline"></i> Telat</span>
            <span class="metric-pill info"><i class="mdi mdi-clock-plus-outline"></i> Over {{ number_format($overtimeRate, 1, ',', '.') }}%</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card insight-card">
        <div class="card-body ps-4">
          <div class="insight-label mb-2">Prioritas Management</div>
          <div class="insight-value" style="font-size: 1.05rem; line-height: 1.35;">
            {{ $topClinicRisk->store_name ?? 'Belum Ada Data' }}
          </div>
          <div class="insight-note mt-1">
            Klinik/cabang dengan risiko keterlambatan tertinggi sesuai filter.
          </div>
          <div class="d-flex gap-2 flex-wrap mt-3">
            <span class="metric-pill danger"><i class="mdi mdi-alert-circle-outline"></i> Perlu analisa</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 mb-4">
    <div class="col-xl-8">
      <div class="card management-card">
        <div class="card-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2">
          <div>
            <h5 class="mb-1">Demografi Absensi per Klinik</h5>
            <small class="text-muted">
              Ringkasan performa absensi per cabang: jumlah absensi, karyawan, telat, over jam, dan kelengkapan clock in/out.
            </small>
          </div>
          <div class="d-flex gap-2 flex-wrap">
            <span class="metric-pill warning">Telat {{ number_format($lateRate, 1, ',', '.') }}%</span>
            <span class="metric-pill info">Over {{ number_format($overtimeRate, 1, ',', '.') }}%</span>
          </div>
        </div>
        <div class="card-body">
          @if($demographicRows->count())
            <div class="table-responsive">
              <table class="table table-bordered table-sm table-nowrap mb-0 shift-table">
                <thead>
                  <tr>
                    <th>Klinik / Cabang</th>
                    <th class="text-end">Absensi</th>
                    <th class="text-end">Karyawan</th>
                    <th class="text-end">Telat</th>
                    <th class="text-end">Menit Telat</th>
                    <th class="text-end">Over Jam</th>
                    <th class="text-end">Belum Clock</th>
                    <th style="min-width: 180px;">Late Rate</th>
                    <th style="min-width: 190px;">Kelengkapan Clock</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($demographicRows->take(10) as $clinic)
                    @php
                      $clinicLateRate = (float) ($clinic->late_rate ?? 0);
                      $clinicCompleteRate = (float) ($clinic->complete_clock_rate ?? 0);
                      $clinicLateClass = $clinicLateRate >= 30 ? 'danger' : ($clinicLateRate >= 15 ? 'warning' : 'success');
                      $clinicClockClass = $clinicCompleteRate >= 90 ? 'success' : ($clinicCompleteRate >= 70 ? 'warning' : 'danger');
                      $clinicMissingClock = (int) ($clinic->not_clock_in ?? 0) + (int) ($clinic->not_clock_out ?? 0);
                    @endphp
                    <tr>
                      <td class="store-col">
                        <div class="fw-semibold">{{ $clinic->store_name ?? '-' }}</div>
                        @if(!empty($clinic->store_code))
                          <div class="small text-muted">{{ $clinic->store_code }}</div>
                        @endif
                      </td>
                      <td class="text-end">{{ number_format((float) ($clinic->total_attendance ?? 0), 0, ',', '.') }}</td>
                      <td class="text-end">{{ number_format((float) ($clinic->total_employee ?? 0), 0, ',', '.') }}</td>
                      <td class="text-end">
                        <span class="badge {{ ((int) ($clinic->total_late ?? 0) > 0) ? 'badge-soft-warning' : 'badge-soft-success' }}">
                          {{ number_format((float) ($clinic->total_late ?? 0), 0, ',', '.') }}
                        </span>
                      </td>
                      <td class="text-end">{{ $formatDuration($clinic->late_minutes ?? 0) }}</td>
                      <td class="text-end">{{ number_format((float) ($clinic->total_overtime ?? 0), 0, ',', '.') }}</td>
                      <td class="text-end">{{ number_format($clinicMissingClock, 0, ',', '.') }}</td>
                      <td>
                        <div class="d-flex justify-content-between small mb-1">
                          <span>{{ number_format($clinicLateRate, 1, ',', '.') }}%</span>
                          <span class="text-muted">Risiko</span>
                        </div>
                        <div class="progress-thin">
                          <div class="progress-bar bg-{{ $clinicLateClass }}" style="width: {{ min($clinicLateRate, 100) }}%"></div>
                        </div>
                      </td>
                      <td>
                        <div class="d-flex justify-content-between small mb-1">
                          <span>{{ number_format($clinicCompleteRate, 1, ',', '.') }}%</span>
                          <span class="text-muted">Lengkap</span>
                        </div>
                        <div class="progress-thin">
                          <div class="progress-bar bg-{{ $clinicClockClass }}" style="width: {{ min($clinicCompleteRate, 100) }}%"></div>
                        </div>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @else
            <div class="text-center text-muted py-5">
              Belum ada data demografi klinik sesuai filter.
            </div>
          @endif
        </div>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="card management-card h-100">
        <div class="card-header">
          <h5 class="mb-1">Karyawan Sering Telat</h5>
          <small class="text-muted">Ranking karyawan yang perlu coaching berdasarkan jumlah dan durasi telat.</small>
        </div>
        <div class="card-body">
          @if($topLateEmployeeRows->count())
            <div class="demography-list">
              @foreach($topLateEmployeeRows->take(8) as $index => $employee)
                @php
                  $employeeLateRate = (float) ($employee->late_rate ?? 0);
                  $riskClass = $employeeLateRate >= 50 ? 'high' : ($employeeLateRate >= 25 ? 'medium' : '');
                @endphp
                <div class="demography-item">
                  <div class="d-flex gap-3 align-items-start">
                    <span class="rank-badge">{{ $index + 1 }}</span>
                    <div class="flex-grow-1">
                      <div class="d-flex justify-content-between gap-2">
                        <div>
                          <div class="fw-semibold">{{ $employee->employee_name ?? '-' }}</div>
                          <div class="small text-muted">{{ $employee->store_name ?? '-' }}</div>
                        </div>
                        <span class="risk-indicator {{ $riskClass }}"></span>
                      </div>

                      <div class="d-flex gap-1 flex-wrap mt-2">
                        <span class="metric-pill warning">{{ number_format((float) ($employee->total_late ?? 0), 0, ',', '.') }}x telat</span>
                        <span class="metric-pill info">{{ $formatDuration($employee->late_minutes ?? 0) }}</span>
                      </div>

                      <div class="mt-3">
                        <div class="d-flex justify-content-between small mb-1">
                          <span>Late rate</span>
                          <b>{{ number_format($employeeLateRate, 1, ',', '.') }}%</b>
                        </div>
                        <div class="progress-thin">
                          <div class="progress-bar bg-warning" style="width: {{ min($employeeLateRate, 100) }}%"></div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
          @else
            <div class="text-center text-muted py-5">
              Belum ada karyawan telat pada data sesuai filter.
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 mb-4">
    <div class="col-xl-5">
      <div class="card management-card h-100">
        <div class="card-header">
          <h5 class="mb-1">Distribusi Masalah per Klinik</h5>
          <small class="text-muted">Cabang dengan kombinasi telat dan over jam paling tinggi.</small>
        </div>
        <div class="card-body">
          @if($storeRiskRows->count())
            <div class="demography-list">
              @foreach($storeRiskRows as $index => $storeRisk)
                @php
                  $score = (float) ($storeRisk->risk_score ?? 0);
                  $maxScore = max((float) ($storeRiskRows->max('risk_score') ?? 1), 1);
                  $scorePercent = round(($score / $maxScore) * 100, 1);
                @endphp
                <div class="demography-item">
                  <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <div class="d-flex gap-2 align-items-center">
                      <span class="rank-badge">{{ $index + 1 }}</span>
                      <div>
                        <div class="fw-semibold">{{ $storeRisk->store_name ?? '-' }}</div>
                        <div class="small text-muted">Skor risiko: {{ number_format($score, 0, ',', '.') }}</div>
                      </div>
                    </div>
                    <div class="d-flex gap-1 flex-wrap justify-content-end">
                      <span class="metric-pill warning">{{ number_format((float) ($storeRisk->total_late ?? 0), 0, ',', '.') }} telat</span>
                      <span class="metric-pill info">{{ number_format((float) ($storeRisk->total_overtime ?? 0), 0, ',', '.') }} over</span>
                    </div>
                  </div>
                  <div class="progress-thin">
                    <div class="progress-bar bg-danger" style="width: {{ min($scorePercent, 100) }}%"></div>
                  </div>
                </div>
              @endforeach
            </div>
          @else
            <div class="text-center text-muted py-5">Belum ada distribusi masalah per klinik.</div>
          @endif
        </div>
      </div>
    </div>

    <div class="col-xl-7">
      <div class="card management-card h-100">
        <div class="card-header">
          <h5 class="mb-1">Catatan Analisa Management</h5>
          <small class="text-muted">Ringkasan cepat untuk membantu keputusan operasional klinik.</small>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <div class="demography-item h-100">
                <div class="metric-pill warning mb-3"><i class="mdi mdi-clock-alert-outline"></i> Kedisiplinan Masuk</div>
                <div class="fw-semibold mb-1">{{ number_format($lateRate, 1, ',', '.') }}% absensi terlambat</div>
                <div class="text-muted small">
                  Gunakan data ini untuk evaluasi cabang dengan late rate tinggi, penyesuaian jadwal, atau coaching karyawan.
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="demography-item h-100">
                <div class="metric-pill info mb-3"><i class="mdi mdi-clock-plus-outline"></i> Beban Operasional</div>
                <div class="fw-semibold mb-1">Rata-rata over jam {{ number_format($avgOvertimeMinutes, 1, ',', '.') }} menit</div>
                <div class="text-muted small">
                  Jika over jam tinggi, cek kebutuhan manpower, pembagian shift, dan jam operasional klinik.
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="demography-item h-100">
                <div class="metric-pill {{ $completeClockRate >= 90 ? 'success' : 'danger' }} mb-3"><i class="mdi mdi-login-variant"></i> Kepatuhan Clock</div>
                <div class="fw-semibold mb-1">{{ number_format($completeClockRate, 1, ',', '.') }}% data clock lengkap</div>
                <div class="text-muted small">
                  Belum clock in {{ number_format($totalNotClockIn, 0, ',', '.') }} dan belum clock out {{ number_format($totalNotClockOut, 0, ',', '.') }}.
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="demography-item h-100">
                <div class="metric-pill danger mb-3"><i class="mdi mdi-account-alert-outline"></i> Fokus Coaching</div>
                <div class="fw-semibold mb-1">{{ $topLateEmployee->employee_name ?? 'Belum ada karyawan telat' }}</div>
                <div class="text-muted small">
                  Karyawan teratas di ranking telat dapat menjadi prioritas pembinaan atau validasi jadwal.
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- CHART --}}
  <div class="row g-4 mb-4">
    <div class="col-xl-8">
      <div class="card chart-card">
        <div class="card-header">
          <h5 class="mb-1">Grafik Absensi Harian</h5>
          <small class="text-muted">Jumlah absensi, telat, dan over jam per tanggal.</small>
        </div>
        <div class="card-body">
          @if(count($dailyLabels))
            <div class="chart-wrapper">
              <canvas id="dailyAttendanceChart"></canvas>
            </div>
          @else
            <div class="text-center text-muted py-5">Tidak ada data grafik.</div>
          @endif
        </div>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="card chart-card">
        <div class="card-header">
          <h5 class="mb-1">Top Store Telat / Over Jam</h5>
          <small class="text-muted">Store dengan jumlah telat dan over jam terbanyak.</small>
        </div>
        <div class="card-body">
          @if(count($storeLabels))
            <div class="chart-wrapper-sm">
              <canvas id="storeAttendanceChart"></canvas>
            </div>
          @else
            <div class="text-center text-muted py-5">Tidak ada data store.</div>
          @endif
        </div>
      </div>
    </div>

    <div class="col-xl-8">
      <div class="card chart-card">
        <div class="card-header">
          <h5 class="mb-1">Top Karyawan Telat / Over Jam</h5>
          <small class="text-muted">Karyawan dengan jumlah telat dan over jam terbanyak.</small>
        </div>
        <div class="card-body">
          @if(count($employeeLabels))
            <div class="chart-wrapper">
              <canvas id="employeeAttendanceChart"></canvas>
            </div>
          @else
            <div class="text-center text-muted py-5">Tidak ada data karyawan.</div>
          @endif
        </div>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="card chart-card">
        <div class="card-header">
          <h5 class="mb-1">Absensi Terbaru</h5>
          <small class="text-muted">10 data absensi terbaru sesuai filter.</small>
        </div>
        <div class="card-body mini-list">
          @forelse($latestRows as $latest)
            @php
              $latestStatusClass = $statusBadge($latest->status);
            @endphp

            <div class="mini-list-item">
              <div class="d-flex justify-content-between gap-2">
                <div>
                  <div class="fw-semibold">{{ $latest->employee_name ?? '-' }}</div>
                  <div class="text-muted small">{{ $latest->store_name ?? '-' }}</div>
                </div>
                <span class="badge {{ $latestStatusClass }}">
                  {{ $latest->status ? ucfirst(str_replace('_', ' ', $latest->status)) : '-' }}
                </span>
              </div>

              <div class="small mt-2">
                Shift: <b>{{ $latest->shift_name ?? '-' }}</b>
              </div>

              <div class="text-muted small">
                {{ $latest->date ? \Carbon\Carbon::parse($latest->date)->format('d M Y') : '-' }}
                |
                In: {{ $latest->clock_in_at ? \Carbon\Carbon::parse($latest->clock_in_at)->format('H:i') : '-' }}
                |
                Out: {{ $latest->clock_out_at ? \Carbon\Carbon::parse($latest->clock_out_at)->format('H:i') : '-' }}
              </div>

              <div class="mt-2 d-flex gap-1 flex-wrap">
                @if($latest->is_late)
                  <span class="badge badge-soft-warning">
                    Telat {{ number_format((float) ($latest->late_minutes ?? 0), 0, ',', '.') }} menit
                  </span>
                @endif

                @if($latest->is_overtime)
                  <span class="badge badge-soft-info">
                    Over {{ number_format((float) ($latest->overtime_minutes ?? 0), 0, ',', '.') }} menit
                  </span>
                @endif
              </div>
            </div>
          @empty
            <div class="text-muted text-center py-4">
              Belum ada data absensi terbaru.
            </div>
          @endforelse
        </div>
      </div>
    </div>
  </div>

  {{-- TABLE --}}
  <div class="card">
    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
      <div>
        <h5 class="mb-1 fw-bold">Laporan Shift Kerja Karyawan</h5>
        <small class="text-muted">
          Menampilkan absensi karyawan, telat, over jam, clock in, dan clock out.
        </small>
      </div>

      @if($rows->count())
        <div class="pagination-info">
          Menampilkan <b>{{ $rows->firstItem() }}</b> - <b>{{ $rows->lastItem() }}</b>
          dari <b>{{ number_format($rows->total(), 0, ',', '.') }}</b> data
          | Halaman <b>{{ $rows->currentPage() }}</b> / <b>{{ $rows->lastPage() }}</b>
        </div>
      @endif
    </div>

    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-striped table-sm table-nowrap mb-0 shift-table">
          <thead>
            <tr>
              <th>No</th>
              <th>Tanggal</th>
              <th>Store</th>
              <th>Karyawan</th>
              <th>Shift</th>
              <th>Status</th>
              <th>Jadwal Mulai</th>
              <th>Jadwal Selesai</th>
              <th>Clock In</th>
              <th>Clock Out</th>
              <th>Telat</th>
              <th>Menit Telat</th>
              <th>Over Jam</th>
              <th>Menit Over</th>
              <th>Durasi Kerja</th>
              <th>Note</th>
            </tr>
          </thead>

          <tbody>
            @forelse($rows as $i => $row)
              @php
                $isLate = (bool) ($row->is_late ?? false);
                $isOvertime = (bool) ($row->is_overtime ?? false);
                $statusClass = $statusBadge($row->status ?? null);
              @endphp

              <tr @class([
                'row-late-overtime' => $isLate && $isOvertime,
                'row-late' => $isLate && !$isOvertime,
                'row-overtime' => !$isLate && $isOvertime,
              ])>
                <td>{{ $rows->firstItem() + $i }}</td>

                <td>
                  {{ $row->date ? \Carbon\Carbon::parse($row->date)->format('d M Y') : '-' }}
                </td>

                <td class="store-col">
                  <div class="fw-semibold">{{ $row->store_name ?? '-' }}</div>
                  @if($row->store_code)
                    <div class="small text-muted">{{ $row->store_code }}</div>
                  @endif
                </td>

                <td class="employee-col">
                  <div class="fw-semibold">{{ $row->employee_name ?? '-' }}</div>
                  @if($row->employee_email)
                    <div class="small text-muted">{{ $row->employee_email }}</div>
                  @endif
                </td>

                <td>
                  <div class="fw-semibold">{{ $row->shift_name ?? '-' }}</div>
                  @if($row->shift_code)
                    <div class="small text-muted">{{ $row->shift_code }}</div>
                  @endif
                </td>

                <td>
                  <span class="badge {{ $statusClass }}">
                    {{ $row->status ? ucfirst(str_replace('_', ' ', $row->status)) : '-' }}
                  </span>
                </td>

                <td>
                  {{ $row->scheduled_start ? \Carbon\Carbon::parse($row->scheduled_start)->format('d M Y H:i') : '-' }}
                </td>

                <td>
                  {{ $row->scheduled_end ? \Carbon\Carbon::parse($row->scheduled_end)->format('d M Y H:i') : '-' }}
                </td>

                <td>
                  {{ $row->clock_in_at ? \Carbon\Carbon::parse($row->clock_in_at)->format('d M Y H:i') : '-' }}
                </td>

                <td>
                  {{ $row->clock_out_at ? \Carbon\Carbon::parse($row->clock_out_at)->format('d M Y H:i') : '-' }}
                </td>

                <td>
                  @if($isLate)
                    <span class="badge badge-soft-warning">Ya</span>
                  @else
                    <span class="badge badge-soft-secondary">Tidak</span>
                  @endif
                </td>

                <td class="text-end">
                  {{ number_format((float) ($row->late_minutes ?? 0), 0, ',', '.') }}
                </td>

                <td>
                  @if($isOvertime)
                    <span class="badge badge-soft-info">Ya</span>
                  @else
                    <span class="badge badge-soft-secondary">Tidak</span>
                  @endif
                </td>

                <td class="text-end">
                  {{ number_format((float) ($row->overtime_minutes ?? 0), 0, ',', '.') }}
                </td>

                <td>
                  {{ $formatDuration($row->work_minutes ?? 0) }}
                </td>

                <td class="note-col">
                  {{ $row->note ?? '-' }}
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="16" class="text-center text-muted py-4">
                  Tidak ada data absensi sesuai filter.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if($rows->hasPages())
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-3">
          <div class="pagination-info">
            Menampilkan <b>{{ $rows->firstItem() }}</b> - <b>{{ $rows->lastItem() }}</b>
            dari <b>{{ number_format($rows->total(), 0, ',', '.') }}</b> data
          </div>

          <div>
            {{ $rows->onEachSide(1)->links() }}
          </div>
        </div>
      @endif
    </div>
  </div>

</div>
@endsection

@push('vendor-script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@endpush

@push('page-script')
<script>
(function ($) {
  'use strict';

  const chartColors = {
    primary: '#5b5cf6',
    info: '#06b6d4',
    success: '#22c55e',
    warning: '#f59e0b',
    danger: '#ef4444',
    dark: '#0f172a',
    muted: '#64748b'
  };

  function formatNumber(value) {
    return new Intl.NumberFormat('id-ID', {
      maximumFractionDigits: 0
    }).format(Number(value || 0));
  }

  function initSelect2() {
    $('.js-select2').each(function () {
      const $el = $(this);

      if ($el.hasClass('select2-hidden-accessible')) {
        $el.select2('destroy');
      }

      $el.select2({
        theme: 'bootstrap-5',
        width: '100%',
        allowClear: true,
        placeholder: $el.data('placeholder') || 'Pilih data'
      });
    });
  }

  function initDailyAttendanceChart() {
    const canvas = document.getElementById('dailyAttendanceChart');

    if (!canvas || typeof Chart === 'undefined') {
      return;
    }

    const labels = @json($dailyLabels);
    const attendances = @json($dailyAttendances);
    const lates = @json($dailyLates);
    const overtimes = @json($dailyOvertimes);

    if (!labels.length) {
      return;
    }

    new Chart(canvas, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Total Absensi',
            data: attendances,
            backgroundColor: chartColors.primary,
            borderColor: chartColors.primary,
            borderWidth: 1,
            borderRadius: 10,
            borderSkipped: false,
            maxBarThickness: 42
          },
          {
            label: 'Telat',
            data: lates,
            backgroundColor: chartColors.warning,
            borderColor: chartColors.warning,
            borderWidth: 1,
            borderRadius: 10,
            borderSkipped: false,
            maxBarThickness: 42
          },
          {
            label: 'Over Jam',
            data: overtimes,
            backgroundColor: chartColors.info,
            borderColor: chartColors.info,
            borderWidth: 1,
            borderRadius: 10,
            borderSkipped: false,
            maxBarThickness: 42
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          mode: 'index',
          intersect: false
        },
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              usePointStyle: true,
              pointStyle: 'circle',
              color: '#475569',
              font: {
                size: 12,
                weight: '600'
              }
            }
          },
          tooltip: {
            backgroundColor: chartColors.dark,
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            padding: 12,
            cornerRadius: 10,
            callbacks: {
              label: function (context) {
                return context.dataset.label + ': ' + formatNumber(context.raw);
              }
            }
          }
        },
        scales: {
          x: {
            grid: {
              display: false,
              drawBorder: false
            },
            ticks: {
              color: chartColors.muted,
              font: {
                size: 11,
                weight: '600'
              }
            }
          },
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(148, 163, 184, .18)',
              drawBorder: false
            },
            ticks: {
              precision: 0,
              color: chartColors.muted,
              font: {
                size: 11,
                weight: '600'
              },
              callback: function (value) {
                return formatNumber(value);
              }
            }
          }
        }
      }
    });
  }

  function initStoreAttendanceChart() {
    const canvas = document.getElementById('storeAttendanceChart');

    if (!canvas || typeof Chart === 'undefined') {
      return;
    }

    const labels = @json($storeLabels);
    const lates = @json($storeLates);
    const overtimes = @json($storeOvertimes);

    if (!labels.length) {
      return;
    }

    new Chart(canvas, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Telat',
            data: lates,
            backgroundColor: chartColors.warning,
            borderColor: chartColors.warning,
            borderWidth: 1,
            borderRadius: 10,
            borderSkipped: false,
            maxBarThickness: 22
          },
          {
            label: 'Over Jam',
            data: overtimes,
            backgroundColor: chartColors.info,
            borderColor: chartColors.info,
            borderWidth: 1,
            borderRadius: 10,
            borderSkipped: false,
            maxBarThickness: 22
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              usePointStyle: true,
              pointStyle: 'circle',
              color: '#475569',
              font: {
                size: 12,
                weight: '600'
              }
            }
          },
          tooltip: {
            backgroundColor: chartColors.dark,
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            padding: 12,
            cornerRadius: 10,
            callbacks: {
              label: function (context) {
                return context.dataset.label + ': ' + formatNumber(context.raw);
              }
            }
          }
        },
        scales: {
          x: {
            beginAtZero: true,
            grid: {
              color: 'rgba(148, 163, 184, .18)',
              drawBorder: false
            },
            ticks: {
              precision: 0,
              color: chartColors.muted,
              font: {
                size: 11,
                weight: '600'
              },
              callback: function (value) {
                return formatNumber(value);
              }
            }
          },
          y: {
            grid: {
              display: false,
              drawBorder: false
            },
            ticks: {
              color: '#475569',
              font: {
                size: 11,
                weight: '600'
              }
            }
          }
        }
      }
    });
  }

  function initEmployeeAttendanceChart() {
    const canvas = document.getElementById('employeeAttendanceChart');

    if (!canvas || typeof Chart === 'undefined') {
      return;
    }

    const labels = @json($employeeLabels);
    const lates = @json($employeeLates);
    const overtimes = @json($employeeOvertimes);

    if (!labels.length) {
      return;
    }

    new Chart(canvas, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Telat',
            data: lates,
            backgroundColor: chartColors.warning,
            borderColor: chartColors.warning,
            borderWidth: 1,
            borderRadius: 10,
            borderSkipped: false,
            maxBarThickness: 42
          },
          {
            label: 'Over Jam',
            data: overtimes,
            backgroundColor: chartColors.info,
            borderColor: chartColors.info,
            borderWidth: 1,
            borderRadius: 10,
            borderSkipped: false,
            maxBarThickness: 42
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          mode: 'index',
          intersect: false
        },
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              usePointStyle: true,
              pointStyle: 'circle',
              color: '#475569',
              font: {
                size: 12,
                weight: '600'
              }
            }
          },
          tooltip: {
            backgroundColor: chartColors.dark,
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            padding: 12,
            cornerRadius: 10,
            callbacks: {
              label: function (context) {
                return context.dataset.label + ': ' + formatNumber(context.raw);
              }
            }
          }
        },
        scales: {
          x: {
            grid: {
              display: false,
              drawBorder: false
            },
            ticks: {
              color: chartColors.muted,
              font: {
                size: 11,
                weight: '600'
              }
            }
          },
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(148, 163, 184, .18)',
              drawBorder: false
            },
            ticks: {
              precision: 0,
              color: chartColors.muted,
              font: {
                size: 11,
                weight: '600'
              },
              callback: function (value) {
                return formatNumber(value);
              }
            }
          }
        }
      }
    });
  }

  $(document).ready(function () {
    initSelect2();
    initDailyAttendanceChart();
    initStoreAttendanceChart();
    initEmployeeAttendanceChart();
  });
})(window.jQuery);
</script>
@endpush