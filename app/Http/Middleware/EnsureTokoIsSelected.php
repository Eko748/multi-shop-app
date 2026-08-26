<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureTokoIsSelected
{
    public function handle(Request $request, Closure $next)
    {
        // Cek jika user sudah terautentikasi
        if (Auth::check()) {
            $user = Auth::user();

            // Jika Super Admin / Level 1 tetapi belum punya active_toko_id di session
            if (($user->id_level == 1 || $user->role_id == 1) && (!session()->has('active_toko_id') || session('pending_toko_selection'))) {

                // Logout paksa dan hapus session
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'status_code' => 401,
                        'message' => 'Silakan pilih toko terlebih dahulu.'
                    ], 401);
                }

                return redirect()->route('login')->with('error', 'Silakan login kembali dan pilih toko.');
            }
        }

        return $next($request);
    }
}
