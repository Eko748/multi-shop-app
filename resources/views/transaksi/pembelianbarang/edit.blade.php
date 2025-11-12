@extends('layouts.main')

@section('title')
    Detail Pembelian Barang
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('css/notyf.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sweetalert2.css') }}">
    <style>
        .atur-harga-btn {
            display: none;
        }

        .table tbody tr {
            height: 20px;
            line-height: 1.2;
        }

        .table tbody tr td {
            padding: 8px;
        }

        .btn-small {
            padding: 4px 8px;
            font-size: 12px;
            line-height: 1.2;
        }

        .status-select-small {
            height: 30px;
            font-size: 12px;
            padding: 4px 8px;
        }
    </style>
@endsection

@section('content')
    <div class="pcoded-main-container">
        <div class="pcoded-content pt-1 mt-1">
            @include('components.breadcrumbs')
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                            <a href="{{ url()->previous() }}" class="btn btn-danger mb-2">Kembali</a>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item">
                                            <div class="d-flex">
                                                <div class="mr-2">
                                                    <h5 class="mb-0"><i class="fa fa-barcode"></i> Nomor Nota</h5>
                                                </div>
                                                <span id="no_nota"
                                                    class="badge badge-pill badge-primary ml-auto align-self-center"></span>
                                            </div>
                                        </li>
                                        <li class="list-group-item">
                                            <div class="d-flex">
                                                <div class="mr-2">
                                                    <h5 class="mb-0"><i class="fa fa-user"></i> Nama Supplier</h5>
                                                </div>
                                                <span id="nama_supplier"
                                                    class="badge badge-pill badge-secondary ml-auto align-self-center"></span>
                                            </div>
                                        </li>
                                    </ul>
                                </div>

                                <div class="col-md-6">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item">
                                            <div class="d-flex">
                                                <div class="mr-2">
                                                    <h5 class="mb-0"><i class="fa fa-calendar-day"></i> Tanggal Nota</h5>
                                                </div>
                                                <span id="tgl_nota"
                                                    class="badge badge-pill badge-secondary ml-auto align-self-center"></span>
                                            </div>
                                        </li>
                                        <li class="list-group-item">
                                            <div class="d-flex">
                                                <div class="mr-2">
                                                    <h5 class="mb-0"><i class="fa fa-wallet"></i> Total Transaksi</h5>
                                                </div>
                                                <span id="total_transaksi"
                                                    class="badge badge-pill badge-secondary ml-auto align-self-center"></span>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <hr class="mt-0">
                            <div class="row">
                                <div class="col-12 d-flex justify-content-end" style="gap: 10px;">
                                    <div class="mb-2" style="width: 100%; max-width: 200px;">
                                        <select name="limitPage" id="limitPage" class="form-control">
                                            <option value="10">10</option>
                                            <option value="20">20</option>
                                            <option value="30">30</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                            <option value="200">200</option>
                                            <option value="300">300</option>
                                        </select>
                                    </div>
                                    <div class="mb-2" style="width: 100%; max-width: 300px;">
                                        <input id="tb-search" class="tb-search form-control" type="search" name="search"
                                            placeholder="Cari Data" aria-label="search">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr class="tb-head">
                                                    <th style="width: 40px;" class="text-center">No</th>
                                                    <th style="width: 50px;">Status</th>
                                                    <th style="min-width: 200px;">QR Code Pembelian Barang</th>
                                                    <th style="min-width: 200px;">Nama Barang</th>
                                                    <th class="text-right">Qty Pembelian</th>
                                                    <th class="text-right">Harga</th>
                                                    <th class="text-right">Total</th>
                                                    <th class="text-right">Qty Out</th>
                                                    <th class="text-right">Qty Tersisa</th>
                                                    <th class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="listData">
                                            </tbody>
                                            <tfoot id="detail-footer">
                                            </tfoot>
                                        </table>
                                    </div>
                                    <div
                                        class="d-flex flex-column flex-md-row justify-content-between align-items-center p-3">
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
    </div>
    <div id="modal-form" class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="modal-title"
        aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-title"></h5>
                    <button type="button" class="btn-close reset-all close" data-bs-dismiss="modal"
                        aria-label="Close"><i class="fa fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditDetail" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form id="formEditDetail">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fa fa-edit mr-1"></i>Edit Detail Pembelian Barang</h5>
                        <button type="button" class="btn-close reset-all close" data-bs-dismiss="modal"
                            aria-label="Close"><i class="fa fa-xmark"></i></button>
                    </div>
                    <div class="modal-body">
                        <h5>Barang: <span id="edit_barang"></span></h5>
                        <input type="hidden" id="edit_id_detail" name="id">
                        <div class="form-group">
                            <label>Qty</label>
                            <input type="number" id="edit_qty" name="qty" class="form-control" required>
                            <small class="font-weight-bold">Minimal: <span class="text-danger"
                                    id="min_qty">1</span></small>
                        </div>
                        <div class="form-group">
                            <label>Harga Barang</label>
                            <input type="number" id="edit_harga_barang" name="harga_barang" class="form-control"
                                required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Simpan
                            Perubahan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('asset_js')
    <script src="{{ asset('js/notyf.min.js') }}"></script>
    <script src="{{ asset('js/pagination.js') }}"></script>
