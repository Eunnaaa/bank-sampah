<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $title ?? 'Portal Nasabah — Si-BankSampah' }}</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body>
    <div class="app-shell">
        <!-- Sidebar Khusus Nasabah Warga (Menyelaraskan dengan Sidebar Admin) -->
        <aside class="sidebar">
            <div class="brand">
                <span class="brand-mark"><x-icon name="recycle" size="20" /></span>
                <div class="brand-text">
                    <strong>Si-Bank</strong>
                    <small>Sampah Warga</small>
                </div>
            </div>

            <div class="sidebar-community">
                <span class="community-mark">{{ $currentResident->rt ?? '05' }}</span>
                <span>
                    <small>Buku Tabungan RT</small>
                    <strong>RT {{ $currentResident->rt ?? '05' }} · RW {{ $currentResident->rw ?? '08' }}</strong>
                </span>
            </div>

            <nav class="sidebar-nav" aria-label="Navigasi portal warga">
                <span class="nav-label">PORTAL NASABAH</span>
                <a href="{{ route('warga.dashboard') }}" class="{{ request()->routeIs('warga.dashboard') ? 'active' : '' }}">
                    <span><x-icon name="bar-chart" size="18" /></span>
                    <span>Ringkasan</span>
                    @if(request()->routeIs('warga.dashboard'))<i></i>@endif
                </a>
                <a href="{{ route('warga.riwayat') }}" class="{{ request()->routeIs('warga.riwayat') ? 'active' : '' }}">
                    <span><x-icon name="file-text" size="18" /></span>
                    <span>Buku Mutasi</span>
                    @if(request()->routeIs('warga.riwayat'))<i></i>@endif
                </a>
                <a href="{{ route('warga.katalog') }}" class="{{ request()->routeIs('warga.katalog') ? 'active' : '' }}">
                    <span><x-icon name="tag" size="18" /></span>
                    <span>Katalog Harga</span>
                    @if(request()->routeIs('warga.katalog'))<i></i>@endif
                </a>
                <a href="{{ route('warga.profil') }}" class="{{ request()->routeIs('warga.profil') ? 'active' : '' }}">
                    <span><x-icon name="credit-card" size="18" /></span>
                    <span>Kartu QR &amp; Profil</span>
                    @if(request()->routeIs('warga.profil'))<i></i>@endif
                </a>
            </nav>

            <a class="sidebar-cta" href="{{ route('warga.profil') }}">
                <span><x-icon name="credit-card" size="16" /></span>
                <div>
                    <strong>Kartu Nasabah</strong>
                    <small>QR Code setoran timbang</small>
                </div>
                <span>→</span>
            </a>

            <nav class="sidebar-nav sidebar-bottom" aria-label="Navigasi tambahan">
                <a href="{{ route('terms') }}" target="_blank">
                    <span><x-icon name="book-open" size="18" /></span>
                    <span>Syarat &amp; Ketentuan</span>
                </a>
                <a href="{{ route('home') }}" target="_blank">
                    <span><x-icon name="globe" size="18" /></span>
                    <span>Halaman Utama ↗</span>
                </a>
            </nav>

            <div class="sidebar-profile">
                <div style="display: flex; align-items: center; gap: 9px; min-width: 0; flex: 1;">
                    <div class="avatar avatar-mint">{{ strtoupper(substr($currentResident->full_name ?? 'W', 0, 1)) }}</div>
                    <div style="display: flex; flex-direction: column; min-width: 0; gap: 2px; overflow: hidden;">
                        <strong style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 13px;">{{ $currentResident->full_name ?? 'Nasabah' }}</strong>
                        <small style="color: #94a3b8; font-size: 11px;">Blok/No. {{ $currentResident->house_number ?? '-' }}</small>
                    </div>
                </div>
                <form action="{{ route('warga.logout') }}" method="POST" style="margin: 0; flex-shrink: 0;">
                    @csrf
                    <button type="submit" title="Keluar dari portal warga" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 6px; display: flex; align-items: center;">
                        <x-icon name="log-out" size="18" />
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content Area with Topbar -->
        <main class="dashboard-main">
            <!-- Topbar Portal Nasabah -->
            <header class="topbar">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="admin-heading-badge" style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; background: #ecfdf5; border: 1px solid #a7f3d0; color: #166534; font-size: 12.5px; font-weight: 700; border-radius: 8px;">
                        <span style="width: 7px; height: 7px; background: #22c55e; border-radius: 50%; box-shadow: 0 0 0 3px #bbf7d0;"></span>
                        Portal Nasabah RT {{ $currentResident->rt ?? '05' }} / RW {{ $currentResident->rw ?? '08' }}
                    </span>
                </div>

                <div class="topbar-actions" style="display: flex; align-items: center; gap: 14px;">
                    <a href="{{ route('warga.profil') }}" class="profile-chip" style="display: flex; align-items: center; gap: 8px; text-decoration: none; color: inherit;">
                        <div class="avatar avatar-mint">{{ strtoupper(substr($currentResident->full_name ?? 'W', 0, 1)) }}</div>
                        <div class="profile-copy">
                            <strong>{{ $currentResident->full_name ?? 'Nasabah' }}</strong>
                            <span style="color: #15803d; font-weight: 700;">Rp {{ number_format($currentResident->balance ?? 0, 0, ',', '.') }}</span>
                        </div>
                    </a>
                </div>
            </header>

            <div class="dashboard-content">
                @if(session('success'))
                <div class="admin-alert success" style="margin-bottom: 20px;">
                    <span><x-icon name="check" size="16" /></span> {{ session('success') }}
                </div>
                @endif

                @if(session('error'))
                <div class="admin-alert error" style="margin-bottom: 20px;">
                    <span><x-icon name="alert-triangle" size="16" /></span> {{ session('error') }}
                </div>
                @endif

                @if(session('info'))
                <div class="admin-alert info" style="margin-bottom: 20px;">
                    <span><x-icon name="help-circle" size="16" /></span> {{ session('info') }}
                </div>
                @endif

                @yield('warga_content')
            </div>
        </main>
    </div>

    <!-- Bottom Navigation Bar (Dock Pintas Nasabah Khusus Mobile) -->
    <nav class="warga-bottom-nav warga-mobile-bar" aria-label="Navigasi bawah warga">
        <a href="{{ route('warga.dashboard') }}" class="{{ request()->routeIs('warga.dashboard') ? 'active' : '' }}">
            <x-icon name="bar-chart" size="18" />
            <span>Dashboard</span>
        </a>
        <a href="{{ route('warga.riwayat') }}" class="{{ request()->routeIs('warga.riwayat') ? 'active' : '' }}">
            <x-icon name="file-text" size="18" />
            <span class="nav-label-desktop">Riwayat Mutasi</span>
            <span class="nav-label-mobile">Riwayat</span>
        </a>
        <a href="{{ route('warga.katalog') }}" class="{{ request()->routeIs('warga.katalog') ? 'active' : '' }}">
            <x-icon name="tag" size="18" />
            <span class="nav-label-desktop">Katalog Harga</span>
            <span class="nav-label-mobile">Katalog</span>
        </a>
        <a href="{{ route('warga.profil') }}" class="{{ request()->routeIs('warga.profil') ? 'active' : '' }}">
            <x-icon name="credit-card" size="18" />
            <span class="nav-label-desktop">Kartu QR &amp; Profil</span>
            <span class="nav-label-mobile">Kartu QR</span>
        </a>
    </nav>
</body>
</html>
