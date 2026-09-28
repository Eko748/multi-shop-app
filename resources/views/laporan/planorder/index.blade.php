@extends('layouts.main')

@section('title')
    {{ $menu[0] }}
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
                                    <h5 class="m-0">List {{ $menu[0] }}</h5>
                                    <div class="d-flex align-items-center" style="gap: 0.5rem;">
                                        <button
                                            class="btn-dynamic btn btn-md btn-outline-secondary d-flex align-items-center justify-content-center"
                                            type="button" data-toggle="collapse" data-target="#filter-collapse"
                                            aria-expanded="false" aria-controls="filter-collapse" data-container="body"
                                            data-toggle="tooltip" data-placement="top"
                                            style="flex: 0 0 45px; max-width: 45px;" title="Filter Data">
                                            <i class="fa fa-filter my-1"></i>
                                        </button>
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
                            <div id="listData" class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
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
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title font-weight-bold" id="modalLabel">
                        <i class="fa fa-file-alt mr-2"></i>Plan Order - <span id="detailKodePlan"></span>
                    </h5>
                    <button type="button" class="btn-close reset-all close text-white" data-bs-dismiss="modal"
                        aria-label="Close"><i class="fa fa-xmark"></i></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Info Ringkasan -->
                    <div class="row mb-3 bg-light p-3 rounded mx-0">
                        <div class="col-6 col-md-3 mb-2 mb-md-0">
                            <small class="text-muted d-block">Toko</small>
                            <strong class="text-dark" id="detailNamaToko">-</strong>
                        </div>
                        <div class="col-6 col-md-3 mb-2 mb-md-0">
                            <small class="text-muted d-block">Tanggal</small>
                            <strong class="text-dark" id="detailTanggal">-</strong>
                        </div>
                        <div class="col-6 col-md-3">
                            <small class="text-muted d-block">Total Qty</small>
                            <strong class="text-dark" id="detailTotalQty">0 Pcs</strong>
                        </div>
                        <div class="col-6 col-md-3">
                            <small class="text-muted d-block">Grand Total</small>
                            <strong class="text-success font-weight-bold" id="detailGrandTotal">Rp 0</strong>
                        </div>
                    </div>

                    <!-- Tabel Daftar Lengkap Barang -->
                    <div class="table-responsive">
                        <!-- Tambahkan style table-layout: fixed agar lebar kolom dipatuhi -->
                        <table class="table table-bordered table-striped mb-0" style="table-layout: fixed; width: 100%;">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 45px;" class="text-center">NO</th>
                                    <th style="width: 45%;">NAMA BARANG</th>
                                    <th style="width: 65px;" class="text-center">QTY</th>
                                    <th style="width: 110px;" class="text-right">HPP</th>
                                    <th style="width: 125px;" class="text-right">TOTAL HPP</th>
                                </tr>
                            </thead>
                            <tbody id="detailItemList">
                                <!-- Diisi via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa fa-times mr-1"></i>Tutup
                    </button>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.6.0/jspdf.plugin.autotable.min.js"></script>
@endsection

