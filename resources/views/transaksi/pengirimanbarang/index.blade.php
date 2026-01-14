@extends('layouts.main')

@section('title')
    Pengiriman Barang
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('css/button-action.css') }}">
    <link rel="stylesheet" href="{{ asset('css/table.css') }}">
    <link rel="stylesheet" href="{{ asset('css/daterange-picker.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sweetalert2.css') }}">
    <link rel="stylesheet" href="{{ asset('css/flatpickr.min.css') }}">
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
                                <div class="col-12 col-xl-6 col-lg-6 mb-2">
                                    <div class="row">
                                        <div class="col-6 col-xxl-2 col-lg-4 col-xl-3">
                                            <button class="btn btn-primary text-white add-data w-100" data-container="body"
                                                data-toggle="tooltip" data-placement="top" title="Tambah Pengiriman Barang">
                                                <i class="fa fa-plus-circle"></i> Tambah
                                            </button>
                                        </div>
                                        <div class="col-12 col-xxl-2 col-lg-4 col-xl-3">
                                            <button class="btn-dynamic btn btn-outline-primary mb-2 w-100" type="button"
                                                data-toggle="collapse" data-target="#filter-collapse" aria-expanded="false"
                                                aria-controls="filter-collapse"data-container="body" data-toggle="tooltip"
                                                data-placement="top" title="Filter Pengiriman Barang">
                                                <i class="fa fa-filter"></i> Filter
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-xl-6 col-lg-6 mb-2">
                                    <div class="row justify-content-end">
                                        <div class="col-4 col-xl-2 col-lg-3">
                                            <select name="limitPage" id="limitPage" class="form-control mr-2 mb-2 mb-lg-0">
                                                <option value="10">10</option>
                                                <option value="20">20</option>
                                                <option value="30">30</option>
                                            </select>
                                        </div>
                                        <div class="col-8 col-xl-4 col-lg-4 justify-content-end">
                                            <input id="tb-search" class="tb-search form-control mb-2 mb-lg-0" type="search"
                                                name="search" placeholder="Cari Data" aria-label="search">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="content">
                            <x-adminlte-alerts />
                            <div class="collapse mt-2" id="filter-collapse">
                                <form id="custom-filter" class="row g-2 align-items-center mx-2">
                                    <div class="col-12 col-xl-3 col-lg-3 mb-2">
                                        <input class="form-control" type="text" id="daterange" name="daterange"
                                            placeholder="Pilih rentang tanggal">
                                    </div>
                                    <div class="col-12 col-xl-9 col-lg-3 mb-2 d-flex justify-content-end align-items-start">
                                        <button form="custom-filter" class="btn btn-info mr-2" id="tb-filter"
                                            type="submit">
                                            <i class="fa fa-magnifying-glass mr-2"></i>Cari
                                        </button>
                                        <button type="button" class="btn btn-secondary" id="tb-reset">
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
                                                <th class="text-wrap align-top">Status</th>
                                                <th class="text-wrap align-top">Tgl Kirim</th>
                                                <th class="text-wrap align-top">Tgl Terima</th>
                                                <th class="text-wrap align-top">No. Resi</th>
                                                <th class="text-wrap align-top">Toko Pengirim</th>
                                                <th class="text-wrap align-top">Nama Pengirim</th>
                                                <th class="text-wrap align-top">Ekspedisi</th>
                                                <th class="text-wrap align-top">Total Qty</th>
                                                <th class="text-wrap align-top">Toko Penerima</th>
                                                <th class="text-wrap align-top">Action</th>
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

    <div class="modal fade" id="modal-form" tabindex="-1" role="dialog" aria-labelledby="modal-form-label"
        aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-title">Tambah Pengiriman Barang</h5>
                    <button type="button" class="btn-close reset-all close" data-bs-dismiss="modal"
                        aria-label="Close"><i class="fa fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <form id="formTambahData">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Toko Asal<sup class="text-danger">*</sup></label>
                                    <input type="text" class="form-control" value="{{ auth()->user()->toko->nama }}"
                                        disabled>
                                    <input type="hidden" id="toko_asal_id" value="{{ auth()->user()->toko_id }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="toko_tujuan_id">Toko Tujuan<sup class="text-danger">*</sup></label>
                                    <select id="toko_tujuan_id" class="form-control select2" required>
                                        <option value="">Pilih Toko Tujuan</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="send_at">Tanggal Kirim<sup class="text-danger">*</sup></label>
                                    <input type="datetime-local" id="send_at" class="form-control"
                                        placeholder="Masukkan Tanggal Kirim" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="ekspedisi">Ekspedisi</label>
                                    <input type="text" id="ekspedisi" class="form-control"
                                        placeholder="Nama ekspedisi" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="no_resi">No Resi<sup class="text-danger">*</sup></label>
                                    <input type="text" id="no_resi" class="form-control"
                                        placeholder="Masukkan no resi" required>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row mb-3">
                            <div class="col-md-5">
                                <label>Scan QR Code</label>
                                <input type="text" id="scan_batch_input" class="form-control"
                                    placeholder="Scan QR / input QR lalu Enter" autofocus>
                            </div>
                            <div class="col-md-2 d-flex flex-column align-items-center justify-content-center">
                                <span class="font-weight-bold">Atau</span>
                                <small id="scan-info" style="display:none;" class="text-muted"></small>
                            </div>
                            <div class="col-md-5">
                                <label>Pilih Barang Manual</label>
                                <select id="select_batch_manual" class="form-control select2"></select>
                            </div>
                        </div>
                        <hr>
                        <table class="table table-bordered" id="table-detail">
                            <thead class="bg-light font-weight-bold">
                                <tr>
                                    <td class="text-center">Barang</td>
                                    <td class="text-center">Harga Beli</td>
                                    <td class="text-center">Qty yang dikirim</td>
                                    <td class="text-center">Aksi</td>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa fa-circle-xmark mr-1"></i> Tutup
                    </button>

                    <button type="button" class="btn btn-warning text-dark" id="btn-draft">
                        <i class="fa fa-save mr-1"></i> Simpan Draft
                    </button>

                    <button type="button" class="btn btn-primary" id="submit-button">
                        <i class="fa fa-save mr-1"></i> Simpan Final
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-verifikasi">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-title"><i
                            class="fa fa-circle-check mr-1 text-success"></i>Verifikasi Pengiriman Barang</h5>
                    <button type="button" class="btn-close reset-all close" data-bs-dismiss="modal"
                        aria-label="Close"><i class="fa fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <div class="card mb-3">
                        <div class="card-body p-0">
                            <div class="row">
                                <div class="col-12 col-md-4 mb-2">
                                    <div
                                        class="list-group-item d-flex justify-content-between align-items-center border rounded p-3">
                                        <h5 class="mb-0"><i class="fa fa-barcode mr-1"></i> No Resi</h5>
                                        <span id="info-no-resi" class="badge badge-secondary"></span>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4 mb-2">
                                    <div
                                        class="list-group-item d-flex justify-content-between align-items-center border rounded p-3">
                                        <h5 class="mb-0"><i class="fa fa-truck mr-1"></i> Ekspedisi</h5>
                                        <span id="info-ekspedisi" class="badge badge-secondary"></span>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4 mb-2">
                                    <div
                                        class="list-group-item d-flex justify-content-between align-items-center border rounded p-3">
                                        <h5 class="mb-0"><i class="fa fa-circle-info mr-1"></i> Status</h5>
                                        <span id="info-status" class="badge badge-secondary"></span>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4 mb-2">
                                    <div
                                        class="list-group-item d-flex justify-content-between align-items-center border rounded p-3">
                                        <h5 class="mb-0"><i class="fa fa-shop mr-1"></i> Toko Asal</h5>
                                        <span id="info-toko-asal" class="badge badge-secondary"></span>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4 mb-2">
                                    <div
                                        class="list-group-item d-flex justify-content-between align-items-center border rounded p-3">
                                        <h5 class="mb-0"><i class="fa fa-shop mr-1"></i> Toko Tujuan</h5>
                                        <span id="info-toko-tujuan" class="badge badge-secondary"></span>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4 mb-2">
                                    <div
                                        class="list-group-item d-flex justify-content-between align-items-center border rounded p-3">
                                        <h5 class="mb-0"><i class="fa fa-user-tie mr-1"></i> Dikirim Oleh</h5>
                                        <span id="info-send-by" class="badge badge-secondary"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <table class="table table-bordered" id="table-verifikasi">
                        <thead class="bg-light font-weight-bold">
                            <tr>
                                <td class="text-center">Barang / Batch</td>
                                <td class="text-center">Harga Beli</td>
                                <td class="text-center">Qty Dikirim</td>
                                <td class="text-center">Qty Diterima</td>
                                <td class="text-center">Qty Problem</td>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa fa-circle-xmark mr-1"></i> Tutup
                    </button>
                    <button id="submit-verifikasi" class="btn btn-success">
                        <i class="fa fa-circle-check mr-1"></i> Simpan Verifikasi
                    </button>
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
    <script src="{{ asset('js/flatpickr.js') }}"></script>
