<?php

use App\Http\Controllers\AssetBarangController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\HutangController;
use App\Http\Controllers\JenisBarangController;
use App\Http\Controllers\KasbonController;
use App\Http\Controllers\KasirController;
use App\Http\Controllers\LaporanKeuangan\ArusKasController;
use App\Http\Controllers\LaporanKeuangan\LabaRugiController;
use App\Http\Controllers\LaporanKeuangan\NeracaController;
use App\Http\Controllers\LapPembelianController;
use App\Http\Controllers\LapPengirimanController;
use App\Http\Controllers\LevelHargaController;
use App\Http\Controllers\LevelUserController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MutasiController;
use App\Http\Controllers\PemasukanController;
use App\Http\Controllers\PembelianBarangController;
use App\Http\Controllers\PengeluaranController;
use App\Http\Controllers\Pengembalian\PengembalianController;
use App\Http\Controllers\PengirimanBarangController;
use App\Http\Controllers\PiutangController;
use App\Http\Controllers\PlanOrderController;
use App\Http\Controllers\PromoController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\RatingMemberController;
use App\Http\Controllers\Reture\RetureSuplierController;
use App\Http\Controllers\RetureController;
use App\Http\Controllers\StockBarangController;
use App\Http\Controllers\StockOpnameController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TokoController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['tamu'])->group(function () {
    Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('post_login');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {

    Route::prefix('admin')->group(function () {
        Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard.index')->middleware('permission:GET /dashboard');
        Route::get('/dashboard', [AuthController::class, 'index'])->name('dashboard.index')->middleware('permission:GET /dashboard');

        // Brand Controller
        Route::get('/brand', [BrandController::class, 'index'])->name('master.brand.index')->middleware('permission:GET /brand');
        Route::get('/brand/create', [BrandController::class, 'create'])->name('master.brand.create')->middleware('permission:GET /brand/create');
        Route::post('/brand/store', [BrandController::class, 'store'])->name('master.brand.store')->middleware('permission:POST /brand/store');
        Route::get('/brand/edit/{id}', [BrandController::class, 'edit'])->name('master.brand.edit')->middleware('permission:GET /brand/edit/{id}');
        Route::put('/brand/{id}', [BrandController::class, 'update'])->name('master.brand.update')->middleware('permission:PUT /brand/{id}');
        Route::delete('/brand/delete/{id}', [BrandController::class, 'delete'])->name('master.brand.delete')->middleware('permission:DELETE /brand/delete/{id}');

        // Jenis Barang Controller
        Route::get('/jenis_barang', [JenisBarangController::class, 'index'])->name('master.jenisbarang.index')->middleware('permission:GET /jenis_barang');
        Route::get('/jenis_barang/create', [JenisBarangController::class, 'create'])->name('master.jenisbarang.create')->middleware('permission:GET /jenis_barang/create');
        Route::post('/jenis_barang/store', [JenisBarangController::class, 'store'])->name('master.jenisbarang.store')->middleware('permission:POST /jenis_barang/store');
        Route::get('/jenis_barang/edit/{id}', [JenisBarangController::class, 'edit'])->name('master.jenisbarang.edit')->middleware('permission:GET /jenis_barang/edit/{id}');
        Route::put('/jenis_barang{id}/update', [JenisBarangController::class, 'update'])->name('master.jenisbarang.update')->middleware('permission:PUT /jenis_barang{id}/update');
        Route::delete('/jenis_barang/delete/{id}', [JenisBarangController::class, 'delete'])->name('master.jenisbarang.delete')->middleware('permission:DELETE /jenis_barang/delete/{id}');

        // Pembelian Barang
        Route::get('/pembelianbarang', [PembelianBarangController::class, 'index'])->name('transaksi.pembelianbarang.index')->middleware('permission:GET /pembelianbarang');
        Route::get('/pembelianbarang/create', [PembelianBarangController::class, 'create'])->name('transaksi.pembelianbarang.create')->middleware('permission:GET /pembelianbarang/create');
        Route::post('/pembelianbarang/store', [PembelianBarangController::class, 'store'])->name('transaksi.pembelianbarang.store')->middleware('permission:POST /pembelianbarang/store');
        Route::post('/pembelianbarang/additem', [PembelianBarangController::class, 'addItem'])->name('transaksi.pembelianbarang.additem')->middleware('permission:POST /pembelianbarang/additem');
        Route::delete('/pembelianbarang/remove-item/{id}', [PembelianBarangController::class, 'removeItem'])->middleware('permission:DELETE /pembelianbarang/remove-item/{id}');
        Route::get('/pembelianbarang/{id}/detail', [PembelianBarangController::class, 'detail'])->name('transaksi.pembelianbarang.detail')->middleware('permission:GET /pembelianbarang/{id}/detail');
        Route::get('/pembelianbarang/Getdetail', [PembelianBarangController::class, 'getDetailPembelian'])->name('transaksi.pembelianbarang.Getdetail')->middleware('permission:GET /pembelianbarang/Getdetail');
        Route::put('/pembelianbarang/{id}/update', [PembelianBarangController::class, 'update'])->name('transaksi.pembelianbarang.update')->middleware('permission:PUT /pembelianbarang/{id}/update');
        Route::delete('/pembelianbarang/{id}/delete', [PembelianBarangController::class, 'delete'])->name('transaksi.pembelianbarang.delete')->middleware('permission:DELETE /pembelianbarang/{id}/delete');
        Route::get('/get-stock/{id_barang}', [PembelianBarangController::class, 'getStock'])->name('transaksi.pembelian.getstock')->middleware('permission:GET /get-stock/{id_barang}');
        Route::post('/pembelianbarang/update_status/{id}', [PembelianBarangController::class, 'updateStatus'])->name('transaksi.pembelianbarang.update_status')->middleware('permission:POST /pembelianbarang/update_status/{id}');
        Route::get('/pembelian-barang/level-harga/{barangId}', [PembelianBarangController::class, 'getLevelHarga'])->middleware('permission:GET /pembelian-barang/level-harga/{barangId}');
        Route::post('/pembelian-barang/store-temp', [PembelianBarangController::class, 'storeTemp'])->name('transaksi.temp.pembelianbarang')->middleware('permission:POST /pembelian-barang/store-temp');
        Route::post('/import-pembelianbarang', [PembelianBarangController::class, 'import'])->name('master.pembelianbarang.import')->middleware('permission:POST /import-pembelianbarang');

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

        // User Controller
        Route::get('/user', [UserController::class, 'index'])->name('master.user.index')->middleware('permission:GET /user');
        Route::get('/user/create', [UserController::class, 'create'])->name('master.user.create')->middleware('permission:GET /user/create');
        Route::post('/user/store', [UserController::class, 'store'])->name('master.user.store')->middleware('permission:POST /user/store');
        Route::get('/user/edit/{id}', [UserController::class, 'edit'])->name('master.user.edit')->middleware('permission:GET /user/edit/{id}');
        Route::put('/user/update/{id}', [UserController::class, 'update'])->name('master.user.update')->middleware('permission:PUT /user/update/{id}');
        Route::delete('/user/delete/{id}', [UserController::class, 'delete'])->name('master.user.delete')->middleware('permission:DELETE /user/delete/{id}');
        Route::post('/import-user', [UserController::class, 'import'])->name('master.user.import')->middleware('permission:POST /import-user');

        // Barang Controller
        Route::get('/barang', [BarangController::class, 'index'])->name('master.barang.index')->middleware('permission:GET /barang');
        Route::get('/barang/create', [BarangController::class, 'create'])->name('master.barang.create')->middleware('permission:GET /barang/create');
        Route::post('/barang/store', [BarangController::class, 'store'])->name('master.barang.store')->middleware('permission:POST /barang/store');
        Route::get('/barang/edit/{id}', [BarangController::class, 'edit'])->name('master.barang.edit')->middleware('permission:GET /barang/edit/{id}');
        Route::put('/barang/update/{id}', [BarangController::class, 'update'])->name('master.barang.update')->middleware('permission:PUT /barang/update/{id}');
        Route::delete('/barang/delete/{id}', [BarangController::class, 'delete'])->name('master.barang.delete')->middleware('permission:DELETE /barang/delete/{id}');
        Route::get('/get-brands-by-jenis', [BrandController::class, 'getBrandsByJenis'])->name('getBrandsByJenis')->middleware('permission:GET /get-brands-by-jenis');
        Route::post('/import-barang', [BarangController::class, 'import'])->name('master.barang.import')->middleware('permission:POST /import-barang');

        // Supplier Controller
        Route::get('/supplier', [SupplierController::class, 'index'])->name('master.supplier.index')->middleware('permission:GET /supplier');
        Route::get('/supplier/create', [SupplierController::class, 'create'])->name('master.supplier.create')->middleware('permission:GET /supplier/create');
        Route::post('/supplier/store', [SupplierController::class, 'store'])->name('master.supplier.store')->middleware('permission:POST /supplier/store');
        Route::get('/supplier/edit/{id}', [SupplierController::class, 'edit'])->name('master.supplier.edit')->middleware('permission:GET /supplier/edit/{id}');
        Route::put('/supplier/update/{id}', [SupplierController::class, 'update'])->name('master.supplier.update')->middleware('permission:PUT /supplier/update/{id}');
        Route::delete('/supplier/delete/{id}', [SupplierController::class, 'delete'])->name('master.supplier.delete')->middleware('permission:DELETE /supplier/delete/{id}');
        Route::post('/import-supplier', [SupplierController::class, 'import'])->name('master.supplier.import')->middleware('permission:POST /import-supplier');

        // Member Controller
        Route::get('/member', [MemberController::class, 'index'])->name('master.member.index')->middleware('permission:GET /member');
        Route::post('/member/store', [MemberController::class, 'store'])->name('master.member.store')->middleware('permission:POST /member/store');
        Route::get('/members/{id}/edit', [MemberController::class, 'edit'])->name('members.edit')->middleware('permission:GET /members/{id}/edit');
        Route::put('/member/update/{id}', [MemberController::class, 'update'])->name('master.member.update')->middleware('permission:PUT /member/update/{id}');
        Route::delete('/member/delete/{id}', [MemberController::class, 'delete'])->name('master.member.delete')->middleware('permission:DELETE /member/delete/{id}');
        Route::get('/get-level-harga/{id_toko}', [MemberController::class, 'getLevelHarga'])->middleware('permission:GET /get-level-harga/{id_toko}');
        Route::post('/import-member', [MemberController::class, 'import'])->name('master.member.import')->middleware('permission:POST /import-member');

        // Promo Controller
        Route::get('/promo', [PromoController::class, 'index'])->name('master.promo.index')->middleware('permission:GET /promo');
        Route::post('/promo/store', [PromoController::class, 'store'])->name('master.promo.store')->middleware('permission:POST /promo/store');
        Route::put('/promo/update', [PromoController::class, 'update'])->name('master.promo.update')->middleware('permission:PUT /promo/update');
        Route::put('/promo/update-status', [PromoController::class, 'updateStatus'])->name('master.promo.update-status')->middleware('permission:PUT /promo/update-status');

        // Level Harga Controller
        Route::get('/levelharga', [LevelHargaController::class, 'index'])->name('master.levelharga.index')->middleware('permission:GET /levelharga');
        Route::get('/levelharga/create', [LevelHargaController::class, 'create'])->name('master.levelharga.create')->middleware('permission:GET /levelharga/create');
        Route::post('/levelharga/store', [LevelHargaController::class, 'store'])->name('master.levelharga.store')->middleware('permission:POST /levelharga/store');
        Route::get('/levelharga/edit/{id}', [LevelHargaController::class, 'edit'])->name('master.levelharga.edit')->middleware('permission:GET /levelharga/edit/{id}');
        Route::put('/levelharga/update/{id}', [LevelHargaController::class, 'update'])->name('master.levelharga.update')->middleware('permission:PUT /levelharga/update/{id}');
        Route::delete('/levelharga/delete/{id}', [LevelHargaController::class, 'delete'])->name('master.levelharga.delete')->middleware('permission:DELETE /levelharga/delete/{id}');

        // Level User Controller
        Route::get('/leveluser', [LevelUserController::class, 'index'])->name('master.leveluser.index')->middleware('permission:GET /leveluser');
        Route::get('/leveluser/create', [LevelUserController::class, 'create'])->name('master.leveluser.create')->middleware('permission:GET /leveluser/create');
        Route::post('/leveluser/store', [LevelUserController::class, 'store'])->name('master.leveluser.store')->middleware('permission:POST /leveluser/store');
        Route::get('/leveluser/edit/{id}', [LevelUserController::class, 'edit'])->name('master.leveluser.edit')->middleware('permission:GET /leveluser/edit/{id}');
        Route::put('/leveluser/update/{id}', [LevelUserController::class, 'update'])->name('master.leveluser.update')->middleware('permission:PUT /leveluser/update/{id}');
        Route::delete('/leveluser/delete/{id}', [LevelUserController::class, 'delete'])->name('master.leveluser.delete')->middleware('permission:DELETE /leveluser/delete/{id}');
        Route::get('/leveluser/permission/{id}', [LevelUserController::class, 'hakAksesUser'])->name('master.leveluser.permission');
        Route::get('/leveluser/permission/get/{id}', [LevelUserController::class, 'getHakAksesUser'])->name('master.leveluser.permission.get');
        Route::post('/leveluser/permission/store/{id}', [LevelUserController::class, 'createHakAksesUser'])->name('master.leveluser.permission.store');

        // Stock Barang Controller
        Route::get('/stockbarang', [StockBarangController::class, 'index'])->name('master.stockbarang.index')->middleware('permission:GET /stockbarang');
        Route::get('/stockbarang/create', [StockBarangController::class, 'create'])->name('master.stockbarang.create')->middleware('permission:GET /stockbarang/create');
        Route::get('/get-stock-details/{id_barang}', [StockBarangController::class, 'getStockDetails'])->name('get-stock-details')->middleware('permission:GET /get-stock-details/{id_barang}');
        Route::get('/get-item/{id}', [StockBarangController::class, 'getItem'])->name('get.item')->middleware('permission:GET /get-item/{id}');
        Route::post('/update-level-harga', [StockBarangController::class, 'updateLevelHarga'])->name('updateLevelHarga')->middleware('permission:POST /update-level-harga');
        Route::get('/hpp_barang', [StockBarangController::class, 'getHppBarang'])->name('master.stock.hpp_barang')->middleware('permission:GET /hpp_barang');
        Route::get('/get-detail-barang/{id_barang}', [StockBarangController::class, 'getdetailbarang'])->name('get.detail.barang')->middleware('permission:GET /get-detail-barang/{id_barang}');
        // Route::get('/stock/detail/{id}', [StockBarangController::class, 'detail'])->name('master.stock.detail')->middleware('permission:GET /stock/detail/{id}');

        // Pengeluaran Controller
        Route::get('/pengeluaran', [PengeluaranController::class, 'index'])->name('keuangan.pengeluaran.index')->middleware('permission:GET /pengeluaran');
        Route::post('/pengeluaran/store', [PengeluaranController::class, 'store'])->name('master.pengeluaran.store')->middleware('permission:POST /pengeluaran/store');
        Route::delete('/pengeluaran/delete/{id}', [PengeluaranController::class, 'delete'])->name('master.pengeluaran.delete')->middleware('permission:DELETE /pengeluaran/delete/{id}');
        Route::put('/pengeluaran/update/{id}', [PengeluaranController::class, 'updatehutang'])->name('master.pengeluaran.update')->middleware('permission:PUT /pengeluaran/update/{id}');
        Route::get('/pengeluaran/detail/{id}', [PengeluaranController::class, 'detail'])->name('master.pengeluaran.detail')->middleware('permission:GET /pengeluaran/detail/{id}');

        // Pemasukan Controller
        Route::get('/pemasukan', [PemasukanController::class, 'index'])->name('keuangan.pemasukan.index')->middleware('permission:GET /pemasukan');
        Route::post('/pemasukan/store', [PemasukanController::class, 'store'])->name('master.pemasukan.store')->middleware('permission:POST /pemasukan/store');
        Route::delete('/pemasukan/delete/{id}', [PemasukanController::class, 'delete'])->name('master.pemasukan.delete')->middleware('permission:DELETE /pemasukan/delete/{id}');
        Route::put('/pemasukan/update/{id}', [PemasukanController::class, 'updatepinjam'])->name('master.pemasukan.update')->middleware('permission:PUT /pemasukan/update/{id}');
        Route::get('/pemasukan/detail/{id}', [PemasukanController::class, 'detail'])->name('master.pemasukan.detail')->middleware('permission:GET /pemasukan/detail/{id}');

        // Mutasi Controller
        Route::get('/mutasi', [MutasiController::class, 'index'])->name('keuangan.mutasi.index')->middleware('permission:GET /mutasi');
        Route::post('mutasi/store', [MutasiController::class, 'store'])->name('master.mutasi.store')->middleware('permission:POST mutasi/store');
        Route::delete('/mutasi/delete/{id}', [MutasiController::class, 'delete'])->name('master.mutasi.delete')->middleware('permission:DELETE /mutasi/delete/{id}');

        // Hutang Controller
        Route::get('/hutang', [HutangController::class, 'index'])->name('keuangan.hutang.index')->middleware('permission:GET /hutang');
        Route::post('hutang/store', [HutangController::class, 'store'])->name('master.hutang.store')->middleware('permission:POST hutang/store');
        Route::get('/hutang/detail/{id}', [HutangController::class, 'detail'])->name('master.hutang.detail')->middleware('permission:GET /hutang/detail/{id}');
        Route::put('/hutang/update/{id}', [HutangController::class, 'updatehutang'])->name('master.hutang.update')->middleware('permission:PUT /hutang/update/{id}');
        Route::delete('/hutang/delete/{id}', [HutangController::class, 'delete'])->name('master.hutang.delete')->middleware('permission:DELETE /hutang/delete/{id}');

        // Piutang Controller
        Route::get('/piutang', [PiutangController::class, 'index'])->name('keuangan.piutang.index')->middleware('permission:GET /piutang');
        Route::post('piutang/store', [PiutangController::class, 'store'])->name('master.piutang.store')->middleware('permission:POST piutang/store');
        Route::get('/piutang/detail/{id}', [PiutangController::class, 'detail'])->name('master.piutang.detail')->middleware('permission:GET /piutang/detail/{id}');
        Route::put('/piutang/update/{id}', [PiutangController::class, 'updatepiutang'])->name('master.piutang.update')->middleware('permission:PUT /piutang/update/{id}');
        Route::delete('/piutang/delete/{id}', [PiutangController::class, 'delete'])->name('master.piutang.delete')->middleware('permission:DELETE /piutang/delete/{id}');

        Route::get('/stockopname', [StockOpnameController::class, 'index'])->name('master.stockopname.index')->middleware('permission:GET /stockopname');
        Route::get('/planorder', [PlanOrderController::class, 'index'])->name('distribusi.planorder.index')->middleware('permission:GET /planorder');

        // Pengiriman Barang
        Route::get('/pengirimanbarang', [PengirimanBarangController::class, 'index'])->name('distribusi.pengirimanbarang.index')->middleware('permission:GET /pengirimanbarang');
        Route::get('/pengirimanbarang/create', [PengirimanBarangController::class, 'create'])->name('distribusi.pengirimanbarang.create')->middleware('permission:GET /pengirimanbarang/create');
        Route::get('/pengirimanbarang/detail/{id}', [PengirimanBarangController::class, 'detail'])->name('distribusi.pengirimanbarang.detail')->middleware('permission:GET /pengirimanbarang/detail/{id}');
        Route::get('/get-users-by-toko/{id_toko}', [PengirimanBarangController::class, 'getUsersByToko'])->middleware('permission:GET /get-users-by-toko/{id_toko}');
        Route::get('/get-barang-stock/{id_barang}/{id_toko}', [PengirimanBarangController::class, 'getBarangStock'])->middleware('permission:GET /get-barang-stock/{id_barang}/{id_toko}');
        Route::get('/get-harga-barang/{id_barang}/{id_toko}', [PengirimanBarangController::class, 'getHargaBarang'])->middleware('permission:GET /get-harga-barang/{id_barang}/{id_toko}');
        Route::post('/pengirimanbarang/additem', [PengirimanBarangController::class, 'addItem'])->name('transaksi.pengirimanbarang.additem')->middleware('permission:POST /pengirimanbarang/additem');
        Route::delete('/pengirimanbarang/remove-item/{id}', [PengirimanBarangController::class, 'removeItem'])->middleware('permission:DELETE /pengirimanbarang/remove-item/{id}');
        Route::post('/pengirimanbarang/store', [PengirimanBarangController::class, 'store'])->name('transaksi.pengirimanbarang.store')->middleware('permission:POST /pengirimanbarang/store');
        Route::post('/pengirimanbarang/storeR', [PengirimanBarangController::class, 'storeReture'])->name('transaksi.pengirimanbarang.storeReture')->middleware('permission:POST /pengirimanbarang/storeR');
        Route::post('/pengirimanbarang/storeDR', [PengirimanBarangController::class, 'storeDetailReture'])->name('transaksi.pengirimanbarang.storeDetailReture')->middleware('permission:POST /pengirimanbarang/storeDR');
        Route::get('/pengirimanbarang/edit/{id}', [PengirimanBarangController::class, 'edit'])->name('transaksi.pengirimanbarang.edit')->middleware('permission:GET /pengirimanbarang/edit/{id}');
        Route::post('/pengirimanbarang/update_status/{id}', [PengirimanBarangController::class, 'updateStatus'])->name('transaksi.pengirimanbarang.update_status')->middleware('permission:POST /pengirimanbarang/update_status/{id}');
        Route::put('/pengirimanbarang/update/{id}', [PengirimanBarangController::class, 'update'])->name('transaksi.pengirimanbarang.update')->middleware('permission:PUT /pengirimanbarang/update/{id}');
        Route::post('/pengirimanbarang/storeTemp', [PengirimanBarangController::class, 'storetempPengiriman'])->name('temp.store.pengiriman')->middleware('permission:POST /pengirimanbarang/storeTemp');
        Route::delete('/pengirimanbarang/delete', [PengirimanBarangController::class, 'deleteTempPengiriman'])->name('delete.temp.pengiriman')->middleware('permission:DELETE /pengirimanbarang/delete');
        Route::put('/pengirimanbarang/update', [PengirimanBarangController::class, 'updatetempPengiriman'])->name('update.temp.pengiriman')->middleware('permission:PUT /pengirimanbarang/update');
        Route::get('/pengirimanbarang/get-temporary-items', [PengirimanBarangController::class, 'getTempPengiriman'])->name('get.temp.pengiriman')->middleware('permission:GET /pengirimanbarang/get-temporary-items');
        Route::post('/pengirimanbarang/save', [PengirimanBarangController::class, 'save'])->name('save.pengiriman')->middleware('permission:POST /pengirimanbarang/save');
        Route::delete('/pengirimanbarang/{id}/delete', [PengirimanBarangController::class, 'delete'])->name('transaksi.pengiriman.delete')->middleware('permission:DELETE /pengirimanbarang/{id}/delete');
        Route::get('/pengirimanbarang/reture', [PengirimanBarangController::class, 'returePengiriman'])->name('distribusi.pengirimanbarang.reture')->middleware('permission:GET /pengirimanbarang/reture');

        // Kasir Controller
        Route::get('/kasir', [KasirController::class, 'index'])->name('transaksi.kasir.index')->middleware('permission:GET /kasir');
        Route::post('/kasir/store', [KasirController::class, 'store'])->name('transaksi.kasir.store')->middleware('permission:POST /kasir/store');
        Route::get('/kasirs/{id}/detail', [KasirController::class, 'detail'])->name('kasirs.detail')->middleware('permission:GET /kasirs/{id}/detail');
        Route::put('/kasir/update/{id}', [KasirController::class, 'update'])->name('transaksi.kasir.update')->middleware('permission:PUT /kasir/update/{id}');
        Route::delete('/kasir/delete/{id}', [KasirController::class, 'delete'])->name('transaksi.kasir.delete')->middleware('permission:DELETE /kasir/delete/{id}');
        Route::get('/kasir/get-filtered-harga', [KasirController::class, 'getFilteredHarga'])->middleware('permission:GET /kasir/get-filtered-harga');
        Route::get('/cetak-struk/{id_kasir}', [KasirController::class, 'cetakStruk'])->name('cetak.struk')->middleware('permission:GET /cetak-struk/{id_kasir}');

        Route::get('/lappembelian', [LapPembelianController::class, 'index'])->name('laporan.pembelian.index')->middleware('permission:GET /lappembelian');
        Route::get('/lappengiriman', [LapPengirimanController::class, 'index'])->name('laporan.pengiriman.index')->middleware('permission:GET /lappengiriman');
        Route::get('/laprating', [RatingController::class, 'index'])->name('laporan.rating.index')->middleware('permission:GET /laprating');
        Route::post('/get-barang-jual', [RatingController::class, 'getBarangJual'])->name('get-barang-jual')->middleware('permission:POST /get-barang-jual');
        Route::get('/get-barang-jual', [RatingController::class, 'getBarangJual'])->middleware('permission:GET /get-barang-jual');
        Route::get('/asetbarang', [AssetBarangController::class, 'index'])->name('laporan.asetbarang.index')->middleware('permission:GET /asetbarang');
        Route::get('/ratingmember', [RatingMemberController::class, 'index'])->name('laporan.ratingmember.index')->middleware('permission:GET /ratingmember');

        // Reture Controller
        Route::get('/reture', [RetureController::class, 'index'])->name('reture.index')->middleware('permission:GET /reture');
        Route::get('/reture/create', [RetureController::class, 'create'])->name('reture.create')->middleware('permission:GET /reture/create');
        Route::post('/reture/storeNota', [RetureController::class, 'store_nota'])->name('reture.storeNota')->middleware('permission:POST /reture/storeNota');
        Route::post('/reture/updateStore', [RetureController::class, 'updateStore'])->name('reture.updateStore')->middleware('permission:POST /reture/updateStore');
        Route::post('/reture/tempStore', [RetureController::class, 'store_temp_item'])->name('reture.tempStore')->middleware('permission:POST /reture/tempStore');
        Route::get('/temporary-items', [RetureController::class, 'getTemporaryItems'])->name('get.temporary.items')->middleware('permission:GET /temporary-items');
        Route::get('/temporary-Data', [RetureController::class, 'getTempoData'])->name('get.tempoData')->middleware('permission:GET /temporary-Data');
        Route::post('/reture/permStore', [RetureController::class, 'saveTemporaryItems'])->name('reture.permStore')->middleware('permission:POST /reture/permStore');
        Route::delete('/reture/deleteTemp', [RetureController::class, 'deleteRowTable'])->name('delete.tempData')->middleware('permission:DELETE /reture/deleteTemp');
        Route::get('/retureItem', [RetureController::class, 'getRetureItems'])->name('get.retureItems')->middleware('permission:GET /retureItem');
        Route::post('/updateNotaReture', [RetureController::class, 'updateNotaReture'])->name('create.updateNotaReture')->middleware('permission:POST /updateNotaReture');
        Route::post('/reture/storeNotaSupplier', [RetureController::class, 'storeNotaSupplier'])->name('create.NoteReture')->middleware('permission:POST /reture/storeNotaSupplier');
        Route::delete('/reture/deleteTempItem', [RetureController::class, 'deleteTempItem'])->name('delete.tempItem')->middleware('permission:DELETE /reture/deleteTempItem');

        // Pengembalian Barang Controller
        Route::delete('/pengembalian/delete', [PengembalianController::class, 'delete'])->name('pengembalian.delete')->middleware('permission:DELETE /pengembalian/delete');

        Route::prefix('reture')->as('reture.')->group(function () {
            Route::prefix('suplier')->as('suplier.')->group(function () {
                Route::get('/', [RetureSuplierController::class, 'index'])->name('index')->middleware('permission:GET /');
                Route::post('/store', [RetureSuplierController::class, 'store'])->name('store')->middleware('permission:POST /store');
                Route::delete('/delete', [RetureSuplierController::class, 'delete'])->name('delete')->middleware('permission:DELETE /delete');
            });
        });

        Route::prefix('kasbon')->as('transaksi.')->group(function () {
            Route::get('/', [KasbonController::class, 'index'])->name('index')->middleware('permission:GET /');
            Route::get('/detail/{id}', [KasbonController::class, 'detail'])->name('detail')->middleware('permission:GET /detail/{id}');
            Route::post('/bayar', [KasbonController::class, 'bayar'])->name('bayar')->middleware('permission:POST /bayar');
        });

        Route::prefix('laporan-keuangan')->as('laporankeuangan.')->group(function () {
            Route::prefix('arus-kas')->as('aruskas.')->group(function () {
                Route::get('/', [ArusKasController::class, 'index'])->name('index')->middleware('permission:GET /');
            });
            Route::prefix('laba-rugi')->as('labarugi.')->group(function () {
                Route::get('/', [LabaRugiController::class, 'index'])->name('index')->middleware('permission:GET /');
            });
            Route::prefix('neraca')->as('neraca.')->group(function () {
                Route::get('/', [NeracaController::class, 'index'])->name('index')->middleware('permission:GET /');
            });
        });
    });
});
