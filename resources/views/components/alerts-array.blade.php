@php
    $alertTypes = ['error', 'success', 'warning', 'info'];
@endphp

@foreach ($alertTypes as $msg)
    @if (session()->has($msg))
        @php
            $message = session()->get($msg);
            $messages = is_array($message) ? $message : [$message]; // pastikan array
        @endphp

        <div class="alert alert-{{ $msg }} fade-target mb-3" role="alert">
            <div class="flex flex-col">
                <span class="font-semibold capitalize">{{ $msg }}:</span>
                <ul class="pl-4 list-disc mt-1">
                    @foreach ($messages as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif
@endforeach

@push('scripts')
    <script>
        window.setTimeout(() => {
            document.querySelectorAll('.fade-target').forEach((el) => {
                el.classList.add('fade-out');
                setTimeout(() => el.remove(), 500); // Hapus setelah animasi selesai
            });
        }, 5000); // 5 detik
    </script>
@endpush

@push('styles')
    <style>
        .fade-out {
            opacity: 0;
            transition: opacity 0.5s ease-out;
        }
    </style>
@endpush
