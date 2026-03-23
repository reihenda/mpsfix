<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataPencatatanController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FobController;
use App\Http\Controllers\RekapPengambilanController;
use App\Http\Controllers\NomorPolisiController;
use App\Http\Controllers\UkuranController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\KeuanganController;
use App\Http\Controllers\KasController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\FinancialAccountController;
use App\Http\Controllers\TransactionDescriptionController;
use App\Http\Controllers\ExcelImportController;
use App\Http\Controllers\Debug\DataSyncDebugController;
use App\Http\Controllers\ProformaInvoiceController;
use App\Http\Controllers\Rekap\RekapPembelianController;
use App\Http\Controllers\DataSyncController;

// Rute Autentikasi
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Rute untuk SuperAdmin
Route::middleware(['auth', 'role:superadmin'])->group(function () {
    Route::get('/superadmin/dashboard', [DashboardController::class, 'superadminDashboard'])
        ->name('superadmin.dashboard');
});

// ============================================================================
// Rute untuk Admin, SuperAdmin, Keuangan, Customer, dan FOB (READ ACCESS)
// Customer dan FOB hanya bisa akses show routes
// Authorization check ada di controller untuk memastikan customer hanya bisa lihat data mereka sendiri
// ============================================================================
Route::middleware(['auth', 'role:admin,superadmin,keuangan,customer,fob,mmbtu'])->group(function () {
    // Invoice & Billing Show Routes - Bisa diakses oleh semua role
    // Authorization check di controller memastikan customer hanya bisa lihat milik mereka sendiri
    // PENTING: Menggunakan where constraint agar hanya menerima ID numerik, tidak menangkap route statis seperti 'select-customer'
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show')->where('invoice', '[0-9]+');
    Route::get('/billings/{billing}', [BillingController::class, 'show'])->name('billings.show')->where('billing', '[0-9]+');
});

// ============================================================================
// Rute untuk Admin, SuperAdmin, Keuangan, dan Staff (READ ACCESS Data Pencatatan)
// Staff hanya bisa melihat data pencatatan customer, tidak bisa edit
// ============================================================================
Route::middleware(['auth', 'role:admin,superadmin,keuangan,staff'])->group(function () {
    // Data Pencatatan - View Only untuk Keuangan dan Staff
    Route::get('/data-pencatatan', [DataPencatatanController::class, 'index'])
        ->name('data-pencatatan.index');
    Route::get('/data-pencatatan/{dataPencatatan}', [DataPencatatanController::class, 'show'])
        ->name('data-pencatatan.show');
    Route::get('/data-pencatatan/customer/{customer}', [DataPencatatanController::class, 'customerDetail'])
        ->name('data-pencatatan.customer-detail');
    Route::get('/data-pencatatan/customer/{customer}/details', [DataPencatatanController::class, 'getCustomerDetails'])
        ->name('data-pencatatan.customer-details');
    Route::post('/data-pencatatan/{customer}/filter', [DataPencatatanController::class, 'filterByDateRange'])
        ->name('data-pencatatan.filter');
    Route::post('/data-pencatatan/{customer}/filter-month-year', [DataPencatatanController::class, 'filterByMonthYear'])
        ->name('data-pencatatan.filter-month-year');
});

