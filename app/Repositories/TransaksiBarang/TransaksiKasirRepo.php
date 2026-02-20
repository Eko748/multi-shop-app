<?php

namespace App\Repositories\TransaksiBarang;

use App\Helpers\RupiahGenerate;
use App\Helpers\TextGenerate;
use App\Models\TransaksiKasir;
use App\Models\TransaksiKasirDetail;
use Illuminate\Support\Carbon;

class TransaksiKasirRepo
{
    protected $model;

    public function __construct(TransaksiKasir $model)
    {
        $this->model = $model;
    }

    public function sumNominal($filter)
    {
        $query = $this->model->newQuery();

        if (!empty($filter->start_date) && !empty($filter->end_date)) {
            $query->whereBetween('tanggal', [
                Carbon::parse($filter->start_date)->startOfDay(),
                Carbon::parse($filter->end_date)->endOfDay(),
            ]);
        } else {
            $query->whereDate('tanggal', Carbon::today());
        }

        return [
            'qty'       => $query->sum('total_qty'),
            'nominal'   => RupiahGenerate::build($query->sum('total_nominal'))
        ];
    }

    public function getAll($filter)
    {
        $query = $this->model->newQuery();

        if (!empty($filter->search)) {
            $query->where(function ($q) use ($filter) {
                $q->where('nota', 'like', '%' . $filter->search . '%');
            });
        }

        if (!empty($filter->toko_id)) {
            $query->where('toko_id', $filter->toko_id);
        }

        if (!empty($filter->nota)) {
            $query->where('nota', $filter->nota);
        }

        if (!empty($filter->start_date) && !empty($filter->end_date)) {
            $query->whereBetween('tanggal', [$filter->start_date, $filter->end_date]);
        } else {
            $query->whereDate('tanggal', Carbon::today());
        }

        return !empty($filter->limit)
            ? $query->orderByDesc('id')->paginate($filter->limit)
            : $query->orderByDesc('id')->get();
    }

