<?php

namespace App\Http\Controllers\DataMaster\ManajemenBarang;

use App\Helpers\ActivityLogger;
use App\Helpers\AssetGenerate;
use App\Helpers\BarcodeGenerator;
use App\Helpers\QrGenerator;
use App\Helpers\TextGenerate;
use App\Http\Controllers\Controller;
use App\Imports\BarangImport;
use App\Models\Barang;
use App\Models\Brand;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Milon\Barcode\Facades\DNS1DFacade;

class BarangController extends Controller
{
    use ApiResponse;

    private array $menu = [];

    public function __construct()
    {
        $this->menu;
        $this->title = [
            'Data Barang',
            'Tambah Data',
            'Edit Data',
        ];
    }

    public function getbarangs(Request $request)
    {
        $meta['orderBy'] = $request->ascending ? 'asc' : 'desc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 200 ? $request->limit : 30;

        $query = Barang::query();

        $query->with(['jenis', 'brand'])->orderBy('id', $meta['orderBy']);

        if (! empty($request['search'])) {
            $searchTerm = trim(strtolower($request['search']));

            $query->where(function ($query) use ($searchTerm) {
                // Pencarian pada kolom langsung
                $query->orWhereRaw('LOWER(barcode) LIKE ?', ["%$searchTerm%"]);
                $query->orWhereRaw('LOWER(nama) LIKE ?', ["%$searchTerm%"]);
                $query->orWhereHas('jenis', function ($subquery) use ($searchTerm) {
                    $subquery->whereRaw('LOWER(nama_jenis_barang) LIKE ?', ["%$searchTerm%"]);
                });
                $query->orWhereHas('brand', function ($subquery) use ($searchTerm) {
                    $subquery->whereRaw('LOWER(nama_brand) LIKE ?', ["%$searchTerm%"]);
                });
            });
        }

        if ($request->has('jenis_barang')) {
            $jenis = $request->jenis_barang;
            $query->whereHas('jenis', function ($q) use ($jenis) {
                $q->where('id', $jenis);
            });
        }

        $data = $query->paginate($meta['limit']);

        $paginationMeta = [
            'total' => $data->total(),
            'per_page' => $data->perPage(),
            'current_page' => $data->currentPage(),
            'total_pages' => $data->lastPage(),
        ];

        $data = [
            'data' => $data->items(),
            'meta' => $paginationMeta,
        ];

        if (empty($data['data'])) {
            return response()->json([
                'status_code' => 400,
                'errors' => true,
                'message' => 'Tidak ada data',
            ], 400);
        }

        $mappedData = collect($data['data'])->map(function ($item) {
            return [
                'id' => $item['id'],
                'garansi' => $item->garansi === 1 ? 'Aktif' : 'Tidak Ada',
                'status_garansi' => $item->garansi,
                'barcode' => $item->barcode,
                'qrcode' => $item->qrcode,
                'barcode_path' => AssetGenerate::build("barcodes/{$item->barcode}.png"),
                'qrcode_path' => AssetGenerate::build("qrcodes/barang/{$item->qrcode}.png"),
                'gambar' => $item->gambar,
                'nama_barang' => TextGenerate::smartTail($item->nama),
                'nama_barang_long' => $item->nama,
                'nama_jenis_barang' => optional($item['jenis'])->nama_jenis_barang ?? 'Tidak Ada',
                'jenis_barang_id' => optional($item['jenis'])->id ?? null,
                'nama_brand' => optional($item['brand'])->nama_brand ?? 'Tidak Ada',
                'brand_id' => optional($item['brand'])->id ?? null,
            ];
        });

        return response()->json([
            'data' => $mappedData,
            'status_code' => 200,
            'errors' => true,
            'message' => 'Sukses',
            'pagination' => $data['meta'],
        ], 200);
    }

    public function index()
    {
        $menu = [$this->title[0], $this->label[0]];

        return view('master.barang.index', compact('menu'));
    }

    public function getBrandsByJenis(Request $request)
    {
        // Validasi bahwa id_jenis_barang dikirim melalui AJAX
        $request->validate([
            'id_jenis_barang' => 'required|exists:jenis_barang,id',
        ]);

        // Ambil semua Brand yang memiliki id_jenis_barang sesuai dengan yang dipilih
        $brands = Brand::where('id_jenis_barang', $request->id_jenis_barang)->get();

        // Kembalikan data dalam bentuk JSON
        return response()->json($brands);
    }

