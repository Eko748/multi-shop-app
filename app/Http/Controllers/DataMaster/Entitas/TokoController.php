<?php

namespace App\Http\Controllers\DataMaster\Entitas;

use App\Http\Controllers\Controller;
use App\Imports\TokoImport;
use App\Models\Barang;
use App\Models\DetailToko;
use App\Models\LevelHarga;
use App\Models\StockBarang;
use App\Models\Toko;
use App\Models\TokoGroup;
use App\Models\TokoGroupItem;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class TokoController extends Controller
{
    use ApiResponse;
    private array $menu = [];

    public function __construct()
    {
        $this->menu;
        $this->title = [
            'Data Toko',
            'Tambah Data',
            'Detail Data',
            'Edit Data'
        ];
    }

    public function gettoko(Request $request)
    {
        $meta['orderBy'] = $request->ascending ? 'asc' : 'desc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? $request->limit : 30;

        $query = Toko::query();

        $query->with(['levelHarga'])->orderBy('id', $meta['orderBy']);

        if (!empty($request['search'])) {
            $searchTerm = trim(strtolower($request['search']));

            $query->where(function ($query) use ($searchTerm) {
                $query->orWhereRaw("LOWER(nama) LIKE ?", ["%$searchTerm%"]);
                $query->orWhereRaw("LOWER(singkatan) LIKE ?", ["%$searchTerm%"]);
                $query->orWhereRaw("LOWER(wilayah) LIKE ?", ["%$searchTerm%"]);
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
            $idLevelHarga = is_array($item->level_harga) ? $item->level_harga : json_decode($item->level_harga, true);

            if (!is_array($idLevelHarga)) {
                $idLevelHarga = [];
            }

            $levelHargaNames = LevelHarga::whereIn('id', $idLevelHarga)
                ->pluck('nama_level_harga')
                ->toArray();

            return [
                'id' => $item['id'],
                'nama' => $item['nama'],
                'singkatan' => $item['singkatan'],
                'nama_level_harga' => !empty($levelHargaNames) ? implode(', ', $levelHargaNames) : 'Tidak Ada Level',
                'wilayah' => $item->wilayah,
                'alamat' => $item->alamat,
            ];
        });

        return response()->json([
            'data' => $mappedData,
            'status_code' => 200,
            'errors' => false,
            'message' => 'Sukses',
            'pagination' => $data['meta']
        ], 200);
    }

    public function index()
    {
        $menu = [$this->title[0], $this->label[0]];
        $user = Auth::user(); // Mendapatkan user yang sedang login

        // Jika level_user = 1, tampilkan semua data toko
        if ($user->id_level == 1) {
            $toko = Toko::orderBy('id', 'desc')->get();
        } else {
            // Jika level_user selain 1, tampilkan hanya toko yang sesuai dengan id_toko user yang login
            $toko = Toko::where('id', $user->id_toko)->orderBy('id', 'desc')->get();
        }

        $levelharga = LevelHarga::all();

        return view('master.toko.index', compact('menu', 'toko', 'levelharga'));
    }

    public function create()
    {
        $menu = [$this->title[0], $this->label[0], $this->title[1]];
        $levelharga = LevelHarga::orderBy('id', 'desc')->get();
        return view('master.toko.create', compact('menu', 'levelharga'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'toko_id'        => 'required|integer',
            'toko_group_id'  => 'nullable|integer|exists:toko_group,id',
            'nama'           => 'required|max:255',
            'singkatan'      => 'required|max:4|unique:toko,singkatan',
            'level_harga'    => 'required|array',
            'wilayah'        => 'required|max:255',
            'alamat'         => 'required|max:255',
            'pin'            => 'nullable|numeric',
            'kas_detail'     => 'nullable',
        ], [
            'nama.required' => 'Nama Toko tidak boleh kosong.',
            'singkatan.required' => 'Singkatan Wajib di Isi.',
            'level_harga.required' => 'Level Harga tidak boleh kosong.',
            'wilayah.required' => 'Wilayah tidak boleh kosong.',
            'alamat.required' => 'Alamat tidak boleh kosong.',
        ]);

        try {
            DB::beginTransaction();

            /** 1️⃣ Buat toko baru */
            $toko = Toko::create([
                'parent_id'   => $request->toko_id,
                'nama'        => $request->nama,
                'singkatan'   => $request->singkatan,
                'wilayah'     => $request->wilayah,
                'alamat'      => $request->alamat,
                'level_harga' => json_encode($request->level_harga),
                'pin'         => $request->filled('pin') ? $request->pin : null,
                'kas_detail'  => 1,
            ]);

            if ($request->filled('toko_group_id')) {
                $group = TokoGroup::findOrFail($request->toko_group_id);
            } else {
                $group = TokoGroup::create([
                    'parent_toko_id' => $request->toko_id,
                    'nama' => $this->generateTokoGroupName($request->toko_id),
                ]);

                TokoGroupItem::firstOrCreate([
                    'toko_group_id' => $group->id,
                    'toko_id'       => $request->toko_id,
                ]);
            }

            TokoGroupItem::firstOrCreate([
                'toko_group_id' => $group->id,
                'toko_id'       => $toko->id,
            ]);

            DB::commit();

            return $this->success(null, 200, 'Data berhasil disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(500, $e->getMessage());
        }
    }

    private function generateTokoGroupName(int $parentTokoId): string
    {
        $parent = Toko::findOrFail($parentTokoId);

        $kode = $parent->singkatan
            ? strtoupper($parent->singkatan)
            : strtoupper(preg_replace('/\s+/', '', $parent->nama));

        // Cari group terakhir untuk parent ini
        $lastGroup = TokoGroup::where('parent_toko_id', $parentTokoId)
            ->orderByDesc('id')
            ->first();

        $nextNumber = 1;

        if ($lastGroup && preg_match('/(\d{3})$/', $lastGroup->nama, $match)) {
            $nextNumber = (int) $match[1] + 1;
        }

        return sprintf(
            '%s %03d',
            $kode,
            $nextNumber
        );
    }

    public function detail(string $id)
    {
        $user = Auth::user();

        $menu = [$this->title[0], $this->label[0], $this->title[2]];
        $toko = Toko::findOrFail($id);

        $levelHargaArray = json_decode($toko->id_level_harga, true) ?? [];

        // Jika hanya satu id disimpan, pastikan dia array
        if (is_int($levelHargaArray)) {
            $levelHargaArray = [$levelHargaArray];
        }

        // Ambil data level harga berdasarkan id yang ada di array
        $levelhargas = [];
        if (is_array($levelHargaArray) && !empty($levelHargaArray)) {
            $levelhargas = LevelHarga::whereIn('id', $levelHargaArray)->get();
        }

        $detail_toko = DetailToko::where('id_toko', $id)
            ->with('barang')
            ->orderBy('id', 'desc')
            ->get();

        $stock = StockBarang::orderBy('id', 'desc')->get();

        return view('master.toko.detail', compact('menu', 'toko', 'detail_toko', 'stock', 'levelhargas'));
    }

    public function create_detail(string $id)
    {
        $toko = Toko::findOrFail($id);
        $barang = Barang::all();
        // $levelharga = LevelHarga::all();
        return view('master.toko.create_detail', ['id_toko' => $toko->id], compact('barang', 'toko'));
    }

    public function store_detail(Request $request)
    {
        $validatedData = $request->validate([
            'id_barang' => 'required|max:255',
            'stock' => 'required|max:225', // Validasi sebagai array
            'harga' => 'required|max:255',
        ], [
            'id_barang.required' => 'Nama Barang tidak boleh kosong.',
            'stock.required' => 'Stock Barang tidak boleh kosong.',
            'harga.required' => 'Harga tidak boleh kosong.',
        ]);

        try {
            $harga = str_replace(',', '', $request->harga);
            $id_toko = $request->input('id_toko');
            $toko = Toko::findOrFail($id_toko);
            // Simpan data Toko
            DetailToko::create([
                'id_toko' => $id_toko,
                'id_barang' => $request->id_barang,
                'stock' => $request->stock,
                'harga' => $harga,
            ]);

            return redirect()->route('master.toko.detail', ['id' => $toko->id])->with('success', 'Berhasil menambahkan Barang Baru');
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', $th->getMessage())->withInput();
        }
    }

    public function edit(string $id)
    {
        $menu = [$this->title[0], $this->label[0], $this->title[3]];
        $levelharga = LevelHarga::all();
        $toko = Toko::findOrFail($id);
        return view('master.toko.edit', compact('menu', 'toko', 'levelharga'));
    }

    public function edit_detail(string $id_toko, $id_barang, $id)
    {
        $toko = Toko::findOrFail($id_toko);
        $detail_toko = DetailToko::where('id', $id)
            ->where('id_toko', $id_toko)
            ->where('id_barang', $id_barang)
            ->firstOrFail(); // Ambil data toko berdasarkan ID
        $barang = Barang::all(); // Cari barang berdasarkan ID dan ID toko
        // dd($detail_toko);
        return view('master.toko.edit_detail', compact('toko', 'barang', 'detail_toko'));
    }

    public function update(Request $request, string $id)
    {
        $toko = Toko::findOrFail($id);

        // Validasi input
        $request->validate([
            'nama' => 'required',
            'singkatan' => 'required|max:4|unique:toko,singkatan,' . $id,
            'wilayah' => 'required',
            'alamat' => 'required',
            'pin' => 'nullable|numeric',
            'tipe_kas' => 'nullable|in:umum,barang', // validasi enum
        ], [
            'singkatan.unique' => 'Singkatan sudah digunakan.',
            'tipe_kas.in' => 'Tipe kas hanya boleh umum atau barang.',
        ]);

        try {
            // Update data
            $toko->update([
                'nama' => $request->nama,
                'singkatan' => $request->singkatan,
                'wilayah' => $request->wilayah,
                'alamat' => $request->alamat,
                'id_level_harga' => json_encode($request->id_level_harga),
                'pin' => $request->filled('pin') ? $request->pin : null,
                'tipe_kas' => $request->filled('tipe_kas') ? $request->tipe_kas : null,
            ]);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', $th->getMessage())->withInput();
        }

        return redirect()->route('master.toko.index')->with('success', 'Sukses Mengubah Data Toko');
    }

    public function update_detail(Request $request, string $id_toko, string $id_barang)
    {
        $validatedData = $request->validate([
            'id_barang' => 'required|max:255',
            'stock' => 'required|numeric', // Validasi sebagai array
            'harga' => 'required|max:255',
        ], [
            'id_barang.required' => 'Nama Barang tidak boleh kosong.',
            'stock.required' => 'Stock Barang tidak boleh kosong.',
            'harga.required' => 'Harga tidak boleh kosong.',
        ]);

        try {
            $toko = Toko::findOrFail($id_toko);
            $harga = str_replace(',', '', $request->harga);
            $detail_toko = DetailToko::where('id_toko', $id_toko)
                ->where('id_barang', $id_barang)
                ->firstOrFail();
            // Update data Toko
            $detail_toko->update([
                'id_toko' => $id_toko,
                'id_barang' => $request->id_barang,
                'stock' => $request->stock,
                'harga' => $harga,
            ]);

            return redirect()->route('master.toko.detail', ['id' => $toko->id])->with('success', 'Berhasil mengupdate Barang Baru');
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', $th->getMessage())->withInput();
        }
    }

    public function delete_detail(string $id_toko, string $id_barang)
    {
        try {
            $toko = Toko::findOrFail($id_toko);
            $detail_toko = DetailToko::where('id_toko', $id_toko)
                ->where('id_barang', $id_barang)
                ->firstOrFail();
            // Hapus data Barang
            $detail_toko->delete();

            return redirect()->route('master.toko.detail', ['id' => $toko->id])->with('success', 'Berhasil Menghapus Data Barang di Toko');
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', $th->getMessage())->withInput();
        }
    }

    public function delete(string $id)
    {
        DB::beginTransaction();
        $toko = Toko::findOrFail($id);
        try {
            $toko->delete();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sukses menghapus Data Toko'
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus Data Toko: ' . $th->getMessage()
            ], 500);
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls'
        ]);

        Excel::import(new TokoImport, $request->file('file'));

        return back()->with('success', 'Data berhasil diimpor!');
    }
}
