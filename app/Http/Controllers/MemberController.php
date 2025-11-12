<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Imports\MemberImport;
use App\Models\JenisBarang;
use App\Models\LevelHarga;
use App\Models\LevelUser;
use App\Models\Member;
use App\Models\Toko;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Arr;
use Throwable;

class MemberController extends Controller
{
    use ApiResponse;
    private array $menu = [];

    public function __construct()
    {
        $this->menu;
        $this->title = [
            'Data Member',
        ];
    }

    public function getmember(Request $request)
    {
        $meta['orderBy'] = $request->ascending ? 'asc' : 'desc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? $request->limit : 30;

        $query = Member::query();

        $query->with(['toko', 'levelHarga', 'jenis_barang'])->orderBy('id', $meta['orderBy']);

        if ($request->has('id_toko')) {
            $idToko = $request->input('id_toko');
            if ($idToko != 1) {
                $query->where('id_toko', $idToko);
            }
        }

        if (!empty($request['search'])) {
            $searchTerm = trim(strtolower($request['search']));

            $query->where(function ($query) use ($searchTerm) {
                if ($searchTerm === 'guest') {
                    $query->where('id_member', 0);
                } else {
                    $query->orWhereRaw("LOWER(nama_member) LIKE ?", ["%$searchTerm%"]);
                    $query->orWhereRaw("LOWER(no_hp) LIKE ?", ["%$searchTerm%"]);
                    $query->orWhereRaw("LOWER(alamat) LIKE ?", ["%$searchTerm%"]);

                    $query->orWhereHas('toko', function ($subquery) use ($searchTerm) {
                        $subquery->whereRaw("LOWER(nama_toko) LIKE ?", ["%$searchTerm%"]);
                    });
                }
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
            $idLevelHarga = is_array($item->id_level_harga) ? $item->id_level_harga : json_decode($item->id_level_harga, true);

            if (!is_array($idLevelHarga) || empty($idLevelHarga)) {
                $idLevelHarga = [];
            }

            $levelData = [];
            if (!empty($idLevelHarga)) {
                $levelData = LevelHarga::whereIn('id', $idLevelHarga)
                    ->get(['jenis_barang', 'nama_level_harga as level_harga'])
                    ->toArray();
            }

            $selectedLevels = [];
            if (!empty($item->level_info)) {
                foreach (json_decode($item->level_info, true) as $info) {
                    preg_match('/(\d+) : (\d+)/', $info, $matches);
                    if (!empty($matches)) {
                        $jenisBarang = JenisBarang::find($matches[1]);
                        $levelHarga = LevelHarga::find($matches[2]);

                        if ($jenisBarang && $levelHarga) {
                            $selectedLevels[] = [
                                'id_jenis_barang' => $jenisBarang->id,
                                'nama_jenis_barang' => $jenisBarang->nama_jenis_barang,
                                'id_level_harga' => $levelHarga->id,
                                'nama_level_harga' => $levelHarga->nama_level_harga,
                            ];
                        }
                    }
                }
            }

            return [
                'id' => $item['id'],
                'nama_member' => $item['nama_member'],
                'id_toko' => $item['toko']->id ?? null,
                'nama_toko' => $item['toko']->nama_toko ?? null,
                'level' => $selectedLevels,
                'no_hp' => $item->no_hp,
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
        $user = Auth::user();

        if ($user->id_level == 1 || $user->id_level == 2) {
            $member = Member::orderBy('id', 'desc')
                ->with(['levelharga', 'toko', 'jenis_barang'])
                ->get();

            $toko = Toko::all();
        } else {
            $member = Member::where('id_toko', $user->id_toko)
                ->orderBy('id', 'desc')
                ->with(['levelharga', 'toko', 'jenis_barang'])
                ->get();

            $toko = Toko::where('id', $user->id_toko)->get();
        }

        $jenis_barang = JenisBarang::all();
        $levelharga = LevelHarga::all();

        $selected_levels = [];
        foreach ($member as $mbr) {
            if (!empty($mbr->level_info)) {
                foreach (json_decode($mbr->level_info, true) as $info) {
                    preg_match('/(\d+) : (\d+)/', $info, $matches);
                    $selected_levels[$mbr->id][$matches[1]] = $matches[2];
                }
            }
        }

        return view('master.member.index', compact('menu', 'member', 'jenis_barang', 'levelharga', 'selected_levels'));
    }

    public function getLevelHarga($id_toko)
    {
        $toko = Toko::where('id', $id_toko)->first();

        if ($toko) {
            $levelHargaIds = json_decode($toko->id_level_harga, true);

            $levelHarga = LevelHarga::whereIn('id', $levelHargaIds)
                ->get(['id', 'nama_level_harga'])
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'text' => $item->nama_level_harga,
                    ];
                })
                ->values();

            $data['data'] = $levelHarga;

            return $this->success($data['data'], 200, 'Berhasil');
        }

        return response()->json(['error' => 'Toko tidak ditemukan'], 404);
    }

    public function create()
    {
        $toko = Toko::all();
        $leveluser = LevelUser::all();
        $levelharga = LevelHarga::all();
        $jenis_barang = JenisBarang::all();

        return view('master.member.create', compact('toko', 'leveluser', 'levelharga', 'jenis_barang'));
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $validatedData = $request->validate(
                [
                    'id_toko' => 'required',
                    'nama_member' => 'required',
                    'no_hp' => 'required',
                    'alamat' => 'required',
                ],
                [
                    'id_toko.required' => 'Toko Wajib diisi.',
                    'nama_member.required' => 'Nama Member tidak boleh kosong',
                    'no_hp.required' => 'No Hp Wajib diisi',
                    'alamat.required' => 'Alamat Wajib diisi',
                ]
            );

            $level_harga = $request->input('level_harga', []);
            $levelInfo = [];

            foreach ($level_harga as $jenis_barang_id => $level_harga_id) {
                if (!empty($level_harga_id)) {
                    $levelInfo[] = "{$jenis_barang_id} : {$level_harga_id}";
                }
            }

            $data = Member::create([
                'id_toko' => $validatedData['id_toko'],
                'nama_member' => $validatedData['nama_member'],
                'no_hp' => $validatedData['no_hp'],
                'alamat' => $validatedData['alamat'],
                'level_info' => json_encode($levelInfo),
            ]);

            $this->saveLogAktivitas(
                logName: $this->title[0],
                subjectType: 'App\Models\Member',
                subjectId: $data->id,
                event: 'Tambah Data',
                properties: [
                    'changes' => [
                        'new' => Arr::except($data->toArray(), ['id', 'created_at', 'updated_at', 'deleted_at']),
                    ],
                ],
                description: "Member {$data->nama_member} (ID {$data->id}) di toko ID {$data->id_toko} ditambahkan.",
                userId: $request->user_id ?? null,
                message: $request->message ?? '(Sistem) Penambahan member baru.'
            );

            DB::commit();
            return $this->success($data, 201, 'Data berhasil ditambahkan');
        } catch (Throwable $th) {
            DB::rollBack();
            return $this->error(500, 'Internal Server Error', $th->getMessage());
        }
    }

