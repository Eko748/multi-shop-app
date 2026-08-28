<header class="site-header navbar pcoded-header navbar-expand-lg" id="siteHeader" role="banner" aria-label="Site header">
    <div class="container-fluid p-0">
        <div class="m-header p-2 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <a class="neu-btn d-md-none text-success mr-2" id="mobile-collapse" href="#!"
                    style="border: 2px solid #2ecd7bdc;">
                    <i class="fa fa-bars"></i>
                </a>
                <div class="brand d-flex align-items-center">
                    <div class="logo mr-2" aria-hidden="true">
                        {{ session('active_toko_singkatan', Auth::user()->toko->singkatan ?? 'APP') }}
                    </div>
                    <div>
                        <div class="font-weight-bold" style="line-height: 1.2;">{{ Auth::user()->leveluser->name }}
                        </div>
                        <div class="site-tag d-flex align-items-center">
                            <span class="text-truncate" style="max-width: 80px;">{{ Auth::user()->nama }}</span>

                            @if (Auth::user()->role_id == 1)
                                @php
                                    $daftarToko = \App\Models\Toko::all();
                                    $activeTokoId = session('active_toko_id', Auth::user()->toko_id ?? 'ALL');
                                @endphp

                                <select id="selectTokoHeader" onchange="switchTokoHeader(this.value)"
                                    class="custom-select custom-select-sm ml-1"
                                    style="width: 110px; height: 22px; padding: 0 16px 0 6px; font-size: 10px; font-weight: bold; background-color: #ffffff !important; color: #0f172a !important; border: 1px solid #cbd5e1; border-radius: 4px; cursor: pointer;">
                                    <option value="ALL" {{ $activeTokoId == 'ALL' ? 'selected' : '' }}>Semua</option>
                                    @foreach ($daftarToko as $toko)
                                        @php
                                            $labelToko = '';
                                            if ($toko->parent_id === null || $toko->parent_id === undefined) {
                                                $labelToko = 'Gudang';
                                            } elseif ($toko->mitra) {
                                                $labelToko = 'Mitra';
                                            } else {
                                                $labelToko = 'Cabang';
                                            }
                                        @endphp
                                        <option value="{{ $toko->id }}"
                                            {{ $activeTokoId == $toko->id ? 'selected' : '' }}>
                                            {{ $toko->singkatan }} ({{ $labelToko }})
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tampilan Mobile (Menggunakan ID unik lumoa) -->
            <ul class="d-md-none navbar-nav align-items-center">
                <li class="nav-item position-relative">
                    @php
                        $countMobile = auth()->user()->catatanBelumDibaca()->count();
                    @endphp

                    <a class="neu-btn position-relative" href="#!" id="lumoaMobileBtn" role="button"
                        title="Menu">
                        <i class="feather icon-more-vertical" id="lumoaMobileIcon"></i>
                        @if ($countMobile > 0)
                            <span class="badge badge-danger position-absolute" id="lumoaMobileBadge"
                                style="top:-6px; right:-3px; font-size:9px; padding: 2px 5px;">
                                {{ $countMobile }}
                            </span>
                        @endif
                    </a>

                    <div class="dropdown-menu dropdown-menu-right shadow-lg border-0 mt-2" id="lumoaMobileContent"
                        style="position: absolute; right: 0; left: auto; min-width: 180px; max-width: 220px; border-radius: 8px; z-index: 1050;">

                        <a href="{{ route('catatan.index') }}"
                            class="dropdown-item d-flex align-items-center justify-content-between py-2">
                            <span><i class="feather icon-bell mr-2 text-primary"></i> Catatan</span>
                            @if ($countMobile > 0)
                                <span class="badge badge-danger badge-pill"
                                    style="font-size: 10px;">{{ $countMobile }}</span>
                            @endif
                        </a>
                        <a href="#!" id="fullscreenBtnMobile" class="dropdown-item d-flex align-items-center py-2">
                            <i class="feather icon-maximize mr-2 text-info"></i> Perluas Layar
                        </a>
                        <a href="{{ route('user.profile') }}" class="dropdown-item d-flex align-items-center py-2">
                            <i class="feather icon-user mr-2 text-success"></i> Profil
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href=""
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                            class="dropdown-item d-flex align-items-center py-2 text-danger">
                            <i class="feather icon-log-out mr-2"></i> Keluar
                        </a>
                    </div>
                </li>
            </ul>
        </div>

        <!-- Tampilan Desktop -->
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ml-auto align-items-center">
                <li class="d-none d-lg-block p-0">
                    <div class="d-flex align-items-end">
                        @php
                            $count = auth()->user()->catatanBelumDibaca()->count();
                        @endphp

                        <a href="{{ route('catatan.index') }}"
                            class="neu-btn position-relative {{ request()->routeIs('catatan.*') ? 'active' : '' }}"
                            title="Catatan">
                            <i class="feather icon-bell"></i>
                            @if ($count > 0)
                                <span class="badge badge-danger position-absolute"
                                    style="top:-5px; right:-5px; font-size:10px;">
                                    {{ $count }}
                                </span>
                            @endif
                        </a>
                        <button class="neu-btn" id="fullscreenBtn" title="Perluas layar">
                            <i class="feather icon-maximize"></i>
                        </button>
                        <a href="{{ route('user.profile') }}" class="neu-btn" title="Profile">
                            <i class="feather icon-user"></i>
                        </a>
                        <a href=""
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                            class="neu-btn text-danger" title="Logout" style="border: 2px solid #c63333dc;">
                            <i class="feather icon-log-out mr-1"></i>
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</header>

<div id="global-loading-overlay"
    style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background-color: rgba(255, 255, 255, 0.85); z-index: 9999999; backdrop-filter: blur(4px);">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center;">
        <div class="spinner-border text-success" role="status" style="width: 3rem; height: 3rem;">
            <span class="sr-only">Memuat...</span>
        </div>
        <p class="mt-3 font-weight-bold text-dark" style="font-size: 14px; letter-spacing: 0.5px;">
            Mengganti Toko...
        </p>
    </div>
</div>

<section class="hero" aria-hidden="true">
    <div style="position:relative;z-index:10;text-align:center;color:#eafaf1;max-width:900px;padding:20px;">
    </div>
</section>

<!-- Script Eksklusif dengan ID Lumoa -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btn = document.getElementById('lumoaMobileBtn');
        const menu = document.getElementById('lumoaMobileContent');
        const icon = document.getElementById('lumoaMobileIcon');

        function toggleLumoaDropdown(forceState) {
            const isOpen = forceState !== undefined ? forceState : !menu.classList.contains('show');

            if (isOpen) {
                menu.classList.add('show');
                if (icon) icon.className = 'feather icon-x';
            } else {
                menu.classList.remove('show');
                if (icon) icon.className = 'feather icon-more-vertical';
            }
        }

        if (btn && menu) {
            // Klik tombol untuk buka/tutup (menggunakan .closest agar ikon di dalamnya aman)
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleLumoaDropdown();
            });

            // Klik di luar area tombol & menu untuk menutup otomatis
            document.addEventListener('click', function(e) {
                const targetBtn = e.target.closest('#lumoaMobileBtn');
                const targetMenu = e.target.closest('#lumoaMobileContent');

                if (!targetBtn && !targetMenu) {
                    toggleLumoaDropdown(false);
                }
            });
        }
    });
</script>
