@extends('layouts.admin')

@section('admin_content')
<div class="reports-page" x-data="{
    showReceiptModal: false,
    receiptData: null,
    isLoadingReceipt: false,

    async viewReceipt(txId) {
        this.isLoadingReceipt = true;
        this.showReceiptModal = true;
        try {
            const resp = await fetch(`/admin/transaksi/${txId}`);
            const json = await resp.json();
            if (json.success) {
                this.receiptData = json.data;
            }
        } catch (e) {
            console.error('Gagal mengambil data struk transaksi', e);
        } finally {
            this.isLoadingReceipt = false;
        }
    },

    get waUrl() {
        if (!this.receiptData) return '#';
        let phone = this.receiptData.residentPhone.replace(/\D/g, '');
        if (phone.startsWith('0')) phone = '62' + phone.slice(1);
        if (!phone.startsWith('62')) phone = '62' + phone;

        const lines = this.receiptData.items.map(it => `• ${it.name}: ${it.weight} ${it.unit} × Rp ${Number(it.price).toLocaleString('id-ID')} = *Rp ${Number(it.subtotal).toLocaleString('id-ID')}*`).join('\n');
        const text = encodeURIComponent(
            `*STRUK DIGITAL BANK SAMPAH*\n------------------------------------\n*Nasabah:* ${this.receiptData.residentName}\n*Alamat:* ${this.receiptData.householdCode}\n*Tanggal:* ${this.receiptData.date}\n*Petugas:* ${this.receiptData.officerName}\n\n*Rincian Setoran:*\n${lines}\n------------------------------------\n*Total Transaksi Ini:* *Rp ${Number(this.receiptData.totalAmount).toLocaleString('id-ID')}*\n*Saldo Setelah Transaksi:* *Rp ${this.receiptData.newBalance == null ? 'Tidak tersedia' : Number(this.receiptData.newBalance).toLocaleString('id-ID')}*\n\n*Terima kasih atas kontribusi aktif Anda memilah sampah di lingkungan kita!*`
        );
        return `https://api.whatsapp.com/send?phone=${phone}&text=${text}`;
    }
}">
    <div class="page-header">
        <div>
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #059669; background: #d1fae5; padding: 2px 8px; border-radius: 6px;">
                    AUDIT &amp; LAPORAN KAS · RT {{ auth()->user()->rt ?? '05' }} / RW {{ auth()->user()->rw ?? '08' }}
                </span>
            </div>
            <h1>Laporan &amp; Rekapitulasi Kas</h1>
            <p>Unduh data rekapitulasi komoditas dan buku besar mutasi transaksi kas untuk transparansi audit RT/RW.</p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="{{ route('admin.reports.export-csv', request()->only('period', 'type')) }}" class="csv-button" style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                <x-icon name="download" size="14" /> Unduh Rekap Komoditas (CSV)
            </a>
            <a href="{{ route('admin.reports.export-ledger', request()->only('period', 'type')) }}" class="primary-button" style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                <x-icon name="bar-chart" size="14" /> Unduh Buku Besar Kas (CSV)
            </a>
        </div>
    </div>

    <!-- Filter Periode -->
    <div style="margin-top: 20px; display: flex; gap: 8px; flex-wrap: wrap;">
        <a href="{{ route('admin.reports.index', ['period' => 'today', 'type' => request('type')]) }}" class="btn-filter {{ $period === 'today' ? 'active' : '' }}">Hari Ini</a>
        <a href="{{ route('admin.reports.index', ['period' => 'month', 'type' => request('type')]) }}" class="btn-filter {{ $period === 'month' ? 'active' : '' }}">Bulan Ini</a>
        <a href="{{ route('admin.reports.index', ['period' => 'all', 'type' => request('type')]) }}" class="btn-filter {{ $period === 'all' ? 'active' : '' }}">Semua Periode</a>
    </div>

    <!-- Kartu Rekap Periode -->
    <div class="stats-grid" style="margin-top: 20px;">
        <div class="stat-card">
            <span class="stat-icon"><x-icon name="coins" size="18" /></span>
            <div class="stat-data">
                <small>Total Setoran Warga</small>
                <strong>Rp {{ number_format($totalDeposit, 0, ',', '.') }}</strong>
                <span>{{ $totalDepositCount }} transaksi setoran</span>
            </div>
        </div>
        <div class="stat-card">
            <span class="stat-icon"><x-icon name="dollar-sign" size="18" /></span>
            <div class="stat-data">
                <small>Total Pencairan Tunai</small>
                <strong>Rp {{ number_format($totalWithdrawal, 0, ',', '.') }}</strong>
                <span>Dana tabungan yang ditarik warga</span>
            </div>
        </div>
    </div>

    <!-- Tabel Rekap Per Komoditas -->
    <div class="table-card" style="background: #fff; border-radius: 12px; border: 1px solid var(--border-color); overflow-x: auto; margin-top: 24px;">
        <h3 style="padding: 16px; margin: 0; border-bottom: 1px solid var(--border-color);">Rekapitulasi Volume Per Komoditas</h3>
        <table class="admin-table" style="width: 100%; border-collapse: collapse; min-width: 650px;">
            <thead>
                <tr style="background: #f8faf8; border-bottom: 1px solid var(--border-color); text-align: left;">
                    <th style="padding: 14px 16px;">Nama Komoditas</th>
                    <th style="padding: 14px 16px;">Satuan</th>
                    <th style="padding: 14px 16px; text-align: right;">Total Bobot Terkumpul</th>
                    <th style="padding: 14px 16px; text-align: right;">Total Nilai Rupiah</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categoriesSummary as $cat)
                <tr style="border-bottom: 1px solid #f0f0f0;">
                    <td style="padding: 14px 16px; font-weight: 600;">{{ $cat['name'] }}</td>
                    <td style="padding: 14px 16px; color: #64748b;">{{ $cat['unit'] }}</td>
                    <td style="padding: 14px 16px; text-align: right; font-weight: 700;">{{ number_format($cat['total_weight'], 2, ',', '.') }} {{ $cat['unit'] }}</td>
                    <td style="padding: 14px 16px; text-align: right; font-weight: 700; color: #1b5e20;">Rp {{ number_format($cat['total_subtotal'], 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Tabel Buku Besar Mutasi Kas (Ledger View) -->
    <div class="table-card" style="background: #fff; border-radius: 12px; border: 1px solid var(--border-color); overflow-x: auto; margin-top: 28px;">
        <div style="padding: 16px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <div>
                <h3 style="margin: 0;">Buku Besar Mutasi Kas Transaksi</h3>
                <small style="color: #64748b;">Rincian transaksi masuk &amp; keluar untuk periode terpilih</small>
            </div>
            <div style="display: flex; gap: 8px;">
                <a href="{{ route('admin.reports.index', ['period' => $period]) }}" class="btn-outline-small {{ !request('type') ? 'active' : '' }}" style="padding: 4px 10px; font-size: 0.8rem;">Semua Jenis</a>
                <a href="{{ route('admin.reports.index', ['period' => $period, 'type' => 'deposit']) }}" class="btn-outline-small {{ request('type') === 'deposit' ? 'active' : '' }}" style="padding: 4px 10px; font-size: 0.8rem;">Setoran Sahaja</a>
                <a href="{{ route('admin.reports.index', ['period' => $period, 'type' => 'withdrawal']) }}" class="btn-outline-small {{ request('type') === 'withdrawal' ? 'active' : '' }}" style="padding: 4px 10px; font-size: 0.8rem;">Pencairan Sahaja</a>
            </div>
        </div>

        <table class="admin-table" style="width: 100%; border-collapse: collapse; min-width: 850px;">
            <thead>
                <tr style="background: #f8faf8; border-bottom: 1px solid var(--border-color); text-align: left;">
                    <th style="padding: 12px 16px;">Waktu</th>
                    <th style="padding: 12px 16px;">Jenis</th>
                    <th style="padding: 12px 16px;">Nasabah</th>
                    <th style="padding: 12px 16px;">Rincian Item</th>
                    <th style="padding: 12px 16px; text-align: right;">Nominal</th>
                    <th style="padding: 12px 16px;">Petugas</th>
                    <th style="padding: 12px 16px; text-align: center;">Struk</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $tx)
                <tr style="border-bottom: 1px solid #f0f0f0; font-size: 0.9rem;">
                    <td style="padding: 12px 16px; color: #555; white-space: nowrap;">
                        {{ $tx->created_at->timezone(config('app.business_timezone'))->translatedFormat('d M Y, H:i') }}
                    </td>
                    <td style="padding: 12px 16px;">
                        @if($tx->type === 'deposit')
                            <span style="background: #dcfce7; color: #15803d; padding: 2px 8px; border-radius: 4px; font-weight: 700; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 4px;">
                                <x-icon name="deposit" size="12" /> SETORAN
                            </span>
                        @else
                            <span style="background: #fee2e2; color: #b91c1c; padding: 2px 8px; border-radius: 4px; font-weight: 700; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 4px;">
                                <x-icon name="withdrawal" size="12" /> PENCAIRAN
                            </span>
                        @endif
                    </td>
                    <td style="padding: 12px 16px; font-weight: 600;">
                        {{ $tx->resident?->full_name ?? '-' }}<br>
                        <small style="color: #64748b; font-weight: 400;">{{ $tx->resident?->household_code }}</small>
                    </td>
                    <td style="padding: 12px 16px; font-size: 0.85rem; color: #334155;">
                        @if($tx->items->count() > 0)
                            @foreach($tx->items as $it)
                                <span>{{ $it->category?->name }}: <b>{{ $it->weight }} {{ $it->category?->unit }}</b></span>{{ !$loop->last ? ' · ' : '' }}
                            @endforeach
                        @else
                            <span style="color: #64748b;">{{ $tx->notes ?? 'Penarikan tunai' }}</span>
                        @endif
                    </td>
                    <td style="padding: 12px 16px; text-align: right; font-weight: 700; font-size: 0.95rem;" :style="'color: ' + ('{{ $tx->type }}' === 'deposit' ? '#166534' : '#991b1b')">
                        {{ $tx->type === 'deposit' ? '+' : '-' }}Rp {{ number_format($tx->total_amount, 0, ',', '.') }}
                    </td>
                    <td style="padding: 12px 16px; color: #64748b; font-size: 0.85rem;">
                        {{ $tx->officer?->full_name ?? '-' }}
                    </td>
                    <td style="padding: 12px 16px; text-align: center;">
                        <button type="button" class="btn-outline-small" @click="viewReceipt('{{ $tx->id }}')" style="padding: 4px 8px; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 4px;" title="Lihat Struk Transaksi">
                            <x-icon name="receipt" size="13" /> Struk
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="padding: 24px; text-align: center; color: #888;">
                        Belum ada data mutasi transaksi pada periode ini.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 20px;">
        {{ $transactions->links() }}
    </div>

    <!-- Modal Detail Struk Digital & Kirim WhatsApp -->
    <div class="overlay modal-overlay" x-show="showReceiptModal" style="display: none;" x-cloak>
        <div class="modal-card" style="max-width: 500px;">
            <div class="modal-head">
                <h3>Struk Bukti Transaksi</h3>
                <button type="button" class="modal-close" @click="showReceiptModal = false">&times;</button>
            </div>
            <div style="padding: 12px 0;">
                <div x-show="isLoadingReceipt" style="text-align: center; padding: 20px; color: #64748b;">
                    Memuat data transaksi...
                </div>
                <div x-show="!isLoadingReceipt && receiptData">
                    <div style="background: #f8faf8; border: 1px solid #e2e8f0; padding: 14px; border-radius: 8px; font-size: 0.88rem; line-height: 1.6;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span>Tanggal:</span>
                            <strong x-text="receiptData?.date"></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span>Nasabah:</span>
                            <strong x-text="receiptData?.residentName"></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span>Alamat:</span>
                            <span x-text="receiptData?.householdCode"></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span>Petugas:</span>
                            <span x-text="receiptData?.officerName"></span>
                        </div>

                        <div style="border-top: 1px dashed #cbd5e1; margin-top: 10px; padding-top: 10px;">
                            <template x-for="(it, i) in receiptData?.items" :key="i">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                    <span x-text="`${it.name} (${it.weight} ${it.unit})`"></span>
                                    <strong x-text="'Rp ' + Number(it.subtotal).toLocaleString('id-ID')"></strong>
                                </div>
                            </template>
                        </div>

                        <div style="border-top: 1px solid #94a3b8; margin-top: 10px; padding-top: 10px; display: flex; justify-content: space-between; font-size: 1rem; color: #166534;">
                            <strong>Total Transaksi:</strong>
                            <strong x-text="'Rp ' + Number(receiptData?.totalAmount).toLocaleString('id-ID')"></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: 4px; font-size: 0.9rem; color: #15803d;">
                            <span>Saldo Setelah Transaksi:</span>
                            <strong x-text="receiptData?.newBalance == null ? 'Tidak tersedia untuk transaksi lama' : 'Rp ' + Number(receiptData.newBalance).toLocaleString('id-ID')"></strong>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-actions" style="margin-top: 14px; display: flex; gap: 8px;">
                <button type="button" class="secondary-button" @click="showReceiptModal = false" style="flex: 1;">Tutup</button>
                <a :href="waUrl" target="_blank" class="primary-button" style="flex: 2; justify-content: center; background: #25D366; border-color: #25D366;">
                    <span><x-icon name="message-square" size="16" /></span> Kirim ke WhatsApp Warga
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
