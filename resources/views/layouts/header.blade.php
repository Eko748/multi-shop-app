{{-- <header class="navbar pcoded-header navbar-expand-lg navbar-light header-dark">
    <div class="container-fluid">
        <div class="m-header p-2">
            <a class="mobile-menu" id="mobile-collapse" href="#!">
                <span></span>
            </a>
            <a href="#!" class="b-brand">
                <b class="text-white" style="font-size: 30px;">
                    @if (auth()->user()->id_level == 1)
                        {{ Auth::user()->leveluser->name }}
                    @else
                        {{ Auth::user()->toko->singkatan }}
                    @endif
                </b>
            </a>
            <a href="" onclick="event.preventDefault(); document.getElementById('logout-m-form').submit();"
                title="Logout" class="btn btn-outline-secondary rounded p-2 text-white d-block d-md-none"
                style="position: absolute; top: 10px; right: 10px; width: 40px; height: 40px; font-size: 18px;">
                <i class="feather icon-log-out"></i>
            </a>
            <form id="logout-m-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
        </div>

        <div class="collapse navbar-collapse">
            <ul class="navbar-nav mr-auto"></ul>
            <ul class="navbar-nav ml-auto">
                <li>
                    <div class="dropdown drp-user">
                        <a href="#" class="dropdown-toggle" role="button" tabindex="0">{{ Auth::user()->nama }}
                            <i class="fa fa-chevron-down m-l-5"></i></a>
                        <div class="dropdown-menu dropdown-menu-right profile-notification">
                            <div class="pro-head">
                                @if (Auth::check())
                                    <h5 style="color: white">
                                        @if (auth()->user()->id_level == 1)
                                            {{ env('APP_NAME') ?? 'GSS' }}
                                        @else
                                            {{ Auth::user()->toko->singkatan }}
                                        @endif
                                    </h5>
                                    <p style="color: white">{{ Auth::user()->leveluser->name }}</p>
                                @endif
                            </div>
                            <ul class="pro-body">
                                <li><a href="{{ route('master.user.edit', Auth::id()) }}" class="dropdown-item"><i
                                            class="feather icon-user"></i> Profile</a></li>
                                <li>
                                    <a href=""
                                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                                        class="dropdown-item">
                                        <i class="feather icon-log-out"></i> Log Out
                                    </a>
                                </li>
                            </ul>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                @csrf
                            </form>
                        </div>
                    </div>
                </li>
                <li class="d-block d-lg-none">
                    <a href="" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                        class="dropdown-item">
                        <i class="feather icon-log-out"></i> Log Out
                    </a>
                </li>
            </ul>
        </div>
    </div>
</header> --}}
<header class="site-header navbar pcoded-header navbar-expand-lg" id="siteHeader" role="banner" aria-label="Site header">
    <div class="container-fluid p-0">
        <div class="m-header p-2 d-flex align-items-center">
            <a class="neu-btn d-md-none text-success mr-3" id="mobile-collapse" href="#!" style="border: 2px solid #2ecd7bdc;">
                <i class="fa fa-bars"></i>
            </a>
            <div class="brand">
                <div class="logo" aria-hidden="true">
                    {{ Auth::user()->toko->singkatan }}
                </div>
                <div>
                    {{ Auth::user()->leveluser->name }}
                    <div class="site-tag">{{ Auth::user()->nama }}</div>
                </div>
            </div>
            <ul class="d-md-none navbar-nav ml-auto align-items-center">
                <li class="d-block p-0">
                    <div class="d-flex align-items-center">
                        <!-- Fullscreen button -->
                        <button class="neu-btn" id="fullscreenBtnMobile" title="Perluas layar">
                            <i class="feather icon-maximize"></i>
                        </button>

                        <!-- Profile button -->
                        <a href="{{ route('master.user.edit', Auth::id()) }}" class="neu-btn" title="Profile">
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
                        <button class="neu-btn" id="fullscreenBtn" title="Perluas layar">
                            <i class="feather icon-maximize"></i>
                        </button>

                        <!-- Profile button -->
                        <a href="{{ route('master.user.edit', Auth::id()) }}" class="neu-btn" title="Profile">
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

<section class="hero" aria-hidden="true">
    <div style="position:relative;z-index:10;text-align:center;color:#eafaf1;max-width:900px;padding:20px;">
    </div>
</section>