    public function getNota($limit = 10, $search = null)
    {
        $query = $this->model::select('id', 'nota', 'created_at')
            ->orderByDesc('created_at');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nota', 'like', '%' . $search . '%');
            });
        }

        return $query->paginate($limit);
    }

    public function create(array $data)
    {
        return $this->model::create($data);
    }

    public function detailByPublicId(string $publicId): array
    {
        $kasir = $this->model
            ->with(['createdBy:id,nama', 'member:id,nama', 'toko:id,nama,alamat'])
            ->where('public_id', $publicId)
            ->firstOrFail();

        $detailKasir = TransaksiKasirDetail::with([
            'stockBarangBatch.stockBarang.barang:id,nama'
        ])
            ->where('transaksi_kasir_id', $kasir->id)
            ->get();

        /** grouping untuk struk */
        $groupedDetails = $detailKasir
            ->groupBy('stock_barang_batch_id')
            ->map(function ($items) {
                $first = $items->first();

                return [
                    'nama_barang' => TextGenerate::smartTail($first->stockBarangBatch->stockBarang->barang->nama) ?? '-',
                    'qty'         => $items->sum('qty'),
                    'harga'       => RupiahGenerate::build($first->nominal),
                    'diskon'      => RupiahGenerate::build($first->diskon ?? 0),
                    'total_harga' => RupiahGenerate::build($items->sum('subtotal')),
                ];
            })
            ->values();

        return [
            'kasir' => [
                'id'              => $kasir->id,
                'public_id'       => $kasir->public_id,
                'nota'            => $kasir->nota,
                'tanggal'         => $kasir->tanggal->format('d-m-Y H:i:s'),
                'total_qty'       => $kasir->total_qty,
                'total_nominal'   => RupiahGenerate::build($kasir->total_nominal ?? 0),
                'total_bayar'     => RupiahGenerate::build($kasir->total_bayar ?? 0),
                'total_diskon'    => RupiahGenerate::build($kasir->total_diskon ?? 0),
                'total_kembalian' => RupiahGenerate::build(max(0, $kasir->total_bayar - $kasir->total_nominal)),
                'total'           => RupiahGenerate::build(($kasir->total_nominal - $kasir->total_diskon) ?? 0),

                'users'  => $kasir->createdBy ?? null,
                'member' => $kasir->member ?? 'Guest',
                'toko'   => $kasir->toko ?? null,
                'kasbon' => $kasir->kasbon ?? null,
            ],

            'detail_kasir' => $detailKasir->map(function ($item) {
                $barang = TextGenerate::smartTail($item->stockBarangBatch->stockBarang->barang->nama) ?? '-';
                $qrcode = $item->qrcode ?? null;
                $retur = ($item->retur_qty ?? 0) > 0 ? "— Qty Retur: {$item->retur_qty}" : "";
                return [
                    'id'        => $item->id,
                    'qty'       => $item->qty,
                    'harga'     => RupiahGenerate::build($item->nominal),
                    'subtotal'  => RupiahGenerate::build($item->subtotal),
                    'qrcode'    => $qrcode,
                    'barang'    => [
                        'nama_barang' => $barang,
                    ],
                    'text' => "
                        <div style='display: flex; align-items: center; gap: 8px;' class='p-1'>
                            <div style='display: flex; flex-direction: column; line-height: 1.2;'>
                                <span style='font-weight: 550; font-size: 12px;'>{$barang}</span>
                                <small class='text-dark'>
                                    {$qrcode} {$retur}
                                </small>
                            </div>
                        </div>
                    "
                ];
            }),

            'grouped_details' => $groupedDetails,
        ];
    }

    public function print(string $publicId): array
    {
        $kasir = $this->model
            ->with([
                'createdBy:id,nama',
                'member:id,nama',
                'toko:id,nama,alamat',
            ])
            ->where('public_id', $publicId)
            ->firstOrFail();

        $detailKasir = TransaksiKasirDetail::with([
            'stockBarangBatch.stockBarang.barang:id,nama'
        ])
            ->where('transaksi_kasir_id', $kasir->id)
            ->get();

        /** =========================
         * GROUPING DETAIL STRUK
         * ========================= */
        $groupedDetails = $detailKasir
            ->groupBy('stock_barang_batch_id')
            ->map(function ($items) {
                $first = $items->first();

                return [
                    'nama_barang' => TextGenerate::smartTail(
                        $first->stockBarangBatch->stockBarang->barang->nama ?? '-'
                    ),
                    'qty'         => $items->sum('qty'),
                    'harga'       => RupiahGenerate::build($first->nominal ?? 0),
                    'diskon'      => RupiahGenerate::build($first->diskon ?? 0),
                    'total_harga' => RupiahGenerate::build($items->sum('subtotal')),
                ];
            })
            ->values();

        /** =========================
         * RESPONSE PRINT STRUK
         * ========================= */
        return [
            'toko' => [
                'nama'   => $kasir->toko->nama ?? '-',
                'alamat' => $kasir->toko->alamat ?? '-',
            ],

            'nota' => [
                'no_nota' => $kasir->nota,
                'tanggal' => $kasir->tanggal->format('d-m-Y H:i:s'),
                'member'  => $kasir->member
                    ? $kasir->member->nama
                    : 'Guest',
                'kasir'   => $kasir->createdBy->nama ?? '-',
            ],

            'detail' => $groupedDetails,

            'total' => [
                'total_harga'     => RupiahGenerate::build($kasir->total_nominal ?? 0),
                'total_potongan'  => RupiahGenerate::build($kasir->total_diskon ?? 0),
                'total_bayar'     => RupiahGenerate::build(
                    ($kasir->total_nominal - $kasir->total_diskon) ?? 0
                ),
                'dibayar'         => RupiahGenerate::build($kasir->total_bayar ?? 0),
                'kembalian'       => RupiahGenerate::build(
                    max(0, ($kasir->total_bayar ?? 0) - ($kasir->total_nominal ?? 0))
                ),
                'sisa_pembayaran' => RupiahGenerate::build(0),
            ],

            'footer' => 'Terima Kasih',
        ];
    }

    public function update($id, array $data)
    {
        $item = $this->model::find($id);
        if ($item) {
            $item->update($data);
        }
        return $item;
    }

    public function delete($id, $data)
    {
        $item = $this->model::where('public_id', $id)->first();
        if (!$item) {
            return false;
        }

        $item->update(['deleted_by' => $data['deleted_by']]);

        return $item->delete();
    }

    public function count()
    {
        return $this->model::count();
    }
}
