@extends('layouts.main')

@section('title')
    Data Barang
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('css/button-action.css') }}">
    <link rel="stylesheet" href="{{ asset('css/table.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sweetalert2.css') }}">
    <style>
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
                                @if (hasAnyPermission(['POST /barang/store', 'POST /import-barang']))
                                    @if (hasAnyPermission(['POST /barang/store']))
                                        <div class="custom-btn-tambah-wrap">
                                            <button class="btn btn-primary text-white add-data w-100" data-container="body"
                                                data-toggle="tooltip" data-placement="top" title="Tambah Toko">
                                                <i class="fa fa-plus-circle"></i> Tambah
                                            </button>
                                        </div>
                                    @endif
                                    @if (hasAnyPermission(['POST /import-barang']))
                                        <form action="{{ route('master.barang.import') }}" method="POST"
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
                                <div class="custom-btn-tambah-wrap">
                                    <button class="btn-dynamic btn btn-outline-primary custom-btn-tambah" type="button"
                                        data-toggle="collapse" data-target="#filter-collapse" aria-expanded="false"
                                        aria-controls="filter-collapse">
                                        <i class="fa fa-filter"></i> Filter
                                    </button>
                                </div>
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
                            <div class="collapse my-2 px-4" id="filter-collapse">
                                <form id="custom-filter" class="row g-2 align-items-end">
                                    <div class="col-md-4">
                                        <select class="form-control select2" id="jenis_barang" name="jenis_barang"
                                            style="width: 100%;"></select>
                                    </div>
                                    <div class="col-auto">
                                        <button class="btn btn-info w-100" id="tb-filter" type="submit">
                                            <i class="fa fa-magnifying-glass mr-2"></i> Cari
                                        </button>
                                    </div>
                                    <div class="col-auto">
                                        <button type="button" class="btn btn-secondary w-100" id="tb-reset">
                                            <i class="fa fa-rotate mr-2"></i> Reset
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <x-adminlte-alerts />
                            <div class="card-body p-0">
                                <div class="table-responsive table-scroll-wrapper">
                                    <table class="table table-striped m-0">
                                        <thead>
                                            <tr class="tb-head">
                                                <th class="text-center text-wrap align-top">No</th>
                                                <th class="text-wrap align-top">ID</th>
                                                <th class="text-wrap align-top">Kode</th>
                                                <th class="text-wrap align-top">Barcode</th>
                                                <th class="text-wrap align-top">Barang</th>
                                                <th class="text-wrap align-top">Jenis</th>
                                                <th class="text-wrap align-top">Brand</th>
                                                <th class="text-wrap align-top">Garansi</th>
                                                <th class="text-center text-wrap align-top" style="width: 15%;"><span
                                                        class="mr-4">Action</span></th>
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
        <div class="modal fade" id="modal-form" tabindex="-1" role="dialog" aria-labelledby="modal-form-label"
            aria-hidden="true">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal-title">Tambah Data Barang</h5>
                        <button type="button" class="btn-close reset-all close" data-bs-dismiss="modal"
                            aria-label="Close"><i class="fa fa-xmark"></i></button>
                    </div>
                    <div class="modal-body">
                        <form id="formTambahData">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="form-group">
                                            <label for="nama" class=" form-control-label">Nama Barang<span
                                                    style="color: red">*</span></label>
                                            <input type="text" id="nama" name="nama_barang" value=""
                                                class="form-control" placeholder="Contoh : Barang Baru">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="jenis_barang_id" class=" form-control-label">Jenis Barang<span
                                                style="color: red">*</span></label>
                                        <select name="jenis_barang_id" id="jenis_barang_id" class="form-control select2">
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="brand_id" class=" form-control-label">Brand Barang<span
                                                style="color: red">*</span></label>
                                        <select name="brand_id" id="brand_id" class="form-control select2">
                                            <option value="1" selected>Umum</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="form-group">
                                            <label for="gambar" class="form-control-label">Gambar Barang<span
                                                    style="font-size: 11px; color: rgb(193, 79, 79)"> (Ukuran tidak lebih
                                                    dari 1MB)</span></label>
                                            <input type="file" id="gambar" name="gambar" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <div class="form-group">
                                            <label for="barcode" class=" form-control-label">Barcode</label>
                                            <input type="text" id="barcode" name="barcode" value=""
                                                class="form-control" placeholder="Kosongkan jika tidak ada barcode">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <div class="form-group">
                                            <label for="flexSwitchCheckDefault"
                                                class=" form-control-label">Garansi</label>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="garansi"
                                                    name="garansi" checked="false">
                                                <span id="switchStatus">Tidak</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
    </div>

    <div id="barcodePreview"
        style="
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.6);
    z-index:9999;
    align-items:center;
    justify-content:center;
