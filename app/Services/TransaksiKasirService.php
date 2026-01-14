<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\Kas;
use App\Models\TransaksiKasir;
use App\Models\TransaksiKasirDetail;
use App\Models\TransaksiKasirHarian;
use App\Models\Toko;
use App\Models\JenisBarang;
use App\Services\KasService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TransaksiKasirService
{
    public function createTransaksiKasir(array $data, array $details)
    {
        return DB::transaction(function () use ($data, $details) {

            // 1. SIMPAN HEADER
            $transaksi = TransaksiKasir::create($data);

            // 2. SIMPAN DETAIL
            $savedDetails = [];
            foreach ($details as $row) {

                // Jenis barang WAJIB ambil dari master Barang
                if (!empty($row['barang_id'])) {
                    $barang = Barang::find($row['barang_id']);
                    $row['jenis_barang_id'] = $barang?->jenis_barang_id;
                }

                $row['transaksi_kasir_id'] = $transaksi->id;

                $detail = TransaksiKasirDetail::create([
                    'transaksi_kasir_id' => $row['transaksi_kasir_id'],
                    'barang_id' => $row['barang_id'] ?? null,
                    'jenis_barang_id' => $row['jenis_barang_id'],
                    'qrcode' => $row['qrcode'] ?? null,
                    'qty' => $row['qty'] ?? 0,
                    'harga' => $row['harga'] ?? 0,
                    'stock_barang_batch_id' => $row['stock_barang_batch_id'] ?? null,
                    'diskon' => $row['diskon'] ?? 0,
                    'retur_qty' => $row['retur_qty'] ?? 0,
                    'retur_by' => $row['retur_by'] ?? null,
                ]);

                $savedDetails[] = $detail;
            }

            // 3. GROUP BY jenis_barang_id
            $grouped = collect($savedDetails)->groupBy(fn($d) => $d->jenis_barang_id);

            foreach ($grouped as $jenisId => $rows) {

                $total_qty = $rows->sum(fn($r) => $r->qty);
                $total_nominal = $rows->sum(fn($r) => ($r->qty * $r->harga) - $r->diskon);

                $tanggal = Carbon::parse($transaksi->tanggal)->toDateString();

                // Rekap harian unique by toko + tanggal + jenis_barang_id
                $rekap = TransaksiKasirHarian::firstOrNew([
                    'toko_id' => $transaksi->toko_id,
                    'tanggal' => $tanggal,
                    'jenis_barang_id' => $jenisId
                ]);

                if (!$rekap->exists) {
                    $rekap->total_transaksi = 1;
                    $rekap->total_qty = $total_qty;
                    $rekap->total_nominal = $total_nominal;
                    $rekap->total_diskon = 0;
                    $rekap->total_bayar = $total_nominal;
                } else {
                    $rekap->total_transaksi += 1;
                    $rekap->total_qty += $total_qty;
                    $rekap->total_nominal += $total_nominal;
                    $rekap->total_bayar += $total_nominal;
                }

                $rekap->updated_by = $transaksi->created_by;
                $rekap->save();

                // 4. KIRIM KE KAS
                $this->syncKasForRekap($rekap);
            }

            return $transaksi;
        });
    }


    /**
     * Sinkronisasi ke Kas — tanpa DOMPET DIGITAL
     */
    protected function syncKasForRekap(TransaksiKasirHarian $rekap)
    {
        $toko = Toko::find($rekap->toko_id);

        if (!$toko) {
            throw new \Exception("Toko tidak ditemukan.");
        }

        // Kas kecil khusus jenis_barang_id > 0
        $kas = Kas::where('toko_id', $toko->id)
            ->where('tipe_kas', 'kecil')
            ->where('jenis_barang_id', $rekap->jenis_barang_id)
            ->first();

        if (!$kas) {
            throw new \Exception(
                "Kas kecil untuk jenis_barang_id {$rekap->jenis_barang_id} belum dibuat."
            );
        }

        $jenisNama = JenisBarang::find($rekap->jenis_barang_id)?->nama_jenis_barang ?? "-";

        KasService::in(
            toko_id: $toko->id,
            jenis_barang_id: $rekap->jenis_barang_id,
            tipe_kas: $kas->tipe_kas,
            qty: $rekap->total_qty,
            nominal: $rekap->total_bayar,
            total_nominal: $rekap->total_bayar,
            item: 'kecil',
            kategori: 'Pendapatan Umum',
            keterangan: "Pendapatan Harian (Kas {$jenisNama})",
            sumber: $rekap
        );
    }
}
