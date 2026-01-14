@php
    $nav_link = 'text-primary bg-white';
@endphp

<nav class="pcoded-navbar theme-horizontal menu-light nav-svg mt-4">
    <div class="navbar-wrapper">
        <div class="navbar-content sidenav-horizontal" id="layout-sidenav">
            <ul class="nav pcoded-inner-navbar p-1">
                <li class="nav-item pcoded-menu-caption">
                    <label>Navigasi</label>
                </li>

                {{-- Dashboard --}}
                @if (hasMenu(1))
                    <li class="nav-item">
                        <a href="{{ route('dashboard.index') }}"
                            class="nav-link {{ request()->routeIs('dashboard.*') ? $nav_link : '' }}">
                            <span class="pcoded-micon"><i class="feather icon-home"></i></span>
                            {{-- <span class="pcoded-mtext">Dashboard</span> --}}
                        </a>
                    </li>
                @endif

                {{-- Data Master --}}
                @if (hasAnyMenu([2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 'log-aktivitas']))
                    <li class="nav-item pcoded-hasmenu">
                        <a href="javascript:void(0)"
                            class="nav-link {{ request()->routeIs('master.*') ? $nav_link : '' }}">
                            <span class="pcoded-micon"><i class="feather icon-box"></i></span>
                            <span class="pcoded-mtext">Data Master</span>
                        </a>
                        <ul class="pcoded-submenu">
                            @if (hasAnyMenu([2, 3, 4, 5]))
                                <li class="font-weight-bold">Entitas</li>
                                @if (hasMenu(2))
                                    <li><a class="dropdown-item" href="{{ route('master.user.index') }}"><i
                                                class="fa fa-users"></i> Data User</a></li>
                                @endif
                                @if (hasMenu(3))
                                    <li><a class="dropdown-item" href="{{ route('master.toko.index') }}"><i
                                                class="fa fa-home"></i> Data Toko</a></li>
                                @endif
                                @if (hasMenu(4))
                                    <li><a class="dropdown-item" href="{{ route('master.member.index') }}"><i
                                                class="fa fa-user"></i> Data Member</a></li>
                                @endif
                                @if (hasMenu(5))
                                    <li><a class="dropdown-item" href="{{ route('master.supplier.index') }}"><i
                                                class="fa fa-download"></i> Data Supplier</a></li>
                                @endif
                            @endif
                            @if (hasAnyMenu([6, 7, 8, 9]))
                                <li class="font-weight-bold mt-2">Manajemen Barang</li>
                                @if (hasMenu(6))
                                    <li><a class="dropdown-item" href="{{ route('master.jenisbarang.index') }}"><i
                                                class="fa fa-box"></i> Jenis Barang</a></li>
                                @endif
                                @if (hasMenu(7))
                                    <li><a class="dropdown-item" href="{{ route('master.brand.index') }}"><i
                                                class="fa fa-tag"></i> Data Brand</a></li>
                                @endif
                                @if (hasMenu(8))
                                    <li><a class="dropdown-item" href="{{ route('master.barang.index') }}"><i
                                                class="fa fa-laptop"></i> Data Barang</a></li>
                                @endif
                                @if (hasMenu(9))
                                    <li><a class="dropdown-item" href="{{ route('master.stockbarang.index') }}"><i
                                                class="fa fa-tasks"></i> Stok Barang</a></li>
                                @endif
                            @endif
                            @if (hasAnyMenu([10, 11, 12, 13]))
                                <li class="font-weight-bold mt-2">Pengaturan</li>
                                @if (hasMenu(10))
                                    <li><a class="dropdown-item" href="{{ route('master.permission.index') }}"><i
                                                class="fa fa-cogs"></i> Hak Akses</a></li>
                                @endif
                                @if (hasMenu(11))
                                    <li><a class="dropdown-item" href="{{ route('master.leveluser.index') }}"><i
                                                class="fa fa-user-shield"></i> Level User</a></li>
                                @endif
                                @if (hasMenu(12))
                                    <li><a class="dropdown-item" href="{{ route('master.levelharga.index') }}"><i
                                                class="fa fa-sitemap"></i> Level Harga</a></li>
                                @endif
                                @if (hasMenu(13))
                                    <li><a class="dropdown-item" href="{{ route('master.promo.index') }}"><i
                                                class="fa fa-star"></i> Promo</a></li>
                                @endif
                            @endif

                            @if (hasAnyMenu(['log-aktivitas']))
                                <li class="font-weight-bold mt-2">Log</li>
                                @if (hasMenu('log-aktivitas'))
                                    <li><a class="dropdown-item" href="{{ route('master.logAktivitas.index') }}"><i
                                                class="fa fa-star"></i> Log Aktivitas</a></li>
                                @endif
                            @endif
                        </ul>
                    </li>
                @endif

                {{-- Distribusi --}}
                @if (hasAnyMenu([14, 15]))
                    <li class="nav-item pcoded-hasmenu">
                        <a href="javascript:void(0)"
                            class="nav-link {{ request()->routeIs('distribusi.*') ? $nav_link : '' }}">
                            <span class="pcoded-micon"><i class="feather icon-package"></i></span>
                            <span class="pcoded-mtext">Distribusi</span>
                        </a>
                        <ul class="pcoded-submenu">
                            @if (hasMenu(14))
                                <li><a class="dropdown-item" href="{{ route('distribusi.pengirimanbarang.index') }}"><i
                                            class="fa fa-truck"></i> Pengiriman Barang</a></li>
                            @endif
                            @if (hasMenu(15))
                                <li><a class="dropdown-item" href="{{ route('distribusi.planorder.index') }}"><i
                                            class="fa fa-laptop"></i> Lokasi & Riwayat Barang</a></li>
                            @endif
                        </ul>
                    </li>
                @endif

                {{-- Transaksi --}}
                @if (hasAnyMenu([16, 17, 18]))
                    <li class="nav-item pcoded-hasmenu">
                        <a href="javascript:void(0)"
                            class="nav-link {{ request()->routeIs('transaksi.*') ? $nav_link : '' }}">
                            <span class="pcoded-micon"><i class="feather icon-shopping-cart"></i></span>
                            <span class="pcoded-mtext">Transaksi Barang</span>
                        </a>
                        <ul class="pcoded-submenu">
                            @if (hasMenu(16))
                                <li><a class="dropdown-item" href="{{ route('transaksi.pembelianbarang.index') }}"><i
                                            class="fa fa-shopping-cart"></i> Pembelian Barang</a></li>
                            @endif
                            @if (hasMenu(17))
                                <li><a class="dropdown-item" href="{{ route('transaksi.kasir.index') }}"><i
                                            class="fa fa-cash-register"></i> Transaksi Kasir</a></li>
                            @endif
                            @if (hasMenu(18))
                                <li><a class="dropdown-item" href="{{ route('transaksi.index') }}"><i
                                            class="fa fa-money-bill"></i> Kasbon Member</a></li>
                            @endif
                        </ul>
                    </li>
                @endif

                @if (hasAnyMenu([39, 40]))
                    <li class="nav-item pcoded-hasmenu">
                        <a href="javascript:void(0)"
                            class="nav-link {{ request()->routeIs('td.*') ? $nav_link : '' }}">
                            <span class="pcoded-micon"><i class="feather icon-folder"></i></span>
                            <span class="pcoded-mtext">Transaksi Digital</span>
                        </a>
                        <ul class="pcoded-submenu">
                            @if (hasMenu(40))
                                <li><a href="{{ route('td.penjualanNonfisik.index') }}" class="dropdown-item"><i
                                            class="icon feather icon-credit-card"></i> Transaksi Non Fisik</a></li>
                            @endif
                            @if (hasMenu(39))
                                <li><a href="{{ route('td.dompetDigital.index') }}" class="dropdown-item"><i
                                            class="icon feather icon-file-text"></i> Dompet Digital</a></li>
                            @endif
                        </ul>
                    </li>
                @endif

                @if (hasAnyMenu([19, 20]))
                    <li class="nav-item pcoded-hasmenu">
                        <a href="javascript:void(0)"
                            class="nav-link {{ request()->routeIs('retur.*') ? $nav_link : '' }}">
                            <span class="pcoded-micon"><i class="feather icon-rotate-ccw"></i></span>
                            <span class="pcoded-mtext">Retur</span>
                        </a>
                        <ul class="pcoded-submenu">
                            @if (hasMenu(19))
                                <li><a href="{{ route('retur.member.index') }}" class="dropdown-item"><i
                                            class="feather icon-rotate-cw"></i> Retur dari Member</a></li>
                            @endif
                            @if (hasMenu(20))
                                <li><a href="{{ route('retur.supplier.index') }}" class="dropdown-item"><i
                                            class="feather icon-corner-down-left"></i> Retur ke Suplier</a></li>
                            @endif
                        </ul>
                    </li>
                @endif

                @if (hasAnyMenu([21, 22, 23, 24, 25, 26, 36, 41]))
                    <li class="nav-item pcoded-hasmenu">
                        <a href="javascript:void(0)"
                            class="nav-link {{ request()->routeIs('laporan.*') ? $nav_link : '' }}">
                            <span class="pcoded-micon"><i class="feather icon-file-text"></i></span>
                            <span class="pcoded-mtext">Rekapitulasi</span>
                        </a>
                        <ul class="pcoded-submenu">
                            @if (hasMenu(36))
                                <li><a class="dropdown-item" href="{{ route('laporan.penjualan.index') }}"><i
                                            class="fa fa-book"></i> Laporan Penjualan</a></li>
                            @endif
                            @if (hasMenu(41))
                                <li><a class="dropdown-item" href="{{ route('laporan.kasir.index') }}"><i
                                            class="fa fa-book"></i> Laporan Kasir</a></li>
                            @endif
                            @if (hasMenu(21))
                                <li><a class="dropdown-item" href="{{ route('laporan.pembelian.index') }}"><i
                                            class="fa fa-book"></i> Rekap Pembelian</a></li>
                            @endif
                            @if (hasMenu(22))
                                <li><a class="dropdown-item" href="{{ route('laporan.pengiriman.index') }}"><i
                                            class="fa fa-truck"></i> Rekap Pengiriman</a></li>
                            @endif
                            @if (hasMenu(23))
                                <li><a class="dropdown-item" href="{{ route('laporan.rating.index') }}"><i
                                            class="fa fa-star"></i> Rating Barang</a></li>
                            @endif
                            @if (hasMenu(24))
                                <li><a class="dropdown-item" href="{{ route('laporan.ratingmember.index') }}"><i
                                            class="fa fa-star"></i> Rating Member</a></li>
                            @endif
                            @if (hasMenu(25))
                                <li><a class="dropdown-item" href="{{ route('laporan.asetbarang.index') }}"><i
                                            class="fa fa-box"></i> Aset Barang Jualan</a></li>
                            @endif
                            @if (hasMenu(26))
                                <li><a class="dropdown-item" href="{{ route('laporan.asetbarangreture.index') }}"><i
                                            class="fa fa-box"></i> Aset Barang Retur</a></li>
                            @endif
                        </ul>
                    </li>
                @endif

                @if (hasAnyMenu([27, 28, 29]))
                    <li class="nav-item pcoded-hasmenu">
                        <a href="javascript:void(0)"
                            class="nav-link {{ request()->routeIs('laporankeuangan.*') ? $nav_link : '' }}">
                            <span class="pcoded-micon"><i class="feather icon-folder"></i></span>
                            <span class="pcoded-mtext">Laporan Keuangan</span>
                        </a>
                        <ul class="pcoded-submenu">
                            @if (hasMenu(27))
                                <li><a href="{{ route('laporankeuangan.aruskas.index') }}" class="dropdown-item"><i
                                            class="icon feather icon-file-text"></i> Arus Kas</a></li>
                            @endif
                            @if (hasMenu(28))
                                <li><a href="{{ route('laporankeuangan.labarugi.index') }}" class="dropdown-item"><i
                                            class="icon feather icon-file-minus"></i> Laba Rugi</a></li>
                            @endif
                            @if (hasMenu(29))
                                <li><a href="{{ route('laporankeuangan.neraca.index') }}" class="dropdown-item"><i
                                            class="icon feather icon-book"></i> Neraca</a></li>
                            @endif
                        </ul>
                    </li>
                @endif

                @if (hasAnyMenu([30, 31, 32, 33, 34]))
                    <li class="nav-item pcoded-hasmenu">
                        <a href="javascript:void(0)"
                            class="nav-link {{ request()->routeIs('keuangan.*') ? $nav_link : '' }}">
                            <span class="pcoded-micon"><i class="icon feather icon-briefcase"></i></span>
                            <span class="pcoded-mtext">Jurnal Keuangan</span>
                        </a>
                        <ul class="pcoded-submenu">
                            @if (hasMenu(30))
                                <li><a href="{{ route('keuangan.pemasukan.index') }}" class="dropdown-item"><i
                                            class="icon feather icon-file-plus"></i> Pemasukan Lainnya</a></li>
                            @endif
                            @if (hasMenu(31))
                                <li><a href="{{ route('keuangan.pengeluaran.index') }}" class="dropdown-item"><i
                                            class="icon feather icon-file-minus"></i> Pengeluaran Lainnya</a></li>
                            @endif
                            @if (hasMenu(32))
                                <li><a href="{{ route('keuangan.piutang.index') }}" class="dropdown-item"><i
                                            class="icon feather icon-file-plus"></i> Piutang</a></li>
                            @endif
                            @if (hasMenu(33))
                                <li><a href="{{ route('keuangan.hutang.index') }}" class="dropdown-item"><i
                                            class="icon feather icon-file-minus"></i> Hutang</a></li>
                            @endif
                            @if (hasMenu(34))
                                <li><a href="{{ route('keuangan.mutasi.index') }}" class="dropdown-item"><i
                                            class="icon feather icon-file-text"></i> Mutasi Kas</a></li>
                            @endif
                        </ul>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</nav>
