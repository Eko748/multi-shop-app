<?php

use App\Http\Controllers\Auth\CatatanController;
use App\Http\Controllers\Auth\DashboardController;
use App\Http\Controllers\LaporanKeuangan\{ArusKasController, LabaRugiController, NeracaController, NeracaPenyesuaianController};
use App\Http\Controllers\Retur\{ReturMemberController, ReturSupplierController};
use App\Http\Controllers\TransaksiDigital\{DompetController, TransaksiNonFisikController};
use App\Http\Controllers\DataMaster\Entitas\{MemberController, SupplierController, TokoController, TokoGroupController, UserController};
use App\Http\Controllers\DataMaster\Log\LogAktivitasController;
use App\Http\Controllers\DataMaster\ManajemenBarang\{BarangController, BrandController, JenisBarangController, StockBarangBatchController, StockBarangController};
use App\Http\Controllers\DataMaster\Pengaturan\{LevelHargaController, LevelUserController, PermissionController, PromoController};
use App\Http\Controllers\Distribusi\{PengirimanBarangController, PlanOrderController};
use App\Http\Controllers\JurnalKeuangan\{HutangController, MutasiController, PemasukanController, PengeluaranController, PiutangController};
use App\Http\Controllers\Rekapitulasi\{AsetBarangJualanController, AsetBarangReturController, LaporanKasirController, LaporanPembelianBarangController, LaporanPengirimanBarangController, LaporanPenjualanController, RatingBarangController, RatingMemberController};
use App\Http\Controllers\TransaksiBarang\{KasbonController, TransaksiKasirController, PembelianBarangController, PengembalianController};
use App\Http\Controllers\TransaksiDigital\DompetKategoriController;
use App\Http\Controllers\TransaksiDigital\DompetSaldoController;
use App\Http\Controllers\TransaksiDigital\ItemNonFisikController;
use App\Http\Controllers\TransaksiDigital\ItemNonFisikHargaController;
use App\Http\Controllers\TransaksiDigital\ItemNonFisikTipeController;
use App\Http\Controllers\TransaksiDigital\PenjualanNonFisikController;
use App\Http\Controllers\Utils\{KasController, MasterController};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('dashboard')->as('dashboard.')->group(function () {
    Route::get('/get-rekapitulasi-penjualan', [DashboardController::class, 'laporan_kasir'])->name('rekapitulasi-penjualan')->middleware('permission:GET /dashboard/get-rekapitulasi-penjualan');
    Route::get('/get-komparasi-toko', [DashboardController::class, 'getKomparasiToko'])->name('komparasi-toko')->middleware('permission:GET /dashboard/get-komparasi-toko');
    Route::get('/get-top-barang', [DashboardController::class, 'getBarangJual'])->name('top-barang')->middleware('permission:GET /dashboard/get-top-barang');
    Route::get('/get-top-member', [DashboardController::class, 'getMember'])->name('top-member')->middleware('permission:GET /dashboard/get-top-member');
    Route::get('/get-omset', [DashboardController::class, 'getOmset'])->name('omset')->middleware('permission:GET /dashboard/get-omset');
    Route::get('/get-laba-kotor', [DashboardController::class, 'getLabaKotor'])->name('laba-kotor')->middleware('permission:GET /dashboard/get-laba-kotor');
    Route::get('/get-jumlah-transaksi', [DashboardController::class, 'getJumlahTransaksi'])->name('jumlah-transaksi')->middleware('permission:GET /dashboard/get-jumlah-transaksi');
});

Route::prefix('user')->as('user.')->group(function () {
    Route::post('post', [UserController::class, 'post'])->name('post')->middleware('permission:POST /user/post');
    Route::put('put', [UserController::class, 'update'])->name('update')->middleware('permission:PUT /user/put');
    Route::delete('delete', [UserController::class, 'delete'])->name('delete')->middleware('permission:DELETE /user/delete');
});

Route::prefix('toko')->as('toko.')->group(function () {
    Route::post('post', [TokoController::class, 'post'])->name('post')->middleware('permission:POST /toko/post');
    Route::put('put', [TokoController::class, 'update'])->name('update')->middleware('permission:PUT /toko/put');
    Route::delete('delete', [TokoController::class, 'delete'])->name('delete')->middleware('permission:DELETE /toko/delete');
});

