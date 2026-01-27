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
                                        <button class="btn btn-primary mb-2 mb-lg-0 text-white add-data custom-btn-tambah"
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
                <a button class="p-1 btn edit-data action_button"
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

        function selectBarang() {
            let data = [{
                id: '#id_barang',
                isFilter: {
                    id_toko: '{{ auth()->user()->toko_id }}',
                    jenis_barang_id: selectedKas.jenis_id,
                    with: 'barcode',
                },
                isUrl: '{{ route('master.barang') }}',
                placeholder: 'Pilih Barang',
                isModal: '#modal-form',
                isForm: true
            }]

            selectData(data);
        }

        async function showData() {
            $("#btn-next-step").on("click", async function() {
                let supplier = $("#id_supplier").val();
                let tokoGroup = $("#toko_group").val();
                let supplierName = $("#id_supplier option:selected").text();
                let tokoGroupName = $("#toko_group option:selected").text();
                let noNota = $("#no_nota").val();
                let tglNota = $("#tgl_nota").val();
                let tipeTransaksi = $("#tipe option:selected").text();
                let kas = $("#kas").val();
                let kasName = $("#kas option:selected").text();
                let kasJenisId = $("#kas").select2('data')[0].jenis_id;
                let kasSaldo = $("#kas").select2('data')[0].saldo_kas;

                selectedSupplier = supplier;
                selectedTokoGroup = tokoGroup;
                selectedKas.id = kas;
                selectedKas.jenis_id = kasJenisId;
                selectedKas.saldo_kas = kasSaldo;

                if (!supplier || !noNota || !tglNota || !tokoGroup) {
                    notificationAlert('warning', 'Error', 'Mohon lengkapi semua data pada Step Pertama');
                    return;
                }

                $("#no-nota").text(noNota);
                $("#nama-supplier").text(supplierName);
                $("#toko-group").text(tokoGroupName);
                $("#sumber-dana").text(kasName);
                $("#tgl-nota").text(tglNota);
                $("#tipe-transaksi").text(tipeTransaksi);
                $("#tambah-tab")
                    .removeClass("active show")
                    .attr("aria-selected", "false")
                    .addClass("disabled")
                    .css({
                        "opacity": "0.6",
                        "pointer-events": "none"
                    });

                $("#tambah").removeClass("active show");

                $("#detail-tab")
                    .removeClass("disabled")
                    .css({
                        "opacity": "1",
                        "pointer-events": "auto"
                    })
                    .addClass("active show")
                    .attr("aria-selected", "true")
                    .tab("show");

                $("#detail").addClass("active show");

                await selectBarang();
            });

            $(document).on("click", ".add-data", function() {
                $("#modal-title").html(`<i class="fa fa-circle-plus mr-1"></i>Form Pembelian Barang`);
                $("#modal-form").modal("show");
                $("#form-tambah-pembelian")[0].reset();

                let tglNotaValue = $("#tgl_nota").val();
                let tipeValue = $("#tipe").val();

                $("#form-tambah-pembelian input").val("");
                $("#form-tambah-pembelian select").val("").trigger("change");

                $("#tgl_nota").val(tglNotaValue);
                $("#tipe").val(tipeValue);
                $("#jml_item").val('');
                $("#harga_barang").val('');
                $('#subtotal').empty();

                $("#id_supplier").val(null).trigger("change");
                $("#toko_group").val(null).trigger("change");
                $("#kas").val(null).trigger("change");
                $("#tempData").empty();

                $("#item-container .item-group").not(':first').remove();
                let $firstItem = $("#item-container .item-group").first();
                $firstItem.find("select").val(null).trigger("change");
                $firstItem.find("input").val("");

                $(".stock").text("0");
                $(".hpp-awal").text("Rp 0");
                $(".hpp-baru").text("Rp 0");

                $(".level-harga").val("");
                $("[id^='persen_']").text("0%");

                $("#tambah-tab").removeClass("d-none disabled")
                    .addClass("active show")
                    .attr("aria-selected", "true");
                $("#tambah").addClass("active show");

                $("#detail-tab").addClass("disabled")
                    .removeClass("active")
                    .attr("aria-selected", "false")
                    .css({
                        "pointer-events": "none",
                        "opacity": "0.5"
                    });
                $("#detail").removeClass("active show");
                setDatePicker('tgl_nota');
            });
        }

        async function editData() {
            $(document).on("click", ".edit-data", async function() {
                let id = $(this).attr("data-id");
                let nota = $(this).attr("data-nota");
                let tanggal = $(this).attr("data-tanggal");
                let tipe = $(this).attr("data-tipe");
                let nama = $(this).attr("data-name");

                $("#modal-title").html(
                    `<i class="fa fa-edit mr-1"></i>Form Edit Pembelian No. Nota: ${nota}`);
                $("#modal-form").modal("show");

                $("form").find("input:not(#tgl_nota):not([type='hidden']), select, textarea")
                    .val("")
                    .prop("checked", false)
                    .trigger("change");

                $("#jml_item").val('');
                $("#harga_barang").val('');
                $("#tempData").empty();
                $('#subtotal').empty();
                $("#tambah-tab").removeClass("active").addClass("d-none");
                $("#tambah").removeClass("show active");
                $("#detail-tab").removeClass("disabled").addClass("active").css({
                    "pointer-events": "auto",
                    "opacity": "1",
                });
                $("#detail").addClass("show active");
                $("#submit-reture").removeClass("d-none");
                try {
                    const response = await renderAPI('GET', '{{ route('tb.pb.temp.get') }}', {
                        pembelian_barang_id: id
                    });
                    if (response && response.status === 200) {
                        const data = response.data.data;

                        $("#no-nota").html(data.nota);
                        $("#tgl-nota").html(data.tanggal);
                        $("#tipe-transaksi").html(data.tipe);
                        $("#nama-supplier").html(data.suplier);
                        $("#sumber-dana").html(data.kas);
                        $("#toko-group").html(data.toko_group);
                        $('#nama-supplier').data('supplier-id', data.suplier_id);
                        idPembelianEdit = data.id;
                        selectedKas.jenis_id = data.jenis_barang_id;
                        selectedKas.id = data.kas_id;
                        tokoGroupID = data.toko_group_id;
                        selectedTokoGroup = data.toko_group_id;
                        selectedTokoGroup = data.toko_group_id;
                        selectedSupplier = data.suplier_id;
                        pembelianBarangId = data.id;
                        rowGlobal = data.detail;
                        let totalHargaAll = 0;

                        rowGlobal.forEach(item => {
                            const totalHarga = item.subtotal;
                            totalHargaAll += totalHarga;

                            $("#tempData").append(`
                                <tr data-barang="${item.barang_id}">
                                    <td class="text-center"><button onclick="removeRow({id_pembelian: '${pembelianBarangId}', id_barang: '${item.barang_id}', toko_group_id: '${tokoGroupID}' })" type="button" class="btn btn-danger btn-sm remove-item"><i class="fa fa-trash-alt mr-1"></i>Hapus</button></td>
                                    <td class="text-center numbered">${$("#tempData tr").length + 1}</td>
                                    <td><input type="hidden" name="id_barang[]" value="${item.barang_id}">${item.barang.nama}</td>
                                    <td class="text-right"><input type="hidden" name="qty[]" value="${item.qty}">${item.qty}</td>
                                    <td class="text-right"><input type="hidden" name="harga_barang[]" value="${item.harga_beli}">${formatRupiah(item.harga_beli)}</td>
                                    <td class="text-right">${formatRupiah(item.subtotal)}</td>
                                </tr>
                            `);
                        });

                        $("#subtotal").html(formatRupiah(totalHargaAll));
                    } else {
                        notificationAlert('info', 'Pemberitahuan', 'Tidak ada data sementara ditemukan.');
                    }
                } catch (error) {
                    const errorMessage = error?.response?.data?.message ||
                        'Terjadi kesalahan saat memuat data sementara.';
                    notificationAlert('error', 'Error', errorMessage);
                }
                await selectBarang();
            });
        }

        async function saveData() {
            $(document).off('click', '#btn-submit').on('click', '#btn-submit', async function(e) {
                e.preventDefault();

                const $btn = $(this);
                $btn.prop('disabled', true).html(
                    `<span class="spinner-border spinner-border-sm" role="status"></span> Menyimpan..`
                );

                try {
                    const dataHeader = {
                        nota: $('#no-nota').text(),
                        tanggal: $('#tgl-nota').text(),
                        supplier_id: selectedSupplier,
                        toko_group_id: selectedTokoGroup,
                        tipe: $('#tipe-transaksi').text().toLowerCase(),
                        toko_id: {{ auth()->user()->toko_id }},
                        created_by: {{ auth()->user()->id }}
                    };

                    let detailItems = [];

                    $('#tempData tr').each(function() {
                        const row = $(this);

                        let id_barang = row.find('input[name="id_barang[]"]').val();
                        let qty = row.find('input[name="qty[]"]').val();
                        let harga_barang = row.find('input[name="harga_barang[]"]').val();

                        let level_harga = [];
                        row.find('input[name^="level_harga"]').each(function() {
                            level_harga.push($(this).val().replace(/\./g, ''));
                        });

                        detailItems.push({
                            id_barang,
                            qty,
                            harga_barang,
                            level_harga
                        });
                    });

                    const payload = {
                        id: pembelianBarangId,
                        id_pembelian: pembelianBarangId,
                        ...dataHeader,
                        items: detailItems
                    };

                    let updateUrl = "{{ route('tb.pb.put') }}";

                    const postData = await renderAPI('PUT', updateUrl, payload);

                    if (postData && postData.status >= 200 && postData.status < 300) {

                        notificationAlert(
                            'success',
                            'Berhasil',
                            postData.data.message || 'Data berhasil diperbarui'
                        );

                        $('#modal-form').modal('hide');

                        setTimeout(() => {
                            getListData(defaultLimitPage, currentPage, defaultAscending,
                                defaultSearch, customFilter);
                        }, 500);
                        pembelianBarangId = null;
                        addedItems.clear();
                    } else {
                        notificationAlert(
                            'warning',
                            'Terjadi Kesalahan',
                            postData?.data.message || 'Terjadi kesalahan saat menyimpan data'
                        );
                    }

                } catch (err) {
                    notificationAlert(
                        'error',
                        'Gagal',
                        'Terjadi kesalahan saat menghubungi server'
                    );

                } finally {
                    $btn.prop('disabled', false).html('Simpan');
                }

            });
        }

        $('#modal-form').on('hidden.bs.modal', function() {
            addedItems.clear();
        });

        $('#label_kas').on('select2:clear', function() {
            $('#jenis_id').remove();
        });

        async function deleteData() {
            $(document).on("click", ".delete-data", async function() {
                let rawData = $(this).attr("data");
                let data = JSON.parse(decodeURIComponent(rawData));

                swal({
                    title: `Hapus ${title} No Nota: ${data.no_nota}`,
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
                        `/admin/pembelianbarang/${data.id}/delete`, {}
                    ).then(function(response) {
                        return response;
                    }).catch(function(error) {
                        let resp = error.response;
                        return resp;
                    });

                    if (postDataRest.status == 200) {
                        setTimeout(function() {
                            getListData(defaultLimitPage, currentPage, defaultAscending,
                                defaultSearch, customFilter);
                        }, 500);
                        notificationAlert('success', 'Pemberitahuan', postDataRest.data
                            .message);
                    }
                }).catch(swal.noop);
            })
        }

        async function addTemporaryField() {
            try {
                let idBarang = document.getElementById('id_barang').value;
                let selectedOption = document.getElementById('id_barang').selectedOptions[0];

                let namaBarang = selectedOption?.text || '';
                let qty = parseFloat(document.getElementById('jml_item').value);

                let hargaBarang = parseFloat(
                    $('#harga_barang').val().replace(/\./g, '')
                );

                let levelHarga = Array.from(document.querySelectorAll('.level-harga'))
                    .map(input =>
                        parseFloat(input.value.replace(/\./g, ''))
                    )
                    .filter(val => !isNaN(val) && val > 0);

                let hppAwalText = document.querySelector('.hpp-awal')?.textContent || 'Rp 0';
                let hppBaruText = document.querySelector('.hpp-baru')?.textContent || 'Rp 0';

                let parseRupiah = (text) =>
                    parseFloat(
                        text
                        .replace(/[Rp\s]/gi, '')
                        .replace(/\./g, '')
                        .replace(',', '.')
                    ) || 0;

                let hppAwal = parseRupiah(hppAwalText);
                let hppBaru = parseRupiah(hppBaruText);

                if (!idBarang || qty <= 0 || hargaBarang <= 0) {
                    notificationAlert(
                        'error',
                        'Pemberitahuan',
                        'Pastikan semua data telah diisi dengan benar.'
                    );
                    return;
                }

                let formData = {
                    id_pembelian: pembelianBarangId || idPembelianEdit,
                    id_barang: idBarang,
                    nama_barang: namaBarang,
                    qty: qty,
                    harga_barang: hargaBarang,

                    level_harga: levelHarga,

                    hpp_awal: hppAwal,
                    hpp_baru: hppBaru,

                    created_by: {{ auth()->user()->id }},
                    supplier_id: selectedSupplier,
                    toko_group_id: selectedTokoGroup,
                    nota: $('#no-nota').text(),
                    tanggal: $('#tgl-nota').text(),
                    tipe: $('#tipe-transaksi').text(),
                    kas_id: selectedKas.id
                };

                const postData = await renderAPI(
                    'POST',
                    '{{ route('tb.pb.temp.post') }}',
                    formData
                );

                if (postData.status >= 200 && postData.status < 300) {
                    const response = postData.data.data;

                    setTimeout(async () => {
                        await getListData(
                            defaultLimitPage,
                            currentPage,
                            defaultAscending,
                            defaultSearch,
                            customFilter
                        );
                    }, 500);

                    pembelianBarangId = response.pembelian_barang_id;
                } else {
                    notificationAlert(
                        'info',
                        'Pemberitahuan',
                        postData.message || 'Terjadi kesalahan'
                    );
                }
            } catch (error) {
                loadingPage(false);
                const resp = error.response || {};
                notificationAlert(
                    'error',
                    'Kesalahan',
                    resp.data?.message || 'Terjadi kesalahan saat menyimpan data.'
                );
            }
        }

        async function removeRow(rowData) {
            const {
                id_pembelian,
                id_barang,
                toko_group_id
            } = rowData;
            const idBarang = String(id_barang);

            await deleteRowTable({
                id_pembelian,
                id_barang,
                toko_group_id
            });

            if (!Array.isArray(rowGlobal)) rowGlobal = [];

            rowGlobal = rowGlobal.filter(row => String(row.id_barang) !== idBarang);

            addedItems.delete(idBarang);

            updateSubtotalAfterRemoval();
        }

        async function deleteRowTable(data) {
            try {
                const postDataRest = await renderAPI(
                    'DELETE',
                    '{{ route('tb.pb.temp.delete') }}',
                    data
                );
                if (postDataRest && postDataRest.status === 200) {
                    const row = document.querySelector(`tr[data-barang="${data.id_barang}"]`);
                    if (row) {
                        row.remove();
                    }
                }
            } catch (error) {
                const resp = error.response;
                const errorMessage = resp?.data?.message || 'Terjadi kesalahan saat menghapus data.';
                notificationAlert('error', 'Kesalahan', errorMessage);
            }
        }

        function updateSubtotalAfterRemoval() {
            let subtotal = 0;
            document.querySelectorAll('.table-bordered tbody tr').forEach((row) => {
                let hargaPerItem = parseInt(row.children[6].textContent.replace(/\D/g, '')) || 0;
                subtotal += hargaPerItem;
            });

            document.querySelector('.table-bordered tfoot tr th:last-child').textContent =
                formatRupiah(subtotal);
        }

        function updateSubTotal() {
            let subtotal = 0;
            $(".total-harga").each(function() {
                subtotal += parseInt($(this).data("harga"));
            });
            $("#subtotal").html(formatRupiah(subtotal));
        }

        async function addData() {
            let subtotal = 0;
            let initialHppBaru = 0;
            let initialStock = 0;
            let initialHppAwal = 0;

            let debounceTimer;
            const debounceDelay = 500;

            function toggleInputFields(disabled) {
                if (disabled = false) {
                    document.getElementById('jml_item').value = '';
                    document.getElementById('harga_barang').value = '';
                } else {
                    document.getElementById('jml_item').disabled = disabled;
                    document.getElementById('harga_barang').disabled = disabled;
                }
            }

            function checkInputFields() {
                let idBarang = document.getElementById('id_barang').value;
                let isItemAdded = addedItems.has(idBarang);
                // toggleInputFields(isItemAdded);
            }

            document.getElementById('add-item-detail').addEventListener('click', async function() {
                let btn = this;
                let originalText = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = `<i class="fa fa-spinner fa-spin"></i> Proses...`;

                try {
                    const select = document.getElementById('id_barang');
                    let idBarang = String(select.value);
                    let selectedOption = select.selectedOptions[0];

                    let namaBarang = selectedOption?.text || '';
                    let qty = parseInt(document.getElementById('jml_item').value) || 0;
                    let harga = parseInt($('#harga_barang').val().replace(/\./g, '')) || 0;

                    // ✅ Validasi
                    if (!idBarang) return showError('Silakan pilih barang terlebih dahulu.');
                    if (!qty || !harga) return showError('Jumlah dan harga wajib diisi.');

                    // ✅ Check duplicate (STATE)
                    if (addedItems.has(idBarang)) return showError('Barang sudah ada di daftar!');

                    // ✅ Check level harga
                    let allLevelsFilled = [...document.querySelectorAll('.level-harga')].every(i => i
                        .value !== '');
                    if (!allLevelsFilled) return showError('Level harga harus diisi!');

                    // 🔥 Simpan ke server
                    await addTemporaryField(id_pembelian_post);

                    // ✅ Update STATE
                    let totalHarga = qty * harga;
                    addedItems.add(idBarang);
                    rowGlobal.push({
                        id_barang: idBarang,
                        qty,
                        harga,
                        total: totalHarga
                    });

                    // 🔥 Hide option supaya tidak bisa dipilih ulang
                    selectedOption.setAttribute('hidden', true);

                    // ✅ Build level harga hidden inputs
                    let levelHargaInputs = '';
                    document.querySelectorAll('.level-harga').forEach(input => {
                        levelHargaInputs +=
                            `<input type="hidden" name="level_harga[${idBarang}][]" value="${input.value}">`;
                    });

                    // ✅ Render row (IMPORTANT: data-barang attribute)
                    let row = `
        <tr data-barang="${idBarang}">
            <td class="text-center">
                <button onclick="removeRow({id_pembelian:'${pembelianBarangId}', id_barang:'${idBarang}', toko_group_id:'${tokoGroupID}'})"
                    type="button" class="btn btn-danger btn-sm">
                    <i class="fa fa-trash-alt"></i> Hapus
                </button>
            </td>
            <td class="numbered text-center">${document.querySelectorAll('.table-bordered tbody tr').length + 1}</td>
            <td><input type="hidden" name="id_barang[]" value="${idBarang}">${namaBarang}</td>
            <td class="text-right"><input type="hidden" name="qty[]" value="${qty}">${qty}</td>
            <td class="text-right"><input type="hidden" name="harga_barang[]" value="${harga}">Rp ${harga.toLocaleString('id-ID')}</td>
            <td class="text-right harga-total">Rp ${totalHarga.toLocaleString('id-ID')}</td>
            ${levelHargaInputs}
        </tr>`;

                    document.querySelector('.table-bordered tbody').insertAdjacentHTML('beforeend', row);

                    // ✅ Update subtotal dari STATE
                    updateSubtotal();

                    // Reset form
                    select.value = '';
                    $('#id_barang').val(null).trigger('change');
                    resetFields();
                    updateNumbers();
                    toggleInputFields(true);

                } catch (err) {
                    console.error(err);
                    notificationAlert('error', 'Kesalahan', 'Terjadi kesalahan saat memproses data.');
                } finally {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }

                function updateSubtotal() {
                    let subtotal = rowGlobal.reduce((sum, r) => {
                        return sum + Number(r.total || 0);
                    }, 0);

                    document.querySelector('.table-bordered tfoot tr th:last-child').textContent =
                        formatRupiah(subtotal);
                }

                function showError(msg) {
                    notificationAlert('error', 'Pemberitahuan', msg);
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    throw new Error(msg);
                }
            });


            document.querySelector('.table-bordered tbody').addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-item')) {
                    let row = e.target.closest('tr');
                    let idBarang = row.querySelector('input[name="id_barang[]"]').value;
                    let qty = row.querySelector('input[name="qty[]"]').value;
                    let harga = row.querySelector('input[name="harga_barang[]"]').value;
                    let totalHarga = parseInt(row.querySelector('td:nth-child(6)').textContent.replace(
                        /\D/g, ''));

                    subtotal -= totalHarga;
                    row.remove();

                    addedItems.delete(idBarang);

                    let optionElement = document.querySelector(`#id_barang option[value="${idBarang}"]`);
                    if (optionElement) {
                        optionElement.removeAttribute('hidden');
                    }

                    document.querySelector('.table-bordered tfoot tr th:last-child').textContent =
                        `Rp ${subtotal.toLocaleString('id-ID')}`;

                    updateNumbers();

                    if (!addedItems.size) {
                        toggleInputFields(false);
                    } else {
                        checkInputFields();
                    }
                }
            });

            $('#id_barang').on('select2:select', async function() {

                checkInputFields();
                $('#jml_item').val('');
                $('#harga_barang').val('');

                let idBarang = $(this).val();

                const baseUrl = "{{ route('sb.getDetail') }}";

                if (idBarang) {
                    let resp = await renderAPI('GET', baseUrl, {
                            barang_id: idBarang,
                            toko_id: {{ auth()->user()->toko_id }}
                        })
                        .then(r => r)
                        .catch(e => e.response);

                    let data = resp?.data || resp;

                    if (!data) {
                        return;
                    }

                    initialHppBaru = data.hpp_baru || 0;
                    initialStock = data.stock || 0;
                    initialHppAwal = data.hpp_awal || 0;

                    document.querySelector('.card-text strong.stock').textContent =
                        initialStock.toLocaleString('id-ID');
                    document.querySelector('.card-text strong.hpp-awal').textContent = formatRupiah(
                        initialHppAwal);
                    document.querySelector('.card-text strong.hpp-baru').textContent = formatRupiah(
                        initialHppBaru);

                    document.querySelectorAll('.level-harga').forEach(input => {
                        input.setAttribute('data-hpp-baru', initialHppBaru);
                    });

                    originalLevelHarga = {
                        ...data.level_harga
                    };
                    const levelHargaValues = Object.values(data.level_harga);

                    document.querySelectorAll('input[name="level_harga[]"]').forEach(function(inputField,
                        index) {
                        const persenElement = document.querySelector(`#persen_${index}`);
                        const harga = parseFloat(levelHargaValues[index]) || 0;

                        inputField.value = formatNumberID(harga);

                        let persen = 0;
                        if (initialHppAwal > 0 && harga > 0) {
                            persen = ((harga - initialHppAwal) / initialHppAwal) * 100;
                        }

                        if (persenElement) {
                            persenElement.textContent = `${persen.toFixed(2)}%`;
                        }
                    });

                    setupInputListeners(data.total_harga_success, data.total_qty_success);

                } else {
                    resetFields();
                }
            });

            function formatNumberID(value) {
                return Number(value || 0).toLocaleString('id-ID');
            }

            document.querySelectorAll('.level-harga').forEach(function(input) {
                input.addEventListener('input', function() {
                    let hppAwal = initialHppAwal || 0;
                    let hppBaru = parseFloat(input.getAttribute('data-hpp-baru')) || 0;
                    let levelHarga = parseFloat(this.value.replace(/\./g, '')) || 0;

                    let persen = 0;

                    if (hppBaru === 0 && hppAwal > 0) {
                        persen = ((levelHarga - hppAwal) / hppAwal) * 100;
                    } else if (hppBaru > 0) {
                        persen = ((levelHarga - hppBaru) / hppBaru) * 100;
                    }

                    const index = this.getAttribute('data-index');
                    const persenElement = document.getElementById(`persen_${index}`);
                    if (persenElement) {
                        persenElement.textContent = `${persen.toFixed(2)}%`;
                    }
                });
            });

            function setupInputListeners(totalHarga, totalQty) {
                document.querySelectorAll('.jumlah-item, .harga-barang').forEach(function(input) {
                    input.addEventListener('input', function() {
                        calculateHPP(totalHarga, totalQty);
                    });
                });
            }

            document.querySelectorAll('.jumlah-item, .harga-barang').forEach(function(input) {
                input.addEventListener('input', function() {
                    calculateHPP(0,
                        0
                    );
                });
            });

            async function calculateHPP(totalHarga, totalQty) {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(async () => {
                    let id_barang = parseFloat(document.querySelector('.id-barang').value) || 0;
                    let jumlah = parseFloat(document.querySelector('.jumlah-item').value) || 0;
                    let harga = parseFloat($('.harga-barang').val().replace(/\./g, '')) || 0;

                    let hppAwal = initialHppAwal || 0;

                    if (jumlah > 0 && harga > 0) {
                        try {
                            let getDataRest = await renderAPI('GET',
                                '{{ route('sb.getHpp') }}', {
                                    barang_id: id_barang,
                                    qty: jumlah,
                                    harga: harga,
                                    toko_group_id: selectedTokoGroup
                                });

                            if (getDataRest && getDataRest.status === 200) {
                                let finalHpp = getDataRest.data.hpp_baru;

                                document.querySelector('.card-text strong.hpp-baru').textContent =
                                    formatRupiah(finalHpp);

                                document.querySelectorAll('.level-harga').forEach(function(input) {
                                    input.setAttribute('data-hpp-baru', finalHpp);
                                });

                                updatePercentages(finalHpp);
                                return;
                            }
                        } catch (error) {
                            let finalHpp = harga;

                            document.querySelector('.card-text strong.hpp-baru').textContent =
                                formatRupiah(finalHpp);

                            document.querySelectorAll('.level-harga').forEach(function(input) {
                                input.setAttribute('data-hpp-baru', finalHpp);
                            });

                            updatePercentages(finalHpp);
                            return;
                        }
                    }

                    document.querySelector('.card-text strong.hpp-baru').textContent =
                        `Rp ${initialHppBaru.toLocaleString('id-ID')}`;

                    document.querySelectorAll('.level-harga').forEach(function(input) {
                        input.setAttribute('data-hpp-baru', hppAwal);
                    });

                    updatePercentages(hppAwal);
                }, debounceDelay);
            }

            function updatePercentages(hpp) {
                document.querySelectorAll('.level-harga').forEach(function(input) {
                    let levelHarga = parseFloat(input.value.replace(/\./g, '')) || 0;
                    let persen = 0;
                    if (hpp > 0) {
                        persen = ((levelHarga - hpp) / hpp) * 100;
                    }

                    const persenElement = document.getElementById(
                        `persen_${input.getAttribute('data-index')}`);
                    if (persenElement) {
                        persenElement.textContent = `${persen.toFixed(2)}%`;
                    }
                });
            }

            function updateNumbers() {
                document.querySelectorAll('.table-bordered tbody tr .numbered').forEach((element, index) => {
                    element.textContent = index + 1;
                });
            }

            function resetFields() {
                document.querySelector('.card-text strong.stock').textContent = '0';
                document.querySelector('.card-text strong.hpp-awal').textContent = 'Rp 0';
                document.querySelector('.card-text strong.hpp-baru').textContent = 'Rp 0';

                document.querySelectorAll('.level-harga').forEach(function(input) {
                    input.value = '';

                    const index = input.getAttribute('data-index');
                    const persenElement = document.getElementById(`persen_${index}`);

                    if (persenElement) {
                        persenElement.textContent = '0%';
                    }
                });
            }

            function resetFieldsToOriginal() {
                let currentHppBaru = parseFloat(document.querySelector('.card-text strong.hpp-baru').textContent
                    .replace(/\D/g, ''));
                let hppUntukPerhitungan = initialHppAwal;
                let awal = 0;

                if (currentHppBaru && currentHppBaru > 0) {
                    hppUntukPerhitungan = currentHppBaru;
                }

                document.querySelector('.jumlah-item').value = '';
                document.querySelector('.harga-barang').value = '';

                document.querySelector('.card-text strong.stock').textContent = initialStock.toLocaleString(
                    'id-ID');
                document.querySelector('.card-text strong.hpp-awal').textContent =
                    `Rp ${initialHppAwal.toLocaleString('id-ID')}`;
                document.querySelector('.card-text strong.hpp-baru').textContent =
                    `Rp ${awal.toLocaleString('id-ID')}`;

                document.querySelectorAll('input[name="level_nama[]"]').forEach(function(namaLevelInput, index) {
                    const namaLevel = namaLevelInput.value.replace(/\./g, '');
                    const inputField = document.querySelectorAll('input[name="level_harga[]"]')[index];
                    const persenElement = document.querySelector(`#persen_${index}`);

                    if (originalLevelHarga.hasOwnProperty(namaLevel)) {
                        inputField.value = originalLevelHarga[namaLevel] ||
                            0;
                        let levelHarga = parseFloat(inputField.value.replace(/\./g, '')) || 0;
                        let persen = 0;
                        if (hppUntukPerhitungan > 0) {
                            persen = ((levelHarga - hppUntukPerhitungan) / hppUntukPerhitungan) * 100;
                        }
                        persenElement.textContent = `${persen.toFixed(2)}%`;
                    } else {
                        inputField.value = 0;
                        persenElement.textContent = '0%';
                    }
                });
            }

            document.getElementById('reset').addEventListener('click', function() {
                let idBarang = document.getElementById('id_barang').value;
                if (idBarang) {
                    resetFieldsToOriginal();
                } else {
                    resetFields();
                }
            });
        }

        function setHpp() {
            $('#id_barang').on('change', getHppInfo);

            $('#jml_item').on('input', debounce(getHppInfo, 500));

            $('#harga_barang').on('input', debounce(getHppInfo, 500));
        }

        async function getHppInfo() {
            const idBarang = $('#id_barang').val();
            const qty = parseInt($('#jml_item').val()) || 0;
            const harga = parseInt($('#harga_barang').val().replace(/\./g, '')) || 0;

            if (!idBarang || qty <= 0 || harga <= 0) return;

            try {
                const res = await renderAPI('POST', "{{ route('sb.getHpp') }}", {
                    id_barang: idBarang,
                    qty: qty,
                    harga: harga
                });

                if (res && res.status === 200) {
                    const data = res.data;

                    $('.stock').text(data.stok_baru);
                    $('.hpp-awal').text(formatRupiah(data.hpp_baru - ((harga * qty) / data.stok_baru)));
                    $('.hpp-baru').text(formatRupiah(data.hpp_baru));

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

        async function waitForPembelianBarangId() {
            return new Promise(resolve => {
                const checkInterval = setInterval(() => {
                    if (pembelianBarangId !== null && pembelianBarangId !== undefined) {
                        clearInterval(checkInterval);
                        resolve(true);
                    }
                }, 100);
            });
        }

        async function initSaveDataWatcher() {
            await waitForPembelianBarangId();
            saveData();
        }

        async function initPageLoad() {
            await Promise.all([
                setDynamicButton(),
                getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch, customFilter),
                searchList(),
                filterList(),
                addData(),
                selectData(selectOptions),
                showData(),
                editData(),
                deleteData(),
                initSaveDataWatcher(),
            ]);
        }
    </script>
@endsection
