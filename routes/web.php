<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PaymentProviderController;
use App\Http\Controllers\PortalManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('home');
Route::get('/pengumuman', [DashboardController::class, 'announcements'])->name('announcements');
Route::get('/struktur-organisasi', [PortalManagementController::class, 'publicOrganization'])->name('organization.public');
Route::get('/hasil-pemilihan/{election}', [PortalManagementController::class, 'publicElectionResults'])->name('elections.public-results');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::get('/daftar', [AuthController::class, 'showRegister'])->name('register');
Route::post('/daftar', [AuthController::class, 'register'])->name('register.store');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
Route::middleware('auth')->group(function () {
    Route::get('/layanan/ajukan', [PortalManagementController::class, 'createServiceRequest'])->name('service-requests.create');
    Route::post('/layanan/ajukan', [PortalManagementController::class, 'storeServiceRequest'])->name('service-requests.store');
    Route::get('/layanan/saya', [PortalManagementController::class, 'myServiceRequests'])->name('service-requests.mine');
    Route::get('/tagihan', [PortalManagementController::class, 'myInvoices'])->name('invoices.mine');
    Route::post('/tagihan/{invoice}/bayar', [PortalManagementController::class, 'startInvoicePayment'])->name('invoices.pay');
    Route::get('/tagihan/pembayaran/{paymentTransaction}', [PortalManagementController::class, 'paymentStatus'])->name('payments.show');
    Route::get('/keanggotaan/data', [PortalManagementController::class, 'editResidentProfile'])->name('profile.edit');
    Route::patch('/keanggotaan/data', [PortalManagementController::class, 'updateResidentProfile'])->name('profile.update');
    Route::get('/kegiatan', [PortalManagementController::class, 'activities'])->name('activities.index');
    Route::post('/kegiatan/{activity}/daftar', [PortalManagementController::class, 'registerForActivity'])->name('activities.register');
    Route::get('/fasilitas', [PortalManagementController::class, 'facilities'])->name('facilities.index');
    Route::post('/fasilitas/{facility}/pemesanan', [PortalManagementController::class, 'bookFacility'])->name('facilities.book');
    Route::get('/keamanan/cctv', [PortalManagementController::class, 'cctv'])->name('cctv.index');
    Route::post('/keamanan/cctv', [PortalManagementController::class, 'requestCctvAccess'])->name('cctv.requests.store');
    Route::get('/keamanan/cctv/{camera}/stream', [PortalManagementController::class, 'viewCctvStream'])->name('cctv.stream');
    Route::get('/pemilihan', [PortalManagementController::class, 'election'])->name('elections.show');
    Route::post('/pemilihan/{election}/consent', [PortalManagementController::class, 'consentToElection'])->name('elections.consent');
    Route::post('/pemilihan/{election}/pilih', [PortalManagementController::class, 'castVote'])->name('elections.vote');
});

Route::middleware(['auth', 'role:superadmin,admin_rt'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'admin'])->name('dashboard');
    Route::get('/warga', [PortalManagementController::class, 'residents'])->name('residents.index');
    Route::get('/warga/ekspor', [PortalManagementController::class, 'exportResidents'])->name('residents.export');
    Route::patch('/warga/{user}/persetujuan', [PortalManagementController::class, 'reviewResident'])->name('residents.review');
    Route::get('/pengajuan', [PortalManagementController::class, 'serviceRequests'])->name('service-requests.index');
    Route::patch('/pengajuan/{serviceRequest}', [PortalManagementController::class, 'reviewServiceRequest'])->name('service-requests.review');
    Route::get('/operasional', [PortalManagementController::class, 'operations'])->name('operations.index');
    Route::post('/pengumuman', [PortalManagementController::class, 'storeAnnouncement'])->name('announcements.store');
    Route::get('/cctv/permintaan', [PortalManagementController::class, 'cctvRequests'])->name('cctv-requests.index');
    Route::patch('/cctv/permintaan/{accessRequest}', [PortalManagementController::class, 'reviewCctvRequest'])->name('cctv-requests.review');
    Route::post('/cctv/kamera', [PortalManagementController::class, 'storeCamera'])->name('cameras.store');
    Route::post('/fasilitas', [PortalManagementController::class, 'storeFacility'])->name('facilities.store');
    Route::patch('/fasilitas/pemesanan/{booking}', [PortalManagementController::class, 'reviewFacilityBooking'])->name('facilities.bookings.review');
    Route::get('/pemilihan', [PortalManagementController::class, 'adminElection'])->name('elections.index');
});

