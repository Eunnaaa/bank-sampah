@extends('layouts.admin')

@section('admin_content')
<div style="max-width: 900px; margin: 0 auto;" x-data="{
    categories: {{ Js::from($inventorySummary) }},
    items: [
        { category_id: '{{ $inventorySummary->first()['category_id'] ?? '' }}', weight: {{ min(1, $inventorySummary->first()['stock_weight'] ?? 1) }}, selling_price: {{ $inventorySummary->first()['buying_price'] ?? 0 }} }
    ],

    addItem() {
        if (this.categories.length > 0) {
            const first = this.categories[0];
            this.items.push({
                category_id: first.category_id,
                weight: Math.min(1, first.stock_weight),
                selling_price: first.buying_price
            });
        }
    },

    removeItem(idx) {
        if (this.items.length > 1) {
            this.items.splice(idx, 1);
        }
    },

    getCategory(catId) {
        return this.categories.find(c => c.category_id === catId);
    },

    onCategoryChange(idx) {
        const cat = this.getCategory(this.items[idx].category_id);
        if (cat) {
            // Default harga jual sedikit di atas harga beli sebagai saran awal
            this.items[idx].selling_price = Math.round(cat.buying_price * 1.15 / 100) * 100;
        }
    },

    getItemSubtotal(it) {
        return Math.round((Number(it.weight) || 0) * (Number(it.selling_price) || 0));
    },

    get grandTotalRevenue() {
        return this.items.reduce((acc, it) => acc + this.getItemSubtotal(it), 0);
    }
}">
    <!-- Header -->
    <div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <a href="{{ route('admin.sales.index') }}" style="color: #64748b; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; margin-bottom: 6px;">
                ← Kembali ke Gudang &amp; Penjualan
            </a>
            <h1 style="margin: 0; font-size: 24px; font-weight: 800; color: #173f35; letter-spacing: -0.02em;">
                Catat Penjualan ke Pengepul / Bandar
            </h1>
            <p style="margin: 4px 0 0; color: #64748b; font-size: 14px;">
                Keluarkan stok komoditas dari gudang posko RT dan catat penerimaan kas hasil penjualan.
            </p>
        </div>
    </div>

    @if($errors->any())
    <div style="background: #fef2f2; border: 1.5px solid #fecaca; color: #b91c1c; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 14px;">
        <strong>Periksa kembali data Anda:</strong>
        <ul style="margin: 6px 0 0; padding-left: 20px;">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if($inventorySummary->isEmpty())
        <p>Belum ada komoditas dengan stok minimal 0,01 satuan untuk dijual.</p>
    @else
    <form method="POST" action="{{ route('admin.sales.store') }}" style="display: flex; flex-direction: column; gap: 20px;">
        @csrf

        <!-- Card 1: Informasi Pembeli & Tanggal -->
        <div style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 16px; padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
            <h2 style="margin: 0 0 16px; font-size: 16px; font-weight: 800; color: #173f35;">1. Informasi Pembeli / Pengepul</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                        Nama Pengepul / Perusahaan Daur Ulang <span style="color: #ef4444;">*</span>
                    </label>
                    <input
                        type="text"
                        name="buyer_name"
                        value="{{ old('buyer_name') }}"
                        placeholder="Contoh: Pak Bejo / CV Berkah Daur Ulang"
                        required
                        class="calc-input"
                        style="height: 44px; width: 100%; border: 1.5px solid #cbd5e1; border-radius: 10px; padding: 0 14px; font-size: 14px;"
                    >
                </div>

                <div>
                    <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                        Tanggal Penjualan <span style="color: #ef4444;">*</span>
                    </label>
                    <input
                        type="date"
                        name="sale_date"
                        value="{{ old('sale_date', now()->format('Y-m-d')) }}"
                        required
                        class="calc-input"
                        style="height: 44px; width: 100%; border: 1.5px solid #cbd5e1; border-radius: 10px; padding: 0 14px; font-size: 14px;"
                    >
                </div>
            </div>

            <div style="margin-top: 14px;">
                <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                    Catatan Tambahan (Opsional)
                </label>
                <input
                    type="text"
                    name="notes"
                    value="{{ old('notes') }}"
                    placeholder="Contoh: Pengangkutan truk pertama, pembayaran tunai langsung di posko"
                    class="calc-input"
                    style="height: 44px; width: 100%; border: 1.5px solid #cbd5e1; border-radius: 10px; padding: 0 14px; font-size: 14px;"
                >
            </div>
        </div>

        <!-- Card 2: Rincian Komoditas yang Dijual -->
        <div style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 16px; padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <div>
                    <h2 style="margin: 0; font-size: 16px; font-weight: 800; color: #173f35;">2. Rincian Komoditas &amp; Harga Jual</h2>
                    <small style="color: #64748b; font-size: 13px;">Tentukan bobot yang diangkut dan harga per satuan yang disepakati dengan pengepul.</small>
                </div>
                <button
                    type="button"
                    @click="addItem()"
                    style="display: inline-flex; align-items: center; gap: 6px; background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; padding: 6px 12px; border-radius: 8px; font-size: 12.5px; font-weight: 700; cursor: pointer;"
                >
                    + Tambah Baris
                </button>
            </div>

            <div style="display: flex; flex-direction: column; gap: 12px;">
                <template x-for="(it, idx) in items" :key="idx">
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 12px; align-items: flex-end;">
                        <!-- Dropdown Komoditas -->
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 4px;">
                                Jenis Komoditas
                            </label>
                            <select
                                :name="`items[${idx}][category_id]`"
                                x-model="it.category_id"
                                @change="onCategoryChange(idx)"
                                class="calc-select"
                                style="height: 42px; width: 100%; font-size: 13.5px;"
                                required
                            >
                                <template x-for="cat in categories" :key="cat.category_id">
                                    <option :value="cat.category_id" x-text="`${cat.name} (Stok: ${cat.stock_weight} ${cat.unit})`"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Bobot Terjual -->
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 4px;">
                                Berat Dijual (<span x-text="getCategory(it.category_id)?.unit || 'kg'"></span>)
                            </label>
                            <input
                                type="number"
                                :name="`items[${idx}][weight]`"
                                x-model.number="it.weight"
                                min="0.01"
                                step="0.01"
                                required
                                class="calc-input"
                                style="height: 42px; width: 100%; font-size: 13.5px;"
                            >
                        </div>

                        <!-- Harga Jual ke Pengepul -->
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 4px;">
                                Harga Jual Pengepul (Rp/<span x-text="getCategory(it.category_id)?.unit || 'kg'"></span>)
                            </label>
                            <input
                                type="number"
                                :name="`items[${idx}][selling_price_per_unit]`"
                                x-model.number="it.selling_price"
                                min="0"
                                step="100"
                                required
                                class="calc-input"
                                style="height: 42px; width: 100%; font-size: 13.5px;"
                            >
                        </div>

                        <!-- Aksi Hapus Baris -->
                        <div style="padding-bottom: 4px;">
                            <button
                                type="button"
                                @click="removeItem(idx)"
                                :disabled="items.length === 1"
                                style="height: 42px; width: 42px; display: inline-flex; align-items: center; justify-content: center; background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; border-radius: 8px; cursor: pointer;"
                                title="Hapus baris"
                            >
                                <x-icon name="trash" size="16" />
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Ringkasan penjualan -->
        <div style="background: linear-gradient(135deg, #10382d 0%, #164938 100%); color: white; border-radius: 16px; padding: 24px; box-shadow: 0 8px 24px rgba(16, 56, 45, 0.2);">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div>
                    <div style="font-size: 12px; color: #a7f3d0; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">Total Kas Masuk (Pengepul)</div>
                    <div style="font-size: 26px; font-weight: 800; margin-top: 4px;">
                        Rp <span x-text="Number(grandTotalRevenue).toLocaleString('id-ID')"></span>
                    </div>
                </div>

            </div>
            <p>Modal dan laba kotor dihitung saat penjualan disimpan, dari setoran tertua yang masih tersedia.</p>

            <div style="margin-top: 24px; padding-top: 18px; border-top: 1px solid rgba(255,255,255,0.15); display: flex; justify-content: flex-end; gap: 12px;">
                <a href="{{ route('admin.sales.index') }}" style="padding: 10px 20px; background: rgba(255,255,255,0.12); color: white; border-radius: 10px; text-decoration: none; font-weight: 600; font-size: 14px;">
                    Batal
                </a>
                <button
                    type="submit"
                    style="padding: 10px 24px; background: #22c55e; color: #052e16; border: none; border-radius: 10px; font-weight: 800; font-size: 14px; cursor: pointer; box-shadow: 0 4px 12px rgba(34, 197, 94, 0.4);"
                >
                    Konfirmasi &amp; Simpan Penjualan
                </button>
            </div>
        </div>
    </form>
    @endif
</div>
@endsection