    public function post(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'nama_barang' => 'required|string|max:255',
            'jenis_barang_id' => 'required|exists:jenis_barang,id',
            'brand_id' => 'required|exists:brand,id',
            'barcode' => 'nullable|string|max:255|unique:barang,barcode',
            'garansi' => 'nullable|boolean',
            'gambar' => 'nullable|image|max:2048',
        ], [
            'user_id.required' => 'User wajib diisi.',
            'user_id.integer' => 'User tidak valid.',

            'nama_barang.required' => 'Nama barang wajib diisi.',
            'nama_barang.max' => 'Nama barang maksimal 255 karakter.',

            'jenis_barang_id.required' => 'Jenis barang wajib dipilih.',
            'jenis_barang_id.exists' => 'Jenis barang tidak ditemukan.',

            'brand_id.required' => 'Brand wajib dipilih.',
            'brand_id.exists' => 'Brand tidak ditemukan.',

            'barcode.unique' => 'Barcode sudah digunakan, silakan gunakan yang lain.',

            'gambar.image' => 'File gambar harus berupa gambar (jpg/png/dll).',
            'gambar.max' => 'Ukuran gambar maksimal 2MB.',
        ]);

        try {
            DB::beginTransaction();

            // =========================
            // BARCODE (PAKAI HELPER)
            // =========================
            $barcodeValue = $request->barcode
                ?: BarcodeGenerator::generateIncrementalBarcode();

            $barcodePath = BarcodeGenerator::generateImage($barcodeValue);

            // =========================
            // GAMBAR
            // =========================
            $gambarPath = null;

            if ($request->hasFile('gambar')) {
                $gambarFile = $request->file('gambar');

                $fileName = time().'_'.uniqid().'.'.$gambarFile->getClientOriginalExtension();

                $gambarPath = $gambarFile->storeAs('barang/gambar', $fileName, 'public');
            }

            // =========================
            // INSERT DATA
            // =========================
            $data = Barang::create([
                'created_by' => $request->user_id,
                'nama' => $request->nama_barang,
                'jenis_barang_id' => $request->jenis_barang_id,
                'gambar' => $gambarPath,
                'barcode' => $barcodeValue,
                'qrcode' => QrGenerator::generate('QR-', 'qrcodes/barang/')['value'],
                'brand_id' => $request->brand_id,
                'garansi' => $request->garansi ?? 0,
            ]);

            // =========================
            // LOG
            // =========================
            $this->saveLogAktivitas(
                logName: $this->title[0],
                subjectType: Barang::class,
                subjectId: $data->id,
                event: 'Tambah Data',
                properties: [
                    'changes' => [
                        'new' => Arr::except($data->toArray(), ['id', 'created_at', 'updated_at', 'deleted_at']),
                    ],
                ],
                description: "Barang {$data->nama} (ID {$data->id}) ditambahkan.",
                userId: $request->user_id ?? null,
                message: $request->message ?? '(Sistem) Penambahan barang baru.'
            );

            DB::commit();

            return $this->success(null, 200, 'Data berhasil disimpan!');
        } catch (ValidationException $e) {
            DB::rollBack();

            // Ambil semua pesan error jadi array sederhana
            $errors = collect($e->errors())->map(function ($item) {
                return $item[0];
            });

            return $this->error(422, 'Data belum lengkap atau tidak valid.', $errors);

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->error(
                500,
                'Terjadi kesalahan saat menyimpan data. Silakan coba lagi atau hubungi admin.'
            );
        }
    }

    public function downloadQrCode(Barang $barang)
    {
        // Pastikan file barcode ada
        if (Storage::exists($barang->barcode_path)) {
            // Nama file download berdasarkan nama barang
            $filename = "{$barang->nama_barang}.png";

            return Storage::download($barang->barcode_path, $filename);
        }

        ActivityLogger::log('Download QrCode', []);

        return redirect()->back()->with('error', 'Barcode tidak ditemukan.');
    }

    public function update(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|integer',
                'user_id' => 'required|integer',
                'brand_id' => 'required|integer',
                'jenis_barang_id' => 'required|integer',
                'nama_barang' => 'required|string|max:255',
                'barcode' => 'nullable|string|max:255|unique:barang,barcode,'.$request->id,
                'gambar' => 'nullable',
                'garansi' => 'nullable|boolean',
            ]);

            DB::beginTransaction();

            $data = Barang::findOrFail($request->id);

            $originalData = Arr::except($data->toArray(), ['id', 'created_at', 'updated_at', 'deleted_at']);

            $barcodeValue = $request->barcode ?: $this->generateIncrementalBarcode();

            $oldBarcode = $data->barcode;

            if ($barcodeValue !== $oldBarcode) {

                // Hapus barcode lama jika ada
                $oldBarcodePath = "barcodes/{$oldBarcode}.png";

                if ($oldBarcode && Storage::disk('public')->exists($oldBarcodePath)) {
                    Storage::disk('public')->delete($oldBarcodePath);
                }

                // Generate barcode baru
                $barcodeFilename = "{$barcodeValue}.png";
                $barcodeRelativePath = "barcodes/{$barcodeFilename}";

                if (! Storage::disk('public')->exists($barcodeRelativePath)) {

                    $barcodeImage = DNS1DFacade::getBarcodePNG($barcodeValue, 'C128', 3, 100);

                    if (! $barcodeImage) {
                        throw new \Exception('Gagal membuat barcode PNG dari base64');
                    }

                    Storage::disk('public')->put(
                        $barcodeRelativePath,
                        base64_decode($barcodeImage)
                    );
                }
            }

            $gambarPath = $data->gambar; // default pakai lama

            if ($request->hasFile('gambar')) {

                // Hapus file lama jika ada
                if ($data->gambar && Storage::disk('public')->exists($data->gambar)) {
                    Storage::disk('public')->delete($data->gambar);
                }

                $gambarFile = $request->file('gambar');
                $fileName = time().'_'.uniqid().'.'.$gambarFile->getClientOriginalExtension();

                $gambarPath = $gambarFile->storeAs('barang/gambar', $fileName, 'public');
            }

            $updateData = [
                'updated_by' => $request->user_id,
                'brand_id' => $request->brand_id,
                'jenis_barang_id' => $request->jenis_barang_id,
                'nama' => $request->nama_barang,
                'barcode' => $barcodeValue,
                'gambar' => $gambarPath,
                'garansi' => $request->garansi ?? 0,
            ];

            $changedData = [];
            foreach ($updateData as $key => $value) {
                $oldValue = $originalData[$key] ?? null;
                if ((string) $oldValue !== (string) $value) {
                    $changedData['old'][$key] = $oldValue;
                    $changedData['new'][$key] = $value;
                }
            }

            $updated = $data->update($updateData);
            if (! $updated) {
                throw new \Exception('Gagal memperbarui data barang');
            }

            if (! empty($changedData)) {
                $this->saveLogAktivitas(
                    logName: $this->title[0],
                    subjectType: Barang::class,
                    subjectId: $data->id,
                    event: 'Edit Data',
                    properties: ['changes' => $changedData],
                    description: "Barang {$data->nama} (ID {$data->id}) diperbarui.",
                    userId: $request->user_id ?? null,
                    message: $request->message ?? '(Sistem) Perubahan data barang.'
                );
            }

            DB::commit();

            return $this->success($updateData, 201, 'Data berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->error(500, $e->getMessage());
        }
    }

    public function delete(Request $request)
    {
        $data = Barang::findOrFail($request->id);
        try {
            DB::beginTransaction();

            $originalData = Arr::except($data->toArray(), ['id', 'created_at', 'updated_at', 'deleted_at']);

            $this->saveLogAktivitas(
                logName: $this->title[0],
                subjectType: Barang::class,
                subjectId: $data->id,
                event: 'Hapus Data',
                properties: [
                    'changes' => [
                        'old' => $originalData,
                    ],
                ],
                description: "Barang {$data->nama} (ID {$data->id}) dihapus.",
                userId: $request->user_id ?? null,
                message: '(Sistem) Penghapusan data barang.'
            );

            $data->delete();

            DB::commit();

            return $this->success(null, 200, 'Data berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->error(500, 'Internal Server Error', $e->getMessage());
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        Excel::import(new BarangImport, $request->file('file'));

        return back()->with('success', 'Data berhasil diimpor!');
    }
}
