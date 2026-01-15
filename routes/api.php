<?php

use App\Http\Controllers\Auth\DashboardController;
use App\Http\Controllers\LaporanKeuangan\{ArusKasController, LabaRugiController, NeracaController, NeracaPenyesuaianController};
use App\Http\Controllers\Retur\{ReturMemberController, ReturSupplierController};
use App\Http\Controllers\TransaksiDigital\{DompetController, TransaksiNonFisikController};
use App\Http\Controllers\DataMaster\Entitas\{MemberController, SupplierController, TokoController, UserController};
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

Route::get('/arusKasir', [ArusKasController::class, 'getaruskas'])->name('master.aruskas.get');
Route::get('/labarugi', [LabaRugiController::class, 'getlabarugi'])->name('master.labarugi.get');

Route::get('/get-komparasi-toko', [DashboardController::class, 'getKomparasiToko'])->name('dashboard.komparasi');
Route::get('/get-rekapitulasi-penjualan', [DashboardController::class, 'laporan_kasir'])->name('master.index.kasir');
Route::get('/get-top-penjualan', [DashboardController::class, 'getBarangJual'])->name('dashboard.rating');
Route::get('/get-top-member', [DashboardController::class, 'getMember'])->name('dashboard.member');
Route::get('/get-omset', [DashboardController::class, 'getOmset'])->name('dashboard.omset');
Route::get('/get-laba-kotor', [DashboardController::class, 'getLabaKotor'])->name('dashboard.laba_kotor');
Route::get('/get-jumlah-transaksi', [DashboardController::class, 'getJumlahTransaksi'])->name('dashboard.jumlah_transaksi');
Route::get('/get-asset', [AsetBarangJualanController::class, 'getAssetBarang'])->name('dashboard.asset');
Route::get('/get-aset-retur', [AsetBarangReturController::class, 'getAsetBarangRetur'])->name('aset.retur');
Route::get('/get-ratingmember', [RatingMemberController::class, 'getMember'])->name('dashboard.ratingmember');

Route::get('/getpembelianbarang', [PembelianBarangController::class, 'getpembelianbarang'])->name('master.pembelian.get');
Route::get('/gettemppembelian', [PembelianBarangController::class, 'gettemppembelian'])->name('master.temppembelian.get');
Route::delete('/hapustemp', [PembelianBarangController::class, 'hapusTemp'])->name('master.temppembelian.hapus');

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
Route::get('/getstockbarang', [StockBarangController::class, 'getstockbarang'])->name('master.getstockbarang');
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

Route::prefix('neraca')->as('neraca.')->group(function () {
    Route::get('get', [NeracaPenyesuaianController::class, 'get'])->name('get');
    Route::post('post', [NeracaPenyesuaianController::class, 'post'])->name('post');
    Route::put('put', [NeracaPenyesuaianController::class, 'put'])->name('put');
    Route::delete('delete', [NeracaPenyesuaianController::class, 'delete'])->name('delete');
});

Route::prefix('log-aktivitas')->as('log.')->group(function () {
    Route::get('get', [LogAktivitasController::class, 'get'])->name('get');
    Route::get('get/log-event', [LogAktivitasController::class, 'getLogEvent'])->name('get.log-event');
});

