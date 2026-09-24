<?php

namespace App\Repositories;

use App\Models\ReturMember;

class ReturMemberRepository
{
    protected $model;

    public function __construct(ReturMember $model)
    {
        $this->model = $model;
    }

    public function getAll($filter)
    {
        $query = $this->model->with(['detail', 'toko', 'member', 'createdBy'])->where('toko_id', $filter->toko_id);

        if (! empty($filter->start_date) && ! empty($filter->end_date)) {
            $query->whereBetween('tanggal', [$filter->start_date, $filter->end_date]);
        }

        if (! empty($filter->search)) {
            $query->where('status', 'like', "%{$filter->search}%");
        }

        return $query->orderByDesc('created_at')->paginate($filter->limit ?? 30);
    }

    public function getDetailById($id)
    {
        return $this->model->where('id', $id)->first();
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        $retur = $this->model->findOrFail($id);
        $retur->update($data);

        return $retur;
    }

    /**
     * Ambil data retur beserta detail dan relasi detail ke batch-nya
     */
    public function findWithDetails($id)
    {
        return $this->model->with([
            'detail.barang.jenis',
            'detail.batch', // Memanggil relasi batches() yang ada pada model Detail
        ])->find($id);
    }

    /**
     * Eksekusi Soft-delete / Hard-delete record
     */
    public function delete($retur, array $data)
    {
        if (! $retur instanceof $this->model) {
            $retur = $this->model->findOrFail($retur);
        }

        // Hapus hirarki dari bawah: ReturMemberDetailBatch -> ReturMemberDetail -> ReturMember
        foreach ($retur->detail as $detail) {
            // Hapus batch yang terikat dengan detail ini terlebih dahulu
            $detail->batch()->delete();

            // Hapus detail retur
            $detail->delete();
        }

        // Set user penghapus & hapus header retur
        $retur->deleted_by = $data['deleted_by'] ?? null;
        $retur->save();

        return $retur->delete();
    }
}
