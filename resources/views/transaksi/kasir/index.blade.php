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
                    <button type="submit" class="btn btn-success" id="save-btn" form="form-data">Simpan</button>
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
                    id_toko: {{ auth()->user()->toko_id }},
                },
                isUrl: '{{ route('master.member') }}',
                placeholder: 'Pilih Member',
                isModal: '#modal-form',
            }, {
                id: '#barang',
                isFilter: {
                    is_name: 1,
                    id_toko: '{{ auth()->user()->toko_id }}',
                },
                isUrl: '{{ route('master.qrbarcode') }}',
                placeholder: 'Pilih Barang',
                isMinimum: 3,
                isModal: '#modal-form',
                isDisabled: false,
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
                                Tidak ada Transaksi hari ini.
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
                    onclick="openDetailKasir(${data.id})">
                    <span class="text-dark">Detail</span>
                    <div class="icon text-info">
                        <i class="fa fa-book"></i>
                    </div>
                </a>`;

            let delete_button = `
                <a class="p-1 btn hapus-data action_button"
                    data-container="body" data-toggle="tooltip" data-placement="top"
                    title="Hapus ${title}: ${data.nota}"
                    data-id='${data.id}'
                    data-name='${data.nota}'>
                    <span class="text-dark">Hapus</span>
                    <div class="icon text-danger">
                        <i class="fa fa-trash"></i>
                    </div>
                </a>`;

            let action_buttons = '';
            if (detail_button || delete_button) {
                action_buttons = `
                <div class="d-flex justify-content-end">
                    ${detail_button ? `<div class="hovering p-1">${detail_button}</div>` : ''}
                </div>`;
            } else {
                action_buttons = `
                <span class="badge badge-danger">Tidak Ada Aksi</span>`;
            }

            return {
                id: data?.id ?? '-',
                nota: data?.nota ?? '-',
                tanggal: data?.tanggal ?? '-',
                qty: data?.qty ?? '-',
                nominal: data?.nominal ?? '-',
                created_by: data?.created_by ?? '-',
                action_buttons,
            };
        }

        async function setListData(dataList, pagination, total) {
            totalPage = pagination.total_pages;
            currentPage = pagination.current_page;
            let display_from = ((defaultLimitPage * (currentPage - 1)) + 1);
            let display_to = Math.min(display_from + dataList.length - 1, pagination.total);

            let getDataTable = '';
            let classCol = 'align-center text-dark text-wrap';
            dataList.forEach((element, index) => {
                getDataTable += `
                            <tr class="text-dark clickable-row" data-id="${element.id}" data-toggle="modal" data-target=".kasirDetailModal">
                                <td class="${classCol} text-center">${display_from + index}.</td>
                                <td class="${classCol}">${element.nota}</td>
                                <td class="${classCol}">${element.tanggal}</td>
                                <td class="${classCol}">${element.qty}</td>
                                <td class="${classCol} text-right">${element.nominal}</td>
                                <td class="${classCol} text-right">${element.metode}</td>
                                <td class="${classCol} text-right">${element.created_by}</td>
                                <td class="${classCol} text-right">${element.action_buttons}</td>
                            </tr>`;
            });

            let totalRow = `
            <tr class="bg-primary">
                <td class="${classCol}" colspan="3"></td>
                <td class="${classCol}" style="font-size: 1rem;"><strong class="text-white fw-bold">Total</strong></td>
                <td class="${classCol} text-right"><strong class="text-white" id="totalData">${total}</strong></td>
                <td class="${classCol}" colspan="4"></td>
            </tr>`;

            $('#listData').html(getDataTable);
            $('#listData').closest('table').find('tfoot').html(totalRow);

            $('#totalPage').text(pagination.total);
            $('#countPage').text(`${display_from} - ${display_to}`);
            $('[data-toggle="tooltip"]').tooltip();
            renderPagination();

            $(document).off('mouseup.clickable-row').on('mouseup.clickable-row', '.clickable-row', function(e) {
                if ($(e.target).closest('.action_button').length > 0) return;

                const selection = window.getSelection();
                if (selection && selection.toString().trim().length > 0) return;

                const id = $(this).data('id');
                if (id) {
                    openDetailKasir(id);
                }
            });
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
            const noNota = ksr.no_nota ?? '-';
            const noNotaFormatted = noNota.length >= 12 ?
                `${noNota.slice(0, 6)}-${noNota.slice(6, 12)}-${noNota.slice(12)}` :
                noNota;

            const createdAt = ksr.formatted_created_at ?? '-';
            const kasirNama = ksr.users?.nama ?? '-';
            const totalItem = ksr.total_item ?? 0;
            const totalNilai = formatRupiah(ksr.total_nilai ?? 0);
            const totalDiskon = formatRupiah(ksr.total_diskon ?? 0);
            const jmlBayar = formatRupiah(ksr.jml_bayar ?? 0);
            const kembalian = formatRupiah(ksr.kembalian ?? 0);
            const memberNama = ksr.member?.nama_member ?? 'Guest';
            const tokoNama = ksr.toko?.nama_toko ?? '-';
            const tokoAlamat = ksr.toko?.alamat ?? '-';
            const kasbon = ksr.kasbon?.utang ? formatRupiah(ksr.kasbon.utang) : null;

            let html = `
            <div class="row">
                <div class="col-md-7 mb-4">
                    <div class="info-wrapper p-3 border rounded bg-light">
                        <div class="row mb-0 pb-0">
                            <div class="col-md-8">
                                <div class="info-row d-flex mb-2">
                                    <p class="label mr-2"><i class="feather icon-file-text mr-1"></i>No Nota</p>
                                    <p class="value">: ${noNotaFormatted}</p>
                                </div>
                                <div class="info-row d-flex mb-2">
                                    <p class="label mr-2"><i class="feather icon-calendar mr-1"></i>Tanggal Transaksi</p>
                                    <p class="value">: ${createdAt}</p>
                                </div>
                            </div>
                            <div class="col-md-4 text-md-right text-left">
                                <div class="info-row d-flex justify-content-md-end mb-2">
                                    <p class="label mr-2"><i class="feather icon-user mr-1"></i>Kasir</p>
                                    <p class="value">: ${kasirNama}</p>
                                </div>
                            </div>
                        </div>
                        <div class="info-row d-flex mb-2"><p class="label mr-2"><i class="feather icon-layers mr-1"></i>Jumlah Item</p><p class="value">: ${totalItem} Item</p></div>
                        <div class="info-row d-flex mb-2"><p class="label mr-2"><i class="feather icon-credit-card mr-1"></i>Nilai Transaksi</p><p class="value">: ${totalNilai}</p></div>
                        <div class="info-row d-flex mb-2"><p class="label mr-2"><i class="feather icon-percent mr-1"></i>Total Potongan</p><p class="value">: ${totalDiskon}</p></div>
                        <div class="info-row d-flex mb-2"><p class="label mr-2"><i class="feather icon-credit-card mr-1"></i>Jumlah Bayar</p><p class="value">: ${jmlBayar}</p></div>
                        <div class="info-row d-flex mb-2"><p class="label mr-2"><i class="feather icon-corner-down-left mr-1"></i>Kembalian</p><p class="value">: ${kembalian}</p></div>
                    </div>
                    <div class="table-responsive table-scroll-wrapper mt-3">
                        <table class="table table-striped m-0">
                            <thead>
                                <tr>
                                    <th>Nama Barang</th>
                                    <th class="text-right">Retur</th>
                                    <th class="text-right">Qty</th>
                                    <th class="text-right">Harga</th>
                                    <th>QR Code</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>`;

            detail_kasir.forEach(dtks => {
                const namaBarang = dtks.barang?.nama_barang ?? '-';
                const shortNamaBarang = namaBarang.length > 30 ? namaBarang.slice(0, 30) + '...' : namaBarang;
                const harga = formatRupiah(dtks.harga);
                const qty = dtks.qty;
                const qrcode = dtks.qrcode ?? '-';
                const qrcodePath = dtks.qrcode_path ? `/storage/${dtks.qrcode_path}` : '#';
                let download_button = `
                        <a href="${qrcodePath}" download class="action_button btn btn-outline-secondary btn-md" title="Download QR Code" data-container="body" data-toggle="tooltip" data-placement="top">
                            <span class="text-dark">Unduh</span>
                            <div class="icon text-success">
                                <i class="mb-1 fa fa-download"></i>
                            </div>
                        </a>
                    `;
                let delete_button = '';

                if (hasPermission('DELETE /pengembalian/delete')) {
                    delete_button = `
                        <button onClick="pengembalianData(${dtks.id}, '${encodeURIComponent(dtks.barang.nama_barang)}', ${detail_kasir.length}, ${ksr.id})" class="action_button btn btn-outline-secondary btn-md" title="Pengembalian Barang" data-container="body" data-toggle="tooltip" data-placement="top">
                            <span class="text-dark">Hapus</span>
                            <div class="icon text-danger">
                                <i class="mb-1 fa fa-rotate"></i>
                            </div>
                        </button>
                    `;
                }
                const hasButtons = download_button || delete_button;
                const actionHTML = `
                    <div class="d-flex justify-content-center flex-column flex-sm-row align-items-center align-items-sm-start mx-3" style="gap: 0.5rem;">
                        ${download_button}
                        ${hasButtons
                            ? `
                                                                                        ${delete_button || ''}
                                                                                    `
                            : ``
                        }
                    </div>
                `;

                html += `
                <tr>
                    <td class="text-wrap align-top" title="${namaBarang}">${shortNamaBarang}</td>
                    <td class="text-wrap align-top text-right">${dtks.reture_qty ?? 0}</td>
                    <td class="text-wrap align-top text-right">${qty}</td>
                    <td class="text-wrap align-top text-right">${harga}</td>
                    <td class="text-wrap align-top">
                        <div class="d-flex flex-wrap align-items-start justify-content-between">
                            <span id="qrcode-text-${dtks.id}" class="mr-1 text-break">${qrcode}</span>
                            <button class="btn btn-sm btn-outline-primary copy-btn"
                                data-toggle="tooltip"
                                title="Salin: ${qrcode}"
                                data-target="qrcode-text-${dtks.id}">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </td>
                    <td class="text-wrap align-top">
                        ${actionHTML}
                    </td>
                </tr>`;
            });

            html += `</tbody></table></div></div>
                <div class="col-md-5 bg-light p-3">
                    <button class="btn btn-primary btn-sm mb-3 w-100" onclick="cetakStruk(${ksr.id})"><i class="fa fa-print mr-2"></i>Cetak Struk</button>
                    <div class="card text-center p-0">
                        <div class="card-header p-0">
                            <h5 class="card-subtitle">${tokoNama}</h5>
                            <p class="card-text">${tokoAlamat}</p>
                        </div>
                        <div class="card-body p-1">
                            <div class="info-wrapper">
                                <div class="info-wrapper">
                                    <div class="info-row">
                                        <p class="label text-left">No Nota</p>
                                        <p class="value">: ${noNotaFormatted}</p>
                                    </div>
                                    <div class="info-row">
                                        <p class="label text-left">Tgl Transaksi</p>
                                        <p class="value">: ${createdAt}</p>
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
                    <div class="table-responsive-js">
                    <table class="table table-borderless mb-2">
                        <thead>
                            <tr>
                                <td class="narrow-column align-top font-weight-bold">#</td>
                                <td class="wide-column align-top font-weight-bold">Barang</td>
                                <td class="price-column align-top font-weight-bold">Potongan</td>
                                <td class="price-column align-top font-weight-bold">Harga</td>
                            </tr>
                        </thead>
                        <tbody>`;

            grouped_details.forEach((item, idx) => {
                html += `
                    <tr>
                        <td class="narrow-column align-top">${idx + 1}.</td>
                        <td class="wide-column align-top">(${item.nama_barang}) ${item.qty}pcs @ ${formatRupiah(item.harga)}</td>
                        <td class="price-column align-top">${formatRupiah(item.diskon)}</td>
                        <td class="price-column align-top">${formatRupiah(item.total_harga)}</td>
                    </tr>`;
            });

            html +=
                `
                </tbody>
                <tfoot>
                    <tr>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-right">Total Harga</td>
                        <td class="price-column">${totalNilai}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-left">Total Diskon</td>
                        <td class="price-column">${totalDiskon}</td>
                    </tr>
                    <tr class="bg-light">
                        <td colspan="3" class="text-left"><b>Total</b></td>
                        <td class="price-column"><b>${formatRupiah(ksr.total_nilai - ksr.total_diskon)}</b></td>
                    </tr>
                    <tr class="bg-success text-white">
                        <td colspan="3" class="text-left">Dibayar</td>
                        <td class="price-column">${jmlBayar}</td>
                    </tr>`;

            if (ksr.kembalian != 0) {
                html +=
                    `<tr class="bg-info text-white">
                        <td colspan="3" class="text-left">Kembalian</td>
                        <td class="price-column">${kembalian}</td>
                    </tr>`;
            }
            if (kasbon) {
                html +=
                    `<tr class="bg-danger text-white">
                        <td colspan="3" class="text-left">Sisa Pembayaran</td>
                        <td class="price-column">${kasbon}</td>
                    </tr>`;
            }

            html += `</tfoot></table></div>
            <p class="text-center">Terima Kasih</p>
            <hr>
            <button class="btn btn-primary btn-sm mb-3 w-100" onclick="cetakStruk(${ksr.id})"><i class="fa fa-print mr-2"></i>Cetak Struk</button>
            </div>
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

        async function cetakStruk(id_kasir) {
            try {
                const url = `{{ route('tb.kasir.print', ':id_kasir') }}`.replace(':id_kasir', id_kasir);

                const res = await fetch(url);
                if (!res.ok) throw new Error("Gagal ambil data struk");
                const data = await res.json();

                if (!data || !data.detail) {
                    notificationAlert('warning', 'Info', 'Data tidak ditemukan untuk dicetak.');
                    return;
                }

                // Buat detail baris item
                let detailRows = data.detail.map(d => `
                    <tr>
                        <td colspan="4" class="align-top text-left">${d.nama_barang}</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="align-top text-left">${d.qty} x ${d.harga}</td>
                        <td colspan="2" class="align-top text-right">${formatRupiah(d.total_harga)}</td>
                    </tr>
                `).join("");

                const hr = '<hr style="border:none; border-top:1px dashed #000; margin:6px 0;"/>';
                const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="black" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M2 1C1.44772 1 1 1.44772 1 2C1 2.55228 1.44772 3 2 3H3.21922L6.78345 17.2569C5.73276 17.7236 5 18.7762 5 20C5 21.6569 6.34315 23 8 23C9.65685 23 11 21.6569 11 20C11 19.6494 10.9398 19.3128 10.8293 19H15.1707C15.0602 19.3128 15 19.6494 15 20C15 21.6569 16.3431 23 18 23C19.6569 23 21 21.6569 21 20C21 18.3431 19.6569 17 18 17H8.78078L8.28078 15H18C20.0642 15 21.3019 13.6959 21.9887 12.2559C22.6599 10.8487 22.8935 9.16692 22.975 7.94368C23.0884 6.24014 21.6803 5 20.1211 5H5.78078L5.15951 2.51493C4.93692 1.62459 4.13696 1 3.21922 1H2ZM18 13H7.78078L6.28078 7H20.1211C20.6742 7 21.0063 7.40675 20.9794 7.81078C20.9034 8.9522 20.6906 10.3318 20.1836 11.3949C19.6922 12.4251 19.0201 13 18 13ZM18 20.9938C17.4511 20.9938 17.0062 20.5489 17.0062 20C17.0062 19.4511 17.4511 19.0062 18 19.0062C18.5489 19.0062 18.9938 19.4511 18.9938 20C18.9938 20.5489 18.5489 20.9938 18 20.9938ZM7.00617 20C7.00617 20.5489 7.45112 20.9938 8 20.9938C8.54888 20.9938 8.99383 20.5489 8.99383 20C8.99383 19.4511 8.54888 19.0062 8 19.0062C7.45112 19.0062 7.00617 19.4511 7.00617 20Z"/>
                            </svg>`;
                const printContent = `
                    <div style="font-family: monospace; width: 300px; position: relative;">
                        <div style="display:flex; align-items:center; justify-content:center; margin-bottom:6px; position: relative; z-index: 2;">
                            <div style="flex:0 0 auto; display:flex; align-items:center;">
                                ${svg}
                            </div>
                            <div style="flex:1; text-align:center;">
                                <div style="font-size:16px; font-weight:bold;">${data.toko.nama}</div>
                                <div style="font-size:12px; margin-top:2px;">${data.toko.alamat}</div>
                            </div>
                            <div style="flex:0 0 auto; display:flex; align-items:center;">
                                ${svg}
                            </div>
                        </div>
                        ${hr}
                        <table style="width:100%; font-size:12px; table-layout:fixed;">
                            <colgroup>
                                <col style="width:35%;">
                                <col style="width:5%;">
                                <col style="width:60%;">
                            </colgroup>
                            <tbody>
                                <tr>
                                    <td>No Nota</td><td>:</td><td class="text-right">${data.nota.no_nota}</td>
                                </tr>
                                <tr>
                                    <td>Tanggal</td><td>:</td><td class="text-right">${data.nota.tanggal}</td>
                                </tr>
                                <tr>
                                    <td>Member</td><td>:</td><td class="text-right">${data.nota.member}</td>
                                </tr>
                                <tr>
                                    <td>Kasir</td><td>:</td><td class="text-right">${data.nota.kasir}</td>
                                </tr>
                            </tbody>
                        </table>
                        ${hr}
                        <table style="width:100%; font-size:14px;">
                            <tbody>
                                ${detailRows}
                            </tbody>
                        </table>
                        ${hr}
                        <table style="width:100%; font-size:14px; table-layout:fixed;">
                            <colgroup>
                                <col style="width:35%;">
                                <col style="width:5%;">
                                <col style="width:60%;">
                            </colgroup>
                            <tbody>
                                <tr>
                                    <td>Total</td><td>:</td><td class="text-right">${formatRupiah(data.total.total_harga)}</td>
                                </tr>
                                <tr>
                                    <td>Potongan</td><td>:</td><td class="text-right">${formatRupiah(data.total.total_potongan)}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight:bold;">Total Bayar</td><td>:</td>
                                    <td class="text-right" style="font-weight:bold;">${formatRupiah(data.total.total_bayar)}</td>
                                </tr>
                                <tr>
                                    <td>Tunai</td><td>:</td><td class="text-right">${formatRupiah(data.total.dibayar)}</td>
                                </tr>
                                <tr>
                                    <td>Kembali</td><td>:</td><td class="text-right">${formatRupiah(data.total.kembalian)}</td>
                                </tr>
                                ${data.total.sisa_pembayaran && data.total.sisa_pembayaran > 0 ? `
                                                                                                                                        <tr>
                                                                                                                                            <td>Sisa</td><td>:</td><td class="text-right">${formatRupiah(data.total.sisa_pembayaran)}</td>
                                                                                                                                        </tr>` : ""}
                            </tbody>
                        </table>
                        ${hr}
                        <p style="text-align:center;">${data.footer}</p>
                    </div>
                `;

                // Buka jendela print
                let w = window.open("", "_blank", "width=400,height=600");
                w.document.write(`
                    <html>
                    <head>
                        <title>Print Nota</title>
                        <style>
                            body { font-family: monospace; padding:10px; }
                            table { border-collapse: collapse; width:100%; }
                            td, th { padding: 4px; }
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
                console.error("Error cetakStruk:", err);
                notificationAlert('error', 'Error', 'Gagal mencetak struk.');
            }
        }

        function openAddModal() {
            renderModalForm('add');
            $('#save-btn')
                .removeClass('btn-primary d-none')
                .addClass('btn-success')
                .prop('disabled', false)
                .html('<i class="fa fa-save mr-1"></i>Simpan');
            setDatePicker();
            $('#modal-form').modal('show');
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
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tanggal"><i class="fa fa-user mr-1"></i>Member <sup class="text-danger">*</sup></label>
                            <select class="form-control" id="member_id" name="member_id">
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tanggal"><i class="fa fa-calendar-day mr-1"></i>Tanggal <sup class="text-danger">*</sup></label>
                            <input type="datetime-local" class="form-control" id="tanggal" name="tanggal"
                                placeholder="Masukkan tanggal" required value="">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="item_nonfisik"><i class="fa fa-layer-group mr-1"></i>Item</label>
                            <select class="form-control select2 flex-grow-1" id="item_nonfisik" name="item_nonfisik" required></select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="select_batch_manual"><i class="fa fa-box mr-1"></i>Nama Barang</label>
                            <select class="form-control select2 flex-grow-1" id="select_batch_manual" name="select_batch_manual" required></select>
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
                                    <th class="${tdClass} text-center" style="width:5%">Aksi</th>
                                    <th class="${tdClass} text-center" style="width:5%">No</th>
                                    <th class="${tdClass}" style="width:40%">Item</th>
                                    <th class="${tdClass}" style="width:10%">Qty</th>
                                    <th class="${tdClass}" style="width:15%">Harga</th>
                                    <th class="${tdClass} text-right" style="width:15%">Total Harga</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
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

            let itemList = [];
            let lastGrandTotal = 0;
            let totalBayarDebounceTimer = null;

            $('#item_nonfisik').off('select2:select').on('select2:select', function(e) {
                const selectedData = e.params.data;
                if (!selectedData || !selectedData.id) {
                    notificationAlert('warning', 'Peringatan', 'Pilih item terlebih dahulu.');
                    return;
                }

                const selectedId = selectedData.id;
                const selectedText = selectedData.text;

                const existingItem = itemList.find(item => item.id === selectedId);
                if (existingItem) {
                    existingItem.qty += 1;
                } else {
                    itemList.push({
                        id: selectedId,
                        name: selectedText,
                        qty: 1,
                        hpp: null,
                        price: null
                    });
                }

                renderTable();

                $(this).val(null).trigger('change');
            });

            function renderTable() {
                const tbody = $('#tableItems tbody');
                tbody.empty();

                if (itemList.length === 0) {
                    tbody.append(`
                    <tr>
                        <td class="${tdClass} text-center" colspan="8">
                            <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
                                <div class="text-center my-3" role="alert">
                                    <i class="fa fa-circle-info mr-1"></i>Silahkan Tambahkan Item Terlebih Dahulu.
                                </div>
                            </div>
                        </td>
                    </tr>
                `);
                } else {
                    itemList.forEach((item, index) => {
                        const totalHppItem = item.hpp * item.qty;
                        const totalHargaItem = item.price * item.qty;
                        const row = `
                        <tr class="glossy-tr" data-id="${item.id}">
                            <td class="${tdClass} text-center">
                                <button type="button" class="btn btn-outline-danger btn-sm btn-delete-item" title="Hapus">
                                    <i class="fa fa-trash-alt"></i>
                                </button>
                            </td>
                            <td class="${tdClass} text-center">${index + 1}</td>
                            <td class="${tdClass}">${item.name}</td>
                            <td class="${tdClass}">
                                <input type="number" class="form-control qty-input" value="${item.qty}" min="1" placeholder="Qty">
                            </td>
                            <td class="${tdClass}">
                                <input type="number" class="form-control price-input" value="${item.price}" min="0" placeholder="Harga">
                            </td>
                            <td class="${tdClass} td-total-hpp text-right font-weight-bold">Rp ${numberFormat(totalHppItem)}</td>
                            <td class="${tdClass} td-total-harga text-right font-weight-bold">Rp ${numberFormat(totalHargaItem)}</td>
                        </tr>
                    `;
                        tbody.append(row);
                    });
                }

                updateTotals();
            }

            $('#tableItems').off('input change', '.qty-input, .hpp-input, .price-input')
                .on('input change', '.qty-input, .hpp-input, .price-input', function() {
                    const tr = $(this).closest('tr');
                    const id = String(tr.data('id'));
                    let item = itemList.find(i => String(i.id) === id);

                    if (item) {
                        const qtyVal = parseInt(tr.find('.qty-input').val(), 10);
                        const hppVal = parseFloat(tr.find('.hpp-input').val());
                        const priceVal = parseFloat(tr.find('.price-input').val());

                        item.qty = isNaN(qtyVal) || qtyVal < 1 ? 1 : qtyVal;
                        item.hpp = isNaN(hppVal) || hppVal < 0 ? 0 : hppVal;
                        item.price = isNaN(priceVal) || priceVal < 0 ? 0 : priceVal;

                        tr.find('.td-total-hpp').text(`Rp ${numberFormat(item.qty * item.hpp)}`);
                        tr.find('.td-total-harga').text(`Rp ${numberFormat(item.qty * item.price)}`);

                        updateTotals();
                    }
                });

            $('#tableItems').off('click', '.btn-delete-item').on('click', '.btn-delete-item', function() {
                const tr = $(this).closest('tr');
                const id = String(tr.data('id'));
                itemList = itemList.filter(i => String(i.id) !== id);
                renderTable();
            });

            function getTotalHargaSemua() {
                return itemList.reduce((sum, item) => sum + (item.price * item.qty), 0);
            }

            function getTotalHppSemua() {
                return itemList.reduce((sum, item) => sum + (item.hpp * item.qty), 0);
            }

            function updateTotals() {
                const newGrandTotal = getTotalHargaSemua();
                const newGrandHpp = getTotalHppSemua();

                $('#total_harga').text(`Rp ${numberFormat(newGrandTotal)}`);
                $('#total_hpp').text(`Rp ${numberFormat(newGrandHpp)}`);

                let bayarSekarang = parseFloat($('#total_bayar').val()) || 0;

                if (bayarSekarang !== newGrandTotal) {
                    bayarSekarang = newGrandTotal;
                    $('#total_bayar').val(bayarSekarang);
                }

                lastGrandTotal = newGrandTotal;
                updateKembalian();
            }

            $('#total_bayar').on('input', function() {
                clearTimeout(totalBayarDebounceTimer);

                let bayar = parseFloat($('#total_bayar').val()) || 0;
                const total = getTotalHargaSemua();

                updateKembalian();

                // totalBayarDebounceTimer = setTimeout(() => {
                //     if (bayar < total) {
                //         $('#total_bayar').val(total);
                //         updateKembalian();
                //     }
                // }, 3000);
            });

            function updateKembalian() {
                const bayar = parseFloat($('#total_bayar').val()) || 0;
                const total = getTotalHargaSemua();
                const kembalian = bayar - total;

                $('#kembalian').text(`Rp ${numberFormat(kembalian > 0 ? kembalian : 0)}`);
            }

            function numberFormat(num) {
                return (Number(num) || 0).toLocaleString('id-ID');
            }

            await saveData(mode, encodedData);
        }

        async function initPageLoad() {
            await Promise.all([
                getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch, customFilter),
                searchList(),
                filterList(),
                selectData(selectOptions),
            ]);
        }
    </script>
@endsection
