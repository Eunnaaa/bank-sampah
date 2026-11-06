@extends('layouts.admin')

@section('admin_content')
<div x-data="{
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
            console.error('Gagal mengambil data struk', e);
        } finally {
            this.isLoadingReceipt = false;
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
            const lines = (this.receiptData.items || []).map(it => `• ${it.name}: ${it.weight} ${it.unit} × Rp ${Number(it.price).toLocaleString('id-ID')} = *Rp ${Number(it.subtotal).toLocaleString('id-ID')}*`).join('\n');
            msg = `*STRUK DIGITAL SETORAN BANK SAMPAH*\n------------------------------------\n*Nasabah:* ${this.receiptData.residentName}\n*Alamat:* ${this.receiptData.householdCode}\n*Tanggal:* ${this.receiptData.date}\n*Petugas:* ${this.receiptData.officerName}\n\n*Rincian Setoran:*\n${lines}\n------------------------------------\n*Total Setoran Masuk:* *Rp ${Number(this.receiptData.totalAmount).toLocaleString('id-ID')}*\n*Saldo Setelah Transaksi:* *Rp ${this.receiptData.newBalance == null ? 'Tidak tersedia' : Number(this.receiptData.newBalance).toLocaleString('id-ID')}*\n\n*Terima kasih atas kontribusi aktif Anda menjaga kebersihan lingkungan!*`;
        } else {
            msg = `*BUKTI PENCAIRAN SALDO TUNAI*\n------------------------------------\n*Nasabah:* ${this.receiptData.residentName}\n*Alamat:* ${this.receiptData.householdCode}\n*Tanggal:* ${this.receiptData.date}\n*Petugas:* ${this.receiptData.officerName}\n------------------------------------\n*Nominal Dicairkan:* *-Rp ${Number(this.receiptData.totalAmount).toLocaleString('id-ID')}*\n*Saldo Setelah Transaksi:* *Rp ${this.receiptData.newBalance == null ? 'Tidak tersedia' : Number(this.receiptData.newBalance).toLocaleString('id-ID')}*\n*Catatan:* ${this.receiptData.notes || 'Pencairan tunai di Pos RT'}\n\n*Pencairan dana tabungan bank sampah telah berhasil diproses.*`;
        }
        return `https://api.whatsapp.com/send?phone=${phone}&text=${encodeURIComponent(msg)}`;
    }
}">