Route::prefix('member')->as('member.')->group(function () {
    Route::post('post', [MemberController::class, 'post'])->name('post')->middleware('permission:POST /member/post');
    Route::put('put', [MemberController::class, 'update'])->name('update')->middleware('permission:PUT /member/put');
    Route::delete('delete', [MemberController::class, 'delete'])->name('delete')->middleware('permission:DELETE /member/delete');
    Route::get('get/level-harga', [MemberController::class, 'getLevelHarga'])->name('getLevelHarga');
});

Route::prefix('suplier')->as('supplier.')->group(function () {
    Route::post('post', [SupplierController::class, 'post'])->name('post')->middleware('permission:POST /suplier/post');
    Route::put('put', [SupplierController::class, 'update'])->name('update')->middleware('permission:PUT /suplier/put');
    Route::delete('delete', [SupplierController::class, 'delete'])->name('delete')->middleware('permission:DELETE /suplier/delete');
});

Route::prefix('jenis-barang')->as('jenisBarang.')->group(function () {
    Route::post('post', [JenisBarangController::class, 'post'])->name('post')->middleware('permission:POST /jenis-barang/post');
    Route::put('put', [JenisBarangController::class, 'update'])->name('update')->middleware('permission:PUT /jenis-barang/put');
    Route::delete('delete', [JenisBarangController::class, 'delete'])->name('delete')->middleware('permission:DELETE /jenis-barang/delete');
});

Route::prefix('barang')->as('barang.')->group(function () {
    Route::post('post', [BarangController::class, 'post'])->name('post')->middleware('permission:POST /barang/post');
    Route::put('put', [BarangController::class, 'update'])->name('update')->middleware('permission:PUT /barang/put');
    Route::delete('delete', [BarangController::class, 'delete'])->name('delete')->middleware('permission:DELETE /barang/delete');
    Route::get('get-brands-by-jenis', [BrandController::class, 'getBrandsByJenis'])->name('getBrandsByJenis');
});

Route::prefix('brand')->as('brand.')->group(function () {
    Route::post('post', [BrandController::class, 'post'])->name('post')->middleware('permission:POST /brand/post');
    Route::put('put', [BrandController::class, 'update'])->name('update')->middleware('permission:PUT /brand/put');
    Route::delete('delete', [BrandController::class, 'delete'])->name('delete')->middleware('permission:DELETE /brand/delete');
});

Route::prefix('permission')->group(function () {
    Route::get('get', [PermissionController::class, 'getData'])->name('master.permission.get');
    Route::put('put', [PermissionController::class, 'update'])->name('master.permission.update')->middleware('permission:PUT /permission/put');
});

