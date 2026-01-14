<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="" />
    <meta name="keywords" content="">
    <meta name="author" content="GSS" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | @if (auth()->user()->id_level == 1)
            {{ env('APP_NAME') ?? 'GSS' }}
        @else
            {{ Auth::user()->toko->singkatan }}
        @endif
    </title>
    <!-- Favicon icon -->
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('images/logo/logo.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700&display=swap" rel="stylesheet">
    <!-- prism css -->
    <link rel="stylesheet" href="{{ asset('css/fontawesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/flatpickr.min.css') }}">

    @include('layouts.css.style_css')
    <style>
        .floating-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            background-color: #25D366;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            z-index: 1000;
        }

        .floating-button img {
            width: 32px;
            height: 32px;
        }

        .dropdown-container {
            position: fixed;
            bottom: 90px;
            right: 20px;
            width: 300px;
            padding: 15px;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: none;
            flex-direction: column;
            gap: 10px;
            z-index: 9999;
            opacity: 0;
            transform: scale(0.8);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        .dropdown-container.show {
            display: flex;
            opacity: 1;
            transform: scale(1);
        }

        .dropdown-container textarea {
            width: 100%;
            height: 100px;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            resize: none;
            font-size: 14px;
        }

        .dropdown-container button {
            width: 100%;
            padding: 10px;
            background-color: #25D366;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        .dropdown-container button:hover {
            background-color: #20b357;
        }

        .loader {
            top: calc(50% - 32px);
            left: calc(50% - 32px);
            width: 24px;
            height: 24px;
            border-radius: 50%;
            perspective: 800px;
        }

        .inner {
            position: absolute;
            box-sizing: border-box;
            width: 100%;
            height: 100%;
            border-radius: 50%;
        }

        .inner.one {
            left: 0%;
            top: 0%;
            animation: rotate-one 1s linear infinite;
            border-bottom: 3px solid #1abc9c;
        }

        .inner.two {
            right: 0%;
            top: 0%;
            animation: rotate-two 1s linear infinite;
            border-right: 3px solid #1abc9c;
        }

        .inner.three {
            right: 0%;
            bottom: 0%;
            animation: rotate-three 1s linear infinite;
            border-top: 3px solid #1abc9c;
        }

        @keyframes rotate-one {
            0% {
                transform: rotateX(35deg) rotateY(-45deg) rotateZ(0deg);
            }

            100% {
                transform: rotateX(35deg) rotateY(-45deg) rotateZ(360deg);
            }
        }

        @keyframes rotate-two {
            0% {
                transform: rotateX(50deg) rotateY(10deg) rotateZ(0deg);
            }

            100% {
                transform: rotateX(50deg) rotateY(10deg) rotateZ(360deg);
            }
        }

        @keyframes rotate-three {
            0% {
                transform: rotateX(35deg) rotateY(55deg) rotateZ(0deg);
            }

            100% {
                transform: rotateX(35deg) rotateY(55deg) rotateZ(360deg);
            }
        }

        .alert-custom {
            background: linear-gradient(135deg, #004d3d, #066854, #0f8f75, #1ec7a5, #6bf1d7);
            color: #ffffff;
        }

        .swal2-container {
            z-index: 99999 !important;
        }

        .new_footer_area {
            background: #fbfbfd;
        }


        .new_footer_top {
            padding: 0px 0px 270px;
            position: relative;
            overflow-x: hidden;
        }

        .new_footer_area .footer_bottom {
            padding-top: 5px;
            padding-bottom: 5px;
        }

        .footer_bottom {
            font-size: 14px;
            line-height: 0px;
            color: #7f88a6;
        }

        .new_footer_top .company_widget p {
            font-size: 16px;
            font-weight: 300;
            line-height: 28px;
            color: #6a7695;
            margin-bottom: 20px;
        }

        .new_footer_top .company_widget .f_subscribe_two .btn_get {
            border-width: 1px;
            margin-top: 20px;
        }

        .btn_get_two:hover {
            background: transparent;
            color: #1abc9c;
        }

        .btn_get:hover {
            color: #fff;
            background: #1abc9c;
            border-color: #1abc9c;
            -webkit-box-shadow: none;
            box-shadow: none;
        }

        a:hover,
        a:focus,
        .btn:hover,
        .btn:focus,
        button:hover,
        button:focus {
            text-decoration: none;
            outline: none;
        }


        .new_footer_top .f_widget.about-widget .f_list li a:hover {
            color: #1abc9c;
        }

        .new_footer_top .f_widget.about-widget .f_list li {
            margin-bottom: 11px;
        }

        .f_widget.about-widget .f_list li:last-child {
            margin-bottom: 0px;
        }

        .f_widget.about-widget .f_list li {
            margin-bottom: 15px;
        }

        .f_widget.about-widget .f_list {
            margin-bottom: 0px;
        }

        .new_footer_top .f_social_icon a {
            width: 44px;
            height: 44px;
            line-height: 43px;
            background: transparent;
            border: 1px solid #e2e2eb;
            font-size: 24px;
        }

        .f_social_icon a {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            font-size: 14px;
            line-height: 45px;
            color: #1abc9c;
            display: inline-block;
            background: #ebeef5;
            text-align: center;
            -webkit-transition: all 0.2s linear;
            -o-transition: all 0.2s linear;
            transition: all 0.2s linear;
        }

        .ti-facebook:before {
            content: "\e741";
        }

        .ti-twitter-alt:before {
            content: "\e74b";
        }

        .ti-vimeo-alt:before {
            content: "\e74a";
        }

        .ti-pinterest:before {
            content: "\e731";
        }

        .btn_get_two {
            -webkit-box-shadow: none;
            box-shadow: none;
            background: #1abc9c;
            border-color: #1abc9c;
            color: #fff;
        }

        .btn_get_two:hover {
            background: transparent;
            color: #1abc9c;
        }

        .new_footer_top .f_social_icon a:hover {
            background: #1abc9c;
            border-color: #1abc9c;
            color: white;
        }

        .new_footer_top .f_social_icon a+a {
            margin-left: 4px;
        }

        .new_footer_top .f-title {
            margin-bottom: 10px;
            color: #263b5e;
        }

        .f_600 {
            font-weight: 600;
        }

        .f_size_18 {
            font-size: 18px;
        }

        .new_footer_top .f_widget.about-widget .f_list li a {
            color: #6a7695;
        }

        .new_footer_top .footer_bg {
            position: absolute;
            bottom: 0;
            background: url('{{ asset('images/footer/footer_bg.png') }}') no-repeat scroll center 0;
            width: 100%;
            height: 266px;
        }

        .new_footer_top .footer_bg .footer_bg_one {
            background: url('{{ asset('images/footer/volks.gif') }}') no-repeat center center;
            width: 330px;
            height: 105px;
            background-size: 100%;
            position: absolute;
            bottom: 0;
            left: 30%;
            -webkit-animation: myfirst 22s linear infinite;
            animation: myfirst 22s linear infinite;
        }

        .new_footer_top .footer_bg .footer_bg_two {
            background: url('{{ asset('images/footer/cyclist.gif') }}') no-repeat center center;
            width: 88px;
            height: 100px;
            background-size: 100%;
            bottom: 0;
            left: 38%;
            position: absolute;
            -webkit-animation: myfirst 30s linear infinite;
            animation: myfirst 30s linear infinite;
        }

        @-moz-keyframes myfirst {
            0% {
                left: -25%;
            }

            100% {
                left: 100%;
            }
        }

        @-webkit-keyframes myfirst {
            0% {
                left: -25%;
            }

            100% {
                left: 100%;
            }
        }

        @keyframes myfirst {
            0% {
                left: -25%;
            }

            100% {
                left: 100%;
            }
        }

        .modal-dialog {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 3.5rem);
        }
    </style>
    <style>
        :root {
            --header-height: 80px;
            --glass-bg: rgba(255, 255, 255, 0.06);
            --accent: #2ecc71;
            /* green accent */
            --accent-weak: rgba(46, 204, 113, 0.12);
            --text: rgba(255, 255, 255, 0.95);
            --glass-border: rgba(255, 255, 255, 0.06);
        }

        .drp-user:hover .dropdown-menu {
            display: block;
        }


        * {
            box-sizing: border-box
        }

        /* body {
            margin: 0;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
            background: linear-gradient(180deg, #071120 0%, #0b1520 100%);
            color: var(--text);
            min-height: 100vh;
        } */

        /* Page hero so header sits on top of something interesting */
        .hero {
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: url('https://images.unsplash.com/photo-1503264116251-35a269479413?auto=format&fit=crop&w=1600&q=60');
            background-size: cover;
            background-position: center;
            position: relative;
            overflow: hidden;
        }

        .hero::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(5, 12, 20, 0.45), rgba(5, 12, 20, 0.65));
        }

        /* Header */
        header.site-header {
            position: fixed;
            left: 0;
            right: 0;
            height: var(--header-height);
            margin: 0 auto;
            padding: 12px 20px;
            z-index: 60;
            display: flex;
            align-items: center;
            justify-content: space-between;
            backdrop-filter: blur(8px) saturate(110%);
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.02), rgba(255, 255, 255, 0.01));
            border: 1px solid var(--glass-border);
            box-shadow: 0 6px 30px rgba(2, 6, 10, 0.6);
            overflow: visible;
            color: white;
            /* default text putih */
            transition: color 0.3s ease, background 0.3s ease;
        }

        /* saat discroll tambahkan class .scrolled */
        header.site-header.scrolled {
            background: rgba(255, 255, 255, 0.9);
            color: #111;
            /* teks jadi dark */
        }


        /* Sweeping green light */
        /* Sweeping green light */
        .site-header::before {
            content: "";
            position: absolute;
            left: -40%;
            top: -40%;
            width: 60%;
            height: 180%;
            transform: translateX(-100%) rotate(8deg);
            background: linear-gradient(90deg,
                    transparent 0%,
                    rgba(46, 204, 113, 0.06) 30%,
                    rgba(35, 231, 116, 0.416) 50%,
                    rgba(46, 204, 113, 0.06) 70%,
                    transparent 100%);
            filter: blur(18px);
            animation: sweep 6s linear infinite;
            pointer-events: none;
            /* biar nggak ganggu klik */
            mix-blend-mode: screen;
            z-index: 0;
            /* selalu di belakang */
        }

        /* pastikan konten header di atas efek */
        .brand,
        .collapse-toggle,
        .main-nav {
            position: relative;
            z-index: 2;
        }

        @keyframes sweep {
            0% {
                transform: translateX(-140%) rotate(8deg);
            }

            50% {
                transform: translateX(40%) rotate(8deg);
            }

            100% {
                transform: translateX(220%) rotate(8deg);
            }
        }

        /* Respect reduced motion */
        @media (prefers-reduced-motion: reduce) {
            .site-header::before {
                animation: none;
                opacity: 0.6
            }
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 10;
        }

        .logo {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: inline-grid;
            place-items: center;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.06), rgba(255, 255, 255, 0.02));
            border: 1px solid rgba(255, 255, 255, 0.04);
            box-shadow: 0 4px 12px rgba(2, 6, 10, 0.5) inset;
            font-weight: 700;
            font-size: 16px;
        }

        .site-title {
            font-weight: 600
        }

        .site-tag {
            font-size: 12px;
            opacity: 0.78
        }

        /* nav.main-nav {
            display: flex;
            gap: 18px;
            align-items: center;
            z-index: 10
        }

        nav.main-nav a {
            color: var(--text);
            text-decoration: none;
            padding: 8px 10px;
            border-radius: 8px;
            font-weight: 500;
            opacity: 0.92
        }

        nav.main-nav a:hover {
            background: var(--accent-weak);
            color: var(--text)
        }
        */
        .cta {
            padding: 10px 14px;
            border-radius: 10px;
            background: linear-gradient(90deg, var(--accent), #00b96b);
            color: #042017;
            font-weight: 700
        }

        .cta:active {
            transform: translateY(1px)
        }

        /* Mobile */
        .hamburger {
            display: none;
            z-index: 15
        }

        /* Make header smaller on scroll */
        header.site-header.shrink {
            height: 60px;
            padding: 8px 16px
        }

        /* Demo content below */

        @media (max-width:820px) {
            /* nav.main-nav {
                display: none
            } */

            .hamburger {
                display: block
            }

            .cta {
                display: none
            }

            header.site-header {
                padding: 10px
            }
        }

        .collapse-toggle {
            position: relative;
            z-index: 2;
            background: none;
            border: none;
            color: var(--text);
            font-size: 1.5rem;
            cursor: pointer;
            margin-left: auto;
        }

        @media(min-width:992px) {
            .collapse-toggle {
                position: absolute;
                right: 1rem;
            }
        }

        /* Default hidden */
        nav.main-nav {
            display: none;
            position: absolute;
            top: 100%;
            /* muncul di bawah header */
            right: 0;
            width: 220px;
            background: rgba(11, 21, 32, 0.95);
            /* transparan gelap */
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 0 0 8px 8px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
            padding: .5rem 0;
            z-index: 999;
        }

        /* Saat aktif */
        nav.main-nav.active {
            display: block;
            animation: fadeIn .3s ease;
        }

        /* Animasi */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        nav.main-nav .navbar-nav {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        nav.main-nav .navbar-nav li {
            padding: .5rem 1rem;
        }

        nav.main-nav .navbar-nav li a {
            display: block;
            color: #fff;
            text-decoration: none;
        }

        nav.main-nav .navbar-nav li a:hover {
            background: rgba(46, 204, 113, 0.15);
        }

        /* Responsive mobile: jadi full width */
        @media(max-width:768px) {
            nav.main-nav {
                right: auto;
                left: 0;
                width: 100%;
                border-radius: 0;
            }
        }
    </style>
    <style>
        .pcoded-navbar .navbar-content {
            overflow: visible !important;
        }

        .drp-user {
            position: relative;
            F
        }

        .drp-user .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            z-index: 9999 !important;
        }


        .drp-user .dropdown-menu.show {
            display: block;
        }

        .drp-user .dropdown-item {
            display: block;
            padding: 8px 12px;
            color: #333;
            text-decoration: none;
        }

        .drp-user .dropdown-item:hover {
            background: #f5f5f5;
        }

        .drp-user .btn {
            padding: 2px 6px;
            line-height: 1;
            margin: 0;
        }

        .drp-user .btn i {
            line-height: 1;
            vertical-align: middle;
        }

        .pcoded-main-container {
            margin-top: 50px !important;
        }

        @media(max-width:768px) {
            .pcoded-main-container {
                margin-top: 0px !important;
            }
        }

        header.site-header .logout-btn {
            color: #eafaf1;
            /* default putih */
            transition: color 0.3s ease;
        }

        header.site-header.scrolled .logout-btn {
            color: #111;
            /* jadi dark pas discroll */
        }

        header.site-header .dropdown-toggle {
            color: #eafaf1;
            /* default putih */
            transition: color 0.3s ease;
        }

        header.site-header.scrolled .dropdown-toggle {
            color: #111;
            /* jadi dark pas discroll */
        }
    </style>

    <style>
        .neu-btn {
            border: none;
            outline: none;
            width: 35px;
            height: 35px;
            margin: 0 5px;
            border-radius: 12px;
            background: #e0e0e0;
            box-shadow: inset 5px 5px 15px #bebebe,
                inset -5px -5px 15px #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            transition: all 0.2s ease-in-out;
            cursor: pointer;
            transition: all 0.3s ease-in-out;
        }

        .neu-btn:hover {
            box-shadow: inset 20px 20px 120px #bebebe,
                inset -20px -20px 130px #ffffff;
        }
    </style>
    @stack('styles')
    @yield('css')
    <script>
        document.onreadystatechange = function() {
            var state = document.readyState;
            if (state == 'complete') {
                document.getElementById('load-screen').style.display = 'none';
                if (window.initPageLoad) {
                    initPageLoad();
                }
            }
        }
    </script>
</head>

<body>
    <a href="https://chat.whatsapp.com/EG7v7NMd5BpF3QZyYX4TZ6" target="_blank" class="floating-button"
        id="whatsappButton">
        <img src="{{ asset('images/logo/WhatsApp.svg') }}" alt="WhatsApp">
    </a>

    {{-- <div class="dropdown-container" id="dropdownContainer">
        <textarea id="customMessage"></textarea>
        <button onclick="sendMessage()"><i class="fa fa-paper-plane mr-2"></i>Kirim Pesan</button>
    </div> --}}

    <div>
        <!-- [ navigation menu ] start -->
        @include('layouts.navbar')
        <!-- [ navigation menu ] end -->

        <!-- [ Header ] start -->
        @include('layouts.header')
        <!-- [ Header ] end -->

        <!-- [ Main Content ] start -->
        @yield('content')
        <!-- [ Main Content ] end -->

        @include('layouts.footer')
    </div>

    <!-- Warning Section Ends -->
    @include('layouts.js.style_js')
    <script src="{{ asset('js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('js/select2.min.js') }}"></script>
    <script src="{{ asset('js/axios.js') }}"></script>
    <script src="{{ asset('js/restAPI.js') }}"></script>
    <script src="{{ asset('js/toastr.min.js') }}"></script>
    <script src="{{ asset('js/notification.js') }}"></script>
    <script src="{{ asset('js/sweetalert2.js') }}"></script>
    @yield('asset_js')
    <script src="{{ asset('js/flatpickr.js') }}"></script>
    <script src="{{ asset('js/id.js') }}"></script>
    <script>
        function loadingPage(value) {
            if (value == true) {
                document.getElementById('load-screen').style.display = '';
            } else {
                document.getElementById('load-screen').style.display = 'none';
            }
            return;
        }

        function loadingData() {
            let html = `
            <tr class="text-dark loading-row">
                <td class="text-center" colspan="${$('.tb-head th').length}">
                    <svg xmlns:svg="http://www.w3.org/2000/svg" xmlns="http://www.w3.org/2000/svg"
                        xmlns:xlink="http://www.w3.org/1999/xlink" version="1.0" width="162px" height="24px"
                        viewBox="0 0 128 19" xml:space="preserve"><rect x="0" y="0" width="100%" height="100%" fill="#FFFFFF" /><path fill="#1abc9c" d="M0.8,2.375H15.2v14.25H0.8V2.375Zm16,0H31.2v14.25H16.8V2.375Zm16,0H47.2v14.25H32.8V2.375Zm16,0H63.2v14.25H48.8V2.375Zm16,0H79.2v14.25H64.8V2.375Zm16,0H95.2v14.25H80.8V2.375Zm16,0h14.4v14.25H96.8V2.375Zm16,0h14.4v14.25H112.8V2.375Z"/><g><path fill="#c7efe7" d="M128.8,2.375h14.4v14.25H128.8V2.375Z"/><path fill="#c7efe7" d="M144.8,2.375h14.4v14.25H144.8V2.375Z"/><path fill="#9fe3d5" d="M160.8,2.375h14.4v14.25H160.8V2.375Z"/><path fill="#72d6c2" d="M176.8,2.375h14.4v14.25H176.8V2.375Z"/><animateTransform attributeName="transform" type="translate" values="0 0;0 0;0 0;0 0;0 0;0 0;0 0;0 0;0 0;0 0;0 0;0 0;-16 0;-32 0;-48 0;-64 0;-80 0;-96 0;-112 0;-128 0;-144 0;-160 0;-176 0;-192 0" calcMode="discrete" dur="2160ms" repeatCount="indefinite"/></g><g><path fill="#c7efe7" d="M-15.2,2.375H-0.8v14.25H-15.2V2.375Z"/><path fill="#c7efe7" d="M-31.2,2.375h14.4v14.25H-31.2V2.375Z"/><path fill="#9fe3d5" d="M-47.2,2.375h14.4v14.25H-47.2V2.375Z"/><path fill="#72d6c2" d="M-63.2,2.375h14.4v14.25H-63.2V2.375Z"/><animateTransform attributeName="transform" type="translate" values="16 0;32 0;48 0;64 0;80 0;96 0;112 0;128 0;144 0;160 0;176 0;192 0;0 0;0 0;0 0;0 0;0 0;0 0;0 0;0 0;0 0;0 0;0 0;0 0" calcMode="discrete" dur="2160ms" repeatCount="indefinite"/></g>
                    </svg>
                </td>
            </tr>`;

            return html;
        }

        function formatRupiah(value) {
            let number = parseFloat(value) || 0;
            let roundedNumber = Math.round(number);
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0,
            }).format(roundedNumber);
        }

        function notificationAlert(tipe, title, message) {
            swal(
                title,
                message,
                tipe
            );
        }

        async function selectList(selectors, placeholders = null) {
            if (!Array.isArray(selectors)) {
                console.error("Selectors must be an array of element IDs.");
                return;
            }

            selectors.forEach((selector, index) => {
                const element = document.getElementById(selector);
                if (element) {
                    if (element.choicesInstance) {
                        element.choicesInstance.destroy();
                    }

                    const placeholderValue = placeholders?.[index] ?? '';

                    const choicesInstance = new Choices(element, {
                        removeItemButton: true,
                        searchEnabled: true,
                        shouldSort: false,
                        allowHTML: true,
                        placeholder: true,
                        placeholderValue: placeholderValue,
                        noResultsText: 'Tidak ada hasil',
                        itemSelectText: '',
                    });

                    element.choicesInstance = choicesInstance;
                } else {
                    console.warn(`Element with ID "${selector}" not found.`);
                }
            });
        }

        async function setDynamicButton(first = 'btn-primary', second = 'btn-outline-primary') {
            const buttons = document.querySelectorAll('.btn-dynamic');

            buttons.forEach((button) => {
                button.addEventListener('click', () => {
                    if (button.classList.contains(first)) {
                        button.classList.remove(first);
                        button.classList.add(second);
                    } else {
                        button.classList.remove(second);
                        button.classList.add(first);
                    }
                });
            });
        }

        async function selectMulti(optionsArray) {
            const auth_token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            for (const {
                    id,
                    isUrl,
                    placeholder
                }
                of optionsArray) {
                let selectOption = {
                    ajax: {
                        url: isUrl,
                        dataType: 'json',
                        delay: 500,
                        headers: {
                            Authorization: `Bearer ` + auth_token
                        },
                        data: function(params) {
                            let query = {
                                search: params.term,
                                page: params.page || 1,
                                limit: 30,
                                ascending: 1,
                            };
                            return query;
                        },
                        processResults: function(res, params) {
                            let data = res.data;
                            let filteredData = $.map(data, function(item) {
                                return {
                                    id: item.id,
                                    text: item.optional ? `${item.optional} / ${item.text}` : item.text
                                };
                            });
                            return {
                                results: filteredData,
                                pagination: {
                                    more: res.pagination && res.pagination.more
                                }
                            };
                        },
                    },
                    allowClear: true,
                    placeholder: placeholder,
                    multiple: true,
                };

                await $(id).select2(selectOption);
            }
        }

        async function selectData(optionsArray) {
            const auth_token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            for (const {
                    id,
                    isUrl,
                    placeholder,
                    isModal = null,
                    isFilter = {},
                    isDisabled = false,
                    isMinimum = 0,
                    extraFields = null,
                    isForm = false,
                    multiple = false,
                    isImage = false,
                }
                of optionsArray) {

                let errorMessage = "Data tidak ditemukan!";

                let selectOption = {
                    ajax: {
                        url: isUrl,
                        dataType: 'json',
                        delay: 500,
                        headers: {
                            Authorization: `Bearer ${auth_token}`
                        },
                        data: function(params) {
                            return {
                                search: params.term,
                                page: params.page || 1,
                                limit: 30,
                                ascending: 1,
                                ...isFilter,
                            };
                        },
                        processResults: function(res) {
                            let data = res.data;

                            let filteredData = $.map(data, function(item) {
                                let base = {
                                    id: item.id,
                                    text: item.text
                                };

                                if (extraFields) {
                                    if (typeof extraFields === 'object') {
                                        for (let key in extraFields) {
                                            base[key] = item[extraFields[key]] ?? null;
                                        }
                                    } else if (typeof extraFields === 'string') {
                                        base[extraFields] = item[extraFields] ?? null;
                                    }
                                }

                                return base;
                            });

                            return {
                                results: filteredData,
                                pagination: {
                                    more: res.pagination && res.pagination.more
                                }
                            };
                        },
                        error: function(xhr) {
                            if (xhr.status === 400) {
                                errorMessage = xhr.responseJSON?.message ||
                                    "Terjadi kesalahan saat memuat data!";
                            }
                        }
                    },
                    dropdownParent: isModal ? $(isModal) : null,
                    allowClear: true,
                    placeholder: placeholder,
                    dropdownAutoWidth: true,
                    width: '100%',
                    disabled: isDisabled,
                    minimumInputLength: isMinimum,
                    multiple: multiple,
                    language: {
                        errorLoading: function() {
                            return errorMessage;
                        }
                    }
                };

                if (isImage) {
                    selectOption.escapeMarkup = function(m) {
                        return m;
                    };
                    selectOption.templateResult = function(data) {
                        return data.text;
                    };
                    selectOption.templateSelection = function(data) {
                        return data.text;
                    };
                }

                await $(id).select2(selectOption);

                if (isForm && extraFields) {
                    $(id).on('select2:select', function(e) {
                        let data = e.params.data;

                        if (typeof extraFields === 'object') {
                            for (let key in extraFields) {
                                $(`#${key}`).remove();
                                if (data[key]) {
                                    $('<input>').attr({
                                        type: 'hidden',
                                        id: key,
                                        name: key,
                                        value: data[key]
                                    }).appendTo($(this).closest('form'));
                                }
                            }
                        } else if (typeof extraFields === 'string') {
                            $(`#${extraFields}`).remove();
                            if (data[extraFields]) {
                                $('<input>').attr({
                                    type: 'hidden',
                                    id: extraFields,
                                    name: extraFields,
                                    value: data[extraFields]
                                }).appendTo($(this).closest('form'));
                            }
                        }
                    });

                    $(id).on('select2:clear', function() {
                        if (typeof extraFields === 'object') {
                            for (let key in extraFields) {
                                $(`#${key}`).remove();
                            }
                        } else if (typeof extraFields === 'string') {
                            $(`#${extraFields}`).remove();
                        }
                    });
                }
            }
        }

        async function setDatePicker(params = 'tanggal') {
            flatpickr(`#${params}`, {
                locale: "id",
                enableTime: true,
                enableSeconds: true,
                time_24hr: true,
                secondIncrement: 1,
                dateFormat: "Y-m-d H:i:S",
                defaultDate: new Date(),
                allowInput: true,
                appendTo: document.querySelector('.modal-body'),
                position: "above",

                onDayCreate: (dObj, dStr, fp, dayElem) => {
                    dayElem.addEventListener('click', () => {
                        fp.calendarContainer
                            .querySelectorAll('.selected')
                            .forEach(el => {
                                el.style.backgroundColor = "#1abc9c";
                                el.style.color = "#fff";
                            });
                    });
                }
            });

            const inputField = document.querySelector(`#${params}`);
            inputField.removeAttribute("readonly");
            inputField.style.backgroundColor = "";
            inputField.style.cursor = "pointer";
        }
    </script>
    <!-- Required Js -->
    @yield('js')
    <!-- Close Js -->
    @stack('scripts')
    <script>
        const allowedPermissions = @json(View::getShared()['allowedPermissions'] ?? []);

        function hasPermission(identifier) {
            // Kalau array, cek apakah ada salah satu yang cocok
            if (Array.isArray(identifier)) {
                return identifier.some(id => allowedPermissions.map(String).includes(String(id)));
            }

            // Kalau string atau number biasa
            return allowedPermissions.map(String).includes(String(identifier));
        }
    </script>
    <script>
        // Cegah dropdown auto-close saat hover
        $('.drp-user .dropdown-toggle').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).next('.dropdown-menu').toggleClass('show');
        });

        $(document).on('input', '.rupiah', function() {
            let value = this.value.replace(/\D/g, '');
            this.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        });

        // Klik di luar, baru nutup
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.drp-user').length) {
                $('.drp-user .dropdown-menu').removeClass('show');
            }
        });
        window.addEventListener("scroll", function() {
            const header = document.querySelector("header.site-header");
            if (window.scrollY > 10) {
                header.classList.add("scrolled");
            } else {
                header.classList.remove("scrolled");
            }
        });
    </script>
    <script>
        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        }


        document.getElementById('fullscreenBtn')?.addEventListener('click', toggleFullscreen);
        document.getElementById('fullscreenBtnMobile')?.addEventListener('click', toggleFullscreen);
    </script>
</body>

</html>
