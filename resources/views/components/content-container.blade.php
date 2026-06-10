@props([
    'tableHeaders' => [],
    'breadcrumbs' => [] // Tambahkan props baru di sini
])

<div class="pcoded-main-container">
    <div class="pcoded-content pt-1 mt-1">

        {{-- Oper data baredcrumbs ke dalam file blade include --}}
        @include('components.breadcrumbs', ['menu' => $breadcrumbs])

        <div class="row">
            <div class="col-xl-12">
                <div class="card">

                    {{-- Slot untuk Header Component --}}
                    {{ $header ?? '' }}

                    <div class="content">
                        <x-adminlte-alerts />
                        <div class="card-body p-0">
                            <div class="table-responsive table-scroll-wrapper">
                                <table class="table table-striped m-0">
                                    <thead>
                                        <tr class="tb-head">
                                            @foreach ($tableHeaders as $headerItem)
                                                <th class="{{ $headerItem['class'] ?? 'text-wrap align-top' }}">
                                                    {!! $headerItem['text'] !!}
                                                </th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody id="listData">
                                        {{-- Diisi via AJAX --}}
                                    </tbody>
                                </table>
                            </div>

                            {{-- Footer Pagination --}}
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center p-3">
                                <div class="text-center text-md-start mb-2 mb-md-0">
                                    <div class="pagination">
                                        <div>Menampilkan <span id="countPage">0</span> dari <span id="totalPage">0</span> data</div>
                                    </div>
                                </div>
                                <nav class="text-center text-md-end">
                                    <ul class="pagination justify-content-center justify-content-md-end" id="pagination-js">
                                    </ul>
                                </nav>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<x-content-modal :title="$breadcrumbs[0] ?? 'Data'" />
