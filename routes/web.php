<?php

use App\Http\Controllers\Auth\AuthController;
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
use App\Http\Controllers\TransaksiBarang\{KasbonController, KasirController, PembelianBarangController, PengembalianController};
use Illuminate\Support\Facades\Route;

Route::middleware(['tamu'])->group(function () {
    Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('post_login');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard.index')->middleware('permission:GET /dashboard');
    Route::get('/dashboard', [AuthController::class, 'index'])->name('dashboard.index')->middleware('permission:GET /dashboard');

    // User Controller
    Route::get('/user', [UserController::class, 'index'])->name('master.user.index')->middleware('permission:GET /user');
    Route::get('/user/create', [UserController::class, 'create'])->name('master.user.create')->middleware('permission:GET /user/create');
    Route::post('/user/store', [UserController::class, 'store'])->name('master.user.store')->middleware('permission:POST /user/store');
    Route::get('/user/edit/{id}', [UserController::class, 'edit'])->name('master.user.edit')->middleware('permission:GET /user/edit/{id}');
    Route::put('/user/update/{id}', [UserController::class, 'update'])->name('master.user.update')->middleware('permission:PUT /user/update/{id}');
    Route::delete('/user/delete/{id}', [UserController::class, 'delete'])->name('master.user.delete')->middleware('permission:DELETE /user/delete/{id}');
    Route::post('/import-user', [UserController::class, 'import'])->name('master.user.import')->middleware('permission:POST /import-user');

    // Toko Controller
    Route::get('/toko', [TokoController::class, 'index'])->name('master.toko.index')->middleware('permission:GET /toko');
    Route::get('/toko/create', [TokoController::class, 'create'])->name('master.toko.create')->middleware('permission:GET /toko/create');
    Route::post('/toko/store', [TokoController::class, 'store'])->name('master.toko.store')->middleware('permission:POST /toko/store');
    Route::get('/toko/edit/{id}', [TokoController::class, 'edit'])->name('master.toko.edit')->middleware('permission:GET /toko/edit/{id}');
    Route::put('/toko/update/{id}', [TokoController::class, 'update'])->name('master.toko.update')->middleware('permission:PUT /toko/update/{id}');
    Route::delete('/toko/delete/{id}', [TokoController::class, 'delete'])->name('master.toko.delete')->middleware('permission:DELETE /toko/delete/{id}');
    Route::get('/toko/detail/{id}', [TokoController::class, 'detail'])->name('master.toko.detail')->middleware('permission:GET /toko/detail/{id}');
    Route::get('/toko/detail/create/{id}', [TokoController::class, 'create_detail'])->name('master.toko.create_detail')->middleware('permission:GET /toko/detail/create/{id}');
    Route::post('/toko/store_detail', [TokoController::class, 'store_detail'])->name('master.toko.store_detail')->middleware('permission:POST /toko/store_detail');
    Route::get('/toko/{id_toko}/detail/{id_barang}/edit/{id}', [TokoController::class, 'edit_detail'])->name('master.toko.edit_detail')->middleware('permission:GET /toko/{id_toko}/detail/{id_barang}/edit/{id}');
    Route::put('/toko/{id_toko}/detail/{id_barang}/update', [TokoController::class, 'update_detail'])->name('master.toko.update_detail')->middleware('permission:PUT /toko/{id_toko}/detail/{id_barang}/update');
    Route::delete('/toko/{id_toko}/detail/{id_barang}/delete', [TokoController::class, 'delete_detail'])->name('master.toko.delete_detail')->middleware('permission:DELETE /toko/{id_toko}/detail/{id_barang}/delete');
    Route::get('/master/toko/search', [TokoController::class, 'search'])->name('master.toko.search')->middleware('permission:GET /master/toko/search');
    Route::get('/master/stock/searchs', [TokoController::class, 'searchs'])->name('master.stock.searchs')->middleware('permission:GET /master/stock/searchs');
    Route::post('/import-toko', [TokoController::class, 'import'])->name('master.toko.import')->middleware('permission:POST /import-toko');

    // Member Controller
    Route::get('/member', [MemberController::class, 'index'])->name('master.member.index')->middleware('permission:GET /member');
    Route::post('/member/store', [MemberController::class, 'store'])->name('master.member.store')->middleware('permission:POST /member/store');
    Route::get('/members/{id}/edit', [MemberController::class, 'edit'])->name('members.edit')->middleware('permission:GET /members/{id}/edit');
    Route::put('/member/update/{id}', [MemberController::class, 'update'])->name('master.member.update')->middleware('permission:PUT /member/update/{id}');
    Route::delete('/member/delete/{id}', [MemberController::class, 'delete'])->name('master.member.delete')->middleware('permission:DELETE /member/delete/{id}');
    Route::get('/get-level-harga/{id_toko}', [MemberController::class, 'getLevelHarga'])->name('master.member.level-harga')->middleware('permission:GET /get-level-harga/{id_toko}');
    Route::post('/import-member', [MemberController::class, 'import'])->name('master.member.import')->middleware('permission:POST /import-member');

    // Supplier Controller
    Route::get('/supplier', [SupplierController::class, 'index'])->name('master.supplier.index')->middleware('permission:GET /supplier');
    Route::get('/supplier/create', [SupplierController::class, 'create'])->name('master.supplier.create')->middleware('permission:GET /supplier/create');
    Route::post('/supplier/store', [SupplierController::class, 'store'])->name('master.supplier.store')->middleware('permission:POST /supplier/store');
    Route::get('/supplier/edit/{id}', [SupplierController::class, 'edit'])->name('master.supplier.edit')->middleware('permission:GET /supplier/edit/{id}');
    Route::put('/supplier/update/{id}', [SupplierController::class, 'update'])->name('master.supplier.update')->middleware('permission:PUT /supplier/update/{id}');
    Route::delete('/supplier/delete/{id}', [SupplierController::class, 'delete'])->name('master.supplier.delete')->middleware('permission:DELETE /supplier/delete/{id}');
    Route::post('/import-supplier', [SupplierController::class, 'import'])->name('master.supplier.import')->middleware('permission:POST /import-supplier');

    // Jenis Barang Controller
    Route::get('/jenis_barang', [JenisBarangController::class, 'index'])->name('master.jenisbarang.index')->middleware('permission:GET /jenis_barang');
    Route::get('/jenis_barang/create', [JenisBarangController::class, 'create'])->name('master.jenisbarang.create')->middleware('permission:GET /jenis_barang/create');
    Route::post('/jenis_barang/store', [JenisBarangController::class, 'store'])->name('master.jenisbarang.store')->middleware('permission:POST /jenis_barang/store');
    Route::get('/jenis_barang/edit/{id}', [JenisBarangController::class, 'edit'])->name('master.jenisbarang.edit')->middleware('permission:GET /jenis_barang/edit/{id}');
    Route::put('/jenis_barang{id}/update', [JenisBarangController::class, 'update'])->name('master.jenisbarang.update')->middleware('permission:PUT /jenis_barang{id}/update');
    Route::delete('/jenis_barang/delete/{id}', [JenisBarangController::class, 'delete'])->name('master.jenisbarang.delete')->middleware('permission:DELETE /jenis_barang/delete/{id}');

    // Brand Controller
    Route::get('/brand', [BrandController::class, 'index'])->name('master.brand.index')->middleware('permission:GET /brand');
    Route::get('/brand/create', [BrandController::class, 'create'])->name('master.brand.create')->middleware('permission:GET /brand/create');
    Route::post('/brand/store', [BrandController::class, 'store'])->name('master.brand.store')->middleware('permission:POST /brand/store');
    Route::get('/brand/edit/{id}', [BrandController::class, 'edit'])->name('master.brand.edit')->middleware('permission:GET /brand/edit/{id}');
    Route::put('/brand/{id}', [BrandController::class, 'update'])->name('master.brand.update')->middleware('permission:PUT /brand/{id}');
    Route::delete('/brand/delete/{id}', [BrandController::class, 'delete'])->name('master.brand.delete')->middleware('permission:DELETE /brand/delete/{id}');

    // Barang Controller
    Route::get('/barang', [BarangController::class, 'index'])->name('master.barang.index')->middleware('permission:GET /barang');
    Route::get('/barang/create', [BarangController::class, 'create'])->name('master.barang.create')->middleware('permission:GET /barang/create');
    Route::post('/barang/store', [BarangController::class, 'store'])->name('master.barang.store')->middleware('permission:POST /barang/store');
    Route::get('/barang/edit/{id}', [BarangController::class, 'edit'])->name('master.barang.edit')->middleware('permission:GET /barang/edit/{id}');
    Route::put('/barang/update/{id}', [BarangController::class, 'update'])->name('master.barang.update')->middleware('permission:PUT /barang/update/{id}');
    Route::delete('/barang/delete/{id}', [BarangController::class, 'delete'])->name('master.barang.delete')->middleware('permission:DELETE /barang/delete/{id}');
    Route::get('/get-brands-by-jenis', [BrandController::class, 'getBrandsByJenis'])->name('getBrandsByJenis')->middleware('permission:GET /get-brands-by-jenis');
    Route::post('/import-barang', [BarangController::class, 'import'])->name('master.barang.import')->middleware('permission:POST /import-barang');

    // Stock Barang Controller
    Route::get('/stockbarang', [StockBarangController::class, 'index'])->name('master.stockbarang.index')->middleware('permission:GET /stockbarang');
    Route::get('/stockbarang/create', [StockBarangController::class, 'create'])->name('master.stockbarang.create')->middleware('permission:GET /stockbarang/create');
    Route::get('/get-stock-details/{id_barang}', [StockBarangController::class, 'getStockDetails'])->name('get-stock-details')->middleware('permission:GET /get-stock-details/{id_barang}');
    Route::get('/get-item/{id}', [StockBarangController::class, 'getItem'])->name('get.item')->middleware('permission:GET /get-item/{id}');
    Route::post('/update-level-harga', [StockBarangController::class, 'updateLevelHarga'])->name('updateLevelHarga')->middleware('permission:POST /update-level-harga');
    Route::get('/hpp_barang', [StockBarangController::class, 'getHppBarang'])->name('master.stock.hpp_barang')->middleware('permission:GET /hpp_barang');
    Route::get('/get-detail-barang/{id_barang}', [StockBarangController::class, 'getdetailbarang'])->name('get.detail.barang')->middleware('permission:GET /get-detail-barang/{id_barang}');
    Route::put('/stock-barang/refresh-stok', [StockBarangController::class, 'refreshStok'])->name('master.stockbarang.refresh-stok');
    Route::put('/stock-barang/edit-stok', [StockBarangController::class, 'editStock'])->name('master.stockbarang.edit-stok');

    // Permission Controller
    Route::get('/permission', [PermissionController::class, 'index'])->name('master.permission.index')->middleware('permission:GET /permission');
    Route::get('/permission/get', [PermissionController::class, 'getData'])->name('master.permission.get')->middleware('permission:GET /permission/get');
    Route::post('/permission/update', [PermissionController::class, 'update'])->name('master.permission.update')->middleware('permission:POST /permission/update');

    // Level User Controller
    Route::get('/leveluser', [LevelUserController::class, 'index'])->name('master.leveluser.index')->middleware('permission:GET /leveluser');
    Route::get('/leveluser/create', [LevelUserController::class, 'create'])->name('master.leveluser.create')->middleware('permission:GET /leveluser/create');
    Route::post('/leveluser/store', [LevelUserController::class, 'store'])->name('master.leveluser.store')->middleware('permission:POST /leveluser/store');
    Route::get('/leveluser/edit/{id}', [LevelUserController::class, 'edit'])->name('master.leveluser.edit')->middleware('permission:GET /leveluser/edit/{id}');
    Route::put('/leveluser/update/{id}', [LevelUserController::class, 'update'])->name('master.leveluser.update')->middleware('permission:PUT /leveluser/update/{id}');
    Route::delete('/leveluser/delete/{id}', [LevelUserController::class, 'delete'])->name('master.leveluser.delete')->middleware('permission:DELETE /leveluser/delete/{id}');
    Route::get('/leveluser/permission/{id}', [LevelUserController::class, 'hakAksesUser'])->name('master.leveluser.permission')->middleware('permission:GET /leveluser/permission/{id}');
    Route::get('/leveluser/permission/get/{id}', [LevelUserController::class, 'getHakAksesUser'])->name('master.leveluser.permission.get')->middleware('permission:GET /leveluser/permission/get/{id}');
    Route::post('/leveluser/permission/store/{id}', [LevelUserController::class, 'createHakAksesUser'])->name('master.leveluser.permission.store')->middleware('permission:POST /leveluser/permission/store/{id}');

    // Level Harga Controller
    Route::get('/levelharga', [LevelHargaController::class, 'index'])->name('master.levelharga.index')->middleware('permission:GET /levelharga');
    Route::get('/levelharga/create', [LevelHargaController::class, 'create'])->name('master.levelharga.create')->middleware('permission:GET /levelharga/create');
    Route::post('/levelharga/store', [LevelHargaController::class, 'store'])->name('master.levelharga.store')->middleware('permission:POST /levelharga/store');
    Route::get('/levelharga/edit/{id}', [LevelHargaController::class, 'edit'])->name('master.levelharga.edit')->middleware('permission:GET /levelharga/edit/{id}');
    Route::put('/levelharga/update/{id}', [LevelHargaController::class, 'update'])->name('master.levelharga.update')->middleware('permission:PUT /levelharga/update/{id}');
    Route::delete('/levelharga/delete/{id}', [LevelHargaController::class, 'delete'])->name('master.levelharga.delete')->middleware('permission:DELETE /levelharga/delete/{id}');

    // Promo Controller
    Route::get('/promo', [PromoController::class, 'index'])->name('master.promo.index')->middleware('permission:GET /promo');
    Route::post('/promo/store', [PromoController::class, 'store'])->name('master.promo.store')->middleware('permission:POST /promo/store');
    Route::put('/promo/update', [PromoController::class, 'update'])->name('master.promo.update')->middleware('permission:PUT /promo/update');
    Route::put('/promo/update-status', [PromoController::class, 'updateStatus'])->name('master.promo.update-status')->middleware('permission:PUT /promo/update-status');

    // Pengiriman Barang
    Route::get('/pengirimanbarang', [PengirimanBarangController::class, 'index'])->name('distribusi.pengirimanbarang.index')->middleware('permission:GET /pengirimanbarang');
    // Plan Order Controller
    Route::get('/planorder', [PlanOrderController::class, 'index'])->name('distribusi.planorder.index')->middleware('permission:GET /planorder');

    // Pembelian Barang
    Route::get('/pembelianbarang', [PembelianBarangController::class, 'index'])->name('transaksi.pembelianbarang.index')->middleware('permission:GET /pembelianbarang');
    Route::get('/pembelianbarang/create', [PembelianBarangController::class, 'create'])->name('transaksi.pembelianbarang.create')->middleware('permission:GET /pembelianbarang/create');
    Route::post('/pembelianbarang/store', [PembelianBarangController::class, 'store'])->name('transaksi.pembelianbarang.store')->middleware('permission:POST /pembelianbarang/store');
    Route::post('/pembelianbarang/additem', [PembelianBarangController::class, 'addItem'])->name('transaksi.pembelianbarang.additem')->middleware('permission:POST /pembelianbarang/additem');
    Route::delete('/pembelianbarang/remove-item/{id}', [PembelianBarangController::class, 'removeItem'])->middleware('permission:DELETE /pembelianbarang/remove-item/{id}');
    Route::get('/pembelianbarang/{id}/detail', [PembelianBarangController::class, 'detail'])->name('transaksi.pembelianbarang.detail')->middleware('permission:GET /pembelianbarang/{id}/detail');
    Route::get('/pembelianbarang/Getdetail', [PembelianBarangController::class, 'getDetailPembelian'])->name('transaksi.pembelianbarang.Getdetail')->middleware('permission:GET /pembelianbarang/Getdetail');
    Route::post('/pembelianbarang/update', [PembelianBarangController::class, 'update'])->name('transaksi.pembelianbarang.update')->middleware('permission:PUT /pembelianbarang/{id}/update');
    Route::delete('/pembelianbarang/{id}/delete', [PembelianBarangController::class, 'delete'])->name('transaksi.pembelianbarang.delete')->middleware('permission:DELETE /pembelianbarang/{id}/delete');
    Route::get('/get-stock/{id_barang}', [PembelianBarangController::class, 'getStock'])->name('transaksi.pembelian.getstock')->middleware('permission:GET /get-stock/{id_barang}');
    Route::post('/pembelianbarang/update_status/{id}', [PembelianBarangController::class, 'updateStatus'])->name('transaksi.pembelianbarang.update_status')->middleware('permission:POST /pembelianbarang/update_status/{id}');
    Route::get('/pembelian-barang/level-harga/{barangId}', [PembelianBarangController::class, 'getLevelHarga'])->middleware('permission:GET /pembelian-barang/level-harga/{barangId}');
    Route::post('/pembelian-barang/store-temp', [PembelianBarangController::class, 'storeTemp'])->name('transaksi.temp.pembelianbarang')->middleware('permission:POST /pembelian-barang/store-temp');
    Route::post('/import-pembelianbarang', [PembelianBarangController::class, 'import'])->name('master.pembelianbarang.import')->middleware('permission:POST /import-pembelianbarang');
    Route::put('/pembelianbarang/edit/detail-pembelian-barang', [PembelianBarangController::class, 'editDetailPembelianBarang'])->name('transaksi.pembelianbarang.update-detail')->middleware('permission:PUT /pembelianbarang/edit/detail-pembelian-barang');

    // Kasir Controller
    Route::get('/kasir', [KasirController::class, 'index'])->name('transaksi.kasir.index')->middleware('permission:GET /kasir');
    Route::post('/kasir/store', [KasirController::class, 'store'])->name('transaksi.kasir.store')->middleware('permission:POST /kasir/store');
    Route::get('/kasirs/{id}/detail', [KasirController::class, 'detail'])->name('kasirs.detail')->middleware('permission:GET /kasirs/{id}/detail');
    Route::put('/kasir/update/{id}', [KasirController::class, 'update'])->name('transaksi.kasir.update')->middleware('permission:PUT /kasir/update/{id}');
    Route::delete('/kasir/delete/{id}', [KasirController::class, 'delete'])->name('transaksi.kasir.delete')->middleware('permission:DELETE /kasir/delete/{id}');
    Route::get('/kasir/get-filtered-harga', [KasirController::class, 'getFilteredHarga'])->middleware('permission:GET /kasir/get-filtered-harga');
    Route::get('/cetak-struk/{id_kasir}', [KasirController::class, 'cetakStruk'])->name('cetak.struk')->middleware('permission:GET /cetak-struk/{id_kasir}');

    // Kasbon Controller
    Route::get('/kasbon', [KasbonController::class, 'index'])->name('transaksi.index')->middleware('permission:GET /kasbon');
    Route::get('/kasbon/detail/{id}', [KasbonController::class, 'detail'])->name('transaksi.detail')->middleware('permission:GET /kasbon/detail/{id}');
    Route::post('/kasbon/bayar', [KasbonController::class, 'bayar'])->name('transaksi.bayar')->middleware('permission:POST /kasbon/bayar');

    Route::get('/rekapitulasi/laporan-penjualan', [LaporanPenjualanController::class, 'index'])->name('laporan.penjualan.index')->middleware('permission:GET /rekapitulasi/laporan-penjualan');
    Route::get('/rekapitulasi/laporan-kasir', [LaporanKasirController::class, 'index'])->name('laporan.kasir.index')->middleware('permission:GET /rekapitulasi/laporan-kasir');
    Route::get('/lappembelian', [LaporanPembelianBarangController::class, 'index'])->name('laporan.pembelian.index')->middleware('permission:GET /lappembelian');

    Route::get('/lappengiriman', [LaporanPengirimanBarangController::class, 'index'])->name('laporan.pengiriman.index')->middleware('permission:GET /lappengiriman');

    Route::get('/laprating', [RatingBarangController::class, 'index'])->name('laporan.rating.index')->middleware('permission:GET /laprating');
    Route::post('/get-barang-jual', [RatingBarangController::class, 'getBarangJual'])->name('get-barang-jual')->middleware('permission:POST /get-barang-jual');
    Route::get('/get-barang-jual', [RatingBarangController::class, 'getBarangJual'])->middleware('permission:GET /get-barang-jual');

    Route::get('/ratingmember', [RatingMemberController::class, 'index'])->name('laporan.ratingmember.index')->middleware('permission:GET /ratingmember');

    Route::get('/asetbarang', [AsetBarangJualanController::class, 'index'])->name('laporan.asetbarang.index')->middleware('permission:GET /asetbarang');

    Route::get('/asetbarang-reture', [AsetBarangReturController::class, 'index'])->name('laporan.asetbarangreture.index')->middleware('permission:GET /asetbarang-reture');

    // Laporan Keuangan
    Route::get('/laporan-keuangan/arus-kas', [ArusKasController::class, 'index'])->name('laporankeuangan.aruskas.index')->middleware('permission:GET /laporan-keuangan/arus-kas');
    Route::get('/laporan-keuangan/laba-rugi', [LabaRugiController::class, 'index'])->name('laporankeuangan.labarugi.index')->middleware('permission:GET /laporan-keuangan/laba-rugi');
    Route::get('/laporan-keuangan/neraca', [NeracaController::class, 'index'])->name('laporankeuangan.neraca.index')->middleware('permission:GET /laporan-keuangan/neraca');
    Route::get('/laporan-keuangan/neraca-penyesuaian', [NeracaPenyesuaianController::class, 'index'])->name('laporankeuangan.neraca.penyesuaian.index')->middleware('permission:GET /laporan-keuangan/neraca-penyesuaian');

    // Pemasukan Controller
    Route::get('/pemasukan', [PemasukanController::class, 'index'])->name('keuangan.pemasukan.index')->middleware('permission:GET /pemasukan');

    // Pengeluaran Controller
    Route::get('/pengeluaran', [PengeluaranController::class, 'index'])->name('keuangan.pengeluaran.index')->middleware('permission:GET /pengeluaran');

    // Piutang Controller
    Route::get('/piutang', [PiutangController::class, 'index'])->name('keuangan.piutang.index')->middleware('permission:GET /piutang');

    // Hutang Controller
    Route::get('/hutang', [HutangController::class, 'index'])->name('keuangan.hutang.index')->middleware('permission:GET /hutang');

    // Mutasi Controller
    Route::get('/mutasi', [MutasiController::class, 'index'])->name('keuangan.mutasi.index')->middleware('permission:GET /mutasi');

    // Pengembalian Barang Controller (sudah benar)
    Route::delete('/pengembalian/delete', [PengembalianController::class, 'delete'])->name('pengembalian.delete')->middleware('permission:DELETE /pengembalian/delete');

    Route::get('/log-aktivitas', [LogAktivitasController::class, 'index'])->name('master.logAktivitas.index')->middleware('permission:GET /log-aktivitas');
    Route::get('/dompet-digital', [DompetController::class, 'index'])->name('td.dompetDigital.index')->middleware('permission:GET /dompet-digital');
    Route::get('/transaksi-nonfisik', [TransaksiNonFisikController::class, 'index'])->name('td.penjualanNonfisik.index')->middleware('permission:GET /transaksi-nonfisik');

    Route::prefix('retur')->as('retur.')->group(function () {
        Route::get('/member', [ReturMemberController::class, 'index'])->name('member.index')->middleware('permission:GET /reture');
        Route::get('/supplier', [ReturSupplierController::class, 'index'])->name('supplier.index')->middleware('permission:GET /reture/suplier');
    });
});
