    <link rel="stylesheet" href="{{ asset('flat-able-lite/dist/assets/css/plugins/prism-coy.css') }}">
    <link rel="stylesheet" href="{{ asset('flat-able-lite/dist/assets/css/style.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
    <style>
        /* Styling Navbar Horizontal Able Pro */
.pcoded-navbar.theme-horizontal .pcoded-inner-navbar>li>a {
    padding: 8px 10px !important; /* Mengecilkan jarak antar menu */
    font-size: 13px !important;   /* Mengecilkan ukuran tulisan sedikit */
}

/* Mengecilkan icon menu agar lebih hemat tempat */
.pcoded-navbar.theme-horizontal .pcoded-inner-navbar>li>a .pcoded-micon {
    margin-right: 5px !important;
}

/* Mengubah layout agar menu fleksibel jika layar kurang lebar */
.pcoded-navbar.theme-horizontal .pcoded-inner-navbar {
    display: flex !important;
    flex-wrap: wrap !important;
}

/* 1. Ratakan kotak submenu paling kanan ke sisi kanan induknya */
.pcoded-navbar.theme-horizontal .pcoded-inner-navbar>li.pcoded-hasmenu:last-child .pcoded-submenu,
.pcoded-navbar.theme-horizontal .pcoded-inner-navbar>li.pcoded-hasmenu:nth-last-child(2) .pcoded-submenu {
    left: auto !important;
    right: 0 !important;
    transform: none !important;
}

/* 2. Pindahkan panah kecil (arrow) di atas submenu agar bergeser ke kanan */
.pcoded-navbar.theme-horizontal .pcoded-inner-navbar>li.pcoded-hasmenu:last-child>.pcoded-submenu:before,
.pcoded-navbar.theme-horizontal .pcoded-inner-navbar>li.pcoded-hasmenu:nth-last-child(2)>.pcoded-submenu:before {
    left: auto !important;
    right: 5px !important;
}

/* 3. Rapikan panah kecil sisi (chevron/arrow) di dalam list */
.pcoded-navbar.theme-horizontal .pcoded-inner-navbar>li.pcoded-hasmenu:last-child .pcoded-submenu li>a,
.pcoded-navbar.theme-horizontal .pcoded-inner-navbar>li.pcoded-hasmenu:nth-last-child(2) .pcoded-submenu li>a {
    text-align: left !important;
}


/* ==========================================================================
   PENGATURAN SUBMENU (2 KOLOM VS 1 KOLOM KE BAWAH)
   ========================================================================== */

/* A. DEFAULT SUBMENU (Tampilan Ke Bawah / 1 Kolom untuk Menu Tanpa Kategori) */
.pcoded-navbar.theme-horizontal .pcoded-inner-navbar>li.pcoded-hasmenu .pcoded-submenu {
    display: block !important;
    min-width: 230px !important;
    padding: 10px !important;
}

.pcoded-navbar.theme-horizontal .pcoded-submenu>li {
    width: 100% !important; /* Item tampil penuh ke bawah */
}

/* B. SUBMENU KHUSUS YANG MEMILIKI KATEGORI (2 Kolom Menyamping) */
.pcoded-navbar.theme-horizontal .pcoded-inner-navbar>li.pcoded-hasmenu .pcoded-submenu:has(li.font-weight-bold) {
    display: flex !important;
    flex-wrap: wrap !important;
    min-width: 480px !important; /* Lebarkan kontainer dropdown */
    padding: 15px !important;
    gap: 10px;
}

/* Mengatur grup/kategori agar membagi area 2 kolom secara rapi */
.pcoded-navbar.theme-horizontal .pcoded-submenu:has(li.font-weight-bold)>li.font-weight-bold {
    width: 100% !important; /* Judul kategori tetap di atas */
    border-bottom: 1px solid #eee;
    padding-bottom: 4px;
    margin-top: 2px !important;
}

/* Setiap item menu pada submenu berkategori mengambil 50% lebar (2 kolom) */
.pcoded-navbar.theme-horizontal .pcoded-submenu:has(li.font-weight-bold)>li:not(.font-weight-bold) {
    width: calc(50% - 5px) !important;
}


