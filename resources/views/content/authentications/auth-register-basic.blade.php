@extends('layouts/blankLayout')

@section('title', 'Back Office - Register')

@section('page-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/page-auth.css') }}">
@endsection

@section('content')
<div class="position-relative">
    <div class="authentication-wrapper authentication-basic container-p-y">
        <div class="authentication-inner py-4">
            <div class="card p-2">
                {{-- Brand --}}
                <div class="app-brand justify-content-center mt-5">
                    <a href="{{ url('/') }}"
                        class="app-brand-link gap-2 text-decoration-none align-items-center d-flex">
                        <span class="app-brand-text demo text-heading fw-semibold">Back Office</span>
                    </a>
                </div>

                <div class="card-body mt-2">
                    <h4 class="mb-2">Create your account ✨</h4>
                    <p class="mb-4">Isi data di bawah untuk mendaftar.</p>

                    @if (session('error'))
                    <div class="alert alert-danger alert-dismissible" role="alert">
                        <i class="mdi mdi-alert-circle-outline me-1"></i>{{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    @endif

                    @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible" role="alert">
                        <i class="mdi mdi-alert-circle-outline me-1"></i> Periksa kembali input Anda.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    @endif

                    <form id="formRegister" action="{{ route('register.attempt') }}" method="POST" novalidate>
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline">
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        id="name" name="name" value="{{ old('name') }}" placeholder="Nama lengkap"
                                        required>
                                    <label for="name">Nama Lengkap</label>
                                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline">
                                    <input type="text" class="form-control @error('username') is-invalid @enderror"
                                        id="username" name="username" value="{{ old('username') }}"
                                        placeholder="Username" required>
                                    <label for="username">Username</label>
                                    @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline">
                                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                                        id="email" name="email" value="{{ old('email') }}"
                                        placeholder="name@example.com" required>
                                    <label for="email">Email</label>
                                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-password-toggle">
                                    <div class="input-group input-group-merge">
                                        <div class="form-floating form-floating-outline">
                                            <input type="password" id="password"
                                                class="form-control @error('password') is-invalid @enderror"
                                                name="password" placeholder="••••••••" autocomplete="new-password"
                                                required>
                                            <label for="password">Password</label>
                                            @error('password') <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <span class="input-group-text cursor-pointer"
                                            onclick="const i=this.previousElementSibling.querySelector('input'); i.type=i.type==='password'?'text':'password'; this.querySelector('i').classList.toggle('mdi-eye-off-outline'); this.querySelector('i').classList.toggle('mdi-eye-outline');">
                                            <i class="mdi mdi-eye-off-outline"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-password-toggle">
                                    <div class="input-group input-group-merge">
                                        <div class="form-floating form-floating-outline">
                                            <input type="password" id="password_confirmation" class="form-control"
                                                name="password_confirmation" placeholder="••••••••"
                                                autocomplete="new-password" required>
                                            <label for="password_confirmation">Konfirmasi Password</label>
                                        </div>
                                        <span class="input-group-text cursor-pointer"
                                            onclick="const i=this.previousElementSibling.querySelector('input'); i.type=i.type==='password'?'text':'password'; this.querySelector('i').classList.toggle('mdi-eye-off-outline'); this.querySelector('i').classList.toggle('mdi-eye-outline');">
                                            <i class="mdi mdi-eye-off-outline"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button class="btn btn-primary d-grid w-100" type="submit">Create account</button>
                        </div>
                    </form>

                    <p class="text-center mt-3">
                        <span>Already have an account?</span>
                        <a href="{{ route('auth-login-basic') }}"><span>Sign in</span></a>
                    </p>
                </div>
            </div>

            {{-- dekorasi --}}
            <img src="{{ asset('assets/img/illustrations/tree-3.png') }}"
                class="authentication-image-object-left d-none d-lg-block" alt="">
            <img src="{{ asset('assets/img/illustrations/auth-basic-mask-light.png') }}"
                class="authentication-image d-none d-lg-block" alt="">
            <img src="{{ asset('assets/img/illustrations/tree.png') }}"
                class="authentication-image-object-right d-none d-lg-block" alt="">
        </div>
    </div>
</div>
@endsection