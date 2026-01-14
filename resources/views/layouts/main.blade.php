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
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('images/logo/logo.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/fontawesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/flatpickr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    @include('layouts.css.style_css')
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
    <script src="{{ asset('js/main.js') }}"></script>
    @yield('js')
    @stack('scripts')
    <script>
        const allowedPermissions = @json(View::getShared()['allowedPermissions'] ?? []);
    </script>
</body>

</html>
