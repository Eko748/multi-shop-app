<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <meta name="author" content="GSS">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('images/logo/logo.png') }}">
    <link rel="stylesheet" href="{{ asset('css/login/slick.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/login/aos.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/login/output.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/login/style.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/sweetalert2.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/login/loading.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/fontawesome.min.css') }}">

    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/stylesheet/sweetalert2@11/sweetalert2.min.css">
    <script>
        document.onreadystatechange = function() {
            var state = document.readyState;
            if (state == 'complete') {
                setTimeout(function() {
                    document.getElementById('preloaderLoadingPage').style.display = 'none';
                    const htmlElement = document.documentElement;
                    if (htmlElement.classList.contains('dark')) {
                        htmlElement.classList.remove('dark');
                        htmlElement.classList.add('light');
                    }
                }, 100);
            }
        }
    </script>
    <style>
        /* Styling Tombol Bulat Close di Pojok Kanan Atas Modal */
        .custom-swal-close-btn {
            position: absolute !important;
            top: 16px !important;
            right: 16px !important;
            width: 34px !important;
            height: 34px !important;
            border-radius: 50% !important;
            background-color: #f1f5f9 !important;
            color: #64748b !important;
            font-size: 18px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            transition: all 0.2s ease !important;
            border: 1px solid #e2e8f0 !important;
            outline: none !important;
            box-shadow: none !important;
        }

        .custom-swal-close-btn:hover {
            background-color: #ef4444 !important;
            /* Berubah merah saat di-hover */
            color: #ffffff !important;
            border-color: #ef4444 !important;
            transform: rotate(90deg);
            /* Efek rotasi saat hover */
        }

        .loader {
            position: absolute;
            top: calc(50% - 32px);
            left: calc(50% - 32px);
            width: 64px;
            height: 64px;
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

        .bubbles {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            overflow: hidden;
            z-index: 0;
            pointer-events: none;
        }

        .bubbles span {
            position: absolute;
            bottom: -150px;
            display: block;
            width: 40px;
            height: 40px;
            background: rgba(33, 70, 156, 0.2);
            border-radius: 50%;
            animation: rise 20s infinite ease-in;
        }

        .bubbles span:nth-child(1) {
            left: 10%;
            width: 60px;
            height: 60px;
            animation-duration: 25s;
        }

        .bubbles span:nth-child(2) {
            left: 20%;
            width: 40px;
            height: 40px;
            animation-duration: 18s;
            animation-delay: 2s;
        }

        .bubbles span:nth-child(3) {
            left: 35%;
            width: 20px;
            height: 20px;
            animation-duration: 12s;
            animation-delay: 4s;
        }

        .bubbles span:nth-child(4) {
            left: 50%;
            width: 80px;
            height: 80px;
            animation-duration: 30s;
            animation-delay: 1s;
        }

        .bubbles span:nth-child(5) {
            left: 65%;
            width: 50px;
            height: 50px;
            animation-duration: 22s;
            animation-delay: 3s;
        }

        .bubbles span:nth-child(6) {
            left: 75%;
            width: 25px;
            height: 25px;
            animation-duration: 15s;
            animation-delay: 5s;
        }

        .bubbles span:nth-child(7) {
            left: 85%;
            width: 55px;
            height: 55px;
            animation-duration: 28s;
            animation-delay: 2s;
        }

        .bubbles span:nth-child(8) {
            left: 90%;
            width: 35px;
            height: 35px;
            animation-duration: 18s;
            animation-delay: 6s;
        }

        .bubbles span:nth-child(9) {
            left: 40%;
            width: 45px;
            height: 45px;
            animation-duration: 20s;
            animation-delay: 4s;
        }

        .bubbles span:nth-child(10) {
            left: 60%;
            width: 30px;
            height: 30px;
            animation-duration: 17s;
            animation-delay: 7s;
        }

        @keyframes rise {
            0% {
                transform: translateY(0) scale(1);
                opacity: 0.6;
            }

            50% {
                opacity: 0.9;
            }

            100% {
                transform: translateY(-120vh) scale(1.2);
                opacity: 0;
            }
        }
    </style>
</head>

<body>
    <div class="bubbles">
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
    </div>

    <div id="preloaderLoadingPage">
        <div class="sk-three-bounce">
            <div class="centerpreloader">
                <div class="loader">
                    <div class="inner one"></div>
                    <div class="inner two"></div>
                    <div class="inner three"></div>
                </div>
            </div>
        </div>
    </div>

    <section class="bg-white dark:bg-darkblack-500">
        <div class="flex flex-col lg:flex-row justify-between min-h-screen">
            <div class="lg:w-1/2 px-5 xl:pl-12 pt-10 mt-8">
                <div class="max-w-[450px] m-auto pt-24 pb-16 mt-8">
                    <header class="text-center mb-8 mt-8">
                        {{-- <center>
                            <img src="{{ asset('images/logo/logo-slogan.png') }}" class="block dark:hidden"
                                style="width: 90%" />
                            <img src="{{ asset('images/logo/logo-slogan.png') }}" class="hidden dark:block"
                                style="width: 90%" />
                        </center> --}}
                        <p class="font-urbanis text-base font-medium text-bgray-600 pt-2 dark:text-bgray-50">
                            <b>MASUK KE APLIKASI</b>
                        </p>
                        <div id="error-message"
                            class="hidden my-3 p-3 rounded-lg bg-danger text-dark border border-red-600 text-sm font-medium transition-all duration-300">
                        </div>
                        @if ($errors->any())
                            @foreach ($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        @endif
                    </header>

                    <div class="mb-4 relative">
                        <input type="text" id="username" autocomplete="off"
                            class="text-bgray-800 text-base border border-bgray-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white h-14 w-full focus:border-success-300 focus:ring-0 rounded-lg px-4 py-3.5 placeholder:text-bgray-500 placeholder:text-base"
                            placeholder="Username" />
                    </div>
                    <div class="mb-6 relative">
                        <input type="password" id="password"
                            class="text-bgray-800 text-base border border-bgray-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white h-14 w-full focus:border-success-300 focus:ring-0 rounded-lg px-4 py-3.5 placeholder:text-bgray-500 placeholder:text-base"
                            placeholder="Kata sandi" />
                        <button type="button" id="togglePassword" class="absolute top-4 right-4 bottom-4">
                            <svg id="eyeIcon" width="22" height="20" viewBox="0 0 22 20" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path d="M2 1L20 19" stroke="#718096" stroke-width="1.5" stroke-linecap="round"
                                    stroke-linejoin="round" />
                                <path
                                    d="M9.58445 8.58704C9.20917 8.96205 8.99823 9.47079 8.99805 10.0013C8.99786 10.5319 9.20844 11.0408 9.58345 11.416C9.95847 11.7913 10.4672 12.0023 10.9977 12.0024C11.5283 12.0026 12.0372 11.7921 12.4125 11.417"
                                    stroke="#718096" stroke-width="1.5" stroke-linecap="round"
                                    stroke-linejoin="round" />
                                <path
                                    d="M8.363 3.36506C9.22042 3.11978 10.1082 2.9969 11 3.00006C15 3.00006 18.333 5.33306 21 10.0001C20.222 11.3611 19.388 12.5241 18.497 13.4881M16.357 15.3491C14.726 16.4491 12.942 17.0001 11 17.0001C7 17.0001 3.667 14.6671 1 10.0001C2.369 7.60506 3.913 5.82506 5.632 4.65906"
                                    stroke="#718096" stroke-width="1.5" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </button>
                    </div>
                    <button type="button" id="btn-login" onclick="submitLogin()"
                        style="background-color: rgb(33 70 156);"
                        class="py-3.5 flex items-center justify-center text-white font-bold bg-success-300 hover:bg-success-400 transition-all rounded-lg w-full">
                        Masuk
                    </button>
                    <p class="text-bgray-600 dark:text-white text-center text-sm mt-6">
                        &copy; {{ now()->year }}<br>All Right Reserved
                    </p>
                </div>
            </div>
            <div
                class="hidden sm:block sm:w-full md:w-3/4 lg:w-1/2 dark:bg-darkblack-600 p-6 sm:p-10 md:p-16 lg:p-20 relative">
                <ul>
                    <li class="absolute top-4 sm:top-6 md:top-10 left-4 sm:left-6 md:left-8">
                        <img src="{{ asset('images/shapes/vline.svg') }}" alt="" />
                    </li>
                    <li class="absolute right-6 sm:right-10 md:right-12 top-6 sm:top-10 md:top-14">
                        <img src="{{ asset('images/shapes/square.svg') }}" alt="" />
                    </li>
                    <li class="absolute bottom-4 sm:bottom-6 md:bottom-7 left-4 sm:left-6 md:left-8">
                        <img src="{{ asset('images/shapes/dotted.svg') }}" alt="" />
                    </li>
                </ul>
                <div class="mb-2">
                    <img src="{{ asset('images/shapes/login.svg') }}"
                        class="mx-auto max-w-[400px] sm:max-w-[250px] md:max-w-[300px]" />
                </div>
                <div class="text-center max-w-lg px-3 sm:px-4 md:px-4 lg:px-2 mx-auto">
                    <h4
                        class="text-bgray-900 dark:text-white font-semibold font-popins text-xl sm:text-3xl md:text-4xl mb-3 sm:mb-4">
                        Transaksi, Pengiriman dan Penjualan
                    </h4>
                    <p class="text-bgray-600 dark:text-bgray-50 text-xs sm:text-sm md:text-base font-medium">
                        Aplikasi yang dibangun untuk mencatat transaksi, melakukan pengiriman dan penjualan, serta
                        menyajikan laporan pendapatan yang terperinci.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!--scripts -->

    <script src="{{ asset('js/jquery.js') }}"></script>

    <script src="{{ asset('js/login/aos.js') }}"></script>
    <script src="{{ asset('js/login/slick.min.js') }}"></script>
    <script>
        AOS.init();
    </script>
    <script src="{{ asset('js/sweetalert2.js') }}"></script>
    <script src="{{ asset('js/axios.js') }}"></script>
    <script src="{{ asset('js/restAPI.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.querySelectorAll('#username, #password').forEach(input => {
            input.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    submitLogin();
                }
            });
        });

        const passwordInput = document.getElementById('password');
        const togglePasswordButton = document.getElementById('togglePassword');
        const eyeIcon = document.getElementById('eyeIcon');

        togglePasswordButton.addEventListener('click', () => {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');

            if (isPassword) {
                eyeIcon.innerHTML = `<path
                d="M8.363 3.36506C9.22042 3.11978 10.1082 2.9969 11 3.00006C15 3.00006 18.333 5.33306 21 10.0001C20.222 11.3611 19.388 12.5241 18.497 13.4881M16.357 15.3491C14.726 16.4491 12.942 17.0001 11 17.0001C7 17.0001 3.667 14.6671 1 10.0001C2.369 7.60506 3.913 5.82506 5.632 4.65906"
                stroke="#718096" stroke-width="1.5" stroke-linecap="round"
                stroke-linejoin="round" />
            `;
            } else {
                eyeIcon.innerHTML = `<path d="M2 1L20 19" stroke="#718096" stroke-width="1.5" stroke-linecap="round"
                                    stroke-linejoin="round" />
                                <path
                                    d="M9.58445 8.58704C9.20917 8.96205 8.99823 9.47079 8.99805 10.0013C8.99786 10.5319 9.20844 11.0408 9.58345 11.416C9.95847 11.7913 10.4672 12.0023 10.9977 12.0024C11.5283 12.0026 12.0372 11.7921 12.4125 11.417"
                                    stroke="#718096" stroke-width="1.5" stroke-linecap="round"
                                    stroke-linejoin="round" />
                                <path
                                    d="M8.363 3.36506C9.22042 3.11978 10.1082 2.9969 11 3.00006C15 3.00006 18.333 5.33306 21 10.0001C20.222 11.3611 19.388 12.5241 18.497 13.4881M16.357 15.3491C14.726 16.4491 12.942 17.0001 11 17.0001C7 17.0001 3.667 14.6671 1 10.0001C2.369 7.60506 3.913 5.82506 5.632 4.65906"
                                    stroke="#718096" stroke-width="1.5" stroke-linecap="round"
                                    stroke-linejoin="round" />`;
            }
        });

        function loadingPage(show) {
            if (show) {
                document.getElementById('preloaderLoadingPage').style.display = '';
            } else {
                document.getElementById('preloaderLoadingPage').style.display = 'none';
            }
        }

        function notificationAlert(type, title, message) {
            swal(
                title,
                message,
                type
            );
        }

        let errorTimeout = null;
        let isSubmitting = false;

        function showErrorText(msg) {
            let $errBox = $('#error-message');

            // 1. Reset timer sebelumnya jika user spam klik/login berkali-kali
            if (errorTimeout) {
                clearTimeout(errorTimeout);
            }

            // 2. Set pesan dan tampilkan elemen
            $errBox.html(msg).removeClass('hidden').show();

            // 3. Otomatis sembunyikan kembali setelah 3000ms (3 detik)
            errorTimeout = setTimeout(function() {
                $errBox.fadeOut(300, function() {
                    $(this).addClass('hidden').html('');
                });
            }, 3000);
        }

        // Helper untuk memperbarui Token CSRF di seluruh DOM & jQuery Setup
        function updateCsrfToken(newToken) {
            if (!newToken) return;

            // 1. Update Meta Tag & Input Hidden
            $('meta[name="csrf-token"]').attr('content', newToken);
            $('input[name="_token"]').val(newToken);

            // 2. Update Default Header jQuery AJAX
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': newToken,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
        }

        // Helper Reset Form Login
        function resetFormLogin() {
            $('#password').val('');
            $('#username, #password').prop('disabled', false);
            $('button[type="submit"], .btn-login').prop('disabled', false);
            $('#password').focus();
        }

        // --- 1. FUNCTION SUBMIT LOGIN ---
        window.submitLogin = function(element) {
            if (isSubmitting) return; // Hentikan jika sedang proses kirim
            isSubmitting = true;
            let container = element ? $(element).closest('form, div') : $(document);
            let username = $('#username').val() || container.find('input[name="username"]').val();
            let password = $('#password').val() || container.find('input[name="password"]').val();
            let csrfToken = $('meta[name="csrf-token"]').attr('content');

            // Validasi Input Kosong
            if (!username || !password) {
                showErrorText('Username dan Password wajib diisi.');
                return;
            }

            if (typeof loadingPage === 'function') loadingPage(true);
            $('.btn-login, #btn-login, button[type="submit"]').prop('disabled', true);

            $.ajax({
                url: '{{ route('post_login') }}',
                type: 'POST',
                data: {
                    _token: csrfToken,
                    username: username,
                    password: password
                },
                success: function(response) {
                    if (response.new_csrf_token) {
                        updateCsrfToken(response.new_csrf_token);
                    }

                    // Handle jika HTTP status 200 namun mengembalikan error/status 300
                    if (response.status_code === 300 || response.error === true) {
                        showErrorText(response.message || 'Username atau password salah.');
                        resetFormLogin();
                        return;
                    }

                    // Redirect / Modal Pilih Toko
                    if (response.data && response.data.show_toko_selection) {
                        showTokoModal(response.data.daftar_toko, response.data.route_redirect);
                    } else if (response.data && response.data.route_redirect) {
                        window.location.href = response.data.route_redirect;
                    }
                },
                error: function(xhr) {
                    let res = xhr.responseJSON;
                    let message = res?.message || 'Username atau password salah.';

                    // Tampilkan pesan error 3 detik
                    showErrorText(message);

                    if (res && res.new_csrf_token) {
                        updateCsrfToken(res.new_csrf_token);
                    }

                    resetFormLogin();
                },
                complete: function() {
                    isSubmitting = false; // Reset status setelah request selesai
                    if (typeof loadingPage === 'function') loadingPage(false);
                }
            });
        };

        // Helper untuk Render Pesan Error ke HTML
        function showErrorText(msg) {
            $('#error-message').removeClass('hidden').html(msg);
        }

        // Trigger saat Tombol Klik (opsional jika tombol punya class/id)
        $(document).on('click', '#btn-login, .btn-login', function(e) {
            e.preventDefault();
            submitLogin();
        });

        // Trigger Enter Key pada Input Password
        $(document).on('keypress', '#username, #password', function(e) {
            if (e.which === 13) { // Keycode Enter
                e.preventDefault();
                submitLogin();
            }
        });

        // --- 2. FUNCTION SHOW TOKO MODAL ---
        window.showTokoModal = async function(daftarToko, defaultRedirect) {
            let cardsHtml = `
        <div class="toko-card" data-id="ALL" onclick="selectTokoCard(this, '${defaultRedirect}')" style="
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 14px 16px;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            background: #ffffff;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        " onmouseover="this.style.borderColor='#2563eb'; this.style.transform='translateY(-3px)'; this.style.boxShadow='0 10px 15px -3px rgba(37,99,235,0.1)'"
           onmouseout="this.style.borderColor='#e2e8f0'; this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.02)'">

            <div style="
                background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
                color: #ffffff;
                border-radius: 50%;
                width: 44px;
                height: 44px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 700;
                font-size: 18px;
                flex-shrink: 0;
                box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3);
            ">
                🌟
            </div>

            <div style="text-align: left; min-width: 0; flex: 1;">
                <div style="font-weight: 700; color: #0f172a; font-size: 14px; line-height: 1.2;">SEMUA TOKO</div>
                <div style="font-size: 12px; color: #64748b; margin-top: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    Akses seluruh toko
                </div>
            </div>
        </div>
    `;

            if (Array.isArray(daftarToko)) {
                daftarToko.forEach(toko => {
                    let namaToko = toko.nama || toko.nama_toko || 'Toko ' + toko.id;
                    let alamatToko = toko.alamat ? toko.alamat : 'Alamat tidak tersedia';
                    let singkatanToko = toko.singkatan ? toko.singkatan.trim() : (namaToko.charAt(0) || toko
                        .id);

                    let fontSize = '15px';
                    if (singkatanToko.length === 3) fontSize = '12px';
                    else if (singkatanToko.length === 4) fontSize = '10px';
                    else if (singkatanToko.length > 4) fontSize = '9px';

                    cardsHtml += `
                <div class="toko-card" data-id="${toko.id}" onclick="selectTokoCard(this, '${defaultRedirect}')" style="
                    border: 1px solid #e2e8f0;
                    border-radius: 14px;
                    padding: 14px 16px;
                    cursor: pointer;
                    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
                    background: #ffffff;
                    display: flex;
                    align-items: center;
                    gap: 14px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
                " onmouseover="this.style.borderColor='#2563eb'; this.style.transform='translateY(-3px)'; this.style.boxShadow='0 10px 15px -3px rgba(37,99,235,0.1)'"
                   onmouseout="this.style.borderColor='#e2e8f0'; this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.02)'">

                    <div style="
                        background: #f1f5f9;
                        color: #1e293b;
                        border-radius: 50%;
                        width: 44px;
                        height: 44px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-weight: 700;
                        font-size: ${fontSize};
                        letter-spacing: -0.5px;
                        flex-shrink: 0;
                        border: 1px solid #cbd5e1;
                        text-transform: uppercase;
                        overflow: hidden;
                        padding: 2px;
                    ">
                        ${singkatanToko}
                    </div>

                    <div style="text-align: left; min-width: 0; flex: 1;">
                        <div style="font-weight: 700; color: #0f172a; font-size: 14px; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${namaToko}">
                            ${namaToko}
                        </div>
                        <div style="font-size: 12px; color: #64748b; margin-top: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${alamatToko}">
                            ${alamatToko}
                        </div>
                    </div>
                </div>
            `;
                });
            }

            Swal.fire({
                title: '<span style="font-size: 22px; font-weight: 800; color: #0f172a;">Pilih Toko</span>',
                html: `
            <p style="font-size: 13px; color: #64748b; margin-bottom: 20px;">
                Klik salah satu toko di bawah ini untuk melanjutkan ke dashboard:
            </p>
            <div id="toko-grid-container" style="
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
                gap: 14px;
                max-height: 380px;
                overflow-y: auto;
                padding: 4px;
            ">
                ${cardsHtml}
            </div>
        `,
                showConfirmButton: false,
                showCancelButton: false,
                showCloseButton: true,
                allowOutsideClick: false,
                allowEscapeKey: false,
                width: '680px',
                padding: '1.75rem',
                customClass: {
                    closeButton: 'custom-swal-close-btn'
                }
            }).then(async (result) => {
                if (result.dismiss === Swal.DismissReason.close) {
                    loadingPage(true);

                    try {
                        let response = await renderAPI('POST', '{{ route('post_cancel_login') }}', {});

                        // PERBARUI CSRF TOKEN DI BROWSER DENGAN TOKEN BARU DARI SERVER
                        if (response && response.data.new_csrf_token) {
                            $('meta[name="csrf-token"]').attr('content', response.data.new_csrf_token);
                            $('input[name="_token"]').val(response.data.new_csrf_token);
                        }
                    } catch (err) {
                        console.error("Gagal melakukan cancel login:", err);
                    } finally {
                        loadingPage(false);
                        resetFormLogin();
                        notificationAlert('info', 'Info',
                            'Login dibatalkan. Silakan masukkan kembali data login.');
                    }
                }
            });
        };

        // --- 3. FUNCTION SELECT TOKO CARD ---
        window.selectTokoCard = async function(element, redirectUrl) {
            let tokoId = $(element).attr('data-id') || $(element).data('id');
            let currentToken = $('meta[name="csrf-token"]').attr('content');

            if (typeof loadingPage === 'function') loadingPage(true);

            try {
                let response = await $.ajax({
                    url: '{{ route('post_select_toko') }}',
                    type: 'POST',
                    contentType: 'application/json',
                    dataType: 'json',
                    headers: {
                        'X-CSRF-TOKEN': currentToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    xhrFields: {
                        withCredentials: true
                    },
                    data: JSON.stringify({
                        _token: currentToken,
                        toko_id: tokoId
                    })
                });

                if (response.status_code === 200 || response.error === false) {
                    window.location.href = response.route_redirect || redirectUrl || '/dashboard';
                } else {
                    Swal.fire('Gagal', response.message || 'Gagal memilih toko.', 'error');
                }
            } catch (xhr) {
                console.error("Error Select Toko:", xhr);
                Swal.fire('Error', xhr.responseJSON?.message || 'Terjadi kesalahan CSRF / Session.', 'error');
            } finally {
                if (typeof loadingPage === 'function') loadingPage(false);
            }
        };
    </script>
    <script>
        document.addEventListener("mousemove", function(e) {
            const bubbles = document.querySelectorAll(".bubbles span");
            const moveX = (e.clientX / window.innerWidth - 0.5) * 20;
            const moveY = (e.clientY / window.innerHeight - 0.5) * 20;

            bubbles.forEach((bubble, index) => {
                let speed = (index + 1) * 0.05;
                bubble.style.transform = `translate(${moveX * speed}px, ${moveY * speed}px)`;
            });
        });
    </script>

</body>

</html>
