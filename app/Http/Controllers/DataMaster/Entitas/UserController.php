<?php

namespace App\Http\Controllers\DataMaster\Entitas;

use App\Http\Controllers\Controller;
use App\Imports\UserImport;
use App\Models\LevelUser;
use App\Models\Toko;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    use ApiResponse;
    private array $menu = [];

    public function __construct()
    {
        $this->menu;
        $this->title = [
            'Data User',
            'Tambah Data',
            'Edit Data'
        ];
    }

    public function getdatauser(Request $request)
    {
        $meta['orderBy'] = $request->ascending ? 'asc' : 'desc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? $request->limit : 30;

        $query = User::query();

        $query->where('role_id', '!=', 1)
            ->where('id', '!=', $request->id_user)
            ->with(['toko', 'role'])
            ->orderBy('id', $meta['orderBy']);

        if ($request->has('toko_id')) {
            $idToko = $request->input('toko_id');
            if ($idToko != 1) {
                $query->where('toko_id', $idToko);
            }
        }

        if (!empty($request['search'])) {
            $searchTerm = trim(strtolower($request['search']));

            $query->where(function ($query) use ($searchTerm) {
                $query->orWhereRaw("LOWER(nama) LIKE ?", ["%$searchTerm%"]);
                $query->orWhereRaw("LOWER(email) LIKE ?", ["%$searchTerm%"]);

                $query->orWhereHas('toko', function ($subquery) use ($searchTerm) {
                    $subquery->whereRaw("LOWER(nama) LIKE ?", ["%$searchTerm%"]);
                });

                $query->orWhereHas('role', function ($subquery) use ($searchTerm) {
                    $subquery->whereRaw("LOWER(name) LIKE ?", ["%$searchTerm%"]);
                });
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
                'nama_toko' => optional($item['toko'])->nama ?? 'Tidak ada',
                'nama_level' => optional($item['role'])->name ?? 'Tidak ada',
                'nama' => $item->nama,
                'username' => $item->username,
                'email' => $item->email,
                'alamat' => $item->alamat,
                'no_hp' => $item->no_hp,
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

        return view('master.user.index', compact('menu'));
    }

    public function create()
    {
        $menu = [$this->title[0], $this->label[0], $this->title[1]];
        $toko = Toko::all();
        $leveluser = LevelUser::all();
        return view('master.user.create', compact('menu', 'toko', 'leveluser'), [
            'leveluser' => LevelUser::all()->pluck('nama_level', 'id'),
            'toko' => Toko::all()->pluck('nama_toko', 'id'),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate(
            [
                'toko_id' => 'required',
                'role_id' => 'required',
                'nama' => 'required|max:255',
                'username' => 'required|max:255',
                'password' => 'required|min:8|regex:/([0-9])/',
                'alamat' => 'required|max:255',
            ],
            [
                'toko_id.required' => 'Nama Toko tidak boleh kosong.',
                'role_id.required' => 'Nama Level tidak boleh kosong.',
                'nama.required' => 'Nama tidak boleh kosong.',
                'username.required' => 'Username tidak boleh kosong.',
                'password.required' => 'Password tidak boleh kosong.',
                'password.min' => 'Password minimal 8 karakter.',
                'password.regex' => 'Password harus mengandung minimal satu angka.',
                'alamat.required' => 'Alamat tidak boleh kosong.',
            ]
        );

        try {
            DB::beginTransaction();

            User::create([
                'toko_id' => $request->toko_id,
                'role_id' => $request->role_id,
                'nama' => $request->nama,
                'username' => $request->username,
                'password' => bcrypt($request->password),
                'alamat' => $request->alamat,
            ]);

            DB::commit();
            return $this->success(null, 200, 'Data berhasil disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(500, 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
        }
    }

    public function edit(string $id)
    {
        $menu = [$this->title[0], $this->label[0], $this->title[2]];
        $user = User::with(['leveluser', 'toko'])->findOrFail($id);

        // dd($user);
        $toko = Toko::all();
        $leveluser = LevelUser::all();
        return view('master.user.edit', compact('menu', 'user', 'toko', 'leveluser'));
    }

    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        try {
            $data = [
                'id_toko' => $request->id_toko,
                'id_level' => $request->id_level,
                'nama' => $request->nama,
                'username' => $request->username,
                'email' => $request->email,
                'alamat' => $request->alamat,
                'no_hp' => $request->no_hp,
            ];

            // Hanya tambahkan password jika field password tidak kosong
            if (!empty($request->password)) {
                $data['password'] = bcrypt($request->password);
            }

            $user->update($data);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', $th->getMessage())->withInput();
        }

        return redirect()->route('master.user.index')->with('success', 'Sukses Mengubah Data User');
    }

    public function delete(string $id)
    {
        DB::beginTransaction();
        $user = User::findOrFail($id);
        try {
            $user->delete();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Sukses menghapus Data User'
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus Data User: ' . $th->getMessage()
            ], 500);
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls'
        ]);

        Excel::import(new UserImport, $request->file('file'));

        return back()->with('success', 'Data berhasil diimpor!');
    }
}
