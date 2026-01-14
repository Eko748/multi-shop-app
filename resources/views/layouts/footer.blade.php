{{-- <footer class="site-footer new_footer_area bg_color p-4">
    <div class="new_footer_top">
        <div class="footer_bg">
            <div class="row justify-content-between">
                <div class="col-lg-3 col-md-6 d-flex justify-content-start">
                    <div class="f_widget social-widget wow fadeInLeft" data-wow-delay="0.8s"
                        style="visibility: visible; animation-delay: 0.8s; animation-name: fadeInLeft;">
                        <h3 class="f-title f_600 t_color f_size_18">Kontak Kami</h3>
                        <div id="kontak" class="f_social_icon">
                            <a href="https://chat.whatsapp.com/EG7v7NMd5BpF3QZyYX4TZ6" target="_blank" class="fab fa-whatsapp"></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="footer_bg_one"></div>
            <div class="footer_bg_two"></div>
        </div>
    </div>
    <div class="footer_bottom">
        <div class="row align-items-center">
            <div class="col-lg-12 col-sm-12">
                <p class="mb-0 f_400">&copy; Copyright {{ now()->year }}
                    {{ env('APP_NAME') ?? 'GSS' }}</p>
            </div>
        </div>
    </div>
</footer> --}}
@php
    $apk = '';
    if (auth()->user()->id_level == 1) {
            $apk = env('APP_NAME') ?? 'GSS';
        }
    else {
            $apk = Auth::user()->toko->singkatan;
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
            <p>Email: {{ $apk }}.lumoa@toko.app</p>
            <p>WhatsApp: +62 812-3456-7890</p>
            <span class="footer-badge">🔒 Secure Transaction</span>
        </div>
    </div>

    <div class="footer-bottom">
        © {{ now()->year }} {{ $apk }} . All rights reserved.
    </div>
</footer>
