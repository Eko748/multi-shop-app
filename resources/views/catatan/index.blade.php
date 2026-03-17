@extends('layouts.main')

@section('title')
    {{ $title }}
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('css/button-action.css') }}">
    <link rel="stylesheet" href="{{ asset('css/table.css') }}">
    <link rel="stylesheet" href="{{ asset('css/daterange-picker.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sweetalert2.css') }}">
    <link rel="stylesheet" href="{{ asset('css/notyf.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/glossy.css') }}">
    <style>
        .todo-container {
            width: 100%;
            padding: 10px 20px;
        }

        .todo-item {
            margin-bottom: 10px;
        }

        .todo-done {
            border-left: 4px solid #28a745;
            background: #f8f9fa;
        }

        .todo-text {
            font-weight: 500;
        }

        .todo-done .todo-text {
            text-decoration: line-through;
        }

        .todo-item:hover {
            transform: translateY(-2px);
            transition: 0.2s;
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
                                    <h5 class="m-0">List {{ $title }}</h5>
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

    <div id="modal-form" class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog"
        aria-labelledby="myLargeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalLabel">Tambah {{ $title }}</h5>
                    <button type="button" class="btn-close reset-all close" data-bs-dismiss="modal"
                        aria-label="Close"><i class="fa fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <form id="form-data">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close"><i
                            class="fa fa-times mr-1"></i>Tutup</button>
                    <button type="submit" form="form-data" class="btn btn-success" id="save-btn">Simpan</button>
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
        let title = 'Catatan';
        let scannedBarang = null;
        let defaultLimitPage = 30;
        let currentPage = 1;
        let totalPage = 1;
        let defaultAscending = 0;
        let defaultSearch = '';
        let customFilter = {};
        let selectOptions = [{
            id: '#toko_tujuan_id',
            isUrl: '{{ route('master.toko') }}',
            placeholder: 'Pilih Toko',
            isModal: '#modal-form',
            isFilter: {
                not_self: {{ auth()->user()->toko_id }},
            },
        }, ];
        const notyf = new Notyf({
            duration: 3000,
            position: {
                x: 'center',
                y: 'top',
            }
        });
        const userId = {{ auth()->user()->id }};
        const tokoId = {{ auth()->user()->toko_id }};

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
                '{{ route('catatan.get') }}', {
                    page: page,
                    limit: limit,
                    ascending: ascending,
                    search: search,
                    toko_id: tokoId,
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
                if (getDataRest.data.data.length == 0) {
                    $('#listData').html(`
                    <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
                        <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
                            <div class="text-center my-3" role="alert">
                                Tidak ada ${title}.
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

            let edit_button = '';
            let delete_button = '';
            let checkbox = '';

            if (data.akses === 'sender') {

                edit_button = `
        <a class="btn btn-sm btn-warning edit-data"
            onClick="openEditModal('${encodeURIComponent(JSON.stringify(data))}')">
            <i class="fa fa-edit"></i>
        </a>`;

                delete_button = `
        <a class="btn btn-sm btn-danger hapus-data"
            data-id="${data.id}"
            data-name="${data.keterangan}">
            <i class="fa fa-trash-alt"></i>
        </a>`;
            }

            if (data.akses === 'receiver' && data.is_read == false) {

                checkbox = `
                <a class="btn btn-sm btn-success read-data"
                    data-id="${data.id}"
                    data-name="${data.keterangan}">
                    <i class="fa fa-circle-check"></i>
                </a>`;
            }

            return {
                id: data.id,
                keterangan: data.keterangan,
                toko_asal: data.toko_asal,
                toko_tujuan: data.toko_tujuan,
                created_by: data.created_by,
                created_at: data.created_at,
                is_read: data.is_read,
                akses: data.akses,
                checkbox,
                edit_button,
                delete_button
            };
        }

        async function setListData(dataList, pagination) {

            let html = `<div class="todo-container">`;

            dataList.forEach((item) => {

                const readIcon = item.is_read ?
                    `<i class="fa fa-check-circle text-success mr-2" title="Sudah dibaca"></i>` :
                    `<i class="fa fa-circle text-secondary mr-2" title="Belum dibaca"></i>`;

                let roleBadge = '';
                let cardBorder = '';

                if (item.akses === 'sender') {
                    roleBadge = `<span class="badge badge-primary ml-2">Terkirim</span>`;
                    cardBorder = 'border-left border-primary';
                } else if (item.akses === 'receiver' && item.is_read == true) {
                    roleBadge = `<span class="badge badge-warning ml-2">Diterima & Ditandai</span>`;
                    cardBorder = 'border-left border-warning';
                } else if (item.akses === 'receiver' && item.is_read == false) {
                    roleBadge = `<span class="badge badge-danger ml-2">Diterima belum Ditandai</span>`;
                    cardBorder = 'border-left border-danger';
                }

                html += `
<div class="todo-item card shadow-sm mx-3 mb-2 ${cardBorder}">
    <div class="card-body d-flex justify-content-between align-items-start">

        <div>

            <div class="font-weight-bold d-flex align-items-center">
                ${readIcon}
                ${item.keterangan}
            </div>

            <small class="text-muted">
                ${roleBadge} ${item.toko_asal} → ${item.toko_tujuan}
            </small>

            <div>
                <small class="text-muted">
                    Dibuat oleh <b>${item.created_by}</b>
                </small>
            </div>

        </div>

        <div class="d-flex text-white" style="gap: 0.5rem;">
            ${item.edit_button || ''}
            ${item.delete_button || ''}
            ${item.checkbox || ''}
        </div>

    </div>
</div>
`;
            });

            html += `</div>`;

            $('#listData').html(html);
        }

        async function deleteData() {
            $(document).on("click", ".hapus-data", async function() {
                isActionForm = "destroy";
                let id = $(this).attr("data-id");
                let name = $(this).attr("data-name");

                swal({
                    title: `Hapus ${title}`,
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
                        '{{ route('catatan.delete') }}', {
                            id: id,
                            user_id: userId,
                            toko_id: tokoId
                        }
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

        async function readData() {
            $(document).on("click", ".read-data", async function() {
                isActionForm = "destroy";
                let id = $(this).attr("data-id");
                let name = $(this).attr("data-name");

                swal({
                    title: `Tandai ${title} ini`,
                    text: "Apakah anda yakin?",
                    type: "question",
                    showCancelButton: true,
                    confirmButtonText: "Ya, Tandai!",
                    cancelButtonText: "Tidak, Batal!",
                    confirmButtonColor: '#47c339',
                    cancelButtonColor: '#6c757d',
                    reverseButtons: true,
                    confirmButtonClass: "btn btn-danger",
                    cancelButtonClass: "btn btn-secondary",
                }).then(async (result) => {
                    let postDataRest = await renderAPI(
                        'PUT',
                        '{{ route('catatan.read') }}', {
                            id: id,
                            user_id: userId,
                            toko_id: tokoId,
                            is_read: true,
                        }
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

        function openAddModal() {
            renderModalForm('add');
            $('#save-btn')
                .removeClass('btn-primary')
                .addClass('btn-success')
                .prop('disabled', false)
                .html('<i class="fa fa-save mr-1"></i>Simpan');

            $('#modal-form').modal('show');
        }

        function openEditModal(data) {
            try {
                let item = JSON.parse(decodeURIComponent(data));

                renderModalForm('edit', item);

                $('#save-btn')
                    .removeClass('btn-success')
                    .addClass('btn-primary')
                    .prop('disabled', false)
                    .html('<i class="fa fa-edit mr-1"></i>Update');

                $('#modal-form').modal('show');
            } catch (e) {
                notificationAlert('info', 'Pemberitahuan', 'Terjadi kesalahan saat memuat data untuk diedit.');
            }
        }

        async function renderModalForm(mode = 'add', data = {}) {
            const flag = mode === 'edit' ?
                `<i class="fa fa-edit mr-1"></i>Edit ${title}` :
                `<i class="fa fa-circle-plus mr-1"></i>Tambah ${title}`;

            $('#modalLabel').html(flag);

            const formContent = `
                <div class="row">
                    <div class="col-xl-12">
                        <div class="card-body">
                            <div class="table-responsive">
                                <div class="form-group">
                                    <label for="toko_tujuan_id" class="form-control-label">Pesan ke Toko<span style="color: red">*</span></label>
                                    <select id="toko_tujuan_id" name="toko_tujuan_id" class="form-control id-toko select2"></select>
                                </div>
                                <div class="form-group">
                                    <label for="keterangan" class="form-control-label">Pesan / Keterangan<span style="color: red">*</span></label>
                                    <textarea id="keterangan" name="keterangan" placeholder="Masukkan pesan atau keterangan" class="form-control"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            await $('#form-data').html(formContent);

            await selectData(selectOptions);

            if (mode === 'edit') {
                if ($('#toko_tujuan_id option[value="' + data.toko_tujuan_id + '"]').length === 0) {
                    const newOption = new Option(data.toko_tujuan, data.toko_tujuan_id, true, true);
                    $('#toko_tujuan_id').append(newOption).trigger('change');
                } else {
                    $('#toko_tujuan_id').val(data.toko_tujuan_id).trigger('change');
                }

                $('#keterangan').val(data.keterangan);

                if ($('#form-data input[name="id"]').length === 0) {
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'id',
                        value: data.id
                    }).appendTo('#form-data');
                } else {
                    $('#form-data input[name="id"]').val(data.id);
                }
            }
        }

        async function saveData() {
            $(document).on("click", "#save-btn", async function(e) {
                e.preventDefault();

                const btn = $(this);
                const saveButton = this;
                const form = $('#form-data')[0];
                const formData = new FormData(form);

                formData.append('user_id', userId);
                formData.append('toko_id', tokoId);

                if (saveButton.disabled) return;

                swal({
                    title: "Konfirmasi",
                    text: `Apakah Anda yakin ingin menyimpan ${title} ini?`,
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
                    const originalContent = btn.data('original-content') || btn.html();
                    btn.data('original-content', originalContent);
                    btn.html(
                        `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan`
                    );

                    loadingPage(true);

                    const isEdit = formData.get('id') !== null && formData.get('id') !== '';
                    const url = isEdit ?
                        `{{ route('catatan.put') }}` : `{{ route('catatan.post') }}`;

                    let method = 'POST';

                    if (isEdit) {
                        formData.append('_method', 'PUT');
                        formData.append('id', formData.get('id'));
                    }

                    try {
                        const response = await renderAPI(method, url, formData);
                        loadingPage(false);

                        if (response.status >= 200 && response.status < 300) {
                            notificationAlert('success', 'Pemberitahuan', response.data
                                .message || 'Data berhasil disimpan.');
                            isDataSaved = true;

                            setTimeout(async function() {
                                await getListData(defaultLimitPage, currentPage,
                                    defaultAscending,
                                    defaultSearch, customFilter);
                            }, 500);

                            setTimeout(() => {
                                $('#modal-form').modal('hide');
                            }, 500);

                        } else {
                            notificationAlert('info', 'Pemberitahuan', response.data.message ||
                                'Terjadi kesalahan saat menyimpan.');
                            saveButton.disabled = false;
                            btn.html(btn.data('original-content'));
                        }
                    } catch (error) {
                        loadingPage(false);
                        notificationAlert('error', 'Kesalahan', error?.response?.data
                            ?.message || 'Terjadi kesalahan saat menyimpan data.');
                        saveButton.disabled = false;
                        btn.html(btn.data('original-content'));
                    }
                });
            });
        }

        async function initPageLoad() {
            await Promise.all([
                getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch, customFilter),
                searchList(),
                deleteData(),
                readData(),
                saveData(),
            ])
        }
    </script>
@endsection
