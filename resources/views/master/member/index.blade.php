@extends('layouts.main')

@section('title')
    Data Member
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('css/button-action.css') }}">
    <link rel="stylesheet" href="{{ asset('css/table.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sweetalert2.css') }}">
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
                                @if (hasAnyPermission(['POST /member/store', 'POST /import-member']))
                                    @if (hasAnyPermission(['POST /member/store']))
                                        <div class="custom-btn-tambah-wrap">
                                            <button type="button" class="btn btn-primary w-100" id="btn-add-data"
                                                onclick="openAddModal()">
                                                <i class="fa fa-circle-plus"></i><span> Tambah Data</span>
                                            </button>
                                        </div>
                                    @endif
                                    @if (hasAnyPermission(['POST /import-member']))
                                        <form action="{{ route('master.member.import') }}" method="POST"
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
                            <x-adminlte-alerts />
                            <div class="card-body p-0">
                                <div class="table-responsive table-scroll-wrapper">
                                    <table class="table table-striped m-0">
                                        <thead>
                                            <tr class="tb-head">
                                                <th class="text-center text-wrap align-top">No</th>
                                                <th class="text-wrap align-top">Nama Member</th>
                                                <th class="text-wrap align-top">Nama Toko</th>
                                                <th class="text-wrap align-top">Level</th>
                                                <th class="text-wrap align-top">No. Hp</th>
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
                    <h5 class="modal-title" id="modalLabel">Tambah Data Member</h5>
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
        let title = 'Data Member';
        let defaultLimitPage = 10;
        let currentPage = 1;
        let totalPage = 1;
        let defaultAscending = 0;
        let defaultSearch = '';
        let customFilter = {};
        let jenisBarangList = @json($jenis_barang);
        let selectOptions = [{
            id: '#id_toko',
            isUrl: '{{ route('master.toko') }}',
            placeholder: 'Pilih Toko',
            isModal: '#modal-form',
        }];

        async function getListData(limit = 10, page = 1, ascending = 0, search = '', customFilter = {}) {
            $('#listData').html(loadingData());

            let filterParams = {};

            let getDataRest = await renderAPI(
                'GET',
                '{{ route('master.getmember') }}', {
                    page: page,
                    limit: limit,
                    ascending: ascending,
                    search: search,
                    id_toko: '{{ auth()->user()->id_toko }}',
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
            let edit_button = `
            <a class="p-1 btn edit-data action_button" onClick="openEditModal('${encodeURIComponent(JSON.stringify(data))}')">
                <span class="text-dark" title="Edit ${title}: ${data.nama_member}">Edit</span>
                <div class="icon text-warning" title="Edit ${title}: ${data.nama_member}">
                    <i class="fa fa-edit"></i>
                </div>
            </a>`;

            let delete_button = `
                <a class="p-1 btn hapus-data action_button"
                    data-container="body" data-toggle="tooltip" data-placement="top"
                    title="Hapus ${title}: ${data.nama_member}"
                    data-id='${data.id}'
                    data-name='${data.nama_member}'>
                    <span class="text-dark">Hapus</span>
                    <div class="icon text-danger">
                        <i class="fa fa-trash"></i>
                    </div>
                </a>`;

            return {
                id: data?.id ?? '-',
                nama_member: data?.nama_member ?? '-',
                nama_toko: data?.nama_toko ?? '<span class="badge badge-danger">Tidak Ada Toko</span>',
                level: data?.level ?? [],
                no_hp: data?.no_hp ?? '-',
                alamat: data?.alamat ?? '-',
                edit_button,
                delete_button,
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
                let levelList = '';
                if (Array.isArray(element.level) && element.level.length > 0) {
                    levelList = '<div class="mb-0">';
                    element.level.forEach(levelItem => {
                        levelList +=
                            `<div>${levelItem.nama_jenis_barang} : ${levelItem.nama_level_harga}</div>`;
                    });
                    levelList += '</div>';
                } else {
                    levelList = '<span class="badge badge-danger">Tidak Ada Level</span>';
                }

                getDataTable += `
                    <tr class="text-dark">
                        <td class="${classCol} text-center">${display_from + index}.</td>
                        <td class="${classCol}">${element.nama_member}</td>
                        <td class="${classCol}">${element.nama_toko}</td>
                        <td class="${classCol}">${levelList}</td>
                        <td class="${classCol}">${element.no_hp}</td>
                        <td class="${classCol}">${element.alamat}</td>
                        <td class="${classCol}">
                            <div class="d-flex justify-content-center w-100">
                                <div class="hovering p-1">
                                    ${element.edit_button}
                                </div>
                                <div class="hovering p-1">
                                    ${element.delete_button}
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

        async function deleteData() {
            $(document).on("click", ".hapus-data", async function() {
                isActionForm = "destroy";
                let id = $(this).attr("data-id");
                let name = $(this).attr("data-name");

                swal({
                    title: `Hapus Member ${name}`,
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
                        `member/delete/${id}`, {
                            user_id: '{{ auth()->user()->id }}'
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
                '<i class="fa fa-edit mr-1"></i>Edit Data Member' :
                '<i class="fa fa-circle-plus mr-1"></i>Tambah Data Member';

            $('#modalLabel').html(title);

            const formContent = `
                <div class="row">
                    <div class="col-xl-12">
                        <div class="card-body">
                            <div class="table-responsive">
                                <div class="form-group">
                                    <label for="id_toko" class="form-control-label">Nama Toko<span style="color: red">*</span></label>
                                    <select id="id_toko" name="id_toko" class="form-control id-toko select2"></select>
                                </div>
                                <div class="form-group">
                                    <label for="nama_member" class="form-control-label">Nama Member<span style="color: red">*</span></label>
                                    <input type="text" id="nama_member" name="nama_member" placeholder="Contoh : Member 1" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="jenis_barang" class="form-control-label">Jenis Barang</label>
                                    <ul class="list-group list-group-flush">
                                        ${jenisBarangList.map(jb => `
                                                                    <li class="list-group-item">
                                                                        <h6>${jb.nama_jenis_barang}
                                                                            <select name="level_harga[${jb.id}]" id="level_harga_${jb.id}" class="form-control select2"></select>
                                                                        </h6>
                                                                    </li>
                                                                `).join('')}
                                    </ul>
                                </div>
                                <div class="form-group">
                                    <label for="no_hp" class="form-control-label">No HP<span style="color: red">*</span></label>
                                    <input type="number" id="no_hp" name="no_hp" placeholder="Contoh : 08123456789" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="alamat" class="form-control-label">Alamat<span style="color: red">*</span></label>
                                    <textarea name="alamat" id="alamat" rows="4" placeholder="Contoh : Jl. Nyimas Gandasari No.18 Plered - Cirebon" class="form-control"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            await $('#form-data').html(formContent);

            const tokoId = mode === 'edit' ? data.id_toko : '{{ auth()->user()->id_toko }}';

            jenisBarangList.forEach(jb => {
                selectOptions.push({
                    id: `#level_harga_${jb.id}`,
                    isUrl: `{{ route('master.member.level-harga', ['id_toko' => '__ID__']) }}`.replace(
                        '__ID__', tokoId),
                    placeholder: 'Pilih Level Harga',
                    isModal: '#modal-form',
                });
            });

            await selectData(selectOptions);

            if (mode === 'edit') {
                if ($('#id_toko option[value="' + data.id_toko + '"]').length === 0) {
                    const newOption = new Option(data.nama_toko, data.id_toko, true, true);
                    $('#id_toko').append(newOption).trigger('change');
                } else {
                    $('#id_toko').val(data.id_toko).trigger('change');
                }

                $('#nama_member').val(data.nama_member);
                $('#no_hp').val(data.no_hp);
                $('#alamat').val(data.alamat);


                if (data.level && Array.isArray(data.level)) {
                    data.level.forEach(item => {
                        const selector = `#level_harga_${item.id_jenis_barang}`;

                        if ($(selector).length) {
                            if ($(selector + ' option[value="' + item.id_level_harga + '"]').length === 0) {
                                const newOption = new Option(item.nama_level_harga, item.id_level_harga, true,
                                    true);
                                $(selector).append(newOption).trigger('change');
                            } else {
                                $(selector).val(item.id).trigger('change');
                            }
                        }
                    });
                }

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
                        `{{ route('master.member.update', ['id' => '__ID__']) }}`.replace(
                            '__ID__', formData.get('id')) :
                        `{{ route('master.member.store') }}`;

                    let method = 'POST';

                    if (isEdit) {
                        formData.append('_method', 'PUT');
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
                saveData(),
            ])
        }
    </script>
@endsection
