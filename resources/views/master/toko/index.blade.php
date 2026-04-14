@extends('layouts.main')

@section('title')
    Data Toko
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('css/button-action.css') }}">
    <link rel="stylesheet" href="{{ asset('css/table.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sweetalert2.css') }}">
    <style>
        #tgl_nota[readonly] {
            background-color: white !important;
            cursor: pointer !important;
            color: inherit !important;
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
                                @if (hasAnyPermission(['POST /toko/post']))
                                    <div class="custom-btn-tambah-wrap">
                                        <button type="button" class="btn btn-primary w-100" id="btn-add-data"
                                            onclick="openAddModal()">
                                            <i class="fa fa-circle-plus"></i><span> Tambah Data</span>
                                        </button>
                                    </div>
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
                            <div class="card-body p-0">
                                <div class="table-responsive table-scroll-wrapper">
                                    <table class="table table-striped m-0">
                                        <thead>
                                            <tr class="tb-head">
                                                <th class="text-center text-wrap align-top">No</th>
                                                <th class="text-wrap align-top">Nama Toko</th>
                                                <th class="text-wrap align-top">Singkatan</th>
                                                <th class="text-wrap align-top">Group</th>
                                                <th class="text-wrap align-top">Level Harga</th>
                                                <th class="text-wrap align-top">Wilayah</th>
                                                <th class="text-wrap align-top">Alamat</th>
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

    <div id="modal-form" class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog"
        aria-labelledby="myLargeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalLabel">Tambah Data Toko</h5>
                    <button type="button" class="btn-close reset-all close" data-bs-dismiss="modal" aria-label="Close"><i
                            class="fa fa-xmark"></i></button>
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
    <script src="{{ asset('js/pagination.js') }}"></script>
@endsection

