@extends('layouts.app')

@section('content')
<main class="balance-page" x-data="{
    showPin: false,
    showChangePin: false
}">
    <section class="balance-search-hero">
        <div class="balance-search-inner">
            <div class="public-eyebrow">
                <span><x-icon name="credit-card" size="16" /></span> Layanan mandiri warga
            </div>
            <h1>Cek saldo<br><em>tabungan sampah.</em></h1>
            <p>Masukkan nomor WhatsApp dan 4 digit PIN keamanan Anda untuk melihat saldo dan riwayat setoran.</p>

            @if(session('error'))
            <div class="alert alert-danger" style="margin-bottom: 20px;">
                <span><x-icon name="alert-triangle" size="16" /></span> {{ session('error') }}
            </div>
            @endif

            @if(session('success'))
            <div class="alert alert-success" style="margin-bottom: 20px;">
                <span><x-icon name="check" size="16" /></span> {{ session('success') }}
            </div>
            @endif

            <!-- Form Cek Saldo & Form Aktivasi PIN -->
            <form class="balance-search-form" action="{{ route('balance.search') }}" method="POST">
                @csrf
                <div class="balance-field-group">
                    <label for="phone" class="balance-label">Nomor WhatsApp terdaftar</label>
                    <div class="balance-input-row">
                        <span class="balance-prefix">+62</span>
                        <input
                            id="phone"
                            name="phone"
                            type="tel"
                            value="{{ old('phone', $phone ?? '') }}"
                            placeholder="812 3456 7890"
                            required
                        >
                    </div>
                </div>

                <div class="balance-field-group">
                    <label for="resident-pin" class="balance-label">
                        <span><x-icon name="key" size="16" /> PIN Keamanan Warga (4 Digit)</span>
                    </label>
                    <div class="balance-input-row">
                        <span class="balance-prefix"><x-icon name="lock" size="16" /></span>
                        <input
                            id="resident-pin"
                            name="pin"
                            :type="showPin ? 'text' : 'password'"
                            inputmode="numeric"
                            maxlength="4"
                            value="{{ old('pin', $pin ?? '') }}"
                            placeholder="••••"
                            required
                        >
                        <button type="button" class="pin-toggle-btn" @click="showPin = !showPin">
                            <span x-text="showPin ? 'Sembunyikan' : 'Lihat'"></span>
                        </button>
                    </div>
                </div>

                @if($activationRequired ?? false)
                <!-- Kartu Bantuan Aktivasi PIN Perdana -->
                <div class="pin-activation-card" style="margin-top: 15px; background: #e8f5e9; border: 1px solid #c8e6c9; padding: 16px; border-radius: 12px;">
                    <div style="font-weight: 700; color: #1b5e20; margin-bottom: 6px;">
                        <x-icon name="shield" size="18" /> Aktivasi PIN Perdana Nasabah
                    </div>
                    <p style="font-size: 0.85rem; color: #2e7d32; margin-bottom: 12px;">
                        Akun nasabah Anda baru pertama kali mengakses layanan ini. Masukkan kode undangan sekali pakai dari pengurus RT untuk mengunci PIN 4 digit ini:
                    </p>
                    <div class="balance-field-group">
                        <label for="activation-token" class="balance-label">Kode Undangan dari Pengurus (Verifikasi)</label>
                        <input
                            id="activation-token"
                            name="token"
                            type="text"
                            class="calc-input"
                            placeholder="Kode undangan sekali pakai" autocomplete="off" maxlength="64"
                            required
                        >
                    </div>
                </div>
                @endif

                <button type="submit" class="balance-submit-button">
                    <span><x-icon name="search" size="16" /></span>
                    <span>Cari Saldo Tabungan</span>
                    <span>→</span>
                </button>
            </form>
        </div>
    </section>

    <!-- Kartu Hasil Saldo Nasabah -->
    @if(isset($result) && $result)
    <section class="balance-result-section">
        <div class="balance-result-wrap">
            <!-- Kartu Saldo Hijau Forest -->
            <div class="resident-balance-card">
                <div class="balance-card-background"><x-icon name="recycle" size="140" /></div>
                <div class="balance-card-top">
                    <span>REKENING WARGA</span>
                    <button type="button" @click="showChangePin = true" style="background: none; border: none; color: #d8efde; cursor: pointer; font-size: 13px; font-weight: 700; text-decoration: underline;">
                        <x-icon name="key" size="14" /> Ganti PIN
                    </button>
                </div>
                <p>Saldo Tabungan Tersedia</p>
                <strong>Rp {{ number_format($result->balance, 0, ',', '.') }}</strong>
                <div class="balance-card-owner">
                    <span><x-icon name="user" size="16" /></span>
                    <div>
                        <small>{{ $result->full_name }}</small>
                        <b>{{ $result->household_code }} · {{ $result->phone_number }}</b>
                    </div>
                </div>
            </div>

            <!-- Kartu Riwayat Mutasi Transaksi -->
            <div class="balance-history-card">
                <div class="history-card-head">
                    <div>
                        <span class="section-label">BUKU MUTASI</span>
                        <h2>Riwayat Transaksi Terkini</h2>
                    </div>
                    <span class="history-count">{{ count($transactions) }} Transaksi</span>
                </div>

                <div class="history-list">
                    @forelse($transactions as $tx)
                    <div class="history-item">
                        <div class="history-icon">
                            @if($tx->type === 'deposit')
                                <x-icon name="deposit" size="18" />
                            @else
                                <x-icon name="withdrawal" size="18" />
                            @endif
                        </div>
                        <div class="history-description">
                            <strong>{{ $tx->type === 'deposit' ? 'Setoran Sampah Terpilah' : 'Pencairan Tabungan Tunai' }}</strong>
                            <span>{{ $tx->created_at->timezone(config('app.business_timezone'))->translatedFormat('d M Y, H:i') }} · Petugas: {{ $tx->officer?->full_name ?? 'Petugas RT' }}</span>
                            @if($tx->items->count() > 0)
                            <span style="font-size: 11.5px; color: #2e7d32;">
                                @foreach($tx->items as $it)
                                    {{ $it->category?->name }} ({{ $it->weight }} {{ $it->category?->unit }}){{ !$loop->last ? ' · ' : '' }}
                                @endforeach
                            </span>
                            @endif
                        </div>
                        <div class="history-value">
                            <strong style="color: {{ $tx->type === 'deposit' ? '#1b5e20' : '#c62828' }};">
                                {{ $tx->type === 'deposit' ? '+' : '-' }}Rp {{ number_format($tx->total_amount, 0, ',', '.') }}
                            </strong>
                            <span>{{ $tx->type === 'deposit' ? 'Kredit Masuk' : 'Debit Keluar' }}</span>
                        </div>
                    </div>
                    @empty
                    <div class="no-history">Belum ada riwayat mutasi transaksi tercatat.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Modal Ganti PIN -->
        <div class="modal-overlay" x-show="showChangePin" style="display: none;" x-cloak>
            <div class="modal-card">
                <div class="modal-head">
                    <h3>Ganti PIN Mandiri</h3>
                    <button type="button" class="modal-close" @click="showChangePin = false">&times;</button>
                </div>
                <form action="{{ route('balance.change-pin') }}" method="POST">
                    @csrf
                    <input type="hidden" name="resident_id" value="{{ $result->id }}">
                    <div class="form-group">
                        <label>PIN Saat Ini (4 Digit)</label>
                        <input type="password" name="current_pin" maxlength="4" class="calc-input" required>
                    </div>
                    <div class="form-group" style="margin-top: 10px;">
                        <label>PIN Baru (4 Digit)</label>
                        <input type="password" name="new_pin" maxlength="4" class="calc-input" required>
                    </div>
                    <div class="modal-actions" style="margin-top: 20px;">
                        <button type="button" class="btn-secondary" @click="showChangePin = false">Batal</button>
                        <button type="submit" class="btn-primary">Simpan PIN Baru</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
    @else
    <!-- Panduan & FAQ Layanan Cek Saldo Mandiri (Saat Belum Ada Pencarian) -->
    <section class="balance-guide-section">
        <div class="guide-section-header">
            <span class="section-label">PANDUAN LAYANAN MANDIRI</span>
            <h2>Kemudahan Cek Saldo &amp; Tabungan Warga</h2>
            <p>Akses informasi saldo dan catatan setoran sampah terpilah Anda secara aman, cepat, dan transparan dari rumah.</p>
        </div>

        <div class="guide-features-grid">
            <div class="guide-feature-card">
                <div class="guide-feature-icon"><x-icon name="smartphone" size="24" /></div>
                <h3>1. No. WhatsApp Terdaftar</h3>
                <p>Gunakan nomor handphone yang Anda daftarkan saat pertama kali menyetor sampah pilahan di pos timbang RT.</p>
            </div>

            <div class="guide-feature-card">
                <div class="guide-feature-icon"><x-icon name="lock" size="24" /></div>
                <h3>2. PIN Keamanan 4 Digit</h3>
                <p>PIN rahasia menjaga keamanan data dan privasi saldo tabungan Anda. Jaga kerahasiaannya dengan baik.</p>
            </div>

            <div class="guide-feature-card">
                <div class="guide-feature-icon"><x-icon name="home" size="24" /></div>
                <h3>3. Aktivasi Mandiri Perdana</h3>
                <p>Nasabah baru yang belum memiliki PIN dapat mengaktifkannya langsung dengan kode undangan sekali pakai dari pengurus RT.</p>
            </div>
        </div>

        <div class="guide-section-header" style="margin-bottom: 24px;">
            <span class="section-label">PERTANYAAN UMUM</span>
            <h2>Informasi Bantuan Nasabah</h2>
        </div>

        <div class="guide-faq-grid">
            <div class="guide-faq-card">
                <h4><x-icon name="help-circle" size="16" /> Bagaimana jika saya lupa PIN 4 digit?</h4>
                <p>Pengurus RT atau administrator bank sampah dapat membantu mengatur ulang PIN Anda melalui sistem dasbor pengurus dengan cepat dan aman.</p>
            </div>

            <div class="guide-faq-card">
                <h4><x-icon name="help-circle" size="16" /> Kapan saldo tabungan bisa dicairkan?</h4>
                <p>Pencairan uang tunai dapat dilakukan kapan saja melalui bendahara pada jadwal penimbangan rutin atau acara pencairan bersama warga.</p>
            </div>

            <div class="guide-faq-card">
                <h4><x-icon name="help-circle" size="16" /> Apakah ada potongan biaya administrasi?</h4>
                <p>Sama sekali tidak ada! Rekening bank sampah warga 100% bebas biaya bulanan dan saldo Anda tidak memiliki masa kedaluwarsa.</p>
            </div>

            <div class="guide-faq-card">
                <h4><x-icon name="help-circle" size="16" /> Apakah ada bukti resmi saat menimbang?</h4>
                <p>Setiap penimbangan langsung dicatat ke sistem dan petugas mengirimkan struk digital resmi langsung ke nomor WhatsApp Anda.</p>
            </div>
        </div>

        <div class="guide-contact-banner">
            <div class="guide-contact-copy">
                <h3><x-icon name="home" size="20" /> Butuh Bantuan Rekening atau Pendaftaran Baru?</h3>
                <p>Kunjungi pos bank sampah balai warga RT 05 / RW 08 pada jadwal penimbangan akhir pekan atau hubungi petugas lapangan kami.</p>
            </div>
            <div class="guide-contact-badge">
                <span><x-icon name="map-pin" size="14" /> RT 05 / RW 08</span>
            </div>
        </div>
    </section>
    @endif
</main>
@endsection
