<?php

namespace App\Http\Controllers\Rekapitulasi;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\PengirimanBarang;
use App\Models\Toko;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class LaporanPengirimanBarangController extends Controller
{
    private array $menu = [];

    public function __construct()
    {
        $this->menu;
        $this->title = [
            'Rekap Pengiriman Barang',
        ];
    }

    public function index(Request $request)
    {
        $menu = [$this->title[0], $this->label[2]];
        $barang = Barang::all();
        $user = User::all();
        $users = Auth::user();
        $toko = collect(); // Inisialisasi koleksi kosong untuk data toko

        // Default tanggal awal dan akhir untuk bulan ini
        $startDate = now()->startOfMonth()->toDateString();
        $endDate = now()->endOfMonth()->toDateString();

        // Jika parameter tanggal dikirimkan, gunakan tanggal dari request
        if ($request->has('startDate') && $request->has('endDate')) {
            $startDate = $request->input('startDate');
            $endDate = $request->input('endDate');
        }

        // Mengambil data toko yang terkait dengan pengiriman pada periode tertentu
        $toko = Toko::with([
            'pengirimanSebagaiPengirim' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('verified_at', [$startDate, $endDate]);
            }
        ])->get();

        // Jika tidak ada data pengiriman di bulan ini dan tidak ada filter, kirim pesan
        $message = null;
        if ($toko->isEmpty() && !$request->has('startDate') && !$request->has('endDate')) {
            $message = 'Tidak ada data pengiriman di bulan ini.';
        }

        return view('laporan.pengiriman.index', compact('menu', 'toko', 'barang', 'user', 'users'))
            ->with('startDate', $startDate)
            ->with('endDate', $endDate)
            ->with('message', $message);
    }
}
