@extends('layouts.admin')

@section('admin_content')
<div class="residents-page" x-data="{
    showAddModal: false,
    showWithdrawModal: false,
    showEditModal: false,
    showResetPinModal: false,
    showHistoryModal: false,
    selectedResident: null,
    withdrawAmount: '',
    withdrawSubmitting: false,
    withdrawalRequestId: '{{ Str::uuid() }}',
    editForm: {
        id: '',
        full_name: '',
        phone_number: '',
        rt: '',
        rw: '',
        house_number: ''
    },
    resetPinForm: {
        id: '',
        resident_name: '',
        new_pin: ''
    },
    historyData: null,
    isLoadingHistory: false,

    openWithdraw(res) {
        this.selectedResident = res;
        this.withdrawAmount = '';
        this.withdrawSubmitting = false;
        this.withdrawalRequestId = crypto.randomUUID();
        this.showWithdrawModal = true;
    },

    openEdit(res) {
        this.editForm = {
            id: res.id,
            full_name: res.full_name,
            phone_number: res.phone_number,
            rt: res.rt,
            rw: res.rw,
            house_number: res.house_number
        };
        this.showEditModal = true;
    },

    openResetPin(res) {
        this.resetPinForm = {
            id: res.id,
            resident_name: res.full_name,
            new_pin: ''
        };
        this.showResetPinModal = true;
    },

    async openHistory(res) {
        this.selectedResident = res;
        this.historyData = null;
        this.isLoadingHistory = true;
        this.showHistoryModal = true;
        try {
            const resp = await fetch(`/admin/warga/${res.id}/riwayat`);
            const json = await resp.json();
            if (json.success) {
                this.historyData = json;
            }
        } catch (e) {
            console.error('Gagal mengambil riwayat nasabah', e);
        } finally {
            this.isLoadingHistory = false;
        }
    }
}">
    <div class="page-header">
        <div>
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #2563eb; background: #dbeafe; padding: 2px 8px; border-radius: 6px;">
                    DIREKTORI NASABAH · RT {{ auth()->user()->rt ?? '05' }} / RW {{ auth()->user()->rw ?? '08' }}
                </span>
            </div>
            <h1>Data Warga &amp; Nasabah Bank Sampah</h1>
            <p>Kelola data nasabah lingkungan RT/RW, lihat riwayat mutasi, atur ulang PIN, dan proses pencairan saldo kas.</p>
        </div>
        <button type="button" class="primary-button" @click="showAddModal = true">
            <span>+ Daftarkan Nasabah Baru</span>
        </button>
    </div>

    @if(session('activation_url'))
        <div class="alert alert-success">
            <p>Bagikan undangan ini hanya kepada nasabah yang bersangkutan. Berlaku dua hari dan hanya sekali pakai.</p>
            <a href="{{ session('activation_url') }}">{{ session('activation_url') }}</a>
        </div>
    @endif

    <!-- Filter & Pencarian -->
    <div class="table-controls" style="margin-bottom: 20px;">
        <form action="{{ route('admin.residents.index') }}" method="GET" style="display: flex; gap: 10px;">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama nasabah, no WA, atau no rumah..." class="calc-input" style="max-width: 380px;">
            <button type="submit" class="secondary-button">Cari</button>
            @if(request('q'))
                <a href="{{ route('admin.residents.index') }}" class="btn-outline-small" style="display: inline-flex; align-items: center; padding: 6px 12px;">Reset</a>
            @endif
        </form>
    </div>

    <!-- Tabel Nasabah -->
    <div class="table-card" style="background: #fff; border-radius: 12px; border: 1px solid var(--border-color); overflow-x: auto;">
        <table class="admin-table" style="width: 100%; border-collapse: collapse; min-width: 800px;">
            <thead>
                <tr style="background: #f8faf8; border-bottom: 1px solid var(--border-color); text-align: left;">
                    <th style="padding: 14px 16px;">Nama Lengkap</th>
                    <th style="padding: 14px 16px;">WhatsApp</th>
                    <th style="padding: 14px 16px;">Alamat Rumah</th>
                    <th style="padding: 14px 16px;">Status PIN</th>
                    <th style="padding: 14px 16px; text-align: right;">Saldo Tabungan</th>
                    <th style="padding: 14px 16px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($residents as $res)
                <tr style="border-bottom: 1px solid #f0f0f0;">
                    <td style="padding: 14px 16px; font-weight: 600;">
                        <a href="javascript:void(0)" @click="openHistory({{ Js::from($res) }})" style="color: inherit; text-decoration: none; border-bottom: 1px dashed #2e7d32;" title="Lihat Buku Mutasi Transaksi">
                            {{ $res->full_name }} <x-icon name="file-text" size="14" />
                        </a>
                    </td>
                    <td style="padding: 14px 16px;">{{ $res->phone_number }}</td>
                    <td style="padding: 14px 16px;">{{ $res->house_number }} (RT {{ $res->rt }}/RW {{ $res->rw }})</td>
                    <td style="padding: 14px 16px;">
                        @if($res->pin_hash)
                        <span style="color: #2e7d32; font-size: 0.85rem; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                            <x-icon name="check" size="14" /> Aktif
                        </span>
                        @else
                        <span style="color: #f57c00; font-size: 0.85rem; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                            <x-icon name="clock" size="14" /> Belum Aktivasi
                        </span>
                        @endif
                    </td>
                    <td style="padding: 14px 16px; text-align: right; font-weight: 700; color: #1b5e20;">
                        Rp {{ number_format($res->balance, 0, ',', '.') }}
                    </td>
                    <td style="padding: 14px 16px; text-align: center;">
                        <div style="display: inline-flex; gap: 6px; align-items: center; justify-content: center;">
                            <button type="button" class="secondary-button" @click="openHistory({{ Js::from($res) }})" style="padding: 4px 8px; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 4px;" title="Lihat Riwayat Transaksi">
                                <x-icon name="file-text" size="13" /> Riwayat
                            </button>

                            @can('admin-only')
                            <button type="button" class="secondary-button" @click="openWithdraw({{ Js::from($res) }})" :disabled="{{ $res->balance <= 0 ? 'true' : 'false' }}" style="padding: 4px 8px; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 4px;" title="Cairkan Dana Tabungan">
                                <x-icon name="dollar-sign" size="13" /> Cairkan
                            </button>

                            <button type="button" class="btn-outline-small" @click="openEdit({{ Js::from($res) }})" style="padding: 4px 8px; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 4px;" title="Edit Data Warga">
                                <x-icon name="edit" size="13" /> Edit
                            </button>

                            @if(! $res->pin_hash)
                                <form action="{{ route('admin.residents.invite', $res->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn-outline-small">Buat Undangan Aktivasi</button>
                                </form>
                            @endif
                            <button type="button" class="btn-outline-small" @click="openResetPin({{ Js::from($res) }})" style="padding: 4px 8px; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 4px;" title="Atur Ulang PIN Keamanan">
                                <x-icon name="key" size="13" /> PIN
                            </button>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="padding: 24px; text-align: center; color: #888;">Belum ada data warga nasabah.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 20px;">
        {{ $residents->links() }}
    </div>

    <!-- Modal Pendaftaran Nasabah Baru -->
    <div class="overlay modal-overlay" x-show="showAddModal" style="display: none;" x-cloak>
        <div class="modal-card">
            <div class="modal-head">
                <h3>Daftarkan Nasabah Baru</h3>
                <button type="button" class="modal-close" @click="showAddModal = false">&times;</button>
            </div>
            <form action="{{ route('admin.residents.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label>Nama Lengkap Kepala Keluarga / Warga</label>
                    <input type="text" name="full_name" class="calc-input" placeholder="Contoh: Pak Budi Santoso" required>
                </div>
                <div class="form-group" style="margin-top: 10px;">
                    <label>Nomor WhatsApp Aktif</label>
                    <input type="tel" name="phone_number" class="calc-input" placeholder="08123456789" required>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 10px;">
                    <div class="form-group">
                        <label>RT</label>
                        <input type="text" name="rt" value="05" class="calc-input" required>
                    </div>
                    <div class="form-group">
                        <label>RW</label>
                        <input type="text" name="rw" value="08" class="calc-input" required>
                    </div>
                </div>
                <div class="form-group" style="margin-top: 10px;">
                    <label>Nomor Rumah / Blok</label>
                    <input type="text" name="house_number" class="calc-input" placeholder="Contoh: Blok B No. 12" required>
                </div>
                <div class="form-group" style="margin-top: 10px;">
                    <label>PIN Awal 4-Digit (Opsional)</label>
                    <input type="password" name="pin" maxlength="4" class="calc-input" placeholder="Dapat diisi nanti oleh warga">
                </div>
                <div class="modal-actions" style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="secondary-button" @click="showAddModal = false">Batal</button>
                    <button type="submit" class="primary-button">Daftarkan Sekarang</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Nasabah -->
    <div class="overlay modal-overlay" x-show="showEditModal" style="display: none;" x-cloak>
        <div class="modal-card">
            <div class="modal-head">
                <h3>Edit Data Nasabah</h3>
                <button type="button" class="modal-close" @click="showEditModal = false">&times;</button>
            </div>
            <form :action="`/admin/warga/${editForm.id}`" method="POST">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="full_name" class="calc-input" x-model="editForm.full_name" required>
                </div>
                <div class="form-group" style="margin-top: 10px;">
                    <label>Nomor WhatsApp</label>
                    <input type="tel" name="phone_number" class="calc-input" x-model="editForm.phone_number" required>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 10px;">
                    <div class="form-group">
                        <label>RT</label>
                        <input type="text" name="rt" class="calc-input" x-model="editForm.rt" required>
                    </div>
                    <div class="form-group">
                        <label>RW</label>
                        <input type="text" name="rw" class="calc-input" x-model="editForm.rw" required>
                    </div>
                </div>
                <div class="form-group" style="margin-top: 10px;">
                    <label>Nomor Rumah / Blok</label>
                    <input type="text" name="house_number" class="calc-input" x-model="editForm.house_number" required>
                </div>
                <div class="modal-actions" style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="secondary-button" @click="showEditModal = false">Batal</button>
                    <button type="submit" class="primary-button">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Reset PIN -->
    <div class="overlay modal-overlay" x-show="showResetPinModal" style="display: none;" x-cloak>
        <div class="modal-card">
            <div class="modal-head">
                <h3>Atur Ulang PIN Nasabah</h3>
                <button type="button" class="modal-close" @click="showResetPinModal = false">&times;</button>
            </div>
            <form :action="`/admin/warga/${resetPinForm.id}/reset-pin`" method="POST">
                @csrf
                <div style="background: #fdf2f2; padding: 12px; border-radius: 8px; margin-bottom: 14px; font-size: 0.85rem; color: #991b1b;">
                    Nasabah: <strong x-text="resetPinForm.resident_name"></strong>
                </div>
                <p style="font-size: 0.85rem; color: #475569; margin-bottom: 12px;">
                    Bila field PIN Baru dikosongkan, nasabah dapat melakukan aktivasi mandiri kembali menggunakan kode undangan sekali pakai dari pengurus.
                </p>
                <div class="form-group">
                    <label>PIN Baru 4 Digit (Opsional)</label>
                    <input type="text" name="new_pin" maxlength="4" placeholder="Contoh: 1234 atau biarkan kosong" class="calc-input" x-model="resetPinForm.new_pin">
                </div>
                <div class="modal-actions" style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="secondary-button" @click="showResetPinModal = false">Batal</button>
                    <button type="submit" class="primary-button">Konfirmasi Reset PIN</button>
                </div>
            </form>
        </div>
    </div>

    @if(session('withdrawal_receipt'))
        <section class="alert alert-success" aria-label="Bukti pencairan tunai">
            <h3>Bukti Pencairan Tunai</h3>
            <p>ID transaksi: <strong>{{ session('withdrawal_receipt.id') }}</strong></p>
            <p>Nasabah: {{ session('withdrawal_receipt.residentName') }} · Rp {{ number_format(session('withdrawal_receipt.totalAmount'), 0, ',', '.') }}</p>
            <p>Sisa saldo: Rp {{ number_format(session('withdrawal_receipt.newBalance'), 0, ',', '.') }}</p>
            <p>Persetujuan warga: {{ \Carbon\CarbonImmutable::parse(session('withdrawal_receipt.residentApprovedAt'))->timezone(config('app.business_timezone'))->translatedFormat('d F Y, H:i') }}</p>
            <p>Dicatat: {{ session('withdrawal_receipt.date') }} · Oleh {{ session('withdrawal_receipt.officerName') }}</p>
            <p>Satu ID transaksi hanya untuk satu penyerahan uang tunai. Cocokkan identitas penerima dan bukti ini.</p>
        </section>
    @endif

    <!-- Modal Pencairan Saldo Tunai -->
    <div class="overlay modal-overlay" x-show="showWithdrawModal" style="display: none;" x-cloak>
        <div class="modal-card">
            <div class="modal-head">
                <h3>Pencairan Saldo Tabungan Tunai</h3>
                <button type="button" class="modal-close" @click="showWithdrawModal = false">&times;</button>
            </div>
            <form action="{{ route('admin.residents.withdraw') }}" method="POST" @submit="if (withdrawSubmitting) { $event.preventDefault(); } else { withdrawSubmitting = true; }">
                @csrf
                <input type="hidden" name="resident_id" :value="selectedResident?.id">
                <input type="hidden" name="request_id" :value="withdrawalRequestId">
                <div style="background: #f1f8e9; padding: 12px; border-radius: 8px; margin-bottom: 14px; font-size: 0.9rem;">
                    <strong>Nasabah:</strong> <span x-text="selectedResident?.full_name"></span><br>
                    <strong>Saldo Saat Ini:</strong> <span style="font-weight: 700; color: #1b5e20;" x-text="'Rp ' + Number(selectedResident?.balance || 0).toLocaleString('id-ID')"></span>
                </div>
                <div class="form-group">
                    <label>Nominal Penarikan Tunai (Rp)</label>
                    <input
                        type="number"
                        name="amount"
                        min="1"
                        :max="selectedResident?.balance || 0"
                        class="calc-input"
                        x-model="withdrawAmount"
                        placeholder="Contoh: 50000"
                        required
                    >
                </div>
                <div class="form-group" style="margin-top: 10px;">
                    <label for="withdrawal-authorization">Kode Persetujuan Warga</label>
                    <input id="withdrawal-authorization" type="text" name="authorization_code" class="calc-input" minlength="12" maxlength="12" autocomplete="off" aria-describedby="withdrawal-help" required>
                    <p id="withdrawal-help">Warga membuat kode di menu Profil untuk nominal ini. Berlaku 10 menit dan hanya sekali pakai. Verifikasi identitas penerima sebelum menyerahkan uang.</p>
                </div>
                <div class="form-group" style="margin-top: 10px;">
                    <label for="withdrawal-password">Konfirmasi Kata Sandi Admin</label>
                    <input id="withdrawal-password" type="password" name="admin_password" class="calc-input" autocomplete="current-password" required>
                </div>
                <div class="form-group" style="margin-top: 10px;">
                    <label>Catatan Penarikan</label>
                    <input type="text" name="notes" class="calc-input" placeholder="Pencairan tabungan rutin">
                </div>
                <div class="modal-actions" style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="secondary-button" @click="showWithdrawModal = false">Batal</button>
                    <button type="submit" class="primary-button" :disabled="withdrawSubmitting" style="background: #c62828; border-color: #c62828;">Konfirmasi Tarik Tunai</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Buku Mutasi / Riwayat Nasabah -->
    <div class="overlay modal-overlay" x-show="showHistoryModal" style="display: none;" x-cloak>
        <div class="modal-card" style="max-width: 650px;">
            <div class="modal-head">
                <h3>Buku Mutasi Transaksi Nasabah</h3>
                <button type="button" class="modal-close" @click="showHistoryModal = false">&times;</button>
            </div>
            <div style="padding: 10px 0;">
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 12px; border-radius: 8px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <strong style="color: #166534;" x-text="selectedResident?.full_name"></strong><br>
                        <small style="color: #15803d;" x-text="selectedResident?.household_code"></small>
                    </div>
                    <div style="text-align: right;">
                        <small style="color: #64748b;">Saldo Aktif:</small><br>
                        <strong style="font-size: 1.15rem; color: #166534;" x-text="'Rp ' + Number(selectedResident?.balance || 0).toLocaleString('id-ID')"></strong>
                    </div>
                </div>

                <div x-show="isLoadingHistory" style="text-align: center; padding: 24px; color: #64748b;">
                    Memuat riwayat mutasi...
                </div>

                <div x-show="!isLoadingHistory && historyData">
                    <div style="max-height: 340px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px;">
                        <template x-for="tx in historyData?.transactions" :key="tx.id">
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 12px; background: #fafafa; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.85rem;">
                                <div>
                                    <div style="font-weight: 700;" :style="tx.type === 'deposit' ? 'color: #166534' : 'color: #991b1b'" x-text="tx.type === 'deposit' ? 'Setoran Sampah' : 'Penarikan Tunai'"></div>
                                    <div style="color: #64748b; font-size: 0.75rem;" x-text="new Date(tx.created_at).toLocaleDateString('id-ID', {day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'}) + ' · ' + (tx.notes || '-')"></div>
                                    <template x-if="tx.items && tx.items.length > 0">
                                        <div style="color: #15803d; font-size: 0.75rem; margin-top: 3px;">
                                            <template x-for="(it, i) in tx.items" :key="i">
                                                <span x-text="`${it.category?.name || 'Komoditas'}: ${it.weight} ${it.category?.unit || 'kg'}` + (i < tx.items.length - 1 ? ' · ' : '')"></span>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-weight: 700; font-size: 0.95rem;" :style="tx.type === 'deposit' ? 'color: #166534' : 'color: #991b1b'" x-text="(tx.type === 'deposit' ? '+Rp ' : '-Rp ') + Number(tx.total_amount).toLocaleString('id-ID')"></div>
                                    <div style="font-size: 0.75rem; color: #64748b;" x-text="tx.officer ? `Oleh: ${tx.officer.full_name}` : ''"></div>
                                </div>
                            </div>
                        </template>

                        <div x-show="!historyData?.transactions || historyData?.transactions.length === 0" style="text-align: center; padding: 20px; color: #94a3b8;">
                            Belum ada riwayat transaksi untuk nasabah ini.
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-actions" style="margin-top: 16px; display: flex; justify-content: flex-end;">
                <button type="button" class="secondary-button" @click="showHistoryModal = false">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection
