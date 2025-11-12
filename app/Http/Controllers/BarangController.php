<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Imports\BarangImport;
use App\Models\Barang;
use App\Models\Brand;
use App\Models\JenisBarang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Milon\Barcode\Facades\DNS1DFacade;
use Maatwebsite\Excel\Facades\Excel;

class BarangController extends Controller
{
    private array $menu = [];

    public function __construct()
    {
        $this->menu;
        $this->title = [
            'Data Barang',
            'Tambah Data',
            'Edit Data'
        ];
    }

    public function getbarangs(Request $request)
    {
        $meta['orderBy'] = $request->ascending ? 'asc' : 'desc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 200 ? $request->limit : 30;

        $query = Barang::query();

        $query->with(['jenis', 'brand',])->orderBy('id', $meta['orderBy']);

        if (!empty($request['search'])) {
            $searchTerm = trim(strtolower($request['search']));

            $query->where(function ($query) use ($searchTerm) {
                // Pencarian pada kolom langsung
                $query->orWhereRaw("LOWER(barcode) LIKE ?", ["%$searchTerm%"]);
                $query->orWhereRaw("LOWER(nama_barang) LIKE ?", ["%$searchTerm%"]);
                $query->orWhereHas('jenis', function ($subquery) use ($searchTerm) {
                    $subquery->whereRaw("LOWER(nama_jenis_barang) LIKE ?", ["%$searchTerm%"]);
                });
                $query->orWhereHas('brand', function ($subquery) use ($searchTerm) {
                    $subquery->whereRaw("LOWER(nama_brand) LIKE ?", ["%$searchTerm%"]);
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
            'total_pages' => $data->lastPage()
        ];

        $data = [
            'data' => $data->items(),
            'meta' => $paginationMeta
        ];

        if (empty($data['data'])) {
            return response()->json([
                'status_code' => 400,
                'errors' => true,
                'message' => 'Tidak ada data'
            ], 400);
        }

        $mappedData = collect($data['data'])->map(function ($item) {
            return [
                'id' => $item['id'],
                'garansi' => $item->garansi === 'Yes' ? 'Ada' : 'Tidak Ada',
                'barcode' => $item->barcode,
                'barcode_path' => $item->barcode_path,
                'gambar_path' => $item->gambar_path,
                'nama_barang' => $item->nama_barang,
                'nama_jenis_barang' => optional($item['jenis'])->nama_jenis_barang ?? 'Tidak Ada',
                'nama_brand' => optional($item['brand'])->nama_brand ?? 'Tidak Ada',
            ];
        });

        return response()->json([
            'data' => $mappedData,
            'status_code' => 200,
            'errors' => true,
            'message' => 'Sukses',
            'pagination' => $data['meta']
        ], 200);
    }

    public function index()
    {
        $menu = [$this->title[0], $this->label[0]];
        $barang = Barang::with('brand', 'jenis')
            ->orderBy('id', 'desc')
            ->get();
        return view('master.barang.index', compact('menu', 'barang'));
    }

    public function create()
    {
        $menu = [$this->title[0], $this->label[0], $this->title[1]];
        $jenis = JenisBarang::all();
        $brand = Brand::all();
        // Mengirim data ke view
        return view('master.barang.create', compact('menu', 'brand', 'jenis'), [
            'brand' => Brand::all()->pluck('nama_brand', 'id'),
            'jenis' => JenisBarang::all()->pluck('nama_jenis_barang', 'id'),
        ]);
    }

    public function getBrandsByJenis(Request $request)
    {
        // Validasi bahwa id_jenis_barang dikirim melalui AJAX
        $request->validate([
            'id_jenis_barang' => 'required|exists:jenis_barang,id'
        ]);

        // Ambil semua Brand yang memiliki id_jenis_barang sesuai dengan yang dipilih
        $brands = Brand::where('id_jenis_barang', $request->id_jenis_barang)->get();

        // Kembalikan data dalam bentuk JSON
        return response()->json($brands);
    }

    // Lokal
    // public function store(Request $request)
    // {
    //     $validated = $request->validate([
    //         'nama_barang' => 'required|string|max:255',
    //         'id_jenis_barang' => 'required|exists:jenis_barang,id',
    //         'id_brand_barang' => 'required|exists:brand,id',
    //         'barcode' => 'nullable|string|max:255',
    //         'garansi' => 'nullable|string|max:255',
    //         'gambar_barang' => 'nullable|image|max:2048',
    //     ]);

    //     ActivityLogger::log('Tambah Barang', $request->all());

    //     try {
    //         // Ambil nama jenis dan brand barang
    //         $jenisBarang = JenisBarang::findOrFail($request->id_jenis_barang)->nama_jenis_barang;
    //         $brandBarang = Brand::findOrFail($request->id_brand_barang)->nama_brand;

    //         // Buat kombinasi kode nama
    //         $initials = strtoupper(substr($jenisBarang, 0, 1) . substr($brandBarang, 0, 1));

    //         // Generate barcode value
    //         $barcodeValue = $request->barcode ?: $initials . random_int(100000, 999999);

    //         // Path folder barcode
    //         $barcodeFolder = storage_path('app/public/barcodes');

    //         // Buat folder barcode jika belum ada
    //         if (!file_exists($barcodeFolder)) {
    //             mkdir($barcodeFolder, 0755, true);
    //         }

    //         // Generate barcode as PNG file
    //         $barcodeFilename = "barcodes/{$barcodeValue}.png";
    //         if (!Storage::exists($barcodeFilename)) {
    //             $barcodeImage = DNS1DFacade::getBarcodePNG($barcodeValue, 'C128', 3, 100);

    //             if (!$barcodeImage) {
    //                 throw new \Exception('Failed to generate barcode PNG as Base64');
    //             }

    //             // Save to Storage
    //             if (!Storage::put($barcodeFilename, base64_decode($barcodeImage))) {
    //                 throw new \Exception('Failed to save barcode image to storage');
    //             }
    //         }

    //         // Path folder gambar_barang
    //         $gambarFolder = storage_path('app/public/gambar_barang');

    //         // Buat folder gambar_barang jika belum ada
    //         if (!file_exists($gambarFolder)) {
    //             mkdir($gambarFolder, 0755, true);
    //         }

    //         $gambarPath = null; // Default null jika gambar tidak diunggah
    //         if ($request->hasFile('gambar_barang')) {
    //             $gambarFile = $request->file('gambar_barang');
    //             $gambarPath = $gambarFile->store('gambar_barang', 'public'); // Simpan ke storage/public/gambar_barang
    //         }

    //         $barang = new Barang();
    //         $barang->nama_barang = $request->nama_barang;
    //         $barang->id_jenis_barang = $request->id_jenis_barang;
    //         $barang->id_brand_barang = $request->id_brand_barang;
    //         $barang->barcode = $barcodeValue;
    //         $barang->barcode_path = $barcodeFilename;
    //         $barang->gambar_path = $gambarPath;
    //         $barang->garansi = $request->garansi ?: 'No';
    //         $barang->save();

    //         return redirect()->route('master.barang.index')->with('success', 'Data Barang berhasil ditambahkan!');
    //     } catch (\Exception $e) {
    //         return redirect()->back()->with(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
    //     }
    // }

    // Server
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_barang' => 'required|string|max:255',
            'id_jenis_barang' => 'required|exists:jenis_barang,id',
            'id_brand_barang' => 'required|exists:brand,id',
            'barcode' => 'nullable|string|max:255',
            'garansi' => 'nullable|string|max:255',
            'gambar_barang' => 'nullable|image|max:2048',
        ]);

        ActivityLogger::log('Tambah Barang', $request->all());

        try {
            $jenisBarang = JenisBarang::findOrFail($request->id_jenis_barang)->nama_jenis_barang;
            $brandBarang = Brand::findOrFail($request->id_brand_barang)->nama_brand;

            // Inisial barcode
            $barcodeValue = $request->barcode ?: $this->generateIncrementalBarcode();

            // Path dan filename barcode
            $barcodeFilename = "{$barcodeValue}.png";
            $barcodeRelativePath = "barcodes/{$barcodeFilename}";

            // Simpan barcode jika belum ada
            if (!Storage::disk('public')->exists($barcodeRelativePath)) {
                $barcodeImage = DNS1DFacade::getBarcodePNG($barcodeValue, 'C128', 3, 100);

                if (!$barcodeImage) {
                    throw new \Exception('Gagal membuat barcode PNG dari base64');
                }

                Storage::disk('public')->put($barcodeRelativePath, base64_decode($barcodeImage));
            }

            // Simpan gambar barang (jika diupload)
            $gambarPath = null;
            if ($request->hasFile('gambar_barang')) {
                $gambarFile = $request->file('gambar_barang');
                $gambarPath = $gambarFile->store('gambar_barang', 'public');
            }

            // Simpan ke database
            $barang = new Barang();
            $barang->nama_barang = $request->nama_barang;
            $barang->id_jenis_barang = $request->id_jenis_barang;
            $barang->id_brand_barang = $request->id_brand_barang;
            $barang->barcode = $barcodeValue;
            $barang->barcode_path = $barcodeRelativePath; // agar bisa diakses via URL
            $barang->gambar_path = $gambarPath ? $gambarPath : null;
            $barang->garansi = $request->garansi ?: 'No';
            $barang->save();

            return redirect()->route('master.barang.index')->with('success', 'Data Barang berhasil ditambahkan!');
        } catch (\Exception $e) {
            return redirect()->back()->with(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    private function generateIncrementalBarcode(): string
    {
        $lastBarcode = Barang::whereRaw('LENGTH(barcode) = 6')
            ->where('barcode', 'REGEXP', '^[0-9]+$')
            ->orderByDesc('barcode')
            ->value('barcode');

        $lastNumber = $lastBarcode ? intval($lastBarcode) : 0;
        $newNumber = $lastNumber + 1;

        return str_pad($newNumber, 6, '0', STR_PAD_LEFT); // jadi format 000001
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

    public function edit(string $id)
    {
        $menu = [$this->title[0], $this->label[0], $this->title[2]];
        $barang = Barang::with('brand', 'jenis')->findOrFail($id);
        $brand = Brand::all();
        $jenis = JenisBarang::all();
        $item = [
            'id' => $id,
            'garansi' => null, // Contoh value garansi
        ];
        return view('master.barang.edit', compact('menu', 'barang', 'brand', 'jenis', 'item'));
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'id_jenis_barang' => 'required|integer',
            'id_brand_barang' => 'required|integer',
            'nama_barang' => 'required|string|max:255',
        ]);

        ActivityLogger::log('Update Barang', ['id' => $id, 'data' => $request->all()]);

        $barang = Barang::findOrFail($id);

        try {
            $barang->update([
                'id_jenis_barang' => $request->id_jenis_barang,
                'id_brand_barang' => $request->id_brand_barang,
                'nama_barang' => $request->nama_barang,
                'garansi' => $request->garansi,
            ]);
            return redirect()->route('master.barang.index')->with('success', 'Sukses Mengubah Data Barang');
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', $th->getMessage())->withInput();
        }
    }

    public function delete(string $id)
    {
        DB::beginTransaction();

        ActivityLogger::log('Delete Barang', ['id' => $id]);

        $barang = Barang::findOrFail($id);
        try {

            $barang->delete();

            DB::commit();

            return redirect()->route('master.barang.index')->with('success', 'Sukses menghapus Data Barang');
        } catch (\Throwable $th) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Gagal menghapus Data Barang: ' . $th->getMessage());
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls'
        ]);

        Excel::import(new BarangImport, $request->file('file'));

        return back()->with('success', 'Data berhasil diimpor!');
    }
}
