@extends('layouts.main')

@section('title')
    Data Transaksi Kasir
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('css/button-action.css') }}">
    <link rel="stylesheet" href="{{ asset('css/table.css') }}">
    <link rel="stylesheet" href="{{ asset('css/daterange-picker.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sweetalert2.css') }}">
    <link rel="stylesheet" href="{{ asset('css/notyf.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/glossy.css') }}">
    <style>
        .clickable-row {
            cursor: pointer;
        }

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

        #daterange[readonly] {
            background-color: white !important;
            cursor: pointer !important;
            color: inherit !important;
        }

        @media (max-width: 768px) {
            #custom-filter {
                flex-direction: column;
            }

            #custom-filter input,
            #custom-filter button {
                width: 100%;
                margin-bottom: 10px;
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
                                    <h5 class="m-0">Data Transaksi</h5>
                                    <div class="d-flex align-items-center" style="gap: 0.5rem;">
                                        <button
                                            class="btn-dynamic btn btn-md btn-outline-secondary d-flex align-items-center justify-content-center"
                                            type="button" data-toggle="collapse" data-target="#filter-collapse"
                                            aria-expanded="false" aria-controls="filter-collapse" data-container="body"
                                            data-toggle="tooltip" data-placement="top"
                                            style="flex: 0 0 45px; max-width: 45px;" title="Filter Data">
                                            <i class="fa fa-filter my-1"></i>
                                        </button>
                                        @if (hasAnyPermission(['POST /kasir/store']))
                                            <button type="button"
                                                class="btn btn-md btn-outline-primary d-flex align-items-center justify-content-center"
                                                id="btn-add-data" onclick="openAddModal()" data-container="body"
                                                data-toggle="tooltip" data-placement="top"
                                                style="flex: 1 1 45px; max-width: 150px;"
                                                title="Tambah Data {{ $menu[0] }}">
                                                <i class="fa fa-circle-plus my-1"></i>
                                                <span class="d-none d-sm-inline ml-1">Tambah Data</span>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                                <hr class="m-0">
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

                                <div class="d-flex flex-row justify-content-between align-items-center p-3 flex-wrap"
                                    style="gap: 0.5rem;">
                                    <select name="limitPage" id="limitPage" class="form-control"
                                        style="flex: 1 1 80px; max-width: 80px;">
                                        <option value="30">30</option>
                                        <option value="40">40</option>
                                        <option value="50">50</option>
                                        <option value="60">60</option>
                                        <option value="70">70</option>
                                        <option value="80">80</option>
                                        <option value="90">90</option>
                                        <option value="100">100</option>
                                        <option value="150">150</option>
                                        <option value="200">200</option>
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
                    <button type="submit" class="btn btn-success" id="submit-button" form="form-data">Simpan</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade kasirDetailModal" id="kasirDetailModal" tabindex="-1" role="dialog"
        aria-labelledby="kasirDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Transaksi</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="kasir-detail-body">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-3">Memuat data...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
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
    <script src="{{ asset('js/notyf.min.js') }}"></script>
@endsection

