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
                                @if (hasAnyPermission(['POST /toko/store', 'POST /import-toko']))
                                    @if (hasAnyPermission(['POST /toko/store']))
                                        <div class="custom-btn-tambah-wrap">
                                            <button class="btn btn-primary text-white add-data w-100" data-container="body"
                                                data-toggle="tooltip" data-placement="top" title="Tambah Toko">
                                                <i class="fa fa-plus-circle"></i> Tambah
                                            </button>
                                        </div>
                                    @endif
                                    @if (hasAnyPermission(['POST /import-toko']))
                                        <form action="{{ route('master.toko.import') }}" method="POST"
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
                                                <th class="text-wrap align-top">Nama Toko</th>
                                                <th class="text-wrap align-top">Singkatan</th>
                                                <th class="text-wrap align-top">Level Harga</th>
                                                <th class="text-wrap align-top">Wilayah</th>
                                                <th class="text-wrap align-top">Alamat</th>
                                                <th class="text-wrap align-top">List Barang</th>
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

    <div class="modal fade" id="modal-form" tabindex="-1" role="dialog" aria-labelledby="modal-form-label"
        aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-title">Tambah Data Pemasukan</h5>
                    <button type="button" class="btn-close reset-all close" data-bs-dismiss="modal" aria-label="Close"><i
                            class="fa fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <form id="formTambahData">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="nama" class=" form-control-label">Nama Toko<span
                                            style="color: red">*</span></label>
                                    <input type="text" id="nama" name="nama"
                                        placeholder="Contoh : Toko Sejahtera" class="form-control">
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
                                    <select class="form-control" id="level_harga" name="level_harga">
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
                                    <label for="toko_group" class="form-control-label">Grup Toko</label>
                                    <select class="form-control" id="toko_group" name="toko_group">
                                    </select>
                                    <small class="text-muted"><i>**kosongkan jika ingin membuat grup toko baru</i></small>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="wilayah" class=" form-control-label">Wilayah<span
                                    style="color: red">*</span></label>
                            <input type="text" id="wilayah" name="wilayah" placeholder="Contoh : Cirebon Timur"
                                class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="wilayah" class=" form-control-label">Alamat<span
                                    style="color: red">*</span></label>
                            <textarea name="alamat" id="alamat" rows="4"
                                placeholder="Contoh : Jl. Nyimas Gandasari No.18 Plered - Cirebon" class="form-control"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close"><i
                            class="fa fa-circle-xmark mr-1"></i>Tutup</button>
                    <button type="submit" class="btn btn-primary" id="submit-button" form="formTambahData"><i
                            class="fa fa-save mr-1"></i>Simpan</button>
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
            id: '#toko_group',
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
            if (hasPermission(['PUT /toko/update/{id}'])) {
                edit_button = `
                <a href='toko/edit/${data.id}' class="p-1 btn edit-data action_button"
                    data-container="body" data-toggle="tooltip" data-placement="top"
                    title="Edit ${title}: ${data.nama}"
                    data-id='${data.id}'>
                    <span class="text-dark">Edit</span>
                    <div class="icon text-warning">
                        <i class="fa fa-edit"></i>
                    </div>
                </a>`;
            }

            let delete_button = '';
            if (hasPermission('DELETE /toko/delete/{id}')) {
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
                    <td class="${classCol}">${element.nama_level_harga}</td>
                    <td class="${classCol}">${element.wilayah}</td>
                    <td class="${classCol}">${element.alamat}</td>
                    <td class="${classCol}">${element.detail_button}</td>
                    <td class="${classCol}">${element.action_buttons}</td>
                </tr>`;
            });

            $('#listData').html(getDataTable);
            $('#totalPage').text(pagination.total);
            $('#countPage').text(`${display_from} - ${display_to}`);
            $('[data-toggle="tooltip"]').tooltip();
            renderPagination();
        }

        async function addData() {
            $(document).on("click", ".add-data", function() {
                $("#modal-title").html(`<i class="fa fa-circle-plus mr-1"></i>Form Tambah Toko`);
                $("#modal-form").modal("show");
                $("#formTambahData").data("action-url", '{{ route('master.toko.store') }}');
            });
        }

        async function submitForm() {
            $(document).off("submit").on("submit", "#formTambahData", async function(e) {
                e.preventDefault();

                const $submitButton = $("#submit-button");
                const originalButtonHTML = $submitButton.html();

                $submitButton.prop("disabled", true).html(
                    `<i class="fas fa-spinner fa-spin"></i> Menyimpan...`);

                loadingPage(true);

                let actionUrl = $("#formTambahData").data("action-url");

                const idToko = '{{ auth()->user()->toko_id }}';

                let formData = {
                    toko_id: idToko,
                    nama: $('#nama').val(),
                    toko_group_id: $('#toko_group').val(),
                    singkatan: $('#singkatan').val(),
                    wilayah: $('#wilayah').val(),
                    alamat: $('#alamat').val(),
                    level_harga: $('#level_harga').val(),
                    pin: $('#pin').val(),
                    kas_detail: $('#kas_detail').val(),
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
                        `toko/delete/${id}`
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

        async function initPageLoad() {
            await Promise.all([
                getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch, customFilter),
                searchList(),
                addData(),
                selectData(selectOptions),
                deleteData(),
                submitForm(),
            ]);
        }
    </script>
@endsection
