@extends('layouts.main')

@section('title')
    {{ $menu[0] }}
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('css/button-action.css') }}">
    <link rel="stylesheet" href="{{ asset('css/table.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sweetalert2.css') }}">
    <style>
        .modal-content {
            font-size: 0.9rem;
        }

        .table th {
            background-color: #f8f9fa;
        }

        /* Mencegah teks overflow ke samping dan memaksanya turun ke bawah */
        #logDetailContent th,
        #logDetailContent td,
        #logChangeTable td,
        #logChangeTable th {
            word-break: break-word;
            white-space: normal;
            vertical-align: top;
        }

        /* Table responsive */
        .table-responsive {
            overflow-x: auto;
        }

        /* Optional: agar cell tidak terlalu sempit */
        #logDetailContent td,
        #logDetailContent th {
            min-width: 120px;
        }

        #logChangeTable td {
            vertical-align: top;
            word-break: break-word;
        }

        .toggle-content {
            max-height: 100px;
            overflow: hidden;
            position: relative;
        }

        .toggle-content.expanded {
            max-height: none;
        }

        .toggle-content::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 20px;
            background: linear-gradient(to top, white, transparent);
            display: block;
        }

        .toggle-btn {
            cursor: pointer;
            color: #0d6efd;
            font-size: 0.9rem;
            display: inline-block;
            margin-top: 0.25rem;
        }

        .badge-method {
            font-size: 10px;
            padding: 2px 5px;
            border-radius: 4px;
            background-color: rgba(0, 0, 0, 0.05);
            color: #000;
        }

        .method-get {
            background-color: rgba(40, 167, 69, 0.15);
            color: #28a745;
        }

        .method-post {
            background-color: rgba(0, 123, 255, 0.15);
            color: #007bff;
        }

        .method-put {
            background-color: rgba(255, 193, 7, 0.15);
            color: #ffc107;
        }

        .method-delete {
            background-color: rgba(220, 53, 69, 0.15);
            color: #dc3545;
        }

        .custom-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 10px;
        }

        .custom-left {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .custom-btn-tambah-wrap {
            flex: 1 1 auto;
        }

        .custom-form-import {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .custom-input-file {
            padding: 8px;
            border: 1px solid #ccc;
            background-color: #fff;
            border-radius: 4px;
            flex: 1 1 auto;
        }

        .custom-btn-import {
            flex: 0 0 auto;
            white-space: nowrap;
        }

        .custom-right {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 10px;
            flex: 0 0 auto;
        }

        .custom-limit-page {
            flex: 0 0 auto;
        }

        .custom-search {
            flex: 0 0 auto;
            width: 200px;
        }

        @media (max-width: 767.98px) {
            .custom-header {
                flex-direction: column;
                align-items: stretch;
            }

            .custom-left {
                flex-direction: column;
                align-items: stretch;
            }

            .custom-btn-tambah-wrap {
                width: 100%;
            }

            .custom-form-import {
                flex-direction: row;
                justify-content: space-between;
                width: 100%;
            }

            .custom-input-file {
                flex: 1 1 65%;
            }

            .custom-btn-import {
                flex: 1 1 30%;
            }

            .custom-right {
                flex-direction: row;
                justify-content: space-between;
                width: 100%;
                margin-top: 10px;
            }

            .custom-limit-page {
                flex: 1 1 25%;
            }

            .custom-search {
                flex: 1 1 70%;
            }

            .custom-btn-tambah {
                width: 100%;
            }
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
                        <div class="card-header custom-header">
                            <div class="custom-left">
                                <button class="btn-dynamic btn btn-outline-primary custom-btn-tambah" type="button"
                                    data-toggle="collapse" data-target="#filter-collapse" aria-expanded="false"
                                    aria-controls="filter-collapse"data-container="body" data-toggle="tooltip"
                                    data-placement="top" title="Filter Pembelian Barang">
                                    <i class="fa fa-filter"></i> Filter
                                </button>
                            </div>
                            <div class="custom-right">
                                <div class="custom-limit-page">
                                    <select name="limitPage" id="limitPage" class="form-control">
                                        <option value="10">10</option>
                                        <option value="20">20</option>
                                        <option value="30">30</option>
                                    </select>
                                </div>
                                <div class="custom-search">
                                    <input id="tb-search" class="tb-search form-control" type="search" name="search"
                                        placeholder="Cari Data" aria-label="search">
                                </div>
                            </div>
                        </div>
                        <div class="content">
                            <div class="collapse mt-2 pl-4" id="filter-collapse">
                                <form id="custom-filter" class="row align-items-center">
                                    <div class="col-md-4 mb-2">
                                        <select class="form-control select2" id="logEvent" name="logEvent"></select>
                                    </div>
                                    <div class="col-md-8 mb-2" style="gap: 0.5rem;">
                                        <button class="btn btn-info h-100" id="tb-filter" type="submit">
                                            <i class="fa fa-magnifying-glass mr-2"></i>Cari
                                        </button>
                                        <button type="button" class="btn btn-secondary h-100" id="tb-reset">
                                            <i class="fa fa-rotate mr-2"></i>Reset
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive table-scroll-wrapper">
                                    <table class="table table-striped m-0">
                                        <thead>
                                            <tr class="tb-head">
                                                <th class="text-center text-wrap align-top">No</th>
                                                <th class="text-wrap align-top">Data Dibuat</th>
                                                <th class="text-wrap align-top">User & IP Address</th>
                                                <th class="text-wrap align-top">Log</th>
                                                <th class="text-wrap align-top">Event</th>
                                                <th class="text-wrap align-top">Deskripsi</th>
                                                {{-- <th class="text-wrap align-top">Metode URL</th> --}}
                                                <th class="text-wrap align-top">Pesan dari User</th>
                                                <th class="text-center text-wrap align-top">Action</th>
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

    <div class="modal fade" id="logDetailModal" tabindex="-1" aria-labelledby="logDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content rounded-4 shadow-sm">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="logDetailModalLabel"><i class="fa fa-book mr-1"></i>Detail Log
                        Aktivitas</h5>
                    <button type="button" class="btn-close reset-all close" data-bs-dismiss="modal" aria-label="Close"><i
                            class="fa fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive mb-4">
                        <table class="table table-sm table-bordered w-100">
                            <tbody id="logDetailContent"></tbody>
                        </table>
                    </div>

                    <!-- Change Table -->
                    <h6 class="mt-4 mb-2 fw-bold">Perubahan Data</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle" id="logChangeTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Field</th>
                                    <th id="oldColumn">Old</th>
                                    <th id="newColumn">New</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
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
        let title = '{{ $menu[0] }}';
        let defaultLimitPage = 10;
        let currentPage = 1;
        let totalPage = 1;
        let defaultAscending = 0;
        let defaultSearch = '';
        let customFilter = {};
        let selectOptions = [{
            id: '#logEvent',
            isUrl: '{{ route('log.get.log-event') }}',
            placeholder: 'Pilih Log Event',
        }];

        async function getListData(limit = 10, page = 1, ascending = 0, search = '', customFilter = {}) {
            $('#listData').html(loadingData());

            let filterParams = {
                ...customFilter
            };

            let getDataRest = await renderAPI(
                'GET',
                '{{ route('log.get') }}', {
                    page: page,
                    limit: limit,
                    ascending: ascending,
                    search: search,
                    id_toko: '{{ auth()->user()->id_toko }}',
                    id_user: '{{ auth()->user()->id }}',
                    ...filterParams
                }
            ).then(function(response) {
                return response;
            }).catch(function(error) {
                let resp = error.response;
                return resp;
            });

            if (getDataRest.status == 200 && Array.isArray(getDataRest.data.data) && getDataRest.data.data.length > 0) {
                let handleDataArray = await Promise.all(
                    getDataRest.data.data.map(async item => await handleData(item))
                );
                await setListData(handleDataArray, getDataRest.data.pagination);
            } else {
                let errorMessage = 'Tidak ada data';
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
            let detail_button = `
            <button id="detail-${data.id}" class="p-1 btn detail-data action_button atur-harga-btn"
                data-container="body" data-toggle="tooltip" data-placement="top"
                title="Detail ${title}: ${data.log_name}"
                onclick="openModal('${encodeURIComponent(JSON.stringify(data))}')">
                <span class="text-dark">Detail</span>
                <div class="icon text-info">
                    <i class="fa fa-book"></i>
                </div>
            </button>`;

            return {
                id: data?.id ?? '-',
                created_at: data?.created_at ?? '-',
                user: data?.user ?? '-',
                ip_address: data?.ip_address ?? '-',
                log_name: data?.log_name ?? '-',
                event: data?.event ?? '-',
                description: data?.description ?? '-',
                method: data?.method ?? '-',
                route: data?.route ?? '-',
                message: data?.message ?? '-',
                detail_button
            };
        }

        async function setListData(dataList, pagination) {
            totalPage = pagination.total_pages;
            currentPage = pagination.current_page;
            let display_from = ((defaultLimitPage * (currentPage - 1)) + 1);
            let display_to = Math.min(display_from + dataList.length - 1, pagination.total);

            let getDataTable = '';
            let classCol = 'align-center text-dark text-wrap';
            dataList.forEach((element, index) => {
                getDataTable += `
                    <tr class="text-dark">
                        <td class="${classCol} text-center">${display_from + index}.</td>
                        <td class="${classCol}">${element.created_at}</td>
                        <td class="${classCol}">${element.user} - ${element.ip_address}</td>
                        <td class="${classCol}">${element.log_name}</td>
                        <td class="${classCol}">${element.event}</td>
                        <td class="${classCol}">${element.description}</td>
                        <td class="${classCol}">${element.message}</td>
                        <td class="${classCol}">
                            <div class="d-flex justify-content-center w-100">
                                <div class="hovering p-1">
                                    ${element.detail_button}
                                </div>
                            </div>
                        </td>
                    </tr>`;
            });

            $('#listData').html(getDataTable);
            $('#totalPage').text(pagination.total);
            $('#countPage').text(`${display_from} - ${display_to}`);
            $('[data-toggle="tooltip"]').tooltip();
            renderPagination();
        }

        // <td class="${classCol}"><sup class="badge badge-method method-${(element.method ?? '').toLowerCase()}">${element.method}</sup> ${element.route}</td>
        async function filterList() {
            document.getElementById('custom-filter').addEventListener('submit', async function(e) {
                e.preventDefault();

                customFilter = {
                    log_event: $("#logEvent").val() || '',
                };

                defaultSearch = $('.tb-search').val();
                defaultLimitPage = $("#limitPage").val();
                currentPage = 1;

                await getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch,
                    customFilter);
            });

            document.getElementById('tb-reset').addEventListener('click', async function() {
                $('#custom-filter select').val(null).trigger('change');
                customFilter = {};
                defaultSearch = $('.tb-search').val();
                defaultLimitPage = $("#limitPage").val();
                currentPage = 1;
                await getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch,
                    customFilter);
            });
        }

        function capitalizeWords(str) {
            return str.replace(/\b\w/g, char => char.toUpperCase());
        }

        function openModal(dataStr) {
            const data = JSON.parse(decodeURIComponent(dataStr));
            const {
                properties,
                ...info
            } = data;

            // Populate detail section
            const detailHTML = Object.entries(info).map(([key, value]) => {
                return `<tr>
                    <th class="text-capitalize text-nowrap">${key.replace(/_/g, ' ')}</th>
                    <td>${value ?? '-'}</td>
                </tr>`;
            }).join('');
            $('#logDetailContent').html(detailHTML);

            // Populate change section
            const tbody = $('#logChangeTable tbody');
            tbody.empty();

            const oldData = properties?.changes?.old || {};
            const newData = properties?.changes?.new || {};
            const allKeys = new Set([...Object.keys(oldData), ...Object.keys(newData)]);

            allKeys.forEach(key => {
                const oldVal = oldData[key] ?? '';
                const newVal = newData[key] ?? '';

                const oldCell = oldVal !== '' ?
                    `<span class="badge bg-danger-subtle text-danger">${oldVal}</span>` : '-';
                const newCell = newVal !== '' ?
                    `<span class="badge bg-success-subtle text-success">${newVal}</span>` : '-';

                tbody.append(`
            <tr>
                <td class="text-capitalize text-nowrap">${key.replace(/_/g, ' ')}</td>
                <td>${oldCell}</td>
                <td>${newCell}</td>
            </tr>
        `);
            });

            $('#logDetailModal').modal('show');
        }


        function formatValue(val) {
            if (val === null || val === undefined) return '<span class="text-muted">–</span>';
            return typeof val === 'object' ? JSON.stringify(val, null, 2) : val;
        }

        function wrapToggle(content) {
            const text = content.toString();
            if (text.length > 100) {
                const id = `toggle-${Math.random().toString(36).substring(2, 9)}`;
                return `
            <div class="toggle-content" id="${id}">${text}</div>
            <span class="toggle-btn" onclick="toggleContent('${id}', this)">Lihat Selengkapnya</span>
        `;
            }
            return text;
        }

        function toggleContent(id, el) {
            const content = document.getElementById(id);
            content.classList.toggle('expanded');
            el.textContent = content.classList.contains('expanded') ? 'Sembunyikan' : 'Lihat Selengkapnya';
        }

        async function initPageLoad() {
            await Promise.all([
                setDynamicButton(),
                selectData(selectOptions),
                getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch, customFilter),
                searchList(),
                filterList(),
            ])
        }
    </script>
@endsection