@section('js')
    <script>
        let title = 'Transaksi Kasir';
        let scannedBarang = null;
        let defaultLimitPage = 30;
        let currentPage = 1;
        let totalPage = 1;
        let defaultAscending = 0;
        let defaultSearch = '';
        let customFilter = {};
        let selectOptions = [{
                id: '#member_id',
                isFilter: {
                    toko_id: {{ auth()->user()->toko_id }},
                },
                isUrl: '{{ route('master.member') }}',
                placeholder: 'Pilih Member',
                isModal: '#modal-form',
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
        const notyf = new Notyf({
            duration: 3000,
            position: {
                x: 'center',
                y: 'top',
            }
        });
        const user_id_toko = '{{ auth()->user()->toko_id }}';

        let initialModalFormHTML;

        $(document).ready(function() {
            initialModalFormHTML = $('#modal-form').html();
        });

        function selectFormat(isParameter, isPlaceholder, isDisabled = true) {
            if (!$(isParameter).find('option[value=""]').length) {
                $(isParameter).prepend('<option value=""></option>');
            }

            $(isParameter).select2({
                dropdownParent: $('#modal-form'),
                disabled: isDisabled,
                dropdownAutoWidth: true,
                width: '100%',
                placeholder: isPlaceholder,
                allowClear: true,
            });
        }

        async function getListData(limit = 30, page = 1, ascending = 0, search = '', customFilter = {}) {
            $('#listData').html(`
                <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
                    <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
                        <div class="d-flex justify-content-center align-items-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            `);

            let filterParams = {
                ...customFilter
            };
            let getDataRest = await renderAPI(
                'GET',
                '{{ route('tb.kasir.get') }}', {
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

            if (getDataRest && getDataRest.status == 200 && Array.isArray(getDataRest.data.data.item)) {
                let handleDataArray = await Promise.all(
                    getDataRest.data.data.item.map(async item => await handleData(item))
                );
                await setListData(handleDataArray, getDataRest.data.pagination, getDataRest.data.data.total);
                if (getDataRest.data.data.item.length == 0) {
                    $('#listData').html(`
                    <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
                        <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
                            <div class="text-center my-3" role="alert">
                                Tidak ada Transaksi.
                            </div>
                        </div>
                    </div>
                    `);
                }
            } else {
                $('#listData').html(`
                    <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
                        <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
                            <div class="text-center my-3" role="alert">
                                Data tidak tersedia untuk ditampilkan.
                            </div>
                        </div>
                    </div>
                    `);
                $('#countPage').text("0 - 0");
                $('#totalPage').text("0");
                $('#totalData').text(getDataRest?.data?.total ?? 0);
            }
        }

        async function handleData(data) {
            let detail_button = `
                <a class="p-1 btn detail-data action_button" data-toggle="modal" data-target=".kasirDetailModal"
                    onclick="openDetailKasir('${data.id}')">
                    <span class="text-dark">Detail</span>
                    <div class="icon text-info">
                        <i class="fa fa-book"></i>
                    </div>
                </a>`;

            let print_button = `
                <a class="p-1 btn detail-data action_button"
                    onclick="cetakStruk('${data.id}')">
                    <span class="text-dark">Cetak</span>
                    <div class="icon text-success">
                        <i class="fa fa-print"></i>
                    </div>
                </a>`;

            let delete_button = `
                <a class="p-1 btn hapus-data action_button"
                    data-container="body" data-toggle="tooltip" data-placement="top"
                    onclick="deleteData('${encodeURIComponent(JSON.stringify(data))}')"
                    title="Hapus ${title}: ${data.nota}"
                    data-id='${data.id}'
                    data-name='${data.nota}'>
                    <span class="text-dark">Hapus</span>
                    <div class="icon text-danger">
                        <i class="fa fa-trash"></i>
                    </div>
                </a>`;

            let infoText = 'Dibuat oleh:';
            let infoUser = `${data.created_by || '-'}`;
            let infoTime = `${data.created_at || '-'}`;

            const info = `
            <div>
                <small class="text-muted">${infoText}</small>
                <small class="text-bold">${infoUser}</small>
            </div>`;

            return {
                id: data?.id ?? '-',
                nota: data?.nota ?? '-',
                tanggal: data?.tanggal ?? '-',
                qty: data?.qty ?? '-',
                nominal: data?.nominal ?? '-',
                created_by: data?.created_by ?? '-',
                detail_button,
                delete_button,
                print_button,
                info
            };
        }

        async function setListData(dataList, pagination, total) {
            totalPage = pagination.total_pages;
            currentPage = pagination.current_page;
            let display_from = ((defaultLimitPage * (currentPage - 1)) + 1);
            let display_to = Math.min(display_from + dataList.length - 1, pagination.total);
            let tdClass = 'text-wrap align-top';
            let getDataTable = `
            <div class="col-12">
                <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover m-0">
                                <thead class="glossy-thead">
                                    <tr>
                                        <th scope="col" class="${tdClass} text-center" style="width:5%">No</th>
                                        <th scope="col" class="${tdClass}" style="width:15%">Tanggal</th>
                                        <th scope="col" class="${tdClass}" style="width:15%">Informasi</th>
                                        <th scope="col" class="${tdClass}" style="width:25%">Nota</th>
                                        <th scope="col" class="${tdClass} text-center" style="width:5%">Qty</th>
                                        <th scope="col" class="${tdClass} text-right" style="width:10%">Nominal</th>
                                        <th scope="col" class="${tdClass} text-center" style="width:25%">Aksi</th>
                                    </tr>
                                </thead>
                                <thead>
                                    <tr>
                                        <th colspan="4" class="${tdClass} text-right"></th>
                                        <th class="${tdClass} text-center"><span class="badge badge-primary">${total.qty || 0}</span></th>
                                        <th class="${tdClass} text-right"><span class="badge badge-primary">${total.nominal || 0}</span></th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>`;

            dataList.forEach((element, index) => {
                const number = display_from + index;
                const hasButtons = element.detail_button || element.print_button || element.delete_button;
                const actionHTML = `
                    <div class="d-flex justify-content-center flex-column flex-sm-row align-items-center align-items-sm-start mx-3" style="gap: 0.5rem;">
                        ${hasButtons
                            ? `
                                ${element.print_button || ''}
                                ${element.detail_button || ''}
                                ${element.delete_button || ''}
                               `
                            : `<i class="text-muted">Tidak ada aksi</span>`
                        }
                    </div>
                `;

                getDataTable += `
                    <tr class="glossy-tr">
                        <td class="${tdClass} text-center">${number}</td>
                        <td class="${tdClass}">${element.tanggal}</td>
                        <td class="${tdClass}">${element.info}</td>
                        <td class="${tdClass}">${element.nota}</td>
                        <td class="${tdClass} text-center">${element.qty}</td>
                        <td class="${tdClass} text-right">${element.nominal}</td>
                        <td class="${tdClass}">${actionHTML}</td>
                    </tr>
                `;
            });

            getDataTable += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>`;

            $('#listData').html(getDataTable);
            $('#totalPage').text(pagination.total);
            $('#countPage').text(`${display_from} - ${display_to}`);
            $('[data-toggle="tooltip"]').tooltip();
            renderPagination();
        }

        async function deleteData(encodedData) {
            let data = JSON.parse(decodeURIComponent(encodedData));

            swal({
                title: `Hapus ${title}`,
                html: `
                    <p class="font-weight-bold">Transaksi ${data.nota} akan dihapus!</p>
                    <hr>
                    <div class="px-4" style="gap: 0.5rem;">
                        <div class="form-group">
                            <label for="input-pin" class="form-control-label d-flex justify-content-start">PIN<span class="text-danger ml-1">*</span></label>
                            <input type="text" id="input-pin" class="swal-content__input form-control mb-2" placeholder="Masukkan PIN Toko">
                        </div>
                        <div class="form-group">
                            <label for="input-message" class="form-control-label d-flex justify-content-start">Pesan<span class="text-danger ml-1">*</span></label>
                            <textarea id="input-message" class="swal-content__input form-control mb-2" placeholder="Masukkan Pesan" rows="4"></textarea>
                        </div>
                    </div>
                `,
                type: "question",
                showCancelButton: true,
                confirmButtonText: "Konfirmasi",
                cancelButtonText: "Batal",
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                reverseButtons: true,
                confirmButtonClass: "btn btn-danger",
                cancelButtonClass: "btn btn-secondary"
            }).then(async (result) => {
                const pin = document.getElementById('input-pin')?.value;
                const message = document.getElementById('input-message')?.value;

                if (!pin) {
                    notificationAlert("error", "Gagal", "PIN tidak boleh kosong!");
                    return;
                }
                if (!message) {
                    notificationAlert("error", "Gagal", "Pesan tidak boleh kosong!");
                    return;
                }

                let postDataRest = await renderAPI(
                        'DELETE',
                        '{{ route('tb.kasir.delete') }}', {
                            public_id: data.id,
                            deleted_by: {{ auth()->user()->id }},
                            toko_id: {{ auth()->user()->toko_id }},
                            message: message,
                            pin: pin
                        }
                    ).then(response => response)
                    .catch(error => error.response);

                swal.close();

                if (postDataRest.status == 200) {
                    setTimeout(() => {
                        getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch,
                            customFilter);
                    }, 500);
                    notificationAlert('success', 'Pemberitahuan', postDataRest.data.message);
                } else {
                    notificationAlert('error', 'Gagal', postDataRest.data?.message ||
                        'Terjadi kesalahan.');
                }
            }).catch(swal.noop);
        }

        async function openDetailKasir(id) {
            $('#kasir-detail-body').html(`
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-3">Memuat data...</p>
                </div>
            `);

            try {
                let res = await renderAPI(
                    'GET',
                    '{{ route('tb.kasir.detail') }}', {
                        id: id
                    }
                );
                let result = res?.data?.data ?? {};

                if (result.kasir) {
                    const ksr = result.kasir;
                    const detail = result.detail_kasir;
                    const grouped = result.grouped_details;

                    $('#kasir-detail-body').html(generateKasirDetailHTML(ksr, detail, grouped));
                    document.querySelectorAll('.copy-btn').forEach(function(button) {
                        button.addEventListener('click', function() {
                            const targetId = this.getAttribute('data-target');
                            const textToCopy = document.getElementById(targetId).innerText;

                            navigator.clipboard.writeText(textToCopy).then(function() {
                                notyf.success('QR Code berhasil disalin');
                            }).catch(function(err) {
                                notyf.error('Gagal menyalin QR Code');
                            });
                        });
                    });
                } else {
                    $('#kasir-detail-body').html(`<p class="text-danger text-center">Data tidak ditemukan.</p>`);
                }
            } catch (err) {
                $('#kasir-detail-body').html(`<p class="text-danger text-center">Gagal mengambil data.</p>`);
            }
        }

        function generateKasirDetailHTML(ksr, detail_kasir, grouped_details) {
            return `
        <div class="row">
            ${generateKasirLeftColumn(ksr, detail_kasir)}
            ${generateKasirRightColumn(ksr, grouped_details)}
        </div>
    `;
        }

        function generateKasirLeftColumn(ksr, detail_kasir) {
            const noNota = ksr.nota ?? '-';
            const tanggal = ksr.tanggal;
            const kasirNama = ksr.users?.nama ?? '-';
            const totalItem = ksr.total_qty ?? 0;
            const totalNilai = ksr.total_nominal ?? 0;
            const totalDiskon = ksr.total_diskon ?? 0;
            const jmlBayar = ksr.total_bayar ?? 0;
            const kembalian = ksr.total_kembalian ?? 0;

            let html = `
    <div class="col-md-7 mb-4">
        <div class="info-wrapper p-3 border rounded bg-light">
                    <div class="row">
    <div class="col-12 col-xl-6 col-lg-12 col-md-12">
        <div class="info-row d-flex">
            <p class="label mr-2">No Nota</p>
            <p class="value">: ${noNota}</p>
        </div>
    </div>

    <div class="col-12 col-xl-6 col-lg-12 col-md-12">
        <div class="info-row d-flex">
            <p class="label mr-2">Tanggal Transaksi</p>
            <p class="value">: ${tanggal}</p>
        </div>
    </div>

    <div class="col-12 col-xl-6 col-lg-12 col-md-12">
        <div class="info-row d-flex">
            <p class="label mr-2">Kasir</p>
            <p class="value">: ${kasirNama}</p>
        </div>
    </div>

    <div class="col-12 col-xl-6 col-lg-12 col-md-12">
        <div class="info-row d-flex">
            <p class="label mr-2">Total Qty</p>
            <p class="value">: ${totalItem} Qty</p>
        </div>
    </div>

    <div class="col-12 col-xl-6 col-lg-12 col-md-12">
        <div class="info-row d-flex">
            <p class="label mr-2">Nominal Transaksi</p>
            <p class="value">: ${totalNilai}</p>
        </div>
    </div>

    <div class="col-12 col-xl-6 col-lg-12 col-md-12">
        <div class="info-row d-flex">
            <p class="label mr-2">Total Potongan</p>
            <p class="value">: ${totalDiskon}</p>
        </div>
    </div>

    <div class="col-12 col-xl-6 col-lg-12 col-md-12">
        <div class="info-row d-flex">
            <p class="label mr-2">Jumlah Bayar</p>
            <p class="value">: ${jmlBayar}</p>
        </div>
    </div>

    <div class="col-12 col-xl-6 col-lg-12 col-md-12">
        <div class="info-row d-flex">
            <p class="label mr-2">Kembalian</p>
            <p class="value">: ${kembalian}</p>
        </div>
    </div>
</div>


        <div class="table-responsive table-scroll-wrapper mt-3">
            <table class="table table-striped m-0">
                <thead>
                    <tr>
                        <th class="text-center">No</th>
                        <th>Nama Barang</th>
                        <th class="text-center">Qty</th>
                        <th class="text-right">Harga</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
    `;

            detail_kasir.forEach((dtks, idx) => {
                html += `
            <tr>
                <td class="text-center">${idx + 1}</td>
                <td style="
                    max-width: 280px;
                    width: 280px;
                    overflow: hidden;
                    vertical-align: top;
                ">
                    <div style="
                        max-width: 100%;
                        overflow: hidden;
                    ">
                        ${dtks.text ?? '-'}
                    </div>
                </td>
                <td class="text-center">${dtks.qty ?? 0}</td>
                <td class="text-right">${dtks.harga ?? 0}</td>
                <td class="text-right">${dtks.subtotal ?? 0}</td>
            </tr>
        `;
            });

            html += `
                </tbody>
            </table>
        </div>
                </div>
    </div>`;

            return html;
        }

        function generateKasirRightColumn(ksr, grouped_details) {
            const noNota = ksr.nota ?? '-';
            const tanggal = ksr.tanggal;
            const kasirNama = ksr.users?.nama ?? '-';
            const memberNama = ksr.member ?? '-';
            const tokoNama = ksr.toko?.nama ?? '-';
            const tokoAlamat = ksr.toko?.alamat ?? '-';

            const totalNilai = ksr.total_nominal ?? 0;
            const totalDiskon = ksr.total_diskon ?? 0;
            const total = ksr.total ?? 0;
            const jmlBayar = ksr.total_bayar ?? 0;
            const kembalian = ksr.total_kembalian ?? 0;

            let html = `
            <div class="col-md-5 bg-light p-3">
                <button class="btn btn-primary btn-sm mb-3 w-100" onclick="cetakStruk('${ksr.public_id}')">
                    <i class="fa fa-print mr-2"></i>Cetak Struk
                </button>

                <div class="card text-center p-0">
                                <div class="card-header p-2">
                                    <h5 class="card-subtitle">${tokoNama}</h5>
                                    <div><span class="card-text">${tokoAlamat}</span></div>
                                </div>
                                <div class="card-body p-1">
                                    <div class="info-wrapper">
                                        <div class="info-wrapper">
                                            <div class="info-row">
                                                <p class="label text-left">No Nota</p>
                                                <p class="value">: ${noNota}</p>
                                            </div>
                                            <div class="info-row">
                                                <p class="label text-left">Tgl Transaksi</p>
                                                <p class="value">: ${tanggal}</p>
                                            </div>
                                            <div class="info-row">
                                                <p class="label text-left">Member</p>
                                                <p class="value">: ${memberNama}</p>
                                            </div>
                                            <div class="info-row">
                                                <p class="label text-left">Kasir</p>
                                                <p class="value">: ${kasirNama}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                <table class="table table-borderless mb-2">
                <thead>
                                    <tr>
                                        <td class="narrow-column align-top font-weight-bold text-center">No</td>
                                        <td class="wide-column align-top font-weight-bold">Barang</td>
                                        <td class="price-column align-top font-weight-bold">Potongan</td>
                                        <td class="price-column align-top font-weight-bold">Harga</td>
                                    </tr>
                                </thead>
                    <tbody>
            `;

            grouped_details.forEach((item, idx) => {
                html += `
                <tr>
                    <td class="narrow-column align-top text-center">${idx + 1}.</td>
                    <td colspan="3" class="wide-column align-top"><b>${item.nama_barang}</b></td>
                </tr>
                <tr>
                    <td colspan="1"></td>
                    <td class="wide-column align-top">${item.qty} pcs @ ${item.harga}</td>
                    <td class="price-column align-top">${item.diskon}</td>
                    <td class="price-column align-top"><b>${item.total_harga}</b></td>
                </tr>
            `;
            });

            html += `
                    </tbody>
                    <tfoot>
                        <tr><td colspan="3">Total Harga</td><td class="text-right">${totalNilai}</td></tr>
                        <tr><td colspan="3">Total Diskon</td><td class="text-right">${totalDiskon}</td></tr>
                        <tr class="bg-light"><td colspan="3"><b>Total</b></td><td class="text-right"><b>${total}</b></td></tr>
                        <tr class="bg-success text-white"><td colspan="3">Dibayar</td><td class="text-right">${jmlBayar}</td></tr>
                        ${kembalian != 0 ? `
                                                                        <tr class="bg-info text-white"><td colspan="3">Kembalian</td><td class="text-right">${kembalian}</td></tr>` : ''}
                    </tfoot>
                </table>

                <p class="text-center">Terima Kasih</p>
                <button class="btn btn-primary btn-sm mb-3 w-100" onclick="cetakStruk('${ksr.public_id}')">
                    <i class="fa fa-print mr-2"></i>Cetak Struk
                </button>
            </div>`;

            return html;
        }

        async function pengembalianData(id, encodedBarang, jumlahItem, idKasir) {
            const barang = decodeURIComponent(encodedBarang);

            swal({
                title: `Pengembalian Barang`,
                text: `${barang}`,
                type: "warning",
                showCancelButton: true,
                confirmButtonText: "Ya, Konfirmasi!",
                cancelButtonText: "Tidak, Batal!",
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                reverseButtons: true,
                confirmButtonClass: "btn btn-danger",
                cancelButtonClass: "btn btn-secondary",
            }).then(async (result) => {
                let postDataRest = await renderAPI(
                    'DELETE',
                    `{{ route('pengembalian.delete') }}`, {
                        id: id,
                        id_toko: {{ auth()->user()->toko_id }}
                    }
                ).then(function(response) {
                    return response;
                }).catch(function(error) {
                    return error.response;
                });

                if (postDataRest.status == 200) {
                    notificationAlert('success', 'Pemberitahuan', postDataRest.data.message);

                    if (jumlahItem <= 1) {
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        setTimeout(() => openDetailKasir(idKasir), 1000);
                    }
                }

            }).catch(swal.noop);
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

        async function cetakStruk(kasir_id) {
            try {
                const url = `{{ route('tb.kasir.print') }}`;

                const res = await renderAPI('GET', url, {
                    id: kasir_id
                });

                const data = res?.data?.data;
                if (!data || !data.detail) {
                    notificationAlert('warning', 'Info', 'Data tidak ditemukan untuk dicetak.');
                    return;
                }

                /* ===============================
                 * DETAIL BARANG
                 * =============================== */
                const detailRows = data.detail.map(d => `
                    <tr>
                        <td colspan="4" class="align-top text-left">${d.nama_barang}</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="align-top text-left">
                            ${d.qty} x ${d.harga}
                        </td>
                        <td colspan="2" class="align-top text-right">
                            ${d.total_harga}
                        </td>
                    </tr>
                `).join("");

                const hr = `<hr style="border:none;border-top:1px dashed #000;margin:6px 0;">`;

                const svg = `
                <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="black" viewBox="0 0 24 24">
                    <path d="M2 1h1.219l3.564 14.257A3 3 0 1 0 11 20h4a3 3 0 1 0 3-3H8.78l-.5-2H18c2 0 3-3 3-6s-2-4-4-4H5.78L5.16 2.515A2 2 0 0 0 3.22 1H2z"/>
                </svg>`;

                const printContent = `
                <div style="font-family: monospace; width:300px;">
                    <div style="display:flex;justify-content:center;align-items:center;gap:6px;">
                        ${svg}
                        <div style="text-align:center;">
                            <div style="font-size:16px;font-weight:bold;">${data.toko.nama}</div>
                            <div style="font-size:12px;">${data.toko.alamat}</div>
                        </div>
                        ${svg}
                    </div>

                    ${hr}

                    <table style="width:100%;font-size:12px;">
                        <tr><td>No Nota</td><td>:</td><td class="text-right">${data.nota.no_nota}</td></tr>
                        <tr><td>Tanggal</td><td>:</td><td class="text-right">${data.nota.tanggal}</td></tr>
                        <tr><td>Member</td><td>:</td><td class="text-right">${data.nota.member}</td></tr>
                        <tr><td>Kasir</td><td>:</td><td class="text-right">${data.nota.kasir}</td></tr>
                    </table>

                    ${hr}

                    <table style="width:100%;font-size:13px;">
                        ${detailRows}
                    </table>

                    ${hr}

                    <table style="width:100%;font-size:13px;">
                        <tr><td>Total</td><td>:</td><td class="text-right">${data.total.total_harga}</td></tr>
                        <tr><td>Potongan</td><td>:</td><td class="text-right">${data.total.total_potongan}</td></tr>
                        <tr style="font-weight:bold;">
                            <td>Total Bayar</td><td>:</td>
                            <td class="text-right">${data.total.total_bayar}</td>
                        </tr>
                        <tr><td>Dibayar</td><td>:</td><td class="text-right">${data.total.dibayar}</td></tr>
                        <tr><td>Kembali</td><td>:</td><td class="text-right">${data.total.kembalian}</td></tr>

                        ${
                            data.total.sisa_pembayaran !== 'Rp 0'
                                ? `<tr><td>Sisa</td><td>:</td><td class="text-right">${data.total.sisa_pembayaran}</td></tr>`
                                : ''
                        }
                    </table>

                    ${hr}
                    <p style="text-align:center;">${data.footer}</p>
                </div>
                `;

                const w = window.open("", "_blank", "width=400,height=600");
                w.document.write(`
                    <html>
                    <head>
                        <title>Print Struk</title>
                        <style>
                            body { font-family: monospace; padding:10px; }
                            table { width:100%; border-collapse:collapse; }
                            td { padding:3px 0; }
                            .text-right { text-align:right; }
                        </style>
                    </head>
                    <body onload="window.print(); window.close();">
                        ${printContent}
                    </body>
                    </html>
                `);
                w.document.close();

            } catch (err) {
                console.error(err);
                notificationAlert('error', 'Error', 'Gagal mencetak struk.');
            }
        }

        function openAddModal() {
            renderModalForm('add');

            $('#submit-button')
                .removeClass('btn-primary btn-info d-none')
                .addClass('btn-success')
                .attr("type", "submit")
                .attr("form", "form-data")
                .prop('disabled', false)
                .html('<i class="fa fa-save mr-1"></i>Simpan')
                .off("click");

            setDatePicker();
            addRowItem();

            $('#modal-form').modal('show');
            setTimeout(() => {
                $('#member_id').val('guest').trigger('change');
            }, 100);
            toggleMemberSelect();
        }

        function addRowItem() {
            $(document).off("keydown", "#scan_batch_input");
            $(document).off("change", "#select_batch_manual");
            $(document).off("click", ".remove-item");
            const debouncedQtyValidation = debounce(function() {
                validateQtyInput(this);
            }, 600);

            let allowSubmit = false;

            $('#submit-button').on('click', function() {
                allowSubmit = true;
            });

            $('#form-data').on('submit', function(e) {
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
                toggleMemberSelect();
            });

            $(document).on("change", "#select_batch_manual", async function() {
                let batchId = $(this).val();
                if (!batchId) return;

                await handleRow(batchId);

                $(this).val("").trigger("change.select2");
                toggleMemberSelect();
            });

            $(document).on("keydown", "#form-data", function(e) {
                if (e.key === "Enter" && e.target.id !== "scan_batch_input") {
                    e.preventDefault();
                }
            });

            $(document).on('input', '.qty_send', function() {
                debouncedQtyValidation.call(this);
                hitungSubtotal();
            });

            $(document).on('blur', '.qty_send', function() {
                validateQtyInput(this);
                hitungSubtotal();
            });

            $(document).on('change', '.qty_send', function() {
                validateQtyInput(this);
                hitungSubtotal();
            });

            $(document).on('change', '.harga_select', function() {
                hitungTotalRow($(this).closest('tr'));
            });

            $(document).on('click', '.remove-item', function() {
                $(this).closest('tr').remove();
                updateNomorUrut();

                if ($("#tableItems tbody tr").length === 0) {
                    showEmptyMessage();
                }
                toggleMemberSelect();
                hitungSubtotal();
            });

            $(document).on('input', '#total_bayar', function() {
                hitungKembalian();
            });
        }

        function debounce(fn, delay = 500) {
            let timer;
            return function(...args) {
                clearTimeout(timer);
                timer = setTimeout(() => fn.apply(this, args), delay);
            };
        }

        function validateQtyInput(input) {
            const $input = $(input);

            let max = parseInt(
                $input.data("max") ?? $input.attr("max"),
                10
            );

            let val = parseInt($input.val(), 10);

            if (isNaN(max)) {
                console.warn("MAX QTY TIDAK VALID", input);
                return;
            }

            if (isNaN(val) || val < 1) {
                $input.val(1);
            } else if (val > max) {
                $input.val(max);
                showScanInfo(`⚠️ Maksimal qty ${max}`, "text-warning");
            }

            hitungTotalRow($input.closest('tr'));
        }

        async function handleRow(search) {
            if ($('#member_id').val() == null) {
                showScanInfo("❌ Silahkan Pilih Member", "text-danger");
                return;
            }
            try {
                let res = await renderAPI("GET", '{{ route('sb.batch.getHargaJual') }}', {
                    search: search,
                    toko_id: {{ auth()->user()->toko_id }},
                    member_id: $('#member_id').val()
                });

                if (!res.data || !res.data.data) {
                    showScanInfo("❌ Batch tidak ditemukan", "text-danger");
                    return;
                }

                let data = res.data.data;
                let maxQty = parseInt(data.qty);

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
            } catch {
                showScanInfo("⚠️ Error mencari batch", "text-warning");
            }
        }

        function addRow(data, maxQty) {
            const tbody = $("#tableItems tbody");

            let existingRow = tbody.find("tr").filter(function() {
                return $(this).find(".stock_batch_id").val() == data.id;
            });

            if (existingRow.length) {
                const qtyInput = existingRow.find(".qty_send");
                let currentQty = parseInt(qtyInput.val(), 10) || 0;

                if (currentQty >= maxQty) {
                    showScanInfo(`⚠️ Qty sudah maksimal (${maxQty})`, "text-warning");
                    qtyInput.val(maxQty);
                    return;
                }

                qtyInput.val(currentQty + 1);
                hitungTotalRow(existingRow);
                showScanInfo(
                    `✅ Qty ditambah (${currentQty + 1}/${maxQty})`,
                    "text-success"
                );
                return;
            }

            if (maxQty <= 0) {
                showScanInfo("⚠️ Stok sudah habis", "text-warning");
                return;
            }

            tbody.find(".empty-row").remove();

            let hargaOptions = data.is_member_price.map(h =>
                `<option value="${h.id}">${h.text}</option>`
            ).join('');

            let row = `
                <tr class="glossy-tr" data-id="${data.id}">
                    <td class="text-center no-urut"></td>
                    <td>${data.text}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-danger btn-sm remove-item">
                            <i class="fa fa-trash-alt"></i>
                        </button>
                    </td>
                    <td width="90">
                        <input
                            type="number"
                            class="form-control qty_send"
                            min="1"
                            max="${maxQty}"
                            data-max="${maxQty}"
                            value="1"
                        >
                    </td>
                    <td width="160">
                        <select class="form-control harga_select">
                            ${hargaOptions}
                        </select>
                    </td>
                    <td class="text-right total_harga">
                        ${data.format_harga}
                    </td>
                </tr>
            `;

            tbody.append(row);

            const newRow = tbody.find("tr").last();
            hitungTotalRow(newRow);
            updateNomorUrut();

            showScanInfo("✅ Item ditambahkan", "text-success");
        }

        function hitungTotalRow(row) {
            const qty = parseInt(row.find('.qty_send').val(), 10) || 0;
            const harga = parseInt(
                row.find('.harga_select option:selected').val(),
                10
            ) || 0;

            const total = qty * harga;

            row.find('.total_harga').text(formatRupiah(total));
            hitungSubtotal();
        }

        function updateNomorUrut() {
            $("#tableItems tbody tr").each(function(i) {
                $(this).find('.no-urut').text(i + 1);
            });
        }

        async function renderModalForm(mode = 'add', encodedData = '') {
            let data = {};

            if (encodedData && typeof encodedData === 'string' && encodedData.trim() !== '') {
                try {
                    data = JSON.parse(decodeURIComponent(encodedData));
                } catch (err) {
                    notificationAlert('error', 'Error', 'Terjadi kesalahan saat membaca data enkripsi.');
                }
            }

            const modalTitle = mode === 'edit' ?
                `<i class="fa fa-edit mr-1"></i>Edit ${title}` :
                `<i class="fa fa-circle-plus mr-1"></i>Form ${title}`;

            $('#modalLabel').html(modalTitle);

            const tdClass = 'text-wrap align-top';
            const formContent = `
            <form id="form-data">
                <style>
                    #form-data .select2-container{width:100% !important; max-width:100%;}
                    #form-data .select2-selection{min-height:35px;}
                    #form-data .select2-selection__rendered{line-height:33px;}
                    #form-data .select2-selection__arrow{height:33px;}
                    #btnAddItem{white-space:nowrap; height:35px; line-height:1;}
                    .table-responsive{overflow-x:auto;}
                    #tableItems{min-width: 900px;}
                    @media (max-width: 576px){
                        #tableItems{min-width: 800px;}
                    }
                </style>

                <div class="form-group">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <label for="member_id"><i class="fa fa-user mr-1"></i>Member <sup class="text-danger">*</sup></label>
                                <select class="form-control" id="member_id" name="member_id">
                                    <option value="guest">Guest</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="metode"><i class="fa fa-layer-group mr-1"></i>Metode <sup class="text-danger">*</sup></label>
                                <select class="form-control" id="metode" name="metode">
                                    <option value="cash">Tunai</option>
                                    <option value="cashless">Non Tunai</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label for="tanggal"><i class="fa fa-calendar-day mr-1"></i>Tanggal <sup class="text-danger">*</sup></label>
                                <input type="search" class="form-control" id="tanggal" name="tanggal"
                                    placeholder="Masukkan Tanggal" value="">
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label for="scan_batch_input"><i class="fa fa-qrcode mr-1"></i>QR Code Barang <sup class="text-danger">*</sup></label>
                                <input type="search" class="form-control" id="scan_batch_input" name="scan_batch_input"
                                    placeholder="Scan QR / input QR lalu Enter" value="">
                            </div>
                        </div>
                        <div class="col-md-2 d-flex flex-column align-items-center justify-content-center text-center">
                            <span class="font-weight-bold">Atau</span>
                            <small id="scan-info" class="text-muted mt-1 invisible">
                                placeholder
                            </small>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label for="select_batch_manual"><i class="fa fa-box mr-1"></i>Nama Barang</label>
                                <select class="form-control select2 flex-grow-1" id="select_batch_manual" name="select_batch_manual"></select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover m-0" id="tableItems">
                                <thead class="glossy-thead">
                                    <tr>
                                        <th class="${tdClass} text-center" style="width:5%">No</th>
                                        <th class="${tdClass}" style="width:40%">Item</th>
                                        <th class="${tdClass} text-center" style="width:5%">Aksi</th>
                                        <th class="${tdClass}" style="width:10%">Qty</th>
                                        <th class="${tdClass}" style="width:15%">Harga</th>
                                        <th class="${tdClass} text-right" style="width:15%">Total Harga</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="empty-row">
                                        <td class="${tdClass} text-center" colspan="6">
                                            <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
                                                <div class="text-center my-3" role="alert">
                                                    <i class="fa fa-circle-info mr-1"></i>Silahkan Tambahkan Item Terlebih Dahulu.
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="5" class="text-right font-weight-bold">SubTotal:</td>
                                        <td id="total_harga" class="text-right font-weight-bold">Rp 0</td>
                                    </tr>
                                    <tr>
                                        <td colspan="5" class="text-right font-weight-bold">Total Bayar:</td>
                                        <td colspan="1">
                                            <input type="number" id="total_bayar" class="form-control" inputmode="numeric" value="0" placeholder="Masukkan nominal">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="text-loeft text-muted"><sup class="text-danger mr-1">**</sup>Pastikan kembali data yang akan disimpan dengan benar.</td>
                                        <td colspan="1" class="text-right font-weight-bold">Kembalian:</td>
                                        <td colspan="1" id="kembalian" class="text-right font-weight-bold">Rp 0</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </form>`;

            $('#modal-data').html(formContent);

            await selectData(selectOptions);
        }

        function showEmptyMessage() {
            $("#tableItems tbody").html(`
            <tr class="empty-row">
                <td colspan="6" class="text-wrap align-top text-center">
                    <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
                        <div class="text-center my-3" role="alert">
                            <i class="fa fa-circle-info mr-1"></i>
                            Silahkan Tambahkan Item Terlebih Dahulu.
                        </div>
                    </div>
                </td>
            </tr>
            `);
        }

        function hitungSubtotal() {
            let subtotal = 0;

            $('#tableItems tbody tr').each(function() {
                if ($(this).hasClass('empty-row')) return;

                const totalText = $(this).find('.total_harga').text();
                const total = parseInt(totalText.replace(/\D/g, ''), 10) || 0;
                subtotal += total;
            });

            $('#total_harga').text(formatRupiah(subtotal));

            setDefaultTotalBayar(subtotal);
            hitungKembalian();
        }

        function setDefaultTotalBayar(subtotal) {
            const inputBayar = $('#total_bayar');

            inputBayar.val(subtotal);
        }

        function hitungKembalian() {
            const subtotal = parseInt($('#total_harga').text().replace(/\D/g, ''), 10) || 0;
            const bayar = parseInt($('#total_bayar').val(), 10) || 0;

            const kembalian = bayar - subtotal;

            $('#kembalian').text(
                formatRupiah(kembalian > 0 ? kembalian : 0)
            );
        }

        function toggleMemberSelect() {
            const hasItem =
                $("#tableItems tbody tr")
                .not(".empty-row")
                .length > 0;

            $("#member_id").prop("disabled", hasItem);
        }

        function submitForm() {
            $(document).off("click", "#submit-button").on("click", "#submit-button", async function(e) {
                e.preventDefault();

                const $submitButton = $("#submit-button");
                const originalHTML = $submitButton.html();
                let isSuccess = false;

                $submitButton.prop("disabled", true)
                    .html(`<i class="fas fa-spinner fa-spin"></i> Menyimpan...`);

                loadingPage(true);

                try {
                    let totalQty = 0;
                    let totalNominal = 0;

                    let details = [];

                    $("#tableItems tbody tr").each(function() {
                        const row = $(this);

                        if (row.hasClass("empty-row")) return;

                        const qty = parseInt(row.find(".qty_send").val()) || 0;
                        const harga = parseFloat(
                            row.find(".harga_select option:selected").val()
                        ) || 0;

                        const nominal = qty * harga;

                        totalQty += qty;
                        totalNominal += nominal;

                        details.push({
                            stock_barang_batch_id: row.data("id"),
                            qty: qty,
                            nominal: harga,
                        });
                    });

                    if (details.length === 0) {
                        notificationAlert("warning", "Peringatan", "Item belum ditambahkan");
                        return;
                    }

                    const totalBayar = parseFloat($("#total_bayar").val()) || 0;

                    const formData = {
                        toko_id: {{ auth()->user()->toko_id }},
                        created_by: {{ auth()->user()->id }},
                        member_id: $("#member_id").val(),
                        metode: $("#metode").val(),
                        tanggal: $("#tanggal").val(),
                        total_qty: totalQty,
                        total_nominal: totalNominal,
                        total_bayar: totalBayar,
                        details: details
                    };

                    const postData = await renderAPI("POST", '{{ route('tb.kasir.post') }}', formData);

                    loadingPage(false);

                    if (postData.status >= 200 && postData.status < 300) {
                        isSuccess = true;

                        const kasirPublicId = postData.data?.data?.public_id;

                        notificationAlert(
                            "success",
                            "Berhasil",
                            postData.data.message || "Transaksi berhasil"
                        );

                        if (kasirPublicId) {
                            $submitButton
                                .removeClass("btn-success")
                                .addClass("btn-info")
                                .prop("disabled", false)
                                .attr("type", "button") // 🔥 INI PENTING
                                .removeAttr("form") // 🔥 ini juga
                                .html('<i class="fa fa-print mr-1"></i> Cetak Struk')
                                .off("click")
                                .on("click", function(e) {
                                    e.preventDefault();
                                    e.stopImmediatePropagation();
                                    cetakStruk(`${kasirPublicId}`);
                                });
                        }

                        setTimeout(async () => {
                            await getListData(
                                defaultLimitPage,
                                currentPage,
                                defaultAscending,
                                defaultSearch,
                                customFilter
                            );
                        }, 500);
                    } else {
                        notificationAlert("info", "Pemberitahuan", postData.data.message ||
                            "Terjadi kesalahan");
                    }

                } catch (error) {
                    loadingPage(false);
                    const resp = error.response?.data || {};
                    notificationAlert("error", "Kesalahan", resp.message || "Terjadi kesalahan");
                } finally {
                    if (!isSuccess) {
                        $submitButton
                            .prop("disabled", false)
                            .html(originalHTML);
                    }
                }
            });
        }

        async function initPageLoad() {
            await Promise.all([
                getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch, customFilter),
                searchList(),
                filterList(),
                selectData(selectOptions),
                submitForm()
            ]);
        }
    </script>
@endsection
