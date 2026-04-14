<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\CatatanController;
use App\Http\Controllers\LaporanKeuangan\{ArusKasController, LabaRugiController, NeracaController, NeracaPenyesuaianController};
use App\Http\Controllers\Retur\{ReturMemberController, ReturSupplierController};
use App\Http\Controllers\TransaksiDigital\{DompetController, TransaksiNonFisikController};
use App\Http\Controllers\DataMaster\Entitas\{MemberController, SupplierController, TokoController, UserController};
use App\Http\Controllers\DataMaster\Log\LogAktivitasController;
use App\Http\Controllers\DataMaster\ManajemenBarang\{BarangController, BrandController, JenisBarangController, StockBarangController};
use App\Http\Controllers\DataMaster\Pengaturan\{LevelHargaController, LevelUserController, PermissionController, PromoController};
use App\Http\Controllers\Distribusi\{PengirimanBarangController, PlanOrderController};
use App\Http\Controllers\JurnalKeuangan\{HutangController, MutasiController, PemasukanController, PengeluaranController, PiutangController};
use App\Http\Controllers\Rekapitulasi\{AsetBarangJualanController, AsetBarangReturController, LaporanKasirController, LaporanPembelianBarangController, LaporanPengirimanBarangController, LaporanPenjualanController, RatingBarangController, RatingMemberController};
use App\Http\Controllers\TransaksiBarang\{KasbonController, TransaksiKasirController, PembelianBarangController, PengembalianController};
use Illuminate\Support\Facades\Route;

Route::middleware(['tamu'])->group(function () {
    Route::get('', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('post_login');
});

Route::post('logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', [AuthController::class, 'dashboard'])->name('dashboard.index')->middleware('permission:GET /dashboard');
    Route::get('dashboard', [AuthController::class, 'index'])->name('dashboard.index')->middleware('permission:GET /dashboard');

    // User Controller
    Route::get('user', [UserController::class, 'index'])->name('master.user.index')->middleware('permission:GET /user');
    Route::get('profile', [UserController::class, 'profile'])->name('user.profile')->middleware('permission:GET /user');

    // Toko Controller
    Route::get('toko', [TokoController::class, 'index'])->name('master.toko.index')->middleware('permission:GET /toko');

    // Member Controller
    Route::get('member', [MemberController::class, 'index'])->name('master.member.index')->middleware('permission:GET /member');

    // Supplier Controller
    Route::get('suplier', [SupplierController::class, 'index'])->name('master.supplier.index')->middleware('permission:GET /suplier');

    // Jenis Barang Controller
    Route::get('jenis-barang', [JenisBarangController::class, 'index'])->name('master.jenisbarang.index')->middleware('permission:GET /jenis-barang');

    // Brand Controller
    Route::get('brand', [BrandController::class, 'index'])->name('master.brand.index')->middleware('permission:GET /brand');

    // Barang Controller
    Route::get('barang', [BarangController::class, 'index'])->name('master.barang.index')->middleware('permission:GET /barang');

    // Stock Barang Controller
    Route::get('stok-barang', [StockBarangController::class, 'index'])->name('master.stockbarang.index')->middleware('permission:GET /stok-barang');

    // Permission Controller
    Route::get('permission', [PermissionController::class, 'index'])->name('master.permission.index')->middleware('permission:GET /permission');

    // Level User Controller
    Route::get('level-user', [LevelUserController::class, 'index'])->name('master.leveluser.index')->middleware('permission:GET /level-user');

    // Level Harga Controller
    Route::get('level-harga', [LevelHargaController::class, 'index'])->name('master.levelharga.index')->middleware('permission:GET /level-harga');

    // Promo Controller
    Route::get('promo', [PromoController::class, 'index'])->name('master.promo.index')->middleware('permission:GET /promo');

    Route::get('log-aktivitas', [LogAktivitasController::class, 'index'])->name('master.logAktivitas.index')->middleware('permission:GET /log-aktivitas');

    Route::prefix('distribusi')->group(function () {
        // Pengiriman Barang
        Route::get('pengiriman-barang', [PengirimanBarangController::class, 'index'])->name('distribusi.pengirimanbarang.index')->middleware('permission:GET /pengiriman-barang');
        // Plan Order Controller
        Route::get('lokasi-riwayat-barang', [PlanOrderController::class, 'index'])->name('distribusi.planorder.index')->middleware('permission:GET /lokasi-riwayat-barang');
    });

    Route::prefix('transaksi-barang')->group(function () {
        // Pembelian Barang
        Route::get('pembelian-barang', [PembelianBarangController::class, 'index'])->name('transaksi.pembelianbarang.index')->middleware('permission:GET /pembelian-barang');
        Route::get('pembelian-barang/detail', [PembelianBarangController::class, 'detail'])->name('transaksi.pembelianbarang.detail')->middleware('permission:GET /pembelian-barang/detail');
        // Kasir Controller
        Route::get('kasir', [TransaksiKasirController::class, 'index'])->name('transaksi.kasir.index')->middleware('permission:GET /kasir');
        // Kasbon Controller
        Route::get('kasbon', [KasbonController::class, 'index'])->name('transaksi.index')->middleware('permission:GET /kasbon');
    });

    Route::prefix('rekapitulasi')->group(function () {
        Route::get('laporan-penjualan', [LaporanPenjualanController::class, 'index'])->name('laporan.penjualan.index')->middleware('permission:GET /laporan-penjualan');
        Route::get('laporan-kasir', [LaporanKasirController::class, 'index'])->name('laporan.kasir.index')->middleware('permission:GET /laporan-kasir');
        Route::get('laporan-pembelian', [LaporanPembelianBarangController::class, 'index'])->name('laporan.pembelian.index')->middleware('permission:GET /laporan-pembelian');
        Route::get('laporan-pengiriman', [LaporanPengirimanBarangController::class, 'index'])->name('laporan.pengiriman.index')->middleware('permission:GET /laporan-pengiriman');
        Route::get('rating-barang', [RatingBarangController::class, 'index'])->name('laporan.rating.index')->middleware('permission:GET /rating-barang');
        Route::get('rating-member', [RatingMemberController::class, 'index'])->name('laporan.ratingmember.index')->middleware('permission:GET /rating-member');
        Route::get('aset-barang-jualan', [AsetBarangJualanController::class, 'index'])->name('laporan.asetbarang.index')->middleware('permission:GET /aset-barang-jualan');
        Route::get('aset-barang-retur', [AsetBarangReturController::class, 'index'])->name('laporan.asetbarangreture.index')->middleware('permission:GET /aset-barang-retur');
    });

    Route::prefix('laporan-keuangan')->group(function () {
        // Laporan Keuangan
        Route::get('arus-kas', [ArusKasController::class, 'index'])->name('laporankeuangan.aruskas.index')->middleware('permission:GET /arus-kas');
        Route::get('laba-rugi', [LabaRugiController::class, 'index'])->name('laporankeuangan.labarugi.index')->middleware('permission:GET /laba-rugi');
        Route::get('neraca', [NeracaController::class, 'index'])->name('laporankeuangan.neraca.index')->middleware('permission:GET /neraca');
        Route::get('neraca-penyesuaian', [NeracaPenyesuaianController::class, 'index'])->name('laporankeuangan.neraca.penyesuaian.index')->middleware('permission:GET /neraca-penyesuaian');
    });

    Route::prefix('jurnal-keuangan')->group(function () {
        // Pemasukan Controller
        Route::get('pemasukan', [PemasukanController::class, 'index'])->name('keuangan.pemasukan.index')->middleware('permission:GET /pemasukan');
        // Pengeluaran Controller
        Route::get('pengeluaran', [PengeluaranController::class, 'index'])->name('keuangan.pengeluaran.index')->middleware('permission:GET /pengeluaran');
        // Piutang Controller
        Route::get('piutang', [PiutangController::class, 'index'])->name('keuangan.piutang.index')->middleware('permission:GET /piutang');
        // Hutang Controller
        Route::get('hutang', [HutangController::class, 'index'])->name('keuangan.hutang.index')->middleware('permission:GET /hutang');
        // Mutasi Controller
        Route::get('mutasi', [MutasiController::class, 'index'])->name('keuangan.mutasi.index')->middleware('permission:GET /mutasi');
    });

    Route::prefix('transaksi-digital')->group(function () {
        Route::get('dompet-digital', [DompetController::class, 'index'])->name('td.dompetDigital.index')->middleware('permission:GET /dompet-digital');
        Route::get('transaksi-nonfisik', [TransaksiNonFisikController::class, 'index'])->name('td.penjualanNonfisik.index')->middleware('permission:GET /transaksi-nonfisik');
    });

    Route::prefix('retur')->as('retur.')->group(function () {
        Route::get('member', [ReturMemberController::class, 'index'])->name('member.index')->middleware('permission:GET /retur-member');
        Route::get('suplier', [ReturSupplierController::class, 'index'])->name('supplier.index')->middleware('permission:GET /retur-suplier');
    });

    Route::get('catatan', [CatatanController::class, 'index'])->name('catatan.index')->middleware('permission:GET /catatan');
});

