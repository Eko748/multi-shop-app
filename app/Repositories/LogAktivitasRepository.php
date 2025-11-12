<?php

namespace App\Repositories;

use App\Models\LogAktivitas;

class LogAktivitasRepository
{
    protected $model;

    public function __construct(LogAktivitas $model)
    {
        $this->model = $model;
    }

    public function query()
    {
        return $this->model->newQuery();
    }

    public function getAll($filter)
    {
        $query = $this->model::with('user')
            ->orderBy('created_at', 'desc');

        if (!empty($filter->search)) {
            $query->where(function ($q) use ($filter) {
                $q->where('log_name', 'like', '%' . $filter->search . '%')
                    ->orWhere('event', 'like', '%' . $filter->search . '%')
                    ->orWhere('description', 'like', '%' . $filter->search . '%')
                    ->orWhere('message', 'like', '%' . $filter->search . '%')
                    ->orWhere('route', 'like', '%' . $filter->search . '%')
                    ->orWhere('method', 'like', '%' . $filter->search . '%')
                    ->orWhere('ip_address', 'like', '%' . $filter->search . '%');
            });
        }

        if (!empty($filter->log_event)) {
            $parts = explode('/', $filter->log_event);
            if (count($parts) === 2) {
                [$logName, $event] = $parts;
                $query->where('log_name', $logName)
                    ->where('event', $event);
            }
        }

        return $query->paginate($filter->limit ?? 10);
    }

    public function getLogEvent($limit = 10, $search = null)
    {
        $query = $this->model::select('log_name', 'event')
            ->distinct()
            ->orderBy('log_name')
            ->orderBy('event');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('log_name', 'like', '%' . $search . '%')
                    ->orWhere('event', 'like', '%' . $search . '%');
            });
        }

        return $query->paginate($limit);
    }

    public function getById($id)
    {
        return $this->model::find($id);
    }
}