@section('js')
    <script>
        let title = 'Data Toko';
        let defaultLimitPage = 10;
        let currentPage = 1;
        let totalPage = 1;
        let defaultAscending = 0;
        let defaultSearch = '';
        let customFilter = {};
        let selectOptions = [{
            id: '#level_harga',
            isUrl: '{{ route('master.getlevelharga') }}',
            placeholder: 'Pilih Level Harga',
            isModal: '#modal-form',
            multiple: true
        }, {
            id: '#toko_group_id',
            isFilter: {
                toko_id: {{ auth()->user()->toko_id }},
            },
            isUrl: '{{ route('dm.toko.group.select') }}',
            placeholder: 'Pilih Grup Toko',
            isModal: '#modal-form',
            isForm: true
        }, ];

        async function getListData(limit = 10, page = 1, ascending = 0, search = '', customFilter = {}) {
            $('#listData').html(loadingData());

            let filterParams = {
                id_level: @json(auth()->user()->role_id),
                id_toko: @json(auth()->user()->toko_id),
            };

            let getDataRest = await renderAPI(
                'GET',
                '{{ route('master.gettoko') }}', {
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
            let detail_button = '';
            if (hasPermission('GET /toko/detail/{id}')) {
                detail_button = `
                <a href='toko/detail/${data.id}' class="p-1 btn detail-data btn btn-primary"
                    data-container="body" data-toggle="tooltip" data-placement="top"
                    title="Lihat Detail ${title}: ${data.nama}"
                    data-id='${data.id}'>
                    <span class="text-white"><i class="fa fa-eye mr-1"></i>Cek Detail</span>
                </a>`;
            } else {
                detail_button = `
                <span style="background-color: rgba(0, 123, 255, 0.1); color: #007bff; font-style: italic; padding: 4px 8px; border-radius: 4px; display: inline-block;">
                    Tidak ada aksi
                </span>`;
            }

            let edit_button = '';
            if (hasPermission(['PUT /toko/put'])) {
                edit_button = `
                <button class="p-1 btn edit-data action_button" onClick="openEditModal('${encodeURIComponent(JSON.stringify(data))}')">
                    <span class="text-dark" title="Edit ${title}: ${data.nama}">Edit</span>
                    <div class="icon text-warning" title="Edit ${title}: ${data.nama}">
                        <i class="fa fa-edit"></i>
                    </div>
                </button>`;
            }

            let delete_button = '';
            if (hasPermission('DELETE /toko/delete')) {
                delete_button = `
                <a class="p-1 btn hapus-data action_button"
                    data-container="body" data-toggle="tooltip" data-placement="top"
                    title="Hapus ${title}: ${data.nama}"
                    data-id='${data.id}'
                    data-name='${data.nama}'>
                    <span class="text-dark">Hapus</span>
                    <div class="icon text-danger">
                        <i class="fa fa-trash"></i>
                    </div>
                </a>`;
            }

            let action_buttons = '';
            if (edit_button || delete_button) {
                action_buttons = `
                <div class="d-flex justify-content-center">
                    ${edit_button ? `<div class="hovering p-1">${edit_button}</div>` : ''}
                    ${delete_button ? `<div class="hovering p-1">${delete_button}</div>` : ''}
                </div>`;
            } else {
                action_buttons = `
                <span style="background-color: rgba(0, 123, 255, 0.1); color: #007bff; font-style: italic; padding: 4px 8px; border-radius: 4px; display: inline-block;">
                    Tidak ada aksi
                </span>`;
            }

            return {
                id: data?.id ?? '-',
                nama: data?.nama ?? '-',
                singkatan: data?.singkatan ?? '-',
                nama_level_harga: data?.nama_level_harga ?? '-',
                wilayah: data?.wilayah ?? '-',
                alamat: data?.alamat ?? '-',
                toko_group: data?.toko_group ?? '-',
                detail_button,
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
                <tr class="text-dark">
                    <td class="${classCol} text-center">${display_from + index}.</td>
                    <td class="${classCol}">${element.nama}</td>
                    <td class="${classCol}">${element.singkatan}</td>
                    <td class="${classCol}">${element.toko_group}</td>
                    <td class="${classCol}">${element.nama_level_harga}</td>
                    <td class="${classCol}">${element.wilayah}</td>
                    <td class="${classCol}">${element.alamat}</td>
                    <td class="${classCol}">${element.action_buttons}</td>
                </tr>`;
            });

            $('#listData').html(getDataTable);
            $('#totalPage').text(pagination.total);
            $('#countPage').text(`${display_from} - ${display_to}`);
            $('[data-toggle="tooltip"]').tooltip();
            renderPagination();
        }

        async function deleteData() {
            $(document).on("click", ".hapus-data", async function() {
                isActionForm = "destroy";
                let id = $(this).attr("data-id");
                let name = $(this).attr("data-name");

                swal({
                    title: `Hapus Toko ${name}`,
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
                        '{{ route('toko.delete') }}', {
                            id: id,
                            user_id: {{ auth()->user()->id }}
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
            const title = mode === 'edit' ?
                '<i class="fa fa-edit mr-1"></i>Edit Data Toko' :
                '<i class="fa fa-circle-plus mr-1"></i>Tambah Data Toko';

            const tokoGroup = mode !== 'edit' ?
                `<div class="col-md-6">
                    <div class="form-group">
                        <label for="toko_group_id" class="form-control-label">Grup Toko</label>
                        <select class="form-control" id="toko_group_id" name="toko_group_id">
                        </select>
                        <small class="text-muted"><i>**kosongkan jika ingin membuat grup toko baru</i></small>
                    </div>
                </div>` : '';

            const cols = mode === 'edit' ? '12' : '6';

            $('#modalLabel').html(title);

            const formContent = `
                <div class="row">
                    <div class="col-xl-12">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="nama" class=" form-control-label">Nama Toko<span
                                                style="color: red">*</span></label>
                                        <input type="text" id="nama" name="nama"
                                            placeholder="Masukkan nama toko" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="singkatan" class="form-control-label">Singkatan<span
                                                style="color: red">*</span></label>
                                        <input type="text" id="singkatan" name="singkatan"
                                            placeholder="Maksimal 4 Karakter" class="form-control"
                                            value="{{ old('singkatan') }}">
                                        @error('singkatan')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="pin" class=" form-control-label">PIN Toko<span
                                                style="color: red">*</span></label>
                                        <input type="password" id="pin" name="pin" placeholder="Masukkan PIN"
                                            class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="level_harga" class="form-control-label">Level Harga<span
                                                style="color: red">*</span></label>
                                        <select class="form-control" id="level_harga" name="level_harga[]">
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="kas_detail" class="form-control-label">Tipe Kas Jenis Barang<span
                                                style="color: red">*</span></label>
                                        <select class="form-control" id="kas_detail" name="kas_detail">
                                            <option value="1">Ya</option>
                                            <option value="0">Tidak</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="kasbon" class="form-control-label">Terima Kasbon?<span
                                                style="color: red">*</span></label>
                                        <select class="form-control" id="kasbon" name="kasbon">
                                            <option value="1">Ya</option>
                                            <option value="0">Tidak</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-${cols}">
                                    <div class="form-group">
                                        <label for="wilayah" class=" form-control-label">Wilayah<span
                                                style="color: red">*</span></label>
                                        <input type="text" id="wilayah" name="wilayah" placeholder="Masukkan wilayah"
                                            class="form-control">
                                    </div>
                                </div>
                                ${tokoGroup}
                            </div>
                            <div class="form-group">
                                <label for="alamat" class=" form-control-label">Alamat<span
                                        style="color: red">*</span></label>
                                <textarea name="alamat" id="alamat" rows="4"
                                    placeholder="Masukkan alamat" class="form-control"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            await $('#form-data').html(formContent);

            await selectData(selectOptions);

            if (mode === 'edit') {
                $('#nama').val(data.nama);
                $('#singkatan').val(data.singkatan);
                $('#pin').val(data.pin);
                $('#wilayah').val(data.wilayah);
                $('#alamat').val(data.alamat);

                $('#kas_detail').val(data.kas_detail).trigger('change');
                $('#kasbon').val(data.kasbon).trigger('change');

                setSelect2MultiValue(
                    '#level_harga',
                    data.level_harga,
                    data.level_harga_text
                );

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

                const userId = '{{ auth()->user()->id }}';
                formData.append('user_id', userId);

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
                        `{{ route('toko.update') }}` : `{{ route('toko.post') }}`;

                    let method = 'POST';

                    if (isEdit) {
                        formData.append('_method', 'PUT');
                        formData.append('id', formData.get('id'));
                    }
                    formData.append('toko_id', {{ auth()->user()->toko_id }});

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
                selectData(selectOptions),
                deleteData(),
                saveData(),
            ]);
        }
    </script>
@endsection