/* Hover Effect (Sesuai kode awal) */
.pcoded-navbar.theme-horizontal .pcoded-submenu>li:not(.font-weight-bold)>a:hover {
    color: #10b981 !important;
    background: #ffffff !important;
    box-shadow: 3px 3px 6px #d1d5db,
                -3px -3px 6px #ffffff,
                inset 0px 0px 0px 1px rgba(16, 185, 129, 0.2) !important;
    transform: translateY(-1px);
}

        .neu-btn.active {
            background: #e9f5ff;
            border: 1px solid #47c339;
            color: #47c339;
            font-weight: 600;
        }

        .neu-btn.active::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 100%;
            height: 3px;
            background: #47c339;
            border-radius: 2px;
        }

        .form-check-input {
            width: 50px;
            height: 25px;
            position: relative;
            appearance: none;
            background-color: #c33939;
            border-radius: 25px;
            outline: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .form-check-input::before {
            content: '';
            position: absolute;
            width: 21px;
            height: 21px;
            top: 2px;
            left: 2px;
            background-color: white;
            border-radius: 50%;
            transition: transform 0.3s;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .form-check-input:checked {
            background-color: #47c339;
        }

        .form-check-input:checked::before {
            transform: translateX(25px);
        }

        .form-check-label {
            margin-left: 10px;
            font-size: 16px;
            cursor: pointer;
        }
    </style>

    <style>
        .new_footer_top .footer_bg {
            position: absolute;
            bottom: 0;
            background: url("{{ asset('images/footer/footer_bg.png') }}") no-repeat center 0;
            width: 100%;
            height: 266px;
        }

        .new_footer_top .footer_bg .footer_bg_one {
            background: url("{{ asset('images/footer/volks.gif') }}") no-repeat center center;
            width: 330px;
            height: 105px;
            background-size: 100%;
            position: absolute;
            bottom: 0;
            left: 30%;
            animation: myfirst 22s linear infinite;
        }

        .new_footer_top .footer_bg .footer_bg_two {
            background: url("{{ asset('images/footer/cyclist.gif') }}") no-repeat center center;
            width: 88px;
            height: 100px;
            background-size: 100%;
            bottom: 0;
            left: 38%;
            position: absolute;
            -webkit-animation: myfirst 30s linear infinite;
            animation: myfirst 30s linear infinite;
        }

        .b-brand b {
            font-family: 'Orbitron', sans-serif;
            font-size: 30px;
            color: white;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(5, 5, 5, 0.5);
        }

        .dropdown .dropdown-menu {
            display: none;
            /* Hanya muncul saat di-hover */
            opacity: 0;
            transform: translateY(-10px);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        .dropdown:hover .dropdown-menu {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        /* Mengurangi padding dan mengatur jarak antar baris */
        #jsTable thead th {
            font-weight: bold;
            /* Font tebal untuk penekanan */
            text-transform: uppercase;
            /* (Opsional) Semua huruf kapital */
            padding: 5px;
            /* Sedikit padding untuk thead */
            vertical-align: middle;
            line-height: 3;
            font-size: 15px;
        }

        #jsTable tbody td {
            padding: 5px;
            /* Sesuaikan padding untuk jarak antar sel */
            line-height: 1.2;
            /* Sesuaikan tinggi baris agar cukup untuk teks panjang */
            vertical-align: middle;
            font-size: 14px;
            word-wrap: break-word;
            /* Pecah kata panjang */
            word-break: break-word;
            /* Tambahkan dukungan pemecahan kata */
            white-space: normal;
            /* Izinkan teks membuat baris baru */
            overflow-wrap: break-word;
            /* Pecah teks jika terlalu panjang */
            max-width: 150px;
            /* Atur lebar maksimum kolom */
        }

        /* Efek hover untuk baris */
        .table.table-striped tbody tr:hover {
            background-color: #99a8b3d3;
            /* Warna background seluruh baris saat di-hover */
            transition: background-color 0.3s ease;
            transform: scale(1.008);
            transform-origin: center;
        }

        .table-striped thead {
            background-color: #dcf6df;
            color: #1900ff;
            border-bottom: 2px solid #0056b3;
            /* Garis bawah */
        }

        /* Informasi tambahan di luar tabel */
        .info-wrapper {
            max-width: 100%;
            /* Lebar fleksibel untuk kolom */
            margin-bottom: 15px;
            /* Spasi antara informasi dan tabel */
        }

        .info-row {
            display: flex;
            padding: 4px 0;
            /* Spasi antar baris */
        }

        .label {
            width: 150px;
            /* Atur lebar label tetap untuk meratakan titik dua */
            margin: 0;
            font-weight: bold;
            /* Opsional: untuk membedakan label dari nilai */
        }

        .value {
            margin: 0;
            text-align: left;
            /* Pastikan teks rata kiri */
        }

        /* Atur lebar khusus untuk kolom tertentu */
        .table-responsive th:nth-child(2),
        .table-responsive td:nth-child(2) {
            /* Nama Barang */
            max-width: 150px;
        }

        .table-responsive th:nth-child(4),
        .table-responsive td:nth-child(4) {
            /* Harga */
            max-width: 100px;
        }

        .table-responsive-js table {
            table-layout: fixed;
            /* Pastikan tabel memiliki lebar tetap */
            width: 100%;
        }

        /* Atur lebar kolom agar otomatis sesuai konten */
        .table-responsive-js th,
        .table-responsive-js td {
            word-wrap: break-word;
            /* Izinkan teks panjang dipotong */
            white-space: normal;
            /* Izinkan teks membuat baris baru */
            padding: 5px;
            /* Mengurangi jarak antar kolom */
            overflow-wrap: break-word;
            /* Tambahan untuk browser modern */
        }

        .narrow-column {
            width: 7%;
            /* atau atur ke lebar sesuai keinginan, misalnya 5% atau 50px */
        }

        .wide-column {
            width: 40%;
            /* Lebih luas untuk kolom Nama Barang */
            white-space: nowrap;
            /* Menjaga agar konten tetap dalam satu baris, jika memungkinkan */
        }

        .price-column {
            width: auto;
            /* Biarkan kolom harga mengikuti ukuran kontennya */
            text-align: right;
            /* Mengatur teks di sisi kanan untuk tampilan harga */
        }
    </style>