Route::middleware(['auth', 'role:superadmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/payment-gateways', [PaymentProviderController::class, 'index'])->name('payment-providers.index');
    Route::post('/payment-gateways', [PaymentProviderController::class, 'store'])->name('payment-providers.store');
    Route::patch('/payment-gateways/{paymentProvider}', [PaymentProviderController::class, 'update'])->name('payment-providers.update');
    Route::delete('/payment-gateways/{paymentProvider}', [PaymentProviderController::class, 'destroy'])->name('payment-providers.destroy');
});

Route::middleware(['auth', 'role:superadmin,admin_rt,bendahara'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/keuangan', [PortalManagementController::class, 'ledger'])->name('ledger.index');
    Route::post('/keuangan', [PortalManagementController::class, 'storeTransaction'])->name('ledger.store');
    Route::post('/iuran/tagihan', [PortalManagementController::class, 'createInvoices'])->name('invoices.store');
    Route::post('/keuangan/{ledgerTransaction}/persetujuan', [PortalManagementController::class, 'approveTransaction'])->name('ledger.approve');
    Route::post('/keuangan/{ledgerTransaction}/koreksi', [PortalManagementController::class, 'reverseTransaction'])->name('ledger.reverse');
});

Route::middleware(['auth', 'role:superadmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/pengaturan', [PortalManagementController::class, 'settings'])->name('settings.edit');
    Route::patch('/pengaturan', [PortalManagementController::class, 'updateSettings'])->name('settings.update');
    Route::get('/aktivitas', [PortalManagementController::class, 'activityLogs'])->name('activity-logs.index');
});

Route::middleware(['auth', 'role:superadmin,panitia_pemilihan'])->prefix('admin')->name('admin.')->group(function () {
    Route::post('/pemilihan', [PortalManagementController::class, 'createElection'])->name('elections.store');
    Route::post('/pemilihan/{election}/calon', [PortalManagementController::class, 'storeCandidate'])->name('elections.candidates.store');
    Route::post('/pemilihan/{election}/pemilih/{user}', [PortalManagementController::class, 'verifyVoter'])->name('elections.voters.verify');
    Route::get('/pemilihan/{election}/hasil', [PortalManagementController::class, 'electionResults'])->name('elections.results');
    Route::patch('/pemilihan/{election}', [PortalManagementController::class, 'updateElection'])->name('elections.update');
});

Route::middleware(['auth', 'role:superadmin,admin_rt,pengurus_dkm,pengurus_karang_taruna'])->prefix('admin')->name('admin.')->group(function () {
    Route::post('/kegiatan', [PortalManagementController::class, 'storeActivity'])->name('activities.store');
});

Route::middleware(['auth', 'role:superadmin,admin_rt,bendahara'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/keuangan/ekspor', [PortalManagementController::class, 'exportLedger'])->name('ledger.export');
});

Route::middleware(['auth', 'role:superadmin,admin_rt,pengurus_dkm,pengurus_karang_taruna'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/struktur-organisasi', [PortalManagementController::class, 'adminOrganization'])->name('organization.index');
    Route::post('/struktur-organisasi', [PortalManagementController::class, 'storeOrganizationMember'])->name('organization.store');
    Route::patch('/struktur-organisasi/{organizationMember}', [PortalManagementController::class, 'updateOrganizationMember'])->name('organization.update');
    Route::delete('/struktur-organisasi/{organizationMember}', [PortalManagementController::class, 'destroyOrganizationMember'])->name('organization.destroy');
});

Route::post('/webhooks/payments/{paymentProvider}', [PortalManagementController::class, 'paymentWebhook'])->name('payments.webhook');
