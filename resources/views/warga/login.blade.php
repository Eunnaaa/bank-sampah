@extends('layouts.app', ['title' => 'Masuk Nasabah Warga — Si-BankSampah'])

@section('content')
<main class="officer-login-page-wrap" style="min-height: calc(100vh - 68px - 90px); background: radial-gradient(circle at 90% 10%, rgba(201,236,114,.18), transparent 30rem), #f8faf7; display: flex; align-items: center; justify-content: center; padding: 48px 20px;" x-data="{ showPin: false }">
    <div style="width: 100%; max-width: 440px;">
        @if($activationRequired ?? false)
        <!-- Form Aktivasi PIN Perdana Warga -->
        <form class="officer-login-card" action="{{ route('warga.activate-pin') }}" method="POST" style="width: 100%; background: #ffffff; border: 1px solid rgba(23, 63, 53, 0.12); border-radius: 20px; box-shadow: 0 16px 40px rgba(16, 51, 42, 0.07); padding: clamp(26px, 5vw, 40px); display: grid; gap: 16px;">
            @csrf
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2px;">
                <span class="officer-login-icon" style="display: grid; place-items: center; width: 44px; height: 44px; border-radius: 12px; background: #ecfdf5; color: #059669;">
                    <x-icon name="shield" size="22" />
                </span>
                <span class="role-badge" style="padding: 4px 10px; font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; border-radius: 20px; background: #e0f2fe; color: #0369a1;">
                    Aktivasi Mandiri
                </span>
            </div>

            <div>
                <span class="section-label" style="font-size: 11px; font-weight: 800; letter-spacing: .12em; color: #059669; text-transform: uppercase;">VERIFIKASI AWAL</span>
                <h1 style="margin: 6px 0 4px; font: 800 24px 'Manrope', sans-serif; color: #173f35; letter-spacing: -.03em;">Aktivasi PIN Nasabah</h1>
                <p style="margin: 0; color: #64748b; font-size: 13.5px; line-height: 1.5;">Kunci 4-digit PIN keamanan Anda dengan verifikasi nomor rumah/blok tempat tinggal.</p>
            </div>

            @if(session('error'))
            <div class="alert alert-danger" style="margin: 4px 0 8px; width: 100%; display: flex; align-items: center; gap: 8px; padding: 10px 14px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; color: #991b1b; font-size: 13px;">
                <x-icon name="alert-triangle" size="16" />
                <span>{{ session('error') }}</span>
            </div>
            @endif

            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 12px 14px; border-radius: 10px; width: 100%; font-size: 0.85rem; text-align: left; line-height: 1.5;">
                <strong>Nasabah:</strong> {{ $resident->full_name ?? '' }}<br>
                <strong>WhatsApp:</strong> {{ $phone ?? '' }}
            </div>

            <input type="hidden" name="phone" value="{{ $phone ?? '' }}">
            <input type="hidden" name="pin" value="{{ $pin ?? '' }}">

            <label for="house_number" style="font-size: 13px; font-weight: 700; color: #2d4239; margin-top: 4px;">Nomor Rumah / Blok (Sesuai Pendaftaran)</label>
            <div style="width: 100%;">
                <input
                    id="house_number"
                    name="house_number"
                    type="text"
                    placeholder="Contoh: Blok C No. 05"
                    required
                    autofocus
                    style="width: 100%; height: 46px; padding: 0 14px; border: 1.5px solid #d4dfd8; border-radius: 10px; font-size: 14px; outline: none; transition: border-color .15s ease;"
                >
            </div>

            <button class="role-primary-cta" type="submit" style="margin-top: 8px; width: 100%; height: 46px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; background: #059669; color: #ffffff; border: none; border-radius: 10px; font: 700 14.5px 'Manrope', sans-serif; cursor: pointer; transition: background .18s ease;">
                <span><x-icon name="shield" size="16" /></span>
                <span>Konfirmasi &amp; Masuk Dashboard</span>
            </button>

            <a class="return-home-link" href="{{ route('warga.login') }}" style="margin-top: 4px; justify-self: center; font-size: 13.5px;">
                Batal &amp; ganti nomor telepon
            </a>
        </form>
        @else
        <!-- Form Login Normal Warga -->
        <form class="officer-login-card" action="{{ route('warga.login.post') }}" method="POST" style="width: 100%; background: #ffffff; border: 1px solid rgba(23, 63, 53, 0.12); border-radius: 20px; box-shadow: 0 16px 40px rgba(16, 51, 42, 0.07); padding: clamp(26px, 5vw, 40px); display: grid; gap: 16px;">
            @csrf
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2px;">
                <span class="officer-login-icon" style="display: grid; place-items: center; width: 44px; height: 44px; border-radius: 12px; background: #f0fdf4; color: #166534;">
                    <x-icon name="credit-card" size="22" />
                </span>
                <span class="role-badge" style="padding: 4px 10px; font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; border-radius: 20px; background: #e0f2fe; color: #0369a1;">
                    Portal Nasabah Warga
                </span>
            </div>

            <div>
                <span class="section-label" style="font-size: 11px; font-weight: 800; letter-spacing: .12em; color: #166534; text-transform: uppercase;">PORTAL WARGA</span>
                <h1 style="margin: 6px 0 4px; font: 800 24px 'Manrope', sans-serif; color: #173f35; letter-spacing: -.03em;">Masuk Akun Nasabah</h1>
                <p style="margin: 0; color: #64748b; font-size: 13.5px; line-height: 1.5;">Masukkan nomor WhatsApp dan 4 digit PIN keamanan rekening bank sampah Anda.</p>
            </div>

            @if(session('error'))
            <div class="alert alert-danger" style="margin: 4px 0 8px; width: 100%; display: flex; align-items: center; gap: 8px; padding: 10px 14px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; color: #991b1b; font-size: 13px;">
                <x-icon name="alert-triangle" size="16" />
                <span>{{ session('error') }}</span>
            </div>
            @endif

            @if(session('info'))
            <div class="alert" style="margin: 4px 0 8px; width: 100%; display: flex; align-items: center; gap: 8px; padding: 10px 14px; background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; border-radius: 10px; font-size: 13px;">
                <x-icon name="help-circle" size="16" />
                <span>{{ session('info') }}</span>
            </div>
            @endif

            <label for="warga-phone" style="font-size: 13px; font-weight: 700; color: #2d4239; margin-top: 4px;">Nomor WhatsApp Terdaftar</label>
            <div style="position: relative; display: flex; align-items: center; width: 100%;">
                <input
                    id="warga-phone"
                    name="phone"
                    type="tel"
                    autocomplete="tel"
                    placeholder="Contoh: 081999888777"
                    value="{{ old('phone', $phone ?? '') }}"
                    required
                    autofocus
                    style="width: 100%; height: 46px; padding: 0 14px; border: 1.5px solid #d4dfd8; border-radius: 10px; font-size: 14px; outline: none; transition: border-color .15s ease;"
                >
            </div>

            <label for="warga-pin" style="font-size: 13px; font-weight: 700; color: #2d4239; margin-top: 4px;">PIN Keamanan (4 Digit)</label>
            <div style="position: relative; display: flex; align-items: center; width: 100%;">
                <input
                    id="warga-pin"
                    name="pin"
                    :type="showPin ? 'text' : 'password'"
                    inputmode="numeric"
                    maxlength="4"
                    placeholder="••••"
                    required
                    style="width: 100%; height: 46px; padding: 0 40px 0 14px; border: 1.5px solid #d4dfd8; border-radius: 10px; font-size: 14px; outline: none; transition: border-color .15s ease;"
                >
                <button
                    type="button"
                    @click="showPin = !showPin"
                    style="position: absolute; right: 10px; background: none; border: none; cursor: pointer; color: #64748b; display: flex; align-items: center; padding: 4px;"
                    title="Tampilkan / sembunyikan PIN"
                >
                    <span x-show="!showPin"><x-icon name="eye" size="18" /></span>
                    <span x-show="showPin" style="display: none;"><x-icon name="eye-off" size="18" /></span>
                </button>
            </div>

            <button class="role-primary-cta" type="submit" style="margin-top: 8px; width: 100%; height: 46px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; background: #173f35; color: #ffffff; border: none; border-radius: 10px; font: 700 14.5px 'Manrope', sans-serif; cursor: pointer; transition: background .18s ease;">
                <span><x-icon name="log-out" size="16" /></span>
                <span>Buka Dashboard Warga</span>
            </button>

            <!-- Box Demo Credentials Warga -->
            <div style="margin-top: 6px; padding: 12px 14px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; font-size: 0.8rem; color: #166534; text-align: left; line-height: 1.55; width: 100%;">
                <strong>Akun Demo Nasabah:</strong><br>
                • WhatsApp: <code>081999888777</code><br>
                • PIN: <code>1234</code><br>
                • Nasabah: <em>Keluarga Budi Santoso (Blok C No. 05)</em>
            </div>

            <a class="return-home-link" href="{{ route('home') }}" style="margin-top: 4px; justify-self: center; font-size: 13.5px;">
                Kembali ke beranda utama
            </a>
        </form>
        @endif
    </div>
</main>
@endsection
