@extends('layouts.app')

@section('content')
<main class="terms-page">
    <section class="terms-hero">
        <div class="terms-hero-inner">
            <span class="terms-badge">Dokumen Operasional Resmi</span>
            <h1>Syarat &amp; Ketentuan</h1>
            <p>Ketentuan operasional, standar penimbangan, hak nasabah, dan transparansi pengelolaan Bank Sampah Warga RT 05 / RW 08.</p>
        </div>
    </section>

    <section class="terms-content">
        <div class="terms-grid">
            <article class="terms-card">
                <div class="terms-card-header">
                    <span class="terms-num">01</span>
                    <h2>Ketentuan Umum &amp; Keanggotaan</h2>
                </div>
                <p>Layanan Bank Sampah terbuka bagi seluruh warga di lingkungan RT 05 / RW 08 serta komunitas binaan yang telah terdaftar dalam basis data resmi.</p>
                <ul>
                    <li>Setiap kepala keluarga/rumah tangga berhak memiliki 1 nomor rekening nasabah berbasis nomor telepon seluler aktif.</li>
                    <li>Warga wajib menjaga kerahasiaan 4-digit PIN keamanan untuk mengakses portal saldo mandiri.</li>
                </ul>
            </article>

            <article class="terms-card">
                <div class="terms-card-header">
                    <span class="terms-num">02</span>
                    <h2>Kriteria &amp; Pemilahan Sampah</h2>
                </div>
                <p>Sampah yang dapat disetorkan harus memenuhi standar mutu dan kebersihan dasar:</p>
                <ul>
                    <li><strong>Plastik &amp; Botol:</strong> Bersih dari sisa cairan manis/minyak dan dikempeskan bila memungkinkan.</li>
                    <li><strong>Kardus &amp; Kertas:</strong> Kering, tidak terkena air, minyak, atau lumpur.</li>
                    <li><strong>Minyak Jelantah:</strong> Bebas endapan makanan berat dan dikemas dalam wadah tertutup rapat.</li>
                </ul>
            </article>

            <article class="terms-card">
                <div class="terms-card-header">
                    <span class="terms-num">03</span>
                    <h2>Standar Penimbangan &amp; Transparansi</h2>
                </div>
                <p>Penimbangan dilakukan secara terbuka di hadapan nasabah menggunakan timbangan yang telah dikalibrasi berkala oleh pengurus.</p>
                <ul>
                    <li>Hasil timbangan dicatat digital secara real-time ke dalam sistem buku kas RT.</li>
                    <li>Nasabah berhak menerima rincian bukti setoran digital via WhatsApp seketika setelah penimbangan selesai.</li>
                </ul>
            </article>

            <article class="terms-card">
                <div class="terms-card-header">
                    <span class="terms-num">04</span>
                    <h2>Penetapan Harga &amp; Saldo Tabungan</h2>
                </div>
                <p>Harga per unit mengacu pada katalog harga resmi yang berlaku pada saat transaksi dicatat.</p>
                <ul>
                    <li>Perubahan harga di masa mendatang tidak mempengaruhi nilai saldo dari transaksi yang telah berhasil dicatat.</li>
                    <li>Saldo tabungan tidak dikenakan biaya administrasi bulanan dan tidak memiliki masa kedaluwarsa selama program beroperasi.</li>
                </ul>
            </article>

            <article class="terms-card">
                <div class="terms-card-header">
                    <span class="terms-num">05</span>
                    <h2>Pencairan Dana (Tarik Tunai)</h2>
                </div>
                <p>Nasabah dapat mengajukan pencairan saldo tabungan menjadi uang tunai kepada bendahara bank sampah.</p>
                <ul>
                    <li>Pencairan dapat dilakukan pada jadwal operasional atau acara pencairan bersama menjelang hari raya / akhir tahun.</li>
                    <li>Nominal penarikan maksimal sama dengan saldo tercatat saat diverifikasi.</li>
                    <li>Nasabah mengonfirmasi nominal dan PIN melalui menu Profil untuk membuat kode persetujuan sekali pakai yang berlaku 10 menit.</li>
                    <li>Bendahara memverifikasi identitas penerima dan menyerahkan uang hanya sekali untuk setiap ID transaksi pencairan.</li>
                </ul>
            </article>

            <article class="terms-card">
                <div class="terms-card-header">
                    <span class="terms-num">06</span>
                    <h2>Perlindungan Data Pribadi (UU PDP)</h2>
                </div>
                <p>Pengurus berkomitmen melindungi data pribadi warga mencakup nama, nomor WhatsApp, nomor rumah, dan catatan transaksi.</p>
                <ul>
                    <li>Data warga digunakan semata-mata untuk keperluan operasional bank sampah dan pencatatan buku kas warga RT.</li>
                    <li>Data tidak diperjualbelikan atau dibagikan kepada pihak ketiga komersial.</li>
                </ul>
            </article>

            <article class="terms-card">
                <div class="terms-card-header">
                    <span class="terms-num">07</span>
                    <h2>Audit &amp; Transparansi Kas</h2>
                </div>
                <p>Setoran dan pencairan baru disimpan bersama perubahan saldo dan log audit digital agar dapat ditelusuri. Jika pencatatan audit gagal, perubahan saldo dibatalkan.</p>
                <ul>
                    <li>Pengurus menyajikan laporan rekapitulasi secara transparan dalam musyawarah warga RT secara berkala.</li>
                </ul>
            </article>

            <article class="terms-card">
                <div class="terms-card-header">
                    <span class="terms-num">08</span>
                    <h2>Penyelesaian Kendala &amp; Perubahan</h2>
                </div>
                <p>Apabila terdapat selisih penimbangan atau kendala akses akun, nasabah dapat menghubungi petugas piket atau Ketua RT setempat untuk penelusuran buku besar.</p>
            </article>
        </div>
    </section>
</main>
@endsection
