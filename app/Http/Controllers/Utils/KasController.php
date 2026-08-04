<?php

namespace App\Http\Controllers\Utils;

use App\Enums\LabelKas;
use App\Helpers\RupiahGenerate;
use App\Http\Controllers\Controller;
use App\Models\JenisBarang;
use App\Models\Kas;
use App\Models\Toko;
use App\Services\KasService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KasController extends Controller
{
    use ApiResponse;

    protected $service;

    private array $menu = [];

    public function __construct(KasService $service)
    {
        $this->service = $service;
        $this->menu;
        $this->title = [
            'Total Kas',
        ];
    }

    public function getTotalKas(Request $request)
    {
        $idTokoLogin = $request->input('toko_id') ?? $request->input('id_toko');

        $filterTipe = $request->input('tipe');

        if ($filterTipe && ! in_array($filterTipe, ['kecil', 'besar'])) {
            return $this->error(400, 'Parameter tipe harus kecil atau besar');
        }

        $filterDompet = $request->input('dompet');

        try {
            $toko = Toko::findOrFail($idTokoLogin);
            $isParent = is_null($toko->parent_id);

            $parentName = null;
            if (! $isParent) {
                $parent = Toko::find($toko->parent_id);
                $parentName = $parent ? $parent->nama : null;
            }

            $data = [];

            $jenisBarangList = JenisBarang::select('id', 'nama_jenis_barang')->get();
            $jenisBarangList->push((object) [
                'id' => 0,
                'nama_jenis_barang' => 'Dompet Digital',
            ]);

            $counter = 1;

            foreach ($jenisBarangList as $jenisBarang) {
                if ($filterDompet === 'false' && $jenisBarang->id == 0) {
                    continue;
                }

                $tipeList = $filterTipe ? [$filterTipe] : ['kecil', 'besar'];

                foreach ($tipeList as $tipeKas) {

                    $kas = Kas::where('toko_id', $idTokoLogin)
                        ->where('jenis_barang_id', $jenisBarang->id)
                        ->where('tipe_kas', $tipeKas)
                        ->first();

                    $kasId = $kas ? $kas->id : "NEW-{$counter}";
                    $saldo = $kas ? $kas->saldo : 0;

                    if ($tipeKas === 'besar') {
                        $labelToko = $isParent ? 'Owner' : ($parentName ?? 'Owner');
                        $labelKas = "Kas Besar ({$labelToko})";
                    } else {
                        $labelKas = "Kas Kecil ({$toko->nama})";
                    }

                    $data[] = [
                        'id' => $kas ? $kas->id : "{$kasId} - {$jenisBarang->nama_jenis_barang}",
                        'jenis_id' => $jenisBarang->id,
                        'tipe_kas' => $tipeKas,
                        'saldo_kas' => $saldo,
                        'text' => "{$labelKas} - {$jenisBarang->nama_jenis_barang} - (Rp ".number_format($saldo, 0, ',', '.').')',
                    ];

                    $counter++;
                }
            }

            return $this->success($data, 200, 'Data kas berhasil diambil');
        } catch (\Throwable $e) {
            return $this->error(500, 'Terjadi kesalahan saat mengambil data', [
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    public function getTotalKasHirarki(Request $request)
    {
        try {
            $idTokoLogin = $request->input('id_toko');
            $mode        = $request->input('mode'); // pengirim / penerima
            $kasAsalId   = $request->input('kas_asal_id'); // kas yang dipilih di select lain

            if (!$mode || !in_array($mode, ['pengirim', 'penerima'])) {
                return $this->error(400, 'Parameter mode harus pengirim atau penerima');
            }

            $toko = Toko::findOrFail($idTokoLogin);

            // Ambil daftar toko anak yang BUKAN Mitra (hanya cabang reguler)
            $childNonMitraIds = Toko::where('parent_id', $idTokoLogin)
                ->where('mitra', false)
                ->pluck('id');

            // =========================================================
            // LOGIKA HIRARKI TRANSFER KAS
            // =========================================================
            if ($toko->mitra) {
                // 🔴 TOKO MITRA: Terisolasi penuh (Pengirim & Penerima HANYA Tokonya Sendiri)
                $allowedTokoIds = collect([$idTokoLogin]);

            } else {
                $isParent = is_null($toko->parent_id);

                if ($mode === 'pengirim') {
                    // 🟢 MODE PENGIRIM (Parent / Child Non-Mitra): HANYA Kas Toko Sendiri
                    $allowedTokoIds = collect([$idTokoLogin]);

                } else {
                    // 🔵 MODE PENERIMA
                    if ($isParent) {
                        // Parent Penerima: Kas Toko Sendiri + Kas Anak-Anak Cabang (Non-Mitra)
                        $allowedTokoIds = collect([$idTokoLogin])->merge($childNonMitraIds);
                    } else {
                        // Child Non-Mitra Penerima: Kas Toko Sendiri + Kas Parent
                        $allowedTokoIds = collect([$idTokoLogin, $toko->parent_id]);
                    }
                }
            }

            // Ambil jenis barang + Dompet Digital
            $jenisBarangList = JenisBarang::select('id', 'nama_jenis_barang')->get();
            $jenisBarangList->push((object)[
                'id' => 0,
                'nama_jenis_barang' => 'Dompet Digital',
            ]);

            // Eager Loading dengan filter toko yang diperbolehkan
            $kasList = Kas::with(['toko.parent'])
                ->whereIn('toko_id', $allowedTokoIds)
                ->get();

            $data = [];

            foreach ($jenisBarangList as $jenis) {
                foreach (['kecil', 'besar'] as $tipeKas) {

                    $filteredKas = $kasList->where('jenis_barang_id', $jenis->id)
                        ->where('tipe_kas', $tipeKas);

                    foreach ($filteredKas as $k) {
                        // Exclude jika kasAsalId di-passed dari request
                        if ($kasAsalId && $k->id == $kasAsalId) {
                            continue;
                        }

                        $labelKas = ($tipeKas === 'besar')
                            ? "Kas Besar (" . ($k->toko->parent_id ? ($k->toko->parent->singkatan ?? $k->toko->parent->nama) : "Owner") . ")"
                            : "Kas Kecil ({$k->toko->singkatan})";

                        $data[] = [
                            'id'        => $k->id,
                            'jenis_id'  => $jenis->id,
                            'tipe_kas'  => $tipeKas,
                            'saldo_kas' => $k->saldo,
                            'text'      => "{$labelKas} - {$jenis->nama_jenis_barang} - (" . RupiahGenerate::build($k->saldo) . ")"
                        ];
                    }
                }
            }

            return $this->success(array_values($data), 200, "Data kas berhasil diambil");

        } catch (\Throwable $e) {
            return $this->error(500, 'Terjadi kesalahan saat mengambil data', [
                'error_message' => $e->getMessage(),
                'line'          => $e->getLine(),
            ]);
        }
    }

    public function getTotalKasJenisBarang(Request $request)
    {
        try {
            // Ambil semua jenis barang dari tabel
            $jenisBarangList = DB::table('jenis_barang')->whereNull('deleted_at')->select('id', 'nama_jenis_barang')->get();

            $data = [];

            foreach ($jenisBarangList as $jenisBarang) {
                $kasBesar = $this->service->getKasJenisBarang(0, $jenisBarang->id);
                $kasKecil = $this->service->getKasJenisBarang(1, $jenisBarang->id);

                $data[] = [
                    'jenis_id' => $jenisBarang->id,
                    'jenis_nama' => $jenisBarang->nama_jenis_barang,
                    'kas' => [
                        [
                            'id' => LabelKas::KAS_BESAR->value.'/'.$kasBesar['total'],
                            'text' => LabelKas::KAS_BESAR->label().' - '.$kasBesar['format'],
                        ],
                        [
                            'id' => LabelKas::KAS_KECIL->value.'/'.$kasKecil['total'],
                            'text' => LabelKas::KAS_KECIL->label().' - '.$kasKecil['format'],
                        ],
                    ],
                ];
            }

            return $this->success($data, 200, "{$this->title[0]} berhasil diambil");
        } catch (\Throwable $e) {
            return $this->error(500, 'Terjadi kesalahan saat mengambil data', [
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    public function getTotalKasBesar(Request $request)
    {
        $idTokoLogin = $request->input('id_toko');

        try {
            $data = $this->service->getKasBesar($idTokoLogin);

            return $this->success($data, 200, "{$this->title[0]} Besar berhasil diambil");
        } catch (\Throwable $e) {

            return $this->error(500, 'Terjadi kesalahan saat mengambil data', [
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    public function getTotalKasKecil(Request $request)
    {
        $idTokoLogin = $request->input('id_toko');

        try {
            $data = $this->service->getKasKecil($idTokoLogin);

            return $this->success($data, 200, "{$this->title[0]} Kecil berhasil diambil");
        } catch (\Throwable $e) {

            return $this->error(500, 'Terjadi kesalahan saat mengambil data', [
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