Route::prefix('td')->as('td.')->group(function () {
    Route::prefix('dompet-kategori')->as('dompetKategori.')->group(function () {
        Route::get('get', [DompetKategoriController::class, 'get'])->name('get');
        Route::get('get-nama', [DompetKategoriController::class, 'getNama'])->name('getNama');
        Route::post('post', [DompetKategoriController::class, 'post'])->name('post')->middleware('permission:POST /td/dompet-kategori/post');
        Route::put('put', [DompetKategoriController::class, 'put'])->name('put')->middleware('permission:PUT /td/dompet-kategori/put');
        Route::delete('delete', [DompetKategoriController::class, 'delete'])->name('delete')->middleware('permission:DELETE /td/dompet-kategori/delete');
    });

    Route::prefix('dompet-saldo')->as('dompetSaldo.')->group(function () {
        Route::get('get', [DompetSaldoController::class, 'get'])->name('get');
        Route::get('get-sisa-saldo', [DompetSaldoController::class, 'getSisaSaldo'])->name('getSisaSaldo');
        Route::get('get-saldo', [DompetSaldoController::class, 'getSaldo'])->name('getSaldo');
        Route::get('get-saldo-akhir', [DompetSaldoController::class, 'getSaldoAkhir'])->name('getSaldoAkhir');
        Route::get('get-total-kategori', [DompetSaldoController::class, 'getTotalPerKategori'])->name('getTotalPerKategori');
        Route::post('post', [DompetSaldoController::class, 'post'])->name('post')->middleware('permission:POST /td/dompet-saldo/post');
        Route::put('put', [DompetSaldoController::class, 'put'])->name('put')->middleware('permission:PUT /td/dompet-saldo/put');
        Route::delete('delete', [DompetSaldoController::class, 'delete'])->name('delete')->middleware('permission:DELETE /td/dompet-saldo/delete');
    });

    Route::prefix('item-nonfisik')->as('itemNonfisik.')->group(function () {
        Route::get('get', [ItemNonFisikController::class, 'get'])->name('get');
        Route::get('get-nama', [ItemNonFisikController::class, 'getNama'])->name('getNama');
        Route::post('post', [ItemNonFisikController::class, 'post'])->name('post')->middleware('permission:POST /td/item-nonfisik/post');
        Route::put('put', [ItemNonFisikController::class, 'put'])->name('put')->middleware('permission:PUT /td/item-nonfisik/put');
        Route::delete('delete', [ItemNonFisikController::class, 'delete'])->name('delete')->middleware('permission:DELETE /td/item-nonfisik/delete');
    });

    Route::prefix('item-nonfisik-harga')->as('itemNonfisikHarga.')->group(function () {
        Route::get('get-item-harga', [ItemNonFisikHargaController::class, 'getItemHarga'])->name('getItemHarga');
        Route::post('post', [ItemNonFisikHargaController::class, 'post'])->name('post')->middleware('permission:POST /td/item-nonfisik-harga/post');
        Route::put('put', [ItemNonFisikHargaController::class, 'put'])->name('put')->middleware('permission:PUT /td/item-nonfisik-harga/put');
        Route::delete('delete', [ItemNonFisikHargaController::class, 'delete'])->name('delete')->middleware('permission:DELETE /td/item-nonfisik-harga/delete');
    });

    Route::prefix('item-nonfisik-tipe')->as('itemNonfisikTipe.')->group(function () {
        Route::get('get', [ItemNonFisikTipeController::class, 'get'])->name('get');
        Route::get('get-nama', [ItemNonFisikTipeController::class, 'getNama'])->name('getNama');
        Route::post('post', [ItemNonFisikTipeController::class, 'post'])->name('post')->middleware('permission:POST /td/item-nonfisik-tipe/post');
        Route::put('put', [ItemNonFisikTipeController::class, 'put'])->name('put')->middleware('permission:PUT /td/item-nonfisik-tipe/put');
        Route::delete('delete', [ItemNonFisikTipeController::class, 'delete'])->name('delete')->middleware('permission:DELETE /td/item-nonfisik-tipe/delete');
    });

    Route::prefix('penjualan-nonfisik')->as('penjualanNonfisik.')->group(function () {
        Route::get('get', [PenjualanNonFisikController::class, 'get'])->name('get');
        Route::get('detail', [PenjualanNonFisikController::class, 'getDetail'])->name('getDetail');
        Route::get('get-nota', [PenjualanNonFisikController::class, 'getNota'])->name('getNota');
        Route::post('post', [PenjualanNonFisikController::class, 'post'])->name('post')->middleware('permission:POST /td/penjualan-nonfisik/post');
        Route::put('put', [PenjualanNonFisikController::class, 'put'])->name('put')->middleware('permission:PUT /td/penjualan-nonfisik/put');
        Route::delete('delete', [PenjualanNonFisikController::class, 'delete'])->name('delete')->middleware('permission:DELETE /td/penjualan-nonfisik/delete');
    });
});

