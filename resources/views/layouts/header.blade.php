<header class="site-header navbar pcoded-header navbar-expand-lg" id="siteHeader" role="banner" aria-label="Site header">
    <div class="container-fluid p-0">
        <div class="m-header p-2 d-flex align-items-center">
            <a class="neu-btn d-md-none text-success mr-3" id="mobile-collapse" href="#!"
                style="border: 2px solid #2ecd7bdc;">
                <i class="fa fa-bars"></i>
            </a>
            <div class="brand">
                <div class="logo" aria-hidden="true">
                    {{ session('active_toko_singkatan', Auth::user()->toko->singkatan ?? 'APP') }}
                </div>
                <div>
                    {{ Auth::user()->leveluser->name }}
                    <div class="site-tag d-flex align-items-center">
                        <span>{{ Auth::user()->nama }}</span>

                        @if (Auth::user()->role_id == 1)
                            @php
                                $daftarToko = \App\Models\Toko::all();
                                $activeTokoId = session('active_toko_id', Auth::user()->toko_id ?? 'ALL');
                            @endphp

                            <select id="selectTokoHeader" onchange="switchTokoHeader(this.value)"
                                class="custom-select custom-select-sm ml-2"
                                style="width: 80px; height: 22px; padding: 0 20px 0 8px; font-size: 10px; font-weight: bold; background-color: #ffffff !important; color: #0f172a !important; border: 1px solid #cbd5e1; border-radius: 4px; cursor: pointer;">
                                <option value="ALL" {{ $activeTokoId == 'ALL' ? 'selected' : '' }}>Semua</option>
                                @foreach ($daftarToko as $toko)
                                    <option value="{{ $toko->id }}"
                                        {{ $activeTokoId == $toko->id ? 'selected' : '' }}>
                                        {{ $toko->singkatan }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                </div>
            </div>
            <ul class="d-md-none navbar-nav ml-auto align-items-center">
                <li class="d-block p-0">
                    <div class="d-flex align-items-center">
                        <!-- Fullscreen button -->
                        <a href="{{ route('catatan.index') }}" class="neu-btn" title="Profile">
                            <i class="feather icon-bell"></i>
                        </a>
                        <button class="neu-btn" id="fullscreenBtnMobile" title="Perluas layar">
                            <i class="feather icon-maximize"></i>
                        </button>
                        <!-- Profile button -->
                        <a href="{{ route('user.profile') }}" class="neu-btn" title="Profile">
                            <i class="feather icon-user"></i>
                        </a>

                        <!-- Logout button -->
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

        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ml-auto align-items-center">
                <li class="d-none d-lg-block p-0">
                    <div class="d-flex align-items-end">
                        <!-- Fullscreen button -->
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

                        <!-- Profile button -->
                        <a href="{{ route('user.profile') }}" class="neu-btn" title="Profile">
                            <i class="feather icon-user"></i>
                        </a>

                        <!-- Logout button -->
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

<div id="global-loading-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background-color: rgba(255, 255, 255, 0.85); z-index: 9999999; backdrop-filter: blur(4px);">
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
