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
                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-header custom-header">
                            <div class="custom-left">
                                <div class="custom-btn-tambah-wrap">
                                    @if (hasAnyPermission(['POST /kasir/store']))
                                        <a id="btn-tambah" class="btn btn-primary custom-btn-tambah text-white"
                                            data-toggle="modal" data-target=".bd-example-modal-lg">
                                            <i class="fa fa-plus-circle"></i> Tambah
                                        </a>
                                    @endif
                                </div>
                                <form id="custom-filter" class="row">
                                    <div class="col-xl-6 col-12">
                                        <input class="form-control w-100" type="text" id="daterange" name="daterange"
                                            placeholder="Pilih rentang tanggal">
                                    </div>
                                    <div class="col-xl-6 col-12">
                                        <div class="row">
                                            <div class="col-6 px-3 px-lg-1">
                                                <button class="btn btn-info w-100" id="tb-filter" type="submit">
                                                    <i class="fa fa-magnifying-glass mr-2"></i>Cari
                                                </button>
                                            </div>
                                            <div class="col-6 px-3 px-lg-1">
                                                <button type="button" class="btn btn-secondary w-100" id="tb-reset">
                                                    <i class="fa fa-rotate mr-2"></i>Reset
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="custom-right">
                                <div class="custom-limit-page">
                                    <select name="limitPage" id="limitPage" class="form-control">
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
                                </div>
                                <div class="custom-search">
                                    <input id="tb-search" class="tb-search form-control" type="search" name="search"
                                        placeholder="Cari Data" aria-label="search">
                                </div>
                            </div>
                        </div>
                        <div class="content">
                            <x-adminlte-alerts />
                            <div class="card-body p-0">
                                <div class="table-responsive table-scroll-wrapper">
                                    <table class="table table-striped m-0">
                                        <thead>
                                            <tr class="tb-head">
                                                <th class="text-center text-wrap align-top">No</th>
                                                <th class="text-wrap align-top">No. Nota</th>
                                                <th class="text-wrap align-top">Tanggal Transaksi</th>
                                                <th class="text-wrap align-top">Member</th>
                                                <th class="text-wrap align-top">Item</th>
                                                <th class="text-wrap align-top text-right">Nilai</th>
                                                <th class="text-wrap align-top text-right">Payment</th>
                                                <th class="text-wrap align-top text-right">Kasir</th>
                                                <th class="text-right text-wrap align-top"><span
                                                        class="mr-2">Action</span></th>
                                            </tr>
                                        </thead>
                                        <tbody id="listData">
                                        </tbody>
                                        <tfoot></tfoot>
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

    <div id="modal-form" class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog"
        aria-labelledby="myLargeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="myLargeModalLabel">Form Transaksi Kasir</h6>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form>
                    <div class="modal-body">
                        <div class="container-fluid">
                            <div class="row">
                                <div class="col-md-6 mb-2 d-flex align-items-center">
                                    <i class="icon feather icon-file-text mr-1"></i>
                                    <div class="d-flex justify-content-between w-100">
                                        <span>No Nota:</span>
                                        <span id="noNota" name="no_nota"></span>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-2 d-flex align-items-center">
                                    <i class="icon feather icon-airplay mr-1"></i>
                                    <div class="d-flex justify-content-between w-100">
                                        <span>Nama Toko:</span>
                                        @if (Auth::check())
                                            <span class="badge badge-info">{{ Auth::user()->toko->nama_toko }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-2 d-flex align-items-center">
                                    <i class="icon feather icon-calendar mr-1"></i>
                                    <div class="d-flex justify-content-between w-100">
                                        <span>Tanggal Transaksi:</span>
                                        <span id="tglTransaksi" name="tgl_transaksi"></span>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-2 d-flex align-items-center">
                                    <i class="icon feather icon-user mr-1"></i>
                                    <div class="d-flex justify-content-between w-100">
                                        <span>Kasir:</span>
                                        @if (Auth::check())
                                            <span class="badge badge-info">{{ Auth::user()->nama }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="row mb-3" id="memberRow">
                                <div class="col-md-12" id="memberSelectContainer">
                                    <label for="id_member" class="form-control-label">
                                        <i class="icon feather icon-users mr-1"></i>Member
                                    </label>
                                    <div class="d-flex align-items-center justify-content">
                                        <select id="id_member" class="form-control select2 w-100">
                                            <option value="Guest" selected>Guest</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="" id="guestInputContainer"></div>
                            </div>
                            <hr>
                            <input type="hidden" id="hiddenNoNota" name="no_nota">
                            <input type="hidden" id="hiddenKembalian" name="kembalian">
                            <input type="hidden" id="hiddenMember" name="id_member">
                            <input type="hidden" id="hiddenMinus" name="minus">

                            <div class="form-row mb-4 align-items-end">
                                <div class="form-group mb-0 col-md-12">
                                    <div class="row">
                                        <div class="col-md-12 mt-3">
                                            <label for="scan_qr" class="form-control-label">
                                                <i class="fa fa-qrcode"></i> Scan Barcode/QR Code Pembelian
                                            </label>
                                            <input type="text" id="scan_qr" class="form-control"
                                                placeholder="Scan atau masukkan Barcode/QR Code Pembelian">
                                            <small id="scanned-barang-name"></small>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group mb-2 mt-2 col-md-12">
                                    Atau
                                </div>
                                <div class="form-group mb-0 col-md-12">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <label for="id_barang" class="form-control-label">
                                                <i class="feather icon-package"></i> Ketik Nama Barang/Barcode/QR Code
                                                Pembelian
                                                secara manual <sup style="color: red">*</sup>
                                            </label>
                                            <select id="barang" class="form-control select2 w-100"></select>
                                        </div>
                                    </div>
                                </div>
                                {{-- <div class="form-group mb-0 col-md-5">
                                    <label for="harga" class="form-control-label">Harga<sup
                                            style="color: red">*</sup></label>
                                    <select class="form-control select2 w-100" id="harga">
                                        <option value="">~Pilih Member Dahulu~</option>
                                    </select>
                                </div> --}}
                                {{-- <div class="form-group mb-0 col-md-2">
                                    <label class="d-block invisible">Add</label>
                                    <button type="button" id="add-button"
                                        class="btn btn-outline-success btn-md w-100 h-100 mb-2">
                                        <i class="mr-2 fa fa-circle-plus"></i>Add
                                    </button>
                                </div> --}}
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th class="text-center">Action</th>
                                            <th class="text-center">No</th>
                                            <th>Nama Barang</th>
                                            <th>Qty</th>
                                            <th>Harga</th>
                                            <th class="text-right">Total Harga</th>
                                        </tr>
                                    </thead>
                                    <tbody id="dataStore"></tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="5" class="text-right">SubTotal</th>
                                            <th class="text-right" name="total_nilai">Rp 0</th>
                                        </tr>
                                        <tr>
                                            <th colspan="5" class="text-right">Payment</th>
                                            <th>
                                                <select name="metode" id="metode" class="form-control w-100">
                                                    <option value="">Pilih Payment</option>
                                                    <option value="Tunai">Tunai</option>
                                                    <option value="Non-Tunai">Non-Tunai</option>
                                                </select>
                                            </th>
                                        </tr>
                                        <tr id="uang-bayar-row">
                                            <th colspan="5" class="text-right">Jml Bayar</th>
                                            <th>
                                                <input type="text" name="jml_bayar" id="uang-bayar-input"
                                                    class="form-control">
                                                <input type="hidden" id="hiddenUangBayar" name="jml_bayar">
                                            </th>
                                        </tr>
                                        <tr id="kembalian-row">
                                            <th colspan="5" id="kembalian-text" class="text-right">Kembalian</th>
                                            <th class="text-right" id="kembalian-amount" name="kembalian">Rp 0</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" id="save-print-btn" class="btn btn-success w-100">
                            <i class="mr-2 fa fa-save"></i>Simpan
                        </button>
                    </div>
                </form>
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
            id: '#id_member',
            isFilter: {
                id_toko: '{{ auth()->user()->toko_id }}',
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
        }];
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
            $('#listData').html(loadingData());

            let filterParams = {};

            if (customFilter['startDate'] && customFilter['endDate']) {
                filterParams.startDate = customFilter['startDate'];
                filterParams.endDate = customFilter['endDate'];
            }

            let getDataRest = await renderAPI(
                'GET',
                '{{ route('master.transaksi.get') }}', {
                    page: page,
                    limit: limit,
                    ascending: ascending,
                    search: search,
                    id_toko: '{{ auth()->user()->toko_id }}',
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
                await setListData(handleDataArray, getDataRest.data.pagination, getDataRest.data.total);
            } else {
                errorMessage = getDataRest?.data?.message;
                let errorRow = `
                            <tr class="text-dark">
                                <th class="text-center" colspan="${$('.tb-head th').length}"> ${errorMessage} </th>
                            </tr>`;
                $('#listData').html(errorRow);
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
                    title="Hapus ${title}: ${data.no_nota}"
                    data-id='${data.id}'
                    data-name='${data.no_nota}'>
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
                no_nota: data?.no_nota ?? '-',
                tgl_transaksi: data?.tgl_transaksi ?? '-',
                nama_member: data?.nama_member ?? '-',
                total_item: data?.total_item ?? '-',
                total_nilai: data?.total_nilai ?? '-',
                metode: data?.metode ?? '-',
                nama: data?.nama ?? '-',
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
                                <td class="${classCol}">${element.no_nota}</td>
                                <td class="${classCol}">${element.tgl_transaksi}</td>
                                <td class="${classCol}">${element.nama_member}</td>
                                <td class="${classCol}">${element.total_item}</td>
                                <td class="${classCol} text-right">${element.total_nilai}</td>
                                <td class="${classCol} text-right">${element.metode}</td>
                                <td class="${classCol} text-right">${element.nama}</td>
                                <td class="${classCol} text-right">${element.action_buttons}</td>
                            </tr>`;
            });

            let totalRow = `
            <tr class="bg-primary">
                <td class="${classCol}" colspan="4"></td>
                <td class="${classCol}" style="font-size: 1rem;"><strong class="text-white fw-bold">Total</strong></td>
                <td class="${classCol} text-right"><strong class="text-white" id="totalData">${total}</strong></td>
                <td class="${classCol}" colspan="3"></td>
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
                    '{{ route('kasir.detail') }}', {
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

        // function cetakStruk(id_kasir) {
        //     const url = `{{ route('cetak.struk', ':id_kasir') }}`.replace(':id_kasir', id_kasir);
        //     const newWindow = window.open(url, '_blank');
        //     newWindow.onload = function() {
        //         newWindow.print();
        //     };
        // }

        async function cetakStruk(id_kasir) {
            try {
                const url = `{{ route('cetak.struk', ':id_kasir') }}`.replace(':id_kasir', id_kasir);

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

        function getTodayDateWithDay() {
            const today = new Date();

            const day = String(today.getDate()).padStart(2, '0');
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const year = today.getFullYear();

            const hours = String(today.getHours()).padStart(2, '0');
            const minutes = String(today.getMinutes()).padStart(2, '0');
            const seconds = String(today.getSeconds()).padStart(2, '0');

            const days = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
            const dayName = days[today.getDay()];

            return `${dayName}, ${day}-${month}-${year} ${hours}:${minutes}:${seconds}`;
        }

        function generateFormattedNumber() {
            const now = new Date();
            const day = String(now.getDate()).padStart(2, '0');
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const year = String(now.getFullYear()).slice(-2);
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            const randomDigits = Math.floor(100 + Math.random() * 900);
            const noNota = `${day}${month}${year}${hours}${minutes}${seconds}${randomDigits}`;

            return `${noNota.slice(0, 6)}-${noNota.slice(6, 12)}-${noNota.slice(12)}`;
        }

        const select = document.getElementById("barang");

        select.addEventListener("change", function() {
            select.size = 1;
        });

        document.addEventListener("click", function(event) {
            if (!select.contains(event.target)) {
                select.size = 1;
            }
        });

        function add() {
            document.getElementById('btn-tambah').addEventListener('click', function() {
                const formattedNoNota = generateFormattedNumber();
                const hiddenNoNotaInput = document.getElementById('hiddenNoNota');
                const noNotaWithoutSeparator = formattedNoNota.replace(/-/g, '');
                hiddenNoNotaInput.value = noNotaWithoutSeparator;

                $('#noNota').html(`<span class="badge badge-primary">${formattedNoNota}</span>`);
                $('#tglTransaksi').html(`<span class="badge badge-primary">${getTodayDateWithDay()}</span>`);

                setTimeout(function() {
                    $('#scan_qr').trigger('focus');
                }, 750);
            });
        }

        let tableBody;
        let subtotalFooter;
        let subtotal = 0;
        let hargaBarangTerpilih = {};
        const memberSelect = $('#id_member');
        const barangSelect = $('#barang');
        // const hargaSelect = $('#harga');
        // const addButton = document.getElementById('add-button');

        function setCreate() {
            tableBody = document.querySelector('.modal-body table tbody');
            subtotalFooter = document.querySelector('.modal-body tfoot th[colspan="5"] + th');
            const metodeSelect = document.getElementById('metode');
            const uangBayarInput = document.getElementById('uang-bayar-input');
            const kembalianText = document.getElementById('kembalian-text');
            const kembalianAmount = document.getElementById('kembalian-amount');
            let hiddenUangBayar = document.getElementById('hiddenUangBayar');
            let getStock = 0;

            const scanQRInput = document.getElementById('scan_qr');
            const scannedNameLabel = document.getElementById('scanned-barang-name');
            scanQRInput.disabled = !memberSelect.val();

            // === EVENT: Tekan ENTER di input scan ===
            scanQRInput.addEventListener('keydown', async function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();

                    const search = this.value.trim();
                    if (!search) return;

                    let getDataRest = await renderAPI(
                        'GET',
                        '{{ route('master.qrbarcode') }}', {
                            search: search,
                            id_toko: user_id_toko,
                            page: 1,
                            limit: 10,
                            ascending: true
                        }
                    ).then(res => res).catch(err => err.response);

                    const data = getDataRest?.data?.data;
                    if (Array.isArray(data) && data.length > 0) {
                        const firstItem = data[0];
                        const barangId = firstItem.id;
                        const finalBarangId = barangId.includes('/') ? barangId : `${barangId}/${barangId}`;
                        let memberId = memberSelect.val();

                        if (!memberId) {
                            notificationAlert('error', 'Error', 'Silakan pilih member terlebih dahulu.');
                            this.disabled = false;
                            return;
                        }

                        try {
                            const hargaResponse = await fetch(
                                `/admin/kasir/get-filtered-harga?id_member=${memberId}&id_barang=${finalBarangId}&id_toko=${user_id_toko}`
                            ).then(res => res.json());

                            if (!hargaResponse || (!hargaResponse.filteredHarga && !Array.isArray(hargaResponse
                                    .filteredHarga))) {
                                notificationAlert('error', 'Error', 'Harga untuk barang ini tidak ditemukan.');
                                return;
                            }

                            getStock = hargaResponse.stock || 0;
                            const newStock = hargaResponse.stock || 0;
                            const idBarangOnly = firstItem.id.includes('/') ? firstItem.id.split('/')[1] :
                                firstItem.id;

                            // Jika hasil pencarian QR dan stock habis
                            if (newStock <= 0 && search.toLowerCase().startsWith('qr')) {
                                notificationAlert('error', 'Error', 'Stock habis.');
                                this.disabled = false;
                                return;
                            }

                            scannedBarang = {
                                id: firstItem.id,
                                nama: firstItem.text,
                                stock: newStock
                            };

                            this.value = '';
                            scannedNameLabel.textContent = firstItem.text;
                            updateInputState();

                            const hargaList = Array.isArray(hargaResponse.filteredHarga) ?
                                hargaResponse.filteredHarga : [hargaResponse.filteredHarga];

                            if (hargaList.length > 0) {
                                if (scannedBarang.stock <= 0) {
                                    notificationAlert('error', 'Error', 'Stock tidak cukup.');
                                    return;
                                }

                                setTimeout(() => {
                                    addBarangToTable(
                                        scannedBarang.id,
                                        scannedBarang.nama,
                                        hargaList, // array harga dikirim ke tabel
                                        parseInt(scannedBarang.stock)
                                    );
                                    scannedBarang = null;
                                    scannedNameLabel.textContent = '';
                                    updateInputState();
                                }, 100);
                            }
                        } catch (err) {
                            notificationAlert('error', 'Error', 'Gagal mengambil data harga.');
                            this.disabled = false;
                        }
                    } else {
                        notificationAlert('error', 'Error', 'Barang tidak ditemukan.');
                        this.disabled = false;
                    }
                }
            });

            // === EVENT: Pilih barang manual ===
            barangSelect.select2().on('change', function() {
                const selectedBarang = $(this).find(':selected');
                const memberId = memberSelect.val();
                const barangId = selectedBarang.val();

                if (memberId && barangId) {
                    fetch(
                            `/admin/kasir/get-filtered-harga?id_member=${memberId}&id_barang=${barangId}&id_toko=${user_id_toko}`
                        )
                        .then(response => response.json())
                        .then(data => {
                            if (!data || (!data.filteredHarga && !Array.isArray(data.filteredHarga))) {
                                notificationAlert('error', 'Error', 'Harga untuk barang ini tidak ditemukan.');
                                return;
                            }

                            const hargaList = Array.isArray(data.filteredHarga) ? data.filteredHarga : [data
                                .filteredHarga
                            ];
                            const namaBarang = selectedBarang.text();
                            const stock = data.stock || 0;

                            if (stock <= 0) {
                                notificationAlert('error', 'Error', 'Stock tidak cukup.');
                                return;
                            }

                            // tambahkan ke tabel
                            addBarangToTable(
                                barangId,
                                namaBarang,
                                hargaList,
                                stock
                            );

                            // reset select supaya bisa pilih lagi
                            barangSelect.val(null).trigger('change');

                            scannedNameLabel.textContent = '';
                            updateInputState();
                        })
                        .catch(error => {
                            console.error('Error fetching filtered harga:', error);
                            notificationAlert('error', 'Error', 'Gagal mengambil data harga.');
                        });
                }
            });

            // === EVENT: Klik tombol tambah barang ===
            // addButton.addEventListener('click', function() {
            //     const idBarang = scannedBarang?.id || barangSelect.val();
            //     const namaBarang = scannedBarang?.nama || barangSelect.find(':selected').text();

            //     let stock = 0;
            //     if (scannedBarang) {
            //         stock = parseInt(scannedBarang.stock);
            //     } else {
            //         const selectedOption = barangSelect.find(':selected');
            //         stock = parseInt(selectedOption.data('stock')) || 0;
            //     }

            //     if (!idBarang || !namaBarang) {
            //         notificationAlert('error', 'Error', 'Data barang belum lengkap.');
            //         return;
            //     }

            //     // hargaList tetap ditentukan di addBarangToTable
            //     addBarangToTable(idBarang, namaBarang, [], stock);
            //     scannedBarang = null;
            //     scannedNameLabel.textContent = '';
            //     updateInputState();
            // });

            // === Fokus otomatis di select2 saat dibuka ===
            barangSelect.on('select2:open', function() {
                const searchField = document.querySelector(`.select2-container--open input.select2-search__field`);
                if (searchField) {
                    setTimeout(() => searchField.focus(), 0);
                }
            });

            // === Event ganti Member ===
            $('#id_member').on('change', function() {
                const selectedValue = $(this).val();
                const guestInputContainer = $('#guestInputContainer');
                const memberSelectContainer = $('#memberSelectContainer');

                if (selectedValue === 'Guest') {
                    if ($('#nama_guest').length === 0) {
                        guestInputContainer.html(`
                    <label for="nama_guest" class="form-control-label">
                        <i class="icon feather icon-user mr-1"></i>Nama Guest <sup class="text-danger"><i>*Boleh dikosongkan</i></sup>
                    </label>
                    <div class="d-flex align-items-center justify-content">
                        <input type="text" id="nama_guest" name="nama_guest" class="form-control" placeholder="Masukkan nama guest">
                    </div>`);
                    }
                    memberSelectContainer.removeClass('col-md-12').addClass('col-md-6');
                    guestInputContainer.addClass('col-md-6');
                } else {
                    guestInputContainer.empty().removeClass('col-md-6');
                    memberSelectContainer.removeClass('col-md-6').addClass('col-md-12');
                }

                document.getElementById('scan_qr').disabled = !selectedValue;
                barangSelect.prop('disabled', !selectedValue).trigger('change');
                barangSelect.data('select2').$container.find('.select2-selection__placeholder').text(
                    'Pilih Barang');
                document.getElementById('hiddenMember').value = selectedValue;
                updateInputState();
            });

            // === Submit Form ===
            document.querySelector('form').addEventListener('submit', function() {
                document.getElementById('hiddenNoNota').value = document.getElementById('noNota').textContent;
                document.getElementById('hiddenKembalian').value = kembalianAmount.textContent;
                document.getElementById('hiddenMember').value = memberSelect.val();
            });

            // === Input Uang Bayar ===
            uangBayarInput.addEventListener('input', function() {
                let value = this.value.replace(/[^0-9]/g, '');
                hiddenUangBayar.value = value;
                this.value = value ? parseInt(value).toLocaleString() : '';
                updateKembalian();
            });

            // === Pilih metode pembayaran ===
            metodeSelect.addEventListener('change', function() {
                const isTunai = metodeSelect.value === "Tunai";
                document.getElementById('uang-bayar-row').style.display = isTunai ? '' : 'none';
                document.getElementById('kembalian-row').style.display = isTunai ? '' : 'none';
                uangBayarInput.value = '';
                kembalianAmount.textContent = 'Rp 0';
            });
            metodeSelect.dispatchEvent(new Event('change'));

            // === Hitung Kembalian ===
            function updateKembalian() {
                const uangBayar = hiddenUangBayar.value || 0;
                const kembalian = uangBayar - subtotal;

                if (kembalian >= 0) {
                    kembalianText.textContent = 'Kembalian';
                    kembalianAmount.textContent = `Rp ${kembalian.toLocaleString()}`;
                    document.getElementById('hiddenKembalian').value = kembalian;
                    document.getElementById('hiddenMinus').value = '';
                } else {
                    let math = Math.abs(kembalian);
                    kembalianText.textContent = 'Sisa Pembayaran';
                    kembalianAmount.textContent = `Rp ${math.toLocaleString()}`;
                    document.getElementById('hiddenMinus').value = math;
                    document.getElementById('hiddenKembalian').value = '';
                }
            }

            // === Update state input ===
            function updateInputState() {
                const scannedText = scanQRInput.value.trim();
                const selectedBarang = barangSelect.val();
                const isMemberSelected = !!memberSelect.val();

                if (scannedText) {
                    barangSelect.prop('disabled', true).val(null).trigger('change');
                    barangSelect.data('select2').$container.find('.select2-selection__placeholder')
                        .text('Nonaktif karena input QR');
                } else {
                    barangSelect.prop('disabled', !isMemberSelected);
                    barangSelect.data('select2').$container.find('.select2-selection__placeholder')
                        .text(isMemberSelected ? 'Pilih Barang' : 'Pilih Member terlebih dahulu');
                }

                if (selectedBarang) {
                    scanQRInput.disabled = true;
                    scanQRInput.placeholder = 'Nonaktif karena memilih barang manual';
                } else {
                    scanQRInput.disabled = !isMemberSelected;
                    scanQRInput.placeholder = 'Scan atau masukkan Barcode/QR Code Pembelian';
                }
            }

            scanQRInput.addEventListener('input', updateInputState);

            // === Default: Guest ===
            memberSelect.val('Guest').trigger('change');
            barangSelect.prop('disabled', false).trigger('change');
            window.updateKembalian = updateKembalian;
        }


        function checkMemberLock() {
            const hasRows = tableBody.querySelectorAll('tr').length > 0;
            memberSelect.prop('disabled', hasRows);
        }

        function extractIdBarangOnly(id) {
            return id.includes('/') ? id.split('/')[1] : id;
        }

        function extractQrOnly(id) {
            return id.includes('/') ? id.split('/')[0].split(',') : [];
        }

        function addBarangToTable(idBarang, namaBarang, hargaList, newStock) {
            const qty = 1;

            // Pastikan hargaList selalu array
            if (!Array.isArray(hargaList)) {
                hargaList = hargaList ? [hargaList] : [];
            }

            if (!idBarang || hargaList.length === 0) {
                notificationAlert('error', 'Error', 'Silakan lengkapi semua data sebelum menambahkan.');
                return;
            }

            const idBarangOnly = extractIdBarangOnly(idBarang);
            let existingRow = null;

            // Cari baris berdasarkan id_barang utama
            tableBody.querySelectorAll('tr').forEach(row => {
                const rowId = row.querySelector('input[name="id_barang[]"]').value;
                const rowIdOnly = extractIdBarangOnly(rowId);
                if (rowIdOnly === idBarangOnly) {
                    existingRow = row;
                }
            });

            if (existingRow) {
                // kalau barang sama sudah ada → tambah qty saja
                const qtyInput = existingRow.querySelector('.qty-input');
                let currentQty = parseInt(qtyInput.value);
                let currentMax = parseInt(qtyInput.max);
                const highestStock = Math.max(currentMax, newStock);

                let newQty = currentQty + 1;
                if (newQty > highestStock) {
                    notificationAlert('error', 'Error', 'Stock barang tidak cukup');
                    return;
                }

                qtyInput.value = newQty;
                qtyInput.max = highestStock;
                existingRow.querySelector('small.text-danger').textContent = `Max: ${highestStock}`;
            } else {
                const hargaSelectInRow = document.createElement('select');
                hargaSelectInRow.classList.add('form-control', 'harga-select');
                hargaSelectInRow.name = 'harga[]';

                hargaList.forEach((h, i) => {
                    if (h) {
                        const opt = new Option(`Rp ${parseInt(h).toLocaleString()}`, h, i === 0, i === 0);
                        hargaSelectInRow.appendChild(opt);
                    }
                });

                const newRow = document.createElement('tr');
                newRow.innerHTML = `
            <td class="text-center align-top">
                <button type="button" class="btn btn-danger btn-sm remove-btn">
                    <i class="fa fa-trash-alt"></i>
                </button>
            </td>
            <td class="text-center align-top"></td>
            <td class="align-top"><input type="hidden" name="id_barang[]" value="${idBarang}">${namaBarang}</td>
            <td class="align-top">
                <input type="number" class="form-control qty-input" name="qty[]" value="${qty}" min="1" max="${newStock}">
                <small class="text-danger">Max: ${newStock}</small>
            </td>
            <td class="harga-cell align-top"></td>
            <td class="total-harga align-top text-right" data-total="0">Rp 0</td>
                `;

                newRow.querySelector('.harga-cell').appendChild(hargaSelectInRow);
                tableBody.appendChild(newRow);
                checkMemberLock();

                // event qty
                const qtyInput = newRow.querySelector('.qty-input');
                qtyInput.addEventListener('input', function() {
                    let inputQty = parseInt(this.value) || 1;
                    inputQty = Math.min(Math.max(1, inputQty), newStock);
                    this.value = inputQty;
                    recalculateSubtotal();
                });
                qtyInput.addEventListener('blur', function() {
                    if (parseInt(this.value) < 1 || isNaN(this.value)) {
                        this.value = 1;
                        recalculateSubtotal();
                    }
                });

                // event harga
                hargaSelectInRow.addEventListener('change', function() {
                    recalculateSubtotal();
                });

                // tombol remove
                newRow.querySelector('.remove-btn').addEventListener('click', function() {
                    newRow.remove();
                    updateRowNumbers();
                    recalculateSubtotal();
                    checkMemberLock();
                });

                updateRowNumbers();
            }

            recalculateSubtotal();
        }

        function recalculateSubtotal() {
            subtotal = 0;

            tableBody.querySelectorAll('tr').forEach(row => {
                const hargaInput = row.querySelector('.harga-select');
                const harga = parseInt(hargaInput ? hargaInput.value : 0) || 0;
                const qty = parseInt(row.querySelector('input[name="qty[]"]').value) || 0;
                const total = harga * qty;

                subtotal += total;

                const totalCell = row.querySelector('.total-harga');
                totalCell.textContent = `Rp ${total.toLocaleString()}`;
                totalCell.dataset.total = total;
            });

            subtotalFooter.textContent = `Rp ${subtotal.toLocaleString()}`;
            updateKembalian();
        }

        function extractIdBarangOnly(id) {
            return id.includes('/') ? id.split('/')[1] : id;
        }

        function updateRowNumbers() {
            const rows = tableBody.querySelectorAll('tr');
            rows.forEach((row, index) => {
                // Asumsikan kolom nomor ada di kolom ke-2 (index 1)
                const numberCell = row.children[1];
                if (numberCell) {
                    numberCell.textContent = index + 1;
                }
            });
        }

        let isDataSaved = false;

        async function saveData() {
            $(document).on("click", "#save-print-btn", async function(e) {
                e.preventDefault();

                const btn = $(this);
                const isPrintMode = btn.data('mode') === 'print';
                const id = btn.data('id');

                if (isPrintMode) {
                    cetakStruk(id);
                    return;
                }

                const saveButton = this;
                const form = btn.closest("form")[0];
                const formData = new FormData(form);

                if (saveButton.disabled) return;

                swal({
                    title: "Konfirmasi",
                    text: "Apakah Anda yakin ingin menyimpan data ini?",
                    type: "question",
                    showCancelButton: true,
                    confirmButtonColor: '#2ecc71',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Simpan',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                }).then(async (willSave) => {
                    if (!willSave) return;

                    saveButton.disabled = true;
                    const originalContent = btn.html();
                    btn.data('original-content', originalContent);
                    btn.html(
                        `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan`
                    );

                    loadingPage(true);

                    try {
                        const response = await renderAPI('POST',
                            '{{ route('transaksi.kasir.store') }}', formData);
                        loadingPage(false);

                        if (response.status === 200) {
                            const newId = response.data.data.id;
                            await getListData(defaultLimitPage, currentPage, defaultAscending,
                                defaultSearch, customFilter);

                            swal("Berhasil!", "Data berhasil disimpan.", "success");

                            // Set flag bahwa data telah berhasil disimpan
                            isDataSaved = true;

                            btn
                                .removeClass('btn-success')
                                .addClass('btn-primary')
                                .html('<i class="fa fa-print mr-2"></i>Cetak Struk')
                                .attr('type', 'button')
                                .data('id', newId)
                                .data('mode', 'print')
                                .prop('disabled', false);
                        } else {
                            swal("Pemberitahuan", response.data.message || "Terjadi kesalahan",
                                "info");
                            saveButton.disabled = false;
                            btn.html(originalContent);
                        }
                    } catch (error) {
                        loadingPage(false);
                        swal("Kesalahan", error?.response?.data?.message ||
                            "Terjadi kesalahan saat menyimpan data.", "error");
                        saveButton.disabled = false;
                        btn.html(originalContent);
                    }
                });
            });

            // Saat modal ditutup, reload hanya jika data tersimpan
            $('#modal-form').on('hidden.bs.modal', function() {
                if (isDataSaved) {
                    window.location.reload();
                }
            });

            // Redundant, jaga-jaga kalau ada tombol ✖️ manual
            $('#modal-form .close[data-dismiss="modal"]').on('click', function() {
                if (isDataSaved) {
                    window.location.reload();
                }
            });
        }

        function initFormPlugins() {
            $('.select2').select2();
        }

        async function initPageLoad() {
            await add();
            await setCreate();
            await getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch, customFilter);
            await searchList();
            await filterList();
            // await selectFormat('#id_member', 'Pilih Member', false);
            await selectData(selectOptions);
            // await selectFormat('#harga', 'Pilih Harga', true);
            await saveData();
        }
    </script>
@endsection