Route::prefix('td')->as('td.')->group(function () {
    Route::prefix('dompet-kategori')->as('dompetKategori.')->group(function () {
        Route::get('get', [DompetKategoriController::class, 'get'])->name('get');
        Route::get('get-nama', [DompetKategoriController::class, 'getNama'])->name('getNama');
        Route::post('post', [DompetKategoriController::class, 'post'])->name('post');
        Route::put('put', [DompetKategoriController::class, 'put'])->name('put');
        Route::delete('delete', [DompetKategoriController::class, 'delete'])->name('delete');
    });
    Route::prefix('dompet-saldo')->as('dompetSaldo.')->group(function () {
        Route::get('get', [DompetSaldoController::class, 'get'])->name('get');
        Route::get('get-sisa-saldo', [DompetSaldoController::class, 'getSisaSaldo'])->name('getSisaSaldo');
        Route::get('get-saldo', [DompetSaldoController::class, 'getSaldo'])->name('getSaldo');
        Route::get('get-saldo-akhir', [DompetSaldoController::class, 'getSaldoAkhir'])->name('getSaldoAkhir');
        Route::get('get-total-kategori', [DompetSaldoController::class, 'getTotalPerKategori'])->name('getTotalPerKategori');
        Route::post('post', [DompetSaldoController::class, 'post'])->name('post');
        Route::put('put', [DompetSaldoController::class, 'put'])->name('put');
        Route::delete('delete', [DompetSaldoController::class, 'delete'])->name('delete');
    });
    Route::prefix('item-nonfisik')->as('itemNonfisik.')->group(function () {
        Route::get('get', [ItemNonFisikController::class, 'get'])->name('get');
        Route::get('get-nama', [ItemNonFisikController::class, 'getNama'])->name('getNama');
        Route::post('post', [ItemNonFisikController::class, 'post'])->name('post');
        Route::put('put', [ItemNonFisikController::class, 'put'])->name('put');
        Route::delete('delete', [ItemNonFisikController::class, 'delete'])->name('delete');
    });
    Route::prefix('item-nonfisik-harga')->as('itemNonfisikHarga.')->group(function () {
        Route::get('get-item-harga', [ItemNonFisikHargaController::class, 'getItemHarga'])->name('getItemHarga');
        Route::post('post', [ItemNonFisikHargaController::class, 'post'])->name('post');
        Route::put('put', [ItemNonFisikHargaController::class, 'put'])->name('put');
        Route::delete('delete', [ItemNonFisikHargaController::class, 'delete'])->name('delete');
    });
    Route::prefix('item-nonfisik-tipe')->as('itemNonfisikTipe.')->group(function () {
        Route::get('get', [ItemNonFisikTipeController::class, 'get'])->name('get');
        Route::get('get-nama', [ItemNonFisikTipeController::class, 'getNama'])->name('getNama');
        Route::post('post', [ItemNonFisikTipeController::class, 'post'])->name('post');
        Route::put('put', [ItemNonFisikTipeController::class, 'put'])->name('put');
        Route::delete('delete', [ItemNonFisikTipeController::class, 'delete'])->name('delete');
    });
    Route::prefix('penjualan-nonfisik')->as('penjualanNonfisik.')->group(function () {
        Route::get('get', [PenjualanNonFisikController::class, 'get'])->name('get');
        Route::get('detail', [PenjualanNonFisikController::class, 'getDetail'])->name('getDetail');
        Route::get('get-nota', [PenjualanNonFisikController::class, 'getNota'])->name('getNota');
        Route::post('post', [PenjualanNonFisikController::class, 'post'])->name('post');
        Route::put('put', [PenjualanNonFisikController::class, 'put'])->name('put');
        Route::delete('delete', [PenjualanNonFisikController::class, 'delete'])->name('delete');
    });
});

Route::prefix('retur')->as('retur.')->group(function () {
    Route::prefix('member')->as('member.')->group(function () {
        Route::get('get', [ReturMemberController::class, 'get'])->name('get');
        Route::get('detail', [ReturMemberController::class, 'getDetail'])->name('getDetail');
        Route::get('get-qrcode', [ReturMemberController::class, 'getQRCode'])->name('getQRCode');
        Route::get('get-harga', [ReturMemberController::class, 'getHargaBarang'])->name('getHargaBarang');
        Route::post('post', [ReturMemberController::class, 'post'])->name('post');
        Route::put('put', [ReturMemberController::class, 'put'])->name('put');
        Route::delete('delete', [ReturMemberController::class, 'delete'])->name('delete');
    });
    Route::prefix('supplier')->as('supplier.')->group(function () {
        Route::get('get', [ReturSupplierController::class, 'get'])->name('get');
        Route::get('detail', [ReturSupplierController::class, 'getDetail'])->name('getDetail');
        Route::get('get-data', [ReturSupplierController::class, 'getData'])->name('getData');
        Route::get('get-supplier', [ReturSupplierController::class, 'getSupplier'])->name('getSupplier');
        Route::get('get-retur-member', [ReturSupplierController::class, 'getReturMember'])->name('getReturMember');
        Route::get('get-qrcode', [ReturSupplierController::class, 'getQRCode'])->name('getQRCode');
        Route::get('get-harga', [ReturSupplierController::class, 'getHargaBarang'])->name('getHargaBarang');
        Route::post('post', [ReturSupplierController::class, 'post'])->name('post');
        Route::put('put', [ReturSupplierController::class, 'put'])->name('put');
        Route::put('verify', [ReturSupplierController::class, 'verify'])->name('verify');
    });
});

