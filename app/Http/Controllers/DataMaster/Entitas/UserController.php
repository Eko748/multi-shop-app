<?php

namespace App\Http\Controllers\DataMaster\Entitas;

use App\Http\Controllers\Controller;
use App\Imports\UserImport;
use App\Models\LevelUser;
use App\Models\Toko;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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
                'toko_id' => optional($item['toko'])->id,
                'nama_level' => optional($item['role'])->name ?? 'Tidak ada',
                'role_id' => optional($item['role'])->id,
                'nama' => $item->nama,
                'username' => $item->username,
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

    public function post(Request $request)
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

            $data = User::create([
                'toko_id' => $request->toko_id,
                'role_id' => $request->role_id,
                'nama' => $request->nama,
                'username' => $request->username,
                'password' => bcrypt($request->password),
                'alamat' => $request->alamat,
            ]);

            $this->saveLogAktivitas(
                logName: $this->title[0],
                subjectType: User::class,
                subjectId: $data->id,
                event: 'Tambah Data',
                properties: [
                    'changes' => [
                        'new' => Arr::except($data->toArray(), ['id', 'created_at', 'updated_at', 'deleted_at']),
                    ],
                ],
                description: "User {$data->nama} (ID {$data->id}) ditambahkan.",
                userId: $request->user_id ?? null,
                message: $request->message ?? '(Sistem) Penambahan user baru.'
            );

            DB::commit();
            return $this->success(null, 200, 'Data berhasil disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(500, 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
        }
    }

    public function update(Request $request)
    {
        try {
            $request->validate(
                [
                    'toko_id' => 'required',
                    'role_id' => 'required',
                    'nama' => 'required|max:255',
                    'username' => 'required|max:255',
                    'password' => 'nullable|min:8|regex:/([0-9])/',
                    'alamat' => 'required|max:255',
                ],
                [
                    'toko_id.required' => 'Nama Toko tidak boleh kosong.',
                    'role_id.required' => 'Nama Level tidak boleh kosong.',
                    'nama.required' => 'Nama tidak boleh kosong.',
                    'username.required' => 'Username tidak boleh kosong.',
                    'password.min' => 'Password minimal 8 karakter.',
                    'password.regex' => 'Password harus mengandung minimal satu angka.',
                    'alamat.required' => 'Alamat tidak boleh kosong.',
                ]
            );

            DB::beginTransaction();

            $data = User::findOrFail($request->id);

            $updateData = [
                'toko_id' => $request->toko_id,
                'role_id' => $request->role_id,
                'nama' => $request->nama,
                'username' => $request->username,
                'alamat' => $request->alamat,
            ];

            $changedData = [];
            foreach ($updateData as $key => $value) {
                $oldValue = $originalData[$key] ?? null;
                if ((string)$oldValue !== (string)$value) {
                    $changedData['old'][$key] = $oldValue;
                    $changedData['new'][$key] = $value;
                }
            }

            if (!empty($request->password)) {
                $updateData['password'] = bcrypt($request->password);
            }

            $updated = $data->update($updateData);

            if (!$updated) {
                throw new \Exception('Gagal memperbarui data user');
            }

            if (!empty($changedData)) {
                $this->saveLogAktivitas(
                    logName: $this->title[0],
                    subjectType: User::class,
                    subjectId: $data->id,
                    event: 'Edit Data',
                    properties: ['changes' => $changedData],
                    description: "User {$data->nama} (ID {$data->id}) diperbarui.",
                    userId: $request->user_id ?? null,
                    message: $request->message ?? '(Sistem) Perubahan data user.'
                );
            }

            DB::commit();

            return $this->success($updateData, 201, 'Data berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(500, 'Internal Server Error', $e->getMessage());
        }
    }

    public function delete(Request $request)
    {
        $data = User::findOrFail($request->id);

        try {
            DB::beginTransaction();

            $originalData = Arr::except($data->toArray(), ['id', 'created_at', 'updated_at', 'deleted_at']);

            $this->saveLogAktivitas(
                logName: $this->title[0],
                subjectType: User::class,
                subjectId: $data->id,
                event: 'Hapus Data',
                properties: [
                    'changes' => [
                        'old' => $originalData,
                    ],
                ],
                description: "User {$data->nama} (ID {$data->id}) dihapus.",
                userId: $request->user_id ?? null,
                message: '(Sistem) Penghapusan data user.'
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
            'file' => 'required|file|mimes:xlsx,xls'
        ]);

        Excel::import(new UserImport, $request->file('file'));

        return back()->with('success', 'Data berhasil diimpor!');
    }
}
