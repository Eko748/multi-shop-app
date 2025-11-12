<?php

namespace App\Repositories;

use App\Models\NeracaPenyesuaian;

class NeracaPenyesuaianRepository
{
    /**
     * Dapatkan total nilai penyesuaian neraca.
     */
    public function getTotalPenyesuaian(): float
    {
        return (float) (NeracaPenyesuaian::sum('nilai') ?? 0);
    }
}
