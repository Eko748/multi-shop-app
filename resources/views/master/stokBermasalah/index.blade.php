@extends('layouts.main')

@section('title')
    {{ $menu[0] }}
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('css/button-action.css') }}">
    <link rel="stylesheet" href="{{ asset('css/table.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sweetalert2.css') }}">
    <link rel="stylesheet" href="{{ asset('css/daterange-picker.css') }}">
    <style>
        @media (max-width: 768px) {
            .modal-dialog {
                max-width: 100%;
                margin: 0;
            }
        }

        @media (min-width: 769px) {
            .modal-dialog {
                max-width: 90%;
            }
        }

        .scroll-section {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 0.5rem;
            background-color: #f8f9fa;
        }

        .scroll-section table {
            margin-bottom: 0;
        }

        .section-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .divider-col {
            border-right: 1px solid #dee2e6;
        }

        #daterange[readonly] {
            background-color: white !important;
            cursor: pointer !important;
            color: inherit !important;
        }
    </style>
@endsection

@section('content')
    <div class="pcoded-main-container">
        <div class="pcoded-content pt-1 mt-1">
            @include('components.breadcrumbs')
            <div class="row">
                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-sm-12 col-md-3 col-lg-3 col-xl-2 mb-2">
                                    <div class="d-flex align-items-center mb-2" style="gap: 0.5rem">
                                        <!-- Card Total Data di Sebelah Kiri -->
                                        <div class="card border-secondary shadow-none px-3 py-1 d-flex flex-row align-items-center justify-content-center"
                                            style="height: 38px; background-color: #f8f9fa;">
                                            <span class="text-muted small mr-2">Total Qty: <strong id="summary-total-qty"
                                                    class="text-dark">0</strong></span>
                                            <span class="text-muted small">| Total: <strong id="summary-total-harga"
                                                    class="text-dark">Rp 0</strong></span>
                                        </div>

                                        <!-- Button Filter di Sebelah Kanan -->
                                        <div class="col-sm-12 col-md-3 col-lg-3 col-xl-2 mb-0 p-0">
                                            <button
                                                class="btn-dynamic btn btn-md btn-outline-secondary d-flex align-items-center justify-content-center w-100"
                                                type="button" data-toggle="collapse" data-target="#filter-collapse"
                                                aria-expanded="false" aria-controls="filter-collapse" data-container="body"
                                                data-toggle="tooltip" data-placement="top" style="height: 38px;"
                                                title="Filter Data">
                                                <i class="fa fa-filter my-1"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-12 col-md-9 col-lg-9 col-xl-10 mb-2">
                                    <div class="row justify-content-end">
                                        <div class="col-4 col-sm-4 col-md-2 col-lg-2">
                                            <select name="limitPage" id="limitPage" class="form-control mr-2 mb-2 mb-lg-0">
                                                <option value="10">10</option>
                                                <option value="20">20</option>
                                                <option value="30">30</option>
                                            </select>
                                        </div>
                                        <div class="col-8 col-sm-8 col-md-4 col-lg-4 justify-content-end">
                                            <input id="tb-search" class="tb-search form-control mb-2 mb-lg-0" type="search"
                                                name="search" placeholder="Cari Data" aria-label="search">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="collapse" id="filter-collapse">
                            <form id="custom-filter" class="p-3">
                                <div class="d-flex flex-column flex-md-row justify-content-md-end align-items-md-center"
                                    style="gap: 1rem;">
                                    <div class="input-group w-25 w-md-auto filter-input">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fa fa-calendar"></i>
                                            </span>
                                        </div>
                                        <input class="form-control" type="text" id="daterange" name="daterange"
                                            placeholder="Pilih rentang tanggal">
                                    </div>
                                    <div class="d-flex justify-content-end" style="gap: 1rem;">
                                        <button class="btn btn-info" id="tb-filter" type="submit">
                                            <i class="fa fa-magnifying-glass mr-1"></i>Cari
                                        </button>
                                        <button type="button" class="btn btn-secondary" id="tb-reset">
                                            <i class="fa fa-rotate mr-1"></i>Reset
                                        </button>
                                    </div>
                                </div>
                            </form>
                            <hr class="m-0">
                        </div>
                        <div class="content">
                            <div class="card-body p-0">
                                <div class="table-responsive table-scroll-wrapper">
                                    <table class="table table-striped m-0">
                                        <thead>
                                            <tr class="tb-head">
                                                <th class="text-center text-wrap align-top">No</th>
                                                <th class="text-wrap align-top">Nama Barang</th>
                                                <th class="text-wrap align-top">Status</th>
                                                <th class="text-wrap align-top">Qty</th>
                                                <th class="text-wrap align-top">Harga Beli</th>
                                                <th class="text-wrap align-top">Total</th>
                                                <th class="text-wrap align-top">Batch</th>
                                                <th class="text-wrap align-top">Data Dibuat</th>
                                            </tr>
                                        </thead>
                                        <tbody id="listData">
                                        </tbody>
                                    </table>
                                </div>
                                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center p-3">
                                    <div class="text-center text-md-start mb-2 mb-md-0">
                                        <div class="pagination">
                                            <div>Menampilkan <span id="countPage">0</span> dari <span
                                                    id="totalPage">0</span> data</div>
                                        </div>
                                    </div>
                                    <nav class="text-center text-md-end">
                                        <ul class="pagination justify-content-center justify-content-md-end"
                                            id="pagination-js">
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
@endsection

