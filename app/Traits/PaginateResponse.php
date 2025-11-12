<?php

namespace App\Traits;

trait PaginateResponse
{
    protected function setPaginate($paginator)
    {
        return [
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'total_pages' => $paginator->lastPage(),
        ];
    }
}