">
        <div
            style="
        background:#fff;
        padding:16px;
        padding-top:48px;
        max-width:90vw;
        max-height:90vh;
        box-shadow:0 10px 40px rgba(0,0,0,.3);
        position:relative;
    ">
            <button onclick="closeBarcodePreview()"
                style="
            position:absolute;
            top:8px;
            right:8px;
            border:none;
            background:none;
            font-size:20px;
            cursor:pointer;
        ">✕</button>

            <img id="barcodePreviewImg" src=""
                style="
            max-width:100%;
            max-height:80vh;
            display:block;
            margin:auto;
        ">
        </div>
    </div>

@endsection

@section('asset_js')
    <script src="{{ asset('js/pagination.js') }}"></script>
@endsection

@section('js')
    <script>
        let title = 'Barang';
        let defaultLimitPage = 30;
        let currentPage = 1;
        let totalPage = 1;
        let defaultAscending = 0;
        let defaultSearch = '';
        let customFilter = {};
        let selectOptions = [{
                id: '#jenis_barang',
                isUrl: '{{ route('master.jenisBarang') }}',
                placeholder: 'Pilih Jenis Barang',
            },
            {
                id: '#jenis_barang_id',
                isUrl: '{{ route('master.jenisBarang') }}',
                placeholder: 'Pilih Jenis Barang',
                isModal: '#modal-form',
            },
            {
                id: '#brand_id',
                isUrl: '{{ route('master.brand') }}',
                placeholder: 'Pilih Brand',
                isModal: '#modal-form',
            }
        ];

        async function getListData(limit = 30, page = 1, ascending = 0, search = '', customFilter = {}) {
            $('#listData').html(loadingData());

            let filterParams = {
                ...customFilter
            };

            let getDataRest = await renderAPI(
                'GET',
                '{{ route('master.getbarangs') }}', {
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
            let view_button = data?.barcode_path && data.barcode_path !== "" ?
                `
                <a href="javascript:void(0)"
                class="view-barcode text-info"
                style="text-decoration: underline;"
                title="Preview Barcode ${title}: ${data.nama_barang}"
                onclick="previewBarcode('${data.barcode_path}')">
                ${data.barcode}
                </a>` : ``;

            // Lokal
            let gambar_barcode = data?.barcode_path && data.barcode_path !== "" ?
                `<img src="${data.barcode_path}" width="100" class="barcode-img" alt="Barcode">` :
                `<span class="badge badge-danger">Tidak Ada Gambar</span>`;

            let edit_button = `
                <a href='barang/edit/${data.id}' class="p-1 btn edit-data action_button"
                    data-container="body" data-toggle="tooltip" data-placement="top"
                    title="Edit ${title}: ${data.nama_barang}"
                    data-id='${data.id}'>
                    <span class="text-dark">Edit</span>
                    <div class="icon text-warning">
                        <i class="fa fa-edit"></i>
                    </div>
                </a>`;

            let download_button = data?.barcode_path && data.barcode_path !== "" ?
                `<a href="${data.barcode_path}" download class="p-1 btn download-data action_button"
                    data-container="body" data-toggle="tooltip" data-placement="top"
                    title="Unduh Barcode ${title}: ${data.nama_barang}"
                    data-id='${data.id}'>
                    <span class="text-dark">Unduh</span>
                    <div class="icon text-success">
                        <i class="fa fa-download"></i>
                    </div>
                </a>` :
                ``;

            let delete_button = `
                <a class="p-1 btn hapus-data action_button"
                    data-container="body" data-toggle="tooltip" data-placement="top"
                    title="Hapus ${title}: ${data.nama_barang}"
                    data-id='${data.id}'
                    data-name='${data.nama_barang}'>
                    <span class="text-dark">Hapus</span>
                    <div class="icon text-danger">
                        <i class="fa fa-trash"></i>
                    </div>
                </a>`;

            let action_buttons = '';
            if (edit_button || delete_button) {
                action_buttons = `
                <div class="d-flex justify-content-start">
                    ${download_button ? `<div class="hovering p-1">${download_button}</div>` : ''}
                    ${edit_button ? `<div class="hovering p-1">${edit_button}</div>` : ''}
                    ${delete_button ? `<div class="hovering p-1">${delete_button}</div>` : ''}
                </div>`;
            } else {
                action_buttons = `
                <span class="badge badge-danger">Tidak Ada Aksi</span>`;
            }

            return {
                id: data?.id ?? '-',
                nama_barang: data?.nama_barang && data.nama_barang !== "" ? data.nama_barang :
                    '<span class="badge badge-danger">Tidak Ada Data</span>',
                barcode: data?.barcode && data.barcode !== "" ? view_button :
                    '<span class="badge badge-danger">Tidak Ada Data</span>',
                nama_jenis_barang: data?.nama_jenis_barang && data.nama_jenis_barang !== "" ? data.nama_jenis_barang :
                    '<span class="badge badge-danger">Tidak Ada Data</span>',
                nama_brand: data?.nama_brand && data.nama_brand !== "" ? data.nama_brand :
                    '<span class="badge badge-danger">Tidak Ada Data</span>',
                garansi: data?.garansi && data.garansi !== "" ? data.garansi :
                    '<span class="badge badge-danger">Tidak Ada Data</span>',
                gambar_barcode,
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
                        <td class="${classCol} text-primary">${element.id}</td>
                        <td class="${classCol}">${element.barcode}</td>
                        <td class="${classCol}">${element.gambar_barcode}</td>
                        <td class="${classCol}">${element.nama_barang}</td>
                        <td class="${classCol}">${element.nama_jenis_barang}</td>
                        <td class="${classCol}">${element.nama_brand}</td>
                        <td class="${classCol}">${element.garansi}</td>
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
                    title: `Hapus ${title} ${name}`,
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
                        `barang/delete/${id}`
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

        async function filterList() {
            document.getElementById('custom-filter').addEventListener('submit', async function(e) {
                e.preventDefault();

                customFilter = {
                    jenis_barang: $("#jenis_barang").val() || '',
                };

                defaultSearch = $('.tb-search').val();
                defaultLimitPage = $("#limitPage").val();
                currentPage = 1;

                await getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch,
                    customFilter);
            });

            document.getElementById('tb-reset').addEventListener('click', async function() {
                $('#custom-filter select').val(null).trigger('change');
                customFilter = {};
                defaultSearch = $('.tb-search').val();
                defaultLimitPage = $("#limitPage").val();
                currentPage = 1;
                await getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch,
                    customFilter);
            });
        }

        async function addData() {
            $(document).on("click", ".add-data", function() {
                $("#formTambahData")[0].reset();
                $("#formTambahData select").val("");
                $("#formTambahData .select2").val(null).trigger("change");
                $('#garansi').prop('checked', false);
                $('#switchStatus').text('Tidak');

                $("#modal-title").html(`<i class="fa fa-circle-plus mr-1"></i>Form Tambah Barang`);
                $("#modal-form").modal("show");
                $("#formTambahData").data("action-url", '{{ route('master.barang.store') }}');
                setTimeout(() => {
                    $('#brand_id').val('1').trigger('change');
                }, 100);
            });

            $('#garansi').on('change', function() {
                $('#switchStatus').text(this.checked ? 'Ya' : 'Tidak');
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

                const userId = {{ auth()->user()->id }};

                let formData = {
                    created_by: userId,
                    nama: $('#nama').val(),
                    barcode: $('#barcode').val(),
                    gambar: $('#gambar').val(),
                    jenis_barang_id: $('#jenis_barang_id').val(),
                    brand_id: $('#brand_id').val(),
                    garansi: $('#garansi').is(':checked'),
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

        function previewBarcode(base64) {
            const overlay = document.getElementById('barcodePreview');
            const img = document.getElementById('barcodePreviewImg');

            img.src = base64;
            overlay.style.display = 'flex';
        }

        function closeBarcodePreview() {
            const overlay = document.getElementById('barcodePreview');
            const img = document.getElementById('barcodePreviewImg');

            img.src = '';
            overlay.style.display = 'none';
        }

        document.getElementById('barcodePreview').addEventListener('click', function(e) {
            if (e.target === this) closeBarcodePreview();
        });

        async function initPageLoad() {
            await Promise.all([
                selectData(selectOptions),
                setDynamicButton(),
                getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch, customFilter),
                searchList(),
                filterList(),
                deleteData(),
                addData(),
                submitForm(),
            ]);
        }
    </script>
@endsection
