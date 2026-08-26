@php
    $apk = env('APP_NAME', 'GSS');

    if (auth()->user()->role_id == 1) {
        $activeTokoId = session('active_toko_id', 'ALL');

        if ($activeTokoId !== 'ALL') {
            // Ambil singkatan dari session atau query toko jika memilih toko spesifik
            $apk = session('active_toko_singkatan')
                ?? optional(auth()->user()->toko)->singkatan
                ?? env('APP_NAME', 'GSS');
        } else {
            $apk = env('APP_NAME', 'GSS') . ' (ALL)';
        }
    } else {
        // Menggunakan optional() agar aman jika relasi toko bernilai null
        $apk = optional(auth()->user()->toko)->singkatan ?? env('APP_NAME', 'GSS');
    }
@endphp

<footer class="app-footer">
    <div class="footer-container">
        <!-- Brand -->
        <div class="footer-col">
            <h3 class="footer-logo">
                {{ $apk }}
            </h3>
            <p class="footer-desc">
                Aplikasi yang dibangun untuk mencatat transaksi, melakukan pengiriman dan penjualan, serta
                menyajikan laporan pendapatan yang terperinci.
            </p>
        </div>

        <!-- Navigation -->
        <div class="footer-col">
            <h4>Menu</h4>
            <ul>
                <li><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
                <li><a href="{{ route('transaksi.kasir.index') }}">Transaksi Kasir</a></li>
                <li><a href="{{ route('td.penjualanNonfisik.index') }}">Transaksi Digital</a></li>
                <li><a href="{{ route('laporankeuangan.aruskas.index') }}">Arus Kas</a></li>
            </ul>
        </div>

        <!-- Info -->
        <div class="footer-col">
            <h4>Informasi</h4>
            <ul>
                <li><a href="#">Tentang Aplikasi</a></li>
                <li><a href="#">Kebijakan Privasi</a></li>
                <li><a href="#">Syarat & Ketentuan</a></li>
                <li><a href="#">Bantuan</a></li>
            </ul>
        </div>

        <!-- Contact -->
        <div class="footer-col">
            <h4>Kontak</h4>
            <p>Email: {{ strtolower(str_replace(' ', '', $apk)) }}.lumoa@toko.app</p>
            <p>WhatsApp: +62 812-3456-7890</p>
            <span class="footer-badge">🔒 Secure Transaction</span>
        </div>
    </div>

    <div class="footer-bottom">
        © {{ now()->year }} {{ $apk }}. All rights reserved.
    </div>
</footer>