// ============================================================================
// Rute untuk Admin, SuperAdmin, dan Keuangan (READ ACCESS - tanpa Staff)
// ============================================================================
Route::middleware(['auth', 'role:admin,superadmin,keuangan'])->group(function () {

    // Dashboard untuk Keuangan
    Route::get('/keuangan', [KeuanganController::class, 'index'])->name('keuangan.index');

    // Operator GTM - View Only untuk Keuangan
    // Index untuk melihat daftar operator
    Route::get('/operator-gtm', [App\Http\Controllers\OperatorGtmController::class, 'index'])->name('operator-gtm.index');
    // Show untuk melihat detail operator dan data lembur (READ ONLY untuk Keuangan)
    Route::get('/operator-gtm/{operatorGtm}', [App\Http\Controllers\OperatorGtmController::class, 'show'])->name('operator-gtm.show');

    // Keuangan Routes - Full Access untuk Keuangan
    Route::get('/keuangan/accounts', [FinancialAccountController::class, 'index'])->name('keuangan.accounts.index');
    Route::get('/keuangan/accounts/create', [FinancialAccountController::class, 'create'])->name('keuangan.accounts.create');
    Route::post('/keuangan/accounts', [FinancialAccountController::class, 'store'])->name('keuangan.accounts.store');
    Route::get('/keuangan/accounts/{account}/edit', [FinancialAccountController::class, 'edit'])->name('keuangan.accounts.edit');
    Route::put('/keuangan/accounts/{account}', [FinancialAccountController::class, 'update'])->name('keuangan.accounts.update');
    Route::delete('/keuangan/accounts/{account}', [FinancialAccountController::class, 'destroy'])->name('keuangan.accounts.destroy');

    Route::get('/keuangan/descriptions', [TransactionDescriptionController::class, 'index'])->name('keuangan.descriptions.index');
    Route::get('/keuangan/descriptions/create', [TransactionDescriptionController::class, 'create'])->name('keuangan.descriptions.create');
    Route::post('/keuangan/descriptions', [TransactionDescriptionController::class, 'store'])->name('keuangan.descriptions.store');
    Route::get('/keuangan/descriptions/{description}/edit', [TransactionDescriptionController::class, 'edit'])->name('keuangan.descriptions.edit');
    Route::put('/keuangan/descriptions/{description}', [TransactionDescriptionController::class, 'update'])->name('keuangan.descriptions.update');
    Route::delete('/keuangan/descriptions/{description}', [TransactionDescriptionController::class, 'destroy'])->name('keuangan.descriptions.destroy');
    Route::get('/api/descriptions/category', [TransactionDescriptionController::class, 'getByCategory'])->name('keuangan.descriptions.by-category');

    Route::get('/keuangan/kas', [KasController::class, 'index'])->name('keuangan.kas.index');
    Route::get('/keuangan/kas/create', [KasController::class, 'create'])->name('keuangan.kas.create');
    Route::post('/keuangan/kas', [KasController::class, 'store'])->name('keuangan.kas.store');
    Route::get('/keuangan/kas/{transaction}/edit', [KasController::class, 'edit'])->name('keuangan.kas.edit');
    Route::put('/keuangan/kas/{transaction}', [KasController::class, 'update'])->name('keuangan.kas.update');
    Route::delete('/keuangan/kas/{transaction}', [KasController::class, 'destroy'])->name('keuangan.kas.destroy');
    Route::get('/keuangan/kas/recalculate-all', [KasController::class, 'recalculateAllBalances'])->name('keuangan.kas.recalculate-all');

    Route::get('/keuangan/kas/download-template', [App\Http\Controllers\KasExcelController::class, 'downloadTemplate'])->name('keuangan.kas.download-template');
    Route::post('/keuangan/kas/upload-excel', [App\Http\Controllers\KasExcelController::class, 'uploadExcel'])->name('keuangan.kas.upload-excel');

    Route::get('/keuangan/bank', [BankController::class, 'index'])->name('keuangan.bank.index');
    Route::get('/keuangan/bank/create', [BankController::class, 'create'])->name('keuangan.bank.create');
    Route::post('/keuangan/bank', [BankController::class, 'store'])->name('keuangan.bank.store');
    Route::get('/keuangan/bank/{transaction}/edit', [BankController::class, 'edit'])->name('keuangan.bank.edit');
    Route::put('/keuangan/bank/{transaction}', [BankController::class, 'update'])->name('keuangan.bank.update');
    Route::delete('/keuangan/bank/{transaction}', [BankController::class, 'destroy'])->name('keuangan.bank.destroy');

    // Invoice & Billing Routes - Full Access untuk Keuangan, READ-ONLY untuk Customer/FOB
    // PENTING: Route STATIS harus SEBELUM route dengan PARAMETER DINAMIS
    
    // Invoice Routes - URUTAN PENTING!
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/select-customer', [InvoiceController::class, 'selectCustomer'])->name('invoices.select-customer');
    Route::get('/invoices/create/{customer}', [InvoiceController::class, 'create'])->name('invoices.create');
    Route::post('/invoices/{customer}', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::post('/invoices/{customer}/generate-number', [InvoiceController::class, 'generateInvoiceNumber'])->name('invoices.generate-number');
    Route::get('/invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
    Route::put('/invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
    Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
    // Route show ada di grup middleware umum (admin,superadmin,keuangan,customer,fob) di atas

    // Billing Routes - URUTAN PENTING!
    Route::get('/billings', [BillingController::class, 'index'])->name('billings.index');
    Route::get('/billings/select-customer', [BillingController::class, 'selectCustomer'])->name('billings.select-customer');
    Route::get('/billings/create/{customer}', [BillingController::class, 'create'])->name('billings.create');
    Route::post('/billings/{customer}', [BillingController::class, 'store'])->name('billings.store');
    Route::post('/billings/{customer}/generate-number', [BillingController::class, 'generateBillingNumber'])->name('billings.generate-number');
    Route::get('/billings/{billing}/edit', [BillingController::class, 'edit'])->name('billings.edit');
    Route::put('/billings/{billing}', [BillingController::class, 'update'])->name('billings.update');
    Route::delete('/billings/{billing}', [BillingController::class, 'destroy'])->name('billings.destroy');
    // Route show ada di grup middleware umum (admin,superadmin,keuangan,customer,fob) di atas

    Route::get('/proforma-invoices', [ProformaInvoiceController::class, 'index'])->name('proforma-invoices.index');
    Route::get('/proforma-invoices/select-customer', [ProformaInvoiceController::class, 'selectCustomer'])->name('proforma-invoices.select-customer');
    Route::get('/proforma-invoices/create/{customer}', [ProformaInvoiceController::class, 'create'])->name('proforma-invoices.create');
    Route::post('/proforma-invoices/{customer}', [ProformaInvoiceController::class, 'store'])->name('proforma-invoices.store');
    Route::post('/proforma-invoices/{customer}/generate-number', [ProformaInvoiceController::class, 'generateProformaNumber'])->name('proforma-invoices.generate-number');
    Route::get('/proforma-invoices/{customer}/balance', [ProformaInvoiceController::class, 'getCustomerBalance'])->name('proforma-invoices.get-balance');
    Route::get('/proforma-invoices/{proformaInvoice}', [ProformaInvoiceController::class, 'show'])->name('proforma-invoices.show');
    Route::get('/proforma-invoices/{proformaInvoice}/edit', [ProformaInvoiceController::class, 'edit'])->name('proforma-invoices.edit');
    Route::put('/proforma-invoices/{proformaInvoice}', [ProformaInvoiceController::class, 'update'])->name('proforma-invoices.update');
    Route::delete('/proforma-invoices/{proformaInvoice}', [ProformaInvoiceController::class, 'destroy'])->name('proforma-invoices.destroy');

    // Print Routes - View Only Access
    Route::get('/customer/{customer}/print-billing', [DataPencatatanController::class, 'printBilling'])
        ->name('data-pencatatan.print-billing');
    Route::get('/customer/{customer}/print-invoice', [DataPencatatanController::class, 'printInvoice'])
        ->name('data-pencatatan.print-invoice');
    Route::get('/customer/{customer}/print-deposit-history', [DataPencatatanController::class, 'printDepositHistory'])
        ->name('customer.print-deposit-history');

    // ========================================================================
    // FOB Routes - View Only untuk Keuangan
    // CRITICAL: Route ini bisa diakses oleh Keuangan untuk VIEW data FOB
    // ========================================================================
    Route::get('/data-pencatatan/fob/{customer}', [FobController::class, 'fobDetail'])
        ->name('data-pencatatan.fob-detail');
    Route::post('/data-pencatatan/fob/{customer}/filter-month-year', [FobController::class, 'filterByMonthYear'])
        ->name('data-pencatatan.fob.filter-month-year');
});

// ============================================================================
// Rute KHUSUS untuk Admin dan SuperAdmin saja (WRITE ACCESS)
// Keuangan TIDAK bisa akses routes di bawah ini
// ============================================================================
Route::middleware(['auth', 'role:admin,superadmin'])->group(function () {

    // Admin Dashboard
    Route::get('/admin/dashboard', [DashboardController::class, 'adminDashboard'])
        ->name('admin.dashboard');

    // User Management - HANYA Admin
    Route::get('/kelola-user', [UserController::class, 'index'])->name('user.index');
    Route::post('/tambah-user', [AuthController::class, 'tambahUser'])->name('tambah.user');
    Route::get('/edit-user/{user}', [UserController::class, 'edit'])->name('user.edit');
    Route::put('/update-user/{user}', [UserController::class, 'update'])->name('user.update');
    Route::delete('/delete-user/{user}', [UserController::class, 'destroy'])->name('user.destroy');

    // Kelola Mobil/NOPOL - HANYA Admin
    Route::resource('nomor-polisi', NomorPolisiController::class);
    Route::get('/api/nomor-polisi/get-all', [NomorPolisiController::class, 'getAll'])->name('nomor-polisi.getAll');
    Route::get('/api/ukuran/get-all', [UkuranController::class, 'getAll'])->name('ukuran.getAll');
    Route::post('/api/ukuran', [UkuranController::class, 'store'])->name('ukuran.store');

    // Data Pencatatan - CREATE/UPDATE/DELETE (HANYA Admin)
    Route::get('/data-pencatatan/create', [DataPencatatanController::class, 'create'])
        ->name('data-pencatatan.create');
    Route::post('/data-pencatatan', [DataPencatatanController::class, 'store'])
        ->name('data-pencatatan.store');
    Route::get('/data-pencatatan/{dataPencatatan}/edit', [DataPencatatanController::class, 'edit'])
        ->name('data-pencatatan.edit');
    Route::put('/data-pencatatan/{dataPencatatan}', [DataPencatatanController::class, 'update'])
        ->name('data-pencatatan.update');
    Route::delete('/data-pencatatan/{dataPencatatan}', [DataPencatatanController::class, 'destroy'])
        ->name('data-pencatatan.destroy');
    Route::get('/data-pencatatan/create/{customerId}', [DataPencatatanController::class, 'createWithCustomer'])
        ->name('data-pencatatan.create-with-customer');
    Route::get('/data-pencatatan/get-latest-reading', [DataPencatatanController::class, 'getLatestReading'])
        ->name('data-pencatatan.get-latest-reading');
    Route::post('/data-pencatatan/{customer}/import-excel', [ExcelImportController::class, 'importExcel'])
        ->name('data-pencatatan.import-excel');
    Route::get('/data-pencatatan/template-excel', [ExcelImportController::class, 'downloadTemplateExcel'])
        ->name('data-pencatatan.template-excel');

    // Pricing & Deposit Management - HANYA Admin
    Route::post('/user/customer/{customer}/update-pricing', [UserController::class, 'updateCustomerPricing'])
        ->name('user.update-pricing');
    Route::post('/user/customer/{customer}/update-pricing-khusus', [UserController::class, 'updateCustomerPricingKhusus'])
        ->name('user.update-pricing-khusus');
    Route::post('/customer/{userId}/add-deposit', [UserController::class, 'addDeposit'])
        ->name('customer.add-deposit');
    Route::post('/customer/{userId}/reduce-balance', [UserController::class, 'reduceBalance'])
        ->name('customer.reduce-balance');
    Route::post('/customer/{userId}/zero-balance', [UserController::class, 'zeroBalance'])
        ->name('customer.zero-balance');
    Route::delete('/customers/{userId}/remove-deposit', [UserController::class, 'removeDeposit'])
        ->name('customer.remove-deposit');
    Route::put('/customers/{userId}/update-deposit', [UserController::class, 'updateDeposit'])
        ->name('customer.update-deposit');
    Route::put('/customers/{userId}/update-deposit-mmbtu', [UserController::class, 'updateDepositMmbtu'])
        ->name('customer.update-deposit-mmbtu');
    Route::get('/customer/{customer}/pricing-history', [UserController::class, 'getPricingHistory'])
        ->name('customer.pricing-history');
    Route::get('/sync-balance/{customer}', [UserController::class, 'syncBalance'])
        ->name('sync.balance');

    // MMBTU-specific routes
    Route::post('/user/customer/{customer}/update-pricing-mmbtu', [UserController::class, 'updateCustomerMmbtuPricing'])
        ->name('user.update-pricing-mmbtu');
    Route::post('/customer/{userId}/add-deposit-mmbtu', [UserController::class, 'addDepositMmbtu'])
        ->name('customer.add-deposit-mmbtu');

    // ========================================================================
    // Operator GTM - CREATE/UPDATE/DELETE (HANYA Admin)
    // CRITICAL: Route STATIS harus SEBELUM route DINAMIS untuk menghindari konflik!
    // ========================================================================

    // Route statis untuk operator-gtm (harus di atas route dinamis)
    Route::get('/operator-gtm/create', [App\Http\Controllers\OperatorGtmController::class, 'create'])->name('operator-gtm.create');

    // Route statis untuk operator-gtm-lembur (harus di atas route dinamis)
    Route::get('/operator-gtm-lembur/{lembur}/edit', [App\Http\Controllers\OperatorGtmController::class, 'editLembur'])->name('operator-gtm.edit-lembur');
    Route::put('/operator-gtm-lembur/{lembur}', [App\Http\Controllers\OperatorGtmController::class, 'updateLembur'])->name('operator-gtm.update-lembur');
    Route::delete('/operator-gtm-lembur/{lembur}', [App\Http\Controllers\OperatorGtmController::class, 'destroyLembur'])->name('operator-gtm.destroy-lembur');

    // Route dengan parameter dinamis untuk operator-gtm (harus setelah route statis)
    Route::get('/operator-gtm/{operatorGtm}/create-lembur', [App\Http\Controllers\OperatorGtmController::class, 'createLembur'])->name('operator-gtm.create-lembur');
    Route::get('/operator-gtm/{operatorGtm}/edit', [App\Http\Controllers\OperatorGtmController::class, 'edit'])->name('operator-gtm.edit');
    // Note: Route show sudah dipindah ke grup keuangan di atas untuk READ ACCESS

    // Route POST/PUT/DELETE untuk operator-gtm
    Route::post('/operator-gtm', [App\Http\Controllers\OperatorGtmController::class, 'store'])->name('operator-gtm.store');
    Route::post('/operator-gtm/{operatorGtm}/lembur', [App\Http\Controllers\OperatorGtmController::class, 'storeLembur'])->name('operator-gtm.store-lembur');
    Route::put('/operator-gtm/{operatorGtm}', [App\Http\Controllers\OperatorGtmController::class, 'update'])->name('operator-gtm.update');
    Route::delete('/operator-gtm/{operatorGtm}', [App\Http\Controllers\OperatorGtmController::class, 'destroy'])->name('operator-gtm.destroy');

    // Rekap Routes - HANYA Admin
    Route::get('/rekap-pengambilan', [RekapPengambilanController::class, 'index'])
        ->name('rekap-pengambilan.index');
    Route::get('/rekap-pengambilan/create', [RekapPengambilanController::class, 'create'])
        ->name('rekap-pengambilan.create');
    Route::get('/rekap-pengambilan/create-with-customer/{customer}', [RekapPengambilanController::class, 'createWithCustomer'])
        ->name('rekap-pengambilan.create-with-customer');
    Route::post('/rekap-pengambilan', [RekapPengambilanController::class, 'store'])
        ->name('rekap-pengambilan.store');
    Route::get('/rekap-pengambilan/{rekapPengambilan}', [RekapPengambilanController::class, 'show'])
        ->name('rekap-pengambilan.show');
    Route::get('/rekap-pengambilan/{rekapPengambilan}/edit', [RekapPengambilanController::class, 'edit'])
        ->name('rekap-pengambilan.edit');
    Route::put('/rekap-pengambilan/{rekapPengambilan}', [RekapPengambilanController::class, 'update'])
        ->name('rekap-pengambilan.update');
    Route::delete('/rekap-pengambilan/{rekapPengambilan}', [RekapPengambilanController::class, 'destroy'])
        ->name('rekap-pengambilan.destroy');
    Route::get('/rekap-pengambilan/find-by-date/{customer}/{date}', [RekapPengambilanController::class, 'findByDate'])
        ->name('rekap-pengambilan.find-by-date');
    Route::get('/rekap-pengambilan/find-by-date-volume/{customer}/{date}/{volume}', [RekapPengambilanController::class, 'findByDateAndVolume'])
        ->name('rekap-pengambilan.find-by-date-volume');

    Route::get('/rekap-penjualan', [App\Http\Controllers\Rekap\RekapPenjualanController::class, 'index'])
        ->name('rekap.penjualan.index');
    Route::get('/rekap-penjualan/cetak', [App\Http\Controllers\Rekap\RekapPenjualanController::class, 'cetakRekapPenjualan'])
        ->name('rekap.penjualan.cetak');

    Route::get('/rekap-pembelian', [App\Http\Controllers\Rekap\RekapPembelianController::class, 'index'])
        ->name('rekap.pembelian.index');
    Route::get('/rekap-pembelian/cetak', [App\Http\Controllers\Rekap\RekapPembelianController::class, 'cetakRekapPembelian'])
        ->name('rekap.pembelian.cetak');
    Route::get('/rekap-pembelian/kelola-harga-gagas', [App\Http\Controllers\Rekap\RekapPembelianController::class, 'kelolaHargaGagas'])
        ->name('rekap.pembelian.kelola-harga-gagas');
    Route::post('/rekap-pembelian/update-harga-gagas', [App\Http\Controllers\Rekap\RekapPembelianController::class, 'updateHargaGagas'])
        ->name('rekap.pembelian.update-harga-gagas');
    Route::get('/rekap-pembelian/get-current-rate', [App\Http\Controllers\Rekap\RekapPembelianController::class, 'getCurrentRate'])
        ->name('rekap.pembelian.get-current-rate');
    Route::delete('/rekap-pembelian/delete-harga-gagas/{id}', [App\Http\Controllers\Rekap\RekapPembelianController::class, 'deleteHargaGagas'])
        ->name('rekap.pembelian.delete-harga-gagas');
    Route::post('/rekap-pembelian/copy-from-previous', [App\Http\Controllers\Rekap\RekapPembelianController::class, 'copyFromPreviousPeriod'])
        ->name('rekap.pembelian.copy-from-previous');

    // ========================================================================
    // FOB Routes - CREATE/UPDATE/DELETE (HANYA Admin)
    // CRITICAL: Route STATIS harus SEBELUM route DINAMIS untuk menghindari konflik!
    // Route view-only (fobDetail & filterByMonthYear) sudah dipindah ke grup keuangan di atas
    // ========================================================================
    
    // Route statis untuk FOB (harus di atas route dinamis)
    Route::get('/data-pencatatan/fob/create', [FobController::class, 'create'])
        ->name('data-pencatatan.fob.create');
    
    // Route dengan parameter dinamis untuk FOB (harus setelah route statis)
    Route::get('/data-pencatatan/fob/create/{fobId}', [FobController::class, 'createWithFob'])
        ->name('data-pencatatan.fob.create-with-fob');
    Route::get('/data-pencatatan/fob/{customer}/print', [FobController::class, 'printPage'])
        ->name('data-pencatatan.fob.print');
    Route::get('/data-pencatatan/fob/{customer}/sync', [FobController::class, 'syncData'])
        ->name('data-pencatatan.fob.sync-data');
    Route::get('/data-pencatatan/fob/{fob}/debug', function (App\Models\User $fob) {
        return view('data-pencatatan.fob.fob-debug', compact('fob'));
    })->name('fob.debug');
    
    // Route POST untuk FOB
    Route::post('/data-pencatatan/fob', [FobController::class, 'store'])
        ->name('data-pencatatan.fob.store');
    Route::post('/fob/{fobId}/update-pricing', [UserController::class, 'updateFobPricing'])
        ->name('fob.update-pricing');

    // Route GET untuk print deposit history FOB
    Route::get('/fob/{customer}/print-deposit-history', [FobController::class, 'printDepositHistory'])
        ->name('fob.print-deposit-history');

    // Debug Routes - HANYA Admin
    Route::get('/test/process-queue', [App\Http\Controllers\QueueTestController::class, 'processQueue'])->name('test.process-queue');
    Route::get('/test/check-cache', [App\Http\Controllers\QueueTestController::class, 'checkCache'])->name('test.check-cache');
    Route::get('/debug/system-check', [App\Http\Controllers\DebugKasController::class, 'checkSystem'])->name('debug.system-check');
    Route::get('/debug/check-import', [App\Http\Controllers\DebugKasController::class, 'checkLastImport'])->name('debug.check-import');
    Route::get('/debug/simulate-import', [App\Http\Controllers\DebugKasController::class, 'simulateImport'])->name('debug.simulate-import');
    Route::get('/debug/fob-calculations/{customer}', [FobController::class, 'debugAndFixCalculations'])->name('debug.fob-calculations');

    // Data Sync Routes - HANYA Admin
    Route::prefix('data-sync')->group(function () {
        Route::post('/fob/{customer}/analyze-and-fix', [App\Http\Controllers\DataSyncController::class, 'analyzeAndFixFobDataSync'])
            ->name('data-sync.fob.analyze-and-fix');
        Route::get('/fob/{customer}/debug', [App\Http\Controllers\DataSyncController::class, 'debugFobDataSync'])
            ->name('data-sync.fob.debug');
    });

    // Test route untuk Advanced Fix
    Route::get('/test-advanced-fix/{customer}', function(App\Models\User $customer) {
        return view('test-advanced-fix', compact('customer'));
    })->name('test.advanced-fix');

    Route::prefix('debug')->group(function () {
        Route::get('/compare-data', [DataSyncDebugController::class, 'compareCustomerBillingData'])
            ->name('debug.compare-data');
        Route::get('/find-duplicates', [DataSyncDebugController::class, 'findDuplicateDates'])
            ->name('debug.find-duplicates');
        Route::get('/compare-balance', [DataSyncDebugController::class, 'compareBalanceCalculation'])
            ->name('debug.compare-balance');
        Route::post('/quick-fix', [DataSyncDebugController::class, 'quickFixDataSync'])
            ->name('debug.quick-fix');
        Route::get('/fob/{customer}/analyze', [FobController::class, 'analyzeFobData'])
            ->name('debug.fob.analyze');
        Route::post('/fob/{customer}/clean-duplicates', [FobController::class, 'cleanDuplicateFobData'])
            ->name('debug.fob.clean-duplicates');
        Route::post('/fob/{customer}/validate-consistency', [FobController::class, 'validateFobTotalConsistency'])
            ->name('debug.fob.validate-consistency');
    });
});

// ============================================================================
// Rute untuk Customer, FOB, dan MMBTU
// ============================================================================
Route::middleware(['auth', 'role:customer,fob,mmbtu'])->group(function () {
    Route::get('/customer/dashboard', [DashboardController::class, 'customerDashboard'])
        ->name('customer.dashboard');
    Route::get('/customer/filter', [DashboardController::class, 'customerDashboard'])
        ->name('customer.filter');
    Route::get('/data-saya', [DataPencatatanController::class, 'indexCustomer'])
        ->name('customer.data');
    Route::post('/proses-pembayaran/{dataPencatatan}', [DataPencatatanController::class, 'prosesPembayaran'])
        ->name('customer.proses-pembayaran');

    // Customer Invoice & Billing Routes
    Route::get('/customer/invoices', [InvoiceController::class, 'customerInvoices'])->name('customer.invoices');
    Route::get('/customer/billings', [BillingController::class, 'customerBillings'])->name('customer.billings');
    // Customer bisa akses show invoice/billing melalui route /invoices/{invoice} dan /billings/{billing} di grup middleware umum
});

// Rute khusus untuk FOB (dashboard berbeda)
Route::middleware(['auth', 'role:fob'])->group(function () {
    Route::get('/fob/dashboard', [DashboardController::class, 'fobDashboard'])
        ->name('fob.dashboard');
    Route::get('/fob/filter', [DashboardController::class, 'fobDashboard'])
        ->name('fob.filter');
});




// ============================================================================
// Rute untuk Demo
// ============================================================================
Route::middleware(['auth', 'role:demo'])->group(function () {
    Route::get('/demo/admin', [App\Http\Controllers\Demo\DemoController::class, 'demoAdmin'])
        ->name('demo.admin');
    Route::get('/demo/customer', [App\Http\Controllers\Demo\DemoController::class, 'demoCustomer'])
        ->name('demo.customer');
    Route::post('/demo/filter', [App\Http\Controllers\Demo\DemoController::class, 'filterByMonthYear'])
        ->name('demo.filter');
    Route::get('/demo/create', [App\Http\Controllers\Demo\DemoController::class, 'create'])
        ->name('demo.create');
    Route::post('/demo/store', [App\Http\Controllers\Demo\DemoController::class, 'store'])
        ->name('demo.store');
    Route::post('/demo/pricing', [App\Http\Controllers\Demo\DemoController::class, 'updatePricing'])
        ->name('demo.update-pricing');
    Route::post('/demo/deposit', [App\Http\Controllers\Demo\DemoController::class, 'addDeposit'])
        ->name('demo.deposit');
    Route::delete('/demo/deposit/{index}', [App\Http\Controllers\Demo\DemoController::class, 'removeDeposit'])
        ->name('demo.remove-deposit');
});

// Rute default setelah login
Route::get('/', function () {
    return redirect('/login');
});
