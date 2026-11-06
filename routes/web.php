<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\Api\ResidentApiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BalanceController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\OfficerController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ResidentAuthController;
use App\Http\Controllers\ResidentController;
use App\Http\Controllers\ResidentDashboardController;
use App\Http\Controllers\WeighingController;
use Illuminate\Support\Facades\Route;

// Rute Publik (Identik dengan Web App TanStack Start)
Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/warga', [PublicController::class, 'warga'])->name('warga');
Route::get('/cek-saldo', [BalanceController::class, 'index'])->name('balance.index');
Route::post('/cek-saldo', [BalanceController::class, 'search'])->name('balance.search')->middleware('throttle:credentials');
Route::post('/cek-saldo/ubah-pin', [BalanceController::class, 'changePin'])->name('balance.change-pin')->middleware('throttle:credentials');
Route::get('/syarat-ketentuan', [PublicController::class, 'terms'])->name('terms');

// ==============================================================================
// AUTENTIKASI UMUM TERPADU (1 Halaman Login untuk RT, Petugas, dan Warga)
// ==============================================================================
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post')->middleware('throttle:credentials');
Route::get('/masuk', [AuthController::class, 'showLogin']);
Route::post('/masuk', [AuthController::class, 'login'])->middleware('throttle:credentials');

// Alias Rute Lama Petugas (kompatibel penuh dengan link & test eksisting)
Route::get('/petugas', [AuthController::class, 'showLogin']);
Route::post('/petugas', [AuthController::class, 'login'])->middleware('throttle:credentials');

// Pendaftaran Mandiri (HANYA untuk Nasabah Warga)
Route::get('/daftar', [AuthController::class, 'showRegister'])->name('register');
Route::post('/daftar', [AuthController::class, 'register'])->name('register.post')->middleware('throttle:credentials');
Route::get('/register', [AuthController::class, 'showRegister']);
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:credentials');

// Aktivasi & Logout Global
Route::post('/aktivasi-pin', [AuthController::class, 'activatePin'])->name('activate-pin')->middleware('throttle:credentials');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ==============================================================================
// AREA KHUSUS WARGA (Portal Warga)
// ==============================================================================
Route::prefix('warga')->name('warga.')->group(function () {
    // Kompatibilitas rute login warga
    Route::get('/masuk', [ResidentAuthController::class, 'showLogin'])->name('login');
    Route::post('/masuk', [ResidentAuthController::class, 'login'])->name('login.post')->middleware('throttle:credentials');
    Route::post('/aktivasi', [ResidentAuthController::class, 'activatePin'])->name('activate-pin')->middleware('throttle:credentials');
    Route::post('/keluar', [ResidentAuthController::class, 'logout'])->name('logout');

    // Area Khusus Warga Login (Resident Protected)
    Route::middleware(['resident.auth'])->group(function () {
        Route::get('/dashboard', [ResidentDashboardController::class, 'dashboard'])->name('dashboard');
        Route::get('/riwayat', [ResidentDashboardController::class, 'riwayat'])->name('riwayat');
        Route::get('/katalog', [ResidentDashboardController::class, 'katalog'])->name('katalog');
        Route::get('/profil', [ResidentDashboardController::class, 'profil'])->name('profil');
        Route::post('/profil/ganti-pin', [ResidentDashboardController::class, 'changePin'])->name('profil.change-pin')->middleware('throttle:credentials');
        Route::post('/profil/persetujuan-penarikan', [ResidentDashboardController::class, 'authorizeWithdrawal'])->name('withdrawals.authorize')->middleware('throttle:credentials');
        Route::get('/transaksi/{id}', [ResidentDashboardController::class, 'showTransaction'])->name('transactions.show');
    });
});

// API Endpoints Nasabah Warga
Route::prefix('api/warga')->name('api.warga.')->middleware('resident.auth')->group(function () {
    Route::get('/ringkasan', [ResidentApiController::class, 'summary'])->name('summary');
    Route::get('/riwayat', [ResidentApiController::class, 'transactions'])->name('transactions');
    Route::get('/katalog', [ResidentApiController::class, 'categories'])->name('categories');
    Route::get('/qrcode', [ResidentApiController::class, 'qrcode'])->name('qrcode');
});

// ==============================================================================
// AREA KHUSUS PENGURUS (Admin RT & Petugas Lapangan)
// ==============================================================================
Route::middleware(['auth', 'staff.auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/timbang', [WeighingController::class, 'index'])->name('weighing.index');
    Route::post('/timbang', [WeighingController::class, 'store'])->name('weighing.store');

    Route::get('/warga', [ResidentController::class, 'index'])->name('residents.index');
    Route::post('/warga', [ResidentController::class, 'store'])->name('residents.store');
    Route::get('/warga/{id}/riwayat', [ResidentController::class, 'history'])->name('residents.history');
    Route::get('/transaksi/{id}', [WeighingController::class, 'show'])->name('transactions.show');

    // Rute Khusus Administrator (Pengurus RT)
    Route::middleware(['can:admin-only'])->group(function () {
        // Manajemen Petugas (HANYA Pengurus RT yang bisa membuat akun petugas)
        Route::get('/petugas', [OfficerController::class, 'index'])->name('officers.index');
        Route::post('/petugas', [OfficerController::class, 'store'])->name('officers.store');
        Route::post('/petugas/{id}/reset-password', [OfficerController::class, 'resetPassword'])->name('officers.reset-password');
        Route::delete('/petugas/{id}', [OfficerController::class, 'destroy'])->name('officers.destroy');

        Route::put('/warga/{id}', [ResidentController::class, 'update'])->name('residents.update');
        Route::post('/warga/{id}/undangan', [ResidentController::class, 'invite'])->name('residents.invite');
        Route::post('/warga/{id}/reset-pin', [ResidentController::class, 'resetPin'])->name('residents.reset-pin');
        Route::post('/warga/tarik', [ResidentController::class, 'withdraw'])->name('residents.withdraw')->middleware('throttle:withdrawals');
        Route::post('/kategori', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/kategori/{id}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/kategori/{id}', [CategoryController::class, 'destroy'])->name('categories.destroy');
        Route::put('/kategori/{id}/harga', [CategoryController::class, 'updatePrice'])->name('categories.update-price');
        Route::put('/kategori/{id}/toggle', [CategoryController::class, 'toggleStatus'])->name('categories.toggle');
    });

    Route::get('/kategori', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/laporan/export-csv', [ReportController::class, 'exportCsv'])->name('reports.export-csv');
    Route::get('/laporan/export-ledger', [ReportController::class, 'exportLedger'])->name('reports.export-ledger');
    Route::get('/audit-log', [ReportController::class, 'auditLog'])->name('audit-log.index');
    Route::get('/bantuan', [HelpController::class, 'index'])->name('help.index');
});