Route::prefix('retur')->as('retur.')->group(function () {
    Route::prefix('member')->as('member.')->group(function () {
        Route::get('get', [ReturMemberController::class, 'get'])->name('get');
        Route::get('detail', [ReturMemberController::class, 'getDetail'])->name('getDetail');
        Route::get('get-qrcode', [ReturMemberController::class, 'getQRCode'])->name('getQRCode');
        Route::get('get-harga', [ReturMemberController::class, 'getHargaBarang'])->name('getHargaBarang');
        Route::post('post', [ReturMemberController::class, 'post'])->name('post')->middleware('permission:POST /retur/member/post');
        Route::put('put', [ReturMemberController::class, 'put'])->name('put')->middleware('permission:PUT /retur/member/put');
        Route::delete('delete', [ReturMemberController::class, 'delete'])->name('delete')->middleware('permission:DELETE /retur/member/delete');
    });

    Route::prefix('suplier')->as('supplier.')->group(function () {
        Route::get('get', [ReturSupplierController::class, 'get'])->name('get');
        Route::get('detail', [ReturSupplierController::class, 'getDetail'])->name('getDetail');
        Route::get('get-data', [ReturSupplierController::class, 'getData'])->name('getData');
        Route::get('get-supplier', [ReturSupplierController::class, 'getSupplier'])->name('getSupplier');
        Route::get('get-retur-member', [ReturSupplierController::class, 'getReturMember'])->name('getReturMember');
        Route::get('get-qrcode', [ReturSupplierController::class, 'getQRCode'])->name('getQRCode');
        Route::get('get-harga', [ReturSupplierController::class, 'getHargaBarang'])->name('getHargaBarang');
        Route::post('post', [ReturSupplierController::class, 'post'])->name('post')->middleware('permission:POST /retur/suplier/post');
        Route::put('put', [ReturSupplierController::class, 'put'])->name('put')->middleware('permission:PUT /retur/suplier/put');
        Route::put('verify', [ReturSupplierController::class, 'verify'])->name('verify')->middleware('permission:PUT /retur/suplier/verify');
    });
});

Route::prefix('stock-barang')->as('sb.')->group(function () {
    Route::get('get', [StockBarangController::class, 'get'])->name('get');
    Route::get('get-detail', [StockBarangController::class, 'getDetail'])->name('getDetail');
    Route::get('get-level-harga', [StockBarangController::class, 'getLevelHarga'])->name('getLevelHarga');
    Route::get('get-item/{id}', [StockBarangController::class, 'getItem'])->name('get.item');
    Route::get('get-hpp', [StockBarangController::class, 'getHpp'])->name('getHpp');
    Route::get('get-barang', [StockBarangController::class, 'getBarang'])->name('getBarang');
    Route::post('post-harga', [StockBarangController::class, 'updateHarga'])->name('updateHarga')->middleware('permission:POST /stock-barang/post-harga');
    Route::put('put-refresh', [StockBarangController::class, 'refreshStock'])->name('refreshStock')->middleware('permission:PUT /stock-barang/put-refresh');
    Route::put('put-stok', [StockBarangController::class, 'updateStock'])->name('updateStock')->middleware('permission:PUT /stock-barang/put-stok');

    Route::prefix('batch')->as('batch.')->group(function () {
        Route::get('get', [StockBarangBatchController::class, 'get'])->name('get');
        Route::get('get-by-qrcode', [StockBarangBatchController::class, 'getByQR'])->name('getByQR');
        Route::get('get-harga-jual', [StockBarangBatchController::class, 'getHargaJual'])->name('getHargaJual');
    });
});

Route::prefix('distribusi')->as('distribusi.')->group(function () {
    Route::prefix('pengiriman')->as('pengiriman.')->group(function () {
        Route::get('get', [PengirimanBarangController::class, 'get'])->name('get');
        Route::get('detail', [PengirimanBarangController::class, 'detail'])->name('detail');
        Route::get('temporary', [PengirimanBarangController::class, 'temporary'])->name('temporary');
        Route::get('progress', [PengirimanBarangController::class, 'progress'])->name('progress');
        Route::post('post', [PengirimanBarangController::class, 'post'])->name('post')->middleware('permission:POST /distribusi/pengiriman/post');
        Route::post('draft', [PengirimanBarangController::class, 'draft'])->name('draft')->middleware('permission:POST /distribusi/pengiriman/draft');
        Route::post('verify', [PengirimanBarangController::class, 'verify'])->name('verify')->middleware('permission:POST /distribusi/pengiriman/verify');
        Route::delete('delete-temporary', [PengirimanBarangController::class, 'deleteTemporary'])->name('deleteTemporary')->middleware('permission:DELETE /distribusi/pengiriman/delete-temporary');
        Route::delete('delete', [PengirimanBarangController::class, 'delete'])->name('delete')->middleware('permission:DELETE /distribusi/pengiriman/delete');
    });
});

