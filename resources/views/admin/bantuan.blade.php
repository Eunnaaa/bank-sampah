@extends('layouts.admin')

@section('admin_content')
<div class="help-page">
    <div class="page-header">
        <div>
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #15803d; background: #dcfce7; padding: 2px 8px; border-radius: 6px;">
                    PUSAT BANTUAN &amp; SOP · RT {{ auth()->user()->rt ?? '05' }} / RW {{ auth()->user()->rw ?? '08' }}
                </span>
            </div>
            <h1>SOP &amp; Panduan Operasional Petugas</h1>
            <p>Pedoman standar operasional bank sampah, tata cara penimbangan, dan referensi kepatuhan RT/RW.</p>
        </div>
    </div>

    <div class="terms-grid" style="margin-top: 24px;">
        <article class="terms-card">
            <div class="terms-card-header">
                <span class="terms-num">01</span>
                <h2>SOP Penimbangan Sampah Lapangan</h2>
            </div>
            <p>Langkah baku yang harus dipatuhi oleh setiap petugas pencatat saat pos bank sampah beroperasi:</p>
            <ul>
                <li>Pastikan jarum atau layar timbangan digital berada tepat di angka 0.00 sebelum meletakkan karung/wadah.</li>
                <li>Verifikasi identitas warga nasabah melalui nomor rumah atau WhatsApp di portal petugas.</li>
                <li>Pilih jenis komoditas yang tepat. Bila terdapat kontaminasi kotoran/basah berlebih, minta warga menyortirnya terlebih dahulu.</li>
                <li>Setelah transaksi tersimpan, klik <strong>Kirim Struk WhatsApp</strong> untuk menyampaikan bukti digital langsung ke ponsel warga.</li>
            </ul>
        </article>

        <article class="terms-card">
            <div class="terms-card-header">
                <span class="terms-num">02</span>
                <h2>SOP Pencairan Saldo (Tarik Tunai)</h2>
            </div>
            <p>Hanya petugas berwenang (Administrator/Bendahara) yang berhak mengeksekusi pencairan saldo tunai:</p>
            <ul>
                <li>Pastikan saldo nasabah mencukupi dan verifikasi identitas pemegang rekening.</li>
                <li>Warga membuka menu Profil, memilih nominal penarikan, dan mengonfirmasi PIN untuk membuat kode persetujuan yang berlaku 10 menit.</li>
                <li>Masukkan kode warga dan konfirmasi kata sandi admin pada formulir tarik tunai.</li>
                <li>Cocokkan bukti pencairan dan serahkan uang tunai sekali untuk setiap ID transaksi. Jika muncul pesan sudah diproses, jangan serahkan uang lagi.</li>
                <li>Setiap transaksi penarikan akan dicatat dalam Log Audit resmi dan mengurangi liabilitas kas secara real-time.</li>
            </ul>
        </article>

        <article class="terms-card">
            <div class="terms-card-header">
                <span class="terms-num">03</span>
                <h2>Referensi Kebijakan &amp; Kepatuhan</h2>
            </div>
            <p>Seluruh aturan main operasional, hak perlindungan data warga sesuai UU PDP, dan pembagian hasil mengacu pada dokumen resmi:</p>
            <div style="margin-top: 14px;">
                <a href="{{ route('terms') }}" target="_blank" class="btn-outline-small" style="display: inline-flex; align-items: center; gap: 6px;">
                    <x-icon name="file-text" size="14" /> Buka Dokumen Syarat &amp; Ketentuan Resmi <span>↗</span>
                </a>
            </div>
        </article>
    </div>
</div>
@endsection
