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
    <div class="footer-bottom">
        © {{ now()->year }} {{ $apk }}. All rights reserved.
    </div>
</footer>
