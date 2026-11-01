@extends('layouts/contentNavbarLayout')

@section('title', 'Akses Ditolak')

@section('content')
<style>
    .error-container {
        text-align: center;
        padding: 100px 20px;
        min-height: 70vh;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }

    .error-code {
        font-size: 8rem;
        font-weight: 800;
        color: #ff6b6b;
        text-shadow: 2px 2px 6px rgba(255, 0, 0, 0.3);
        animation: pulse 2s infinite;
    }

    .error-message {
        font-size: 1.5rem;
        margin-top: 1rem;
        color: #555;
    }

    .error-desc {
        color: #888;
        margin-top: 0.5rem;
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.05); opacity: 0.8; }
    }

    .btn-back {
        margin-top: 2rem;
        font-weight: 600;
    }

    .lock-icon {
        font-size: 5rem;
        color: #ff6b6b;
        margin-bottom: 1rem;
        animation: float 2s ease-in-out infinite;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-8px); }
    }
</style>

<div class="error-container">
    <i class="mdi mdi-lock-outline lock-icon"></i>
    <div class="error-code">403</div>
    <div class="error-message">Akses Ditolak</div>
    <p class="error-desc">
        {{ $message ?? 'Anda tidak memiliki izin untuk mengakses halaman ini.' }}<br>
        Silakan hubungi admin untuk meminta akses.
    </p>

    <div class="d-flex gap-2 justify-content-center">
        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-back">
            <i class="mdi mdi-arrow-left"></i> Kembali
        </a>
        <a href="{{ route('dashboard') }}" class="btn btn-primary btn-back">
            <i class="mdi mdi-home-outline"></i> Ke Dashboard
        </a>
    </div>
</div>
@endsection
