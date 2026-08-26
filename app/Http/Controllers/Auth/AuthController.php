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
            try {
                $request->session()->regenerate();
                $user = Auth::user();

                $user->update([
                    'ip_login' => $request->ip(),
                    'last_activity' => Carbon::now(),
                ]);

                // Cek jika Super Admin / Level 1
                if ($user->id_level == 1 || $user->role_id == 1) {
                    $daftarToko = \App\Models\Toko::select('id', 'nama', 'singkatan', 'alamat')->get();

                    if ($daftarToko->count() > 1) {
                        // TANDAI BAHWA USER BELUM MEMILIH TOKO
                        session(['active_toko_id' => null]);
                        session(['pending_toko_selection' => true]);

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
                        session(['active_toko_id' => $daftarToko->first()->id ?? $user->toko_id]);
                        session()->forget('pending_toko_selection');
                    }
                } else {
                    session(['active_toko_id' => $user->toko_id]);
                    session()->forget('pending_toko_selection');
                }

                $route = route('dashboard.index');
                if (isset($user->nama_level) && $user->nama_level == 'petugas') {
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

            } catch (\Exception $e) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return response()->json([
                    'status_code' => 500,
                    'error' => true,
                    'message' => "Terjadi kesalahan server: " . $e->getMessage(),
                ], 500);
            }
        } else {
            return response()->json([
                'status_code' => 300,
                'error' => true,
                'message' => "Username atau password salah",
            ], 300);
        }
    }

    public function selectToko(Request $request)
    {
        $request->validate([
            'toko_id' => 'required'
        ]);

        // Simpan toko pilihan dan hilangkan status pending
        session(['active_toko_id' => $request->toko_id]);
        session()->forget('pending_toko_selection');

        return response()->json([
            'status_code' => 200,
            'error' => false,
            'message' => 'Toko berhasil dipilih',
            'route_redirect' => route('dashboard.index')
        ]);
    }

    public function postCancelLogin(Request $request)
    {
        // 1. Logout user dari session auth
        Auth::logout();

        // 2. Hapus data session spesifik
        $request->session()->forget([
            'active_toko_id',
            'pending_toko_selection',
            'daftar_toko'
        ]);

        // 3. Regenerate CSRF token agar aman untuk request berikutnya
        $request->session()->regenerateToken();

        return response()->json([
            'status_code' => 200,
            'message' => 'Login dibatalkan.',
            'new_csrf_token' => csrf_token()
        ]);
    }

    public function cancelLogin(Request $request)
    {
        // Logout otomatis jika modal dibatalkan / ditutup tanpa memilih toko
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['status' => 'logged_out']);
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
