@extends('layouts.warga', ['title' => 'Kartu Digital & Profil — Si-BankSampah'])

@section('warga_content')
<div class="warga-profil-wrap" x-data="{
    scannerActive: false,
    scanProgress: 0,
    scanResult: null,
    simulateScan() {
        this.scannerActive = true;
        this.scanResult = null;
        this.scanProgress = 0;
        let interval = setInterval(() => {
            this.scanProgress += 20;
            if (this.scanProgress >= 100) {
                clearInterval(interval);
                this.scannerActive = false;
                this.scanResult = @js($qrData);
            }
        }, 150);
    }
}">
    <!-- Header Halaman -->
    <div class="warga-page-header">
        <div>
            <span class="section-label">IDENTITAS &amp; KEAMANAN NASABAH</span>
            <h1>Kartu Digital &amp; Profil Warga</h1>
            <p>Tunjukkan kartu digital Anda kepada petugas di pos penimbangan untuk pencatatan otomatis yang cepat dan akurat.</p>
        </div>
    </div>

    <div class="profil-split-grid" style="display: grid; grid-template-columns: 1.15fr 0.85fr; gap: 24px; margin-top: 20px;">
        <!-- Kolom Kiri: Kartu Digital Nasabah Resmi (DoD #3) -->
        <div>
            <div class="warga-id-card-full">
                <div class="id-card-topbar">
                    <div class="id-brand-chip">
                        <span class="id-chip-icon"><x-icon name="recycle" size="22" /></span>
                        <div>
                            <strong>SI-BANK SAMPAH</strong>
                            <small>KARTU NASABAH RESMI RT {{ $resident->rt }} / RW {{ $resident->rw }}</small>
                        </div>
                    </div>
                    <span class="id-status-badge">
                        <x-icon name="check-circle" size="14" />
                        <span>AKTIF</span>
                    </span>
                </div>

                <div class="id-card-body">
                    <div class="id-qr-container">
                        <div class="id-qr-wrapper">
                            {!! $qrCodeSvg !!}
                        </div>
                        <small class="id-qr-hint">Pindai QR di Pos Timbang</small>
                    </div>

                    <div class="id-details">
                        <div class="id-detail-item">
                            <span class="id-label">NAMA LENGKAP KEPALA KELUARGA</span>
                            <strong class="id-name">{{ $resident->full_name }}</strong>
                        </div>

                        <div class="id-detail-item">
                            <span class="id-label">NOMOR WHATSAPP TERDAFTAR</span>
                            <span class="id-value">{{ $resident->phone_number }}</span>
                        </div>

                        <div class="id-detail-item">
                            <span class="id-label">ALAMAT DOMISILI LINGKUNGAN</span>
                            <span class="id-value">{{ $resident->household_code }}</span>
                        </div>

                        <div class="id-detail-item">
                            <span class="id-label">TERDAFTAR SEJAK</span>
                            <span class="id-value">{{ $resident->created_at->timezone(config('app.business_timezone'))->translatedFormat('d F Y') }}</span>
                        </div>
                    </div>
                </div>

                <div class="id-card-footer">
                    <div>
                        <small>Saldo Tabungan Saat Ini</small>
                        <strong>Rp {{ number_format($resident->balance, 0, ',', '.') }}</strong>
                    </div>
                    <div style="font-size: 11.5px; opacity: 0.8; text-align: right;">
                        ID: {{ substr($resident->id, 0, 8) }}•••<br>
                        Bank Sampah Swadaya RT
                    </div>
                </div>
            </div>

            <!-- Panel Simulator Scanner (DoD #3: 'sukses di-scan menggunakan scanner simulasi') -->
            <div class="scanner-simulation-panel" style="margin-top: 20px; background: #ffffff; border: 1px solid #dce6df; border-radius: 18px; box-shadow: 0 4px 18px rgba(16, 56, 45, 0.04); padding: 22px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <div>
                        <h3 style="margin: 0; font-size: 16px; color: #173f35;">Uji Pindai QR Code (Scanner Simulasi)</h3>
                        <small style="color: #64748b;">Simulasikan pemindaian QR Code oleh kamera scanner pos timbang admin.</small>
                    </div>
                    <button id="btn-scan-sim" type="button" class="primary-button" @click="simulateScan()" :disabled="scannerActive" style="min-height: 38px; padding: 0 16px; display: inline-flex; align-items: center; gap: 6px;">
                        <x-icon name="camera" size="16" />
                        <span x-text="scannerActive ? 'Memindai...' : 'Uji Pindai Sekarang'">Uji Pindai Sekarang</span>
                    </button>
                </div>

                <!-- Animasi Laser Scanner -->
                <div x-show="scannerActive" style="text-align: center; padding: 20px; background: #0f172a; border-radius: 10px; color: #38bdf8;">
                    <div style="font-weight: 700; margin-bottom: 8px;">[ SIMULATOR SCANNER AKTIF ]</div>
                    <div style="background: #334155; height: 6px; border-radius: 3px; overflow: hidden; max-width: 300px; margin: 0 auto 10px;">
                        <div style="background: #38bdf8; height: 100%; transition: width 0.15s ease;" :style="`width: ${scanProgress}%`"></div>
                    </div>
                    <small style="color: #94a3b8;">Membaca pola QR matriks digital nasabah...</small>
                </div>

                <!-- Hasil Scan Berhasil -->
                <div x-show="scanResult" style="background: #f0fdf4; border: 1px solid #86efac; padding: 16px; border-radius: 10px; margin-top: 10px;">
                    <div style="display: flex; align-items: center; gap: 8px; color: #166534; font-weight: 800; font-size: 14px; margin-bottom: 8px;">
                        <x-icon name="check-circle" size="18" />
                        <span>QR Code Berhasil Di-scan &amp; Terverifikasi Valid!</span>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; font-size: 12.5px; color: #334155;">
                        <div>
                            <span style="color: #64748b;">Nama Nasabah:</span><br>
                            <strong x-text="scanResult?.full_name"></strong>
                        </div>
                        <div>
                            <span style="color: #64748b;">Nomor WhatsApp:</span><br>
                            <strong x-text="scanResult?.phone"></strong>
                        </div>
                        <div>
                            <span style="color: #64748b;">Alamat Rumah:</span><br>
                            <span x-text="scanResult?.address"></span>
                        </div>
                        <div>
                            <span style="color: #64748b;">Status Rekening:</span><br>
                            <span style="color: #166534; font-weight: 700;" x-text="scanResult?.status"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Pengaturan Akun & Formulir Ganti PIN -->
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <section class="warga-profile-card" aria-labelledby="withdrawal-title" style="background: #ffffff; border: 1px solid #dce6df; border-radius: 18px; padding: 22px;">
                <h3 id="withdrawal-title">Persetujuan Penarikan Tunai</h3>
                <p>Masukkan nominal yang ingin Anda cairkan dan konfirmasi PIN. Bagikan kode hanya kepada bendahara saat Anda menerima uang tunai.</p>
                @if(session('withdrawal_code'))
                    <div class="alert alert-success" role="status">
                        <p>Anda menyetujui pencairan <strong>Rp {{ number_format(session('withdrawal_amount'), 0, ',', '.') }}</strong>.</p>
                        <p>Kode: <strong>{{ session('withdrawal_code') }}</strong></p>
                        <p>Berlaku 10 menit, sekali pakai. Kode baru membatalkan kode sebelumnya.</p>
                    </div>
                @endif
                <form action="{{ route('warga.withdrawals.authorize') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="withdrawal-amount">Nominal Penarikan (Rp)</label>
                        <input id="withdrawal-amount" type="number" name="amount" class="calc-input" min="1" max="{{ $resident->balance }}" step="1" required>
                        @error('amount')<p role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group" style="margin-top: 12px;">
                        <label for="withdrawal-pin">Konfirmasi PIN Anda</label>
                        <input id="withdrawal-pin" type="password" name="current_pin" class="calc-input" inputmode="numeric" pattern="[0-9]{4}" minlength="4" maxlength="4" autocomplete="off" required>
                        @error('current_pin')<p role="alert">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="primary-button" style="margin-top: 16px;">Setujui Nominal &amp; Buat Kode</button>
                </form>
            </section>

            <!-- Informasi Data Diri Warga -->
            <div class="warga-profile-card" style="background: #ffffff; border: 1px solid #dce6df; border-radius: 18px; box-shadow: 0 4px 18px rgba(16, 56, 45, 0.04); padding: 22px;">
                <h3 style="margin: 0 0 16px; font-size: 16px; color: #10382d;">Detail Profil Warga</h3>
                <div class="profile-info-list" style="display: flex; flex-direction: column; gap: 12px; font-size: 13.5px;">
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                        <span style="color: #64748b;">Nama Lengkap:</span>
                        <strong>{{ $resident->full_name }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                        <span style="color: #64748b;">No. Handphone / WhatsApp:</span>
                        <strong>{{ $resident->phone_number }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                        <span style="color: #64748b;">RT / RW:</span>
                        <span>RT {{ $resident->rt }} / RW {{ $resident->rw }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                        <span style="color: #64748b;">Nomor Rumah / Blok:</span>
                        <span>{{ $resident->house_number }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #64748b;">Status PIN Keamanan:</span>
                        <span style="color: #166534; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                            <x-icon name="shield" size="14" /> Terpasang &amp; Aktif
                        </span>
                    </div>
                </div>
            </div>

            <!-- Form Ganti PIN Keamanan Mandiri -->
            <div class="warga-pin-card" style="background: #ffffff; border: 1px solid #dce6df; border-radius: 18px; box-shadow: 0 4px 18px rgba(16, 56, 45, 0.04); padding: 22px;">
                <div style="margin-bottom: 16px;">
                    <h3 style="margin: 0 0 4px; font-size: 16px; color: #10382d;">Ganti PIN Keamanan (4 Digit)</h3>
                    <small style="color: #64748b;">Gunakan 4 digit angka rahasia yang mudah diingat.</small>
                </div>

                @if($errors->any())
                <div class="alert alert-danger" style="margin-bottom: 14px; padding: 10px 14px; font-size: 13px;">
                    <ul style="margin: 0; padding-left: 18px;">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form action="{{ route('warga.profil.change-pin') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="current_pin">PIN Saat Ini (4 Digit)</label>
                        <input
                            id="current_pin"
                            type="password"
                            name="current_pin"
                            maxlength="4"
                            inputmode="numeric"
                            class="calc-input"
                            placeholder="••••"
                            required
                        >
                    </div>

                    <div class="form-group" style="margin-top: 12px;">
                        <label for="new_pin">PIN Baru (4 Digit)</label>
                        <input
                            id="new_pin"
                            type="password"
                            name="new_pin"
                            maxlength="4"
                            inputmode="numeric"
                            class="calc-input"
                            placeholder="••••"
                            required
                        >
                    </div>

                    <div class="form-group" style="margin-top: 12px;">
                        <label for="new_pin_confirmation">Konfirmasi PIN Baru (4 Digit)</label>
                        <input
                            id="new_pin_confirmation"
                            type="password"
                            name="new_pin_confirmation"
                            maxlength="4"
                            inputmode="numeric"
                            class="calc-input"
                            placeholder="••••"
                            required
                        >
                    </div>

                    <button type="submit" class="primary-button" style="margin-top: 18px; width: 100%; justify-content: center;">
                        <x-icon name="key" size="16" />
                        <span>Simpan PIN Baru</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