Route::prefix('jurnal-keuangan')->as('jk.')->group(function () {
    Route::prefix('pemasukan')->as('pemasukan.')->group(function () {
        Route::get('get', [PemasukanController::class, 'get'])->name('get');
        Route::post('post', [PemasukanController::class, 'post'])->name('post')->middleware('permission:POST /jurnal-keuangan/pemasukan/post');
        Route::delete('delete', [PemasukanController::class, 'delete'])->name('delete')->middleware('permission:DELETE /jurnal-keuangan/pemasukan/delete');
    });

    Route::prefix('pengeluaran')->as('pengeluaran.')->group(function () {
        Route::get('get', [PengeluaranController::class, 'get'])->name('get');
        Route::post('post', [PengeluaranController::class, 'post'])->name('post')->middleware('permission:POST /jurnal-keuangan/pengeluaran/post');
        Route::delete('delete', [PengeluaranController::class, 'delete'])->name('delete')->middleware('permission:DELETE /jurnal-keuangan/pengeluaran/delete');
    });

    Route::prefix('hutang')->as('hutang.')->group(function () {
        Route::get('get', [HutangController::class, 'get'])->name('get');
        Route::get('detail', [HutangController::class, 'detail'])->name('detail');
        Route::post('post', [HutangController::class, 'post'])->name('post')->middleware('permission:POST /jurnal-keuangan/hutang/post');
        Route::put('pay', [HutangController::class, 'pay'])->name('pay')->middleware('permission:PUT /jurnal-keuangan/hutang/pay');
        Route::delete('delete', [HutangController::class, 'delete'])->name('delete')->middleware('permission:DELETE /jurnal-keuangan/hutang/delete');
    });

    Route::prefix('piutang')->as('piutang.')->group(function () {
        Route::get('get', [PiutangController::class, 'get'])->name('get');
        Route::get('detail', [PiutangController::class, 'detail'])->name('detail');
        Route::post('post', [PiutangController::class, 'post'])->name('post')->middleware('permission:POST /jurnal-keuangan/piutang/post');
        Route::put('pay', [PiutangController::class, 'pay'])->name('pay')->middleware('permission:PUT /jurnal-keuangan/piutang/pay');
        Route::delete('delete', [PiutangController::class, 'delete'])->name('delete')->middleware('permission:DELETE /jurnal-keuangan/piutang/delete');
    });

    Route::prefix('mutasi')->as('mutasi.')->group(function () {
        Route::get('get', [MutasiController::class, 'get'])->name('get');
        Route::post('post', [MutasiController::class, 'post'])->name('post')->middleware('permission:POST /jurnal-keuangan/mutasi/post');
        Route::delete('delete', [MutasiController::class, 'delete'])->name('delete')->middleware('permission:DELETE /jurnal-keuangan/mutasi/delete');
    });
});