Route::get('levelharga/create', [LevelHargaController::class, 'create'])->name('master.levelharga.create');
Route::post('levelharga/store', [LevelHargaController::class, 'store'])->name('master.levelharga.store');
Route::get('levelharga/edit/{id}', [LevelHargaController::class, 'edit'])->name('master.levelharga.edit');
Route::put('levelharga/update/{id}', [LevelHargaController::class, 'update'])->name('master.levelharga.update');
Route::delete('levelharga/delete/{id}', [LevelHargaController::class, 'delete'])->name('master.levelharga.delete');
Route::get('leveluser/create', [LevelUserController::class, 'create'])->name('master.leveluser.create');
Route::post('leveluser/store', [LevelUserController::class, 'store'])->name('master.leveluser.store');
Route::get('leveluser/edit/{id}', [LevelUserController::class, 'edit'])->name('master.leveluser.edit');
Route::put('leveluser/update/{id}', [LevelUserController::class, 'update'])->name('master.leveluser.update');
Route::delete('leveluser/delete/{id}', [LevelUserController::class, 'delete'])->name('master.leveluser.delete');
Route::get('leveluser/permission/{id}', [LevelUserController::class, 'hakAksesUser'])->name('master.leveluser.permission');
Route::get('leveluser/permission/get/{id}', [LevelUserController::class, 'getHakAksesUser'])->name('master.leveluser.permission.get');
Route::post('leveluser/permission/store/{id}', [LevelUserController::class, 'createHakAksesUser'])->name('master.leveluser.permission.store');

Route::post('promo/store', [PromoController::class, 'store'])->name('master.promo.store')->middleware('permission:POST /promo/store');
Route::put('promo/update', [PromoController::class, 'update'])->name('master.promo.update')->middleware('permission:PUT /promo/update');
Route::put('promo/update-status', [PromoController::class, 'updateStatus'])->name('master.promo.update-status')->middleware('permission:PUT /promo/update-status');
