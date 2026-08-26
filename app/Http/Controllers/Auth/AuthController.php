<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\ActivityLogger;
use App\Http\Controllers\Controller;
use App\Models\DetailKasir;
use App\Models\DetailToko;
use App\Models\Kasir;
use App\Models\Member;
use App\Models\StockBarang;
use App\Models\Toko;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('login');
    }

    public function login(Request $request)
    {
        $credentials = $request->only('username', 'password');

        ActivityLogger::log('Login', []);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $user = Auth::user();

            $user->update([
                'ip_login' => $request->ip(),
                'last_activity' => Carbon::now(),
            ]);

            // Cek jika Super Admin (role_id = 1 atau id_level = 1)
            if ($user->role_id == 1) {
                // Ambil seluruh data toko dari database
                $daftarToko = \App\Models\Toko::select('id', 'nama')->get();

                // Jika jumlah toko lebih dari 1
                if ($daftarToko->count() > 1) {
                    return response()->json([
                        'status_code' => 200,
                        'error' => false,
                        'message' => "Silakan pilih toko",
                        'data' => [
                            'show_toko_selection' => true,
                            'daftar_toko' => $daftarToko,
                            'route_redirect' => route('dashboard.index')
                        ]
                    ], 200);
                } else {
                    // Jika cuma 1 toko, otomatis set session ke toko tersebut
                    session(['active_toko_id' => $daftarToko->first()->id ?? $user->toko_id]);
                }
            } else {
                // Untuk user non-superadmin, set toko aktif dari toko_id bawaan user
                session(['active_toko_id' => $user->toko_id]);
            }

            // Penentuan Route Redirect standar
            $route = route('dashboard.index');
            if ($user->nama_level == 'petugas') {
                $route = url('/petugas/dashboard');
            }

            return response()->json([
                'status_code' => 200,
                'error' => false,
                'message' => "Successfully",
                'data' => [
                    'show_toko_selection' => false,
                    'route_redirect' => $route
                ]
            ], 200);
        } else {
            return response()->json([
                'status_code' => 300,
                'error' => true,
                'message' => "Username atau password salah",
            ], 300);
        }
    }

    // Tambahkan Method khusus untuk menyimpan Pilihan Toko ke Session
    public function selectToko(Request $request)
    {
        $request->validate([
            'toko_id' => 'required'
        ]);

        // Simpan toko_id (bisa ID toko tertentu atau 'ALL') ke session
        session(['active_toko_id' => $request->toko_id]);

        return response()->json([
            'status_code' => 200,
            'error' => false,
            'message' => 'Toko berhasil dipilih',
            'route_redirect' => route('dashboard.index')
        ]);
    }

    public function dashboard()
    {
        $menu = ['Dashboard'];
        $toko = Toko::all();
        $title = 'Dashboard';

        return view('dashboard', compact('menu', 'title', 'toko'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function index(Request $request)
    {
        $menu = ['Dashboard'];
        $toko = Toko::where('id', '!=', 1)->get();

        return view('dashboard', compact('menu', 'toko'));
    }
}
