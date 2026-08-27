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
    <title>
        @yield('title') |
        @if (auth()->user()->toko && auth()->user()->toko->singkatan)
            {{ auth()->user()->toko->singkatan }}
        @else
            {{ env('APP_NAME', 'GSS') }}
        @endif
    </title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('images/logo/logo.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/fontawesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/flatpickr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/main.css') }}?v={{ filemtime(public_path('css/main.css')) }}">
    @include('layouts.css.style_css')
    @stack('styles')
    @yield('css')
    <script>
        window.ALL = null;

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
    <div>
        @include('layouts.navbar')
        @include('layouts.header')
        @yield('content')
        @include('layouts.footer')
    </div>

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
    <script src="{{ asset('js/main.js') }}?v={{ filemtime(public_path('js/main.js')) }}"></script>
    @yield('js')
    @stack('scripts')

    <script>
        const allowedPermissions = @json(View::getShared()['allowedPermissions'] ?? []);
        window.activeTokoId = {{ session('active_toko_id', auth()->user()->toko_id ?? 'ALL') }};

        $(document).ready(function() {
            $('#dropdownTokoHeaderBtn').on('show.bs.dropdown', function() {
                let $menu = $(this).next('.dropdown-menu');
                $('body').append($menu.detach());
                let offset = $(this).offset();
                $menu.css({
                    'display': 'block',
                    'top': offset.top + $(this).outerHeight() + 4,
                    'left': offset.left,
                    'z-index': 999999,
                    'position': 'absolute',
                    'background-color': '#ffffff',
                    'opacity': '1'
                });
            });

            $('#dropdownTokoHeaderBtn').on('hide.bs.dropdown', function() {
                let $menu = $('body').find('.dropdown-menu[aria-labelledby="dropdownTokoHeaderBtn"]');
                $(this).after($menu.detach());
                $menu.css('display', '');
            });
        });

        function switchTokoHeader(tokoId) {
            const loadingOverlay = document.getElementById('global-loading-overlay');
            if (loadingOverlay) {
                loadingOverlay.style.display = 'block';
            }

            fetch("{{ route('switch.toko') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        toko_id: tokoId
                    })
                })
                .then(response => {
                    if (response.ok) {
                        window.location.reload();
                    } else {
                        if (loadingOverlay) loadingOverlay.style.display = 'none';
                        notificationAlert('warning', 'Info', 'Gagal mengganti toko.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    if (loadingOverlay) loadingOverlay.style.display = 'none';
                    alert('Terjadi kesalahan koneksi.');
                    notificationAlert('warning', 'Info', 'Terjadi kesalahan koneksi.');

                });
        }

        function handleGlobalAddButtonState(activeTokoId) {
            const isAll = !activeTokoId || String(activeTokoId).toUpperCase() === 'ALL';

            // Mendukung selector .add-data DAN #btn-add-data
            $('.add-data, #btn-add-data').each(function() {
                const $btn = $(this);
                const $wrapper = $btn.parent();

                if (isAll) {
                    $btn.addClass('disabled')
                        .prop('disabled', true)
                        .css('cursor', 'not-allowed')
                        .attr('title', "Tidak dapat menambah data saat filter toko 'ALL'")
                        .attr('data-original-title', "Tidak dapat menambah data saat filter toko 'ALL'");

                    // 1. Simpan & Lepas Onclick Inline jika ada
                    if ($btn.attr('onclick')) {
                        $btn.attr('data-onclick-backup', $btn.attr('onclick')).removeAttr('onclick');
                    }

                    // 2. Simpan & Lepas Modal Bootstrap jika ada
                    if ($btn.attr('data-toggle')) {
                        $btn.attr('data-toggle-disabled', $btn.attr('data-toggle')).removeAttr('data-toggle');
                    }
                    if ($btn.attr('data-bs-toggle')) {
                        $btn.attr('data-bs-toggle-disabled', $btn.attr('data-bs-toggle')).removeAttr(
                            'data-bs-toggle');
                    }

                    $btn.css('pointer-events', 'auto');

                    if ($wrapper.find('.info-toko-warning').length === 0) {
                        $wrapper.append(`
                            <small class="text-danger d-block text-center info-toko-warning" style="font-size: 9px;">
                                Pilih toko untuk tambah
                            </small>
                        `);
                    }
                } else {
                    $btn.removeClass('disabled')
                        .prop('disabled', false)
                        .css({
                            'cursor': '',
                            'pointer-events': ''
                        })
                        .attr('title', "Tambah Data")
                        .attr('data-original-title', "Tambah Data");

                    // 1. Kembalikan Onclick Inline jika sebelumnya dilepas
                    if ($btn.attr('data-onclick-backup')) {
                        $btn.attr('onclick', $btn.attr('data-onclick-backup')).removeAttr('data-onclick-backup');
                    }

                    // 2. Kembalikan Modal Bootstrap
                    if ($btn.attr('data-toggle-disabled')) {
                        $btn.attr('data-toggle', $btn.attr('data-toggle-disabled')).removeAttr(
                            'data-toggle-disabled');
                    }
                    if ($btn.attr('data-bs-toggle-disabled')) {
                        $btn.attr('data-bs-toggle', $btn.attr('data-bs-toggle-disabled')).removeAttr(
                            'data-bs-toggle-disabled');
                    }

                    $wrapper.find('.info-toko-warning').remove();
                }

                if ($.fn.tooltip && $btn.data('bs.tooltip')) {
                    $btn.tooltip('dispose').tooltip();
                }
            });
        }

        // Global Interseptor Event Capture (Menahan klik sebelum fungsi JS lain dipanggil)
        document.addEventListener('click', function(e) {
            const target = e.target.closest('.add-data, #btn-add-data');
            if (target && target.classList.contains('disabled')) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                return false;
            }
        }, true); // Event capture mode (true)

        $(document).ready(function() {
            const activeToko = window.activeTokoId || 'ALL';
            handleGlobalAddButtonState(activeToko);
        });
    </script>
</body>

</html>
