<?php

namespace App\Http\Controllers\TransaksiDigital;

use App\Http\Controllers\Controller;

class TransaksiNonFisikController extends Controller
{
    private array $menu = [];

    public function __construct()
    {
        $this->menu;
        $this->title = [
            'Transaksi Non Fisik',
        ];
    }

    public function index()
    {
        $menu = [$this->title[0], $this->label[7]];

        return view('transaksiDigital.penjualan.index', compact('menu'));
    }
}
