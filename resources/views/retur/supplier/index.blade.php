@extends('layouts.main')

@section('title')
    {{ $menu[0] }}
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('css/button-action.css') }}">
    <link rel="stylesheet" href="{{ asset('css/table.css') }}">
    <link rel="stylesheet" href="{{ asset('css/daterange-picker.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sweetalert2.css') }}">
    <link rel="stylesheet" href="{{ asset('css/glossy.css') }}">
    <style>
        #daterange[readonly] {
            background-color: white !important;
            cursor: pointer !important;
            color: inherit !important;
        }

        @media (max-width: 767.98px) {
            .filter-input {
                max-width: 100% !important;
            }
        }

        @media (min-width: 768px) {
            .filter-input {
                max-width: 300px;
            }
        }
    </style>
@endsection

@section('content')
    <div class="pcoded-main-container">
        <div class="pcoded-content pt-1 mt-1">
            @include('components.breadcrumbs')
            <div class="row">
                <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12 mb-2">
                    <div class="row" id="tambahData">
                        <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
                            <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
                                <div class="d-flex flex-row justify-content-between align-items-center p-3 flex-wrap">
                                    <h5 class="m-0"><i class="fa fa-undo mr-1"></i> Retur ke Suplier</h5>
                                    <div class="d-flex align-items-center" style="gap: 0.5rem;">
                                        <button
                                            class="btn-dynamic btn btn-md btn-outline-secondary d-flex align-items-center justify-content-center"
                                            type="button" data-toggle="collapse" data-target="#filter-collapse"
                                            aria-expanded="false" aria-controls="filter-collapse" data-container="body"
                                            data-toggle="tooltip" data-placement="top"
                                            style="flex: 0 0 45px; max-width: 45px;" title="Filter Data">
                                            <i class="fa fa-filter my-1"></i>
                                        </button>
                                        <button type="button"
                                            class="btn btn-md btn-outline-primary d-flex align-items-center justify-content-center"
                                            id="btn-add-data" onclick="openAddModal()" data-container="body"
                                            data-toggle="tooltip" data-placement="top"
                                            style="flex: 1 1 45px; max-width: 150px;"
                                            title="Tambah Data {{ $menu[0] }}">
                                            <i class="fa fa-circle-plus my-1"></i>
                                            <span class="d-none d-sm-inline ml-1">Tambah Data</span>
                                        </button>
                                    </div>
                                </div>
                                <hr class="m-0">
                                <div class="collapse" id="filter-collapse">
                                    <form id="custom-filter" class="p-3">
                                        <div class="d-flex flex-column flex-md-row justify-content-md-end align-items-md-center"
                                            style="gap: 0.5rem;">
                                            <div class="input-group w-100 w-md-auto filter-input">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">
                                                        <i class="fa fa-calendar"></i>
                                                    </span>
                                                </div>
                                                <input class="form-control" type="text" id="daterange" name="daterange"
                                                    placeholder="Pilih rentang tanggal">
                                            </div>
                                            <div class="d-flex justify-content-end" style="gap: 0.5rem;">
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

                                <div class="d-flex flex-row justify-content-between align-items-center p-3 flex-wrap"
                                    style="gap: 0.5rem;">
                                    <select name="limitPage" id="limitPage" class="form-control"
                                        style="flex: 1 1 80px; max-width: 80px;">
                                        <option value="30">30</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                        <option value="200">200</option>
                                        <option value="300">300</option>
                                    </select>
                                    <input class="tb-search form-control ms-auto" type="search" name="search"
                                        placeholder="Cari Data" aria-label="search"
                                        style="flex: 1 1 100px; max-width: 200px;">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
                            <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
                                <div class="row overflow-auto" id="listData" style="max-height: 50vh;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="row" id="paginateData">
                        <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
                            <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
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

    <div class="modal fade" id="modal-form" tabindex="-1" role="dialog" aria-labelledby="modalLabel"
        aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalLabel">Form Data</h5>
                    <button type="button" class="btn-close reset-all close" data-bs-dismiss="modal"
                        aria-label="Close"><i class="fa fa-xmark"></i></button>
                </div>
                <div class="modal-body card-body" id="modal-data">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close"><i
                            class="fa fa-times mr-1"></i>Tutup</button>
                    <button type="submit" class="btn btn-success" id="save-btn" form="form-data">Simpan</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('asset_js')
    <script src="{{ asset('js/pagination-multi.js') }}"></script>
    <script src="{{ asset('js/moment.js') }}"></script>
    <script src="{{ asset('js/daterange-picker.js') }}"></script>
    <script src="{{ asset('js/daterange-custom.js') }}"></script>
@endsection

@section('js')
    <script>
        let title = '{{ $menu[0] }}';
        let defaultLimitPage = 30;
        let currentPage = 1;
        let totalPage = 1;
        let defaultAscending = 0;
        let defaultSearch = '';
        let customFilter = {};

        let defaultLimitPage2 = 10;
        let currentPage2 = 1;
        let totalPage2 = 1;
        let defaultAscending2 = 0;
        let defaultSearch2 = '';
        let customFilter2 = {};
    </script>

    @include('retur.supplier.item')
    @include('retur.supplier.itemDetail')

    <script>
        const fetchConfigMap = {
            getListData: {
                fn: getListData,
                getConfig: () => ({
                    limit: defaultLimitPage,
                    page: currentPage,
                    asc: defaultAscending,
                    search: defaultSearch,
                    filter: customFilter
                }),
                setPage: (val) => currentPage = val,
                getTotalPage: () => totalPage,
                setSearch: (val) => defaultSearch = val,
                setLimit: (val) => {
                    defaultLimitPage = val;
                }
            },
            getListData2: {
                fn: getListData2,
                getConfig: () => ({
                    limit: defaultLimitPage2,
                    page: currentPage2,
                    asc: defaultAscending2,
                    search: defaultSearch2,
                    filter: customFilter2
                }),
                setPage: (val) => currentPage2 = val,
                getTotalPage: () => totalPage2,
                setSearch: (val) => defaultSearch2 = val,
                setLimit: (val) => {
                    defaultLimitPage2 = val;
                }
            }
        };

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
                setDynamicButton('btn-secondary', 'btn-outline-secondary'),
                getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch, customFilter),
                searchList(),
                filterList()
            ])
        }
    </script>
@endsection
