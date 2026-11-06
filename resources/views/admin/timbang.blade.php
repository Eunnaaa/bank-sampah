@extends('layouts.admin')

@section('admin_content')
<div class="weighing-page" x-data="{
    residents: {{ Js::from($residents) }},
    categories: {{ Js::from($categories) }},
    selectedResidentId: '',
    requestId: '{{ Str::uuid() }}',
    activationUrl: '',
    notes: '',
    items: [
        { category_id: '{{ $categories->first()?->id ?? '' }}', weight: '' }
    ],
    showReceiptModal: false,
    showQuickAddResident: false,
    showScanQrModal: false,
    qrScanInput: '',
    newResident: {
        full_name: '',
        phone_number: '',
        rt: '05',
        rw: '08',
        house_number: ''
    },
    receiptData: null,
    isSubmitting: false,
    errorMessage: '',

    scanQrCode() {
        if (!this.qrScanInput) return;
        let query = this.qrScanInput.trim();
        if (query.startsWith('BS:')) query = query.replace('BS:', '');
        if (query.startsWith('BS-WARGA:')) query = query.replace('BS-WARGA:', '');

        // Try parsing JSON if scanner sent full payload
        try {
            const parsed = JSON.parse(query);
            if (parsed.id) query = parsed.id;
            else if (parsed.phone) query = parsed.phone;
            else if (parsed.resident_id) query = parsed.resident_id;
        } catch (e) {}

        const match = this.residents.find(r => r.id === query || r.phone_number === query);
        if (match) {
            this.selectedResidentId = match.id;
            this.showScanQrModal = false;
            this.qrScanInput = '';
            this.errorMessage = '';
        } else {
            alert('Nasabah tidak ditemukan dengan kode QR tersebut.');
        }
    },

    async quickSaveResident() {
        try {
            const resp = await fetch('{{ route('admin.residents.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(this.newResident)
            });
            const json = await resp.json();
            if (resp.ok && json.success) {
                this.residents.push(json.data);
                this.activationUrl = json.activation_url || '';
                this.selectedResidentId = json.data.id;
                this.showQuickAddResident = false;
                this.newResident = { full_name: '', phone_number: '', rt: '05', rw: '08', house_number: '' };
            } else {
                alert(json.message || 'Gagal mendaftarkan warga.');
            }
        } catch (e) {
            alert('Terjadi kesalahan: ' + e.message);
        }
    },

    get selectedResident() {
        return this.residents.find(r => r.id === this.selectedResidentId) || null;
    },

    addRow() {
        this.items.push({ category_id: this.categories[0]?.id || '', weight: '' });
    },

    removeRow(index) {
        if (this.items.length > 1) {
            this.items.splice(index, 1);
        }
    },

    getSubtotal(item) {
        const cat = this.categories.find(c => c.id === item.category_id);
        const w = parseFloat(item.weight) || 0;
        return cat ? Math.round(w * cat.price_per_unit) : 0;
    },

    get grandTotal() {
        const weights = {};
        for (const item of this.items) {
            weights[item.category_id] = (weights[item.category_id] || 0) + Math.round((Number(item.weight) || 0) * 100);
        }
        return Object.entries(weights).reduce((total, [id, weight]) => {
            const price = BigInt(this.categories.find(category => category.id === id)?.price_per_unit || 0);
            return total + Number((BigInt(weight) * price + 50n) / 100n);
        }, 0);
    },

    get totalWeight() {
        return this.items.reduce((acc, it) => acc + (parseFloat(it.weight) || 0), 0).toFixed(2);
    },

    async submitWeighing() {
        this.errorMessage = '';
        if (!this.selectedResidentId) {
            this.errorMessage = 'Pilih warga nasabah terlebih dahulu.';
            return;
        }

        for (const it of this.items) {
            if (!/^\d+(?:\.\d{1,2})?$/.test(String(it.weight)) || Number(it.weight) <= 0) {
                const cat = this.categories.find(c => c.id === it.category_id);
                this.errorMessage = `Nilai setoran untuk ${cat?.name || 'komoditas'} terlalu kecil (Rp 0). Masukkan bobot yang valid.`;
                return;
            }
        }

        if (this.isSubmitting) return;
        this.isSubmitting = true;
        try {
            const response = await fetch('{{ route('admin.weighing.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    request_id: this.requestId,
                    resident_id: this.selectedResidentId,
                    notes: this.notes,
                    items: this.items
                })
            });

            const data = await response.json();
            if (!response.ok || !data.success) {
                this.errorMessage = data.message || 'Terjadi kesalahan saat menyimpan transaksi.';
                return;
            }

            this.receiptData = data.data.receipt;
            this.requestId = crypto.randomUUID();

            this.showReceiptModal = true;
            // Reset form
            this.items = [{ category_id: this.categories[0]?.id || '', weight: '' }];
            this.notes = '';
        } catch (err) {
            this.errorMessage = 'Gagal menghubungi server: ' + err.message;
        } finally {
            this.isSubmitting = false;
        }
    },

    get waUrl() {
        if (!this.receiptData) return '#';
        let phone = this.receiptData.residentPhone.replace(/\D/g, '');
        if (phone.startsWith('0')) phone = '62' + phone.slice(1);
        if (!phone.startsWith('62')) phone = '62' + phone;

        const lines = this.receiptData.items.map(it => `• ${it.name}: ${it.weight} ${it.unit} × Rp ${Number(it.price).toLocaleString('id-ID')} = *Rp ${Number(it.subtotal).toLocaleString('id-ID')}*`).join('\n');
        const text = encodeURIComponent(
            `*STRUK SETORAN BANK SAMPAH*\n------------------------------------\n*Nasabah:* ${this.receiptData.residentName}\n*Alamat:* ${this.receiptData.householdCode}\n*Tanggal:* ${this.receiptData.date}\n*Petugas:* ${this.receiptData.officerName}\n\n*Rincian Setoran:*\n${lines}\n------------------------------------\n*Total Setoran Ini:* *Rp ${Number(this.receiptData.totalAmount).toLocaleString('id-ID')}*\n*Saldo Setelah Transaksi:* *Rp ${this.receiptData.newBalance == null ? 'Tidak tersedia' : Number(this.receiptData.newBalance).toLocaleString('id-ID')}*\n\n*Terima kasih telah memilah sampah dan menjaga lingkungan kita!*`
        );
        return `https://api.whatsapp.com/send?phone=${phone}&text=${text}`;
    }
}">
    <div class="alert alert-success" x-show="activationUrl" x-cloak>
        <p>Bagikan undangan berikut hanya kepada nasabah yang bersangkutan. Berlaku dua hari.</p>
        <a :href="activationUrl" x-text="activationUrl"></a>
    </div>
    <div class="page-header">
        <div>
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #15803d; background: #dcfce7; padding: 2px 8px; border-radius: 6px;">
                    OPERASIONAL TIMBANGAN · RT {{ auth()->user()->rt ?? '05' }} / RW {{ auth()->user()->rw ?? '08' }}
                </span>
            </div>
            <h1>Pencatatan Timbangan Sampah</h1>
            <p>Timbang setoran sampah nasabah, hitung otomatis berdasarkan katalog komoditas, dan kirimkan struk digital resmi via WhatsApp.</p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button type="button" class="btn-outline-small" @click="showScanQrModal = true" style="padding: 0 14px; height: 38px;">
                <x-icon name="camera" size="15" /> <span>Scan QR Kartu Warga</span>
            </button>
            <button type="button" class="primary-button" @click="showQuickAddResident = true" style="height: 38px; font-size: 13px;">
                <span>+ Daftarkan Nasabah Baru</span>
            </button>
        </div>
    </div>

    <!-- Alert Error JS -->
    <template x-if="errorMessage">
        <div class="alert alert-danger" style="margin-bottom: 20px;">
            <span><x-icon name="alert-triangle" size="16" /></span> <span x-text="errorMessage"></span>
        </div>
    </template>

    <div class="weighing-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
        <!-- Panel Form Input -->
        <div class="weighing-form-card" style="background: #fff; padding: 24px; border-radius: 14px; border: 1px solid var(--border-color);">
            <!-- Pilih Nasabah -->
            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <label for="select-resident" style="margin: 0;"><strong>1. Pilih Nasabah Warga</strong></label>
                    <div style="display: flex; gap: 6px;">
                        <button type="button" class="btn-outline-small" @click="showScanQrModal = true" style="padding: 2px 8px; font-size: 0.78rem; display: inline-flex; align-items: center; gap: 4px;">
                            <x-icon name="camera" size="13" /> Scan QR
                        </button>
                        <button type="button" class="btn-outline-small" @click="showQuickAddResident = true" style="padding: 2px 8px; font-size: 0.78rem;">
                            + Daftarkan Warga Baru
                        </button>
                    </div>
                </div>
                <select id="select-resident" class="calc-select" x-model="selectedResidentId">
                    <option value="">-- Cari atau pilih warga nasabah --</option>
                    <template x-for="res in residents" :key="res.id">
                        <option :value="res.id" x-text="`${res.full_name} (${res.house_number} · RT ${res.rt}/RW ${res.rw})`"></option>
                    </template>
                </select>
            </div>

            <!-- Tabel Baris Timbangan -->
            <div style="margin-top: 24px;">
                <label><strong>2. Rincian Komoditas &amp; Bobot Timbangan</strong></label>
                <div style="margin-top: 10px; display: flex; flex-direction: column; gap: 12px;">
                    <template x-for="(it, idx) in items" :key="idx">
                        <div style="display: flex; gap: 12px; align-items: center; background: #fafafa; padding: 12px; border-radius: 8px; border: 1px solid #ebebeb;">
                            <div style="flex: 2;">
                                <select class="calc-select" x-model="it.category_id">
                                    <template x-for="cat in categories" :key="cat.id">
                                        <option :value="cat.id" x-text="`${cat.name} (Rp ${Number(cat.price_per_unit).toLocaleString('id-ID')}/${cat.unit})`"></option>
                                    </template>
                                </select>
                            </div>
                            <div style="flex: 1;">
                                <input type="number" step="0.01" min="0.01" placeholder="Bobot" class="calc-input" x-model="it.weight">
                            </div>
                            <div style="flex: 1; text-align: right; font-weight: 700; color: #1b5e20;">
                                <small>Perkiraan</small><br>
                                <span x-text="'Rp ' + getSubtotal(it).toLocaleString('id-ID')"></span>
                            </div>
                            <button type="button" @click="removeRow(idx)" style="background: none; border: none; cursor: pointer; color: #c62828; display: flex; align-items: center;" title="Hapus baris">
                                <x-icon name="trash" size="16" />
                            </button>
                        </div>
                    </template>
                </div>

                <button type="button" class="btn-outline-small" @click="addRow()" style="margin-top: 12px;">
                    + Tambah Baris Sampah
                </button>
            </div>

            <div class="form-group" style="margin-top: 20px;">
                <label for="weigh-notes">Catatan Tambahan (Opsional)</label>
                <input id="weigh-notes" type="text" class="calc-input" placeholder="Contoh: Kondisi kardus sangat kering & rapi" x-model="notes">
            </div>
        </div>

        <!-- Panel Ringkasan Total & Eksekusi -->
        <div class="weighing-summary-panel" style="background: #fff; padding: 24px; border-radius: 14px; border: 1px solid var(--border-color); display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <h3 style="margin: 0 0 16px; font-size: 16px; color: #1e3a2d;">Ringkasan Transaksi</h3>
                <p>Bobot komoditas yang sama dijumlahkan sebelum pembulatan rupiah.</p>
                <div style="display: flex; flex-direction: column; gap: 12px; font-size: 0.95rem;">
                    <div style="display: flex; justify-content: space-between;">
                        <span>Nasabah:</span>
                        <strong x-text="selectedResident?.full_name || '-'"></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Alamat:</span>
                        <span x-text="selectedResident ? `RT ${selectedResident.rt}/RW ${selectedResident.rw} (${selectedResident.house_number})` : '-'"></span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Total Berat:</span>
                        <strong x-text="totalWeight + ' kg'"></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-top: 1px dashed #ccc; padding-top: 12px; font-size: 1.15rem; color: #1b5e20;">
                        <strong>Total Rupiah:</strong>
                        <strong x-text="'Rp ' + grandTotal.toLocaleString('id-ID')"></strong>
                    </div>
                </div>
            </div>

            <button type="button" class="save-deposit-button" @click="submitWeighing()" :disabled="isSubmitting">
                <span x-show="!isSubmitting"><x-icon name="save" size="16" /> Catat Setoran &amp; Terbitkan Struk</span>
                <span x-show="isSubmitting">Menyimpan...</span>
            </button>
        </div>
    </div>

    <!-- Modal Struk WhatsApp & Print -->
    <div class="overlay modal-overlay" x-show="showReceiptModal" style="display: none;" x-cloak>
        <div class="modal-card" style="max-width: 500px;">
            <div class="modal-head">
                <h3>Struk Setoran Berhasil</h3>
                <button type="button" class="modal-close" @click="showReceiptModal = false">&times;</button>
            </div>
            <div style="padding: 16px 0;">
                <div style="text-align: center; margin-bottom: 16px;">
                    <span style="color: #2e7d32; display: inline-block; margin-bottom: 8px;"><x-icon name="check-circle" size="44" /></span>
                    <h4>Setoran Berhasil Dicatat!</h4>
                    <p style="color: #666; font-size: 0.85rem;">Saldo nasabah telah otomatis bertambah.</p>
                </div>

                <div style="background: #f9f9f9; padding: 14px; border-radius: 8px; font-size: 0.9rem; line-height: 1.6;">
                    <strong>Nasabah:</strong> <span x-text="receiptData?.residentName"></span><br>
                    <strong>Total Setoran:</strong> <span style="font-weight: 700; color: #1b5e20;" x-text="'Rp ' + Number(receiptData?.totalAmount).toLocaleString('id-ID')"></span><br>
                    <strong>Saldo Tabungan Baru:</strong> <span style="font-weight: 700; color: #2e7d32;" x-text="receiptData?.newBalance == null ? 'Tidak tersedia untuk transaksi lama' : 'Rp ' + Number(receiptData.newBalance).toLocaleString('id-ID')"></span>
                </div>
            </div>
            <div class="modal-actions" style="display: flex; gap: 10px;">
                <button type="button" class="btn-secondary" @click="showReceiptModal = false" style="flex: 1;">Tutup</button>
                <a :href="waUrl" target="_blank" class="btn-primary" style="flex: 2; justify-content: center; background: #25D366; border-color: #25D366;">
                    <span><x-icon name="message-square" size="16" /></span> Kirim Struk WhatsApp
                </a>
            </div>
        </div>
    </div>

    <!-- Modal Pendaftaran Cepat Warga Baru -->
    <div class="overlay modal-overlay" x-show="showQuickAddResident" style="display: none;" x-cloak>
        <div class="modal-card">
            <div class="modal-head">
                <h3>Daftar Cepat Nasabah Baru</h3>
                <button type="button" class="modal-close" @click="showQuickAddResident = false">&times;</button>
            </div>
            <form @submit.prevent="quickSaveResident()">
                <div class="form-group">
                    <label>Nama Lengkap Warga</label>
                    <input type="text" class="calc-input" x-model="newResident.full_name" placeholder="Contoh: Ibu Rina" required>
                </div>
                <div class="form-group" style="margin-top: 10px;">
                    <label>Nomor WhatsApp Aktif</label>
                    <input type="tel" class="calc-input" x-model="newResident.phone_number" placeholder="Contoh: 081234567890" required>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 10px;">
                    <div class="form-group">
                        <label>RT</label>
                        <input type="text" class="calc-input" x-model="newResident.rt" required>
                    </div>
                    <div class="form-group">
                        <label>RW</label>
                        <input type="text" class="calc-input" x-model="newResident.rw" required>
                    </div>
                </div>
                <div class="form-group" style="margin-top: 10px;">
                    <label>Nomor Rumah / Blok</label>
                    <input type="text" class="calc-input" x-model="newResident.house_number" placeholder="Contoh: Blok C No. 08" required>
                </div>
                <div class="modal-actions" style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="secondary-button" @click="showQuickAddResident = false">Batal</button>
                    <button type="submit" class="primary-button">Simpan &amp; Pilih Langsung</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Pindai / Input Kode QR Nasabah -->
    <div class="overlay modal-overlay" x-show="showScanQrModal" style="display: none;" x-cloak>
        <div class="modal-card" style="max-width: 440px;">
            <div class="modal-head">
                <h3>Pindai QR Kartu Nasabah</h3>
                <button type="button" class="modal-close" @click="showScanQrModal = false">&times;</button>
            </div>
            <div style="padding: 16px 0;">
                <p style="font-size: 13px; color: #475569; margin: 0 0 14px;">
                    Scan kartu QR nasabah dengan barcode/QR scanner atau masukkan nomor WhatsApp / kode nasabah:
                </p>
                <form @submit.prevent="scanQrCode()">
                    <div class="form-group">
                        <label>Kode QR / No. WhatsApp</label>
                        <input
                            type="text"
                            class="calc-input"
                            x-model="qrScanInput"
                            placeholder="Contoh: BS:081999888777"
                            autofocus
                            required
                        >
                    </div>
                    <div class="modal-actions" style="margin-top: 18px; display: flex; gap: 8px;">
                        <button type="button" class="secondary-button" @click="showScanQrModal = false" style="flex: 1;">Batal</button>
                        <button type="submit" class="primary-button" style="flex: 2; justify-content: center;">
                            <x-icon name="check" size="14" />
                            <span>Pilih Nasabah</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