Route::prefix('transaksi-barang')->as('tb.')->group(function () {
    Route::prefix('kasir')->as('kasir.')->group(function () {
        Route::get('get', [TransaksiKasirController::class, 'get'])->name('get');
        Route::get('detail', [TransaksiKasirController::class, 'detail'])->name('detail');
        Route::post('post', [TransaksiKasirController::class, 'post'])->name('post')->middleware('permission:POST /transaksi-barang/kasir/post');
        Route::delete('delete', [TransaksiKasirController::class, 'delete'])->name('delete')->middleware('permission:DELETE /transaksi-barang/kasir/delete');
        Route::get('get-harga', [TransaksiKasirController::class, 'getHarga'])->name('getHarga');
        Route::get('print', [TransaksiKasirController::class, 'print'])->name('print');
    });

    Route::prefix('pembelian')->as('pb.')->group(function () {
        Route::put('put', [PembelianBarangController::class, 'update'])->name('put')->middleware('permission:PUT /transaksi-barang/pembelian/put');
        Route::get('get', [PembelianBarangController::class, 'get'])->name('get');
        Route::get('get-detail', [PembelianBarangController::class, 'getDetail'])->name('getDetail');
        Route::put('put-detail', [PembelianBarangController::class, 'putDetail'])->name('putDetail')->middleware('permission:PUT /transaksi-barang/pembelian/put-detail');
        Route::delete('delete', [PembelianBarangController::class, 'delete'])->name('delete')->middleware('permission:DELETE /transaksi-barang/pembelian/delete');
        Route::delete('delete-detail', [PembelianBarangController::class, 'deleteDetail'])->name('deleteDetail')->middleware('permission:DELETE /transaksi-barang/pembelian/delete-detail');

        Route::prefix('temporary')->as('temp.')->group(function () {
            Route::post('post', [PembelianBarangController::class, 'postTemp'])->name('post')->middleware('permission:POST /transaksi-barang/pembelian/temporary/post');
            Route::delete('delete', [PembelianBarangController::class, 'deleteTemp'])->name('delete')->middleware('permission:DELETE /transaksi-barang/pembelian/temporary/delete');
            Route::get('get-temporary', [PembelianBarangController::class, 'getTemporary'])->name('get');
        });
    });
});

Route::prefix('catatan')->as('catatan.')->group(function () {
    Route::get('get', [CatatanController::class, 'get'])->name('get');
    Route::post('post', [CatatanController::class, 'post'])->name('post')->middleware('permission:POST /catatan/post');
    Route::put('put', [CatatanController::class, 'put'])->name('put')->middleware('permission:PUT /catatan/put');
    Route::put('read', [CatatanController::class, 'read'])->name('read')->middleware('permission:PUT /catatan/read');
    Route::delete('delete', [CatatanController::class, 'delete'])->name('delete')->middleware('permission:DELETE /catatan/delete');
});

Route::get('/arusKasir', [ArusKasController::class, 'getaruskas'])->name('master.aruskas.get');
Route::get('/labarugi', [LabaRugiController::class, 'getlabarugi'])->name('master.labarugi.get');

Route::get('/get-aset-retur', [AsetBarangReturController::class, 'getAsetBarangRetur'])->name('aset.retur');
Route::prefix('log-aktivitas')->as('log.')->group(function () {
    Route::get('get', [LogAktivitasController::class, 'get'])->name('get');
    Route::get('get/log-event', [LogAktivitasController::class, 'getLogEvent'])->name('get.log-event');
});

Route::prefix('neraca')->as('neraca.')->group(function () {
    Route::get('get', [NeracaPenyesuaianController::class, 'get'])->name('get');
    Route::post('post', [NeracaPenyesuaianController::class, 'post'])->name('post');
    Route::put('put', [NeracaPenyesuaianController::class, 'put'])->name('put');
    Route::delete('delete', [NeracaPenyesuaianController::class, 'delete'])->name('delete');
});

Route::prefix('laporan')->as('laporan.')->group(function () {
    Route::get('get-aset-barang-jualan', [AsetBarangJualanController::class, 'getAssetBarang'])->name('aset-barang-jualan');
    Route::get('get-rating-member', [RatingMemberController::class, 'getMember'])->name('rating-member');
});

Route::prefix('data-master')->as('dm.')->group(function () {
    Route::prefix('toko')->as('toko.')->group(function () {
        Route::prefix('group')->as('group.')->group(function () {
            Route::get('select', [TokoGroupController::class, 'select'])->name('select');
        });
    });
});

