@extends('layouts.admin')

@section('admin_content')
<div class="audit-log-page">
    <div class="page-header">
        <div>
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #7c3aed; background: #ede9fe; padding: 2px 8px; border-radius: 6px;">
                    KEAMANAN &amp; AUDIT TRAIL · RT {{ auth()->user()->rt ?? '05' }} / RW {{ auth()->user()->rw ?? '08' }}
                </span>
            </div>
            <h1>Log Audit &amp; Kepatuhan Sistem</h1>
            <p>Rekam jejak setiap aktivitas pengurus, transaksi keuangan, penimbangan, dan perubahan konfigurasi sistem.</p>
        </div>
    </div>

    <!-- Filter & Pencarian Audit Log -->
    <div class="table-controls" style="margin-top: 20px; display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between;">
        <form action="{{ route('admin.audit-log.index') }}" method="GET" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari pelaku atau detail..." class="calc-input" style="max-width: 320px;">
            <select name="action" class="calc-select" style="max-width: 220px;" onchange="this.form.submit()">
                <option value="">-- Semua Jenis Aksi --</option>
                @foreach($distinctActions as $act)
                    <option value="{{ $act }}" {{ request('action') === $act ? 'selected' : '' }}>{{ $act }}</option>
                @endforeach
            </select>
            <button type="submit" class="secondary-button">Filter</button>
            @if(request()->hasAny(['q', 'action']))
                <a href="{{ route('admin.audit-log.index') }}" class="btn-outline-small" style="padding: 8px 12px;">Reset</a>
            @endif
        </form>
    </div>

    <!-- Tabel Audit Log -->
    <div class="table-card" style="background: #fff; border-radius: 12px; border: 1px solid var(--border-color); overflow: hidden; margin-top: 16px;">
        <table class="admin-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8faf8; border-bottom: 1px solid var(--border-color); text-align: left;">
                    <th style="padding: 12px 16px;">Waktu &amp; Tanggal</th>
                    <th style="padding: 12px 16px;">Aktor</th>
                    <th style="padding: 12px 16px;">Aksi</th>
                    <th style="padding: 12px 16px;">Target</th>
                    <th style="padding: 12px 16px;">Detail Keterangan</th>
                    <th style="padding: 12px 16px;">Alamat IP</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr style="border-bottom: 1px solid #f0f0f0; font-size: 0.9rem;">
                    <td style="padding: 12px 16px; color: #555; white-space: nowrap;">
                        {{ $log->created_at ? $log->created_at->timezone(config('app.business_timezone'))->translatedFormat('d M Y, H:i:s') : '-' }}
                    </td>
                    <td style="padding: 12px 16px; font-weight: 600;">
                        {{ $log->actor_name ?? 'Sistem' }}
                    </td>
                    <td style="padding: 12px 16px;">
                        @php
                            $badgeColor = match($log->action) {
                                'STAFF_LOGIN' => '#0284c7',
                                'RECORD_DEPOSIT' => '#16a34a',
                                'RECORD_WITHDRAWAL' => '#dc2626',
                                'CREATE_RESIDENT', 'CREATE_CATEGORY' => '#8b5cf6',
                                'UPDATE_PRICE', 'UPDATE_RESIDENT', 'UPDATE_CATEGORY' => '#ea580c',
                                default => '#475569'
                            };
                        @endphp
                        <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700; color: #fff; background: {{ $badgeColor }};">
                            {{ $log->action }}
                        </span>
                    </td>
                    <td style="padding: 12px 16px; color: #64748b; font-size: 0.85rem;">
                        {{ $log->target_type }}: <code style="font-size: 0.8rem;">{{ substr($log->target_id, 0, 8) }}...</code>
                    </td>
                    <td style="padding: 12px 16px; color: #334155;">
                        {{ $log->details }}
                    </td>
                    <td style="padding: 12px 16px; color: #64748b; font-size: 0.85rem; font-family: monospace;">
                        {{ $log->ip_address ?? '127.0.0.1' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="padding: 30px; text-align: center; color: #888;">
                        Belum ada data log audit yang tercatat.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 20px;">
        {{ $logs->links() }}
    </div>
</div>
@endsection
