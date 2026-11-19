@extends('layouts.admin')

@section('admin_content')
<div style="display: flex; flex-direction: column; gap: 26px;">
    <!-- 1. Header Halaman -->
    <div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-end; gap: 20px; flex-wrap: wrap;">
        <div>
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #0369a1; background: #e0f2fe; padding: 2px 8px; border-radius: 6px;">
                    LOGISTIK &amp; OFFTAKER B2B · RT {{ auth()->user()->rt ?? '05' }} / RW {{ auth()->user()->rw ?? '08' }}
                </span>
            </div>
            <h1 style="margin: 0; font-size: 24px; font-weight: 800; color: #173f35; letter-spacing: -0.02em;">Gudang &amp; Penjualan ke Pengepul</h1>
            <p style="margin: 6px 0 0; color: #64748b; font-size: 14px;">
                Pantau sisa stok sampah siap angkut di posko dan catat penerimaan kas dari penjualan ke pabrik/pengepul daur ulang.
            </p>
        </div>
        <div>
            <a href="{{ route('admin.sales.create') }}" class="primary-button" style="display: inline-flex; align-items: center; gap: 8px; text-decoration: none; padding: 10px 18px; border-radius: 10px; font-weight: 700;">
                <x-icon name="plus" size="16" />
                Catat Penjualan ke Pengepul
            </a>
        </div>
    </div>

    @if(session('success'))
    <div style="background: #ecfdf5; border: 1.5px solid #a7f3d0; color: #065f46; padding: 14px 18px; border-radius: 12px; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 10px;">
        <x-icon name="check-circle" size="20" />
        <span>{{ session('success') }}</span>
    </div>
    @endif

    <!-- 2. Ringkasan Metrik Gudang & Keuangan B2B -->
    <section class="metric-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
        <article class="metric-card">
            <div class="metric-topline">
                <span class="metric-icon lime"><x-icon name="package" size="18" /></span>
            </div>
            <span class="metric-label">Stok Fisik di Gudang</span>
            <div class="metric-value-row">
                <strong>{{ number_format($metrics['total_stock_kg'], 1, ',', '.') }}</strong>
                <span>kg</span>
            </div>
            <div class="metric-foot">
                <span>Komoditas yang dicatat dalam kilogram</span>
            </div>
        </article>

        <article class="metric-card">
            <div class="metric-topline">
                <span class="metric-icon blue"><x-icon name="coins" size="18" /></span>
            </div>
            <span class="metric-label">Estimasi Nilai Stok</span>
            <div class="metric-value-row">
                <strong>Rp {{ number_format($metrics['total_stock_value'], 0, ',', '.') }}</strong>
            </div>
            <div class="metric-foot">
                <span>Berdasarkan harga beli di katalog saat ini</span>
            </div>
        </article>

        <article class="metric-card">
            <div class="metric-topline">
                <span class="metric-icon green"><x-icon name="trending-up" size="18" /></span>
            </div>
            <span class="metric-label">Kas Masuk dari Pengepul</span>
            <div class="metric-value-row">
                <strong>Rp {{ number_format($metrics['total_offtaker_revenue'], 0, ',', '.') }}</strong>
            </div>
            <div class="metric-foot">
                <span>Total omzet penjualan komoditas</span>
            </div>
        </article>

        <article class="metric-card">
            <div class="metric-topline">
                <span class="metric-icon violet"><x-icon name="award" size="18" /></span>
            </div>
            <span class="metric-label">Laba Kotor</span>
            <div class="metric-value-row">
                <strong style="color: #15803d;">Rp {{ number_format($metrics['total_gross_profit'], 0, ',', '.') }}</strong>
            </div>
            <div class="metric-foot">
                <span>Selisih harga jual vs harga beli</span>
            </div>
        </article>
    </section>

    <!-- 3. Tabel Stok Inventaris Gudang per Komoditas -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 22px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
            <div>
                <h2 style="margin: 0; font-size: 16px; font-weight: 800; color: #173f35;">Inventaris Komoditas di Gudang RT</h2>
                <small style="color: #64748b; font-size: 13px;">Dihitung otomatis: Total Setoran Warga dikurangi Total Penjualan Keluar</small>
            </div>
            <span style="font-size: 12px; font-weight: 600; color: #475569; background: #f1f5f9; padding: 4px 12px; border-radius: 20px;">
                {{ $inventorySummary->count() }} Jenis Komoditas Aktif
            </span>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13.5px; text-align: left;">
                <thead>
                    <tr style="border-bottom: 2px solid #e2e8f0; color: #475569; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em;">
                        <th style="padding: 10px 12px;">Komoditas</th>
                        <th style="padding: 10px 12px;">Total Masuk (Warga)</th>
                        <th style="padding: 10px 12px;">Total Terjual (Pengepul)</th>
                        <th style="padding: 10px 12px;">Stok di Gudang</th>
                        <th style="padding: 10px 12px;">Tarif Beli Warga</th>
                        <th style="padding: 10px 12px;">Estimasi Nilai Stok</th>
                        <th style="padding: 10px 12px; text-align: center;">Status Logistik</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inventorySummary as $item)
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 12px; font-weight: 700; color: #1e293b;">
                            {{ $item['name'] }}
                            @unless($item['is_active']) (Nonaktif) @endunless
                        </td>
                        <td style="padding: 12px; color: #047857; font-weight: 600;">
                            +{{ number_format($item['in_weight'], 1, ',', '.') }} {{ $item['unit'] }}
                        </td>
                        <td style="padding: 12px; color: #b91c1c; font-weight: 600;">
                            -{{ number_format($item['out_weight'], 1, ',', '.') }} {{ $item['unit'] }}
                        </td>
                        <td style="padding: 12px;">
                            <strong style="font-size: 15px; color: {{ $item['stock_weight'] > 0 ? '#173f35' : '#94a3b8' }};">
                                {{ number_format($item['stock_weight'], 2, ',', '.') }} {{ $item['unit'] }}
                            </strong>
                        </td>
                        <td style="padding: 12px; color: #64748b;">
                            Rp {{ number_format($item['buying_price'], 0, ',', '.') }} / {{ $item['unit'] }}
                        </td>
                        <td style="padding: 12px; font-weight: 700; color: #1e293b;">
                            Rp {{ number_format($item['estimated_stock_value'], 0, ',', '.') }}
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            @if($item['unit'] === 'kg' && $item['stock_weight'] >= 50)
                                <span style="background: #fef3c7; color: #92400e; font-weight: 700; font-size: 11px; padding: 4px 10px; border-radius: 8px; border: 1px solid #fde68a;">
                                    🚛 Siap Angkut (≥50 kg)
                                </span>
                            @elseif($item['stock_weight'] > 0)
                                <span style="background: #ecfdf5; color: #065f46; font-weight: 700; font-size: 11px; padding: 4px 10px; border-radius: 8px; border: 1px solid #a7f3d0;">
                                    ✓ Tersimpan
                                </span>
                            @elseif($item['stock_weight'] < 0)
                                <span>Perlu rekonsiliasi</span>
                            @else
                                <span style="background: #f1f5f9; color: #64748b; font-weight: 600; font-size: 11px; padding: 4px 10px; border-radius: 8px;">
                                    Kosong
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="padding: 24px; text-align: center; color: #94a3b8;">
                            Belum ada data komoditas.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 4. Riwayat Transaksi Penjualan ke Pengepul -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 22px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
            <div>
                <h2 style="margin: 0; font-size: 16px; font-weight: 800; color: #173f35;">Riwayat Penjualan ke Pengepul / Bandar</h2>
                <small style="color: #64748b; font-size: 13px;">Daftar transaksi pelepasan komoditas daur ulang ke pihak ketiga</small>
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13.5px; text-align: left;">
                <thead>
                    <tr style="border-bottom: 2px solid #e2e8f0; color: #475569; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em;">
                        <th style="padding: 10px 12px;">Tanggal</th>
                        <th style="padding: 10px 12px;">Nama Pembeli / Pengepul</th>
                        <th style="padding: 10px 12px;">Rincian Komoditas</th>
                        <th style="padding: 10px 12px;">Total Berat</th>
                        <th style="padding: 10px 12px;">Total Kas Masuk</th>
                        <th style="padding: 10px 12px;">Laba Kotor</th>
                        <th style="padding: 10px 12px;">Petugas</th>
                        <th style="padding: 10px 12px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 12px; color: #475569; font-weight: 600;">
                            {{ $sale->sale_date->format('d/m/Y') }}
                        </td>
                        <td style="padding: 12px; font-weight: 700; color: #0f172a;">
                            {{ $sale->buyer_name }}
                        </td>
                        <td style="padding: 12px; font-size: 12.5px; color: #334155;">
                            @foreach($sale->items as $it)
                                <div>• {{ $it->category?->name ?? 'Komoditas' }}: {{ number_format($it->weight, 2, ',', '.') }} {{ $it->category?->unit ?? 'kg' }}</div>
                            @endforeach
                        </td>
                        <td style="padding: 12px; font-weight: 700; color: #173f35;">
                            {{ number_format($sale->items->filter(fn ($item) => $item->category?->unit === 'kg')->sum('weight'), 2, ',', '.') }} kg
                        </td>
                        <td style="padding: 12px; font-weight: 700; color: #047857;">
                            Rp {{ number_format($sale->total_revenue, 0, ',', '.') }}
                        </td>
                        <td style="padding: 12px;">
                            <span style="display: inline-block; padding: 3px 8px; border-radius: 6px; font-weight: 800; font-size: 12px; {{ $sale->gross_profit >= 0 ? 'background: #dcfce7; color: #15803d;' : 'background: #fee2e2; color: #b91c1c;' }}">
                                {{ $sale->gross_profit >= 0 ? '+' : '' }}Rp {{ number_format($sale->gross_profit, 0, ',', '.') }}
                            </span>
                        </td>
                        <td style="padding: 12px; color: #64748b; font-size: 12.5px;">
                            {{ $sale->officer?->full_name ?? 'Petugas RT' }}
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <a href="{{ route('admin.sales.show', $sale->id) }}" style="display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; color: #0f172a; text-decoration: none; font-size: 12px; font-weight: 600;">
                                <x-icon name="file-text" size="14" /> Nota
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="padding: 32px; text-align: center; color: #94a3b8;">
                            Belum ada riwayat penjualan sampah ke pengepul.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($sales->hasPages())
        <div style="margin-top: 18px;">
            {{ $sales->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
