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
                                @if (hasAnyPermission(['POST /barang/post']))
                                    <div class="custom-btn-tambah-wrap">
                                        <button type="button" class="btn btn-primary w-100" id="btn-add-data"
                                            onclick="openAddModal()">
                                            <i class="fa fa-circle-plus"></i><span> Tambah Data</span>
                                        </button>
                                    </div>
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
                            <div class="card-body p-0">
                                <div class="table-responsive table-scroll-wrapper">
                                    <table class="table table-striped m-0">
                                        <thead>
                                            <tr class="tb-head">
                                                <th class="text-center text-wrap align-top">No</th>
                                                <th class="text-wrap align-top">ID</th>
                                                <th class="text-wrap align-top">Barcode</th>
                                                <th class="text-wrap align-top">QR code</th>
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
                        <h5 class="modal-title" id="modalLabel">Tambah Data Barang</h5>
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
        <div class="modal fade" id="modal-detail" tabindex="-1" role="dialog" aria-labelledby="modal-form-label"
            aria-hidden="true">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalDetail">Detail Data Barang</h5>
                        <button type="button" class="btn-close reset-all close" data-bs-dismiss="modal"
                            aria-label="Close"><i class="fa fa-xmark"></i></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="thead-light">
                                    <tr class="tb-head">
                                        <th class="text-center">No.</th>
                                        <th>Tanggal Masuk</th>
                                        <th class="text-center">Stok</th>
                                        <th class="text-right">Harga Beli</th>
                                        <th class="text-right">Riwayat Hpp</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="detail-data"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close"><i
                                class="fa fa-times mr-1"></i>Tutup</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="modal-print-form" tabindex="-1" role="dialog" aria-labelledby="modal-form-label"
            aria-hidden="true">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal-print-title">Print QR Code</h5>
                        <button type="button" class="btn-close reset-all close" data-bs-dismiss="modal"
                            aria-label="Close"><i class="fa fa-xmark"></i></button>
                    </div>
                    <div class="modal-body">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close"><i
                                class="fa fa-times mr-1"></i>Tutup</button>
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
                    toko_id: {{ auth()->user()->toko_id }},
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
            let edit_button = '';
            let delete_button = '';

            let view_button = data?.barcode_path && data.barcode_path !== "" ?
                `
                <a href="javascript:void(0)"
                class="view-barcode text-info"
                style="text-decoration: underline;"
                title="Preview Barcode ${title}: ${data.nama_barang}"
                onclick="previewBarcode('${data.barcode_path}')">
                ${data.barcode}
                </a>` : ``;

            let gambar_barcode = data?.barcode_path && data.barcode_path !== "" ?
                `
                <div class="d-flex flex-column align-items-start">
                    <img src="${data.barcode_path}" width="50" class="barcode-img" alt="Barcode">
                    <span>${view_button}</span>
                </div>
                ` :
                `<span class="badge badge-danger">Tidak Ada Gambar</span>`;
            let gambar_qrcode = data?.qrcode_path && data.qrcode_path !== "" ?
                `
                <div class="d-flex flex-column align-items-start">
                    <img src="${data.qrcode_path}" width="25" class="qrcode-img" alt="QRCode">
                    <span>${data.qrcode}</span>
                </div>
                ` :
                `<span class="badge badge-danger">Tidak Ada Gambar</span>`;

            if (hasPermission(['PUT /barang/put'])) {
                edit_button = `
                    <a class="p-1 btn edit-data action_button" onClick="openEditModal('${encodeURIComponent(JSON.stringify(data))}')">
                        <span class="text-dark" title="Edit ${title}: ${data.nama_barang}">Edit</span>
                        <div class="icon text-warning" title="Edit ${title}: ${data.nama_barang}">
                            <i class="fa fa-edit"></i>
                        </div>
                    </a>`;
            }

            let detail_button = `
            <a class="p-1 btn detail-data action_button" onClick="openDetailModal('${encodeURIComponent(JSON.stringify(data))}')">
                <span class="text-dark" title="Detail ${title}: ${data.nama_barang}">Detail</span>
                <div class="icon text-info" title="Detail ${title}: ${data.nama_barang}">
                    <i class="fa fa-folder"></i>
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
            if (hasPermission(['DELETE /barang/delete'])) {
                delete_button = `
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
            }

            let action_buttons = '';
            if (edit_button || delete_button) {
                action_buttons = `
                <div class="d-flex justify-content-start">
                    ${detail_button ? `<div class="hovering p-1">${detail_button}</div>` : ''}
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
                nama_jenis_barang: data?.nama_jenis_barang && data.nama_jenis_barang !== "" ? data
                    .nama_jenis_barang : '<span class="badge badge-danger">Tidak Ada Data</span>',
                nama_brand: data?.nama_brand && data.nama_brand !== "" ? data.nama_brand :
                    '<span class="badge badge-danger">Tidak Ada Data</span>',
                garansi: data?.garansi && data.garansi !== "" ? data.garansi :
                    '<span class="badge badge-danger">Tidak Ada Data</span>',
                gambar_barcode,
                gambar_qrcode,
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
                        <td class="${classCol}">${element.gambar_barcode}</td>
                        <td class="${classCol}">${element.gambar_qrcode}</td>
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
                        '{{ route('barang.delete') }}', {
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
                            getListData(defaultLimitPage, currentPage,
                                defaultAscending,
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

        async function openDetailModal(data) {
            let item = JSON.parse(decodeURIComponent(data));

            const detailResp = await renderAPI('GET', '{{ route('sb.getBarang') }}', {
                barang_id: item.id,
                toko_id: {{ auth()->user()->toko_id }}
            }).then(r => r).catch(
                e => e.response);
            const target = '#detail-data';

            if (detailResp && detailResp.status === 200 && Array.isArray(detailResp.data.data)) {
                const rows = detailResp.data.data.map((el, idx) => `
                <tr class="text-dark">
                    <td class="text-center">${idx + 1}.</td>
                    <td>${el.created_at}</td>
                    <td class="text-center">${el.qty}</td>
                    <td class="text-right">${el.harga}</td>
                    <td class="text-right">${el.hpp_baru}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-outline-info btn-sm open-modal-print" style="min-width: 120px;" data-container="body" data-toggle="tooltip" data-placement="top"
                            title="Atur print QR Code"
                            data-text="${el.qrcode}" data-tanggal="${el.tanggal}" data-qty="${el.qty}" data-barang="${el.nama_barang}" data-qrcode="${el.qrcode_path}">
                            <i class="fa fa-print"></i>
                            <span class="d-none d-md-inline"> Print</span>
                        </button>
                    </td>
                </tr>`).join('');
                $(target).html(rows);

                document.querySelectorAll('.copy-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const text = document.getElementById(this.getAttribute('data-target'))
                            .innerText;
                        navigator.clipboard.writeText(text).then(() => {
                            notyf.success('QR Code berhasil disalin');
                        }).catch(() => {
                            notyf.error('Gagal menyalin QR Code');
                        });
                    });
                });
            } else {
                $(target).html(`<tr><td class="text-center" colspan="6">Tidak ada Stok Barang</td></tr>`);
            }
            $('#modalDetail').html(`<i class="fa fa-folder-open mr-1"></i> Detail Barang ${item.nama_barang}`);
            $('#modal-detail').modal('show');

            $(document).on("click", ".open-modal-print", function() {
                const maxQty = $(this).data("qty");
                const qrCodePath = $(this).data("qrcode");
                const namaBarang = $(this).data("barang");
                const tanggal = $(this).data("tanggal");
                const text = $(this).data("text");

                $("#modal-print-form .-print-body").html("");
                $("#modal-print-title").html(`Form Print QR Code`);
                $("#modal-print-form").modal("show");

                $("#modal-print-form .modal-body").html(`
                <div class="mb-3">
                    <label for="qty_print" class="form-label">Jumlah Print</label>
                    <input type="number" id="qty_print" class="form-control" min="1" max="${maxQty}" value="${maxQty}">
                    <small class="form-text text-danger">Maksimum: ${maxQty}</small>
                </div>
                <div class="justify-content-end">
                    <button type="button" class="btn btn-primary w-100" id="confirm-print"
                        data-text="${text}" data-tanggal="${tanggal}" data-qrcode="${qrCodePath}" data-barang="${namaBarang}" data-max="${maxQty}">
                        <i class="fa fa-print mr-1"></i>Konfirmasi Print
                    </button>
                </div>
            `);
            });

            $(document).off("click", "#confirm-print").on("click", "#confirm-print", function() {
                const qty = parseInt($("#qty_print").val());
                const maxQty = parseInt($(this).data("max"));
                const qrCodePath = $(this).data("qrcode");
                const namaBarang = $(this).data("barang");
                const tanggal = $(this).data("tanggal");
                const text = $(this).data("text");

                if (isNaN(qty) || qty < 1 || qty > maxQty) {
                    notificationAlert('error', 'Error',
                        `Jumlah print tidak valid. Harus antara 1 hingga ${maxQty}`);
                    return;
                }

                const width = 1020;
                const height = 620;

                const left = (screen.width / 2) - (width / 2);
                const top = (screen.height / 2) - (height / 2);

                const printWindow = window.open(
                    '',
                    'printWindow',
                    `width=${width},height=${height},top=${top},left=${left},resizable=no,scrollbars=no,toolbar=no,menubar=no,location=no,status=no`
                );

                let imagesHtml = '';
                for (let i = 0; i < qty; i++) {
                    if (i % 3 === 0) {
                        if (i !== 0) imagesHtml += `</div></div>`;
                        imagesHtml += `<div class="page"><div class="label-container">`;
                    }

                    let displayName = formatLabelText(namaBarang);


                    imagesHtml += `
                            <div class="label">
                                <div class="label-left">
                                    <img src="${qrCodePath}" alt="QR Code">
                                    <div class="qr-text">${text ?? ''}</div>
                                </div>

                                <div class="label-text">
                                    <div class="label-name">${displayName}</div>
                                    <div class="label-date">${tanggal ?? ''}</div>
                                </div>
                            </div>
                        `;

                    if (i === qty - 1) {
                        imagesHtml += `</div></div>`;
                    }
                }

                printWindow.document.write(`
                    <html>
                        <head>
                            <title>Print QR Code</title>
                            <style>
                                @media print {
                                    @page {
                                        size: 110mm 17mm;
                                        margin: 0;
                                    }

                                    body, html {
                                        margin: 0;
                                        padding: 0;
                                    }

                                    .page {
                                        page-break-after: always;
                                        width: 110mm;
                                        height: 17mm;
                                        margin: 0;
                                        padding: 0;
                                        box-sizing: border-box;
                                    }
                                }

                                body {
                                    font-family: Arial, sans-serif;
                                }

                                .label-container {
                                    display: flex;
                                    flex-wrap: nowrap;
                                    justify-content: flex-start;
                                    column-gap: 2mm;
                                }

                                .label {
                                    width: 31mm;
                                    height: 15mm;
                                    display: flex;
                                    align-items: center;
                                    padding: 0;
                                    box-sizing: border-box;
                                    margin-top: 1mm;
                                    margin-bottom: 1mm;
                                    margin-left: 2mm;
                                }

                                .label-left {
                                    display: flex;
                                    flex-direction: column;
                                    align-items: center;

                                }

                                .qr-text {
                                    font-size: 4px;
                                    max-width: 16mm;
                                    white-space: nowrap;
                                    overflow: hidden;
                                    text-overflow: ellipsis;
                                }

                                .label img {
                                    width: 14mm;
                                    height: 14mm;
                                    object-fit: contain;
                                    margin-right: 1mm;
                                }

                                .label-text {
                                    display: flex;
                                    flex-direction: column;
                                    justify-content: space-between;
                                    height: 100%;
                                    margin-top: 1mm;
                                }

                                .label-name {
                                    font-size: 6px;
                                    line-height: 1.2;
                                }

                                .label-date {
                                    font-size: 4px;
                                    max-width: 16mm;
                                    white-space: nowrap;
                                    overflow: hidden;
                                    text-overflow: ellipsis;
                                    text-align: left;
                                    margin-bottom: 0.5mm;
                                }
                            </style>
                        </head>
                        <body>${imagesHtml}</body>
                    </html>
                `);

                printWindow.document.close();

                printWindow.onload = function() {
                    printWindow.focus();
                    setTimeout(() => {
                        printWindow.print();
                    }, 0);
                };

                const handleAfterPrint = () => {
                    printWindow.close();
                    setTimeout(() => {
                        const input = document.getElementById('qty_print');
                        if (input) {
                            input.blur();
                            setTimeout(() => {
                                input.focus();
                                input.select();
                            }, 10);
                        }
                    }, 300);
                    window.removeEventListener('afterprint', handleAfterPrint);
                };

                window.addEventListener('afterprint', handleAfterPrint);
            });
        }

        function formatLabelText(namaBarang) {
            const words = namaBarang.trim().split(/\s+/);
            let result = '';
            let totalLength = 0;

            for (let i = 0; i < words.length; i++) {
                let word = words[i];
                if (word.length > 7) {
                    word = word.substring(0, 7) + '..';
                }

                let wordWithSpace = (result ? ' ' : '') + word;
                if (totalLength + wordWithSpace.length > 40) {
                    result += '..';
                    break;
                }

                result += wordWithSpace;
                totalLength = result.length;
            }

            return result;
        }

        async function renderModalForm(mode = 'add', data = {}) {
            const title = mode === 'edit' ?
                '<i class="fa fa-edit mr-1"></i>Edit Data Barang' :
                '<i class="fa fa-circle-plus mr-1"></i>Tambah Data Barang';

            $('#modalLabel').html(title);

            const formContent = `
                <div class="row">
                    <div class="col-xl-12">
                        <div class="card-body">
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
                                                <label class="form-control-label">Garansi</label>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input"
                                                        type="checkbox"
                                                        id="garansi"
                                                        name="garansi"
                                                        value="1">
                                                    <span id="switchStatus">Tidak</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        </div>
                    </div>
                </div>
            `;

            await $('#form-data').html(formContent);

            const tokoId = mode === 'edit' ? data.toko_id : {{ auth()->user()->toko_id }};

            selectOptions.push({
                id: `#jenis_barang_id`,
                isUrl: `{{ route('master.jenisBarang') }}`,
                isFilter: {
                    'toko_id': {{ auth()->user()->toko_id }}
                },
                placeholder: 'Pilih Jenis Barang',
                isModal: '#modal-form',
            });

            await selectData(selectOptions);
            // default add = Tidak
            $('#garansi').prop('checked', false);
            $('#switchStatus').text('Tidak');

            if (mode === 'edit') {
                $('#nama').val(data.nama_barang_long);
                $('#barcode').val(data.barcode);
                $('#gambar').val(data.gambar);
                const isGaransi = data.status_garansi == 1;

                $('#garansi').prop('checked', isGaransi);
                $('#switchStatus').text(isGaransi ? 'Ya' : 'Tidak');

                const selectorJB = '#jenis_barang_id';

                if ($(selectorJB).length && data.jenis_barang_id) {

                    if ($(selectorJB + ' option[value="' + data.jenis_barang_id + '"]').length === 0) {

                        const newOption = new Option(
                            data.nama_jenis_barang,
                            data.jenis_barang_id,
                            true,
                            true
                        );

                        $(selectorJB).append(newOption).trigger('change');

                    } else {
                        $(selectorJB).val(data.jenis_barang_id).trigger('change');
                    }
                }

                const selectorBrand = '#brand_id';

                if ($(selectorBrand).length && data.brand_id) {

                    if ($(selectorBrand + ' option[value="' + data.brand_id + '"]').length === 0) {

                        const newOption = new Option(
                            data.nama_brand,
                            data.brand_id,
                            true,
                            true
                        );

                        $(selectorBrand).append(newOption).trigger('change');

                    } else {
                        $(selectorBrand).val(data.brand_id).trigger('change');
                    }
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

            $(document).on('change', '#garansi', function() {
                $('#switchStatus').text($(this).is(':checked') ? 'Ya' : 'Tidak');
            });
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

                    const isEdit = formData.get('id') !== null && formData.get('id') !==
                        '';
                    const url = isEdit ?
                        `{{ route('barang.update') }}` : `{{ route('barang.post') }}`;

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
                                await getListData(defaultLimitPage,
                                    currentPage,
                                    defaultAscending,
                                    defaultSearch, customFilter);
                            }, 500);

                            setTimeout(() => {
                                $('#modal-form').modal('hide');
                            }, 500);

                        } else {
                            notificationAlert('info', 'Pemberitahuan', response.data
                                .message ||
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
                selectData(selectOptions),
                setDynamicButton(),
                getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch,
                    customFilter),
                searchList(),
                filterList(),
                deleteData(),
                saveData(),
            ]);
        }
    </script>
@endsection
