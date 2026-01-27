@extends('layouts.main')

@section('title')
    Pembelian Barang
@endsection

@section('css')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.0.0/dist/css/tom-select.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/button-action.css') }}">
    <link rel="stylesheet" href="{{ asset('css/table.css') }}">
    <link rel="stylesheet" href="{{ asset('css/daterange-picker.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sweetalert2.css') }}">
    @include('transaksi.pembelianbarang.css')
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
                                @if (hasAnyPermission(['POST /pembelianbarang/store', 'POST /import-pembelianbarang']))
                                    @if (hasAnyPermission(['POST /pembelianbarang/store']))
                                        <button onclick="openAddModal()"
                                            class="btn btn-primary mb-2 mb-lg-0 text-white add-data custom-btn-tambah"
                                            data-container="body" data-toggle="tooltip" data-placement="top"
                                            title="Tambah Data Pembelian Barang">
                                            <i class="fa fa-plus-circle"></i> Tambah
                                        </button>
                                    @endif
                                    <button class="btn-dynamic btn btn-outline-primary custom-btn-tambah" type="button"
                                        data-toggle="collapse" data-target="#filter-collapse" aria-expanded="false"
                                        aria-controls="filter-collapse" data-container="body" data-toggle="tooltip"
                                        data-placement="top" title="Filter Pembelian Barang">
                                        <i class="fa fa-filter"></i> Filter
                                    </button>
                                    @if (hasAnyPermission(['POST /import-pembelianbarang']))
                                        <form action="{{ route('master.pembelianbarang.import') }}" method="POST"
                                            enctype="multipart/form-data" class="custom-form-import">
                                            @csrf
                                            <input type="file" name="file" class="custom-input-file" accept=".xlsx"
                                                required>
                                            <button type="submit" class="btn btn-success custom-btn-import">
                                                <i class="fa fa-file-import"></i> Import
                                            </button>
                                        </form>
                                    @endif
                                @endif
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
                            <x-alerts-array />
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
                                                <th class="text-wrap align-top">ID Pembelian</th>
                                                <th class="text-wrap align-top">Status</th>
                                                <th class="text-wrap align-top">Nota</th>
                                                <th class="text-wrap align-top">Tanggal</th>
                                                <th class="text-wrap align-top">Sumber Dana</th>
                                                <th class="text-wrap align-top">Suplier</th>
                                                <th class="text-wrap align-top text-right">Total Qty</th>
                                                <th class="text-wrap align-top text-right">Total Harga</th>
                                                <th class="text-wrap align-top text-center">Action</th>
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

                <div id="modal-form" class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog"
                    aria-labelledby="modal-title" aria-hidden="true">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="modal-title"></h5>
                                <button type="button" class="btn-close reset-all close" data-bs-dismiss="modal"
                                    aria-label="Close"><i class="fa fa-xmark"></i></button>
                            </div>
                            <div class="modal-body">
                                <div class="custom-tab">
                                    <nav>
                                        <div class="nav nav-tabs" id="nav-tab" role="tablist">
                                            <a class="nav-item nav-link active" id="tambah-tab" data-toggle="tab"
                                                href="#tambah" role="tab" aria-controls="tambah"
                                                aria-selected="true">Tambah Pembelian</a>
                                            <a class="nav-item nav-link disabled" id="detail-tab" data-toggle="tab"
                                                href="#detail" role="tab" aria-controls="detail"
                                                aria-selected="true">Detail Pembelian</a>
                                        </div>
                                    </nav>
                                    <div class="tab-content pl-3 pt-2" id="nav-tabContent">
                                        <div class="tab-pane fade show active" id="tambah" role="tabpanel"
                                            aria-labelledby="tambah-tab">
                                            <form id="form-tambah-pembelian">
                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label for="no_nota" class=" form-control-label">Nomor
                                                                Nota<sup style="color: red">*</sup></label>
                                                            <input type="text" id="no_nota" name="no_nota"
                                                                placeholder="Contoh : 001" class="form-control">
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label for="id_supplier" class="form-control-label">Nama
                                                                Suplier<sup style="color: red">*</sup></label>
                                                            <select name="id_supplier" id="id_supplier"
                                                                class="form-control select2">
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label for="toko_group" class="form-control-label">Grup
                                                                Toko<sup style="color: red">*</sup></label>
                                                            <select name="toko_group" id="toko_group"
                                                                class="form-control select2">
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label for="id_supplier" class="form-control-label">Sumber
                                                                Dana<sup style="color: red">*</sup></label>
                                                            <select name="kas" id="kas" data-jenis_id
                                                                data-saldo_kas class="form-control select2">
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label for="tgl_nota" class="form-control-label">
                                                                Tanggal Nota<sup style="color: red">*</sup>
                                                            </label>

                                                            <input class="form-control tgl_nota" type="datetime-local"
                                                                name="tgl_nota" id="tgl_nota"
                                                                placeholder="Pilih tanggal & waktu">
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label for="tipe" class="form-control-label">Tipe
                                                                Transaksi<sup style="color: red">*</sup></label>
                                                            <select name="tipe" id="tipe" class="form-control">
                                                                <option value="cash" selected>Cash</option>
                                                                <option value="hutang">Hutang</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <button type="button" style="float: right" id="btn-next-step"
                                                    class="btn btn-primary">
                                                    <i class="fa fa-arrow-right"></i> Lanjut
                                                </button>
                                            </form>
                                        </div>
                                        <div class="tab-pane fade" id="detail" role="tabpanel"
                                            aria-labelledby="detail-tab">
                                            <div class="container-fluid px-0">
                                                <div class="row">
                                                    <div class="col-12 col-md-6 mb-2">
                                                        <div
                                                            class="list-group-item d-flex justify-content-between align-items-center border rounded p-3">
                                                            <h5 class="mb-0"><i class="fa fa-barcode"></i> Nomor &
                                                                Tanggal Nota
                                                            </h5>
                                                            <span class="badge badge-primary"><strong
                                                                    id="no-nota"></strong> / <strong
                                                                    id="tgl-nota"></strong></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 col-md-6 mb-2">
                                                        <div
                                                            class="list-group-item d-flex justify-content-between align-items-center border rounded p-3">
                                                            <h5 class="mb-0"><i class="fa fa-user-tie"></i> Nama
                                                                Supplier</h5>
                                                            <span id="nama-supplier" class="badge badge-secondary"></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 col-md-6 mb-2">
                                                        <div
                                                            class="list-group-item d-flex justify-content-between align-items-center border rounded p-3">
                                                            <h5 class="mb-0"><i class="fa fa-coins"></i> Sumber
                                                                Dana</h5>
                                                            <span id="sumber-dana" class="badge badge-secondary"></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 col-md-6 mb-2">
                                                        <div
                                                            class="list-group-item d-flex justify-content-between align-items-center border rounded p-3">
                                                            <h5 class="mb-0"><i class="fa fa-layer-group"></i> Lainnya
                                                            </h5>
                                                            <div class="badge badge-secondary">
                                                                <span id="tipe-transaksi"></span>
                                                                <span> / </span>
                                                                <span id="toko-group"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <hr>
                                            <div id="item-container">
                                                <div class="item-group">
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <div class="form-group">
                                                                <label for="id_barang" class="form-control-label">
                                                                    Nama Barang<sup style="color: red">*</sup></label>
                                                                <select name="id_barangs[]" id="id_barang"
                                                                    class="id-barang" data-placeholder="Pilih Barang...">
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-3">
                                                            <div class="form-group">
                                                                <label for="jml_item" class="form-control-label">Jumlah
                                                                    Item<sup style="color: red">*</sup></label>
                                                                <input type="number" id="jml_item" min="1"
                                                                    name="qty[]" placeholder="Contoh: 16"
                                                                    class="form-control jumlah-item">
                                                            </div>
                                                        </div>
                                                        <div class="col-3">
                                                            <div class="form-group">
                                                                <label for="harga_barang" class="form-control-label">Harga
                                                                    Barang<sup style="color: red">*</sup></label>
                                                                <input type="text" id="harga_barang" min="1"
                                                                    name="harga_barang[]" placeholder="Contoh: 16000"
                                                                    class="form-control harga-barang rupiah">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <br>
                                            <div class="row mb-0">
                                                <div class="col-6">
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <div class="card border border-primary mb-1">
                                                                <div class="card-body">
                                                                    <p class="font-weight-bold">
                                                                        Detail Stok
                                                                    </p>
                                                                    <p class="card-text">Stok :<strong
                                                                            class="stock">0</strong></p>
                                                                    <p class="card-text">Hpp Awal : <strong
                                                                            class="hpp-awal">Rp 0</strong></p>
                                                                    <p class="card-text">Hpp Baru : <strong
                                                                            class="hpp-baru">Rp 0</strong></p>
                                                                </div>
                                                                <button type="button" id="reset"
                                                                    style="float: right"
                                                                    class="btn btn-secondary">Reset</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    @foreach ($LevelHarga as $index => $level)
                                                        <div class="input-group mb-2">
                                                            <div class="input-group-prepend">
                                                                <span
                                                                    class="input-group-text">{{ $level->nama_level_harga }}</span>
                                                            </div>
                                                            <input type="hidden" name="level_nama[]"
                                                                value="{{ $level->nama_level_harga }}">
                                                            <div class="custom-file">
                                                                <input type="text"
                                                                    class="form-control level-harga rupiah"
                                                                    name="level_harga[]" placeholder="0"
                                                                    id="level_harga_{{ $index }}"
                                                                    data-index="{{ $index }}" data-hpp-baru="0">
                                                                <label class="input-group-text"
                                                                    id="persen_{{ $index }}">0%</label>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                    <button type="button" id="add-item-detail" style="float: right"
                                                        class="btn btn-outline-success"><i
                                                            class="fa fa-circle-plus mr-1"></i>Add</button>
                                                </div>
                                            </div>
                                            <hr>
                                            <div class="row">
                                                <div class="col-12">
                                                    <table class="table table-bordered">
                                                        <thead>
                                                            <tr>
                                                                <th class="text-center">Action</th>
                                                                <th class="text-center" scope="col">No</th>
                                                                <th scope="col">Nama Barang</th>
                                                                <th class="text-right" scope="col">Qty</th>
                                                                <th class="text-right" scope="col">Harga</th>
                                                                <th class="text-right" scope="col">Total Harga</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="tempData">
                                                        </tbody>
                                                        <tfoot>
                                                            <tr>
                                                                <th scope="col" colspan="5"
                                                                    style="text-align:right">SubTotal</th>
                                                                <th class="text-right" scope="col" id="subtotal">
                                                                    Rp </th>
                                                            </tr>
                                                        </tfoot>
                                                    </table>
                                                    <div class="form-group text-right">
                                                        <button type="button" id="cancel-button"
                                                            class="btn btn-secondary mr-1 btn-close"
                                                            data-bs-dismiss="modal" aria-label="Close">
                                                            <i class="fa fa-times"></i> Cancel
                                                        </button>
                                                        <button type="button" id="btn-submit"
                                                            class="btn btn-primary ml-1">
                                                            <i class="fa fa-save"></i> Simpan
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
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
        let title = 'Pembeliang Barang';
        let defaultLimitPage = 10;
        let currentPage = 1;
        let totalPage = 1;
        let defaultAscending = 0;
        let defaultSearch = '';
        let customFilter = {};
        let id_pembelian_post = null;
        let tokoGroupID = null;
        let idPembelianEdit = null;
        let rowGlobal = [];
        let selectedSupplier = null;
        let selectedTokoGroup = null;
        let pembelianBarangId = null;
        let selectedKas = {
            id: null,
            jenis_id: null,
            saldo_kas: null
        };
        let addedItems = new Set();
        let selectOptions = [{
                id: '#kas',
                isUrl: '{{ route('total.kas') }}',
                placeholder: 'Pilih Kas',
                isModal: '#modal-form',
                isFilter: {
                    toko_id: '{{ auth()->user()->toko_id }}',
                    tipe: 'kecil',
                    dompet: false
                },
                extraFields: {
                    jenis_id: 'jenis_id',
                    tipe_kas: 'tipe_kas',
                    saldo_kas: 'saldo_kas',
                },
                isForm: true
            },
            {
                id: '#id_supplier',
                isFilter: {
                    id_toko: '{{ auth()->user()->toko_id }}',
                },
                isUrl: '{{ route('master.suplier') }}',
                placeholder: 'Pilih Suplier',
                isModal: '#modal-form',
                isForm: true
            },
            {
                id: '#toko_group',
                isFilter: {
                    toko_id: {{ auth()->user()->toko_id }},
                },
                isUrl: '{{ route('dm.toko.group.select') }}',
                placeholder: 'Pilih Grup Toko',
                isModal: '#modal-form',
                isForm: true
            },
        ];

        async function getListData(limit = 10, page = 1, ascending = 0, search = '', customFilter = {}) {
            $('#listData').html(loadingData());

            let filterParams = {};

            if (customFilter['start_date'] && customFilter['end_date']) {
                filterParams.start_date = customFilter['start_date'];
                filterParams.end_date = customFilter['end_date'];
            }

            let getDataRest = await renderAPI(
                'GET',
                '{{ route('tb.pb.get') }}', {
                    page: page,
                    limit: limit,
                    ascending: ascending,
                    search: search,
                    ...filterParams
                }
            ).then(function(response) {
                return response;
            }).catch(function(error) {
                let resp = error.response;
                return resp;
            });

            if (getDataRest && getDataRest.status == 200 && Array.isArray(getDataRest.data.data.item) && getDataRest
                .data
                .data.item.length > 0) {
                let handleDataArray = await Promise.all(
                    getDataRest.data.data.item.map(async item => await handleData(item))
                );
                await setListData(handleDataArray, getDataRest.data.pagination, getDataRest.data.data.total);
            } else {
                errorMessage = 'Tidak ada data';
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
            let elementData = encodeURIComponent(JSON.stringify(data));
            let status = '';
            let edit_button = '';
            let delete_button = '';
            let detail_button = '';

            if (data?.status === 'Sukses' || data?.status === 'success_debt') {
                status =
                    `<span class="badge badge-success custom-badge"><i class="mx-1 fa fa-circle-check"></i>${data.status}</span>`;
                detail_button = `
                    <a href="pembelianbarang/${data.id}/detail?r=${data.id}" class="p-1 btn detail-data action_button"
                        data-container="body" data-toggle="tooltip" data-placement="top"
                        title="Detail Data Nomor Nota: ${data.nota}"
                        data-id='${data.id}'>
                        <span class="text-dark">Detail</span>
                        <div class="icon text-info">
                            <i class="fa fa-eye"></i>
                        </div>
                    </a>`;
            } else if (data?.status === 'completed_debt') {
                status =
                    `<span class="badge badge-warning custom-badge"><i class="mx-1 fa fa-circle-info"></i>${data.status}</span>`;
                detail_button = `
                    <a href="pembelianbarang/${data.id}/detail?r=${data.id}" class="p-1 btn detail-data action_button"
                        data-container="body" data-toggle="tooltip" data-placement="top"
                        title="Detail Data Nomor Nota: ${data.nota}"
                        data-id='${data.id}'>
                        <span class="text-dark">Detail</span>
                        <div class="icon text-info">
                            <i class="fa fa-eye"></i>
                        </div>
                    </a>`;
                edit_button = `
                <a href="{{ route('keuangan.hutang.index') }}" button class="p-1 btn action_button"
                    data-container="body" data-toggle="tooltip" data-placement="top" class="p-1 btn action_button"
                    title="Bayar Nota ${data.nota} ke Halaman Hutang"
                    data-id='${data.id}' data-name='${data.nama_supplier}' data-nota='${data.nota}' data-tanggal='${data.tgl_nota}' data-tipe='${data.tipe}'>
                    <span class="text-dark">Bayar</span>
                    <div class="icon text-success">
                        <i class="fa fa-coins"></i>
                    </div>
                </a>`;
            } else {
                status =
                    `<span class="badge badge-info custom-badge"><i class="mx-1 fa fa-spinner"></i>${data.status}</span>`;
                edit_button = `
                <a button class="p-1 btn edit-data action_button" onclick="openEditModal(${data.id})"
                    data-container="body" data-toggle="tooltip" data-placement="top" class="p-1 btn edit-data action_button"
                    title="Edit Data Nomor Nota: ${data.nota}"
                    data-id='${data.id}' data-name='${data.nama_supplier}' data-nota='${data.nota}' data-tanggal='${data.tgl_nota}' data-tipe='${data.tipe}'>
                    <span class="text-dark">Edit</span>
                    <div class="icon text-warning">
                        <i class="fa fa-edit"></i>
                    </div>
                </a>`;
                delete_button = `
                <a class="p-1 btn delete-data action_button"
                    data-container="body" data-toggle="tooltip" data-placement="top"
                    title="Hapus ${title} No.Nota: ${data.nota}" data="${elementData}">
                    <span class="text-dark">Hapus</span>
                    <div class="icon text-danger">
                        <i class="fa fa-trash"></i>
                    </div>
                </a>`;
            }

            let action_buttons = '';
            if (edit_button || detail_button || delete_button) {
                action_buttons = `
                <div class="d-flex justify-content-center">
                    ${detail_button ? `<div class="hovering p-1">${detail_button}</div>` : ''}
                    ${edit_button ? `<div class="hovering p-1">${edit_button}</div>` : ''}
                    ${delete_button ? `<div class="hovering p-1">${delete_button}</div>` : ''}
                </div>`;
            } else {
                action_buttons = `
                <span class="badge badge-secondary">Tidak Ada Aksi</span>`;
            }

            return {
                id: data?.id ?? '-',
                is_status: data?.status ?? '-',
                status,
                suplier: data?.suplier ?? '-',
                kas: data?.kas ?? '-',
                tanggal: data?.tanggal ?? '-',
                nota: data?.nota ?? '-',
                qty: data?.qty ?? '-',
                total: data?.total ?? '-',
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
                let classStatus = element.is_status === 'Sukses' ? 'clickable-row' : '';
                getDataTable += `
                        <tr class="text-dark ${classStatus}" data-id="${element.id}">
                            <td class="${classCol} text-center">${display_from + index}.</td>
                            <td class="${classCol} text-primary text-center">${element.id}</td>
                            <td class="${classCol}">${element.status}</td>
                            <td class="${classCol}">${element.nota}</td>
                            <td class="${classCol}">${element.tanggal}</td>
                            <td class="${classCol}">${element.kas}</td>
                            <td class="${classCol}">${element.suplier}</td>
                            <td class="${classCol} text-right">${element.qty}</td>
                            <td class="${classCol} text-right">${element.total}</td>
                            <td class="${classCol}">${element.action_buttons}</td>
                        </tr>`;
            });

            let totalRow = `
            <tr class="bg-primary">
                <td class="${classCol}" colspan="6"></td>
                <td class="${classCol}" style="font-size: 1rem;"><strong class="text-white fw-bold">Total Sukses</strong></td>
                <td class="${classCol} text-right"><strong class="text-white" id="totalQty">${total.qty}</strong></td>
                <td class="${classCol} text-right"><strong class="text-white" id="totalNominal">${total.nominal}</strong></td>
                <td class="${classCol}" colspan="3"></td>
            </tr>`;

            $('#listData').html(getDataTable);
            $('#listData').closest('table').find('tfoot').html(totalRow);

            $('#totalPage').text(pagination.total);
            $('#countPage').text(`${display_from} - ${display_to}`);
            $('[data-toggle="tooltip"]').tooltip();
            renderPagination();

            $('.clickable-row').on('click', function(e) {
                if ($(e.target).closest('.edit-data, .detail-data, .delete-data').length) {
                    return;
                }

                let id = $(this).data('id');
                if (id) {
                    window.location.href = `pembelianbarang/${id}/detail?r=${id}`;
                }
            });
        }

        const PBState = {
            mode: 'add', // add | edit
            pembelianId: null,
            header: {},
            items: [], // single source of truth
            addedItems: new Set(),
        };

        function selectBarang() {

            if (!PBState.header.jenis_barang_id) {
                console.error('Jenis barang belum ada');
                return;
            }

            let data = [{
                id: '#id_barang',
                isFilter: {
                    id_toko: '{{ auth()->user()->toko_id }}',
                    jenis_barang_id: PBState.header.jenis_barang_id,
                    with: 'barcode',
                },
                isUrl: '{{ route('master.barang') }}',
                placeholder: 'Pilih Barang',
                isModal: '#modal-form',
                isForm: true
            }];

            selectData(data);
        }

        async function refreshKas() {

            const $kas = $('#kas');

            if ($kas.hasClass("select2-hidden-accessible")) {
                $kas.select2('destroy');
            }

            $kas.empty();

            await selectData(selectOptions);

            $kas.val(null).trigger('change');
        }

        function setTabMode(mode) {

            if (mode === 'add') {
                // Tambah aktif
                $('#tambah-tab').removeClass('d-none disabled').addClass('active show');
                $('#tambah').addClass('active show');

                // Detail disabled
                $('#detail-tab').addClass('disabled').removeClass('active');
                $('#detail').removeClass('active show');

            } else if (mode === 'edit') {
                // Tambah hidden
                $('#tambah-tab').addClass('d-none');
                $('#tambah').removeClass('active show');

                // Detail langsung aktif
                $('#detail-tab').removeClass('disabled').addClass('active show');
                $('#detail').addClass('active show');
            }
        }

        async function openAddModal() {
            PBState.mode = 'add';
            PBState.pembelianId = null;
            PBState.items = [];
            PBState.addedItems.clear();
            PBState.header = {};

            resetForm();
            setTabMode('add');

            $('#modal-title').html('<i class="fa fa-circle-plus"></i> Form Pembelian Barang');
            await refreshKas();

            $('#modal-form').modal('show');
            setDatePicker('tgl_nota');

            $('#btn-next-step').off().on('click', async function() {
                let supplier = $('#id_supplier').val();
                let nota = $('#no_nota').val();
                let tgl = $('#tgl_nota').val();
                let tokoGroup = $('#toko_group').val();
                let kas = $('#kas').val();

                if (!supplier || !nota || !tgl || !tokoGroup || !kas) {
                    return notificationAlert('warning', 'Error', 'Lengkapi data header dulu');
                }

                // ambil kas data
                let kasData = $('#kas').select2('data')[0];
                let jenisBarang = kasData?.jenis_id;

                if (!jenisBarang) {
                    return notificationAlert('warning', 'Error', 'Jenis barang tidak ditemukan');
                }

                // simpan ke state (JANGAN overwrite)
                Object.assign(PBState.header, {
                    supplier,
                    nota,
                    tgl,
                    tokoGroup,
                    kas,
                    jenis_barang_id: jenisBarang
                });

                // preview
                $('#no-nota').text(nota);
                $('#tgl-nota').text(tgl);
                $('#nama-supplier').text($('#id_supplier option:selected').text());
                $('#toko-group').text($('#toko_group option:selected').text());
                $('#sumber-dana').text(kasData.text);
                $('#tipe-transaksi').text($('#tipe option:selected').text());

                $('#form-tambah-pembelian :input').prop('disabled', true);
                $('#detail-tab').removeClass('disabled').tab('show');

                await Promise.all([
                    selectBarang(),
                    setHpp()
                ]);
            });
        }

        async function openEditModal(id) {
            PBState.mode = 'edit';
            PBState.pembelianId = id;
            PBState.items = [];
            PBState.addedItems.clear();

            resetForm();
            setTabMode('edit');

            $('#modal-title').html('<i class="fa fa-edit"></i> Edit Pembelian Barang');
            $('#modal-form').modal('show');

            await loadEditData(id);
        }

        async function loadEditData(id) {

            const res = await renderAPI('GET', '{{ route('tb.pb.temp.get') }}', {
                pembelian_barang_id: id
            });

            const data = res.data.data;

            Object.assign(PBState.header, {
                supplier: data.suplier_id,
                nota: data.nota,
                tgl: data.tanggal,
                tokoGroup: data.toko_group_id,
                kas: data.kas_id,
                jenis_barang_id: data.jenis_barang_id,
                tipe: data.tipe
            });
            PBState.items = data.detail.map(d => ({
                id_barang: String(d.barang_id),
                qty: d.qty,
                harga_barang: d.harga_beli,
                subtotal: Number(d.subtotal),
                barang_label: d.barang_label,
            }));

            PBState.items.forEach(i => PBState.addedItems.add(i.id_barang));

            // preview header
            $('#no-nota').text(data.nota);
            $('#tgl-nota').text(data.tanggal);
            $('#nama-supplier').text(data.suplier);
            $('#toko-group').text(data.toko_group);
            $('#tipe-transaksi').text(data.tipe);
            $('#sumber-dana').text(data.kas);

            renderTable();
            updateSubtotal();
            await Promise.all([
                selectBarang(),
                setHpp()
            ]);
        }

        async function removeItem(idBarang) {
            idBarang = String(idBarang);

            await renderAPI('DELETE', '{{ route('tb.pb.temp.delete') }}', {
                id_pembelian: PBState.pembelianId,
                id_barang: idBarang,
                toko_group_id: PBState.header.tokoGroup,
            });

            PBState.items = PBState.items.filter(i => i.id_barang !== idBarang);
            PBState.addedItems.delete(idBarang);

            renderTable();
            updateSubtotal();
        }

        function renderTable() {
            const tbody = $('#tempData');
            tbody.html('');

            PBState.items.forEach((item, i) => {
                tbody.append(`
                    <tr>
                        <td class="text-center">
                            <button class="btn btn-danger btn-sm" onclick="removeItem('${item.id_barang}')">
                                <i class="fa fa-trash-alt"></i>
                            </button>
                        </td>
                        <td class="text-center">${i+1}</td>
                        <td>${item.barang_label}</td>
                        <td class="text-right">${item.qty}</td>
                        <td class="text-right">${formatRupiah(item.harga_barang)}</td>
                        <td class="text-right">${formatRupiah(item.subtotal)}</td>
                    </tr>
                `);
            });
        }

        function updateSubtotal() {
            const subtotal = PBState.items.reduce((s, i) => s + i.subtotal, 0);
            $('#subtotal').text(formatRupiah(subtotal));
        }

        async function savePembelian() {
            const url =
                "{{ route('tb.pb.put') }}";

            const payload = {
                id: PBState.pembelianId,
                ...PBState.header,
                items: PBState.items
            };

            await renderAPI(PBState.mode === 'add' ? 'POST' : 'PUT', url, payload);
        }

        function resetForm() {
            $('form')[0].reset();
            $('#tempData').empty();
            $('#subtotal').text('Rp 0');
        }

        $('#modal-form').on('hidden.bs.modal', function() {
            PBState.mode = 'add';
            PBState.items = [];
            PBState.addedItems.clear();
        });

        $('#detail-tab').on('shown.bs.tab', function() {
            $('#form-tambah-pembelian input, #form-tambah-pembelian select').prop('disabled', true);
        });

        $('#add-item-detail').off().on('click', addItemDetail);

        function setHpp() {
            $('#id_barang').on('change', getHppInfo);

            $('#jml_item').on('input', debounce(getHppInfo, 1000));

            $('#harga_barang').on('input', debounce(getHppInfo, 1000));
        }

        async function getHppInfo() {
            const idBarang = $('#id_barang').val();
            const tokoGroupID = PBState.header.tokoGroup;
            const qty = parseInt($('#jml_item').val()) || 0;
            const harga = parseInt($('#harga_barang').val().replace(/\./g, '')) || 0;

            if (!idBarang || qty <= 0 || harga <= 0) return;

            try {
                const res = await renderAPI('GET', "{{ route('sb.getHpp') }}", {
                    barang_id: idBarang,
                    qty: qty,
                    harga: harga,
                    toko_group_id: tokoGroupID
                });

                if (res && res.status === 200) {
                    const data = res.data;

                    $('.hpp-awal').text(formatRupiah(data.hpp_lama ?? 0));
                    $('.hpp-baru').text(formatRupiah(data.hpp_baru ?? 0));
                    $('.stock').text(data.stok_baru);

                    updateLevelHarga(data.hpp_baru);
                }

            } catch (e) {
                console.error('HPP API ERROR', e);
            }
        }

        function updateLevelHarga(hppBaru) {
            document.querySelectorAll('.level-harga').forEach((input, index) => {
                const val = parseInt(input.value.replace(/\./g, '')) || 0;
                const persen = hppBaru > 0 ? ((val - hppBaru) / hppBaru * 100).toFixed(2) : 0;
                document.getElementById('persen_' + index).innerText = persen + '%';
            });
        }

        function debounce(func, delay) {
            let timer;
            return function() {
                clearTimeout(timer);
                timer = setTimeout(func, delay);
            };
        }

        async function addItemDetail() {
            const idBarang = String($('#id_barang').val());
            const qty = Number($('#jml_item').val());
            const harga = Number($('#harga_barang').val().replace(/\./g, ''));

            if (!idBarang || qty <= 0 || harga <= 0) {
                return notificationAlert('warning', 'Error', 'Barang, qty dan harga wajib diisi');
            }

            if (PBState.addedItems.has(idBarang)) {
                return notificationAlert('warning', 'Error', 'Barang sudah ada di list');
            }

            // ✅ AMBIL LEVEL HARGA
            let levelHarga = [];
            $('.level-harga').each(function() {
                levelHarga.push(Number($(this).val().replace(/\./g, '')) || 0);
            });

            // HPP
            const hppAwal = parseRupiah($('.hpp-awal').text());
            const hppBaru = parseRupiah($('.hpp-baru').text());

            const payload = {
                id_pembelian: PBState.pembelianId,
                id_barang: idBarang,
                qty,
                harga_barang: harga,
                level_harga: levelHarga,
                hpp_awal: hppAwal,
                hpp_baru: hppBaru,
                toko_group_id: PBState.header.tokoGroup,
                supplier_id: PBState.header.supplier,
                kas_id: PBState.header.kas,
                nota: PBState.header.nota,
                tanggal: PBState.header.tgl,
                tipe: $('#tipe').val(),
                created_by: "{{ auth()->id() }}"
            };

            const res = await renderAPI('POST', "{{ route('tb.pb.temp.post') }}", payload);
            const temp = res.data.data;

            PBState.pembelianId = temp.pembelian_barang_id ?? PBState.pembelianId;

            const barangLabel = $('#id_barang option:selected').text();
            const subtotal = qty * harga;

            // ✅ SIMPAN KE STATE
            PBState.items.push({
                id_barang: idBarang,
                qty,
                harga_barang: harga,
                subtotal,
                barang_label: barangLabel,
                level_harga: levelHarga, // ⭐ PENTING
                hpp_awal: hppAwal,
                hpp_baru: hppBaru
            });

            PBState.addedItems.add(idBarang);

            renderTable();
            updateSubtotal();
            resetItemInput();

            setTimeout(async () => {
                await getListData(defaultLimitPage, currentPage, defaultAscending,
                    defaultSearch, customFilter);
            }, 500);
        }


        function resetLevelHargaPersen() {
            document.querySelectorAll('[id^="persen_"]').forEach(el => {
                el.innerText = '0%';
            });
        }

        function resetItemInput() {
            $('#id_barang').val(null).trigger('change');
            $('#jml_item').val('');
            $('#harga_barang').val('');
            $('.level-harga').val('');
            $('.stock').text('0');
            $('.hpp-awal').text('Rp 0');
            $('.hpp-baru').text('Rp 0');
        }

        $('#id_barang').on('change', async function() {
            let barangId = $(this).val();
            if (!barangId) return;

            await getDetailBarang(barangId);
        });


        async function getDetailBarang(barangId) {

            let tokoId = $('#toko_group').val(); // atau toko aktif

            try {
                let res = await renderAPI('GET', '{{ route('sb.getDetail') }}', {
                    barang_id: barangId,
                    toko_id: {{ auth()->user()->toko_id }}
                });

                let data = res.data;

                console.log('DETAIL BARANG:', data);

                // ================= STOCK INFO =================
                $('.hpp-awal').text(formatRupiah(data.hpp_awal ?? 0));
                $('.stock').text(data.stock);

                // ================= LEVEL HARGA =================
                // Misal input level harga dynamic
                setLevelHarga(data.level_harga);

            } catch (e) {
                console.error('Gagal get detail barang', e);
                notificationAlert('error', 'Error', 'Gagal mengambil detail barang');
            }
        }

        function setLevelHarga(levelHargaArray) {

            levelHargaArray.forEach((harga, index) => {
                let input = $(`#level_harga_${index}`);

                if (input.length) {
                    input.val(harga).trigger('input'); // trigger rupiah formatter
                }
            });
        }

        $('#btn-submit').on('click', async function() {
            await submitFinalPembelian();
        });

        async function submitFinalPembelian() {

            const btn = $('#btn-submit');
            const btnText = btn.find('.btn-text');
            const btnIcon = btn.find('i');

            // prevent double click
            if (btn.prop('disabled')) return;

            if (!PBState.pembelianId) {
                return notificationAlert('warning', 'Error', 'Pembelian belum dibuat');
            }

            if (PBState.items.length === 0) {
                return notificationAlert('warning', 'Error', 'Belum ada item pembelian');
            }

            const items = PBState.items.map(i => ({
                id_barang: Number(i.id_barang),
                qty: Number(i.qty),
                harga_barang: Number(i.harga_barang),
                level_harga: i.level_harga || []
            }));

            const payload = {
                id: PBState.pembelianId,
                toko_group_id: PBState.header.tokoGroup,
                toko_id: "{{ auth()->user()->toko_id }}",
                created_by: "{{ auth()->id() }}",
                nota: PBState.header.nota,
                tipe: $('#tipe').val(),
                supplier_id: PBState.header.supplier,
                tanggal: PBState.header.tgl,
                items
            };

            console.log("FINAL PAYLOAD:", payload);

            // 🔥 LOADING STATE
            btn.prop('disabled', true);
            btnIcon.removeClass('fa-save').addClass('fa-spinner fa-spin');
            btnText.text('Menyimpan...');

            try {
                const res = await renderAPI('PUT', "{{ route('tb.pb.put') }}", payload);

                notificationAlert('success', 'Success', res.message || 'Pembelian berhasil disimpan');

                $('#modal-form').modal('hide');

                setTimeout(async () => {
                    await getListData(defaultLimitPage, currentPage, defaultAscending,
                        defaultSearch, customFilter);
                }, 500);
            } catch (e) {
                console.error('FINAL SAVE ERROR', e);

                if (e.responseJSON?.errors) {
                    console.log("VALIDATION ERRORS:", e.responseJSON.errors);
                }

                notificationAlert('error', 'Error', 'Gagal simpan pembelian final');

            } finally {
                // 🔄 RESTORE BUTTON
                btn.prop('disabled', false);
                btnIcon.removeClass('fa-spinner fa-spin').addClass('fa-save');
                btnText.text('Simpan');
            }
        }

        $(document).on('input', '.level-harga', function() {
            const hppBaru = Number($('.hpp-baru').text().replace(/\D/g, '')) || 0;

            const index = $(this).data('index');
            const val = Number($(this).val().replace(/\./g, '')) || 0;

            let persen = 0;
            if (hppBaru > 0) {
                persen = ((val - hppBaru) / hppBaru * 100).toFixed(2);
            }

            $('#persen_' + index).text(persen + '%');
        });


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

        $('#modal-form').on('hidden.bs.modal', function() {
            // reset form tambah
            $('#form-tambah-pembelian')[0].reset();
            $('#id_supplier, #toko_group, #kas').val(null).trigger('change');
            $('#id_supplier, #no_nota, #tgl_nota, #toko_group, #kas, #tipe, #btn-next-step').prop('disabled',
                false);

            // reset input detail
            $('#id_barang').val(null).trigger('change');
            $('#jml_item').val('');
            $('#harga_barang').val('');

            // reset level harga
            $('.level-harga').val('');
            $('[id^=persen_]').text('0%');

            // reset info stok
            $('.stock').text('0');
            $('.hpp-awal').text('Rp 0');
            $('.hpp-baru').text('Rp 0');

            // reset table
            $('#tempData').html('');
            $('#subtotal').text('Rp 0');

            // reset tab ke awal
            $('#tambah-tab').tab('show');

            resetPBState();
        });

        function resetPBState() {
            PBState.mode = 'add';
            PBState.pembelianId = null;
            PBState.header = {};
            PBState.items.length = 0;
            PBState.addedItems.clear();
        }

        async function initPageLoad() {
            await Promise.all([
                setDynamicButton(),
                getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch, customFilter),
                searchList(),
                filterList(),
            ]);
        }
    </script>
@endsection
