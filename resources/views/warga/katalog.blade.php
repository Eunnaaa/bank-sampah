@extends('layouts.warga', ['title' => 'Katalog Harga & Panduan — Si-BankSampah'])

@section('warga_content')
<div class="warga-katalog-wrap" x-data="{
    categories: {{ Js::from($categories) }},
    selectedCatId: '{{ $categories->first()?->id ?? '' }}',
    weightInput: 5,
    get selectedCategory() {
        return this.categories.find(c => c.id === this.selectedCatId) || this.categories[0] || null;
    },
    get estimatedRupiah() {
        return this.selectedCategory ? Math.round(this.weightInput * this.selectedCategory.price_per_unit) : 0;
    },
    get estimatedCo2Saved() {
        return (this.weightInput * 2.9).toFixed(1);
    },
    formatRupiah(num) {
        return 'Rp ' + Number(num).toLocaleString('id-ID');
    }
}">
    <!-- Header Halaman -->
    <div class="warga-page-header">
        <div>
            <span class="section-label">KATALOG TRANSAKSI RESMI</span>
            <h1>Harga Beli Komoditas Hari Ini</h1>
            <p>Harga beli per unit komoditas sampah terpilah yang berlaku di pos bank sampah RT {{ $resident->rt }} / RW {{ $resident->rw }}.</p>
        </div>
        <span class="live-indicator">
            <x-icon name="check-circle" size="16" />
            <span>Katalog Aktif Terverifikasi</span>
        </span>
    </div>

    <!-- Grid Kartu Harga Komoditas -->
    <div class="warga-price-grid" style="margin-top: 20px;">
        @forelse($categories as $index => $cat)
        <article class="warga-price-card">
            <div class="price-card-topline">
                <span class="price-icon-badge">
                    <x-icon name="recycle" size="20" />
                </span>
                <span class="price-unit-tag">Per {{ $cat->unit }}</span>
            </div>
            <h3>{{ $cat->name }}</h3>
            <p class="price-desc">Kondisi sampah terpilah dalam keadaan bersih, kering, dan siap timbang.</p>
            <div class="price-card-bottom">
                <small>Harga Beli Pos RT</small>
                <strong>Rp {{ number_format($cat->price_per_unit, 0, ',', '.') }}</strong>
            </div>
        </article>
        @empty
        <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: white; border-radius: 14px; border: 1px solid #e2e8e2; color: #94a3b8;">
            <x-icon name="package" size="32" />
            <p style="margin: 8px 0 0;">Belum ada kategori sampah aktif yang terdaftar.</p>
        </div>
        @endforelse
    </div>

    <!-- Kalkulator Potensi Tabungan Mandiri Interaktif -->
    <section class="calc-section" style="margin-top: 36px; padding: 0;">
        <div class="section-heading" style="margin-bottom: 16px;">
            <div>
                <span class="section-label">SIMULASI MANDIRI</span>
                <h2>Kalkulator Estimasi Tabungan</h2>
                <p>Perkirakan rupiah yang akan masuk ke rekening Anda dan emisi CO₂ yang berhasil dicegah sebelum menyetor.</p>
            </div>
        </div>

        <div class="calc-card" style="margin-top: 0;">
            <div class="calc-form">
                <div class="calc-field">
                    <label for="cat-select">Pilih Jenis Sampah</label>
                    <select id="cat-select" class="calc-select" x-model="selectedCatId">
                        <template x-for="cat in categories" :key="cat.id">
                            <option :value="cat.id" x-text="`${cat.name} (Rp ${Number(cat.price_per_unit).toLocaleString('id-ID')} / ${cat.unit})`"></option>
                        </template>
                    </select>
                </div>

                <div class="calc-field">
                    <label for="weight-field">
                        Perkiraan Bobot / Jumlah (<span x-text="selectedCategory?.unit || 'kg'"></span>)
                    </label>
                    <input
                        id="weight-field"
                        type="number"
                        min="0.1"
                        step="0.5"
                        class="calc-input"
                        x-model.number="weightInput"
                    >
                    <div class="calc-presets">
                        <span>Pilihan cepat:</span>
                        <template x-for="p in [2, 5, 10, 20]" :key="p">
                            <button
                                type="button"
                                class="calc-preset-btn"
                                @click="weightInput = p"
                                x-text="`${p} ${selectedCategory?.unit || 'kg'}`"
                            ></button>
                        </template>
                    </div>
                </div>
            </div>

            <div class="calc-result-box">
                <div>
                    <div class="calc-result-title">Estimasi Tabungan Masuk</div>
                    <div class="calc-result-amount" x-text="formatRupiah(estimatedRupiah)"></div>
                    <div class="calc-eco-pill">
                        <x-icon name="leaf" size="16" />
                        <span>Mencegah ~<span x-text="estimatedCo2Saved"></span> kg emisi CO2</span>
                    </div>
                </div>
                <div style="font-size: 12px; color: #d1fae5; line-height: 1.4;">
                    Nilai riil ditentukan dari hasil timbangan akurat petugas di pos penimbangan lingkungan.
                </div>
            </div>
        </div>
    </section>

    <!-- 4 Langkah Praktis Edukasi Pemilahan Sampah -->
    <section class="steps-section" style="margin-top: 36px; padding: 0;">
        <div class="section-heading" style="margin-bottom: 16px;">
            <div>
                <span class="section-label">PANDUAN LINGKUNGAN</span>
                <h2>4 Langkah Pemilahan Bernilai Maksimal</h2>
                <p>Ikuti panduan berikut agar sampah Anda dihargai dengan kualitas kelas A saat ditimbang.</p>
            </div>
        </div>

        <div class="steps-grid">
            <article class="step-card">
                <span class="step-num">01</span>
                <h3><x-icon name="trash" size="18" /> Pilah dari Rumah</h3>
                <p>Pisahkan anorganik dari sampah dapur organik agar tidak kotor atau membusuk.</p>
            </article>
            <article class="step-card">
                <span class="step-num">02</span>
                <h3><x-icon name="sparkles" size="18" /> Bersihkan &amp; Keringkan</h3>
                <p>Bilas sisa cairan pada botol dan keringkan kardus agar tidak lembap atau berat palsu.</p>
            </article>
            <article class="step-card">
                <span class="step-num">03</span>
                <h3><x-icon name="package" size="18" /> Lipat &amp; Ikat Rapi</h3>
                <p>Pipihkan kardus dan ikat plastik per jenis agar praktis ditimbang di pos timbang.</p>
            </article>
            <article class="step-card">
                <span class="step-num">04</span>
                <h3><x-icon name="scale" size="18" /> Timbang &amp; Masuk Saldo</h3>
                <p>Tunjukkan kartu digital QR Code Anda ke petugas; saldo tabungan otomatis tercatat.</p>
            </article>
        </div>
    </section>
</div>
@endsection
