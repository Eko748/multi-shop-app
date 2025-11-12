<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\DetailStockBarang;
use App\Models\DetailToko;
use App\Models\Hutang;
use App\Models\JenisBarang;
use App\Models\JenisHutang;
use App\Models\JenisPemasukan;
use App\Models\JenisPengeluaran;
use App\Models\JenisPiutang;
use App\Models\Kasbon;
use App\Models\LevelUser;
use App\Models\Member;
use App\Models\Pemasukan;
use App\Models\Piutang;
use App\Models\Role;
use App\Models\StockBarang;
use App\Models\Supplier;
use App\Models\Toko;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterController extends Controller
{
    public function getToko(Request $request)
    {
        $meta['orderBy'] = $request->ascending ? 'asc' : 'desc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? $request->limit : 30;

        $query = Toko::query();

        if (!empty($request['is_admin'])) {
            $query->where('id', '!=', 1);
        }

        if (!empty($request['super_admin'])) {
            $query->where('id', '=', 1);
        }

        if (!empty($request['is_delete'])) {
            $query->where('id', '!=', $request['is_delete']);
        }

        if (!empty($request['search'])) {
            $searchTerm = trim(strtolower($request['search']));

            $query->where(function ($query) use ($searchTerm) {
                $query->orWhereRaw("LOWER(nama_toko) LIKE ?", ["%$searchTerm%"]);
                $query->orWhereRaw("LOWER(singkatan) LIKE ?", ["%$searchTerm%"]);
            });
        }

        $query->orderBy('id', $meta['orderBy']);

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

        $mappedData = array_map(function ($item) {
            return [
                'id' => $item['id'],
                'text' => $item['nama_toko'],
                'optional' => $item['singkatan'],
            ];
        }, $data['data']);

        return response()->json([
            'data' => $mappedData,
            'status_code' => 200,
            'errors' => false,
            'message' => 'Berhasil',
            'pagination' => $data['meta']
        ], 200);
    }

    public function getJenis(Request $request)
    {
        $meta['orderBy'] = $request->ascending ? 'asc' : 'desc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? $request->limit : 30;

        $query = JenisPengeluaran::query();

        if (!empty($request['is_admin'])) {
            $query->where('id', '!=', 1);
        }

        if (!empty($request['super_admin'])) {
            $query->where('id', '=', 1);
        }

        if (!empty($request['is_delete'])) {
            $query->where('id', '!=', $request['is_delete']);
        }

        if (!empty($request['search'])) {
            $searchTerm = trim(strtolower($request['search']));

            $query->where(function ($query) use ($searchTerm) {
                $query->orWhereRaw("LOWER(nama_jenis) LIKE ?", ["%$searchTerm%"]);
            });
        }

        $query->orderBy('id', $meta['orderBy']);

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

        $mappedData = array_map(function ($item) {
            return [
                'id' => $item['id'],
                'text' => $item['nama_jenis'],
            ];
        }, $data['data']);

        return response()->json([
            'data' => $mappedData,
            'status_code' => 200,
            'errors' => false,
            'message' => 'Berhasil',
            'pagination' => $data['meta']
        ], 200);
    }

    public function getJenismasuk(Request $request)
    {
        $meta['orderBy'] = $request->ascending ? 'asc' : 'desc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? $request->limit : 30;

        $query = JenisPemasukan::query();

        $cek_id_jenis = Pemasukan::where('id_jenis_pemasukan', 1)->exists();
        if ($cek_id_jenis) {
            $query->where('id', '!=', 1);
        }

        if (!empty($request['is_admin'])) {
            $query->where('id', '!=', 1);
        }

        if (!empty($request['super_admin'])) {
            $query->where('id', '=', 1);
        }

        if (!empty($request['is_delete'])) {
            $query->where('id', '!=', $request['is_delete']);
        }

        if (!empty($request['search'])) {
            $searchTerm = trim(strtolower($request['search']));

            $query->where(function ($query) use ($searchTerm) {
                $query->orWhereRaw("LOWER(nama_jenis) LIKE ?", ["%$searchTerm%"]);
            });
        }

        $query->orderBy('id', $meta['orderBy']);

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

        $mappedData = array_map(function ($item) {
            return [
                'id' => $item['id'],
                'text' => $item['nama_jenis'],
            ];
        }, $data['data']);

        return response()->json([
            'data' => $mappedData,
            'status_code' => 200,
            'errors' => false,
            'message' => 'Berhasil',
            'pagination' => $data['meta']
        ], 200);
    }

    public function getJenishutang(Request $request)
    {
        $meta['orderBy'] = $request->ascending ? 'asc' : 'desc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? $request->limit : 30;

        $query = JenisHutang::query()->where('id', '!=', 1);

        // if (empty($request['is_not'])) {
        //     $cek_id_jenis = Hutang::where('id_jenis', 1)->exists();
        //     if ($cek_id_jenis) {
        //         $query->where('id', '!=', 1);
        //     }
        // }

        // if (!empty($request['is_admin'])) {
        //     $query->where('id', '!=', 1);
        // }

        // if (!empty($request['super_admin'])) {
        //     $query->where('id', '=', 1);
        // }

        // if (!empty($request['is_delete'])) {
        //     $query->where('id', '!=', $request['is_delete']);
        // }

        if (!empty($request['search'])) {
            $searchTerm = trim(strtolower($request['search']));

            $query->where(function ($query) use ($searchTerm) {
                $query->orWhereRaw("LOWER(nama_jenis) LIKE ?", ["%$searchTerm%"]);
            });
        }

        $query->orderBy('id', $meta['orderBy']);

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

        $mappedData = array_map(function ($item) {
            return [
                'id' => $item['id'],
                'text' => $item['nama_jenis'],
            ];
        }, $data['data']);

        return response()->json([
            'data' => $mappedData,
            'status_code' => 200,
            'errors' => false,
            'message' => 'Berhasil',
            'pagination' => $data['meta']
        ], 200);
    }

    public function getJenispiutang(Request $request)
    {
        $meta['orderBy'] = $request->ascending ? 'asc' : 'desc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? $request->limit : 30;

        $query = JenisPiutang::query();

        if (!empty($request['is_admin'])) {
            $query->where('id', '!=', 1);
        }

        if (!empty($request['super_admin'])) {
            $query->where('id', '=', 1);
        }

        if (!empty($request['is_delete'])) {
            $query->where('id', '!=', $request['is_delete']);
        }

        if (!empty($request['search'])) {
            $searchTerm = trim(strtolower($request['search']));

            $query->where(function ($query) use ($searchTerm) {
                $query->orWhereRaw("LOWER(nama_jenis) LIKE ?", ["%$searchTerm%"]);
            });
        }

        $query->orderBy('id', $meta['orderBy']);

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

        $mappedData = array_map(function ($item) {
            return [
                'id' => $item['id'],
                'text' => $item['nama_jenis'],
            ];
        }, $data['data']);

        return response()->json([
            'data' => $mappedData,
            'status_code' => 200,
            'errors' => false,
            'message' => 'Berhasil',
            'pagination' => $data['meta']
        ], 200);
    }

    public function getMember(Request $request)
    {
        $meta['orderBy'] = $request->ascending ? 'asc' : 'desc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? $request->limit : 30;

        $id_toko = $request->id_toko;

        $query = ($id_toko == 1) ? Member::query() : Member::where('id_toko', $id_toko);

        if (!empty($request['search'])) {
            $searchTerm = trim(strtolower($request['search']));
            $query->where(function ($query) use ($searchTerm) {
                $query->orWhereRaw("LOWER(nama_member) LIKE ?", ["%$searchTerm%"]);
                $query->orWhereRaw("LOWER(no_hp) LIKE ?", ["%$searchTerm%"]);
            });
        }

        $data = $query->paginate($meta['limit']);

        $paginationMeta = [
            'total' => $data->total(),
            'per_page' => $data->perPage(),
            'current_page' => $data->currentPage(),
            'total_pages' => $data->lastPage()
        ];

        $items = $data->items();

        if (empty($items)) {
            return response()->json([
                'status_code' => 400,
                'errors' => true,
                'message' => 'Tidak ada data'
            ], 400);
        }

        $mappedData = array_map(function ($item) {
            $alamat = trim($item['alamat'] ?? '');
            $noHp = trim($item['no_hp'] ?? '');
            $noHp = strtolower($noHp) === 'none' ? '' : $noHp;

            $info = '';
            if ($alamat && $noHp) {
                $info = "{$alamat} / {$noHp}";
            } elseif ($alamat) {
                $info = $alamat;
            } elseif ($noHp) {
                $info = $noHp;
            }

            return [
                'id' => $item['id'],
                'text' => $info ? "{$item['nama_member']} ({$info})" : $item['nama_member'],
            ];
        }, $items);

        array_unshift($mappedData, [
            'id' => 'Guest',
            'text' => 'Guest'
        ]);

        return response()->json([
            'data' => $mappedData,
            'status_code' => 200,
            'errors' => false,
            'message' => 'Berhasil',
            'pagination' => $paginationMeta
        ], 200);
    }

    public function getBarang(Request $request)
    {
        $meta['orderBy'] = $request->ascending ? 'asc' : 'desc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? $request->limit : 30;

        $query = Barang::query();

        if (!empty($request['search'])) {
            $searchTerm = trim(strtolower($request['search']));

            $query->where(function ($query) use ($searchTerm) {
                $query->orWhereRaw("LOWER(nama_barang) LIKE ?", ["%$searchTerm%"]);
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

        $mappedData = array_map(function ($item) {
            return [
                'id' => $item['id'],
                'text' => $item['nama_barang'],
            ];
        }, $data['data']);

        return response()->json([
            'data' => $mappedData,
            'status_code' => 200,
            'errors' => false,
            'message' => 'Berhasil',
            'pagination' => $data['meta']
        ], 200);
    }

    public function getSuplier(Request $request)
    {
        $meta['orderBy'] = $request->ascending ? 'asc' : 'desc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? $request->limit : 30;

        $query = Supplier::query();

        if (!empty($request['search'])) {
            $searchTerm = trim(strtolower($request['search']));

            $query->where(function ($query) use ($searchTerm) {
                $query->orWhereRaw("LOWER(nama_supplier) LIKE ?", ["%$searchTerm%"]);
                $query->orWhereRaw("LOWER(contact) LIKE ?", ["%$searchTerm%"]);
            });
        }

        // $query->join('detail_kasir', 'supplier.id', '=', 'detail_kasir.id_supplier')
        //   ->join('detail_retur', function($join) {
        //       $join->on('detail_kasir.id_kasir', '=', 'detail_retur.id_transaksi')
        //            ->where('detail_retur.status', '=', 'success')
        //            ->where('detail_retur.status_reture', '=', 'pending')
        //            ->where('detail_retur.status_kirim', '=', 'success');
        //   })
        //   ->select('supplier.*')
        //   ->distinct();

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

        $mappedData = array_map(function ($item) {
            return [
                'id' => $item['id'],
                'text' => $item['nama_supplier'] . ' / ' . $item['contact'],
            ];
        }, $data['data']);

        return response()->json([
            'data' => $mappedData,
            'status_code' => 200,
            'errors' => false,
            'message' => 'Berhasil',
            'pagination' => $data['meta']
        ], 200);
    }

    public function getBarangPengiriman(Request $request)
    {
        $meta['orderBy'] = $request->ascending ? 'asc' : 'desc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? $request->limit : 30;

        $id_toko = $request->id_toko;

        if ($id_toko == 1) {
            $query = StockBarang::join('barang', 'stock_barang.id_barang', '=', 'barang.id')
                ->join('detail_pembelian_barang as dt_barang', 'stock_barang.id_barang', '=', 'dt_barang.id_barang')
                ->leftJoin('supplier', 'dt_barang.id_supplier', '=', 'supplier.id') // <-- GANTI ke LEFT JOIN
                ->join('detail_stock', 'dt_barang.id', '=', 'detail_stock.id_detail_pembelian')
                ->select(
                    'supplier.nama_supplier',
                    'barang.nama_barang',
                    'detail_stock.qty_now as qty',
                    'dt_barang.qrcode',
                    'dt_barang.id as id_detail'
                );
        } else {
            $query = DetailToko::join('barang', 'detail_toko.id_barang', '=', 'barang.id')
                ->join('detail_pembelian_barang as dt_barang', 'detail_toko.qrcode', '=', 'dt_barang.qrcode')
                ->where('detail_toko.id_toko', $id_toko)
                ->select('detail_toko.id_barang', 'barang.nama_barang', 'detail_toko.qty', 'dt_barang.qrcode', 'dt_barang.id as id_detail');
        }

        if (!empty($request['search'])) {
            $searchTerm = trim(strtolower($request['search']));

            $query->where(function ($query) use ($searchTerm) {
                $query->orWhereRaw("LOWER(barang.nama_barang) LIKE ?", ["%$searchTerm%"]);
                $query->orWhereRaw("LOWER(dt_barang.qrcode) LIKE ?", ["%$searchTerm%"]);
            });
        } else {
            return response()->json([
                'status_code' => 400,
                'errors' => true,
                'message' => 'Silahkan masukkan nama barang / qrcode',
            ], 400);
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

        $mappedData = array_map(function ($item) {
            return [
                'id' => $item['qrcode'] . '/' . $item['id_detail'],
                'text' => "{$item['nama_barang']} / Sisa Stock: ({$item['qty']}) / Supplier: {$item['nama_supplier']} / QRcode : {$item['qrcode']}",
            ];
        }, $data['data']);

        return response()->json([
            'data' => $mappedData,
            'status_code' => 200,
            'errors' => false,
            'message' => 'Berhasil',
            'pagination' => $data['meta']
        ], 200);
    }

    public function getBarangKasir(Request $request)
    {
        $meta['orderBy'] = $request->ascending ? 'asc' : 'desc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? $request->limit : 30;

        $id_toko = $request->id_toko;

        if (!$id_toko) {
            return response()->json([
                'status_code' => 400,
                'errors' => true,
                'message' => 'ID Toko harus diisi',
            ], 400);
        }

        // Jika id_toko == 1, ambil dari StockBarang
        if ($id_toko == 1) {
            $query = DetailStockBarang::join('barang', 'detail_stock.id_barang', '=', 'barang.id')
                ->leftJoin('detail_pembelian_barang as dpb', 'detail_stock.id_detail_pembelian', '=', 'dpb.id')
                ->select(
                    'detail_stock.id_barang',
                    'barang.nama_barang',
                    'barang.barcode',
                    'dpb.qrcode',
                    DB::raw('SUM(detail_stock.qty_now) as stock')
                )
                ->groupBy('detail_stock.id_barang', 'barang.nama_barang', 'barang.barcode', 'dpb.qrcode')
                ->havingRaw('SUM(detail_stock.qty_now) > 0')
                ->orderByRaw('stock ' . $meta['orderBy']);

            if (!empty($request['search'])) {
                $searchTerm = trim(strtolower($request['search']));

                $query->where(function ($q) use ($searchTerm) {
                    $q->orWhereRaw("LOWER(barang.nama_barang) LIKE ?", ["%$searchTerm%"])
                        ->orWhereRaw("LOWER(barang.barcode) LIKE ?", ["%$searchTerm%"])
                        ->orWhereRaw("LOWER(dpb.qrcode) LIKE ?", ["%$searchTerm%"]);
                });
            } else {
                return response()->json([
                    'status_code' => 400,
                    'errors' => true,
                    'message' => 'Silahkan masukkan nama barang, barcode, atau qrcode',
                ], 400);
            }

            $data = $query->paginate($meta['limit']);

            if ($data->isEmpty()) {
                return response()->json([
                    'status_code' => 400,
                    'errors' => true,
                    'message' => 'Tidak ada data',
                ], 400);
            }

            $mappedData = $data->map(function ($item) {
                $namaBarang = $item->nama_barang ?? 'Tidak diketahui';
                $barcode = $item->barcode ?? '-';
                $qrcode = $item->qrcode ?? '-';

                return [
                    'id' => $qrcode . '/' . $item->id_barang,
                    'text' => "{$namaBarang} ~ [Stok: ({$item->stock})]",
                ];
            });
        } else {
            // Jika bukan toko 1, ambil dari DetailToko
            $query = DetailToko::join('barang', 'detail_toko.id_barang', '=', 'barang.id')
                ->leftJoin('supplier', 'detail_toko.id_supplier', '=', 'supplier.id')
                ->join('detail_pembelian_barang as dt_barang', 'detail_toko.qrcode', '=', 'dt_barang.qrcode')
                ->where('detail_toko.id_toko', $id_toko)
                ->select(
                    'detail_toko.id',
                    'detail_toko.id_supplier',
                    'supplier.nama_supplier',
                    'detail_toko.id_toko',
                    'detail_toko.id_barang',
                    'barang.nama_barang',
                    'detail_toko.qty',
                    'detail_toko.harga',
                    'dt_barang.qrcode'
                );

            if (!empty($request['search'])) {
                $searchTerm = trim(strtolower($request['search']));

                $query->where(function ($query) use ($searchTerm) {
                    $query->orWhereRaw("LOWER(dt_barang.qrcode) LIKE ?", ["%$searchTerm%"])
                        ->orWhereRaw("LOWER(barang.nama_barang) LIKE ?", ["%$searchTerm%"])
                        ->orWhere(function ($subQuery) use ($searchTerm) {
                            $subQuery->where('barang.is_old', true)
                                ->whereRaw("LOWER(barang.barcode) LIKE ?", ["%$searchTerm%"]);
                        });
                });
            } else {
                return response()->json([
                    'status_code' => 400,
                    'errors' => true,
                    'message' => 'Silahkan masukkan qrcode',
                ], 400);
            }

            $data = $query->paginate($meta['limit']);

            if ($data->isEmpty()) {
                return response()->json([
                    'status_code' => 400,
                    'errors' => true,
                    'message' => 'Tidak ada data',
                ], 400);
            }

            $mappedData = $data->map(function ($item) {
                $supplierName = $item->nama_supplier ?? 'Tidak ada';

                return [
                    'id' => $item->qrcode . '/' . $item->id_barang,
                    'text' => "{$item->nama_barang} / Sisa Stock: ({$item->qty}) / Supplier: {$supplierName} / QRcode: {$item->qrcode}",
                ];
            });
        }

        $paginationMeta = [
            'total' => $data->total(),
            'per_page' => $data->perPage(),
            'current_page' => $data->currentPage(),
            'total_pages' => $data->lastPage()
        ];

        return response()->json([
            'data' => $mappedData,
            'status_code' => 200,
            'errors' => false,
            'message' => 'Berhasil',
            'pagination' => $paginationMeta
        ], 200);
    }

    public function getQrBarcode(Request $request)
    {
        $id_toko = $request->id_toko;

        if (!$id_toko) {
            return response()->json([
                'status_code' => 400,
                'errors' => true,
                'message' => 'ID Toko harus diisi',
            ], 400);
        }

        if (empty($request['search'])) {
            return response()->json([
                'status_code' => 400,
                'errors' => true,
                'message' => 'Silahkan masukkan barcode, qrcode, atau nama barang',
            ], 400);
        }

        $searchTerm = trim(strtolower($request['search']));
        $isNameSearch = $request->has('is_name') && $request->is_name == 1;

        // ------------------------------
        // 🔹 JIKA TOKO PUSAT (id_toko == 1)
        // ------------------------------
        if ($id_toko == 1) {
            $items = DetailStockBarang::join('barang', 'detail_stock.id_barang', '=', 'barang.id')
                ->join('stock_barang as sb', 'detail_stock.id_stock', '=', 'sb.id')
                ->leftJoin('detail_pembelian_barang as dpb', 'detail_stock.id_detail_pembelian', '=', 'dpb.id')
                ->select(
                    'detail_stock.id_barang',
                    'barang.nama_barang',
                    'barang.barcode',
                    DB::raw('SUM(detail_stock.qty_now) as stock'),
                    DB::raw('GROUP_CONCAT(DISTINCT dpb.qrcode SEPARATOR ",") as qrcodes')
                )
                ->whereNull('barang.deleted_at')
                ->where('detail_stock.qty_now', '>', 0)
                ->where(function ($q) use ($searchTerm, $isNameSearch) {
                    // 🔸 Barcode dan QRCode harus presisi (exact match)
                    $q->orWhereRaw("LOWER(barang.barcode) = ?", [strtolower($searchTerm)])
                        ->orWhereRaw("LOWER(dpb.qrcode) = ?", [strtolower($searchTerm)]);

                    // 🔸 Nama barang masih fleksibel (LIKE)
                    if ($isNameSearch) {
                        $q->orWhereRaw("LOWER(barang.nama_barang) LIKE ?", ["%$searchTerm%"]);
                    }
                })
                ->groupBy('detail_stock.id_barang', 'barang.nama_barang', 'barang.barcode')
                ->get();

            if ($items->isEmpty()) {
                return response()->json([
                    'status_code' => 404,
                    'errors' => true,
                    'message' => 'Data tidak ditemukan',
                ], 404);
            }

            $results = $items->map(function ($item) {
                $qrcodes = $item->qrcodes ?? '-';
                $idBarang = $item->id_barang;
                $namaBarang = $item->nama_barang ?? '-';
                $stokInfo = "[Stok: {$item->stock}]";

                return [
                    'id' => "{$qrcodes}/{$idBarang}",
                    'text' => "{$namaBarang} ~ {$stokInfo}",
                ];
            })->unique('id')->values();
        }

        // ------------------------------
        // 🔹 JIKA TOKO CABANG
        // ------------------------------
        else {
            $items = DetailToko::join('barang', 'detail_toko.id_barang', '=', 'barang.id')
                ->join('detail_pembelian_barang as dpb', 'detail_toko.qrcode', '=', 'dpb.qrcode')
                ->where('detail_toko.id_toko', $id_toko)
                ->whereNull('barang.deleted_at')
                ->where(function ($q) use ($searchTerm, $isNameSearch) {
                    // 🔸 Barcode dan QRCode harus presisi
                    $q->orWhereRaw("LOWER(dpb.qrcode) = ?", [strtolower($searchTerm)])
                        ->orWhereRaw("LOWER(barang.barcode) = ?", [strtolower($searchTerm)]);

                    // 🔸 Nama barang tetap fleksibel
                    if ($isNameSearch) {
                        $q->orWhereRaw("LOWER(barang.nama_barang) LIKE ?", ["%$searchTerm%"]);
                    }
                })
                ->select(
                    'detail_toko.id_barang',
                    'detail_toko.qty',
                    'detail_toko.qrcode',
                    'barang.nama_barang'
                )
                ->get();

            if ($items->isEmpty()) {
                return response()->json([
                    'status_code' => 404,
                    'errors' => true,
                    'message' => 'Data tidak ditemukan',
                ], 404);
            }

            $grouped = $items->groupBy('id_barang')->map(function ($group) {
                $first = $group->first();
                $totalQty = $group->sum('qty');
                $qrcodes = $group->pluck('qrcode')->unique()->implode(',');
                return [
                    'id_barang' => $first->id_barang,
                    'nama_barang' => $first->nama_barang,
                    'qty' => $totalQty,
                    'qrcodes' => $qrcodes
                ];
            });

            $results = $grouped->map(function ($item) {
                $stokInfo = "[Stok: {$item['qty']}]";
                return [
                    'id' => "{$item['qrcodes']}/{$item['id_barang']}",
                    'text' => "{$item['nama_barang']} ~ {$stokInfo}",
                ];
            })->unique('id')->values();
        }

        // ------------------------------
        // 🔹 RETURN RESPONSE
        // ------------------------------
        return response()->json([
            'data' => $results,
            'status_code' => 200,
            'errors' => false,
            'message' => 'Berhasil',
        ], 200);
    }

    public function getKasbon(Request $request)
    {
        $meta['orderBy'] = $request->ascending ? 'asc' : 'desc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? $request->limit : 30;

        $userId = $request->id_user;

        if ($userId == 1) {
            // Jika user ID adalah 1, tampilkan semua data kasbon
            $query = Kasbon::query();
        } else {
            // Ambil id_toko dari user yang sedang login
            $userTokoId = User::where('id', $userId)->value('id_toko');

            // Ambil semua member yang memiliki id_toko yang sama
            $memberIds = Member::where('id_toko', $userTokoId)->pluck('id');

            // Tampilkan kasbon yang sesuai dengan member dari toko tersebut
            $query = Kasbon::with('member')->whereIn('id_member', $memberIds);
        }

        if (!empty($request['search'])) {
            $searchTerm = trim(strtolower($request['search']));

            $query->where(function ($query) use ($searchTerm) {
                $query->orWhereRaw("LOWER(id_member) LIKE ?", ["%$searchTerm%"]);
                $query->orWhereHas('member', function ($query) use ($searchTerm) {
                    $query->whereRaw("LOWER(nama_member) LIKE ?", ["%$searchTerm%"]);
                    $query->whereRaw("LOWER(no_hp) LIKE ?", ["%$searchTerm%"]);
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

        $mappedData = array_map(function ($item) {
            return [
                'id' => $item['id'],
                'nama_member' => $item['member']['nama_member'] ?? 'Guest',
                'no_hp' => $item['member']['no_hp'] ?? '-',
                'utang' => intval($item['utang']),
                'utang_sisa' => intval($item['utang_sisa']),
                'tgl_kasbon' => $item['created_at'],
                'status' => $item['status'] === 'BL' ? 'Belum Lunas' : 'Lunas',
            ];
        }, $data['data']);

        return response()->json([
            'data' => $mappedData,
            'status_code' => 200,
            'errors' => false,
            'message' => 'Berhasil',
            'pagination' => $data['meta']
        ], 200);
    }

    public function getLevelUser(Request $request)
    {
        $meta['orderBy'] = $request->ascending ? 'asc' : 'desc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? $request->limit : 30;

        $query = Role::query();

        if ($request['id_level'] == 3) {
            $query->where('id', 4);
        }

        if (!empty($request['search'])) {
            $searchTerm = trim(strtolower($request['search']));

            $query->where(function ($query) use ($searchTerm) {
                $query->orWhereRaw("LOWER(name) LIKE ?", ["%$searchTerm%"]);
            });
        }

        $query->orderBy('id', $meta['orderBy']);

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

        $mappedData = array_map(function ($item) {
            return [
                'id' => $item['id'],
                'text' => $item['name'],
            ];
        }, $data['data']);

        return response()->json([
            'data' => $mappedData,
            'status_code' => 200,
            'errors' => false,
            'message' => 'Berhasil',
            'pagination' => $data['meta']
        ], 200);
    }

    public function getJenisBarang(Request $request)
    {
        $meta['orderBy'] = $request->ascending ? 'asc' : 'desc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? $request->limit : 30;

        $query = JenisBarang::query();

        if (!empty($request['search'])) {
            $searchTerm = trim(strtolower($request['search']));

            $query->where(function ($query) use ($searchTerm) {
                $query->orWhereRaw("LOWER(nama_jenis_barang) LIKE ?", ["%$searchTerm%"]);
            });
        }

        $query->orderBy('id', $meta['orderBy']);

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

        $mappedData = array_map(function ($item) {
            return [
                'id' => $item['id'],
                'text' => $item['nama_jenis_barang'],
            ];
        }, $data['data']);

        return response()->json([
            'data' => $mappedData,
            'status_code' => 200,
            'errors' => false,
            'message' => 'Berhasil',
            'pagination' => $data['meta']
        ], 200);
    }
}
