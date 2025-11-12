@extends('layouts.main')

@section('title')
    {{ $menu[0] }}
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('css/button-action.css') }}">
    <link rel="stylesheet" href="{{ asset('css/table.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sweetalert2.css') }}">
    <link rel="stylesheet" href="{{ asset('css/glossy.css') }}">
@endsection

@section('content')
    <div class="pcoded-main-container">
        <div class="pcoded-content pt-1 mt-1">
            @include('components.breadcrumbs')
            <div class="row">
                <div class="col-md-5 mb-2">
                    <div class="row" style="gap: 0.5rem;">
                        <div class="col-md-12">
                            <div class="row" id="tambahData">
                                <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
                                    <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
                                        <div
                                            class="d-flex flex-row justify-content-between align-items-center p-3 flex-wrap">
                                            <h5 class="m-0">Riwayat Top Up</h5>
                                            <div class="d-flex align-items-center" style="gap: 0.5rem;">
                                                <button type="button"
                                                    class="btn btn-md btn-outline-secondary d-flex align-items-center justify-content-center"
                                                    onclick="openFilterModal()" data-container="body" data-toggle="tooltip"
                                                    data-placement="top" style="flex: 0 0 45px; max-width: 45px;"
                                                    title="Filter Data">
                                                    <i class="fa fa-filter my-1"></i>
                                                </button>
                                                <button type="button"
                                                    class="btn btn-md btn-outline-primary d-flex align-items-center justify-content-center"
                                                    onclick="openAddModal()" data-container="body" data-toggle="tooltip"
                                                    data-placement="top" style="flex: 1 1 45px; max-width: 150px;"
                                                    title="Tambah Data Saldo Digital">
                                                    <i class="fa fa-circle-plus my-1"></i>
                                                    <span class="d-none d-sm-inline ml-1">Tambah Data</span>
                                                </button>
                                            </div>
                                        </div>
                                        <hr class="m-0">
                                        <div class="d-flex flex-row justify-content-between align-items-center p-3 flex-wrap"
                                            style="gap: 0.5rem;">
                                            <select name="limitPage" id="limitPage" class="form-control"
                                                style="flex: 1 1 80px; max-width: 80px;">
                                                <option value="10">10</option>
                                                <option value="20">20</option>
                                                <option value="30">30</option>
                                            </select>
                                            <input class="tb-search form-control ms-auto" type="search" name="search"
                                                placeholder="Cari Data" aria-label="search"
                                                style="flex: 1 1 200px; max-width: 200px;">
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
                                        <div
                                            class="d-flex flex-column flex-md-row justify-content-between align-items-center p-3">
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
                <div class="col-md-7 mb-2">
                    <div class="row">
                        <div class="col-md-12 mb-1">
                            <div class="row" id="tambahData3">
                                <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
                                    <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
                                        <div
                                            class="d-flex flex-row justify-content-between align-items-center p-3 flex-wrap">
                                            <h5 class="m-0">Item Non Fisik</h5>
                                            <div class="d-flex align-items-center" style="gap: 0.5rem;">
                                                <button type="button"
                                                    class="btn btn-md btn-outline-secondary d-flex align-items-center justify-content-center"
                                                    onclick="openFilterModal3()" data-container="body" data-toggle="tooltip"
                                                    data-placement="top" style="flex: 0 0 45px; max-width: 45px;"
                                                    title="Filter Data">
                                                    <i class="fa fa-filter my-1"></i>
                                                </button>
                                                <button type="button"
                                                    class="btn btn-md btn-outline-primary d-flex align-items-center justify-content-center"
                                                    onclick="openAddModal3()" data-container="body" data-toggle="tooltip"
                                                    data-placement="top" style="flex: 1 1 45px; max-width: 150px;"
                                                    title="Tambah Data Item Non Fisik">
                                                    <i class="fa fa-circle-plus my-1"></i>
                                                    <span class="d-none d-sm-inline ml-1">Tambah Data</span>
                                                </button>
                                            </div>
                                        </div>
                                        <hr class="m-0">
                                        <div class="d-flex flex-row justify-content-between align-items-center p-3 flex-wrap"
                                            style="gap: 0.5rem;">
                                            <select name="limitPage" id="limitPage3" class="form-control"
                                                style="flex: 1 1 80px; max-width: 80px;">
                                                <option value="10">10</option>
                                                <option value="20">20</option>
                                                <option value="30">30</option>
                                            </select>
                                            <input class="tb-search3 form-control ms-auto" type="search" name="search"
                                                placeholder="Cari Data" aria-label="search"
                                                style="flex: 1 1 200px; max-width: 200px;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row" id="listData3"></div>
                            <div class="row" id="paginateData3">
                                <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
                                    <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
                                        <div
                                            class="d-flex flex-column flex-md-row justify-content-between align-items-center p-3">
                                            <div class="text-center text-md-start mb-2 mb-md-0">
                                                <div class="pagination">
                                                    <div>Menampilkan <span id="countPage3">0</span> dari <span
                                                            id="totalPage3">0</span> data</div>
                                                </div>
                                            </div>
                                            <nav class="text-center text-md-end">
                                                <ul class="pagination justify-content-center justify-content-md-end"
                                                    id="pagination-js3">
                                                </ul>
                                            </nav>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-1">
                            <div class="row" id="tambahData2">
                                <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
                                    <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
                                        <div
                                            class="d-flex flex-row justify-content-between align-items-center p-3 flex-wrap">
                                            <h5 class="m-0">Kategori Saldo</h5>
                                            <div class="d-flex align-items-center" style="gap: 0.5rem;">
                                                <button type="button"
                                                    class="btn btn-md btn-outline-secondary d-flex align-items-center justify-content-center"
                                                    onclick="openFilterModal2()" data-container="body"
                                                    data-toggle="tooltip" data-placement="top"
                                                    style="flex: 0 0 45px; max-width: 45px;" title="Filter Data">
                                                    <i class="fa fa-filter my-1"></i>
                                                </button>
                                                <button type="button"
                                                    class="btn btn-md btn-outline-primary d-flex align-items-center justify-content-center"
                                                    onclick="openAddModal2()" data-container="body" data-toggle="tooltip"
                                                    data-placement="top" style="flex: 1 1 45px; max-width: 150px;"
                                                    title="Tambah Data Kategori Saldo">
                                                    <i class="fa fa-circle-plus my-1"></i>
                                                    <span class="d-none d-sm-inline ml-1">Tambah Data</span>
                                                </button>
                                            </div>
                                        </div>
                                        <hr class="m-0">
                                        <div class="d-flex flex-row justify-content-between align-items-center p-3 flex-wrap"
                                            style="gap: 0.5rem;">
                                            <select name="limitPage" id="limitPage2" class="form-control"
                                                style="flex: 1 1 80px; max-width: 80px;">
                                                <option value="10">10</option>
                                                <option value="20">20</option>
                                                <option value="30">30</option>
                                            </select>
                                            <input class="tb-search2 form-control ms-auto" type="search" name="search"
                                                placeholder="Cari Data" aria-label="search"
                                                style="flex: 1 1 200px; max-width: 200px;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row" id="listData2"></div>
                            <div class="row" id="paginateData2">
                                <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
                                    <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
                                        <div
                                            class="d-flex flex-column flex-md-row justify-content-between align-items-center p-3">
                                            <div class="text-center text-md-start mb-2 mb-md-0">
                                                <div class="pagination">
                                                    <div>Menampilkan <span id="countPage2">0</span> dari <span
                                                            id="totalPage2">0</span> data</div>
                                                </div>
                                            </div>
                                            <nav class="text-center text-md-end">
                                                <ul class="pagination justify-content-center justify-content-md-end"
                                                    id="pagination-js2">
                                                </ul>
                                            </nav>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-1">
                            <div class="row" id="tambahData4">
                                <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
                                    <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
                                        <div
                                            class="d-flex flex-row justify-content-between align-items-center p-3 flex-wrap">
                                            <h5 class="m-0">Tipe Item</h5>
                                            <div class="d-flex align-items-center" style="gap: 0.5rem;">
                                                <button type="button"
                                                    class="btn btn-md btn-outline-secondary d-flex align-items-center justify-content-center"
                                                    onclick="openFilterModal4()" data-container="body"
                                                    data-toggle="tooltip" data-placement="top"
                                                    style="flex: 0 0 45px; max-width: 45px;" title="Filter Data">
                                                    <i class="fa fa-filter my-1"></i>
                                                </button>
                                                <button type="button"
                                                    class="btn btn-md btn-outline-primary d-flex align-items-center justify-content-center"
                                                    onclick="openAddModal4()" data-container="body" data-toggle="tooltip"
                                                    data-placement="top" style="flex: 1 1 45px; max-width: 150px;"
                                                    title="Tambah Data Tipe Item">
                                                    <i class="fa fa-circle-plus my-1"></i>
                                                    <span class="d-none d-sm-inline ml-1">Tambah Data</span>
                                                </button>
                                            </div>
                                        </div>
                                        <hr class="m-0">
                                        <div class="d-flex flex-row justify-content-between align-items-center p-3 flex-wrap"
                                            style="gap: 0.5rem;">
                                            <select name="limitPage" id="limitPage4" class="form-control"
                                                style="flex: 1 1 80px; max-width: 80px;">
                                                <option value="10">10</option>
                                                <option value="20">20</option>
                                                <option value="30">30</option>
                                            </select>
                                            <input class="tb-search4 form-control ms-auto" type="search" name="search"
                                                placeholder="Cari Data" aria-label="search"
                                                style="flex: 1 1 200px; max-width: 200px;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row" id="listData4"></div>
                            <div class="row" id="paginateData4">
                                <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
                                    <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
                                        <div
                                            class="d-flex flex-column flex-md-row justify-content-between align-items-center p-3">
                                            <div class="text-center text-md-start mb-2 mb-md-0">
                                                <div class="pagination">
                                                    <div>Menampilkan <span id="countPage4">0</span> dari <span
                                                            id="totalPage4">0</span> data</div>
                                                </div>
                                            </div>
                                            <nav class="text-center text-md-end">
                                                <ul class="pagination justify-content-center justify-content-md-end"
                                                    id="pagination-js4">
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
@endsection

