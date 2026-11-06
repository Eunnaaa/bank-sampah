<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $title ?? 'Area Pengurus — Si-BankSampah' }}</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar">
            <div class="brand">
                <span class="brand-mark"><x-icon name="recycle" size="20" /></span>
                <div class="brand-text">
                    <strong>Si-Bank</strong>
                    <small>Sampah</small>
                </div>
            </div>

            <div class="sidebar-community">
                <span class="community-mark">{{ auth()->user()->rt ?? '05' }}</span>
                <span>
                    <small>Lingkungan</small>
                    <strong>RT {{ auth()->user()->rt ?? '05' }} · RW {{ auth()->user()->rw ?? '08' }}</strong>
                </span>
            </div>

            <nav class="sidebar-nav" aria-label="Navigasi utama">
                <span class="nav-label">MENU UTAMA</span>
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <span><x-icon name="bar-chart" size="18" /></span>
                    <span>Ringkasan</span>
                    @if(request()->routeIs('admin.dashboard'))<i></i>@endif
                </a>
                <a href="{{ route('admin.weighing.index') }}" class="{{ request()->routeIs('admin.weighing.*') ? 'active' : '' }}">
                    <span><x-icon name="scale" size="18" /></span>
                    <span>Setoran</span>
                    @if(request()->routeIs('admin.weighing.*'))<i></i>@endif
                </a>
                <a href="{{ route('admin.residents.index') }}" class="{{ request()->routeIs('admin.residents.*') ? 'active' : '' }}">
                    <span><x-icon name="users" size="18" /></span>
                    <span>Warga</span>
                    @if(request()->routeIs('admin.residents.*'))<i></i>@endif
                </a>
                @can('admin-only')
                <a href="{{ route('admin.officers.index') }}" class="{{ request()->routeIs('admin.officers.*') ? 'active' : '' }}">
                    <span><x-icon name="user" size="18" /></span>
                    <span>Petugas RT</span>
                    @if(request()->routeIs('admin.officers.*'))<i></i>@endif
                </a>
                @endcan
                <a href="{{ route('admin.categories.index') }}" class="{{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                    <span><x-icon name="tag" size="18" /></span>
                    <span>Kategori &amp; Harga</span>
                    @if(request()->routeIs('admin.categories.*'))<i></i>@endif
                </a>
                <a href="{{ route('admin.reports.index') }}" class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                    <span><x-icon name="trending-up" size="18" /></span>
                    <span>Laporan</span>
                    @if(request()->routeIs('admin.reports.*'))<i></i>@endif
                </a>
                <a href="{{ route('admin.audit-log.index') }}" class="{{ request()->routeIs('admin.audit-log.*') ? 'active' : '' }}">
                    <span><x-icon name="shield" size="18" /></span>
                    <span>Log Audit</span>
                    @if(request()->routeIs('admin.audit-log.*'))<i></i>@endif
                </a>
            </nav>

            <a class="sidebar-cta" href="{{ route('admin.weighing.index') }}">
                <span>+</span>
                <div>
                    <strong>Setoran baru</strong>
                    <small>Catat hasil timbang</small>
                </div>
                <span>→</span>
            </a>

            <nav class="sidebar-nav sidebar-bottom" aria-label="Navigasi tambahan">
                <a href="{{ route('admin.help.index') }}" class="{{ request()->routeIs('admin.help.*') ? 'active' : '' }}">
                    <span><x-icon name="book-open" size="18" /></span>
                    <span>Pusat bantuan</span>
                    @if(request()->routeIs('admin.help.*'))<i></i>@endif
                </a>
                <a href="{{ route('home') }}" target="_blank">
                    <span><x-icon name="globe" size="18" /></span>
                    <span>Halaman warga ↗</span>
                </a>
            </nav>

            <div class="sidebar-profile">
                <div style="display: flex; align-items: center; gap: 9px; min-width: 0; flex: 1;">
                    <div class="avatar avatar-admin">{{ strtoupper(substr(auth()->user()->full_name, 0, 1)) }}</div>
                    <div style="display: flex; flex-direction: column; min-width: 0; gap: 2px; overflow: hidden;">
                        <strong style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 13px;">{{ auth()->user()->full_name }}</strong>
                        <small style="color: #94a3b8; font-size: 11px;">{{ auth()->user()->role === 'admin' ? 'Administrator' : 'Petugas Lapangan' }}</small>
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST" style="margin: 0; flex-shrink: 0;">
                    @csrf
                    <button type="submit" title="Keluar sesi" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 6px; display: flex; align-items: center;">
                        <x-icon name="log-out" size="18" />
                    </button>
                </form>
            </div>
        </aside>

        <main class="dashboard-main">
            <header class="topbar">
                <form action="{{ route('admin.residents.index') }}" method="GET" class="search-box" style="margin: 0;">
                    <span><x-icon name="search" size="16" /></span>
                    <input name="q" placeholder="Cari warga, no. rumah, atau WhatsApp..." value="{{ request('q') }}">
                    <button type="submit" style="display: none;"></button>
                    <kbd>Enter</kbd>
                </form>

                <div class="topbar-actions" style="display: flex; align-items: center; gap: 14px;">
                    @if(!request()->routeIs('admin.dashboard'))
                    <a href="{{ route('admin.weighing.index') }}" class="primary-button compact" style="gap: 6px; font-size: 13px;">
                        <x-icon name="plus" size="15" />
                        <span>Catat Timbang</span>
                    </a>
                    @endif
                    <div class="profile-chip" style="display: flex; align-items: center; gap: 8px;">
                        <div class="avatar avatar-admin">{{ strtoupper(substr(auth()->user()->full_name, 0, 1)) }}</div>
                        <div class="profile-copy">
                            <strong>{{ auth()->user()->full_name }}</strong>
                            <span>{{ auth()->user()->role === 'admin' ? 'Administrator' : 'Petugas Lapangan' }}</span>
                        </div>
                    </div>
                </div>
            </header>

            <div class="dashboard-content">
                @if($errors->any())
                <div class="admin-alert error" role="alert" style="margin-bottom: 20px;">
                    <ul>
                        @foreach($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                @if(session('info'))
                <div class="admin-alert" role="status" style="margin-bottom: 20px;">
                    {{ session('info') }}
                </div>
                @endif

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

                @yield('admin_content')
            </div>
        </main>
    </div>
</body>
</html>