<div style="display: flex; flex-direction: column; gap: 26px;">
    <!-- 1. Hero Header & Overview Summary -->
    <div class="hero-row" style="display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; flex-wrap: wrap;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px; flex-wrap: wrap;">
                <span class="admin-heading-badge" style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; background: #ecfdf5; border: 1px solid #a7f3d0; color: #166534; font-size: 12px; font-weight: 700; border-radius: 8px;">
                    <span style="width: 7px; height: 7px; background: #22c55e; border-radius: 50%; box-shadow: 0 0 0 3px #bbf7d0;"></span>
                    Unit RT {{ auth()->user()->rt ?? '05' }} / RW {{ auth()->user()->rw ?? '08' }} · Aktif
                </span>
                <span style="font-size: 12px; color: #64748b; font-weight: 600;">
                    {{ $officersCount }} Petugas Lapangan Siap Operasional
                </span>
            </div>
            <h1 style="margin: 0; font-size: 26px; font-weight: 800; color: #173f35; letter-spacing: -0.03em;">Ringkasan Eksekutif Bank Sampah</h1>
            <p style="margin: 6px 0 0; color: #64748b; font-size: 14px; max-width: 720px; line-height: 1.5;">
                Pusat kendali data operasional, perputaran kas tabungan warga, akumulasi komoditas terpilah, dan kepatuhan sistem audit.
            </p>
        </div>
        <div class="hero-actions" style="display: flex; gap: 10px; flex-shrink: 0;">
            <a href="{{ route('admin.weighing.index') }}" class="primary-button" style="display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(23,63,53,0.18);">
                <x-icon name="scale" size="16" />
                <span>+ Catat Timbangan</span>
            </a>
            <a href="{{ route('admin.reports.index') }}" class="secondary-button" style="display: inline-flex; align-items: center; gap: 8px;">
                <x-icon name="file-text" size="16" />
                <span>Laporan &amp; Kas</span>
            </a>
        </div>
    </div>

    <!-- 2. Empat Kartu Metrik Utama -->
    <section class="metrics-grid">
        <article class="metric-card">
            <div class="metric-topline">
                <span class="metric-icon emerald"><x-icon name="credit-card" size="18" /></span>
                <span class="tiny-chart" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></span>
            </div>
            <span class="metric-label">Saldo Warga Terkelola</span>
            <div class="metric-value-row">
                <strong>Rp {{ number_format($totalSystemBalance, 0, ',', '.') }}</strong>
            </div>
            <div class="metric-foot">
                <span>Liabilitas tabungan kas warga</span>
            </div>
        </article>

        <article class="metric-card">
            <div class="metric-topline">
                <span class="metric-icon blue"><x-icon name="users" size="18" /></span>
                <span class="tiny-chart" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></span>
            </div>
            <span class="metric-label">Nasabah Terdaftar</span>
            <div class="metric-value-row">
                <strong>{{ number_format($totalResidents, 0, ',', '.') }}</strong>
                <span>KK</span>
            </div>
            <div class="metric-foot">
                <span>{{ $activatedPinsCount }} PIN aktif · {{ $unactivatedPinsCount }} butuh aktivasi</span>
            </div>
        </article>

        <article class="metric-card">
            <div class="metric-topline">
                <span class="metric-icon lime"><x-icon name="package" size="18" /></span>
                <span class="tiny-chart" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></span>
            </div>
            <span class="metric-label">Total Sampah Terpilah</span>
            <div class="metric-value-row">
                <strong>{{ number_format($totalWasteKg, 1, ',', '.') }}</strong>
                <span>kg</span>
            </div>
            <div class="metric-foot">
                <span>Berhasil dialihkan dari TPA</span>
            </div>
        </article>

        <article class="metric-card">
            <div class="metric-topline">
                <span class="metric-icon violet"><x-icon name="leaf" size="18" /></span>
                <span class="tiny-chart" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></span>
            </div>
            <span class="metric-label">Pencegahan Emisi Karbon</span>
            <div class="metric-value-row">
                <strong>~{{ number_format($co2ReductionKg, 1, ',', '.') }}</strong>
                <span>kg CO₂e</span>
            </div>
            <div class="metric-foot">
                <span>Faktor konversi 2.9 kg CO₂/kg</span>
            </div>
        </article>
    </section>

    <!-- 3. Pintasan Modul Utama Aplikasi (Module Directory Cards) -->
    <div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
            <h2 style="margin: 0; font-size: 16px; font-weight: 800; color: #173f35; letter-spacing: -0.02em;">Pusat Layanan &amp; Modul Pengurus</h2>
            <small style="color: #64748b; font-size: 13px;">Akses langsung menu operasional RT/RW</small>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 14px;">
            <!-- Modul 1: Timbang -->
            <a href="{{ route('admin.weighing.index') }}" style="text-decoration: none; background: #ffffff; border: 1.5px solid #e3e9e3; border-radius: 14px; padding: 16px; display: flex; flex-direction: column; gap: 8px; transition: all 0.2s ease; box-shadow: 0 4px 14px rgba(23,63,53,0.03);" onmouseover="this.style.borderColor='#86efac'; this.style.transform='translateY(-2px)'" onmouseout="this.style.borderColor='#e3e9e3'; this.style.transform='translateY(0)'">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="width: 38px; height: 38px; border-radius: 10px; background: #ecfdf5; color: #15803d; display: flex; align-items: center; justify-content: center;">
                        <x-icon name="scale" size="18" />
                    </span>
                    <span style="font-size: 11px; font-weight: 800; color: #15803d; background: #dcfce7; padding: 2px 7px; border-radius: 6px;">Operasional</span>
                </div>
                <div>
                    <strong style="color: #173f35; font-size: 14px; display: block;">Pencatatan Timbangan</strong>
                    <small style="color: #64748b; font-size: 12px; line-height: 1.4; display: block; margin-top: 2px;">Timbang sampah, hitung subtotal otomatis, cetak struk WA.</small>
                </div>
            </a>

            <!-- Modul 2: Warga -->
            <a href="{{ route('admin.residents.index') }}" style="text-decoration: none; background: #ffffff; border: 1.5px solid #e3e9e3; border-radius: 14px; padding: 16px; display: flex; flex-direction: column; gap: 8px; transition: all 0.2s ease; box-shadow: 0 4px 14px rgba(23,63,53,0.03);" onmouseover="this.style.borderColor='#93c5fd'; this.style.transform='translateY(-2px)'" onmouseout="this.style.borderColor='#e3e9e3'; this.style.transform='translateY(0)'">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="width: 38px; height: 38px; border-radius: 10px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center;">
                        <x-icon name="users" size="18" />
                    </span>
                    <span style="font-size: 11px; font-weight: 800; color: #2563eb; background: #dbeafe; padding: 2px 7px; border-radius: 6px;">{{ $totalResidents }} Nasabah</span>
                </div>
                <div>
                    <strong style="color: #173f35; font-size: 14px; display: block;">Data Warga &amp; Saldo</strong>
                    <small style="color: #64748b; font-size: 12px; line-height: 1.4; display: block; margin-top: 2px;">Kelola identitas, riwayat mutasi, reset PIN, &amp; pencairan kas.</small>
                </div>
            </a>

            <!-- Modul 3: Kategori & Harga -->
            <a href="{{ route('admin.categories.index') }}" style="text-decoration: none; background: #ffffff; border: 1.5px solid #e3e9e3; border-radius: 14px; padding: 16px; display: flex; flex-direction: column; gap: 8px; transition: all 0.2s ease; box-shadow: 0 4px 14px rgba(23,63,53,0.03);" onmouseover="this.style.borderColor='#fcd34d'; this.style.transform='translateY(-2px)'" onmouseout="this.style.borderColor='#e3e9e3'; this.style.transform='translateY(0)'">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="width: 38px; height: 38px; border-radius: 10px; background: #fefce8; color: #ca8a04; display: flex; align-items: center; justify-content: center;">
                        <x-icon name="tag" size="18" />
                    </span>
                    <span style="font-size: 11px; font-weight: 800; color: #ca8a04; background: #fef9c3; padding: 2px 7px; border-radius: 6px;">{{ $commoditiesSummary->count() }} Komoditas</span>
                </div>
                <div>
                    <strong style="color: #173f35; font-size: 14px; display: block;">Katalog Harga Beli</strong>
                    <small style="color: #64748b; font-size: 12px; line-height: 1.4; display: block; margin-top: 2px;">Kelola harga beli per kg/liter yang berlaku saat penimbangan.</small>
                </div>
            </a>

            <!-- Modul 4: Laporan Kas -->
            <a href="{{ route('admin.reports.index') }}" style="text-decoration: none; background: #ffffff; border: 1.5px solid #e3e9e3; border-radius: 14px; padding: 16px; display: flex; flex-direction: column; gap: 8px; transition: all 0.2s ease; box-shadow: 0 4px 14px rgba(23,63,53,0.03);" onmouseover="this.style.borderColor='#6ee7b7'; this.style.transform='translateY(-2px)'" onmouseout="this.style.borderColor='#e3e9e3'; this.style.transform='translateY(0)'">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="width: 38px; height: 38px; border-radius: 10px; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center;">
                        <x-icon name="trending-up" size="18" />
                    </span>
                    <span style="font-size: 11px; font-weight: 800; color: #059669; background: #d1fae5; padding: 2px 7px; border-radius: 6px;">CSV &amp; Ledger</span>
                </div>
                <div>
                    <strong style="color: #173f35; font-size: 14px; display: block;">Laporan &amp; Rekap Kas</strong>
                    <small style="color: #64748b; font-size: 12px; line-height: 1.4; display: block; margin-top: 2px;">Unduh rekapitulasi volume dan buku besar mutasi untuk audit RT.</small>
                </div>
            </a>

            <!-- Modul 5: Log Audit -->
            <a href="{{ route('admin.audit-log.index') }}" style="text-decoration: none; background: #ffffff; border: 1.5px solid #e3e9e3; border-radius: 14px; padding: 16px; display: flex; flex-direction: column; gap: 8px; transition: all 0.2s ease; box-shadow: 0 4px 14px rgba(23,63,53,0.03);" onmouseover="this.style.borderColor='#c4b5fd'; this.style.transform='translateY(-2px)'" onmouseout="this.style.borderColor='#e3e9e3'; this.style.transform='translateY(0)'">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="width: 38px; height: 38px; border-radius: 10px; background: #f5f3ff; color: #7c3aed; display: flex; align-items: center; justify-content: center;">
                        <x-icon name="shield" size="18" />
                    </span>
                    <span style="font-size: 11px; font-weight: 800; color: #7c3aed; background: #ede9fe; padding: 2px 7px; border-radius: 6px;">Keamanan</span>
                </div>
                <div>
                    <strong style="color: #173f35; font-size: 14px; display: block;">Log Audit &amp; Kepatuhan</strong>
                    <small style="color: #64748b; font-size: 12px; line-height: 1.4; display: block; margin-top: 2px;">Rekam jejak setiap login, penimbangan, &amp; perubahan konfigurasi.</small>
                </div>
            </a>
        </div>
    </div>

    <!-- 4. Tengah: Grafik Tren & Ringkasan Arus Kas -->
    <div class="dashboard-analytics-grid" style="display: grid; grid-template-columns: minmax(0, 1.7fr) minmax(320px, 1.1fr); gap: 20px;">
        <!-- Kiri: Grafik 7 Hari & Rekapitulasi Komoditas -->
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <!-- Chart Panel -->
            <div class="chart-panel" style="background: #ffffff; border-radius: 17px; padding: 22px; border: 1px solid #e3e9e3; box-shadow: 0 4px 18px rgba(23,63,53,0.03);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; gap: 12px; flex-wrap: wrap;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                            <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #173f35;">Tren Timbangan 7 Hari Terakhir</h3>
                            @php
                                $sevenDayTotal = $chartDays->sum('weight');
                            @endphp
                            <span style="font-size: 11px; font-weight: 700; color: #15803d; background: #dcfce7; padding: 2px 8px; border-radius: 6px;">
                                {{ number_format($sevenDayTotal, 1, ',', '.') }} kg total
                            </span>
                        </div>
                        <small style="color: #64748b; font-size: 13px;">Akumulasi timbulan sampah daur ulang per hari</small>
                    </div>
                    <a href="{{ route('admin.weighing.index') }}" class="btn-outline-small">
                        <x-icon name="plus" size="14" />
                        <span>Timbang Sekarang</span>
                    </a>
                </div>

                @php
                    $maxWeight = max($chartDays->max('weight'), 1);
                @endphp
                <div style="position: relative; min-height: 180px; display: flex; align-items: flex-end; padding-top: 20px; border-bottom: 1px solid #edf1ed;">
                    <!-- Garis Panduan -->
                    <div style="position: absolute; inset: 0 0 35px 0; display: flex; flex-direction: column; justify-content: space-between; pointer-events: none; opacity: 0.35;">
                        <div style="border-top: 1px dashed #cbd5e1; width: 100%;"></div>
                        <div style="border-top: 1px dashed #cbd5e1; width: 100%;"></div>
                        <div style="border-top: 1px dashed #cbd5e1; width: 100%;"></div>
                    </div>

                    <!-- Batang Bar -->
                    <div style="position: relative; z-index: 2; width: 100%; display: flex; align-items: flex-end; gap: 12px; height: 100%;">
                        @foreach($chartDays as $d)
                        @php
                            $weight = (float) $d['weight'];
                            $heightPct = min(100, round(($weight / $maxWeight) * 100));
                            $isToday = $loop->last;
                        @endphp
                        <div style="flex: 1; display: flex; flex-direction: column; align-items: center; height: 100%; justify-content: flex-end;">
                            @if($weight > 0)
                                <span style="font-size: 11px; font-weight: 800; color: #15803d; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 2px 6px; border-radius: 6px; margin-bottom: 6px; white-space: nowrap; box-shadow: 0 2px 6px rgba(16,185,129,0.15);">
                                    {{ $weight }} kg
                                </span>
                            @else
                                <span style="font-size: 11px; font-weight: 500; color: #94a3b8; margin-bottom: 6px;">-</span>
                            @endif

                            @if($weight > 0)
                                <div style="width: 100%; max-width: 36px; height: {{ max(10, $heightPct) }}%; background: linear-gradient(180deg, #34d399 0%, #059669 100%); border-radius: 8px 8px 3px 3px; box-shadow: 0 4px 12px rgba(5,150,105,0.22); transition: all 0.25s ease;"></div>
                            @else
                                <div style="width: 100%; max-width: 36px; height: 6px; background: #e2e8f0; border-radius: 4px;"></div>
                            @endif

                            <div style="margin-top: 10px; text-align: center; line-height: 1.2;">
                                <strong style="display: block; font-size: 12px; color: {{ $isToday ? '#15803d' : '#334155' }}; font-weight: {{ $isToday ? '800' : '600' }};">
                                    {{ substr($d['label'], 0, 3) }}
                                </strong>
                                <small style="font-size: 10px; color: {{ $isToday ? '#16a34a' : '#94a3b8' }};">
                                    {{ $isToday ? 'Hari Ini' : substr($d['label'], 5) }}
                                </small>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Ringkasan Katalog Komoditas Berjalan -->
            <div style="background: #ffffff; border-radius: 17px; padding: 22px; border: 1px solid #e3e9e3; box-shadow: 0 4px 18px rgba(23,63,53,0.03);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <div>
                        <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #173f35;">Distribusi Komoditas Sampah</h3>
                        <small style="color: #64748b; font-size: 13px;">Volume terpilah per jenis &amp; nilai ekonomi</small>
                    </div>
                    <a href="{{ route('admin.categories.index') }}" style="font-size: 13px; color: #15803d; text-decoration: none; font-weight: 700;">Kelola Katalog →</a>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px;">
                    @foreach($commoditiesSummary as $com)
                    <div style="padding: 12px 14px; background: #fbfdfb; border: 1px solid #edf2ee; border-radius: 10px;">
                        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 4px;">
                            <strong style="color: #1e293b; font-size: 13px;">{{ $com['name'] }}</strong>
                            <span style="font-weight: 800; color: #15803d; font-size: 13px;">{{ $com['total_weight'] }} {{ $com['unit'] }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 11.5px; color: #64748b;">
                            <span>Harga Beli: <strong>Rp {{ number_format($com['price_per_unit'], 0, ',', '.') }}</strong>/{{ $com['unit'] }}</span>
                            <span style="color: #0f172a; font-weight: 700;">Rp {{ number_format($com['total_amount'], 0, ',', '.') }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Kanan: Arus Kas & Setoran Terbaru -->
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <!-- Arus Kas Tabungan Warga -->
            <div style="background: #ffffff; border-radius: 17px; padding: 22px; border: 1px solid #e3e9e3; box-shadow: 0 4px 18px rgba(23,63,53,0.03);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <div>
                        <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #173f35;">Arus Keuangan Kas Tabungan</h3>
                        <small style="color: #64748b; font-size: 13px;">Akumulasi kas masuk vs pencairan dana</small>
                    </div>
                    <a href="{{ route('admin.reports.index') }}" style="font-size: 13px; color: #15803d; text-decoration: none; font-weight: 700;">Buku Besar →</a>
                </div>

                <div style="display: grid; gap: 10px;">
                    <!-- Inflow: Setoran -->
                    <div style="padding: 12px 14px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <span style="font-size: 12px; color: #166534; font-weight: 600;">Total Setoran Masuk (Inflow)</span>
                            <div style="font-size: 18px; font-weight: 800; color: #14532d; margin-top: 2px;">
                                Rp {{ number_format($totalDepositAmount, 0, ',', '.') }}
                            </div>
                            <small style="font-size: 11px; color: #15803d;">{{ $totalDepositCount }} transaksi tercatat</small>
                        </div>
                        <span style="width: 36px; height: 36px; border-radius: 8px; background: #dcfce7; color: #166534; display: flex; align-items: center; justify-content: center;">
                            <x-icon name="arrow-down-left" size="18" />
                        </span>
                    </div>

                    <!-- Outflow: Pencairan Tunai -->
                    <div style="padding: 12px 14px; background: #fff5f5; border: 1px solid #fed7d7; border-radius: 10px; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <span style="font-size: 12px; color: #991b1b; font-weight: 600;">Total Pencairan Kas (Outflow)</span>
                            <div style="font-size: 18px; font-weight: 800; color: #7f1d1d; margin-top: 2px;">
                                Rp {{ number_format($totalWithdrawalAmount, 0, ',', '.') }}
                            </div>
                            <small style="font-size: 11px; color: #b91c1c;">{{ $totalWithdrawalCount }} pencairan tunai</small>
                        </div>
                        <span style="width: 36px; height: 36px; border-radius: 8px; background: #fee2e2; color: #991b1b; display: flex; align-items: center; justify-content: center;">
                            <x-icon name="arrow-up-right" size="18" />
                        </span>
                    </div>
                </div>
            </div>

            <!-- Setoran Terbaru -->
            <div style="background: #ffffff; border-radius: 17px; padding: 22px; border: 1px solid #e3e9e3; box-shadow: 0 4px 18px rgba(23,63,53,0.03); flex: 1; display: flex; flex-direction: column;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <div>
                        <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #173f35;">Setoran Terbaru</h3>
                        <small style="color: #64748b; font-size: 13px;">Aktivitas penimbangan terkini</small>
                    </div>
                    <a href="{{ route('admin.reports.index') }}" style="font-size: 13px; color: #15803d; text-decoration: none; font-weight: 700;">Lihat Semua →</a>
                </div>

                <div style="display: flex; flex-direction: column; gap: 10px; flex: 1;">
                    @forelse($recentTransactions as $tx)
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 12px; border-radius: 10px; background: #fbfdfb; border: 1px solid #edf2ee; transition: all 0.15s ease;">
                        <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                            <div style="width: 34px; height: 34px; border-radius: 8px; background: #e8f5ed; color: #15803d; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <x-icon name="scale" size="16" />
                            </div>
                            <div style="min-width: 0;">
                                <a href="javascript:void(0)" @click="viewReceipt('{{ $tx->id }}')" style="font-weight: 700; color: #173f35; text-decoration: none; font-size: 13.5px; display: inline-flex; align-items: center; gap: 5px;" title="Klik untuk lihat struk digital">
                                    <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $tx->resident?->full_name }}</span>
                                    <x-icon name="file-text" size="13" class="text-emerald-600" />
                                </a>
                                <div style="color: #64748b; font-size: 11.5px; margin-top: 1px;">
                                    {{ $tx->created_at->diffForHumans() }} · {{ $tx->officer?->full_name ?? 'Petugas RT' }}
                                </div>
                            </div>
                        </div>
                        <div style="text-align: right; flex-shrink: 0; padding-left: 10px;">
                            <span style="font-weight: 800; color: #15803d; font-size: 13px; background: #ecfdf5; border: 1px solid #bbf7d0; padding: 3px 8px; border-radius: 6px; display: inline-block;">
                                +Rp {{ number_format($tx->total_amount, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                    @empty
                    <div style="padding: 24px 16px; text-align: center; color: #94a3b8; display: flex; flex-direction: column; align-items: center; justify-content: center; flex: 1;">
                        <x-icon name="inbox" size="28" style="opacity: 0.4; margin-bottom: 6px;" />
                        <p style="margin: 0; font-size: 13px; font-weight: 600; color: #64748b;">Belum ada aktivitas transaksi</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- 5. Bawah: Leaderboard Warga & Log Audit Keamanan Terkini -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 20px;">
        <!-- Nasabah Saldo Teratas -->
        <div style="background: #ffffff; border-radius: 17px; padding: 22px; border: 1px solid #e3e9e3; box-shadow: 0 4px 18px rgba(23,63,53,0.03);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #173f35;">Nasabah Teraktif Lingkungan</h3>
                    <small style="color: #64748b; font-size: 13px;">Warga dengan saldo tabungan tertinggi</small>
                </div>
                <a href="{{ route('admin.residents.index') }}" style="font-size: 13px; color: #15803d; text-decoration: none; font-weight: 700;">Direktori Warga →</a>
            </div>

            <div style="display: flex; flex-direction: column; gap: 10px;">
                @forelse($topResidents as $idx => $resident)
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 12px; background: #fbfdfb; border: 1px solid #edf2ee; border-radius: 10px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 28px; height: 28px; border-radius: 50%; background: {{ $idx === 0 ? '#fef3c7' : '#f1f5f9' }}; color: {{ $idx === 0 ? '#b45309' : '#475569' }}; font-weight: 800; font-size: 12px; display: flex; align-items: center; justify-content: center;">
                            #{{ $idx + 1 }}
                        </div>
                        <div>
                            <strong style="color: #1e293b; font-size: 13.5px; display: block;">{{ $resident->full_name }}</strong>
                            <small style="color: #64748b; font-size: 11.5px;">{{ $resident->household_code }} · {{ $resident->phone_number }}</small>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <strong style="color: #15803d; font-size: 14px; font-weight: 800;">Rp {{ number_format($resident->balance, 0, ',', '.') }}</strong>
                        <div style="margin-top: 2px;">
                            @if($resident->pin_hash)
                                <span style="font-size: 10px; color: #166534; background: #dcfce7; padding: 1px 6px; border-radius: 4px; font-weight: 700;">PIN Aktif</span>
                            @else
                                <span style="font-size: 10px; color: #9a3412; background: #ffedd5; padding: 1px 6px; border-radius: 4px; font-weight: 700;">Belum Aktivasi</span>
                            @endif
                        </div>
                    </div>
                </div>
                @empty
                <p style="color: #94a3b8; font-size: 13px; text-align: center; padding: 16px;">Belum ada data warga terdaftar.</p>
                @endforelse
            </div>
        </div>

        <!-- Log Audit & Keamanan Sistem Terkini -->
        <div style="background: #ffffff; border-radius: 17px; padding: 22px; border: 1px solid #e3e9e3; box-shadow: 0 4px 18px rgba(23,63,53,0.03);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #173f35;">Aktivitas &amp; Log Audit Terkini</h3>
                    <small style="color: #64748b; font-size: 13px;">Pengawasan kepatuhan &amp; keamanan akun</small>
                </div>
                <a href="{{ route('admin.audit-log.index') }}" style="font-size: 13px; color: #15803d; text-decoration: none; font-weight: 700;">Semua Log →</a>
            </div>

            <div style="display: flex; flex-direction: column; gap: 10px;">
                @forelse($recentAuditLogs as $log)
                @php
                    $actionBadge = match($log->action) {
                        'STAFF_LOGIN' => ['bg' => '#e0f2fe', 'color' => '#0369a1', 'text' => 'Login Staf'],
                        'RECORD_DEPOSIT' => ['bg' => '#dcfce7', 'color' => '#15803d', 'text' => 'Setor Sampah'],
                        'RECORD_WITHDRAWAL' => ['bg' => '#fee2e2', 'color' => '#b91c1c', 'text' => 'Tarik Tunai'],
                        'CREATE_RESIDENT', 'CREATE_CATEGORY', 'CREATE_OFFICER' => ['bg' => '#ede9fe', 'color' => '#6d28d9', 'text' => 'Data Baru'],
                        default => ['bg' => '#f1f5f9', 'color' => '#475569', 'text' => $log->action]
                    };
                @endphp
                <div style="display: flex; justify-content: space-between; align-items: flex-start; padding: 10px 12px; background: #fbfdfb; border: 1px solid #edf2ee; border-radius: 10px; gap: 10px;">
                    <div style="min-width: 0;">
                        <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 2px;">
                            <span style="font-size: 10.5px; font-weight: 800; background: {{ $actionBadge['bg'] }}; color: {{ $actionBadge['color'] }}; padding: 1px 6px; border-radius: 4px;">
                                {{ $actionBadge['text'] }}
                            </span>
                            <strong style="color: #1e293b; font-size: 12.5px;">{{ $log->actor_name }}</strong>
                        </div>
                        <p style="margin: 0; color: #475569; font-size: 12px; line-height: 1.4; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            {{ $log->details }}
                        </p>
                    </div>
                    <small style="color: #94a3b8; font-size: 11px; white-space: nowrap; flex-shrink: 0; margin-top: 2px;">
                        {{ \Carbon\Carbon::parse($log->created_at)->diffForHumans() }}
                    </small>
                </div>
                @empty
                <p style="color: #94a3b8; font-size: 13px; text-align: center; padding: 16px;">Belum ada log aktivitas tercatat.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Struk Digital -->
<div class="overlay modal-overlay" x-show="showReceiptModal" style="display: none;" x-cloak>
    <div class="modal-card" style="max-width: 500px;" @click.away="showReceiptModal = false">
        <div class="modal-head">
            <h3>Struk Bukti Setoran</h3>
            <button type="button" class="modal-close" @click="showReceiptModal = false">&times;</button>
        </div>
        <div style="padding: 12px 0;">
            <div x-show="isLoadingReceipt" style="text-align: center; padding: 20px; color: #64748b;">
                Memuat data transaksi...
            </div>
            <div x-show="!isLoadingReceipt && receiptData">
                <div style="background: #f8faf8; border: 1px solid #e2e8f0; padding: 14px; border-radius: 10px; font-size: 0.88rem; line-height: 1.6;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="color: #64748b;">Tanggal:</span>
                        <strong x-text="receiptData?.date" style="color: #1e293b;"></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="color: #64748b;">Nasabah:</span>
                        <strong x-text="receiptData?.residentName" style="color: #173f35;"></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="color: #64748b;">Alamat:</span>
                        <span x-text="receiptData?.householdCode" style="color: #334155;"></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="color: #64748b;">Petugas:</span>
                        <span x-text="receiptData?.officerName" style="color: #334155;"></span>
                    </div>

                    <div x-show="receiptData?.items && receiptData?.items.length > 0" style="border-top: 1px dashed #cbd5e1; margin-top: 10px; padding-top: 10px;">
                        <template x-for="(it, i) in receiptData?.items" :key="i">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                <span x-text="`${it.name} (${it.weight} ${it.unit})`" style="color: #475569;"></span>
                                <strong x-text="'Rp ' + Number(it.subtotal).toLocaleString('id-ID')" style="color: #0f172a;"></strong>
                            </div>
                        </template>
                    </div>

                    <div style="border-top: 1.5px solid #cbd5e1; margin-top: 12px; padding-top: 10px; display: flex; justify-content: space-between; font-size: 1rem; color: #166534;">
                        <strong x-text="receiptData?.type === 'deposit' ? 'Total Setoran Masuk:' : 'Total Pencairan Tunai:'"></strong>
                        <strong x-text="'Rp ' + Number(receiptData?.totalAmount).toLocaleString('id-ID')"></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-top: 4px; font-size: 0.9rem; color: #15803d;">
                        <span>Saldo Setelah Transaksi:</span>
                        <strong x-text="receiptData?.newBalance == null ? 'Tidak tersedia untuk transaksi lama' : 'Rp ' + Number(receiptData.newBalance).toLocaleString('id-ID')"></strong>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-actions" style="margin-top: 16px; display: flex; gap: 10px;">
            <button type="button" class="secondary-button" @click="showReceiptModal = false" style="flex: 1;">Tutup</button>
            <a :href="waUrl" target="_blank" class="primary-button" style="flex: 2; justify-content: center; background: #25D366; border-color: #25D366; color: white;">
                <span><x-icon name="message-square" size="16" /></span> Kirim Ulang WhatsApp
            </a>
        </div>
    </div>
</div>
</div>
@endsection