    public function edit($id)
    {
        $member = Member::with('levelharga')->findOrFail($id);
        $jenis_barang = JenisBarang::all();
        $levelharga = LevelHarga::all();

        $selected_levels = $member->level_data;

        return view('member.edit', compact('member', 'jenis_barang', 'levelharga', 'selected_levels'));
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'id_toko' => 'required|integer',
                'nama_member' => 'required|string',
                'no_hp' => 'required|string',
                'alamat' => 'nullable|string',
                'level_harga' => 'nullable|array',
            ]);

            DB::beginTransaction();

            $member = Member::findOrFail($id);

            $originalData = Arr::except($member->toArray(), ['id', 'created_at', 'updated_at', 'deleted_at']);

            $level_harga = $request->input('level_harga', []);
            $levelInfo = [];

            if (!empty($level_harga)) {
                foreach ($level_harga as $jenis_barang_id => $level_harga_id) {
                    if (!empty($level_harga_id)) {
                        $levelInfo[] = "{$jenis_barang_id} : {$level_harga_id}";
                    }
                }
            }

            $updateData = [
                'id_toko' => $request->id_toko,
                'nama_member' => $request->nama_member,
                'no_hp' => $request->no_hp,
                'alamat' => $request->alamat,
                'level_info' => json_encode($levelInfo),
            ];

            $changedData = [];
            foreach ($updateData as $key => $value) {
                $oldValue = $originalData[$key] ?? null;
                if ((string)$oldValue !== (string)$value) {
                    $changedData['old'][$key] = $oldValue;
                    $changedData['new'][$key] = $value;
                }
            }

            $updated = $member->update($updateData);

            if (!$updated) {
                throw new \Exception('Gagal memperbarui data member');
            }

            if (!empty($changedData)) {
                $this->saveLogAktivitas(
                    logName: $this->title[0],
                    subjectType: 'App\Models\Member',
                    subjectId: $member->id,
                    event: 'Edit Data',
                    properties: ['changes' => $changedData],
                    description: "Member {$member->nama_member} (ID {$member->id}) di toko ID {$member->id_toko} diperbarui.",
                    userId: $request->user_id ?? null,
                    message: $request->message ?? '(Sistem) Perubahan data member.'
                );
            }

            DB::commit();
            return $this->success($updateData, 201, 'Data berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(500, 'Internal Server Error', $e->getMessage());
        }
    }

    public function delete(Request $request, string $id)
    {
        $member = Member::findOrFail($id);

        ActivityLogger::log('Delete Member', ['id' => $id]);

        try {
            DB::beginTransaction();

            $originalData = Arr::except($member->toArray(), ['id', 'created_at', 'updated_at', 'deleted_at']);

            $this->saveLogAktivitas(
                logName: $this->title[0],
                subjectType: 'App\Models\Member',
                subjectId: $member->id,
                event: 'Hapus Data',
                properties: [
                    'changes' => [
                        'old' => $originalData,
                    ],
                ],
                description: "Member {$member->nama_member} (ID {$member->id}) di toko ID {$member->id_toko} dihapus.",
                userId: $request->user_id ?? null,
                message: '(Sistem) Penghapusan data member.'
            );

            $member->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sukses menghapus Data Member'
            ]);
        } catch (Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus Data Member: ' . $th->getMessage()
            ], 500);
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls'
        ]);

        Excel::import(new MemberImport, $request->file('file'));

        return back()->with('success', 'Data berhasil diimpor!');
    }
}
