<?php

namespace App\Http\Controllers\TransaksiDigital;

use App\Http\Controllers\Controller;

class DompetController extends Controller
{
    private array $menu = [];

    public function __construct()
    {
        $this->menu;
        $this->title = [
            'Dompet Digital',
        ];
    }

    public function index()
    {
        $menu = [$this->title[0], $this->label[7]];

        return view('transaksiDigital.dompet.index', compact('menu'));
    }
}
