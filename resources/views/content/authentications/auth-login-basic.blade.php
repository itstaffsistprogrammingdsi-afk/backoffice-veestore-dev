@extends('layouts/blankLayout')

@section('title', 'Backoffice VeeStore')

@section('page-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/page-auth.css') }}">

<style>
    :root {
        --beauty-pink: #ff4da6;
        --beauty-rose: #ff7ac3;
        --beauty-soft: #fff1f7;
        --beauty-dark: #2b1b26;
    }

    /* Full-page background */
    .beauty-page {
        min-height: 100vh;
        background:
            radial-gradient(1200px 600px at 10% 10%, rgba(255, 77, 166, .18), transparent 60%),
            radial-gradient(1000px 500px at 90% 15%, rgba(255, 122, 195, .16), transparent 55%),
            radial-gradient(900px 500px at 30% 85%, rgba(255, 199, 225, .22), transparent 60%),
            linear-gradient(135deg, #fff 0%, var(--beauty-soft) 40%, #ffffff 100%);
        position: relative;
        overflow: hidden;
    }

    .beauty-page:before {
        content: "";
        position: absolute;
        inset: -40px;
        background-image: radial-gradient(rgba(255, 77, 166, .10) 1px, transparent 1px);
        background-size: 18px 18px;
        transform: rotate(8deg);
        opacity: .50;
        pointer-events: none;
    }

    /* Container width FIX (biar gak sempit) */
    .beauty-shell {
        position: relative;
        z-index: 1;
        max-width: 1060px;
        margin: 0 auto;
        padding: 32px 16px;
        min-height: 100vh;
        display: flex;
        align-items: center;
    }

    /* Two column layout */
    .beauty-grid {
        width: 100%;
        display: grid;
        grid-template-columns: 1fr;
        gap: 18px;
    }

    @media (min-width: 992px) {
        .beauty-grid {
            grid-template-columns: 1.05fr .95fr;
            align-items: stretch;
            gap: 22px;
        }
    }

    /* Card login */
    .beauty-card {
        background: rgba(255, 255, 255, .82);
        border: 1px solid rgba(255, 77, 166, .16);
        border-radius: 18px;
        box-shadow: 0 18px 55px rgba(17, 12, 16, .12), 0 2px 10px rgba(255, 77, 166, .08);
        overflow: hidden;
        backdrop-filter: blur(10px);
    }

    .beauty-accent {
        height: 6px;
        background: linear-gradient(90deg, var(--beauty-pink), var(--beauty-rose));
    }

    .beauty-card-body {
        padding: 22px;
    }

    @media (min-width: 768px) {
        .beauty-card-body {
            padding: 28px;
        }
    }

    /* Brand row */
    .brand-badge {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: grid;
        place-items: center;
        background: linear-gradient(135deg, rgba(255, 77, 166, .16), rgba(255, 122, 195, .14));
        border: 1px solid rgba(255, 77, 166, .20);
        box-shadow: 0 10px 24px rgba(255, 77, 166, .14);
        flex: 0 0 auto;
    }

    .brand-title {
        margin: 0;
        font-weight: 700;
        color: var(--beauty-dark);
        letter-spacing: .2px;
        line-height: 1.15;
        font-size: 1.25rem;
        white-space: nowrap;
        /* penting: jangan pecah jadi Back / Office / Beauty */
    }

    .brand-sub {
        font-size: .85rem;
        color: rgba(43, 27, 38, .70);
    }

    @media (max-width: 380px) {
        .brand-title {
            white-space: normal;
        }

        /* layar sangat kecil boleh wrap */
    }

    /* Button */
    .btn-beauty {
        border: 0;
        color: #fff !important;
        background: linear-gradient(90deg, var(--beauty-pink), var(--beauty-rose));
        box-shadow: 0 14px 25px rgba(255, 77, 166, .22);
    }

    .btn-beauty:hover {
        transform: translateY(-1px);
        box-shadow: 0 16px 28px rgba(255, 77, 166, .28);
    }

    /* Links */
    .beauty-link {
        color: var(--beauty-pink);
        text-decoration: none;
        font-weight: 600;
    }

    .beauty-link:hover {
        text-decoration: underline;
    }

    /* Tip box */
    .beauty-tip {
        border: 1px dashed rgba(255, 77, 166, .28);
        background: rgba(255, 241, 247, .70);
        border-radius: 14px;
        padding: .75rem .9rem;
        color: rgba(43, 27, 38, .82);
    }

    /* Inputs focus */
    .form-control:focus {
        border-color: rgba(255, 77, 166, .55) !important;
        box-shadow: 0 0 0 .15rem rgba(255, 77, 166, .18) !important;
    }

    /* HERO kanan */
    .beauty-hero {
        display: none;
    }

    @media (min-width: 992px) {
        .beauty-hero {
            display: block;
            border-radius: 18px;
            background:
                radial-gradient(600px 320px at 50% 10%, rgba(255, 77, 166, .22), transparent 60%),
                linear-gradient(135deg, rgba(255, 255, 255, .82), rgba(255, 241, 247, .95));
            border: 1px solid rgba(255, 77, 166, .16);
            box-shadow: 0 18px 55px rgba(17, 12, 16, .08);
            overflow: hidden;
            position: relative;
            padding: 28px;
        }

        .beauty-hero:before {
            content: "";
            position: absolute;
            inset: -30px;
            background-image: radial-gradient(rgba(255, 77, 166, .10) 1px, transparent 1px);
            background-size: 16px 16px;
            opacity: .55;
            pointer-events: none;
            transform: rotate(-6deg);
        }

        .beauty-hero>* {
            position: relative;
            z-index: 1;
        }
    }

    .icon-row {
        display: flex;
        gap: 10px;
        margin-bottom: 18px;
    }

    .icon-bubble {
        width: 50px;
        height: 50px;
        border-radius: 16px;
        display: grid;
        place-items: center;
        background: rgba(255, 255, 255, .75);
        border: 1px solid rgba(255, 77, 166, .18);
        box-shadow: 0 12px 20px rgba(255, 77, 166, .14);
    }

    .feature-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }

    .feature {
        padding: 12px;
        border-radius: 16px;
        border: 1px solid rgba(0, 0, 0, .08);
        background: rgba(255, 255, 255, .70);
    }

    .feature .t {
        font-weight: 700;
    }

    .feature .s {
        font-size: .85rem;
        color: rgba(0, 0, 0, .55);
    }

    /* Prevent weird wrapping in headings */
    .no-weird-wrap {
        word-break: normal;
        overflow-wrap: normal;
    }