@section('asset_js')
    <script src="{{ asset('js/moment.js') }}"></script>
    <script src="{{ asset('js/daterange-picker.js') }}"></script>
    <script src="{{ asset('js/daterange-custom.js') }}"></script>
    <script src="{{ asset('js/pagination.js') }}"></script>
@endsection

@section('js')
    <script>
        let title = '{{ $menu[0] }}';
        let defaultLimitPage = 10;
        let currentPage = 1;
        let totalPage = 1;
        let defaultAscending = 0;
        let defaultSearch = '';
        let customFilter = {};

        async function getListData(limit = 10, page = 1, ascending = 0, search = '', customFilter = {}) {
            $('#listData').html(loadingData());

            let filterParams = {
                ...customFilter
            };
            let getDataRest = await renderAPI(
                'GET',
                '{{ route('sb.bermasalah.get') }}', {
                    page: page,
                    limit: limit,
                    ascending: ascending,
                    search: search,
                    toko_id: {{ auth()->user()->toko_id }},
                    ...filterParams
                }
            ).then(function(response) {
                return response;
            }).catch(function(error) {
                let resp = error.response;
                return resp;
            });

            if (getDataRest && getDataRest.status == 200 && Array.isArray(getDataRest.data.data)) {
                let handleDataArray = await Promise.all(
                    getDataRest.data.data.map(async item => await handleData(item))
                );
                await setListData(handleDataArray, getDataRest.data.pagination);
            } else {
                errorMessage = getDataRest?.data?.message;
                let errorRow = `
                            <tr class="text-dark">
                                <th class="text-center" colspan="${$('.tb-head th').length}"> ${errorMessage} </th>
                            </tr>`;
                $('#listData').html(errorRow);
                $('#countPage').text("0 - 0");
                $('#totalPage').text("0");
            }
        }

        async function handleData(data) {
            return {
                id: data?.id ?? '-',
                nama_barang: data?.nama_barang ?? '-',
                total: data?.total ?? '-',
                harga_beli: data?.harga_beli ?? '-',
                qty: data?.qty ?? 0,
                status: data?.status ?? '-',
                tanggal_masuk: data?.tanggal_masuk ?? '-',
                created_at: data?.created_at ?? '-',
            };
        }

        async function setListData(dataList, pagination) {
            totalPage = pagination.total_pages;
            currentPage = pagination.current_page;

            let display_from = ((defaultLimitPage * (currentPage - 1)) + 1);
            let display_to = Math.min(display_from + dataList.length - 1, pagination.total);

            let getDataTable = '';
            let classCol = 'align-middle text-dark text-wrap';

            dataList.forEach((element, index) => {
                const warningClass = element.warning ? 'table-danger' : '';

                const isHilang = element.status === 'hilang';
                const badgeClass = isHilang ? 'bg-danger text-white' : 'bg-warning text-dark';

                getDataTable += `
                <tr class="text-dark ${warningClass}">
                    <td class="${classCol} text-center">${display_from + index}.</td>
                    <td class="${classCol}">${element.nama_barang}</td>
                    <td class="${classCol}">
                        <span class="badge ${badgeClass} text-uppercase px-2 py-1">
                            ${element.status}
                        </span>
                    </td>
                    <td class="${classCol}">${element.qty}</td>
                    <td class="${classCol}">${element.harga_beli}</td>
                    <td class="${classCol}">${element.total}</td>
                    <td class="${classCol}">${element.tanggal_masuk}</td>
                    <td class="${classCol}">${element.created_at}</td>
                </tr>`;
            });

            $('#listData').html(getDataTable);
            $('#totalPage').text(pagination.total);
            $('#countPage').text(`${display_from} - ${display_to}`);
            $('[data-toggle="tooltip"]').tooltip();

            renderPagination();
        }

        async function filterList() {
            let dateRangePickerList = initializeDateRangePicker();

            document.getElementById('custom-filter').addEventListener('submit', async function(e) {
                e.preventDefault();
                let startDate = dateRangePickerList.data('daterangepicker').startDate;
                let endDate = dateRangePickerList.data('daterangepicker').endDate;

                if (!startDate || !endDate) {
                    startDate = null;
                    endDate = null;
                } else {
                    startDate = startDate.startOf('day').format('YYYY-MM-DD HH:mm:ss');
                    endDate = endDate.endOf('day').format('YYYY-MM-DD HH:mm:ss');
                }

                customFilter = {
                    'start_date': $("#daterange").val() != '' ? startDate : '',
                    'end_date': $("#daterange").val() != '' ? endDate : ''
                };

                defaultSearch = $('.tb-search').val();
                defaultLimitPage = $("#limitPage").val();
                currentPage = 1;

                await getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch,
                    customFilter);
            });

            document.getElementById('tb-reset').addEventListener('click', async function() {
                $('#daterange').val('');
                customFilter = {};
                defaultSearch = $('.tb-search').val();
                defaultLimitPage = $("#limitPage").val();
                currentPage = 1;
                await getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch,
                    customFilter);
            });
        }

        async function initPageLoad() {
            await Promise.all([
                getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch, customFilter),
                searchList(),
                filterList(),
            ])
        }
    </script>
@endsection
