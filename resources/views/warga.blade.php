@extends('layouts.app')

@section('content')
<main class="warga-page">
    <!-- Hero Banner Portal Warga -->
    <section class="public-hero warga-hero">
        <div class="hero-orbit orbit-one"></div>
        <div class="hero-orbit orbit-two"></div>
        <div class="public-hero-inner">
            <div class="hero-copy">
                <div class="public-eyebrow">
                    <span><x-icon name="recycle" size="16" /></span> Portal Lingkungan RT
                </div>
                <h1>Direktori Warga &amp;<br><em>Edukasi Pemilahan.</em></h1>
                <p>Panduan praktis pemilahan sampah dari rumah dan daftar warga yang telah terdaftar sebagai nasabah aktif bank sampah RT 05 / RW 08.</p>
                <div class="hero-cta-row">
                    @if(session('resident_id') || (auth()->check() && auth()->user()->role === 'resident'))
                        <a class="public-primary-cta" href="{{ route('warga.dashboard') }}">
                            <span><x-icon name="credit-card" size="18" /></span> Dashboard Saldo Warga <span>→</span>
                        </a>
                    @elseif(auth()->check() && in_array(auth()->user()->role, ['admin', 'officer']))
                        <a class="public-primary-cta" href="{{ route('admin.dashboard') }}">
                            <span><x-icon name="shield" size="18" /></span> Panel Pengurus <span>→</span>
                        </a>
                    @else
                        <a class="public-primary-cta" href="{{ route('login') }}">
                            <span><x-icon name="credit-card" size="18" /></span> Masuk Akun Warga <span>→</span>
                        </a>
                        <a class="public-text-cta" href="{{ route('register') }}">Daftar nasabah baru <span>→</span></a>
                    @endif
                </div>
                <div class="trust-row">
                    <span><x-icon name="shield" size="16" /> 4 Langkah Edukasi</span>
                    <span><x-icon name="scale" size="16" /> Penimbangan Terbuka</span>
                </div>
            </div>

            <div class="hero-visual" aria-hidden="true">
                <div class="visual-disc disc-a"></div>
                <div class="visual-disc disc-b"></div>
                <div class="hero-card hero-card-main">
                    <div class="card-top">
                        <span class="card-leaf"><x-icon name="users" size="16" /></span>
                        <span>Komunitas Warga</span>
                        <i></i>
                    </div>
                    <strong>RT 05 / RW 08</strong>
                    <small>Gerakan Pemilahan Sampah Mandiri</small>
                    <div class="card-spark"><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>
                </div>
                <div class="hero-card hero-card-float">
                    <span><x-icon name="scale" size="20" /></span>
                    <div>
                        <strong>Pos Penimbangan</strong>
                        <small>Rutin Setiap Akhir Pekan</small>
                    </div>
                </div>
                <div class="hero-stamp" aria-label="Status warga aktif pilah">
                    <span>WARGA</span>
                    <strong>AKTIF</strong>
                    <small>PILAH</small>
                </div>
            </div>
        </div>
    </section>

    <!-- 4 Langkah Praktis Pemilahan Sampah -->
    <section class="steps-section">
        <div class="steps-grid">
            <article class="step-card">
                <span class="step-num">01</span>
                <h3><x-icon name="trash" size="18" /> Pilah dari Sumber</h3>
                <p>Pisahkan sampah anorganik (plastik, botol, kardus, kaleng) dari sampah organik dapur sejak di rumah.</p>
            </article>
            <article class="step-card">
                <span class="step-num">02</span>
                <h3><x-icon name="sparkles" size="18" /> Bersihkan &amp; Keringkan</h3>
                <p>Bilas sisa cairan pada botol atau kemasan plastik, dan pastikan kardus tidak basah agar harga jual maksimal.</p>
            </article>
            <article class="step-card">
                <span class="step-num">03</span>
                <h3><x-icon name="scale" size="18" /> Setor ke Pos RT</h3>
                <p>Bawa sampah pilahan pada jadwal penimbangan rutin setiap akhir pekan di pos bank sampah lingkungan.</p>
            </article>
            <article class="step-card">
                <span class="step-num">04</span>
                <h3><x-icon name="credit-card" size="18" /> Saldo Otomatis Masuk</h3>
                <p>Petugas menimbang secara transparan, dan nominal langsung tercatat ke tabungan warga yang dapat dicek kapan saja.</p>
            </article>
        </div>
    </section>

    @if(auth()->check())
    <!-- Direktori Warga Terdaftar -->
    <section class="directory-section">
        <div class="section-heading">
            <div>
                <span class="section-label">DIREKTORI INTERNAL RT</span>
                <h2>Daftar Nasabah Terdaftar</h2>
                <p>Warga yang berpartisipasi aktif dalam gerakan bank sampah swadaya lingkungan.</p>
            </div>
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <span class="live-indicator"><x-icon name="users" size="16" /> {{ $residents->total() }} Nasabah Terdaftar</span>
                <form action="{{ route('warga') }}" method="GET" class="directory-search">
                    <span><x-icon name="search" size="16" /></span>
                    <input type="text" name="q" value="{{ $search ?? '' }}" placeholder="Cari nama atau blok rumah...">
                    <button type="submit">Cari</button>
                    @if(!empty($search))
                        <a href="{{ route('warga') }}" style="color: #64748b; font-size: 16px; font-weight: 700; text-decoration: none; padding: 0 4px;" title="Reset pencarian">&times;</a>
                    @endif
                </form>
            </div>
        </div>

        <div class="directory-grid">
            @forelse($residents as $res)
            <div class="resident-card">
                <div class="resident-avatar">{{ strtoupper(substr($res->full_name, 0, 1)) }}</div>
                <div class="resident-meta">
                    <strong>{{ $res->full_name }}</strong>
                    <span>RT {{ $res->rt }} / RW {{ $res->rw }} · {{ $res->house_number }}</span>
                </div>
                <a href="{{ route('warga.dashboard') }}" class="btn-check-balance" title="Buka Dashboard Nasabah">
                    <span><x-icon name="credit-card" size="14" /> Dashboard Warga</span>
                </a>
            </div>
            @empty
            <div class="empty-state">
                <span style="display: block; margin-bottom: 8px;"><x-icon name="search" size="36" /></span>
                <strong>Tidak ditemukan nasabah yang sesuai.</strong>
                <p style="margin: 4px 0 0; color: #94a3b8;">Coba cari dengan kata kunci nama nasabah atau nomor blok rumah lainnya.</p>
            </div>
            @endforelse
        </div>

        @if($residents->hasPages())
        <div class="pagination-wrapper">
            {{ $residents->links() }}
        </div>
        @endif
    </section>
    @else
    <section class="directory-section">
        <h2>Direktori Warga</h2>
        <p>Masuk untuk melihat direktori nasabah di lingkungan RT Anda.</p>
        <a href="{{ route('login') }}" class="primary-button">Masuk Akun</a>
    </section>
    @endif
</main>
@endsection
