<?php

namespace App\Http\Controllers\TransaksiBarang;

use App\Http\Controllers\Controller;
use App\Models\DetailKasir;
use App\Models\DetailStockBarang;
use App\Models\DetailToko;
use App\Models\Kasir;
use App\Models\StockBarang;
use Illuminate\Http\Request;

class PengembalianController extends Controller
{
    private array $menu = [];

    public function __construct()
    {
        $this->menu;
        $this->title = [
            'Pengembalian Barang',
        ];
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'id_toko' => 'required|integer',
        ]);

        $id = $request->id;
        $idToko = $request->id_toko;

        $detailKasir = DetailKasir::find($id);

        if (!$detailKasir) {
            return response()->json(['message' => 'Data detail kasir tidak ditemukan.'], 404);
        }

        $idKasir = $detailKasir->id_kasir;

        if ($idToko != 1) {
            $detailToko = DetailToko::where('qrcode', $detailKasir->detailPembelian->qrcode)
                ->where('id_toko', $idToko)
                ->first();

            if ($detailToko) {
                $detailToko->qty += $detailKasir->qty;
                $detailToko->save();
            }
        } else {
            $detailPembelian = $detailKasir->detailPembelian;

            if ($detailPembelian) {
                $stockBarang = StockBarang::where('id_barang', $detailPembelian->id_barang)->first();

                if ($stockBarang) {
                    $stockBarang->stock += $detailKasir->qty;
                    $stockBarang->save();

                    $detailStockBarang = DetailStockBarang::where('id_detail_pembelian', $detailPembelian->id)
                        ->where('id_stock', $stockBarang->id) // <- dari StockBarang
                        ->where('id_barang', $detailPembelian->id_barang)
                        ->first();

                    if ($detailStockBarang) {
                        $detailStockBarang->qty_now += $detailKasir->qty;
                        $detailStockBarang->qty_out -= $detailKasir->qty;
                        $detailStockBarang->save();
                    }
                }
            }
        }

        $jumlahDetailKasir = DetailKasir::where('id_kasir', $idKasir)->count();

        if ($jumlahDetailKasir == 1) {
            $detailKasir->delete();

            $kasir = Kasir::find($idKasir);
            if ($kasir) {
                $kasir->delete();
            }

            return response()->json(['message' => 'Detail kasir dan kasir berhasil dihapus.']);
        } else {
            $kasir = Kasir::find($idKasir);
            if ($kasir) {
                $kasir->total_item -= $detailKasir->qty;
                $kasir->total_nilai -= ($detailKasir->qty * $detailKasir->harga);
                $kasir->kembalian = $kasir->jml_bayar - $kasir->total_nilai;
                $kasir->save();
            }

            $detailKasir->delete();

            return response()->json(['message' => 'Detail kasir berhasil dihapus dan kasir diperbarui.']);
        }
    }


    // public function delete(Request $request)
    // {
    //     $request->validate([
    //         'id' => 'required|integer',
    //         'id_toko' => 'required|integer',
    //     ]);

    //     $id = $request->id;
    //     $idToko = $request->id_toko;

    //     $detailKasir = DetailKasir::find($id);

    //     if (!$detailKasir) {
    //         return response()->json(['message' => 'Data detail kasir tidak ditemukan.'], 404);
    //     }

    //     $idKasir = $detailKasir->id_kasir;

    //     $detailToko = DetailToko::where('qrcode', $detailKasir->detailPembelian->qrcode)
    //         ->where('id_toko', $idToko)
    //         ->first();

    //     if ($detailToko) {
    //         $detailToko->qty += $detailKasir->qty;
    //         $detailToko->save();
    //     }

    //     $jumlahDetailKasir = DetailKasir::where('id_kasir', $idKasir)->count();

    //     if ($jumlahDetailKasir == 1) {
    //         $detailKasir->delete();

    //         $kasir = Kasir::find($idKasir);
    //         if ($kasir) {
    //             $kasir->delete();
    //         }

    //         return response()->json(['message' => 'Detail kasir dan kasir berhasil dihapus.']);
    //     } else {
    //         $kasir = Kasir::find($idKasir);
    //         if ($kasir) {
    //             $kasir->total_item -= $detailKasir->qty;
    //             $kasir->total_nilai -= ($detailKasir->qty * $detailKasir->harga);
    //             $kasir->kembalian = $kasir->jml_bayar - $kasir->total_nilai;
    //             $kasir->save();
    //         }

    //         $detailKasir->delete();

    //         return response()->json(['message' => 'Detail kasir berhasil dihapus dan kasir diperbarui.']);
    //     }
    // }

}