Route::get('/getdatauser', [UserController::class, 'getdatauser'])->name('master.getdatauser');
Route::get('/getpengeluaran', [PengeluaranController::class, 'getpengeluaran'])->name('master.getpengeluaran');
Route::get('/getpemasukan', [PemasukanController::class, 'getpemasukan'])->name('master.getpemasukan');
Route::get('/gethutang', [HutangController::class, 'getHutang'])->name('master.gethutang');
Route::get('/getpiutang', [PiutangController::class, 'getPiutang'])->name('master.getpiutang');
Route::get('/getmutasi', [MutasiController::class, 'getmutasi'])->name('master.getmutasi');
Route::get('/gettoko', [TokoController::class, 'gettoko'])->name('master.gettoko');
Route::get('/getmember', [MemberController::class, 'getmember'])->name('master.getmember');
Route::get('/getsupplier', [SupplierController::class, 'getsupplier'])->name('master.getsupplier');
Route::get('/getjenisbarang', [JenisBarangController::class, 'getjenisbarang'])->name('master.getjenisbarang');
Route::get('/getbrand', [BrandController::class, 'getbrand'])->name('master.getbrand');
Route::get('/getleveluser', [LevelUserController::class, 'getleveluser'])->name('master.getleveluser');
Route::get('/getlevelharga', [LevelHargaController::class, 'getlevelharga'])->name('master.getlevelharga');
Route::get('/getpromo', [PromoController::class, 'getpromo'])->name('master.getpromo');
Route::get('/getbarangs', [BarangController::class, 'getbarangs'])->name('master.getbarangs');
Route::get('/getplanorder', [PlanOrderController::class, 'getplanorder'])->name('master.getplanorder');

Route::get('/getBarang', [PengirimanBarangController::class, 'getHargaBarang'])->name('master.getBarangKirim');
Route::get('/get-rating-barang', [RatingBarangController::class, 'getRatingBarang'])->name('rekapitulasi.getRatingBarang');

Route::get('/neraca', [NeracaController::class, 'getNeraca'])->name('master.getNeraca');

Route::prefix('master')->as('master.')->group(function () {
    Route::get('levelUser', [MasterController::class, 'getLevelUser'])->name('levelUser');
    Route::get('toko', [MasterController::class, 'getToko'])->name('toko');
    Route::get('member', [MasterController::class, 'getMember'])->name('member');
    Route::get('barang', [MasterController::class, 'getBarang'])->name('barang');
    Route::get('jenis', [MasterController::class, 'getJenis'])->name('jenis');
    Route::get('jenismasuk', [MasterController::class, 'getJenismasuk'])->name('jenismasuk');
    Route::get('jenishutang', [MasterController::class, 'getJenishutang'])->name('jenishutang');
    Route::get('jenispiutang', [MasterController::class, 'getJenispiutang'])->name('jenispiutang');
    Route::get('suplier', [MasterController::class, 'getSuplier'])->name('suplier');
    Route::get('barang-pengiriman', [MasterController::class, 'getBarangPengiriman'])->name('barangKirim');
    Route::get('barang-kasir', [MasterController::class, 'getBarangKasir'])->name('barangKasir');
    Route::get('qr-barcode', [MasterController::class, 'getQrBarcode'])->name('qrbarcode');
    Route::get('kasbon', [MasterController::class, 'getKasbon'])->name('kasbon');
    Route::get('jenis-barang', [MasterController::class, 'getJenisBarang'])->name('jenisBarang');
    Route::get('brand', [MasterController::class, 'getBrand'])->name('brand');
});

Route::get('/rekapitulasi/laporan-penjualan', [LaporanPenjualanController::class, 'getSalesReport'])->name('rekapitulasi.laporan-penjualan');
Route::get('/get/total-kas/jenis-barang', [KasController::class, 'getTotalKasJenisBarang'])->name('total.kas.jenis-barang');
Route::get('/get/total-kas', [KasController::class, 'getTotalKas'])->name('total.kas');
Route::get('/get/total-kas-hirarki', [KasController::class, 'getTotalKasHirarki'])->name('total.kas-hirarki');
Route::get('/get/total-kas-besar', [KasController::class, 'getTotalKasBesar'])->name('total.kas-besar');
Route::get('/get/total-kas-kecil', [KasController::class, 'getTotalKasKecil'])->name('total.kas-kecil');

Route::prefix('rekapitulasi')->as('rekapitulasi.')->group(function () {
    Route::get('laporan-pembelian-barang', [LaporanPembelianBarangController::class, 'get'])->name('laporanPembelian');
    Route::get('laporan-pengiriman-barang', [LaporanPengirimanBarangController::class, 'get'])->name('laporanPengiriman');
});
