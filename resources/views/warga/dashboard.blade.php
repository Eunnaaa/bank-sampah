@extends('layouts.warga', ['title' => 'Dashboard Nasabah — Si-BankSampah'])

@section('warga_content')
<div x-data="{
    showChangePin: false,
    showReceiptModal: false,
    receiptData: null,
    isLoadingReceipt: false,

    async viewReceipt(txId) {
        this.isLoadingReceipt = true;
        this.showReceiptModal = true;
        try {
            const resp = await fetch(`/warga/transaksi/${txId}`);
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
    <!-- 1. Hero Header & Overview Summary (Mengacu pada Executive Header Admin) -->
    <div class="hero-row" style="display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; flex-wrap: wrap;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px; flex-wrap: wrap;">
                <span class="admin-heading-badge" style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; background: #ecfdf5; border: 1px solid #a7f3d0; color: #166534; font-size: 12px; font-weight: 700; border-radius: 8px;">
                    <span style="width: 7px; height: 7px; background: #22c55e; border-radius: 50%; box-shadow: 0 0 0 3px #bbf7d0;"></span>
                    Unit RT {{ $resident->rt ?? '05' }} / RW {{ $resident->rw ?? '08' }} · Nasabah Aktif
                </span>
                <span style="font-size: 12px; color: #64748b; font-weight: 600;">
                    Peringkat #{{ $residentRank }} Teraktif dari {{ $totalResidents }} KK Terdaftar
                </span>
            </div>
            <h1 style="margin: 0; font-size: 26px; font-weight: 800; color: #173f35; letter-spacing: -0.03em;">Halo, {{ $resident->full_name }}!</h1>
            <p style="margin: 6px 0 0; color: #64748b; font-size: 14px; max-width: 720px; line-height: 1.5;">
                Buku rekening tabungan digital bank sampah. Pantau saldo rupiah, akumulasi pemilahan komoditas daur ulang di {{ $resident->house_number }}, serta kontribusi kelestarian lingkungan keluarga Anda.
            </p>
        </div>
        <div class="hero-actions" style="display: flex; gap: 10px; flex-shrink: 0; flex-wrap: wrap;">
            <a href="{{ route('warga.katalog') }}" class="primary-button" style="display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(23,63,53,0.18);">
                <x-icon name="tag" size="16" />
                <span>Katalog Harga Beli</span>
            </a>
            <a href="{{ route('warga.profil') }}" class="secondary-button" style="display: inline-flex; align-items: center; gap: 8px;">
                <x-icon name="credit-card" size="16" />
                <span>Kartu QR Nasabah</span>
            </a>
            <button type="button" @click="showChangePin = true" class="secondary-button" style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer;">
                <x-icon name="key" size="15" />
                <span>Ganti PIN</span>
            </button>
        </div>
    </div>

    <!-- 2. Empat Kartu Metrik Utama (Identik dengan Dashboard Admin Metrics Grid) -->
    <section class="metrics-grid">
        <!-- Metrik 1: Saldo Tersedia -->
        <article class="metric-card">
            <div class="metric-topline">
                <span class="metric-icon emerald"><x-icon name="credit-card" size="18" /></span>
                <span class="tiny-chart" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></span>
            </div>
            <span class="metric-label">Saldo Tabungan Tersedia</span>
            <div class="metric-value-row">
                <strong>Rp {{ number_format($resident->balance, 0, ',', '.') }}</strong>
            </div>
            <div class="metric-foot">
                <span>Siap dicairkan tunai di pos RT</span>
            </div>
        </article>

        <!-- Metrik 2: Bobot Sampah Disetor -->
        <article class="metric-card">
            <div class="metric-topline">
                <span class="metric-icon lime"><x-icon name="package" size="18" /></span>
                <span class="tiny-chart" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></span>
            </div>
            <span class="metric-label">Total Sampah Disetor</span>
            <div class="metric-value-row">
                <strong>{{ number_format($resident->total_waste_weight_kg, 1, ',', '.') }}</strong>
                <span>kg</span>
            </div>
            <div class="metric-foot">
                <span>{{ $totalDepositCount }} kali penimbangan tercatat</span>
            </div>
        </article>

        <!-- Metrik 3: Pengurangan Emisi Karbon -->
        <article class="metric-card">
            <div class="metric-topline">
                <span class="metric-icon violet"><x-icon name="leaf" size="18" /></span>
                <span class="tiny-chart" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></span>
            </div>
            <span class="metric-label">Pencegahan Emisi Karbon</span>
            <div class="metric-value-row">
                <strong>~{{ number_format($resident->total_co2_saved_kg, 1, ',', '.') }}</strong>
                <span>kg CO₂e</span>
            </div>
            <div class="metric-foot">
                <span>Faktor reduksi 2.9 kg CO₂/kg</span>
            </div>
        </article>

        <!-- Metrik 4: Apresiasi Peringkat RT -->
        <article class="metric-card">
            <div class="metric-topline">
                <span class="metric-icon blue"><x-icon name="trophy" size="18" /></span>
                <span class="tiny-chart" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></span>
            </div>
            <span class="metric-label">Apresiasi Keaktifan RT</span>
            <div class="metric-value-row">
                <strong>#{{ $residentRank }}</strong>
                <span>Teraktif</span>
            </div>
            <div class="metric-foot">
                <span>Dari {{ $totalResidents }} KK di RT {{ $resident->rt ?? '05' }}/RW {{ $resident->rw ?? '08' }}</span>
            </div>
        </article>
    </section>

    <!-- 3. Pintasan Modul Layanan Warga (Module Directory Cards) -->
    <div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
            <h2 style="margin: 0; font-size: 16px; font-weight: 800; color: #173f35; letter-spacing: -0.02em;">Pusat Layanan &amp; Navigasi Warga</h2>
            <small style="color: #64748b; font-size: 13px;">Akses cepat fitur portal nasabah mandiri</small>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 14px;">
            <!-- Modul 1: Riwayat Mutasi -->
            <a href="{{ route('warga.riwayat') }}" style="text-decoration: none; background: #ffffff; border: 1.5px solid #e3e9e3; border-radius: 14px; padding: 16px; display: flex; flex-direction: column; gap: 8px; transition: all 0.2s ease; box-shadow: 0 4px 14px rgba(23,63,53,0.03);" onmouseover="this.style.borderColor='#93c5fd'; this.style.transform='translateY(-2px)'" onmouseout="this.style.borderColor='#e3e9e3'; this.style.transform='translateY(0)'">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="width: 38px; height: 38px; border-radius: 10px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center;">
                        <x-icon name="file-text" size="18" />
                    </span>
                    <span style="font-size: 11px; font-weight: 800; color: #2563eb; background: #dbeafe; padding: 2px 7px; border-radius: 6px;">{{ $resident->transactions()->count() }} Transaksi</span>
                </div>
                <div>
                    <strong style="color: #173f35; font-size: 14px; display: block;">Buku Mutasi Kas</strong>
                    <small style="color: #64748b; font-size: 12px; line-height: 1.4; display: block; margin-top: 2px;">Lihat seluruh catatan setoran sampah dan penarikan saldo tunai.</small>
                </div>
            </a>

            <!-- Modul 2: Katalog Harga -->
            <a href="{{ route('warga.katalog') }}" style="text-decoration: none; background: #ffffff; border: 1.5px solid #e3e9e3; border-radius: 14px; padding: 16px; display: flex; flex-direction: column; gap: 8px; transition: all 0.2s ease; box-shadow: 0 4px 14px rgba(23,63,53,0.03);" onmouseover="this.style.borderColor='#fcd34d'; this.style.transform='translateY(-2px)'" onmouseout="this.style.borderColor='#e3e9e3'; this.style.transform='translateY(0)'">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="width: 38px; height: 38px; border-radius: 10px; background: #fefce8; color: #ca8a04; display: flex; align-items: center; justify-content: center;">
                        <x-icon name="tag" size="18" />
                    </span>
                    <span style="font-size: 11px; font-weight: 800; color: #ca8a04; background: #fef9c3; padding: 2px 7px; border-radius: 6px;">{{ $activeCategories->count() }} Komoditas</span>
                </div>
                <div>
                    <strong style="color: #173f35; font-size: 14px; display: block;">Katalog Harga Beli</strong>
                    <small style="color: #64748b; font-size: 12px; line-height: 1.4; display: block; margin-top: 2px;">Cek patokan harga beli sampah per kg/liter yang berlaku hari ini.</small>
                </div>
            </a>

            <!-- Modul 3: Kartu QR & Profil -->
            <a href="{{ route('warga.profil') }}" style="text-decoration: none; background: #ffffff; border: 1.5px solid #e3e9e3; border-radius: 14px; padding: 16px; display: flex; flex-direction: column; gap: 8px; transition: all 0.2s ease; box-shadow: 0 4px 14px rgba(23,63,53,0.03);" onmouseover="this.style.borderColor='#86efac'; this.style.transform='translateY(-2px)'" onmouseout="this.style.borderColor='#e3e9e3'; this.style.transform='translateY(0)'">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="width: 38px; height: 38px; border-radius: 10px; background: #ecfdf5; color: #15803d; display: flex; align-items: center; justify-content: center;">
                        <x-icon name="credit-card" size="18" />
                    </span>
                    <span style="font-size: 11px; font-weight: 800; color: #15803d; background: #dcfce7; padding: 2px 7px; border-radius: 6px;">Identitas</span>
                </div>
                <div>
                    <strong style="color: #173f35; font-size: 14px; display: block;">Kartu QR Nasabah</strong>
                    <small style="color: #64748b; font-size: 12px; line-height: 1.4; display: block; margin-top: 2px;">Tunjukkan kartu barcode saat menimbang sampah di pos RT.</small>
                </div>
            </a>

            <!-- Modul 4: Keamanan Akun & PIN -->
            <div @click="showChangePin = true" style="cursor: pointer; background: #ffffff; border: 1.5px solid #e3e9e3; border-radius: 14px; padding: 16px; display: flex; flex-direction: column; gap: 8px; transition: all 0.2s ease; box-shadow: 0 4px 14px rgba(23,63,53,0.03);" onmouseover="this.style.borderColor='#c4b5fd'; this.style.transform='translateY(-2px)'" onmouseout="this.style.borderColor='#e3e9e3'; this.style.transform='translateY(0)'">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="width: 38px; height: 38px; border-radius: 10px; background: #f5f3ff; color: #7c3aed; display: flex; align-items: center; justify-content: center;">
                        <x-icon name="key" size="18" />
                    </span>
                    <span style="font-size: 11px; font-weight: 800; color: #7c3aed; background: #ede9fe; padding: 2px 7px; border-radius: 6px;">Keamanan</span>
                </div>
                <div>
                    <strong style="color: #173f35; font-size: 14px; display: block;">Ganti PIN Mandiri</strong>
                    <small style="color: #64748b; font-size: 12px; line-height: 1.4; display: block; margin-top: 2px;">Perbarui 4 digit PIN rahasia untuk perlindungan saldo rekening.</small>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Tengah: Analitik Dua Kolom (Tren, Komposisi, Kartu Nasabah, & Arus Kas) -->
    <div class="dashboard-analytics-grid" style="display: grid; grid-template-columns: minmax(0, 1.7fr) minmax(320px, 1.1fr); gap: 20px;">
        <!-- Kolom Kiri: Tren 7 Hari, Komposisi Sampah, & Katalog Harga Cepat -->
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <!-- Grafik Tren Penimbangan Nasabah -->
            <div class="chart-panel" style="background: #ffffff; border-radius: 17px; padding: 22px; border: 1px solid #e3e9e3; box-shadow: 0 4px 18px rgba(23,63,53,0.03);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; gap: 12px; flex-wrap: wrap;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                            <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #173f35;">Tren Kontribusi Penimbangan</h3>
                            @php
                                $weekTotal = $chartDays->sum('weight');
                            @endphp
                            <span style="font-size: 11px; font-weight: 700; color: #15803d; background: #dcfce7; padding: 2px 8px; border-radius: 6px;">
                                {{ number_format($weekTotal, 1, ',', '.') }} kg disetor
                            </span>
                        </div>
                        <small style="color: #64748b; font-size: 13px;">Volume sampah terpilah yang disetorkan keluarga Anda 7 hari terakhir</small>
                    </div>
                    <a href="{{ route('warga.katalog') }}" class="btn-outline-small">
                        <x-icon name="tag" size="14" />
                        <span>Katalog Harga</span>
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

                    <!-- Batang Bar Grafik -->
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

            <!-- Komposisi Sampah Terpilah & Nilai Tabungan -->
            <div style="background: #ffffff; border-radius: 17px; padding: 22px; border: 1px solid #e3e9e3; box-shadow: 0 4px 18px rgba(23,63,53,0.03);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <div>
                        <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #173f35;">Komposisi Sampah Terpilah</h3>
                        <small style="color: #64748b; font-size: 13px;">Rincian jenis komoditas sampah dan akumulasi nilai rupiah keluarga</small>
                    </div>
                    <a href="{{ route('warga.katalog') }}" style="font-size: 13px; color: #15803d; text-decoration: none; font-weight: 700;">Lihat Katalog Lengkap →</a>
                </div>

                <div style="display: flex; flex-direction: column; gap: 14px;">
                    @php
                        $totalWeightAll = max($resident->total_waste_weight_kg, 0.01);
                    @endphp
                    @forelse($categoryBreakdown as $cat)
                    @php
                        $pct = min(100, round(($cat['weight'] / $totalWeightAll) * 100));
                    @endphp
                    <div style="padding: 12px 14px; background: #fbfdfb; border: 1px solid #edf2ee; border-radius: 10px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <strong style="color: #1e293b; font-size: 13.5px;">{{ $cat['name'] }}</strong>
                            <span style="font-weight: 800; color: #15803d; font-size: 13px;">
                                {{ number_format($cat['weight'], 1, ',', '.') }} {{ $cat['unit'] }} ({{ $pct }}%)
                            </span>
                        </div>
                        <div style="height: 7px; background: #e2e8f0; border-radius: 6px; overflow: hidden; margin-bottom: 6px;">
                            <div style="width: {{ max(5, $pct) }}%; height: 100%; background: linear-gradient(90deg, #10b981 0%, #059669 100%); border-radius: 6px;"></div>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 11.5px; color: #64748b;">
                            <span>Akumulasi Tabungan</span>
                            <strong style="color: #15803d; font-weight: 700;">Rp {{ number_format($cat['subtotal'], 0, ',', '.') }}</strong>
                        </div>
                    </div>
                    @empty
                    <div style="padding: 24px 16px; text-align: center; color: #94a3b8;">
                        <x-icon name="package" size="28" style="opacity: 0.4; margin-bottom: 6px;" />
                        <p style="margin: 0; font-size: 13px; font-weight: 600; color: #64748b;">Belum ada komoditas sampah tercatat.</p>
                        <small style="color: #94a3b8;">Bawa sampah terpilah Anda saat jadwal penimbangan di pos RT.</small>
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Katalog Harga Beli Terkini (Quick Rate Card) -->
            <div style="background: #ffffff; border-radius: 17px; padding: 22px; border: 1px solid #e3e9e3; box-shadow: 0 4px 18px rgba(23,63,53,0.03);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <div>
                        <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #173f35;">Harga Beli Komoditas Hari Ini</h3>
                        <small style="color: #64748b; font-size: 13px;">Patokan harga beli resmi dari Bank Sampah RT</small>
                    </div>
                    <a href="{{ route('warga.katalog') }}" style="font-size: 13px; color: #15803d; text-decoration: none; font-weight: 700;">Kalkulator Tabungan →</a>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                    @foreach($activeCategories as $cat)
                    <div style="padding: 10px 12px; background: #fbfdfb; border: 1px solid #edf2ee; border-radius: 10px; display: flex; justify-content: space-between; align-items: center;">
                        <div style="min-width: 0;">
                            <strong style="color: #1e293b; font-size: 13px; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $cat->name }}</strong>
                            <small style="color: #64748b; font-size: 11px;">Per {{ $cat->unit }}</small>
                        </div>
                        <div style="text-align: right; flex-shrink: 0; padding-left: 8px;">
                            <span style="font-weight: 800; color: #15803d; font-size: 13px; background: #ecfdf5; border: 1px solid #bbf7d0; padding: 2px 6px; border-radius: 6px; display: inline-block;">
                                Rp {{ number_format($cat->price_per_unit, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Kartu Digital Nasabah, Arus Kas, & Mutasi Terkini -->
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <!-- Kartu Nasabah Digital Resmi (KARTU DIGITAL NASABAH) -->
            <div style="background: linear-gradient(135deg, #10382d 0%, #154536 60%, #1e5946 100%); border-radius: 18px; padding: 22px; color: #ffffff; box-shadow: 0 10px 30px rgba(16,56,45,0.22); position: relative; overflow: hidden; border: 1px solid rgba(255,255,255,0.12);">
                <div style="position: absolute; right: -25px; bottom: -25px; opacity: 0.08; pointer-events: none;">
                    <x-icon name="recycle" size="180" />
                </div>

                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 18px; position: relative; z-index: 2;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="width: 36px; height: 36px; border-radius: 10px; background: rgba(201, 236, 114, 0.18); border: 1px solid rgba(201, 236, 114, 0.35); color: #c9ec72; display: flex; align-items: center; justify-content: center;">
                            <x-icon name="recycle" size="20" />
                        </span>
                        <div>
                            <strong style="font-size: 13px; letter-spacing: 0.08em; display: block; color: #ffffff;">KARTU DIGITAL NASABAH</strong>
                            <small style="font-size: 11px; color: #a7f3d0;">Bank Sampah RT {{ $resident->rt ?? '05' }} / RW {{ $resident->rw ?? '08' }}</small>
                        </div>
                    </div>
                    <span style="background: rgba(201, 236, 114, 0.2); border: 1px solid rgba(201, 236, 114, 0.35); color: #c9ec72; font-size: 10.5px; font-weight: 800; padding: 3px 8px; border-radius: 20px; letter-spacing: 0.04em;">
                        PAS-BARCODE
                    </span>
                </div>

                <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 18px; position: relative; z-index: 2;">
                    <div style="background: #ffffff; padding: 7px; border-radius: 12px; display: inline-flex; box-shadow: 0 4px 12px rgba(0,0,0,0.15); flex-shrink: 0;">
                        <div style="width: 90px; height: 90px;">
                            {!! $qrCodeSvg !!}
                        </div>
                    </div>
                    <div style="min-width: 0; line-height: 1.4;">
                        <span style="font-size: 11px; color: #93c5fd; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; display: block;">Pemilik Rekening</span>
                        <strong style="font-size: 15px; color: #ffffff; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $resident->full_name }}</strong>
                        <div style="margin-top: 4px; font-size: 12px; color: #d1fae5;">
                            {{ $resident->house_number }}<br>
                            WhatsApp: {{ $resident->phone_number }}
                        </div>
                    </div>
                </div>

                <div style="border-top: 1px solid rgba(255,255,255,0.12); padding-top: 14px; display: flex; justify-content: space-between; align-items: flex-end; position: relative; z-index: 2;">
                    <div>
                        <span style="font-size: 11px; color: #a7f3d0; display: block;">Saldo Tabungan Aktif</span>
                        <strong style="font-size: 20px; font-weight: 800; color: #ffffff;">Rp {{ number_format($resident->balance, 0, ',', '.') }}</strong>
                    </div>
                    <a href="{{ route('warga.profil') }}" style="background: rgba(255,255,255,0.14); border: 1px solid rgba(255,255,255,0.25); color: #ffffff; text-decoration: none; font-size: 12px; font-weight: 700; padding: 7px 12px; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s ease;">
                        <span>Lihat Kartu Lengkap</span>
                        <x-icon name="arrow-right" size="14" />
                    </a>
                </div>
            </div>

            <!-- Arus Tabungan Kas Keluarga (Inflow vs Outflow) -->
            <div style="background: #ffffff; border-radius: 17px; padding: 22px; border: 1px solid #e3e9e3; box-shadow: 0 4px 18px rgba(23,63,53,0.03);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <div>
                        <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #173f35;">Arus Tabungan Kas Keluarga</h3>
                        <small style="color: #64748b; font-size: 13px;">Akumulasi kas masuk vs pencairan dana</small>
                    </div>
                    <a href="{{ route('warga.riwayat') }}" style="font-size: 13px; color: #15803d; text-decoration: none; font-weight: 700;">Semua Mutasi →</a>
                </div>

                <div style="display: grid; gap: 10px;">
                    <!-- Inflow: Setoran Masuk -->
                    <div style="padding: 12px 14px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <span style="font-size: 12px; color: #166534; font-weight: 600;">Total Tabungan Masuk (Kredit)</span>
                            <div style="font-size: 18px; font-weight: 800; color: #14532d; margin-top: 2px;">
                                +Rp {{ number_format($totalDepositAmount, 0, ',', '.') }}
                            </div>
                            <small style="font-size: 11px; color: #15803d;">{{ $totalDepositCount }} setoran sampah tercatat</small>
                        </div>
                        <span style="width: 36px; height: 36px; border-radius: 8px; background: #dcfce7; color: #166534; display: flex; align-items: center; justify-content: center;">
                            <x-icon name="arrow-down-left" size="18" />
                        </span>
                    </div>

                    <!-- Outflow: Pencairan Tunai -->
                    <div style="padding: 12px 14px; background: #fff5f5; border: 1px solid #fed7d7; border-radius: 10px; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <span style="font-size: 12px; color: #991b1b; font-weight: 600;">Total Saldo Dicairkan (Debit)</span>
                            <div style="font-size: 18px; font-weight: 800; color: #7f1d1d; margin-top: 2px;">
                                -Rp {{ number_format($totalWithdrawalAmount, 0, ',', '.') }}
                            </div>
                            <small style="font-size: 11px; color: #b91c1c;">{{ $totalWithdrawalCount }} pencairan tunai di pos RT</small>
                        </div>
                        <span style="width: 36px; height: 36px; border-radius: 8px; background: #fee2e2; color: #991b1b; display: flex; align-items: center; justify-content: center;">
                            <x-icon name="arrow-up-right" size="18" />
                        </span>
                    </div>
                </div>
            </div>

            <!-- Riwayat Mutasi Terkini & Struk Digital -->
            <div style="background: #ffffff; border-radius: 17px; padding: 22px; border: 1px solid #e3e9e3; box-shadow: 0 4px 18px rgba(23,63,53,0.03); flex: 1; display: flex; flex-direction: column;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <div>
                        <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #173f35;">Riwayat Mutasi Terkini</h3>
                        <small style="color: #64748b; font-size: 13px;">Klik transaksi untuk melihat &amp; membagikan struk digital</small>
                    </div>
                    <a href="{{ route('warga.riwayat') }}" style="font-size: 13px; color: #15803d; text-decoration: none; font-weight: 700;">Lihat Semua →</a>
                </div>

                <div style="display: flex; flex-direction: column; gap: 10px; flex: 1;">
                    @forelse($recentTransactions as $tx)
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 12px; border-radius: 10px; background: #fbfdfb; border: 1px solid #edf2ee; transition: all 0.15s ease;">
                        <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                            <div style="width: 34px; height: 34px; border-radius: 8px; background: {{ $tx->type === 'deposit' ? '#e8f5ed' : '#fee2e2' }}; color: {{ $tx->type === 'deposit' ? '#15803d' : '#b91c1c' }}; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <x-icon name="{{ $tx->type === 'deposit' ? 'scale' : 'arrow-up-right' }}" size="16" />
                            </div>
                            <div style="min-width: 0;">
                                <a href="javascript:void(0)" @click="viewReceipt('{{ $tx->id }}')" style="font-weight: 700; color: #173f35; text-decoration: none; font-size: 13.5px; display: inline-flex; align-items: center; gap: 5px;" title="Klik untuk lihat struk digital">
                                    <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $tx->type === 'deposit' ? 'Setoran Sampah Terpilah' : 'Pencairan Tabungan Tunai' }}</span>
                                    <x-icon name="file-text" size="13" class="text-emerald-600" />
                                </a>
                                <div style="color: #64748b; font-size: 11.5px; margin-top: 1px;">
                                    {{ $tx->created_at->diffForHumans() }} · Petugas: {{ $tx->officer?->full_name ?? 'Petugas RT' }}
                                </div>
                            </div>
                        </div>
                        <div style="text-align: right; flex-shrink: 0; padding-left: 10px;">
                            <span style="font-weight: 800; font-size: 13px; padding: 3px 8px; border-radius: 6px; display: inline-block; {{ $tx->type === 'deposit' ? 'color: #15803d; background: #ecfdf5; border: 1px solid #bbf7d0;' : 'color: #b91c1c; background: #fef2f2; border: 1px solid #fecaca;' }}">
                                {{ $tx->type === 'deposit' ? '+' : '-' }}Rp {{ number_format($tx->total_amount, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                    @empty
                    <div style="padding: 24px 16px; text-align: center; color: #94a3b8; display: flex; flex-direction: column; align-items: center; justify-content: center; flex: 1;">
                        <x-icon name="inbox" size="28" style="opacity: 0.4; margin-bottom: 6px;" />
                        <p style="margin: 0; font-size: 13px; font-weight: 600; color: #64748b;">Belum ada riwayat mutasi tabungan.</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Struk Digital Nasabah -->
<div class="overlay modal-overlay" x-show="showReceiptModal" style="display: none;" x-cloak>
    <div class="modal-card" style="max-width: 500px;" @click.away="showReceiptModal = false">
        <div class="modal-head">
            <h3>Struk Bukti Transaksi</h3>
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

                    <div style="border-top: 1px dashed #cbd5e1; margin-top: 10px; padding-top: 10px;">
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
                <span><x-icon name="message-square" size="16" /></span> Bagikan ke WhatsApp
            </a>
        </div>
    </div>
</div>

<!-- Modal Ganti PIN Mandiri -->
<div class="overlay modal-overlay" x-show="showChangePin" style="display: none;" x-cloak>
    <div class="modal-card" @click.away="showChangePin = false" style="max-width: 420px;">
        <div class="modal-head">
            <h3>Ganti PIN Mandiri</h3>
            <button type="button" class="modal-close" @click="showChangePin = false">&times;</button>
        </div>
        <p style="margin: 0 0 16px; font-size: 13px; color: #64748b; line-height: 1.45;">Perbarui 4 digit PIN rahasia untuk akses masuk dan perlindungan saldo tabungan nasabah Anda.</p>
        <form action="{{ route('warga.profil.change-pin') }}" method="POST">
            @csrf
            <div class="form-group" style="margin-bottom: 14px;">
                <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">PIN Saat Ini (4 Digit)</label>
                <input type="password" name="current_pin" maxlength="4" pattern="\d{4}" inputmode="numeric" class="calc-input" style="width: 100%; padding: 10px 14px; border: 1.5px solid #dce6df; border-radius: 10px; font-size: 16px; letter-spacing: 0.25em; text-align: center;" required autofocus placeholder="****">
            </div>
            <div class="form-group" style="margin-bottom: 14px;">
                <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">PIN Baru (4 Digit)</label>
                <input type="password" name="new_pin" maxlength="4" pattern="\d{4}" inputmode="numeric" class="calc-input" style="width: 100%; padding: 10px 14px; border: 1.5px solid #dce6df; border-radius: 10px; font-size: 16px; letter-spacing: 0.25em; text-align: center;" required placeholder="****">
            </div>
            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Konfirmasi PIN Baru (4 Digit)</label>
                <input type="password" name="new_pin_confirmation" maxlength="4" pattern="\d{4}" inputmode="numeric" class="calc-input" style="width: 100%; padding: 10px 14px; border: 1.5px solid #dce6df; border-radius: 10px; font-size: 16px; letter-spacing: 0.25em; text-align: center;" required placeholder="****">
            </div>
            <div class="modal-actions" style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="secondary-button" @click="showChangePin = false">Batal</button>
                <button type="submit" class="primary-button">Simpan PIN Baru</button>
            </div>
        </form>
    </div>
</div>
</div>
@endsection
