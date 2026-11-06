@extends('layouts.app', ['title' => 'Pendaftaran Nasabah Warga — Si-BankSampah'])

@section('content')
<main class="auth-split-container" x-data="{ showPin: false }">
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
                <h2 class="auth-graphic-heading">Mulai langkah hijau,<br><em>panen manfaatnya.</em></h2>
                <p class="auth-graphic-lead" style="margin-top: 10px;">
                    Dapatkan rekening digital dan Kartu QR Anggota untuk kemudahan penimbangan di pos RT.
                </p>
            </div>

            <!-- Visual Kartu Grafis -->
            <div class="auth-graphic-card">
                <div class="auth-graphic-card-header">
                    <span class="auth-graphic-card-chip">
                        <x-icon name="credit-card" size="16" />
                        <span>Kartu Anggota Digital</span>
                    </span>
                    <span style="font-size: 11.5px; color: #bad7c3;">
                        RT 05 / RW 08
                    </span>
                </div>

                <div style="display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 12px 14px; background: rgba(255,255,255,0.06); border-radius: 12px; border: 1px solid rgba(255,255,255,0.1);">
                    <div style="display: grid; place-items: center; width: 40px; height: 40px; border-radius: 10px; background: rgba(201,236,114,0.18); color: var(--lime); flex-shrink: 0;">
                        <x-icon name="camera" size="20" />
                    </div>
                    <div>
                        <strong style="display: block; font-size: 13.5px; color: #ffffff;">Kartu Digital Siap Scan</strong>
                        <span style="display: block; font-size: 12px; color: #b7cbbf;">QR Code otomatis terbit setelah pendaftaran.</span>
                    </div>
                </div>

                <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 6px; border-top: 1px solid rgba(255,255,255,0.12); font-size: 12px; color: #c8ded1;">
                    <span><x-icon name="check" size="14" style="margin-right: 4px; color: var(--lime);" /> 100% Gratis</span>
                    <span style="color: var(--lime); font-weight: 700;">Tanpa Biaya Admin</span>
                </div>
            </div>

            <!-- 3 Poin Informasi Ringkas -->
            <div class="auth-info-list">
                <div class="auth-info-item">
                    <div class="auth-info-icon">
                        <x-icon name="sparkles" size="18" />
                    </div>
                    <div>
                        <h4 class="auth-info-title">Kartu QR Digital</h4>
                        <p class="auth-info-desc">Tunjukkan QR saat setor sampah di pos RT.</p>
                    </div>
                </div>

                <div class="auth-info-item">
                    <div class="auth-info-icon">
                        <x-icon name="tag" size="18" />
                    </div>
                    <div>
                        <h4 class="auth-info-title">Katalog Harga</h4>
                        <p class="auth-info-desc">Pantau harga sampah terkini secara transparan.</p>
                    </div>
                </div>

                <div class="auth-info-item">
                    <div class="auth-info-icon">
                        <x-icon name="users" size="18" />
                    </div>
                    <div>
                        <h4 class="auth-info-title">Tabungan Mandiri</h4>
                        <p class="auth-info-desc">Saldo tercatat otomatis dan siap dicairkan.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Bagian Kanan: Formulir Pendaftaran (Sign Up) Bersih & Ringkas -->
    <section class="auth-split-form">
        <div class="auth-split-form-inner">
            <form class="auth-clean-card" action="{{ route('register.post') }}" method="POST">
                @csrf
                
                <div class="auth-clean-header">
                    <span class="auth-clean-tag">
                        <x-icon name="users" size="13" />
                        <span>Khusus Nasabah Warga</span>
                    </span>
                    <h1 class="auth-clean-title">Buka Tabungan Sampah</h1>
                    <p class="auth-clean-desc">Pendaftaran nasabah baru Bank Sampah RT 05 / RW 08.</p>
                </div>

                @if(session('error'))
                <div class="alert alert-danger" style="margin: 0; width: 100%; display: flex; align-items: center; gap: 8px; padding: 10px 14px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; color: #991b1b; font-size: 13px;">
                    <x-icon name="alert-triangle" size="16" />
                    <span>{{ session('error') }}</span>
                </div>
                @endif

                @if ($errors->any())
                <div class="alert alert-danger" style="margin: 0; width: 100%; padding: 10px 14px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; color: #991b1b; font-size: 13px; line-height: 1.5;">
                    <strong style="display: block; margin-bottom: 4px;">Periksa kembali data pendaftaran:</strong>
                    <ul style="margin: 0; padding-left: 18px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <p class="auth-clean-desc">Pendaftaran melalui undangan pengurus RT. Identitas dan RT mengikuti data yang telah diverifikasi pengurus.</p>
                <div class="auth-field-group">
                    <div class="auth-field">
                        <label for="reg-token">Kode undangan sekali pakai</label>
                        <input id="reg-token" name="token" type="text" value="{{ $token }}" autocomplete="off" required maxlength="64">
                        <small>Belum memiliki kode? Hubungi pengurus RT untuk pendaftaran.</small>
                    </div>
                    <div class="auth-field-row">
                        <div class="auth-field">
                            <label for="reg-pin">PIN (4 Digit)</label>
                            <input
                                id="reg-pin"
                                name="pin"
                                :type="showPin ? 'text' : 'password'"
                                inputmode="numeric"
                                maxlength="4"
                                placeholder="••••"
                                required
                            >
                        </div>
                        <div class="auth-field">
                            <label for="reg-pin-conf">Ulangi PIN</label>
                            <input
                                id="reg-pin-conf"
                                name="pin_confirmation"
                                :type="showPin ? 'text' : 'password'"
                                inputmode="numeric"
                                maxlength="4"
                                placeholder="••••"
                                required
                            >
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 7px; margin-top: -2px;">
                        <input type="checkbox" id="toggle-show-pin" @change="showPin = !showPin" style="width: 15px; height: 15px; cursor: pointer; accent-color: #173f35;">
                        <label for="toggle-show-pin" style="font-size: 12.5px; color: #52675b; cursor: pointer; user-select: none;">Tampilkan PIN</label>
                    </div>
                </div>

                <button class="auth-submit-btn" type="submit">
                    <x-icon name="check" size="16" />
                    <span>Daftar Tabungan Warga</span>
                </button>

                <div class="auth-switch-link">
                    Sudah punya akun?
                    <a href="{{ route('login') }}">
                        Masuk di sini &rarr;
                    </a>
                </div>
            </form>
        </div>
    </section>
</main>
@endsection