</style>
@endsection

@section('content')
<div class="beauty-page">
    <div class="beauty-shell">
        <div class="beauty-grid">

            {{-- LEFT: LOGIN --}}
            <div class="beauty-card">
                <div class="beauty-accent"></div>

                <div class="beauty-card-body">

                    {{-- Brand --}}
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="brand-badge">
                            <i class="mdi mdi-lipstick" style="font-size:1.6rem; color: var(--beauty-pink);"></i>
                        </div>
                        <div>
                            <a href="{{ route('auth-login-basic') }}" class="text-decoration-none">
                                <div class="brand-title">Backoffice VeeStore</div>
                            </a>
                            <div class="brand-sub">Makeup • Skincare • Beauty Tools</div>
                        </div>
                    </div>

                    <h4 class="mb-1 no-weird-wrap">Welcome 👋</h4>


                    {{-- Flash success --}}
                    @if(session('success'))
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <i class="mdi mdi-check-circle-outline me-1"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    @endif

                    {{-- Flash error --}}
                    @if(session('error'))
                    <div class="alert alert-danger alert-dismissible" role="alert">
                        <i class="mdi mdi-alert-circle-outline me-1"></i>{{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    @endif

                    {{-- Validation error --}}
                    @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible" role="alert">
                        <i class="mdi mdi-alert-circle-outline me-1"></i> Periksa kembali input Anda.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    @endif

                    <form action="{{ route('login.attempt') }}" method="POST" novalidate>
                        @csrf

                        <div class="form-floating form-floating-outline mb-3">
                            <input type="text" class="form-control @error('login') is-invalid @enderror" id="login"
                                name="login" value="{{ old('login') }}" placeholder="Email atau Username"
                                autocomplete="username" autofocus>
                            <label for="login">Email / Username</label>
                            @error('login')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <div class="input-group input-group-merge">
                                <div class="form-floating form-floating-outline flex-grow-1">
                                    <input type="password" id="password"
                                        class="form-control @error('password') is-invalid @enderror" name="password"
                                        placeholder="••••••••" autocomplete="current-password" />
                                    <label for="password">Password</label>
                                    @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <span class="input-group-text cursor-pointer"
                                    onclick="const i=document.getElementById('password'); const ic=this.querySelector('i'); i.type=(i.type==='password'?'text':'password'); ic.classList.toggle('mdi-eye-off-outline'); ic.classList.toggle('mdi-eye-outline');">
                                    <i class="mdi mdi-eye-off-outline"></i>
                                </span>
                            </div>
                        </div>

                        <!--  <div class="mb-3 d-flex justify-content-between align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember-me" name="remember"
                                    {{ old('remember') ? 'checked' : '' }}>
                                <label class="form-check-label" for="remember-me">Remember me</label>
                            </div>
                            <a href="#" class="beauty-link">Forgot password?</a>
                        </div> -->

                        <button class="btn btn-beauty w-100 py-2" type="submit">
                            <i class="mdi mdi-lock-open-outline me-1"></i> Sign in
                        </button>
                    </form>

                    <div class="beauty-tip d-flex gap-2 mt-4">
                        <i class="mdi mdi-sparkles-outline" style="font-size: 1.2rem;"></i>
                        <div>
                            <!--   <div class="fw-semibold">Tip</div>
                            <div class="small">
                                Kelola produk seperti lipstik, foundation, sisir, hair tools, harga promo, dan stok di
                                sini.
                            </div> -->
                        </div>
                    </div>

                    <!--   <p class="text-center mt-4 mb-0">
                        <span class="text-muted">New here?</span>
                        <a href="#" class="beauty-link ms-1">Create an account</a>
                        {{-- <a href="{{ route('auth-register-basic') }}" class="beauty-link ms-1">Create an account</a>
                        --}}
                    </p> -->

                </div>
            </div>

            {{-- RIGHT: HERO (desktop only) --}}
            <div class="beauty-hero">
                <div class="icon-row">
                    <div class="icon-bubble"><i class="mdi mdi-brush-outline" style="font-size:1.6rem;"></i></div>
                    <div class="icon-bubble"><i class="mdi mdi-face-woman-outline" style="font-size:1.6rem;"></i></div>
                    <div class="icon-bubble"><i class="mdi mdi-scissors-cutting" style="font-size:1.6rem;"></i></div>
                </div>

                <h3 class="fw-bold mb-2" style="color: var(--beauty-dark);">Manage your Beauty Store</h3>
                <p class="mb-4" style="color: rgba(43,27,38,.75);">
                    Kelola katalog, stok, promo, dan transaksi dengan tampilan yang clean & elegan.
                </p>

                <div class="feature-grid">
                    <div class="feature">
                        <div class="t"><i class="mdi mdi-package-variant-closed me-1"></i>Inventory</div>
                        <div class="s">Stok produk & varian</div>
                    </div>
                    <div class="feature">
                        <div class="t"><i class="mdi mdi-sale-outline me-1"></i>Promotions</div>
                        <div class="s">Diskon & bundling</div>
                    </div>
                    <div class="feature">
                        <div class="t"><i class="mdi mdi-cart-outline me-1"></i>Orders</div>
                        <div class="s">Pesanan & status</div>
                    </div>
                    <div class="feature">
                        <div class="t"><i class="mdi mdi-chart-line me-1"></i>Reports</div>
                        <div class="s">Analitik penjualan</div>
                    </div>
                </div>

                <div class="mt-4 small" style="color: rgba(43,27,38,.65);">
                    <i class="mdi mdi-heart-outline me-1"></i> Beauty first, business smart.
                </div>
            </div>

        </div>
    </div>
</div>
@endsection