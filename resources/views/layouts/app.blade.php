<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $title ?? 'Si-BankSampah — Pengelolaan Bank Sampah Warga RT/RW' }}</title>
    
    <!-- Google Fonts Manrope -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- CSS Utama (100% Identik) -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    
    <!-- Alpine.js untuk Modal, Kalkulator & Baris Timbang -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body>
    @if(!request()->is('admin*'))
    <header class="public-header">
        <div class="public-header-inner">
            <a href="{{ route('home') }}" class="public-brand">
                <span class="brand-mark"><x-icon name="recycle" size="20" /></span>
                <div class="brand-text">
                    <strong>Si-Bank</strong>
                    <small>Sampah</small>
                </div>
            </a>
            <nav class="public-nav" aria-label="Navigasi publik">
                <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Beranda</a>
                <a href="{{ route('warga') }}" class="{{ request()->routeIs('warga') ? 'active' : '' }}">Portal warga</a>
                
                @if(session('resident_id') || (auth()->check() && auth()->user()->role === 'resident'))
                    <a href="{{ route('warga.dashboard') }}" class="{{ request()->routeIs('warga.*') ? 'active' : '' }}">Dashboard warga</a>
                @elseif(auth()->check() && in_array(auth()->user()->role, ['admin', 'officer']))
                    <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.*') ? 'active' : '' }}">Panel Pengurus</a>
                @else
                    <a href="{{ route('register') }}" class="{{ request()->routeIs('register*') ? 'active' : '' }}">Daftar warga</a>
                @endif
            </nav>

            @if(auth()->check() && in_array(auth()->user()->role, ['admin', 'officer']))
                <a class="public-admin-link active" href="{{ route('admin.dashboard') }}">
                    Panel RT / Petugas <span>→</span>
                </a>
            @elseif(session('resident_id') || (auth()->check() && auth()->user()->role === 'resident'))
                <a class="public-admin-link active" href="{{ route('warga.dashboard') }}">
                    Dashboard Warga <span>→</span>
                </a>
            @else
                <a class="public-admin-link {{ request()->routeIs('login') ? 'active' : '' }}" href="{{ route('login') }}">
                    Masuk Akun <span>→</span>
                </a>
            @endif
        </div>
    </header>
    @endif

    @yield('content')

    @if(!request()->is('admin*'))
    <footer class="public-footer">
        <div class="public-footer-inner">
            <div class="footer-brand">
                <span class="brand-mark"><x-icon name="recycle" size="20" /></span>
                <div class="brand-text">
                    <strong>Si-Bank</strong>
                    <small>Sampah</small>
                </div>
            </div>
            <p>Bank sampah warga. Bersih lingkungannya, bertumbuh tabungannya.</p>
            <div class="footer-links">
                <a href="{{ route('terms') }}" class="footer-terms-link">Syarat &amp; Ketentuan</a>
                <span class="footer-divider">·</span>
                <span>Operasional Lingkungan RT/RW</span>
            </div>
        </div>
    </footer>
    @endif
</body>
</html>