@endsection

@section('js')
    <script>
        let defaultLimitPage = 10;
        let currentPage = 1;
        let totalPage = 1;
        let defaultAscending = 0;
        let defaultSearch = '';
        let customFilter = {};
        let selectOptions = [{
                id: '#toko_tujuan_id',
                isUrl: '{{ route('master.toko') }}',
                placeholder: 'Pilih Kas',
                isModal: '#modal-form',
                isFilter: {
                    not_self: {{ auth()->user()->toko_id }},
                },
            },
            {
                id: '#select_batch_manual',
                isFilter: {
                    toko_id: {{ auth()->user()->toko_id }},
                },
                isUrl: '{{ route('sb.batch.get') }}',
                placeholder: 'Pilih Barang',
                isModal: '#modal-form',
                isForm: true,
                isImage: true
            },
        ];

        async function getListData(limit = 10, page = 1, ascending = 0, search = '', customFilter = {}) {
            $('#listData').html(loadingData());

            let filterParams = {};

            if (customFilter['startDate'] && customFilter['endDate']) {
                filterParams.startDate = customFilter['startDate'];
                filterParams.endDate = customFilter['endDate'];
            }

            let getDataRest = await renderAPI(
                'GET',
                '{{ route('distribusi.pengiriman.get') }}', {
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
            let id_toko = {{ auth()->user()->toko_id }};
            let status = '';
            if (data?.status === 'Sukses') {
                status =
                    `<span class="badge badge-success custom-badge"><i class="mx-1 fa fa-circle-check"></i>Sukses</span>`;
            } else if (data?.status === 'Pending') {
                status =
                    `<span class="badge badge-info custom-badge"><i class="mx-1 fa fa-circle-half-stroke "></i>Pending</span>`;
            } else if (data?.status === 'Progress') {
                status =
                    `<span class="badge badge-warning custom-badge"><i class="mx-1 fa fa-spinner fa-spin"></i>Progress</span>`;
            } else if (data?.status === 'Gagal') {
                status =
                    `<span class="badge badge-danger custom-badge"><i class="mx-1 fa fa-circle-xmark"></i>Gagal</span>`;
            } else {
                status = `<span class="badge badge-secondary custom-badge">Tidak Diketahui</span>`;
            }

            let delete_button = '';
            let detail_button = '';

            if (id_toko == data?.id_toko_pengirim) {
                detail_button = (data?.status === 'Pending') ? `
                <a href="pengirimanbarang/detail/${data.id}" class="p-1 btn detail-data action_button"
                    data-container="body" data-toggle="tooltip" data-placement="top"
                    title="Edit Data Nomor Resi: ${data.no_resi}"
                    data-id='${data.id}'>
                    <span class="text-dark">Edit</span>
                    <div class="icon text-warning">
                        <i class="fa fa-edit"></i>
                    </div>
                </a>` :
                    (data?.status === 'Progress' || data?.status === 'Sukses') ? `
                <a href="pengirimanbarang/detail/${data.id}" class="p-1 btn detail-data action_button"
                    data-container="body" data-toggle="tooltip" data-placement="top"
                    title="Detail Data Nomor Resi: ${data.no_resi}"
                    data-id='${data.id}'>
                    <span class="text-dark">Detail</span>
                    <div class="icon text-info">
                        <i class="fa fa-book"></i>
                    </div>
                </a>` : '';

                delete_button = (data?.status === 'Progress' || data?.status === 'Pending') ? `
                <a class="p-1 btn hapus-data action_button"
                    data-container="body" data-toggle="tooltip" data-placement="top"
                    title="Hapus Data Nomor Resi: ${data.no_resi}"
                    data-id='${data.id}' data-name='${data.no_resi}'>
                    <span class="text-dark">Hapus</span>
                    <div class="icon text-danger">
                        <i class="fa fa-trash-alt"></i>
                    </div>
                </a>` : '';
            }

            let edit_button = '';
            if (id_toko == data?.toko_tujuan_id && data?.status == 'Progress') {
                edit_button = `
                <button class="p-1 btn action_button verify-data"
                    data-id="${data.id}"
                    title="Verifikasi Nomor Resi: ${data.no_resi}">
                    <span class="text-dark">Verif</span>
                    <div class="icon text-success">
                        <i class="fa fa-circle-check"></i>
                    </div>
                </button>`;
            }

            if (id_toko == data?.toko_tujuan_id && data?.status == 'Sukses') {
                detail_button = `
                <a href="pengirimanbarang/detail/${data.id}" class="p-1 btn detail-data action_button"
                    data-container="body" data-toggle="tooltip" data-placement="top"
                    title="Detail Data Nomor Resi: ${data.no_resi}"
                    data-id='${data.id}'>
                    <span class="text-dark">Detail</span>
                    <div class="icon text-info">
                        <i class="fa fa-book"></i>
                    </div>
                </a>`;
            }

            let action_buttons = '';
            if (edit_button || detail_button || delete_button) {
                action_buttons = `
                <div class="d-flex justify-content-start">
                    ${edit_button ? `<div class="hovering p-1">${edit_button}</div>` : ''}
                    ${detail_button ? `<div class="hovering p-1">${detail_button}</div>` : ''}
                    ${delete_button ? `<div class="hovering p-1">${delete_button}</div>` : ''}
                </div>`;
            } else {
                action_buttons = `
                <span class="badge badge-secondary">Tidak Ada Aksi</span>`;
            }

            return {
                id: data?.id ?? '-',
                status,
                no_resi: data?.no_resi ?? '-',
                ekspedisi: data?.ekspedisi ?? '-',
                toko_asal: data?.toko_asal ?? '-',
                nama_pengirim: data?.nama_pengirim ?? '-',
                tgl_kirim: data?.tgl_kirim ?? '-',
                tgl_terima: data?.tgl_terima ?? '<span class="badge badge-secondary"><i>Belum diterima</i></span>',
                total_item: data?.total_item ?? '-',
                toko_tujuan: data?.toko_tujuan ?? '-',
                action_buttons,
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
                <tr class="text-dark clickable-row" data-id="${element.id}">
                    <td class="${classCol} text-center">${display_from + index}.</td>
                    <td class="${classCol}">${element.status}</td>
                    <td class="${classCol}">${element.tgl_kirim}</td>
                    <td class="${classCol}">${element.tgl_terima}</td>
                    <td class="${classCol}">${element.no_resi}</td>
                    <td class="${classCol}">${element.toko_asal}</td>
                    <td class="${classCol}">${element.nama_pengirim}</td>
                    <td class="${classCol}">${element.ekspedisi}</td>
                    <td class="${classCol}">${element.total_item}</td>
                    <td class="${classCol}">${element.toko_tujuan}</td>
                    <td class="${classCol}">${element.action_buttons}</td>
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
                    startDate = startDate.startOf('day').toISOString();
                    endDate = endDate.endOf('day').toISOString();
                }

                customFilter = {
                    'startDate': $("#daterange").val() != '' ? startDate : '',
                    'endDate': $("#daterange").val() != '' ? endDate : ''
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

        async function deleteData() {
            $(document).on("click", ".hapus-data", async function() {
                let id = $(this).attr("data-id");
                let name = $(this).attr("data-name");

                swal({
                    title: `Hapus Pengiriman No Resi: ${name}`,
                    text: "Apakah anda yakin?",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Ya, Hapus!",
                    cancelButtonText: "Tidak, Batal!",
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    reverseButtons: true,
                    confirmButtonClass: "btn btn-danger",
                    cancelButtonClass: "btn btn-secondary",
                }).then(async (result) => {
                    let postDataRest = await renderAPI(
                        'DELETE',
                        `pengirimanbarang/${id}/delete`
                    ).then(function(response) {
                        return response;
                    }).catch(function(error) {
                        let resp = error.response;
                        return resp;
                    });

                    if (postDataRest.status == 200) {
                        setTimeout(function() {
                            getListData(defaultLimitPage, currentPage,
                                defaultAscending,
                                defaultSearch, customFilter);
                        }, 500);
                        notificationAlert('success', 'Pemberitahuan', postDataRest.data
                            .message);
                    }
                }).catch(swal.noop);
            })
        }

        async function addData() {
            $(document).on("click", ".add-data", function() {
                $("#formTambahData")[0].reset();
                $("#toko_tujuan_id").val("").trigger("change");
                $("#select_batch_manual").val("").trigger("change");
                $("#table-detail tbody").html("");
                if ($("#table-detail tbody tr").length === 0) {
                    showEmptyMessage();
                }
                $("#scan_batch_input").val("");
                $("#scan-info").hide().text("");
                $("#modal-title").html(
                    `<i class="fa fa-circle-plus mr-1"></i>Form Tambah Pengiriman Barang`
                );
                $("#formTambahData").data("action-url", '{{ route('distribusi.pengiriman.post') }}');
                $("#modal-form").modal("show");
                if (window.tglPengiriman) {
                    tglPengiriman.setDate(new Date());
                }

                draftForm();
            });
        }

        async function submitForm() {
            $(document).on("click", "#submit-button", async function(e) {
                e.preventDefault();

                const $submitButton = $("#submit-button");
                const originalButtonHTML = $submitButton.html();

                $submitButton.prop("disabled", true).html(
                    `<i class="fas fa-spinner fa-spin"></i> Menyimpan...`);

                loadingPage(true);

                let actionUrl = $("#formTambahData").data("action-url");

                let details = [];

                $("#table-detail tbody tr").each(function() {
                    details.push({
                        barang_id: $(this).find(".barang_id").val(),
                        stock_barang_batch_id: $(this).find(".stock_batch_id").val(),
                        qty_send: $(this).find(".qty_send").val(),
                    });
                });

                let formData = {
                    toko_asal_id: {{ auth()->user()->toko_id }},
                    toko_tujuan_id: $("#toko_tujuan_id").val(),
                    no_resi: $("#no_resi").val(),
                    ekspedisi: $("#ekspedisi").val(),
                    send_by: {{ auth()->user()->id }},
                    send_at: $("#send_at").val(),
                    details: details
                };

                try {
                    let postData = await renderAPI("POST", actionUrl, formData);

                    loadingPage(false);
                    if (postData.status >= 200 && postData.status < 300) {
                        notificationAlert("success", "Pemberitahuan", postData.data.message || "Berhasil");
                        setTimeout(async function() {
                            await getListData(defaultLimitPage, currentPage, defaultAscending,
                                defaultSearch, customFilter);
                        }, 500);
                        setTimeout(() => {
                            $("#modal-form").modal("hide");
                        }, 500);
                    } else {
                        notificationAlert("info", "Pemberitahuan", postData.data.message ||
                            "Terjadi kesalahan");
                    }
                } catch (error) {
                    loadingPage(false);
                    let resp = error.response?.data || {};
                    notificationAlert("error", "Kesalahan", resp.message || "Terjadi kesalahan");
                } finally {
                    $submitButton.prop("disabled", false).html(originalButtonHTML);
                }
            });
        }

        async function draftForm(id = null) {
            $(document).off("click", "#btn-draft");
            $(document).on("click", "#btn-draft", async function(e) {
                e.preventDefault();

                const $submitButton = $("#btn-draft");
                const originalButtonHTML = $submitButton.html();

                $submitButton.prop("disabled", true).html(
                    `<i class="fas fa-spinner fa-spin"></i> Menyimpan...`
                );

                loadingPage(true);

                let details = [];

                $("#table-detail tbody tr").each(function() {
                    details.push({
                        barang_id: $(this).find(".barang_id").val(),
                        stock_barang_batch_id: $(this).find(".stock_batch_id").val(),
                        qty_send: $(this).find(".qty_send").val(),
                    });
                });

                let formData = {
                    pengiriman_barang_id: id,
                    toko_asal_id: {{ auth()->user()->toko_id }},
                    toko_tujuan_id: $("#toko_tujuan_id").val(),
                    no_resi: $("#no_resi").val(),
                    ekspedisi: $("#ekspedisi").val(),
                    send_by: {{ auth()->user()->id }},
                    send_at: $("#send_at").val(),
                    details: details
                };

                try {
                    let postData = await renderAPI("POST", '{{ route('distribusi.pengiriman.draft') }}', formData);

                    loadingPage(false);
                    if (postData.status >= 200 && postData.status < 300) {
                        notificationAlert("success", "Pemberitahuan", postData.data.message || "Berhasil");

                        setTimeout(async function() {
                            await getListData(defaultLimitPage, currentPage, defaultAscending,
                                defaultSearch, customFilter);
                        }, 500);

                        setTimeout(() => {
                            $("#modal-form").modal("hide");
                        }, 500);

                    } else {
                        notificationAlert("info", "Pemberitahuan",
                            postData.data.message || "Terjadi kesalahan");
                    }
                } catch (error) {
                    loadingPage(false);
                    let resp = error.response?.data || {};
                    notificationAlert("error", "Kesalahan", resp.message || "Terjadi kesalahan");
                } finally {
                    $submitButton.prop("disabled", false).html(originalButtonHTML);
                }
            });
        }

        async function setDatePicker() {
            window.tglPengiriman = flatpickr("#send_at", {
                enableTime: true,
                time_24hr: true,
                dateFormat: "Y-m-d H:i",
                defaultDate: new Date(),
                allowInput: true,
                appendTo: document.querySelector('.modal-body'),
                position: "above",

                onDayCreate: (dObj, dStr, fp, dayElem) => {
                    dayElem.addEventListener('click', () => {
                        fp.calendarContainer.querySelectorAll('.selected').forEach(el => {
                            el.style.backgroundColor = "#1abc9c";
                            el.style.color = "#fff";
                        });
                    });
                }
            });

            const inputField = document.querySelector("#send_at");

            inputField.removeAttribute("readonly");

            inputField.style.backgroundColor = "";
            inputField.style.cursor = "pointer";
        }

        function addRowItem() {
            $(document).off("keydown", "#scan_batch_input");
            $(document).off("change", "#select_batch_manual");
            $(document).off("click", ".remove-item");

            let allowSubmit = false;

            $('#submit-button').on('click', function() {
                allowSubmit = true;
            });

            $('#formTambahData').on('submit', function(e) {
                if (!allowSubmit) {
                    e.preventDefault();
                    return false;
                }
                allowSubmit = false;
            });

            $(document).on("keydown", "#scan_batch_input", async function(e) {
                if (e.key === "Enter") {
                    e.preventDefault();

                    let qrcode = $(this).val().trim();
                    if (!qrcode) return;

                    showScanInfo("Mencari batch...", "text-info");

                    await handleRow(qrcode);
                    $(this).val("");
                }
            });

            $(document).on("change", "#select_batch_manual", async function() {
                let batchId = $(this).val();
                if (!batchId) return;

                await handleRow(batchId);

                $(this).val("").trigger("change.select2");
            });

            $(document).on("click", ".remove-item", function() {
                $(this).closest("tr").remove();

                if ($("#table-detail tbody tr").length === 0) {
                    showEmptyMessage();
                }
            });

            $(document).on("keydown", "#formTambahData", function(e) {
                if (e.key === "Enter" && e.target.id !== "scan_batch_input") {
                    e.preventDefault();
                }
            });
        }

        async function handleRow(search) {
            try {
                let res = await renderAPI("GET", '{{ route('sb.batch.getByQR') }}', {
                    search: search,
                    toko_id: {{ auth()->user()->toko_id }}
                });

                if (!res.data || !res.data.data) {
                    showScanInfo("❌ Batch tidak ditemukan", "text-danger");
                    return;
                }

                let data = res.data.data;
                let maxQty = parseInt(data.qty_sisa);

                let existingRow = $(`#table-detail tbody tr`)
                    .filter(function() {
                        return $(this).find(".stock_batch_id").val() == data.id;
                    });

                if (existingRow.length) {
                    let qtyInput = existingRow.find(".qty_send");
                    let currentQty = parseInt(qtyInput.val());

                    if (currentQty >= maxQty) {
                        showScanInfo(`⚠️ Qty sudah maksimal (${maxQty})`, "text-warning");
                        return;
                    }

                    qtyInput.val(currentQty + 1);

                    showScanInfo(`✅ Qty ditambah (${currentQty + 1}/${maxQty})`, "text-success");
                    return;
                }

                if (maxQty <= 0) {
                    showScanInfo("⚠️ Stok sudah habis", "text-warning");
                    return;
                }

                addRow(data, maxQty);
                showScanInfo("✅ Ditambahkan", "text-success");

            } catch {
                showScanInfo("⚠️ Error mencari batch", "text-warning");
            }
        }


        function showEmptyMessage() {
            $("#table-detail tbody").html(`
                <tr class="empty-row">
                    <td colspan="4" class="text-center">
                        <small class="text-muted">
                            <i class="fa fa-circle-info mr-1"></i>
                            Silahkan Scan / Input QR terlebih dahulu
                        </small>
                    </td>
                </tr>
            `);
        }

        function addRow(data, maxQty) {
            const tbody = $("#table-detail tbody");

            tbody.find(".empty-row").remove();

            let row = `
                <tr>
                    <td>${data.text}</td>
                    <td class="text-right">${data.format_harga_beli}</td>
                    <td>
                        <input type="number" class="form-control qty_send"
                            min="1" max="${maxQty}" value="1" required>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-danger btn-sm remove-item">
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>
                    <input type="hidden" class="barang_id" value="${data.barang_id}">
                    <input type="hidden" class="stock_batch_id" value="${data.id}">
                </tr>
            `;

            tbody.append(row);
        }

        function showScanInfo(message, statusClass) {
            let $info = $("#scan-info");

            $info
                .text(message)
                .removeClass("text-danger text-success text-warning text-info")
                .addClass(statusClass)
                .show();

            clearTimeout($info.data("timer"));

            let timer = setTimeout(() => {
                $info.fadeOut(600, function() {
                    $(this).text("").removeClass(statusClass).show();
                });
            }, 3000);

            $info.data("timer", timer);
        }

        function verifyData() {
            $(document).on("click", ".verify-data", async function() {
                let id = $(this).data("id");

                let res = await renderAPI("GET", '{{ route('distribusi.pengiriman.progress') }}', {
                    id
                });

                let data = res.data.data;

                $("#info-no-resi").text(data.no_resi);
                $("#info-ekspedisi").text(data.ekspedisi);
                $("#info-toko-asal").text(data.toko_asal?.nama);
                $("#info-toko-tujuan").text(data.toko_tujuan?.nama);
                $("#info-send-by").text(data.sender?.nama);
                $("#info-status").text(data.status.toUpperCase());

                let statusColor = {
                    pending: "badge-secondary",
                    progress: "badge-warning",
                    success: "badge-success",
                    canceled: "badge-danger"
                };
                $("#info-status").removeClass().addClass("badge " + statusColor[data.status]);

                let rows = "";

                data.details.forEach(item => {
                    let barangNama = item.barang?.nama ?? "-";
                    let qrcode = item.batch?.qrcode ?? "-";
                    let path = item.batch?.path ?? "-";

                    rows += `
                    <tr data-id="${item.id}">
                        <td>
                            ${item.text}
                        </td>
                        <td class="text-right">
                            ${item.format_harga_beli}
                        </td>
                        <td class="qty-send text-center font-weight-bold">${item.qty_send}</td>
                        <td>
                            <input type="number" class="form-control qty-verified"
                                min="0" max="${item.qty_send}" value="${item.qty_send}">
                        </td>
                        <td class="qty-problem text-center">0</td>
                    </tr>`;
                });

                $("#table-verifikasi tbody").html(rows);
                $("#modal-verifikasi").modal("show");
            });

            $(document).on("input", ".qty-verified", function() {
                let row = $(this).closest("tr");
                let qtySend = parseInt(row.find(".qty-send").text());
                let qtyVerif = parseInt($(this).val());
                if (isNaN(qtyVerif)) qtyVerif = 0;

                let qtyProblem = qtySend - qtyVerif;

                row.find(".qty-problem").text(qtyProblem);
            });

            submitVerifyForm();
        }

        async function submitVerifyForm() {
            $(document).on("click", "#submit-verifikasi", async function(e) {
                e.preventDefault();

                const $btn = $("#submit-verifikasi");
                const originalBtnHTML = $btn.html();

                $btn.prop("disabled", true).html(`<i class="fas fa-spinner fa-spin"></i> Memproses...`);

                let payload = [];

                $("#table-verifikasi tbody tr").each(function() {
                    payload.push({
                        id: $(this).data("id"),
                        qty_verified: $(this).find(".qty-verified").val(),
                        qty_problem: $(this).find(".qty-problem").text(),
                    });
                });

                let pengirimanId = $(".verify-data").data("id");

                try {
                    let postDataRest = await renderAPI("POST", '{{ route('distribusi.pengiriman.verify') }}', {
                        id: pengirimanId,
                        verified_by: {{ auth()->user()->id }},
                        details: payload
                    });

                    if (postDataRest.status == 200) {

                        $("#modal-verifikasi").modal("hide");

                        setTimeout(function() {
                            getListData(
                                defaultLimitPage,
                                currentPage,
                                defaultAscending,
                                defaultSearch,
                                customFilter
                            );
                        }, 500);

                        notificationAlert('success', 'Pemberitahuan', postDataRest.data.message);
                    }

                } catch (err) {
                    notificationAlert(
                        'error',
                        'Terjadi Kesalahan',
                        err?.response?.data?.message || 'Gagal memproses verifikasi'
                    );

                } finally {
                    $btn.prop("disabled", false).html(originalBtnHTML);
                }
            });

        }

        async function initPageLoad() {
            await Promise.all([
                setDynamicButton(),
                getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch, customFilter),
                searchList(),
                filterList(),
                deleteData(),
                addData(),
                addRowItem(),
                submitForm(),
                setDatePicker(),
                selectData(selectOptions),
                showEmptyMessage(),
                verifyData()
            ]);
        }
    </script>
@endsection
