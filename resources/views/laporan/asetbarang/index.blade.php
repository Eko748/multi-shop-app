@extends('layouts.main')

@section('title')
    Rekapitulasi Asset Barang Jualan
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('css/button-action.css') }}">
    <link rel="stylesheet" href="{{ asset('css/table.css') }}">
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
                                <div class="col-12 col-md-6 mb-2">
                                    <span id="time-report" class="font-weight-bold"></span>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="row justify-content-end">
                                        <div class="col-4 col-md-2">
                                            <select name="limitPage" id="limitPage" class="form-control mr-2 mb-2 mb-lg-0">
                                                <option value="10">10</option>
                                                <option value="20">20</option>
                                                <option value="30">30</option>
                                            </select>
                                        </div>
                                        <div class="col-8 col-md-4">
                                            <input id="tb-search" class="tb-search form-control mb-2 mb-lg-0" type="search"
                                                name="search" placeholder="Cari Data" aria-label="search">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="content">
                            <div class="collapse mt-2 pl-4" id="filter-collapse">
                                <form id="custom-filter" class="d-flex justify-content-start align-items-center">
                                    <input class="form-control w-25 mb-2" type="text" id="daterange" name="daterange"
                                        placeholder="Pilih rentang tanggal">
                                    <button class="btn btn-info mr-2 h-100 mb-2 mx-2" id="tb-filter" type="submit">
                                        <i class="fa fa-magnifying-glass mr-2"></i>Cari
                                    </button>
                                    <button type="button" class="btn btn-secondary mr-2 h-100 mb-2" id="tb-reset">
                                        <i class="fa fa-rotate mr-2"></i>Reset
                                    </button>
                                </form>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive table-scroll-wrapper">
                                    <table class="table table-striped m-0">
                                        <thead>
                                            <tr class="tb-head">
                                                <th class="text-center text-wrap align-top">No</th>
                                                <th class="text-wrap align-top">Area/Nama Toko</th>
                                                <th class="text-wrap align-top text-right">Jumlah Item</th>
                                                <th class="text-wrap align-top text-right"><span class="mr-3">Total
                                                        Nilai</span></th>
                                                <th class="text-wrap align-top text-right"><span class="mr-3">Keterangan</span></th>
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
    <script src="{{ asset('js/pagination.js') }}"></script>
@endsection

@section('js')
    <script>
        let title = 'Aset Barang Jualan';
        let defaultLimitPage = 10;
        let currentPage = 1;
        let totalPage = 1;
        let defaultAscending = 0;
        let defaultSearch = '';
        let customFilter = {};

        async function getListData(limit = 10, page = 1, ascending = 0, search = '', customFilter = {}) {
            $('#listData').html(loadingData());

            let filterParams = {};

            let getDataRest = await renderAPI(
                'GET',
                '{{ route('dashboard.asset') }}', {
                    page: page,
                    limit: limit,
                    ascending: ascending,
                    search: search,
                    toko_id: '{{ auth()->user()->toko_id }}',
                    ...filterParams
                }
            ).then(function(response) {
                return response;
            }).catch(function(error) {
                let resp = error.response;
                return resp;
            });

            if (getDataRest && getDataRest.status == 200 && Array.isArray(getDataRest.data.data) && getDataRest.data
                .pagination.total != 0) {
                let handleDataArray = await Promise.all(
                    getDataRest.data.data.map(async item => await handleData(item))
                );
                await setListData(handleDataArray, getDataRest.data.pagination, getDataRest.data.total_summary);
            } else {
                errorMessage = getDataRest?.data?.message;
                let errorRow = `
                            <tr class="text-dark">
                                <th class="text-center" colspan="${$('.tb-head th').length}"> Tidak ada data </th>
                            </tr>`;
                $('#listData').html(errorRow);
                $('#countPage').text("0 - 0");
                $('#totalPage').text("0");
            }
        }

        async function handleData(data) {
            return {
                nama_jenis_barang: data?.nama_jenis_barang ?? '-',
                items: data?.items.map(item => ({
                    id: item?.toko_id ?? '-',
                    nama_toko: item?.nama_toko ?? '-',
                    total_qty: item?.total_qty ?? 0,
                    total_harga: item?.total_harga ?? 0,
                    keterangan: item?.keterangan ?? '-',
                })) ?? []
            };
        }

        async function setListData(dataList, pagination, totalSummary = {
            total_qty: 0,
            total_harga: 0
        }) {
            totalPage = pagination.total_pages;
            currentPage = pagination.current_page;

            let display_from = ((defaultLimitPage * (currentPage - 1)) + 1);
            let display_to = display_from;
            let classCol = 'align-center text-dark text-wrap';
            let getDataTable = '';
            let getFooterTable = '';

            let totalCount = 0;

            dataList.forEach(group => {
                const groupLabel = `
                <tr class="bg-light text-dark font-weight-bold">
                    <td colspan="5" class="text-left p-2">${group.nama_jenis_barang}</td>
                </tr>`;
                getDataTable += groupLabel;

                group.items.forEach((item, i) => {
                    if (item.id === "ALL") {
                        getFooterTable = `
                        <tr class="bg-primary font-weight-bold">
                            <td class="${classCol} text-center" style="width: 5%"><i class="fa fa-hashtag text-white"></i></td>
                            <td class="${classCol}" style="width: 35%"><span>${item.nama_toko}</span></td>
                            <td class="${classCol} text-right" style="width: 10%"><span>${item.total_qty}</span></td>
                            <td class="${classCol} text-right" style="width: 20%"><span class="mr-3">${item.total_harga}</span></td>
                            <td class="${classCol} text-right" style="width: 30%"><span class="mr-3">${item.keterangan}</span></td>
                        </tr>`;
                    } else {
                        getDataTable += `
                        <tr class="text-dark">
                            <td class="${classCol} text-center" style="width: 5%">${display_to++}.</td>
                            <td class="${classCol}" style="width: 35%">${item.nama_toko}</td>
                            <td class="${classCol} text-right" style="width: 10%">${item.total_qty}</td>
                            <td class="${classCol} text-right" style="width: 20%"><span class="mr-3">${item.total_harga}</span></td>
                            <td class="${classCol} text-right" style="width: 30%"><span class="mr-3">${item.keterangan}</span></td>
                        </tr>`;
                        totalCount++;
                    }
                });
            });
            const globalSummary = `
            <tr class="bg-light font-weight-bold border-top border-dark">
                <td colspan="2" class="${classCol}">Total Keseluruhan:</td>
                <td class="${classCol} text-right">${totalSummary.total_qty}</td>
                <td class="${classCol} text-right"><span class="mr-3">${totalSummary.total_harga}</span></td>
                <td></td>
            </tr>`;

            getDataTable += globalSummary;

            $('#listData').html(getDataTable);
            $('#totalPage').text(totalCount);
            $('#countPage').text(`${display_from} - ${display_to - 1}`);

            if (getFooterTable) {
                if (!$('#listData').closest('table').find('tfoot').length) {
                    $('#listData').closest('table').append('<tfoot></tfoot>');
                }
                $('#listData').closest('table').find('tfoot').html(getFooterTable);
            }

            $('[data-toggle="tooltip"]').tooltip();
            renderPagination();
        }

        async function initPageLoad() {
            const now = new Date();
            const formattedNow =
                `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')} ${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}:${String(now.getSeconds()).padStart(2, '0')}`;

            $('#time-report').html(
                `<i class="fa fa-file-text mr-1"></i><b>${title}</b> (<b class="text-primary">${formattedNow}</b>)`);
            await getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch, customFilter);
            await searchList();
        }
    </script>
@endsection
