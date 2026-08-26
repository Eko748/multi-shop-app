<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetActiveTokoContext
{
    public function handle(Request $request, Closure $next)
    {
        // Cek apakah user sedang login dan memiliki role_id = 1 (Super Admin)
        if (auth()->check() && auth()->user()->role_id == 1) {

            // Cek apakah ada toko pilihan yang tersimpan di Session
            if (session()->has('active_toko_id')) {
                $activeTokoId = session('active_toko_id');

                // Jika request dari frontend membawa parameter toko_id,
                // paksa timpa nilainya dengan active_toko_id dari Session
                if ($request->has('toko_id')) {
                    $request->merge([
                        'toko_id' => $activeTokoId
                    ]);
                }
            }
        }

        return $next($request);
    }
}
