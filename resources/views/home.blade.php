@extends('layouts.app')

@section('content')
<main class="public-page" x-data="{
    selectedCatId: '{{ $categories->first()?->id ?? '' }}',
    weightInput: 5,
    categories: {{ Js::from($categories) }},
    co2Factor: 2.9,
    get selectedCategory() {
        return this.categories.find(c => c.id === this.selectedCatId) || this.categories[0] || null;
    },
    get estimatedRupiah() {
        return this.selectedCategory ? Math.round(this.weightInput * this.selectedCategory.price_per_unit) : 0;
    },
    get estimatedCo2Saved() {
        return (this.weightInput * this.co2Factor).toFixed(1);
    },
    formatRupiah(num) {
        return 'Rp ' + Number(num).toLocaleString('id-ID');
    }
}">
    <!-- Hero Section -->
    <section class="public-hero">
        <div class="hero-orbit orbit-one"></div>
        <div class="hero-orbit orbit-two"></div>
        <div class="public-hero-inner">
            <div class="hero-copy">
                <div class="public-eyebrow">
                    <span><x-icon name="recycle" size="16" /></span> Bank Sampah Warga RT 05 / RW 08
                </div>
                <h1>Sampah bernilai,<br><em>lingkungan pulih.</em></h1>
                <p>Setorkan sampah terpilah, pantau harga terkini, dan lihat pertumbuhan tabunganmu dari rumah.</p>
                <div class="hero-cta-row">
                    <a class="public-primary-cta" href="{{ route('warga.dashboard') }}">
                        <span><x-icon name="credit-card" size="18" /></span> Dashboard Saldo Warga <span>→</span>
                    </a>
                    <a class="public-text-cta" href="{{ route('register') }}">Daftar nasabah baru <span>→</span></a>
                </div>
                <div class="trust-row">
                    <span><x-icon name="shield" size="16" /> Harga transparan</span>
                    <span><x-icon name="scale" size="16" /> Timbangan tercatat</span>
                </div>
            </div>
            <div class="hero-visual" aria-hidden="true">
                <div class="visual-disc disc-a"></div>
                <div class="visual-disc disc-b"></div>
                <div class="hero-card hero-card-main">
                    <div class="card-top">
                        <span class="card-leaf"><x-icon name="recycle" size="16" /></span>
                        <span>Tabungan warga</span>
                        <i></i>
                    </div>
                    <strong>Rp {{ number_format($report['totalAmount'], 0, ',', '.') }}</strong>
                    <small>Total nilai setoran bulan ini</small>
                    <div class="card-spark"><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>
                </div>
                <div class="hero-card hero-card-float">
                    <span><x-icon name="recycle" size="20" /></span>
                    <div>
                        <strong>{{ number_format($report['totalWeightKg'], 1, ',', '.') }} kg</strong>
                        <small>Terkumpul {{ $report['periodLabel'] }}</small>
                    </div>
                </div>
                <div class="hero-stamp" aria-label="Status harga aktif terkini">
                    <span>HARGA</span>
                    <strong>AKTIF</strong>
                    <small>TERKINI</small>
                </div>
            </div>
        </div>
    </section>

    <!-- Katalog Harga Beli Hari Ini -->
    <section class="price-section" id="harga">
        <div class="section-heading">
            <div>
                <span class="section-label">KATALOG HARGA</span>
                <h2>Harga beli hari ini</h2>
                <p>Harga per unit setoran yang tercatat pada saat penimbangan.</p>
            </div>
            <span class="live-indicator"><i></i> Diperbarui dari katalog aktif</span>
        </div>
        <div class="price-grid">
            @forelse($categories as $index => $category)
            <article class="price-card">
                <div class="category-illustration illustration-{{ $index % 4 }}"><x-icon name="recycle" size="24" /></div>
                <div class="price-card-head">
                    <span>{{ $category->name }}</span>
                    <small>/ {{ $category->unit }}</small>
                </div>
                <strong>Rp {{ number_format($category->price_per_unit, 0, ',', '.') }}</strong>
                <div class="price-card-footer">
                    <span>Harga beli aktif</span>
                    <span><x-icon name="coins" size="16" /></span>
                </div>
            </article>
            @empty
            <p>Belum ada kategori sampah aktif saat ini.</p>
            @endforelse
        </div>
        <div class="price-note">
            <span>ℹ</span> Nilai setoran ditetapkan berdasarkan harga saat transaksi dicatat dan tidak berubah ketika harga katalog diperbarui.
        </div>
    </section>

    <!-- Kalkulator Simulasi Tabungan Mandiri -->
    <section class="calc-section">
        <div class="section-heading">
            <div>
                <span class="section-label">SIMULASI MANDIRI</span>
                <h2>Kalkulator Potensi Tabungan</h2>
                <p>Hitung estimasi pendapatan dan kontribusi pencegahan emisi karbon dari sampah rumah tangga Anda.</p>
            </div>
        </div>

        <div class="calc-card">
            <div class="calc-form">
                <div class="calc-field">
                    <label for="category-select">Pilih Jenis Sampah Terpilah</label>
                    <select id="category-select" class="calc-select" x-model="selectedCatId">
                        <template x-for="cat in categories" :key="cat.id">
                            <option :value="cat.id" x-text="`${cat.name} (Rp ${Number(cat.price_per_unit).toLocaleString('id-ID')} / ${cat.unit})`"></option>
                        </template>
                    </select>
                </div>

                <div class="calc-field">
                    <label for="weight-input">
                        Perkiraan Berat / Volume (<span x-text="selectedCategory?.unit || 'kg'"></span>)
                    </label>
                    <input
                        id="weight-input"
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
                        <span><x-icon name="leaf" size="16" /></span>
                        <span>Mencegah ~<span x-text="estimatedCo2Saved"></span> kg emisi CO2</span>
                    </div>
                </div>
                <a href="{{ route('warga.dashboard') }}" class="calc-action-link">
                    Buka Dashboard & Tabungan <span>→</span>
                </a>
            </div>
        </div>
    </section>

    <!-- Callout Banner Warga -->
    <section class="public-balance-banner" style="margin-top: 50px;">
        <div class="balance-banner-icon"><x-icon name="credit-card" size="32" /></div>
        <div>
            <span class="section-label">UNTUK WARGA</span>
            <h2>Sudah pernah menyetor?</h2>
            <p>Masuk ke portal nasabah untuk melihat kartu QR digital, saldo tabungan, dan riwayat mutasi lengkap.</p>
        </div>
        <a href="{{ route('warga.dashboard') }}" class="public-banner-link">
            Buka dashboard warga <span>→</span>
        </a>
    </section>
</main>
@endsection
