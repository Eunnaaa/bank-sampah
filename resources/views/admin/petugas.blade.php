@extends('layouts.admin', ['title' => 'Manajemen Petugas RT — Si-BankSampah'])

@section('admin_content')
<div x-data="{ showAddModal: false, showResetModal: false, selectedOfficer: null }">
    <!-- Header Halaman -->
    <header class="admin-page-heading">
        <div>
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                <span class="role-badge" style="background: #fef3c7; color: #92400e; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 6px;">
                    OTORITAS PENGURUS RT
                </span>
            </div>
            <h1>Manajemen Petugas Lapangan</h1>
            <p>Kelola akun petugas timbang bank sampah. Akun petugas hanya dapat dibuat oleh Pengurus RT.</p>
        </div>
        <div style="display: flex; align-items: center; gap: 10px;">
            <button @click="showAddModal = true" class="primary-button" style="display: inline-flex; align-items: center; gap: 8px;">
                <x-icon name="plus" size="16" />
                <span>+ Tambah Petugas Baru</span>
            </button>
        </div>
    </header>

    @if(session('success'))
    <div class="alert alert-success" style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px; padding: 12px 16px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; color: #166534;">
        <x-icon name="check" size="18" />
        <span>{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger" style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px; padding: 12px 16px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; color: #991b1b;">
        <x-icon name="alert-triangle" size="18" />
        <span>{{ session('error') }}</span>
    </div>
    @endif

    <!-- Banner Kebijakan Hak Akses -->
    <div style="margin-bottom: 24px; padding: 16px 20px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; display: flex; align-items: flex-start; gap: 14px;">
        <div style="display: grid; place-items: center; width: 36px; height: 36px; border-radius: 10px; background: #e0f2fe; color: #0284c7; flex-shrink: 0;">
            <x-icon name="shield" size="20" />
        </div>
        <div style="font-size: 13px; color: #475569; line-height: 1.55;">
            <strong style="color: #0f172a; font-size: 14px; display: block; margin-bottom: 2px;">Aturan Sistem Autentikasi Bank Sampah:</strong>
            Akun <strong>Petugas Lapangan</strong> hanya dapat dibuat dan dikelola oleh akun Pengurus RT (Admin). Warga hanya dapat mendaftar sebagai Nasabah secara mandiri, dan akun Pengurus RT merupakan akun induk sistem yang tidak dapat didaftarkan dari luar.
        </div>
    </div>

    <!-- Tabel Daftar Petugas -->
    <div class="table-container" style="background: white; border: 1px solid #e2e8e2; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 14px rgba(23,63,53,0.03);">
        <div style="padding: 18px 20px; border-bottom: 1px solid #edf2ed; display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h3 style="margin: 0; font: 800 16px 'Manrope', sans-serif; color: #173f35;">Daftar Petugas Aktif</h3>
                <small style="color: #64748b; font-size: 12.5px;">Petugas yang memiliki hak akses mencatat setoran timbangan dan melihat nasabah</small>
            </div>
            <span style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; background: #eaf5eb; color: #173f35; border-radius: 20px; font-size: 12px; font-weight: 700;">
                <x-icon name="user" size="14" />
                <span>{{ $officers->count() }} Petugas Terdaftar</span>
            </span>
        </div>

        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
            <thead>
                <tr style="background: #f8faf8; color: #4b6357; font-size: 12px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; border-bottom: 1px solid #e5ebe5;">
                    <th style="padding: 14px 20px;">Petugas</th>
                    <th style="padding: 14px 16px;">WhatsApp / HP</th>
                    <th style="padding: 14px 16px;">Alamat / Blok</th>
                    <th style="padding: 14px 16px;">Total Timbang</th>
                    <th style="padding: 14px 16px;">Terdaftar Sejak</th>
                    <th style="padding: 14px 20px; text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($officers as $officer)
                <tr style="border-bottom: 1px solid #f0f4f0; transition: background .15s ease;">
                    <td style="padding: 14px 20px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="display: grid; place-items: center; width: 34px; height: 34px; border-radius: 10px; background: #eaf5eb; color: #173f35; font-weight: 800; font-size: 13px;">
                                {{ strtoupper(substr($officer->full_name, 0, 1)) }}
                            </div>
                            <div>
                                <strong style="color: #173f35; font-size: 14px;">{{ $officer->full_name }}</strong>
                                <span style="display: block; font-size: 11.5px; color: #64748b;">Role: Petugas Lapangan</span>
                            </div>
                        </div>
                    </td>
                    <td style="padding: 14px 16px; font-family: monospace; font-size: 13px; color: #2d4239;">
                        {{ $officer->phone_number }}
                    </td>
                    <td style="padding: 14px 16px; color: #52675b;">
                        {{ $officer->house_number }} (RT {{ $officer->rt }}/RW {{ $officer->rw }})
                    </td>
                    <td style="padding: 14px 16px;">
                        <span style="font-weight: 700; color: #173f35;">
                            {{ $officer->officerTransactions()->count() }} transaksi
                        </span>
                    </td>
                    <td style="padding: 14px 16px; color: #64748b; font-size: 13px;">
                        {{ $officer->created_at->timezone(config('app.business_timezone'))->translatedFormat('d M Y') }}
                    </td>
                    <td style="padding: 14px 20px; text-align: right;">
                        <div style="display: inline-flex; align-items: center; gap: 8px;">
                            <button
                                @click="selectedOfficer = { id: '{{ $officer->id }}', name: '{{ $officer->full_name }}' }; showResetModal = true"
                                class="secondary-button"
                                style="padding: 6px 10px; font-size: 12px; display: inline-flex; align-items: center; gap: 4px;"
                                title="Reset Kata Sandi"
                            >
                                <x-icon name="lock" size="14" />
                                <span>Ganti Sandi</span>
                            </button>

                            @if(!$officer->officerTransactions()->exists())
                            <form action="{{ route('admin.officers.destroy', $officer->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun petugas {{ $officer->full_name }}?');" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="background: none; border: 1px solid #fecaca; color: #dc2626; padding: 6px 8px; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center;" title="Hapus Akun">
                                    <x-icon name="trash" size="14" />
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="padding: 40px; text-align: center; color: #64748b;">
                        Belum ada akun petugas lapangan yang didaftarkan oleh Pengurus RT.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Modal Tambah Petugas Baru -->
    <div x-show="showAddModal" style="position: fixed; inset: 0; z-index: 100; background: rgba(16, 51, 42, 0.6); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; padding: 20px;" x-cloak>
        <div @click.away="showAddModal = false" style="background: white; border-radius: 20px; width: 100%; max-width: 480px; padding: 28px; box-shadow: 0 20px 40px rgba(0,0,0,0.2);">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="display: grid; place-items: center; width: 36px; height: 36px; border-radius: 10px; background: #eaf5eb; color: #173f35;">
                        <x-icon name="user" size="18" />
                    </span>
                    <h3 style="margin: 0; font: 800 18px 'Manrope', sans-serif; color: #173f35;">Tambah Petugas Lapangan</h3>
                </div>
                <button @click="showAddModal = false" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b;">
                    <x-icon name="close" size="18" />
                </button>
            </div>

            <p style="margin: 0 0 16px; color: #64748b; font-size: 13px; line-height: 1.5;">
                Akun ini akan memiliki hak akses petugas lapangan (mencatat setoran & menimbang sampah nasabah).
            </p>

            <form action="{{ route('admin.officers.store') }}" method="POST" style="display: grid; gap: 14px;">
                @csrf
                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #2d4239; margin-bottom: 4px;">Nama Lengkap Petugas</label>
                    <input type="text" name="full_name" placeholder="Contoh: Bpk. Joko Susilo" required style="width: 100%; height: 42px; padding: 0 12px; border: 1.5px solid #d4dfd8; border-radius: 8px; font-size: 13.5px;">
                </div>

                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #2d4239; margin-bottom: 4px;">Nomor WhatsApp / HP</label>
                    <input type="tel" name="phone_number" placeholder="Contoh: 081298765432" required style="width: 100%; height: 42px; padding: 0 12px; border: 1.5px solid #d4dfd8; border-radius: 8px; font-size: 13.5px;">
                </div>

                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #2d4239; margin-bottom: 4px;">Nomor Rumah / Blok</label>
                    <input type="text" name="house_number" placeholder="Contoh: Blok A No. 03" required style="width: 100%; height: 42px; padding: 0 12px; border: 1.5px solid #d4dfd8; border-radius: 8px; font-size: 13.5px;">
                </div>

                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #2d4239; margin-bottom: 4px;">Kata Sandi Awal (Min. 6 Karakter)</label>
                    <input type="password" name="password" placeholder="Minimal 6 karakter..." minlength="6" required style="width: 100%; height: 42px; padding: 0 12px; border: 1.5px solid #d4dfd8; border-radius: 8px; font-size: 13.5px;">
                </div>

                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 10px; margin-top: 10px;">
                    <button type="button" @click="showAddModal = false" class="secondary-button" style="padding: 8px 16px;">Batal</button>
                    <button type="submit" class="primary-button" style="padding: 8px 18px;">Simpan Petugas</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Reset Password Petugas -->
    <div x-show="showResetModal" style="position: fixed; inset: 0; z-index: 100; background: rgba(16, 51, 42, 0.6); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; padding: 20px;" x-cloak>
        <div @click.away="showResetModal = false" style="background: white; border-radius: 20px; width: 100%; max-width: 440px; padding: 26px; box-shadow: 0 20px 40px rgba(0,0,0,0.2);">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="display: grid; place-items: center; width: 34px; height: 34px; border-radius: 10px; background: #fef3c7; color: #92400e;">
                        <x-icon name="lock" size="18" />
                    </span>
                    <h3 style="margin: 0; font: 800 17px 'Manrope', sans-serif; color: #173f35;">Ganti Kata Sandi</h3>
                </div>
                <button @click="showResetModal = false" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b;">
                    <x-icon name="close" size="18" />
                </button>
            </div>

            <p style="margin: 0 0 14px; color: #64748b; font-size: 13px;">
                Perbarui kata sandi untuk petugas <strong x-text="selectedOfficer?.name" style="color: #173f35;"></strong>.
            </p>

            <form :action="'{{ url('/admin/petugas') }}/' + (selectedOfficer?.id ?? '') + '/reset-password'" method="POST" style="display: grid; gap: 14px;">
                @csrf
                <div>
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #2d4239; margin-bottom: 4px;">Kata Sandi Baru (Min. 6 Karakter)</label>
                    <input type="password" name="new_password" placeholder="Masukkan sandi baru..." minlength="6" required style="width: 100%; height: 42px; padding: 0 12px; border: 1.5px solid #d4dfd8; border-radius: 8px; font-size: 13.5px;">
                </div>

                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 10px; margin-top: 6px;">
                    <button type="button" @click="showResetModal = false" class="secondary-button" style="padding: 8px 16px;">Batal</button>
                    <button type="submit" class="primary-button" style="padding: 8px 18px;">Simpan Sandi Baru</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
