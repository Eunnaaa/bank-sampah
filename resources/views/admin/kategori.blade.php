@extends('layouts.admin')

@section('admin_content')
<div class="categories-page" x-data="{
    showAddModal: false,
    showEditModal: false,
    editForm: {
        id: '',
        name: '',
        unit: 'kg',
        price_per_unit: ''
    },

    openEdit(cat) {
        this.editForm = {
            id: cat.id,
            name: cat.name,
            unit: cat.unit,
            price_per_unit: cat.price_per_unit
        };
        this.showEditModal = true;
    }
}">
    <div class="page-header">
        <div>
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #ca8a04; background: #fef9c3; padding: 2px 8px; border-radius: 6px;">
                    KATALOG RESMI · RT {{ auth()->user()->rt ?? '05' }} / RW {{ auth()->user()->rw ?? '08' }}
                </span>
            </div>
            <h1>Katalog Komoditas &amp; Harga Beli</h1>
            <p>Kelola harga beli per kg/liter yang berlaku saat petugas mencatat penimbangan sampah di lingkungan.</p>
        </div>
        @can('admin-only')
        <button type="button" class="primary-button" @click="showAddModal = true">
            <span>+ Tambah Komoditas Baru</span>
        </button>
        @endcan
    </div>

    <div class="table-card" style="background: #fff; border-radius: 12px; border: 1px solid var(--border-color); overflow-x: auto; margin-top: 20px;">
        <table class="admin-table" style="width: 100%; border-collapse: collapse; min-width: 750px;">
            <thead>
                <tr style="background: #f8faf8; border-bottom: 1px solid var(--border-color); text-align: left;">
                    <th style="padding: 14px 16px;">Nama Komoditas</th>
                    <th style="padding: 14px 16px;">Satuan</th>
                    <th style="padding: 14px 16px;">Harga Beli / Satuan</th>
                    <th style="padding: 14px 16px;">Status</th>
                    @can('admin-only')
                    <th style="padding: 14px 16px; text-align: center;">Perbarui Cepat</th>
                    <th style="padding: 14px 16px; text-align: center;">Aksi</th>
                    @endcan
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $cat)
                <tr style="border-bottom: 1px solid #f0f0f0;">
                    <td style="padding: 14px 16px; font-weight: 600;">
                        {{ $cat->name }}
                    </td>
                    <td style="padding: 14px 16px; color: #555;">/ {{ $cat->unit }}</td>
                    <td style="padding: 14px 16px; font-weight: 700; color: #1b5e20;">
                        Rp {{ number_format($cat->price_per_unit, 0, ',', '.') }}
                    </td>
                    <td style="padding: 14px 16px;">
                        @if($cat->is_active)
                        <span style="display: inline-flex; align-items: center; gap: 4px; color: #2e7d32; font-weight: 600; font-size: 0.85rem;">
                            <x-icon name="check" style="width: 13px; height: 13px;" /> Aktif
                        </span>
                        @else
                        <span style="display: inline-flex; align-items: center; gap: 4px; color: #c62828; font-weight: 600; font-size: 0.85rem;">
                            <x-icon name="close" style="width: 13px; height: 13px;" /> Non-Aktif
                        </span>
                        @endif
                    </td>
                    @can('admin-only')
                    <td style="padding: 14px 16px; text-align: center;">
                        <form action="{{ route('admin.categories.update-price', $cat->id) }}" method="POST" style="display: inline-flex; gap: 6px; align-items: center;">
                            @csrf
                            @method('PUT')
                            <input type="number" name="price_per_unit" value="{{ $cat->price_per_unit }}" min="1" class="calc-input" style="width: 100px; padding: 6px 8px; font-size: 0.85rem;">
                            <button type="submit" class="secondary-button" style="padding: 4px 8px; font-size: 0.8rem;">Simpan</button>
                        </form>
                    </td>
                    <td style="padding: 14px 16px; text-align: center;">
                        <div style="display: inline-flex; gap: 6px; align-items: center;">
                            <button type="button" class="btn-outline-small" @click="openEdit({{ Js::from($cat) }})" style="padding: 4px 8px; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 4px;" title="Edit Komoditas">
                                <x-icon name="edit" size="13" /> Edit
                            </button>

                            <form action="{{ route('admin.categories.toggle', $cat->id) }}" method="POST" style="display: inline;">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="secondary-button" style="padding: 4px 8px; font-size: 0.8rem;">
                                    {{ $cat->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>

                            <form action="{{ route('admin.categories.destroy', $cat->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus komoditas {{ $cat->name }}? Bila terdapat riwayat transaksi, komoditas akan otomatis dinonaktifkan.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="secondary-button" style="padding: 4px 8px; font-size: 0.8rem; color: #dc2626; display: inline-flex; align-items: center;" title="Hapus Komoditas">
                                    <x-icon name="trash" size="13" />
                                </button>
                            </form>
                        </div>
                    </td>
                    @endcan
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="padding: 24px; text-align: center; color: #888;">Belum ada kategori sampah yang terdaftar.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Modal Tambah Komoditas -->
    @can('admin-only')
    <div class="overlay modal-overlay" x-show="showAddModal" style="display: none;" x-cloak>
        <div class="modal-card">
            <div class="modal-head">
                <h3>Tambah Komoditas Baru</h3>
                <button type="button" class="modal-close" @click="showAddModal = false">&times;</button>
            </div>
            <form action="{{ route('admin.categories.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label>Nama Komoditas Sampah</label>
                    <input type="text" name="name" class="calc-input" placeholder="Contoh: Kaleng Minuman Aluminium" required>
                </div>
                <div class="form-group" style="margin-top: 10px;">
                    <label>Satuan Ukur</label>
                    <select name="unit" class="calc-select" required>
                        <option value="kg">Kilogram (kg)</option>
                        <option value="liter">Liter (liter)</option>
                        <option value="unit">Unit / Satuan (unit)</option>
                    </select>
                </div>
                <div class="form-group" style="margin-top: 10px;">
                    <label>Harga Beli Per Satuan (Rp)</label>
                    <input type="number" name="price_per_unit" min="1" class="calc-input" placeholder="Contoh: 4500" required>
                </div>
                <div class="modal-actions" style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="secondary-button" @click="showAddModal = false">Batal</button>
                    <button type="submit" class="primary-button">Tambahkan ke Katalog</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Komoditas -->
    <div class="overlay modal-overlay" x-show="showEditModal" style="display: none;" x-cloak>
        <div class="modal-card">
            <div class="modal-head">
                <h3>Edit Komoditas</h3>
                <button type="button" class="modal-close" @click="showEditModal = false">&times;</button>
            </div>
            <form :action="`/admin/kategori/${editForm.id}`" method="POST">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label>Nama Komoditas</label>
                    <input type="text" name="name" class="calc-input" x-model="editForm.name" required>
                </div>
                <div class="form-group" style="margin-top: 10px;">
                    <label>Satuan Ukur</label>
                    <select name="unit" class="calc-select" x-model="editForm.unit" required>
                        <option value="kg">Kilogram (kg)</option>
                        <option value="liter">Liter (liter)</option>
                        <option value="unit">Unit / Satuan (unit)</option>
                    </select>
                </div>
                <div class="form-group" style="margin-top: 10px;">
                    <label>Harga Beli Per Satuan (Rp)</label>
                    <input type="number" name="price_per_unit" min="1" class="calc-input" x-model="editForm.price_per_unit" required>
                </div>
                <div class="modal-actions" style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="secondary-button" @click="showEditModal = false">Batal</button>
                    <button type="submit" class="primary-button">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
    @endcan
</div>
@endsection