@section('js')
    <script>
        let title = '{{ $menu[0] }}';
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
        let currentPlanData = [];

        async function getListData(limit = 30, page = 1, ascending = 0, search = '', customFilter = {}) {
            $('#listData').html(`
                <div class="col-12 text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
            `);

            let filterParams = {
                ...customFilter
            };

            let getDataRest = await renderAPI(
                'GET',
                '{{ route('planorder.get') }}', {
                    page: page,
                    limit: limit,
                    ascending: ascending,
                    search: search,
                    toko_id: typeof tokoId !== 'undefined' ? tokoId : '',
                    ...filterParams
                }
            ).then(function(response) {
                return response;
            }).catch(function(error) {
                return error.response;
            });

            // Validasi response sukses dari API (Struktur: { data: { data: [...], total_grand_total: ..., pagination: {...} } })
            if (getDataRest && getDataRest.status == 200 && Array.isArray(getDataRest.data.data.data)) {
                const rawList = getDataRest.data.data.data;
                const pagination = getDataRest.data.pagination;

                if (rawList.length === 0) {
                    $('#listData').html(`
                <div class="col-12">
                    <div class="card shadow-sm border-0 m-0 rounded bg-light">
                        <div class="text-center my-4 text-muted">
                            Tidak ada data Plan Order.
                        </div>
                    </div>
                </div>
            `);
                    $('#countPage').text("0 - 0");
                    $('#totalPage').text("0");
                    return;
                }

                // Mapping data per baris
                let handleDataArray = await Promise.all(
                    rawList.map(async item => await handleData(item))
                );

                await setListData(handleDataArray, pagination);

                // Update Total Grand Summary jika ada elemennya di halaman
                if (getDataRest.data.data.total_grand_total) {
                    $('#totalGrandSummary').text(getDataRest.data.data.total_grand_total);
                }

            } else {
                $('#listData').html(`
            <div class="col-12">
                <div class="card shadow-sm border-0 m-0 rounded bg-light">
                    <div class="text-center my-4 text-muted">
                        Data tidak tersedia untuk ditampilkan.
                    </div>
                </div>
            </div>
        `);
                $('#countPage').text("0 - 0");
                $('#totalPage').text("0");
            }
        }

        async function handleData(data) {
            // Tombol aksi detail / cetak / hapus
            let action_buttons = `
                <button class="btn btn-sm btn-info btn-detail-plan" data-id="${data.id}" title="Detail">
                    <i class="fa fa-eye"></i>
                </button>
                <button class="btn btn-sm btn-primary btn-print-card ml-1" data-id="${data.id}" title="Print PDF">
                    <i class="fa fa-print"></i>
                </button>
                <button class="btn btn-sm btn-success btn-download-card ml-1" data-id="${data.id}" title="Download PDF">
                    <i class="fa fa-file-pdf"></i>
                </button>
                <button class="btn btn-sm btn-danger btn-delete-plan ml-1" data-id="${data.id}" data-kode="${data.kode_plan}" title="Hapus">
                    <i class="fa fa-trash"></i>
                </button>
            `;

            return {
                id: data.id,
                kode_plan: data.kode_plan,
                nama_toko: data.nama_toko,
                total_item: data.total_item,
                grand_total_rp: data.grand_total_rp,
                tanggal: data.tanggal,
                items: data.items || [], // Array item barang [{barang_id, nama_barang, qty, hpp, total_hpp_rp}]
                action_buttons
            };
        }

        async function setListData(dataList, pagination) {
            currentPlanData = dataList;
            let html = `<div class="row overflow-auto px-3" style="max-height: 50vh;">`;

            dataList.forEach((item) => {
                // Menyusun rincian preview item barang
                let itemPreviewHtml = '';
                if (item.items && item.items.length > 0) {
                    const previewItems = item.items.slice(0, 3);
                    itemPreviewHtml = previewItems.map(i => `
                <div class="d-flex justify-content-between align-items-center border-bottom py-1 text-muted" style="font-size: 11.5px; gap: 8px;">
                    <span class="text-truncate" style="max-width: 60%;" title="${i.nama_barang}">
                        • ${i.nama_barang}
                    </span>
                    <span class="font-weight-bold text-nowrap text-right flex-shrink-0">
                        ${i.qty} x ${i.total_hpp_rp}
                    </span>
                </div>
            `).join('');

                    if (item.items.length > 3) {
                        itemPreviewHtml += `
                    <div class="small font-weight-bold text-primary mt-1 text-left" style="font-size: 11px;">
                        +${item.items.length - 3} item lainnya...
                    </div>`;
                    }
                } else {
                    itemPreviewHtml =
                        `<div class="text-muted small italic py-2 text-center">Tidak ada item</div>`;
                }

                html += `
        <div class="col-12 col-md-6 col-lg-4 mb-3">
            <div class="card shadow-sm border-0 border-top border-primary h-100 rounded-lg">
                <div class="card-body d-flex flex-column justify-content-between p-3">

                    <div>
                        <!-- Header Card: Kode & Tanggal -->
                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap" style="gap: 4px;">
                            <span class="badge badge-primary font-weight-bold px-2 py-1" style="font-size: 11px;">
                                ${item.kode_plan}
                            </span>
                            <small class="text-muted font-weight-normal" style="font-size: 11px;">
                                <i class="fa fa-calendar-alt mr-1"></i>${item.tanggal}
                            </small>
                        </div>

                        <!-- Info Toko & Total Qty -->
                        <div class="mb-2">
                            <h6 class="mb-0 font-weight-bold text-dark text-truncate" title="${item.nama_toko}">
                                ${item.nama_toko}
                            </h6>
                            <small class="text-muted" style="font-size: 12px;">
                                Total Qty: <b class="text-dark">${item.total_item} Pcs</b>
                            </small>
                        </div>

                        <!-- Preview Items List -->
                        <div class="bg-light p-2 rounded mb-3" style="min-height: 95px;">
                            ${itemPreviewHtml}
                        </div>
                    </div>

                    <!-- Footer Card: Grand Total & Action Buttons -->
                    <div class="d-flex justify-content-between align-items-end border-top pt-2">
                        <div>
                            <small class="text-muted d-block" style="font-size: 10px; line-height: 1;">Grand Total</small>
                            <span class="font-weight-bold text-success" style="font-size: 15px;">
                                ${item.grand_total_rp}
                            </span>
                        </div>
                        <div class="d-flex align-items-center" style="gap: 4px;">
                            ${item.action_buttons}
                        </div>
                    </div>

                </div>
            </div>
        </div>
        `;
            });

            html += `</div>`;

            $('#listData').html(html);

            // Meta Pagination
            if (typeof pagination !== 'undefined') {
                let display_from = ((pagination.per_page * (pagination.current_page - 1)) + 1);
                let display_to = Math.min(display_from + dataList.length - 1, pagination.total);

                $('#countPage').text(`${display_from} - ${display_to}`);
                $('#totalPage').text(pagination.total);

                if (typeof renderPagination === 'function') {
                    renderPagination(pagination);
                }
            }
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

        $(document).on('click', '.btn-detail-plan', function() {
            const id = $(this).data('id');
            const selectedPlan = currentPlanData.find(item => item.id == id);

            if (!selectedPlan) {
                alert('Data detail tidak ditemukan!');
                return;
            }

            // Set Header Info
            $('#detailKodePlan').text(selectedPlan.kode_plan);
            $('#detailNamaToko').text(selectedPlan.nama_toko);
            $('#detailTanggal').text(selectedPlan.tanggal);
            $('#detailTotalQty').text(`${selectedPlan.total_item} Pcs`);
            $('#detailGrandTotal').text(selectedPlan.grand_total_rp);

            // Render Tabel Item Lengkap di Modal
            let tbodyHtml = '';
            if (selectedPlan.items && selectedPlan.items.length > 0) {
                selectedPlan.items.forEach((item, index) => {
                    tbodyHtml += `
            <tr>
                <td class="text-center align-middle">${index + 1}</td>

                <!-- KUNCI PERBAIKAN: style word-break agar teks tanpa spasi bisa turun baris -->
                <td class="align-middle" style="word-break: break-word; overflow-wrap: anywhere; white-space: normal;">
                    ${item.nama_barang}
                </td>

                <td class="text-center font-weight-bold align-middle">${item.qty}</td>
                <td class="text-right align-middle">${formatRupiah(item.hpp)}</td>
                <td class="text-right font-weight-bold align-middle">${item.total_hpp_rp}</td>
            </tr>
        `;
                });
            } else {
                tbodyHtml =
                    `<tr><td colspan="5" class="text-center text-muted py-3">Tidak ada detail barang</td></tr>`;
            }

            $('#detailItemList').html(tbodyHtml);

            // Tampilkan Modal Form bawaan Anda
            $('#modal-form').modal('show');
        });


        async function deleteData() {
            $(document).off("click", ".btn-delete-plan").on("click", ".btn-delete-plan", async function(e) {
                e.preventDefault();

                let id = $(this).attr("data-id");
                let kodePlan = $(this).attr("data-kode") || "item ini";

                swal({
                    title: `Hapus Plan Order`,
                    text: `Apakah Anda yakin ingin menghapus ${kodePlan}?`,
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
                    if (result === true || result.value || result.isConfirmed) {
                        let postDataRest = await renderAPI(
                            'DELETE',
                            '{{ route('planorder.delete') }}', {
                                id: id,
                                toko_id: {{ auth()->user()->toko_id }}
                            }
                        ).then(function(response) {
                            return response;
                        }).catch(function(error) {
                            return error.response;
                        });

                        if (postDataRest && postDataRest.status == 200) {
                            setTimeout(function() {
                                getListData(defaultLimitPage, currentPage,
                                    defaultAscending, defaultSearch, customFilter);
                            }, 500);

                            notificationAlert('success', 'Pemberitahuan', postDataRest.data
                                .message || 'Plan Order berhasil dihapus');
                        } else {
                            notificationAlert('error', 'Gagal', postDataRest?.data?.message ||
                                'Gagal menghapus Plan Order');
                        }
                    }
                }).catch(swal.noop);
            });
        }

        // Function Helper untuk Tanggal Cetak
        function getCurrentDateTime() {
            const now = new Date();
            const pad = (num) => String(num).padStart(2, '0');
            return `${pad(now.getDate())}-${pad(now.getMonth() + 1)}-${now.getFullYear()} ${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
        }

        // Handler Print Thermal Nota (Font: Consolas - Anti-Meluber)
$(document).off('click', '.btn-print-card').on('click', '.btn-print-card', function(e) {
    e.preventDefault();
    const id = $(this).data('id');
    const selectedPlan = currentPlanData.find(item => item.id == id);

    if (!selectedPlan || !selectedPlan.items) {
        alert('Data plan tidak ditemukan!');
        return;
    }

    let tableRows = '';
    selectedPlan.items.forEach((p, i) => {
        tableRows += `
            <tr>
                <!-- KUNCI 1: Tambahkan word-break: break-all agar teks tanpa spasi bisa terpotong dan turun baris -->
                <td style="text-align: left; vertical-align: top; word-break: break-all; overflow-wrap: anywhere; white-space: normal;" colspan="2">
                    ${p.nama_barang}
                </td>
            </tr>
            <tr>
                <td style="text-align: left; padding-bottom: 4px; color: #333; font-weight: bold;">
                    # ${p.qty} x ${formatRupiah(p.hpp)}
                </td>
                <td style="text-align: right; padding-bottom: 4px; vertical-align: top; font-weight: bold;">
                    ${p.total_hpp_rp}
                </td>
            </tr>
        `;
    });

    const htmlContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Nota_${selectedPlan.kode_plan}</title>
            <style>
                @page {
                    size: 80mm auto;
                    margin: 0;
                }
                * {
                    box-sizing: border-box;
                }
                body {
                    font-family: 'Consolas', 'Courier New', Courier, monospace;
                    width: 72mm; /* Area cetak aman printer thermal 80mm */
                    margin: 0 auto;
                    padding: 8px 4px;
                    font-size: 11px;
                    color: #000;
                    line-height: 1.2;
                    /* KUNCI 2: Mencegah elemen meluber melebihi container */
                    overflow-x: hidden;
                    word-break: break-all;
                }
                .text-center { text-align: center; }
                .text-right { text-align: right; }
                .text-bold { font-weight: bold; }
                .divider {
                    border-top: 1px dashed #000;
                    margin: 6px 0;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    font-family: inherit;
                    font-size: 11px;
                    table-layout: fixed; /* Memaksa lebar tabel mematuhi width 100% */
                }
                td {
                    padding: 1px 0;
                    /* KUNCI 3: Pemutus kata otomatis */
                    word-break: break-all;
                    overflow-wrap: anywhere;
                }
            </style>
        </head>
        <body>
            <!-- Header Nota -->
            <div class="text-center">
                <span class="text-bold" style="font-size: 13px;">PLAN ORDER</span><br>
                <span>${selectedPlan.nama_toko}</span>
            </div>

            <div class="divider"></div>

            <!-- Info Transaksi -->
            <div>
                NO  : ${selectedPlan.kode_plan}<br>
                TGL : ${selectedPlan.tanggal}<br>
                QTY : ${selectedPlan.total_item} Pcs
            </div>

            <div class="divider"></div>

            <!-- List Barang -->
            <table>
                <tbody>
                    ${tableRows}
                </tbody>
            </table>

            <div class="divider"></div>

            <!-- Total -->
            <table>
                <tr>
                    <td class="text-bold" style="width: 45%;">GRAND TOTAL:</td>
                    <td class="text-right text-bold" style="font-size: 12px; width: 55%;">${selectedPlan.grand_total_rp}</td>
                </tr>
            </table>

            <div class="divider"></div>

            <!-- Footer -->
            <div class="text-center" style="margin-top: 8px; font-size: 10px;">
                *** PLAN ORDER ***
            </div>

            <script>
                window.onload = function() {
                    window.print();
                    setTimeout(function() { window.close(); }, 500);
                };
            <\/script>
        </body>
        </html>
    `;

    const printWindow = window.open('', '_blank');
    if (printWindow) {
        printWindow.document.open();
        printWindow.document.write(htmlContent);
        printWindow.document.close();
    } else {
        alert("Pop-up diblokir oleh browser. Harap izinkan pop-up untuk mencetak.");
    }
});

        // Handler Download PDF per Card
        $(document).off('click', '.btn-download-card').on('click', '.btn-download-card', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            const selectedPlan = currentPlanData.find(item => item.id == id);

            if (!selectedPlan || !selectedPlan.items) {
                alert('Data plan tidak ditemukan!');
                return;
            }

            const {
                jsPDF
            } = window.jspdf || {};
            if (!jsPDF) {
                alert("Library jsPDF tidak ditemukan!");
                return;
            }

            const doc = new jsPDF();

            // Title & Header Info
            doc.setFontSize(16);
            doc.setFont("helvetica", "bold");
            doc.text(`Plan Order - ${selectedPlan.kode_plan}`, 14, 15);

            doc.setFontSize(10);
            doc.setFont("helvetica", "normal");
            doc.setTextColor(80);
            doc.text(`Toko     : ${selectedPlan.nama_toko}`, 14, 22);
            doc.text(`Tanggal: ${selectedPlan.tanggal}`, 14, 27);

            // Format Data Table
            const tableData = selectedPlan.items.map((p, i) => [
                i + 1,
                p.nama_barang,
                p.qty,
                formatRupiah(p.hpp),
                p.total_hpp_rp
            ]);

            doc.autoTable({
                startY: 33,
                head: [
                    ['No', 'Nama Barang', 'Qty', 'HPP', 'Total HPP']
                ],
                body: tableData,
                foot: [
                    ['', 'Total', '', '', selectedPlan.grand_total_rp]
                ],
                theme: 'grid',
                styles: {
                    fontSize: 9,
                    cellPadding: 3,
                    overflow: 'linebreak'
                },
                headStyles: {
                    fillColor: [66, 135, 245],
                    textColor: [255, 255, 255],
                    halign: 'center',
                    fontStyle: 'bold'
                },
                columnStyles: {
                    0: {
                        halign: 'center',
                        cellWidth: 12
                    },
                    1: {
                        halign: 'left'
                    },
                    2: {
                        halign: 'center',
                        cellWidth: 20
                    },
                    3: {
                        halign: 'right',
                        cellWidth: 35
                    },
                    4: {
                        halign: 'right',
                        cellWidth: 40
                    }
                },
                footStyles: {
                    fillColor: [240, 240, 240],
                    textColor: [0, 0, 0],
                    fontStyle: 'bold',
                    halign: 'right'
                }
            });

            doc.save(`${selectedPlan.kode_plan}.pdf`);
        });

        async function initPageLoad() {
            await Promise.all([
                getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch, customFilter),
                searchList(),
                deleteData()
            ])
        }
    </script>
@endsection
