<?php

namespace App\Http\Controllers;

use App\Enums\LabelKas;
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
        $idTokoLogin = $request->input('id_toko');

        try {
            $toko = Toko::findOrFail($idTokoLogin);

            $data = [];

            if ($toko->tipe_kas === 'barang') {
                $jenisBarangList = DB::table('jenis_barang')->whereNull('deleted_at')->select('id', 'nama_jenis_barang')->get();

                $jenisBarangList->push((object)[
                    'id' => 0,
                    'nama_jenis_barang' => 'Dompet Digital',
                ]);

                foreach ($jenisBarangList as $jenisBarang) {
                    $kasBesar = $this->service->getKasJenisBarang(0, $jenisBarang->id);
                    $kasKecil = $this->service->getKasJenisBarang(1, $jenisBarang->id);

                    $data[] = [
                        'id' => LabelKas::KAS_BESAR->value . '/' . $kasBesar['total'],
                        'jenis_id' => $jenisBarang->id,
                        'text' => $jenisBarang->nama_jenis_barang . ' - ' . LabelKas::KAS_BESAR->label() . ' - ' . $kasBesar['format'],
                    ];
                    $data[] = [
                        'id' => LabelKas::KAS_KECIL->value . '/' . $kasKecil['total'],
                        'jenis_id' => $jenisBarang->id,
                        'text' => $jenisBarang->nama_jenis_barang . ' - ' . LabelKas::KAS_KECIL->label() . ' - ' . $kasKecil['format'],
                    ];
                }
            } else {
                // Default tipe_kas = umum
                $kasBesar = $this->service->getKasBesar($idTokoLogin);
                $kasKecil = $this->service->getKasKecil($idTokoLogin);

                $data = [
                    [
                        'id' => LabelKas::KAS_BESAR->value . '/' . $kasBesar['total'],
                        'text' => LabelKas::KAS_BESAR->label() . ' - ' . $kasBesar['format'],
                    ],
                    [
                        'id' => LabelKas::KAS_KECIL->value . '/' . $kasKecil['total'],
                        'text' => LabelKas::KAS_KECIL->label() . ' - ' . $kasKecil['format'],
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
                            'id' => LabelKas::KAS_BESAR->value . '/' . $kasBesar['total'],
                            'text' => LabelKas::KAS_BESAR->label() . ' - ' . $kasBesar['format'],
                        ],
                        [
                            'id' => LabelKas::KAS_KECIL->value . '/' . $kasKecil['total'],
                            'text' => LabelKas::KAS_KECIL->label() . ' - ' . $kasKecil['format'],
                        ],
                    ]
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
