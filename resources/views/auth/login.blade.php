@extends('layouts.app') {{-- Menggunakan layout utama (app.blade.php) --}}

@section('title', 'Login Pengguna') {{-- Judul spesifik untuk halaman ini --}}

@section('content')
<div class="container mt-5"> {{-- Beri sedikit margin atas --}}
    <div class="row justify-content-center">
        <div class="col-md-6"> {{-- Batasi lebar kartu agar tidak terlalu lebar di layar besar --}}
            <div class="card shadow-sm"> {{-- Tambahkan sedikit shadow untuk efek visual --}}
                <div class="card-header bg-primary text-white text-center"> {{-- Header kartu dengan background & text center --}}
                    {{-- Logo di atas Judul (Opsional) --}}
                    {{-- <img src="{{ asset('images/kingstar_logo_plain.png') }}" alt="Logo" height="50" class="mb-2"> --}}
                    <h4 class="mb-0">{{ __('Login Sistem CV Kingstar') }}</h4>
                </div>

                <div class="card-body px-4 py-4"> {{-- Tambah padding di dalam card body --}}
                    <form method="POST" action="{{ route('login') }}">
                        @csrf  {{-- Token CSRF Wajib --}}

                        {{-- Input Username --}}
                        <div class="mb-3">
                            <label for="username" class="form-label fw-bold">{{ __('Username') }}</label>
                            <div class="input-group"> {{-- Gunakan input group untuk ikon --}}
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input id="username" type="text"
                                       class="form-control @error('username') is-invalid @enderror"
                                       name="username" value="{{ old('username') }}"
                                       required autocomplete="username" autofocus
                                       placeholder="Masukkan username Anda"> {{-- Tambah placeholder --}}
                            </div>
                            @error('username')
                                <span class="invalid-feedback d-block" role="alert"> {{-- d-block agar tampil meski tanpa input-group --}}
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        {{-- Input Password --}}
                        <div class="mb-3">
                            <label for="password" class="form-label fw-bold">{{ __('Password') }}</label>
                             <div class="input-group"> {{-- Gunakan input group untuk ikon --}}
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input id="password" type="password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       name="password" required autocomplete="current-password"
                                       placeholder="Masukkan password Anda"> {{-- Tambah placeholder --}}
                             </div>
                            @error('password')
                                <span class="invalid-feedback d-block" role="alert"> {{-- d-block --}}
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        {{-- Checkbox Remember Me --}}
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                <label class="form-check-label" for="remember">
                                    {{ __('Ingat Saya') }}
                                </label>
                            </div>
                        </div>

                        {{-- Tombol Login --}}
                        <div class="d-grid mb-2"> {{-- d-grid membuat tombol full width --}}
                            <button type="submit" class="btn btn-primary btn-lg fw-bold"> {{-- btn-lg & fw-bold --}}
                                <i class="bi bi-box-arrow-in-right me-1"></i> {{ __('Login') }}
                            </button>
                        </div>
                    </form>
                </div> {{-- End card-body --}}

                {{-- Footer Kartu (Opsional) --}}
                <div class="card-footer text-center bg-light">
                    <small class="text-muted">Hanya untuk pengguna terdaftar.</small>
                </div>

            </div> {{-- End card --}}
        </div> {{-- End col --}}
    </div> {{-- End row --}}
</div> {{-- End container --}}
@endsection

@push('styles')
{{-- CSS tambahan jika perlu --}}
@endpush

@push('scripts')
{{-- JS tambahan jika perlu --}}
@endpush