@section('js')
    <script>
        let title = 'Saldo Digital';
        let defaultLimitPage = 10;
        let currentPage = 1;
        let totalPage = 1;
        let defaultAscending = 0;
        let defaultSearch = '';
        let customFilter = {};

        let title2 = 'Kategori Saldo';
        let defaultLimitPage2 = 10;
        let currentPage2 = 1;
        let totalPage2 = 1;
        let defaultAscending2 = 0;
        let defaultSearch2 = '';
        let customFilter2 = {};

        let title3 = 'Item Non Fisik';
        let defaultLimitPage3 = 10;
        let currentPage3 = 1;
        let totalPage3 = 1;
        let defaultAscending3 = 0;
        let defaultSearch3 = '';
        let customFilter3 = {};

        let title4 = 'Tipe Item';
        let defaultLimitPage4 = 10;
        let currentPage4 = 1;
        let totalPage4 = 1;
        let defaultAscending4 = 0;
        let defaultSearch4 = '';
        let customFilter4 = {};

        let selectOptions = [{
            id: '#dompet_kategori',
            isUrl: '{{ route('td.dompetKategori.getNama') }}',
            placeholder: `Pilih ${title2}`,
            isModal: '#modal-form',
        }, {
            id: '#tipe_item',
            isUrl: '{{ route('td.itemNonfisikTipe.getNama') }}',
            placeholder: `Pilih ${title4}`,
            isModal: '#modal-form',
        }, {
            id: '#kas',
            isUrl: '{{ route('total.kas') }}',
            placeholder: 'Pilih Kas',
            isModal: '#modal-form',
            isFilter: {
                id_toko: '{{ auth()->user()->id_toko }}'
            },
            extraFields: 'jenis_id',
        }];
    </script>

    @include('transaksiDigital.dompet.saldo')
    @include('transaksiDigital.dompet.kategori')
    @include('transaksiDigital.dompet.itemNonfisik')
    @include('transaksiDigital.dompet.itemNonfisikTipe')

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
            },
            getListData3: {
                fn: getListData3,
                getConfig: () => ({
                    limit: defaultLimitPage3,
                    page: currentPage3,
                    asc: defaultAscending3,
                    search: defaultSearch3,
                    filter: customFilter3
                }),
                setPage: (val) => currentPage3 = val,
                getTotalPage: () => totalPage3,
                setSearch: (val) => defaultSearch3 = val,
                setLimit: (val) => {
                    defaultLimitPage3 = val;
                }
            },
            getListData4: {
                fn: getListData4,
                getConfig: () => ({
                    limit: defaultLimitPage4,
                    page: currentPage4,
                    asc: defaultAscending4,
                    search: defaultSearch4,
                    filter: customFilter4
                }),
                setPage: (val) => currentPage4 = val,
                getTotalPage: () => totalPage4,
                setSearch: (val) => defaultSearch4 = val,
                setLimit: (val) => {
                    defaultLimitPage4 = val;
                }
            }
        };

        async function initPageLoad() {
            await Promise.all([
                getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch, customFilter),
                searchList(),
                getListData2(defaultLimitPage2, currentPage2, defaultAscending2, defaultSearch2, customFilter2),
                searchList('getListData2', '#limitPage2', '.tb-search2'),
                saveData2(),
                getListData3(defaultLimitPage3, currentPage3, defaultAscending3, defaultSearch3, customFilter3),
                searchList('getListData3', '#limitPage3', '.tb-search3'),
                saveData3(),
                getListData4(defaultLimitPage4, currentPage4, defaultAscending4, defaultSearch4, customFilter4),
                searchList('getListData4', '#limitPage4', '.tb-search4'),
                saveData4(),
            ])
        }
    </script>
@endsection