@endsection

@section('js')
    <script>
        let title = 'Detail Pembelian Barang'
        let defaultLimitPage = 10;
        let currentPage = 1;
        let totalPage = 1;
        let defaultAscending = 0;
        let defaultSearch = '';
        let customFilter = {};

        let urlParams = new URLSearchParams(window.location.search);
        let dataParams = urlParams.get('r');

        $('#modal-form').on('hidden.bs.modal', function() {
            $(this).find('.modal-body').html('');
        });

        async function showData() {
            $('#modal-form').on('hidden.bs.modal', function() {
                $(this).find('.modal-body').html('');
            });

            $(document).off("click", "#confirm-print").on("click", "#confirm-print", function() {
                const qty = parseInt($("#qty_print").val());
                const maxQty = parseInt($(this).data("max"));
                const qrCodePath = $(this).data("qrcode");
                const namaBarang = $(this).data("barang");

                if (isNaN(qty) || qty < 1 || qty > maxQty) {
                    notificationAlert('error', 'Error',
                        `Jumlah print tidak valid. Harus antara 1 hingga ${maxQty}`);
                    return;
                }

                const printWindow = window.open('', '_blank');

                let imagesHtml = '';
                for (let i = 0; i < qty; i++) {
                    if (i % 3 === 0) {
                        if (i !== 0) imagesHtml += `</div></div>`;
                        imagesHtml += `<div class="page"><div class="label-container">`;
                    }

                    let displayName = formatLabelText(namaBarang);


                    imagesHtml += `
                        <div class="label">
                            <img src="{{ asset('storage') }}/${qrCodePath}" alt="QR Code">
                            <div class="label-text">${displayName}</div>
                        </div>
                    `;

                    if (i === qty - 1) {
                        imagesHtml += `</div></div>`;
                    }
                }

                printWindow.document.write(`
                    <html>
                        <head>
                            <title>Print QR Code Pembelian</title>
                            <style>
                            @media print {
                                @page {
                                    size: 110mm 17mm;
                                    margin: 0; /* Hapus semua margin halaman */
                                }

                                body, html {
                                    margin: 0;
                                    padding: 0;
                                }

                                .page {
                                    page-break-after: always;
                                    width: 110mm;
                                    height: 17mm;
                                    margin: 0;   /* Pastikan tidak ada margin */
                                    padding: 0;  /* Pastikan tidak ada padding */
                                    box-sizing: border-box;
                                }
                            }

                                body {
                                    font-family: Arial, sans-serif;
                                    margin: 0;
                                    padding: 0;
                                }

                                .label-container {
                                    display: flex;
                                    flex-wrap: nowrap;
                                    justify-content: flex-start;
                                    column-gap: 2mm;
                                    padding: 0;
                                    margin: 0;
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

                                .label img {
                                    width: 16mm;
                                    height: 16mm;
                                    object-fit: contain;
                                    margin-right: 1mm;
                                }

                                .label-text {
                                    font-size: 10px;
                                    line-height: 1.2;
                                    flex: 1;
                                }
                            </style>
                        </head>
                        <body>
                            ${imagesHtml}
                        </body>
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
                    word = word.substring(0, 7) + '..'; // now word is 9 chars
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

        $(document).on("click", ".open-modal-print", function() {
            const maxQty = $(this).data("qty");
            const qrCodePath = $(this).data("qrcode");
            const namaBarang = $(this).data("barang");

            $("#modal-form .modal-body").html("");
            $("#modal-title").html(`Form Print QR Code Pembelian Barang`);
            $("#modal-form").modal("show");

            $("#modal-form .modal-body").html(`
                <div class="mb-3">
                    <label for="qty_print" class="form-label">Jumlah Print</label>
                    <input type="number" id="qty_print" class="form-control" min="1" max="${maxQty}" value="${maxQty}">
                    <small class="form-text text-danger">Maksimum: ${maxQty}</small>
                </div>
                <div class="justify-content-end">
                    <button type="button" class="btn btn-primary w-100" id="confirm-print"
                        data-qrcode="${qrCodePath}" data-barang="${namaBarang}" data-max="${maxQty}">
                        <i class="fa fa-print mr-1"></i>Konfirmasi Print
                    </button>
                </div>
            `);
        });

        $(document).on("click", ".open-modal-print-all", function() {
            const itemsJson = $(this).data("items");

            let items;
            if (typeof itemsJson === "string") {
                items = JSON.parse(decodeURIComponent(itemsJson));
            } else {
                items = itemsJson;
            }

            $("#modal-form .modal-body").html("");
            $("#modal-title").html(`Form Print QR Code Semua Barang`);
            $("#modal-form").modal("show");

            let formHtml = `<form id="print-all-form">`;

            items.forEach((item, index) => {
                formHtml += `
                    <div class="mb-3">
                        <label class="form-label">${index + 1}. ${item.nama_barang}</label>
                        <input type="number" class="form-control qty-print-all"
                            name="qty_print_all[${item.id}]"
                            min="0" max="${item.qty}"
                            value="${item.qty}"
                            data-qrcode="${item.qrcode_path}"
                            data-nama="${item.nama_barang}">
                        <small class="form-text text-danger">Maksimum: ${item.qty}</small>
                    </div>
                `;
            });

            formHtml += `
                    <div class="justify-content-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fa fa-print mr-1"></i>Konfirmasi Print Semua
                        </button>
                    </div>
                </form>`;

            $("#modal-form .modal-body").html(formHtml);
        });

        $(document).off("submit", "#print-all-form").on("submit", "#print-all-form", function(e) {
            e.preventDefault();

            const printWindow = window.open('', '_blank');
            let imagesHtml = '';
            let count = 0;

            $(".qty-print-all").each(function() {
                const qty = parseInt($(this).val());
                const max = parseInt($(this).attr("max"));
                const qrCodePath = $(this).data("qrcode");
                const namaBarang = $(this).data("nama");

                if (!isNaN(qty) && qty > 0 && qty <= max) {
                    for (let i = 0; i < qty; i++) {
                        if (count % 3 === 0) {
                            if (count !== 0) imagesHtml += `</div></div>`;
                            imagesHtml += `<div class="page"><div class="label-container">`;
                        }

                        let displayName = formatLabelText(namaBarang);

                        imagesHtml += `
                            <div class="label">
                                <img src="{{ asset('storage') }}/${qrCodePath}" alt="QR Code">
                                <div class="label-text">${displayName}</div>
                            </div>
                        `;
                        count++;
                    }
                }
            });

            if (count > 0) {
                imagesHtml += `</div></div>`;

                printWindow.document.write(`
                    <html>
                        <head>
                            <title>Print QR Code Pembelian</title>
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

                                .label img {
                                    width: 16mm;
                                    height: 16mm;
                                    object-fit: contain;
                                    margin-right: 1mm;
                                }

                                .label-text {
                                    font-size: 10px;
                                    line-height: 1.2;
                                    flex: 1;
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
                    window.removeEventListener('afterprint', handleAfterPrint);
                };

                window.addEventListener('afterprint', handleAfterPrint);
            } else {
                notificationAlert('error', 'Error', 'Tidak ada barang yang dipilih untuk dicetak.');
            }
        });

        async function getListData(limit = 10, page = 1, ascending = 0, search = '', customFilter = {}) {
            const detailBody = document.getElementById('listData');
            const detailFooter = document.getElementById('detail-footer');
            let filterParams = {};

            detailBody.innerHTML = `
                <tr id="loading-spinner">
                    <td colspan="10" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </td>
                </tr>
            `;
            detailFooter.innerHTML = '';

            try {
                let response = await renderAPI('GET', '{{ route('transaksi.pembelianbarang.Getdetail') }}', {
                    id_pembelian: dataParams,
                    page: page,
                    limit: limit,
                    ascending: ascending,
                    search: search,
                    ...filterParams
                });

                if (response.status === 200) {
                    const data = response.data.data;
                    const pagination = response.data.pagination;
                    const jsonItems = encodeURIComponent(JSON.stringify(data.detail));

                    $('#no_nota').text(data.no_nota || '-');
                    $('#nama_supplier').text(data.nama_supplier || '-');
                    $('#tgl_nota').text(data.tgl_nota || '-');
                    $('#total_transaksi').text(data.total ? formatRupiah(data.total) : 'Rp 0');

                    detailBody.innerHTML = '';
                    detailFooter.innerHTML = '';

                    let subTotal = 0;
                    totalPage = pagination.total_pages;
                    currentPage = pagination.current_page;

                    data.detail.forEach((item, index) => {
                        const total = item.qty * item.harga_barang;

                        let buttons = [];

                        if (hasPermission(['PUT /pembelianbarang/edit/detail-pembelian-barang'])) {
                            buttons.push(`
                                <button type="button" class="btn btn-outline-warning btn-sm" style="min-width: 120px;" data-container="body" data-toggle="tooltip" data-placement="top"
                                    title="Edit Barang" onClick="openModalEdit('${btoa(JSON.stringify(item))}')">
                                    <i class="fa fa-edit"></i>
                                    <span class="d-none d-md-inline"> Edit</span>
                                </button>
                            `);
                        }

                        buttons.push(`
                            <a href="{{ asset('storage') }}/${item.qrcode_path}" download class="btn btn-outline-success btn-sm" style="min-width: 120px;" data-container="body" data-toggle="tooltip" data-placement="top"
                                title="Unduh QR Code Pembelian Barang">
                                <i class="fa fa-download"></i>
                                <span class="d-none d-md-inline"> Unduh</span>
                            </a>
                        `);

                        buttons.push(`
                            <button type="button" class="btn btn-outline-info btn-sm open-modal-print" style="min-width: 120px;" data-container="body" data-toggle="tooltip" data-placement="top"
                                title="Atur print QR Code Pembelian Barang"
                                data-qty="${item.qty}" data-barang="${item.nama_barang}" data-qrcode="${item.qrcode_path}">
                                <i class="fa fa-print"></i>
                                <span class="d-none d-md-inline"> Print</span>
                            </button>
                        `);

                        subTotal += total;

                        detailBody.innerHTML += `
                        <tr>
                            <td class="text-center">${index + 1}</td>
                            <td>
                                ${item.status === 'success'
                                    ? `<span class="badge badge-success w-100"><i class="fas fa-circle-check mr-1"></i>Sukses</span>`
                                    : `<select class="form-control">
                                                <option value="" disabled ${!item.status ? 'selected' : ''}>Pilih Status</option>
                                                <option value="progress" ${item.status === 'progress' ? 'selected' : ''}>progress</option>
                                                <option value="success" ${item.status === 'success' ? 'selected' : ''}>success</option>
                                                <option value="failed" ${item.status === 'failed' ? 'selected' : ''}>failed</option>
                                                </select>`}
                            </td>
                            <td>
                                <div class="d-flex align-items-start" style="gap: 10px;">
                                    <img src="{{ asset('storage') }}/${item.qrcode_path}" alt="QR Code" style="max-width: 50px; height: auto;">
                                    <div class="d-flex flex-column">
                                        <span id="qrcode-text-${index}" class="mr-2 mb-1 text-dark font-weight-bold">${item.qrcode || '-'}</span>
                                        <button type="button" class="btn btn-sm btn-outline-primary copy-btn" data-target="qrcode-text-${index}">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                            </td>
                            <td style="word-wrap: break-word; white-space: normal;">${item.nama_barang}</td>
                            <td class="text-right">${item.qty}</td>
                            <td class="text-right">Rp ${Number(item.harga_barang).toLocaleString('id-ID')}</td>
                            <td class="text-right">Rp ${Number(total).toLocaleString('id-ID')}</td>
                            <td class="text-right">${item.qty_out}</td>
                            <td class="text-right">${item.qty_now}</td>
                            <td>
                                <div class="d-flex flex-wrap justify-content-center" style="gap: 0.5rem;">
                                    ${buttons.join('')}
                                </div>
                            </td>
                        </tr>`;
                    });

                    detailFooter.innerHTML = `
                    <tr>
                        <th colspan="6" class="text-right">SubTotal</th>
                        <th class="text-right">Rp ${Number(data.sub_total).toLocaleString('id-ID')}</th>
                        <th colspan="2"></th>
                        <th>
                            <button type="button" class="btn btn-info btn-sm w-100 open-modal-print-all" data-container="body" data-toggle="tooltip" data-placement="top"
                                title="Atur semua print QR Code Pembelian Barang"
                                data-items='${jsonItems}'>
                                <i class="fa fa-print"></i> Print Semua
                            </button>
                        </th>
                    </tr>`;

                    const notyf = new Notyf({
                        duration: 2000,
                        position: {
                            x: 'center',
                            y: 'top'
                        },
                    });

                    document.querySelectorAll('.copy-btn').forEach(button => {
                        button.addEventListener('click', function() {
                            const targetId = this.getAttribute('data-target');
                            const targetText = document.getElementById(targetId)?.textContent;

                            if (targetText) {
                                navigator.clipboard.writeText(targetText).then(() => {
                                    notyf.success('QR Code berhasil disalin!');
                                }).catch(() => {
                                    notyf.error('Gagal menyalin QR Code');
                                });
                            } else {
                                notyf.error('Data QR Code tidak ditemukan');
                            }
                        });
                    });

                    let display_from = (limit * (currentPage - 1)) + 1;
                    let display_to = Math.min(display_from + data.detail.length - 1, pagination.total);

                    $('#countPage').text(`${display_from} - ${display_to}`);
                    $('#totalPage').text(`${pagination.total}`);
                    $('[data-toggle="tooltip"]').tooltip();
                    renderPagination();
                } else {
                    detailBody.innerHTML = `
                    <tr class="text-dark">
                        <th class="text-center" colspan="8">Tidak ada data</th>
                    </tr>`;
                    $('#countPage').text("0 - 0");
                    $('#totalPage').text("0");
                }
            } catch (err) {
                detailBody.innerHTML = `
                <tr class="text-danger">
                    <td colspan="8" class="text-center">Gagal memuat data detail.</td>
                </tr>`;
                $('#countPage').text("0 - 0");
                $('#totalPage').text("0");
            }
        }

        async function openModalEdit(encodedItem) {
            const item = JSON.parse(atob(encodedItem));
            $('#edit_barang').html(item.nama_barang);

            $('#edit_id_detail').val(item.id);
            $('#edit_qty').val(item.qty);
            $('#edit_harga_barang').val(item.harga_barang);

            const minQty = item.qty_out > 0 ? item.qty_out : 1;
            $('#edit_qty').attr('min', minQty);
            $('#min_qty').text(minQty);
            $('#modalEditDetail').modal('show');
        }

        async function submitEditDetail(e) {
            e.preventDefault();

            const submitBtn = $(e.target).find('button[type="submit"]');
            const originalBtnHTML = submitBtn.html();

            submitBtn
                .html(
                    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memperbarui...'
                )
                .prop('disabled', true);

            const id = $('#edit_id_detail').val();
            const qty = $('#edit_qty').val();
            const harga_barang = $('#edit_harga_barang').val();

            const payload = {
                id: id,
                qty: qty,
                harga_barang: harga_barang
            };

            try {
                let response = await renderAPI('PUT', '{{ route('transaksi.pembelianbarang.update-detail') }}',
                    payload);

                if (response.status === 200 && response.data.success) {
                    $('#modalEditDetail').modal('hide');
                    notificationAlert('success', 'Berhasil', 'Data berhasil diperbarui!');
                    await getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch, customFilter);
                } else {
                    notificationAlert('error', 'Pemberitahuan', response.data.message || 'Gagal memperbarui data.');
                }
            } catch (error) {
                console.error(error);
                notificationAlert('error', 'Kesalahan', 'Terjadi kesalahan saat mengirim data.');
            } finally {
                submitBtn.html(originalBtnHTML).prop('disabled', false);
            }
        }

        $('#formEditDetail').on('submit', submitEditDetail);

        async function initPageLoad() {
            await getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch, customFilter);
            await searchList();
            await showData();
        }
    </script>
@endsection
