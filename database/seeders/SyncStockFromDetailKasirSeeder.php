<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\StockBarang;
use App\Models\DetailStockBarang;
use App\Models\DetailKasir;
use App\Models\DetailPembelianBarang;
use App\Models\Kasir;
use App\Models\User;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Label\Font\NotoSans;

class SyncStockFromDetailKasirSeeder extends Seeder
{
    public function run(): void
    {
        DB::beginTransaction();

        try {
            echo "\n========== [ Sinkronisasi qty_buy & qty_now Berdasarkan detail_pembelian_barang dan stock_barang ] ==========\n";

            $groupedDetailStocks = [];

            // STEP 1: Update qty_buy berdasarkan detail_pembelian_barang
            $pembelianList = DetailPembelianBarang::all();

            foreach ($pembelianList as $pembelian) {
                $detailStock = DetailStockBarang::where('id_detail_pembelian', $pembelian->id)->first();

                if (!$detailStock) {
                    echo "❌ Tidak ditemukan detail_stock_barang untuk id_detail_pembelian: {$pembelian->id}\n";
                    continue;
                }

                $detailStock->qty_buy = $pembelian->qty;
                $detailStock->save();

                echo "🔄 Update qty_buy: ID {$detailStock->id} | id_barang: {$detailStock->id_barang} | qty_buy: {$detailStock->qty_buy}\n";

                $groupedDetailStocks[$detailStock->id_barang][] = $detailStock;
            }

            // STEP 2: Redistribusi qty_now berdasarkan stock_barang.stock
            foreach ($groupedDetailStocks as $idBarang => $stocks) {
                $stockBarang = StockBarang::where('id_barang', $idBarang)->first();

                if (!$stockBarang) {
                    echo "❌ Tidak ditemukan stock_barang untuk id_barang: {$idBarang}\n";
                    continue;
                }

                $expectedQtyNow = $stockBarang->stock;

                echo "📦 ID_Barang $idBarang | stock: $expectedQtyNow\n";

                usort($stocks, fn($a, $b) => $a->created_at <=> $b->created_at);

                foreach ($stocks as $stok) {
                    if ($expectedQtyNow <= 0) {
                        $stok->qty_now = 0;
                        $stok->qty_out = $stok->qty_buy;
                    } else {
                        $alokasi = min($stok->qty_buy, $expectedQtyNow);
                        $stok->qty_now = $alokasi;
                        $stok->qty_out = $stok->qty_buy - $alokasi;
                        $expectedQtyNow -= $alokasi;
                    }

                    $stok->save();
                    echo "   ✔️ DetailStock ID {$stok->id} | qty_buy: {$stok->qty_buy} | qty_now: {$stok->qty_now} | qty_out: {$stok->qty_out}\n";
                }
            }

            // STEP 3: Buat ulang detail_kasir berdasarkan partisi stok
            echo "\n========== [ STEP 3: Bangun ulang DetailKasir berdasarkan distribusi stok ] ==========\n";

            $kasirItems = DetailKasir::with('kasir')->orderBy('id')->get();

            foreach ($kasirItems as $item) {
                $kasir = $item->kasir;
                $tglTransaksi = \Carbon\Carbon::parse($kasir->tgl_transaksi);
                $user = User::find($kasir->id_users);

                // Simpan data sebelum dihapus
                $idBarang = $item->id_barang;
                $hargaBarang = $item->harga;
                $qtyTotal = $item->qty;

                // Hapus yang lama
                $item->delete();

                $this->storeDetailKasirPartitioned($kasir, $idBarang, $qtyTotal, $hargaBarang, $tglTransaksi, $user);
            }

            DB::commit();
            echo "\n🎉 Sukses! Semua qty_buy, qty_now, qty_out, dan detail_kasir telah disesuaikan ulang.\n";
        } catch (\Throwable $th) {
            DB::rollBack();
            echo "\n💥 Gagal: " . $th->getMessage() . "\n";
        }
    }

    protected function storeDetailKasirPartitioned($kasir, $id_barang_final, $qtyTotal, $harga_barang, $tglTransaksi, $user)
    {
        $stocks = DetailStockBarang::where('id_barang', $id_barang_final)
            ->whereColumn('qty_out', '<', 'qty_buy')
            ->orderBy('created_at')
            ->get();

        $remainingQty = $qtyTotal;
        $counter = 1;
        $subCounter = 1;

        foreach ($stocks as $stock) {
            $available = $stock->qty_buy - $stock->qty_out;
            if ($available <= 0) continue;

            $ambilQty = min($available, $remainingQty);
            $remainingQty -= $ambilQty;

            // Buat QR
            $tglFormat = $tglTransaksi->format('dmY');
            $qrValue = "{$tglFormat}TK{$user->id_toko}MM{$kasir->id_member}ID{$kasir->id}-{$counter}-{$subCounter}";
            $qrPath = "qrcodes/trx_kasir/{$kasir->id}-{$counter}-{$subCounter}.png";
            $qrFullPath = storage_path("app/public/" . $qrPath);

            if (!file_exists(dirname($qrFullPath))) mkdir(dirname($qrFullPath), 0755, true);

            $qr = QrCode::create($qrValue)
                ->setEncoding(new Encoding('UTF-8'))
                ->setSize(200)
                ->setMargin(10);

            $writer = new PngWriter();
            $writer->write($qr, null, Label::create($qrValue)->setFont(new NotoSans(12)))
                ->saveToFile($qrFullPath);

            if (!$stock->detailPembelian || !$stock->detailPembelian->qrcode) {
                throw new \Exception("QR code pembelian tidak ditemukan untuk ID detail_pembelian {$stock->id_detail_pembelian}");
            }

            DetailKasir::create([
                'id_kasir' => $kasir->id,
                'id_barang' => $id_barang_final,
                'id_supplier' => $stock->id_supplier,
                'id_detail_pembelian' => $stock->id_detail_pembelian,
                'qty' => $ambilQty,
                'harga' => $harga_barang,
                'diskon' => 0,
                'total_harga' => $ambilQty * $harga_barang,
                'qrcode' => $qrValue,
                'qrcode_path' => $qrPath,
                'hpp_jual' => 0,
                'qrcode_pembelian' => $stock->detailPembelian?->qrcode ?? null,
            ]);

            // Update stock
            $stock->qty_out += $ambilQty;
            $stock->qty_now = $stock->qty_buy - $stock->qty_out;
            $stock->save();

            echo "✅ ID_DetailStock {$stock->id} - Ambil {$ambilQty}, Sisa Qty: {$remainingQty}\n";

            $subCounter++;
            if ($remainingQty <= 0) break;
        }
        $totalAvailable = $qtyTotal - $remainingQty;
        echo "🧮 Diminta: $qtyTotal | Tersedia: $totalAvailable | Kurang: $remainingQty\n";

        if ($remainingQty > 0) {
            throw new \Exception("Qty melebihi total stok tersedia untuk barang ID $id_barang_final");
        }
    }
}
