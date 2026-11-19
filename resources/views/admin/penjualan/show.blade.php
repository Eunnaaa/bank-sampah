@extends('layouts.admin')

@section('admin_content')
<div style="max-width: 800px; margin: 0 auto;">
    <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;" class="no-print">
        <a href="{{ route('admin.sales.index') }}" style="color: #64748b; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
            ← Kembali ke Daftar Penjualan
        </a>
        <button
            type="button"
            onclick="window.print()"
            class="primary-button"
            style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; font-weight: 700;"
        >
            <x-icon name="download" size="14" /> Cetak Nota Penjualan
        </button>
    </div>

    <!-- Kotak Nota Penjualan -->
    <div style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 16px; padding: 32px; box-shadow: 0 4px 16px rgba(0,0,0,0.04);">
        <!-- Kop Nota -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px dashed #e2e8f0; padding-bottom: 20px; margin-bottom: 24px;">
            <div>
                <span style="font-size: 11px; font-weight: 800; color: #0369a1; background: #e0f2fe; padding: 2px 8px; border-radius: 6px;">
                    BUKTI PENJUALAN KOMODITAS (B2B OFFTAKER)
                </span>
                <h1 style="margin: 8px 0 0; font-size: 22px; font-weight: 900; color: #173f35;">
                    Bank Sampah RT {{ $sale->tenant?->rt ?? '05' }} / RW {{ $sale->tenant?->rw ?? '08' }}
                </h1>
                <p style="margin: 4px 0 0; color: #64748b; font-size: 13px;">
                    {{ $sale->tenant?->name ?? 'Bank Sampah Lingkungan' }} · {{ $sale->tenant?->city ?? 'Jakarta' }}
                </p>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 12px; color: #94a3b8; font-weight: 600;">ID NOTA</div>
                <div style="font-family: monospace; font-size: 14px; font-weight: 700; color: #1e293b;">
                    #{{ strtoupper(substr($sale->id, 0, 8)) }}
                </div>
                <div style="font-size: 12.5px; color: #64748b; margin-top: 4px;">
                    {{ $sale->sale_date->translatedFormat('d F Y') }}
                </div>
            </div>
        </div>

        <!-- Info Pembeli & Petugas -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; background: #f8fafc; border-radius: 12px; padding: 16px; margin-bottom: 24px;">
            <div>
                <small style="color: #64748b; font-size: 11.5px; font-weight: 700; text-transform: uppercase;">Pembeli / Pengepul</small>
                <div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-top: 2px;">
                    {{ $sale->buyer_name }}
                </div>
            </div>
            <div>
                <small style="color: #64748b; font-size: 11.5px; font-weight: 700; text-transform: uppercase;">Petugas Pencatat</small>
                <div style="font-size: 14px; font-weight: 700; color: #334155; margin-top: 2px;">
                    {{ $sale->officer?->full_name ?? 'Petugas RT' }}
                </div>
            </div>
            <div>
                <small style="color: #64748b; font-size: 11.5px; font-weight: 700; text-transform: uppercase;">Status Pembayaran</small>
                <div style="margin-top: 2px;">
                    <span style="background: #dcfce7; color: #15803d; font-size: 11.5px; font-weight: 800; padding: 3px 8px; border-radius: 6px;">
                        Lunas Masuk Kas
                    </span>
                </div>
            </div>
        </div>

        <!-- Tabel Rincian Barang -->
        <table style="width: 100%; border-collapse: collapse; font-size: 13.5px; margin-bottom: 24px;">
            <thead>
                <tr style="border-bottom: 1.5px solid #cbd5e1; color: #475569; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em;">
                    <th style="padding: 10px 8px; text-align: left;">Komoditas</th>
                    <th style="padding: 10px 8px; text-align: right;">Bobot</th>
                    <th style="padding: 10px 8px; text-align: right;">Harga Jual</th>
                    <th style="padding: 10px 8px; text-align: right;">Modal Warga</th>
                    <th style="padding: 10px 8px; text-align: right;">Subtotal</th>
                    <th style="padding: 10px 8px; text-align: right;">Laba RT</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $it)
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 12px 8px; font-weight: 700; color: #1e293b;">
                        {{ $it->category?->name ?? 'Komoditas' }}
                    </td>
                    <td style="padding: 12px 8px; text-align: right; color: #173f35; font-weight: 600;">
                        {{ number_format($it->weight, 2, ',', '.') }} {{ $it->category?->unit ?? 'kg' }}
                    </td>
                    <td style="padding: 12px 8px; text-align: right; color: #64748b;">
                        Rp {{ number_format($it->selling_price_per_unit, 0, ',', '.') }}
                    </td>
                    <td style="padding: 12px 8px; text-align: right; color: #94a3b8; font-size: 12px;">
                        Rp {{ number_format($it->buying_price_per_unit, 0, ',', '.') }}
                    </td>
                    <td style="padding: 12px 8px; text-align: right; font-weight: 700; color: #047857;">
                        Rp {{ number_format($it->subtotal, 0, ',', '.') }}
                    </td>
                    <td style="padding: 12px 8px; text-align: right; font-weight: 800; color: #15803d; font-size: 12.5px;">
                        +Rp {{ number_format($it->profit_subtotal, 0, ',', '.') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Ringkasan Total & Laba -->
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; display: flex; flex-direction: column; gap: 8px;">
            <div style="display: flex; justify-content: space-between; font-size: 14px; color: #475569;">
                <span>Total Berat Keseluruhan:</span>
                <strong>{{ number_format($sale->items->filter(fn ($item) => $item->category?->unit === 'kg')->sum('weight'), 2, ',', '.') }} kg</strong>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 14px; color: #475569;">
                <span>Total Modal Beli dari Warga:</span>
                <span>Rp {{ number_format($sale->total_cost, 0, ',', '.') }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 16px; font-weight: 800; color: #047857; border-top: 1px solid #cbd5e1; padding-top: 8px;">
                <span>Total Kas Diterima (Pengepul):</span>
                <span>Rp {{ number_format($sale->total_revenue, 0, ',', '.') }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 16px; font-weight: 900; color: #15803d; background: #dcfce7; padding: 8px 12px; border-radius: 8px; margin-top: 4px;">
                <span>Laba Kotor Kas RT:</span>
                <span>+Rp {{ number_format($sale->gross_profit, 0, ',', '.') }}</span>
            </div>
        </div>

        @if($sale->notes)
        <div style="margin-top: 20px; font-size: 13px; color: #64748b; background: #fffbeb; border: 1px solid #fde68a; padding: 12px 16px; border-radius: 8px;">
            <strong>Catatan Operasional:</strong> {{ $sale->notes }}
        </div>
        @endif

        <div style="margin-top: 32px; text-align: center; color: #94a3b8; font-size: 12px; border-top: 1px solid #f1f5f9; padding-top: 16px;">
            Dokumen ini merupakan bukti sah pencatatan penjualan komoditas daur ulang pada Sistem Informasi Si-BankSampah RT {{ $sale->tenant?->rt ?? '05' }} / RW {{ $sale->tenant?->rw ?? '08' }}.
        </div>
    </div>
</div>
@endsection
