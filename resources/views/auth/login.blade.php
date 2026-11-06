@extends('layouts.app', ['title' => 'Masuk ke Sistem — Si-BankSampah'])

@section('content')
<main class="auth-split-container" x-data="{ showSecret: false }">
    <!-- Bagian Kiri: Informasi Ringkas & Grafis Visual Tema Utama -->
    <section class="auth-split-info">
        <div class="auth-split-orbit orbit-1"></div>
        <div class="auth-split-orbit orbit-2"></div>
        <div class="auth-split-glow-1"></div>
        <div class="auth-split-glow-2"></div>

        <div class="auth-split-info-content">
            <div class="auth-graphic-badge">
                <x-icon name="recycle" size="15" />
                <span>Bank Sampah RT 05 / RW 08</span>
            </div>

            <div>
                <h2 class="auth-graphic-heading">Sampah bernilai,<br><em>tabungan bertumbuh.</em></h2>
                <p class="auth-graphic-lead" style="margin-top: 10px;">
                    Setor sampah anorganik terpilah, catat otomatis, dan pantau saldo langsung dari rumah.
                </p>
            </div>

            <!-- Visual Kartu Grafis -->
            <div class="auth-graphic-card">
                <div class="auth-graphic-card-header">
                    <span class="auth-graphic-card-chip">
                        <x-icon name="wallet" size="16" />
                        <span>Tabungan Warga</span>
                    </span>
                    <span style="display: inline-flex; align-items: center; gap: 5px; font-size: 11.5px; color: #bad7c3;">
                        <i style="display: inline-block; width: 7px; height: 7px; border-radius: 50%; background: #9ee492; box-shadow: 0 0 0 3px rgba(158,228,146,0.2);"></i>
                        Pencatatan Aktif
                    </span>
                </div>

                <div>
                    <div style="font-size: 12px; color: #b7cbbf; margin-bottom: 2px;">Saldo Terkumpul Bulan Ini</div>
                    <div class="auth-graphic-card-amount">Rp 35.000</div>
                </div>

                <div class="auth-graphic-bars" title="Tren Setoran">
                    <div class="auth-graphic-bar" style="height: 35%;"></div>
                    <div class="auth-graphic-bar" style="height: 52%;"></div>
                    <div class="auth-graphic-bar" style="height: 44%;"></div>
                    <div class="auth-graphic-bar" style="height: 68%;"></div>
                    <div class="auth-graphic-bar" style="height: 60%;"></div>
                    <div class="auth-graphic-bar" style="height: 85%;"></div>
                    <div class="auth-graphic-bar highlight" style="height: 100%;"></div>
                </div>

                <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 8px; border-top: 1px solid rgba(255,255,255,0.12); font-size: 12px; color: #c8ded1;">
                    <span><x-icon name="scale" size="14" style="margin-right: 4px;" /> 10,0 kg terpilah</span>
                    <span style="color: var(--lime); font-weight: 700;">~29 kg CO₂ dicegah</span>
                </div>
            </div>

            <!-- 3 Poin Informasi Ringkas -->
            <div class="auth-info-list">
                <div class="auth-info-item">
                    <div class="auth-info-icon">
                        <x-icon name="trash" size="18" />
                    </div>
                    <div>
                        <h4 class="auth-info-title">Pilah dari Rumah</h4>
                        <p class="auth-info-desc">Pisahkan sampah anorganik bersih dan kering.</p>
                    </div>
                </div>

                <div class="auth-info-item">
                    <div class="auth-info-icon">
                        <x-icon name="scale" size="18" />
                    </div>
                    <div>
                        <h4 class="auth-info-title">Timbang di Pos RT</h4>
                        <p class="auth-info-desc">Petugas menimbang dan mencatat saldo real-time.</p>
                    </div>
                </div>

                <div class="auth-info-item">
                    <div class="auth-info-icon">
                        <x-icon name="credit-card" size="18" />
                    </div>
                    <div>
                        <h4 class="auth-info-title">Tarik Saldo Kapan Saja</h4>
                        <p class="auth-info-desc">Cairkan tabungan tunai melalui pengurus RT.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Bagian Kanan: Formulir Masuk (Sign In) Bersih & Ringkas -->
    <section class="auth-split-form">
        <div class="auth-split-form-inner">
            @if($activationRequired ?? false)
            <!-- Form Aktivasi PIN Perdana Warga -->
            <form class="auth-clean-card" action="{{ route('activate-pin') }}" method="POST">
                @csrf
                <div class="auth-clean-header">
                    <span class="auth-clean-tag">
                        <x-icon name="shield" size="13" />
                        <span>Aktivasi Nasabah</span>
                    </span>
                    <h1 class="auth-clean-title">Aktivasi PIN Nasabah</h1>
                    <p class="auth-clean-desc">Gunakan kode undangan dari pengurus untuk mengaktifkan akun Anda.</p>
                </div>

                @if(session('error'))
                <div class="alert alert-danger" style="margin: 0; width: 100%; display: flex; align-items: center; gap: 8px; padding: 10px 14px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; color: #991b1b; font-size: 13px;">
                    <x-icon name="alert-triangle" size="16" />
                    <span>{{ session('error') }}</span>
                </div>
                @endif

                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 10px 14px; border-radius: 10px; font-size: 13px; line-height: 1.5; color: #166534;">
                    <div><strong>Nasabah:</strong> {{ $resident->full_name ?? '' }}</div>
                    <div><strong>WhatsApp:</strong> {{ $phone ?? '' }}</div>
                </div>

                <input type="hidden" name="phone" value="{{ $phone ?? '' }}">
                <input type="hidden" name="pin" value="{{ $pin ?? '' }}">

                <div class="auth-field-group">
                    <div class="auth-field">
                        <label for="activation-token">Kode Undangan dari Pengurus</label>
                        <input
                            id="activation-token"
                            name="token"
                            type="text"
                            placeholder="Kode undangan sekali pakai" autocomplete="off"
                            required
                            autofocus
                        >
                    </div>
                </div>

                <button class="auth-submit-btn" type="submit">
                    <x-icon name="shield" size="16" />
                    <span>Aktivasi &amp; Masuk</span>
                </button>

                <div class="auth-switch-link">
                    <a href="{{ route('login') }}">Batal</a>
                </div>
            </form>
            @else
            <!-- Form Login Terpadu (Warga, Petugas, dan Pengurus RT) -->
            <form class="auth-clean-card" action="{{ route('login.post') }}" method="POST">
                @csrf
                <div class="auth-clean-header">
                    <span class="auth-clean-tag">
                        <x-icon name="shield" size="13" />
                        <span>Pintu Masuk Terpadu</span>
                    </span>
                    <h1 class="auth-clean-title">Masuk ke Sistem</h1>
                    <p class="auth-clean-desc">Masuk Akun Nasabah, Petugas, atau Akses Pengurus RT.</p>
                </div>

                @if(session('error'))
                <div class="alert alert-danger" style="margin: 0; width: 100%; display: flex; align-items: center; gap: 8px; padding: 10px 14px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; color: #991b1b; font-size: 13px;">
                    <x-icon name="alert-triangle" size="16" />
                    <span>{{ session('error') }}</span>
                </div>
                @endif

                @if(session('success'))
                <div class="alert" style="margin: 0; width: 100%; display: flex; align-items: center; gap: 8px; padding: 10px 14px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; color: #166534; font-size: 13px;">
                    <x-icon name="check" size="16" />
                    <span>{{ session('success') }}</span>
                </div>
                @endif

                @if(session('info'))
                <div class="alert" style="margin: 0; width: 100%; display: flex; align-items: center; gap: 8px; padding: 10px 14px; background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; border-radius: 10px; font-size: 13px;">
                    <x-icon name="help-circle" size="16" />
                    <span>{{ session('info') }}</span>
                </div>
                @endif

                <div class="auth-field-group">
                    <div class="auth-field">
                        <label for="login-phone">Nomor WhatsApp / HP</label>
                        <input
                            id="login-phone"
                            name="phone"
                            type="tel"
                            autocomplete="username"
                            placeholder="081234567890"
                            value="{{ old('phone') }}"
                            required
                            autofocus
                        >
                    </div>

                    <div class="auth-field">
                        <label for="login-secret">PIN Keamanan (4 Digit) / Kata Sandi</label>
                        <div class="auth-secret-wrap">
                            <input
                                id="login-secret"
                                name="password"
                                :type="showSecret ? 'text' : 'password'"
                                autocomplete="current-password"
                                placeholder="PIN atau kata sandi"
                                required
                            >
                            <button
                                type="button"
                                class="auth-secret-toggle"
                                @click="showSecret = !showSecret"
                                title="Tampilkan sandi"
                            >
                                <span x-show="!showSecret"><x-icon name="eye" size="18" /></span>
                                <span x-show="showSecret" style="display: none;"><x-icon name="eye-off" size="18" /></span>
                            </button>
                        </div>
                    </div>
                </div>

                <button class="auth-submit-btn" type="submit">
                    <x-icon name="lock" size="16" />
                    <span>Masuk</span>
                </button>

                <div class="auth-switch-link">
                    Belum punya akun?
                    <a href="{{ route('register') }}">
                        Daftar Nasabah di sini &rarr;
                    </a>
                </div>
            </form>
            @endif
        </div>
    </section>
</main>
@endsection
