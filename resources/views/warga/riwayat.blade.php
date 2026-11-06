@extends('layouts.warga', ['title' => 'Riwayat Mutasi Transaksi — Si-BankSampah'])

@section('warga_content')
<div class="warga-riwayat-wrap" x-data="{
    showReceiptModal: false,
    receiptData: null,
    async openReceipt(txId) {
        try {
            const response = await fetch(`/warga/transaksi/${txId}`, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Struk tidak dapat dimuat.');
            this.receiptData = (await response.json()).data;
            this.showReceiptModal = true;
        } catch (error) {
            alert(error.message);
        }
    },
    get waUrl() {
        if (!this.receiptData) return '#';
        let phone = (this.receiptData.residentPhone || '').replace(/\D/g, '');
        if (phone.startsWith('0')) phone = '62' + phone.slice(1);
        if (!phone.startsWith('62')) phone = '62' + phone;

        const isDeposit = (this.receiptData.type || 'deposit') === 'deposit';
        let msg = '';
        if (isDeposit) {
            const lines = (this.receiptData.items || []).map(it => `• ${it.name || 'Komoditas'}: ${it.weight} ${it.unit || 'kg'} × Rp ${Number(it.price).toLocaleString('id-ID')} = *Rp ${Number(it.subtotal).toLocaleString('id-ID')}*`).join('\n');
            msg = `*STRUK DIGITAL SETORAN BANK SAMPAH*\n------------------------------------\n*Nasabah:* ${this.receiptData.residentName}\n*Alamat:* ${this.receiptData.householdCode}\n*Tanggal:* ${this.receiptData.date}\n*Petugas:* ${this.receiptData.officerName}\n\n*Rincian Setoran:*\n${lines}\n------------------------------------\n*Total Setoran Masuk:* *Rp ${Number(this.receiptData.totalAmount).toLocaleString('id-ID')}*\n\n*Terima kasih atas kontribusi aktif Anda menjaga kebersihan lingkungan!*`;
        } else {
            msg = `*BUKTI PENCAIRAN SALDO TUNAI*\n------------------------------------\n*Nasabah:* ${this.receiptData.residentName}\n*Alamat:* ${this.receiptData.householdCode}\n*Tanggal:* ${this.receiptData.date}\n*Petugas:* ${this.receiptData.officerName}\n------------------------------------\n*Nominal Dicairkan:* *-Rp ${Number(this.receiptData.totalAmount).toLocaleString('id-ID')}*\n*Catatan:* ${this.receiptData.notes || 'Pencairan tunai di Pos RT'}\n\n*Pencairan dana tabungan bank sampah telah berhasil diproses.*`;
        }
        return `https://api.whatsapp.com/send?phone=${phone}&text=${encodeURIComponent(msg)}`;
    }
}">
    <!-- Header Halaman -->
    <div class="warga-page-header">
        <div>
            <span class="section-label">BUKU MUTASI TABUNGAN</span>
            <h1>Riwayat Setoran &amp; Penarikan</h1>
            <p>Daftar seluruh aktivitas transaksi setoran sampah dan penarikan saldo tabungan Anda.</p>
        </div>
    </div>

    <!-- Ringkasan Filter & Statistik Ringkas -->
    <div class="riwayat-stats-row">
        <div class="riwayat-stat-box">
            <span class="stat-badge-icon emerald"><x-icon name="coins" size="18" /></span>
            <div>
                <small>Akumulasi Setoran Periode Ini</small>
                <strong>+Rp {{ number_format($totalDeposited, 0, ',', '.') }}</strong>
            </div>
        </div>
        <div class="riwayat-stat-box">
            <span class="stat-badge-icon red"><x-icon name="dollar-sign" size="18" /></span>
            <div>
                <small>Akumulasi Penarikan Periode Ini</small>
                <strong style="color: #991b1b;">-Rp {{ number_format($totalWithdrawn, 0, ',', '.') }}</strong>
            </div>
        </div>
    </div>

    <!-- Filter Control Bar -->
    <div class="warga-filter-bar" style="background: #ffffff; border: 1px solid #dce6df; border-radius: 16px; box-shadow: 0 4px 18px rgba(16, 56, 45, 0.04); padding: 16px; margin-top: 16px;">
        <form action="{{ route('warga.riwayat') }}" method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; justify-content: space-between;">
            <!-- Filter Jenis -->
            <div style="display: flex; gap: 6px; flex-wrap: wrap; align-items: center;">
                <span style="font-size: 13px; font-weight: 700; color: #475569; margin-right: 4px;">Jenis:</span>
                <a href="{{ route('warga.riwayat', array_merge(request()->all(), ['type' => 'all'])) }}" class="btn-filter {{ $type === 'all' ? 'active' : '' }}">Semua</a>
                <a href="{{ route('warga.riwayat', array_merge(request()->all(), ['type' => 'deposit'])) }}" class="btn-filter {{ $type === 'deposit' ? 'active' : '' }}">Setoran</a>
                <a href="{{ route('warga.riwayat', array_merge(request()->all(), ['type' => 'withdrawal'])) }}" class="btn-filter {{ $type === 'withdrawal' ? 'active' : '' }}">Penarikan</a>
            </div>

            <!-- Filter Periode & Search -->
            <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                <select name="period" class="calc-select" style="width: auto; padding: 6px 12px; font-size: 13px;" onchange="this.form.submit()">
                    <option value="all" {{ $period === 'all' ? 'selected' : '' }}>Semua Periode</option>
                    <option value="month" {{ $period === 'month' ? 'selected' : '' }}>Bulan Ini</option>
                    <option value="3months" {{ $period === '3months' ? 'selected' : '' }}>3 Bulan Terakhir</option>
                    <option value="year" {{ $period === 'year' ? 'selected' : '' }}>Tahun Ini</option>
                </select>

                <div style="position: relative; display: flex; align-items: center;">
                    <input
                        type="text"
                        name="q"
                        value="{{ $query }}"
                        placeholder="Cari komoditas atau catatan..."
                        class="calc-input"
                        style="width: 220px; height: 36px; padding: 6px 12px; font-size: 13px;"
                    >
                </div>
                <button type="submit" class="primary-button" style="min-height: 36px; padding: 0 14px; font-size: 13px;">
                    <x-icon name="search" size="14" />
                    <span>Filter</span>
                </button>
                @if($type !== 'all' || $period !== 'all' || !empty($query))
                    <a href="{{ route('warga.riwayat') }}" class="secondary-button" style="min-height: 36px; padding: 0 12px; font-size: 13px;" title="Reset filter">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tabel Data Transaksi -->
    <div class="table-responsive" style="background: #ffffff; border: 1px solid #dce6df; border-radius: 16px; box-shadow: 0 4px 18px rgba(16, 56, 45, 0.04); margin-top: 16px; overflow-x: auto;">
        <table class="warga-data-table">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Jenis Transaksi</th>
                    <th>Rincian Komoditas</th>
                    <th style="text-align: right;">Nominal</th>
                    <th>Petugas</th>
                    <th style="text-align: center;">Bukti Struk</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $tx)
                <tr>
                    <td style="color: #475569; font-size: 13px; white-space: nowrap;">
                        {{ $tx->created_at->timezone(config('app.business_timezone'))->translatedFormat('d M Y, H:i') }}
                    </td>
                    <td>
                        @if($tx->type === 'deposit')
                            <span class="badge-tx deposit">
                                <x-icon name="deposit" size="12" /> Setoran Masuk
                            </span>
                        @else
                            <span class="badge-tx withdrawal">
                                <x-icon name="withdrawal" size="12" /> Tarik Tunai
                            </span>
                        @endif
                    </td>
                    <td style="font-size: 13px; color: #334155;">
                        @if($tx->items->count() > 0)
                            @foreach($tx->items as $it)
                                <span>{{ $it->category?->name }}: <b>{{ $it->weight }} {{ $it->category?->unit }}</b></span>{{ !$loop->last ? ' · ' : '' }}
                            @endforeach
                        @else
                            <span style="color: #64748b;">{{ $tx->notes ?? 'Pencairan tabungan tunai' }}</span>
                        @endif
                    </td>
                    <td style="text-align: right; font-weight: 700; font-size: 14px; color: {{ $tx->type === 'deposit' ? '#166534' : '#991b1b' }};">
                        {{ $tx->type === 'deposit' ? '+' : '-' }}Rp {{ number_format($tx->total_amount, 0, ',', '.') }}
                    </td>
                    <td style="color: #64748b; font-size: 13px;">
                        {{ $tx->officer?->full_name ?? 'Petugas RT' }}
                    </td>
                    <td style="text-align: center;">
                        <button type="button" class="btn-outline-small" @click="openReceipt('{{ $tx->id }}')" style="padding: 4px 10px; font-size: 12px; display: inline-flex; align-items: center; gap: 4px;">
                            <x-icon name="receipt" size="13" />
                            <span>Lihat Struk</span>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8;">
                        <x-icon name="search" size="32" />
                        <p style="margin: 8px 0 0; font-weight: 600;">Tidak ditemukan data transaksi yang cocok.</p>
                        <small>Coba sesuaikan kata kunci pencarian atau ubah filter periode waktu Anda.</small>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div style="margin-top: 20px;">
        {{ $transactions->links() }}
    </div>

    <!-- Modal Struk Digital Nasabah -->
    <div class="modal-overlay" x-show="showReceiptModal" style="display: none;" x-cloak>
        <div class="modal-card" style="max-width: 480px;">
            <div class="modal-head">
                <h3>Struk Bukti Transaksi</h3>
                <button type="button" class="modal-close" @click="showReceiptModal = false">&times;</button>
            </div>
            <div style="padding: 16px 0;" x-show="receiptData">
                <div style="background: #f8faf8; border: 1px solid #e2e8f0; padding: 16px; border-radius: 10px; font-size: 13.5px; line-height: 1.6;">
                    <div style="text-align: center; border-bottom: 1px dashed #cbd5e1; padding-bottom: 12px; margin-bottom: 12px;">
                        <strong style="font-size: 15px; color: #173f35;">SI-BANK SAMPAH RT 05 / RW 08</strong><br>
                        <small style="color: #64748b;" x-text="receiptData?.type === 'deposit' ? 'BUKTI PENIMBANGAN & SETORAN' : 'BUKTI PENCAIRAN SALDO TUNAI'"></small>
                    </div>

                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                        <span>Waktu Transaksi:</span>
                        <strong x-text="receiptData?.date"></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                        <span>Nasabah:</span>
                        <strong x-text="receiptData?.residentName"></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                        <span>Alamat:</span>
                        <span x-text="receiptData?.householdCode"></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                        <span>Petugas Penimbang:</span>
                        <span x-text="receiptData?.officerName || 'Petugas RT'"></span>
                    </div>

                    <div style="border-top: 1px dashed #cbd5e1; margin-top: 10px; padding-top: 10px;" x-show="receiptData?.items && receiptData?.items.length > 0">
                        <template x-for="(it, i) in receiptData?.items" :key="i">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                <span x-text="`${it.name || 'Komoditas'}: ${it.weight} ${it.unit || 'kg'} × Rp ${Number(it.price).toLocaleString('id-ID')}`"></span>
                                <strong x-text="'Rp ' + Number(it.subtotal).toLocaleString('id-ID')"></strong>
                            </div>
                        </template>
                    </div>

                    <div style="display: flex; justify-content: space-between; margin-top: 10px;">
                        <span>Saldo Setelah Transaksi:</span>
                        <strong x-text="receiptData?.newBalance == null ? 'Tidak tersedia untuk transaksi lama' : 'Rp ' + Number(receiptData.newBalance).toLocaleString('id-ID')"></strong>
                    </div>
                    <div style="border-top: 1px solid #173f35; margin-top: 12px; padding-top: 10px; display: flex; justify-content: space-between; font-size: 15px; color: #173f35;">
                        <strong x-text="receiptData?.type === 'deposit' ? 'Total Setoran Masuk:' : 'Total Penarikan:'"></strong>
                        <strong x-text="'Rp ' + Number(receiptData?.totalAmount || 0).toLocaleString('id-ID')"></strong>
                    </div>
                </div>
            </div>
            <div class="modal-actions" style="margin-top: 16px; display: flex; gap: 10px;">
                <button type="button" class="secondary-button" @click="showReceiptModal = false" style="flex: 1;">Tutup Struk</button>
                <a :href="waUrl" target="_blank" class="primary-button" style="flex: 2; justify-content: center; background: #25D366; border-color: #25D366; color: white;">
                    <span><x-icon name="message-square" size="16" /></span> Bagikan ke WhatsApp
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
