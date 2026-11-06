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
            <section class="warga-profile-card" aria-labelledby="withdrawal-title" style="background: #ffffff; border: 1px solid #dce6df; border-radius: 18px; padding: 22px;" x-data="{ channel: '{{ old('channel', 'tunai') }}' }">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <h3 id="withdrawal-title" style="margin: 0; font-size: 1.15rem; color: #10382d; font-weight: 700;">Persetujuan Penarikan Saldo</h3>
                    <span style="background: #e8f5e9; color: #166534; font-size: 0.75rem; font-weight: 700; padding: 4px 10px; border-radius: 999px;">
                        Saldo: Rp {{ number_format($resident->balance, 0, ',', '.') }}
                    </span>
                </div>
                <p style="font-size: 0.88rem; color: #64748b; margin-top: 0; margin-bottom: 16px;">
                    Pilih metode pencairan dana, tentukan nominal, dan verifikasi dengan PIN keamanan Anda. Kode otorisasi 12 digit akan terbit untuk diproses bendahara RT.
                </p>

                @if(session('withdrawal_code'))
                    <div class="alert alert-success" role="status" style="border-left: 4px solid #16a34a; background: #f0fdf4; padding: 16px; border-radius: 12px; margin-bottom: 18px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <span style="font-weight: 700; color: #166534;">Otorisasi Berhasil Diterbitkan</span>
                            <span style="background: #dcfce7; color: #15803d; font-size: 0.75rem; font-weight: 700; padding: 2px 8px; border-radius: 999px;">Aktif 10 Menit</span>
                        </div>
                        <p style="margin: 4px 0;">Anda menyetujui pencairan <strong>Rp {{ number_format(session('withdrawal_amount'), 0, ',', '.') }}</strong>.</p>
                        @if(session('withdrawal_channel_label'))
                            <p style="margin: 4px 0; color: #166534; font-size: 0.88rem;">Metode: <strong>{{ session('withdrawal_channel_label') }}</strong></p>
                        @endif
                        <div style="margin: 12px 0; background: #ffffff; border: 2px dashed #86efac; border-radius: 10px; padding: 12px; text-align: center;">
                            <small style="color: #64748b; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 4px;">Kode Otorisasi (Tunjukkan ke Pengurus)</small>
                            <span style="font-family: monospace; font-size: 1.6rem; font-weight: 800; letter-spacing: 0.15em; color: #15803d; user-select: all;">{{ session('withdrawal_code') }}</span>
                            <p style="margin: 4px 0 0; font-size: 0.8rem; color: #64748b;">Kode: <strong>{{ session('withdrawal_code') }}</strong></p>
                        </div>
                        <p style="margin: 0; font-size: 0.82rem; color: #15803d;">Berlaku 10 menit, sekali pakai. Kode baru membatalkan kode sebelumnya.</p>
                    </div>
                @endif

                <form action="{{ route('warga.withdrawals.authorize') }}" method="POST">
                    @csrf

                    <!-- Pilihan Jalur Pencairan -->
                    <div class="form-group" style="margin-bottom: 14px;">
                        <label style="font-weight: 600; font-size: 0.88rem; margin-bottom: 8px; display: block; color: #10382d;">Metode Penyaluran Dana</label>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;">
                            <button type="button" 
                                @click="channel = 'tunai'" 
                                :style="channel === 'tunai' ? 'background: #173f35; color: #ffffff; border-color: #173f35; font-weight: 700;' : 'background: #f8fafc; color: #475569; border-color: #cbd5e1;'"
                                style="border: 1px solid; border-radius: 10px; padding: 10px 6px; font-size: 0.82rem; cursor: pointer; text-align: center; transition: all 0.2s;">
                                💵 Tunai Posko
                            </button>
                            <button type="button" 
                                @click="channel = 'transfer_bank'" 
                                :style="channel === 'transfer_bank' ? 'background: #173f35; color: #ffffff; border-color: #173f35; font-weight: 700;' : 'background: #f8fafc; color: #475569; border-color: #cbd5e1;'"
                                style="border: 1px solid; border-radius: 10px; padding: 10px 6px; font-size: 0.82rem; cursor: pointer; text-align: center; transition: all 0.2s;">
                                🏦 Transfer Bank
                            </button>
                            <button type="button" 
                                @click="channel = 'ewallet'" 
                                :style="channel === 'ewallet' ? 'background: #173f35; color: #ffffff; border-color: #173f35; font-weight: 700;' : 'background: #f8fafc; color: #475569; border-color: #cbd5e1;'"
                                style="border: 1px solid; border-radius: 10px; padding: 10px 6px; font-size: 0.82rem; cursor: pointer; text-align: center; transition: all 0.2s;">
                                📱 E-Wallet
                            </button>
                        </div>
                        <input type="hidden" name="channel" :value="channel">
                    </div>

                    <!-- Input Detail Transfer Bank -->
                    <div x-show="channel === 'transfer_bank'" x-cloak style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; margin-bottom: 14px; display: flex; flex-direction: column; gap: 10px;">
                        <div class="form-group">
                            <label for="bank-provider" style="font-size: 0.82rem; font-weight: 600; color: #334155;">Pilih Bank Tujuan</label>
                            <select id="bank-provider" name="provider" class="calc-input" :disabled="channel !== 'transfer_bank'" :required="channel === 'transfer_bank'">
                                <option value="">-- Pilih Bank --</option>
                                @foreach($banks ?? \App\Services\DisbursementService::BANKS as $code => $bankName)
                                    <option value="{{ $code }}" {{ old('provider') === $code ? 'selected' : '' }}>{{ $bankName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="bank-account-number" style="font-size: 0.82rem; font-weight: 600; color: #334155;">Nomor Rekening Bank</label>
                            <input id="bank-account-number" type="text" name="account_number" class="calc-input" value="{{ old('account_number') }}" placeholder="Contoh: 1234567890" :disabled="channel !== 'transfer_bank'" :required="channel === 'transfer_bank'">
                        </div>
                        <div class="form-group">
                            <label for="bank-account-holder" style="font-size: 0.82rem; font-weight: 600; color: #334155;">Nama Pemilik Rekening</label>
                            <input id="bank-account-holder" type="text" name="account_holder" class="calc-input" value="{{ old('account_holder', $resident->full_name) }}" placeholder="Nama sesuai buku tabungan" :disabled="channel !== 'transfer_bank'" :required="channel === 'transfer_bank'">
                        </div>
                    </div>

                    <!-- Input Detail E-Wallet -->
                    <div x-show="channel === 'ewallet'" x-cloak style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; margin-bottom: 14px; display: flex; flex-direction: column; gap: 10px;">
                        <div class="form-group">
                            <label for="ewallet-provider" style="font-size: 0.82rem; font-weight: 600; color: #334155;">Pilih Dompet Digital</label>
                            <select id="ewallet-provider" name="provider" class="calc-input" :disabled="channel !== 'ewallet'" :required="channel === 'ewallet'">
                                <option value="">-- Pilih E-Wallet --</option>
                                @foreach($ewallets ?? \App\Services\DisbursementService::EWALLETS as $code => $walletName)
                                    <option value="{{ $code }}" {{ old('provider') === $code ? 'selected' : '' }}>{{ $walletName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="ewallet-account-number" style="font-size: 0.82rem; font-weight: 600; color: #334155;">Nomor HP / Akun E-Wallet</label>
                            <input id="ewallet-account-number" type="text" name="account_number" class="calc-input" value="{{ old('account_number', $resident->phone_number) }}" placeholder="Contoh: 081234567890" :disabled="channel !== 'ewallet'" :required="channel === 'ewallet'">
                        </div>
                        <div class="form-group">
                            <label for="ewallet-account-holder" style="font-size: 0.82rem; font-weight: 600; color: #334155;">Nama Pemilik Akun E-Wallet</label>
                            <input id="ewallet-account-holder" type="text" name="account_holder" class="calc-input" value="{{ old('account_holder', $resident->full_name) }}" placeholder="Nama sesuai akun e-wallet" :disabled="channel !== 'ewallet'" :required="channel === 'ewallet'">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="withdrawal-amount">Nominal Penarikan (Rp)</label>
                        <input id="withdrawal-amount" type="number" name="amount" class="calc-input" min="1" max="{{ $resident->balance }}" step="1" placeholder="Maks. {{ number_format($resident->balance, 0, ',', '.') }}" required>
                        @error('amount')<p role="alert" style="color: #dc2626; font-size: 0.82rem; margin-top: 4px;">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group" style="margin-top: 12px;">
                        <label for="withdrawal-pin">Konfirmasi PIN 4-Digit Anda</label>
                        <input id="withdrawal-pin" type="password" name="current_pin" class="calc-input" inputmode="numeric" pattern="[0-9]{4}" minlength="4" maxlength="4" placeholder="••••" autocomplete="off" required>
                        @error('current_pin')<p role="alert" style="color: #dc2626; font-size: 0.82rem; margin-top: 4px;">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="primary-button" style="margin-top: 16px; width: 100%;">Setujui Nominal &amp; Buat Kode</button>
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