Route::prefix('stock-barang')->as('sb.')->group(function () {
    Route::prefix('batch')->as('batch.')->group(function () {
        Route::get('get', [StockBarangBatchController::class, 'get'])->name('get');
        Route::get('get-by-qrcode', [StockBarangBatchController::class, 'getByQR'])->name('getByQR');
    });
});

Route::prefix('distribusi')->as('distribusi.')->group(function () {
    Route::prefix('pengiriman')->as('pengiriman.')->group(function () {
        Route::get('get', [PengirimanBarangController::class, 'get'])->name('get');
        Route::get('progress', [PengirimanBarangController::class, 'progress'])->name('progress');
        Route::post('post', [PengirimanBarangController::class, 'post'])->name('post');
        Route::post('draft', [PengirimanBarangController::class, 'draft'])->name('draft');
        Route::post('verify', [PengirimanBarangController::class, 'verify'])->name('verify');
    });
});

Route::prefix('jurnal-keuangan')->as('jk.')->group(function () {
    Route::prefix('pemasukan')->as('pemasukan.')->group(function () {
        Route::get('get', [PemasukanController::class, 'get'])->name('get');
        Route::post('post', [PemasukanController::class, 'post'])->name('post');
        Route::delete('delete', [PemasukanController::class, 'delete'])->name('delete');
    });

    Route::prefix('pengeluaran')->as('pengeluaran.')->group(function () {
        Route::get('get', [PengeluaranController::class, 'get'])->name('get');
        Route::post('post', [PengeluaranController::class, 'post'])->name('post');
        Route::delete('delete', [PengeluaranController::class, 'delete'])->name('delete');
    });

    Route::prefix('hutang')->as('hutang.')->group(function () {
        Route::get('get', [HutangController::class, 'get'])->name('get');
        Route::get('detail', [HutangController::class, 'detail'])->name('detail');
        Route::post('post', [HutangController::class, 'post'])->name('post');
        Route::put('pay', [HutangController::class, 'pay'])->name('pay');
        Route::delete('delete', [HutangController::class, 'delete'])->name('delete');
    });

    Route::prefix('piutang')->as('piutang.')->group(function () {
        Route::get('get', [PiutangController::class, 'get'])->name('get');
        Route::get('detail', [PiutangController::class, 'detail'])->name('detail');
        Route::post('post', [PiutangController::class, 'post'])->name('post');
        Route::put('pay', [PiutangController::class, 'pay'])->name('pay');
        Route::delete('delete', [PiutangController::class, 'delete'])->name('delete');
    });

    Route::prefix('mutasi')->as('mutasi.')->group(function () {
        Route::get('get', [MutasiController::class, 'get'])->name('get');
        Route::post('post', [MutasiController::class, 'post'])->name('post');
        Route::delete('delete', [MutasiController::class, 'delete'])->name('delete');
    });
});

Route::prefix('transaksi-barang')->as('tb.')->group(function () {
    Route::prefix('kasir')->as('kasir.')->group(function () {
        Route::get('get', [TransaksiKasirController::class, 'get'])->name('get');
        Route::get('detail', [TransaksiKasirController::class, 'detail'])->name('detail');
        Route::post('post', [TransaksiKasirController::class, 'post'])->name('post');
        Route::get('get-harga', [TransaksiKasirController::class, 'getHarga'])->name('getHarga');
        Route::get('print/{id_kasir}', [TransaksiKasirController::class, 'print'])->name('print');
    });
});
