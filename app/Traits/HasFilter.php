<?php

namespace App\Traits;

use Illuminate\Http\Request;

trait HasFilter
{
    /**
     * Build filter object dari request dengan default yang bisa dikustomisasi.
     *
     * @param  Request  $request
     * @param  int      $defaultLimit   Nilai default untuk limit
     * @param  array    $defaults       Default value lain untuk filter
     * @return object
     */
    protected function makeFilter(Request $request, int $defaultLimit = 10, array $defaults = [])
    {
        $base = [
            'limit'  => $request->input('limit', $defaultLimit),
            'search' => $request->input('search'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'nama'   => $request->input('nama'),
        ];

        return (object) array_merge($base, $defaults);
    }
}
