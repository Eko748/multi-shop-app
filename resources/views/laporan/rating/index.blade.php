@extends('layouts.main')

@section('title')
    Rekapitulasi Rating Barang
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('css/button-action.css') }}">
    <link rel="stylesheet" href="{{ asset('css/table.css') }}">
    <link rel="stylesheet" href="{{ asset('css/daterange-picker.css') }}">
    <style>
        #daterange[readonly] {
            background-color: white !important;
            cursor: pointer !important;
            color: inherit !important;
        }
    </style>
@endsection

@section('content')
    <div class="pcoded-main-container">
        <div class="pcoded-content pt-1 mt-1">
            @include('components.breadcrumbs')
            <div class="row">
                <div class="col-md-7">
                    <div class="card">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-12 col-sm-12 col-md-8 col-lg-8 col-xl-6">
                                    <div class="row align-items-center">
                                        <div class="col-12 col-sm-12 col-md-4 col-lg-3 col-xl-3 col-xxl-2 mb-2">
                                            <button class="btn-dynamic btn btn-outline-primary w-100" type="button"
                                                data-toggle="collapse" data-target="#filter-collapse" aria-expanded="false"
                                                aria-controls="filter-collapse">
                                                <i class="fa fa-filter"></i> Filter
                                            </button>
                                        </div>
                                        <div class="col-12 col-sm-12 col-md-8 col-lg-9 col-xl-9 col-xxl-10 mb-2">
                                            <span id="time-report" class="font-weight-bold"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-sm12 col-md-4 col-lg-4 col-xl-6">
                                    <div class="row justify-content-end">
                                        <div class="col-4 col-sm-4 col-md-4 col-lg-4 col-xl-3 col-xxl-2">
                                            <select name="limitPage" id="limitPage" class="form-control mr-2 mb-2 mb-lg-0">
                                                <option value="100">100</option>
                                                <option value="150">150</option>
                                                <option value="200">200</option>
                                            </select>
                                        </div>
                                        <div class="col-8 col-sm-8 col-md-8 col-lg-8 col-xl-5 col-xxl-4">
                                            <input id="tb-search" class="tb-search form-control mb-2 mb-lg-0" type="search"
                                                name="search" placeholder="Cari Data" aria-label="search">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="content">
                            <div class="collapse my-2 px-4" id="filter-collapse">
                                <form id="custom-filter" class="row g-2 align-items-end">
                                    <div class="col-md-4">
                                        <input class="form-control" type="text" id="daterange" name="daterange"
                                            placeholder="Pilih rentang tanggal">
                                    </div>
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
                                        <thead id="tableHeader"></thead>
                                        <tbody id="listData"></tbody>
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
                <div class="col-md-5">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <span><i class="fa fa-cart-plus mr-2"></i>Plan Order</span>
                            <div>
                                <button id="btn-download-pdf" class="btn btn-light btn-sm text-danger mr-1">
                                    <i class="fa fa-file-pdf"></i> Download
                                </button>
                                <button id="btn-print-pdf" class="btn btn-light btn-sm text-primary mr-1">
                                    <i class="fa fa-print"></i> Print
                                </button>
                                <button id="btn-save-plan" class="btn btn-light btn-sm text-success d-none">
                                    <i class="fa fa-save"></i> Save
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0" id="planTable">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 50px;">No</th>
                                        <th>Nama Barang</th>
                                        <th>Qty</th>
                                        <th>HPP</th>
                                        <th>Total HPP</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="planList">
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Belum ada item</td>
                                    </tr>
                                </tbody>
                                <tfoot class="bg-light">
                                    <tr>
                                        <th colspan="4" class="text-right">Total</th>
                                        <th id="grandTotal" class="text-right">Rp 0</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.6.0/jspdf.plugin.autotable.min.js"></script>
@endsection

@section('js')
    <script>
        let title = 'Rating Barang';
        let defaultLimitPage = 100;
        let currentPage = 1;
        let totalPage = 1;
        let defaultAscending = 0;
        let defaultSearch = '';
        let customFilter = {};
        let selectOptions = [{
            id: '#jenis_barang',
            isUrl: '{{ route('master.jenisBarang') }}',
            placeholder: 'Pilih Jenis Barang',
        }];

        async function getListData(limit = 100, page = 1, ascending = 0, search = '', customFilter = {}) {
            $('#listData').html(loadingData());

            let filterParams = {
                ...customFilter
            };

            let getDataRest = await renderAPI(
                    'GET',
                    '{{ route('rekapitulasi.getRatingBarang') }}', {
                        page: page,
                        limit: limit,
                        ascending: ascending,
                        search: search,
                        id_user: '{{ auth()->user()->id }}',
                        ...filterParams
                    }
                ).then(response => response)
                .catch(error => error.response);

            if (getDataRest && getDataRest.status == 200 && getDataRest.data?.data) {
                await setListData(getDataRest.data.data, getDataRest.data.pagination);
            } else {
                const errorMessage = getDataRest?.data?.message || 'Terjadi kesalahan';
                const errorRow = `
            <tr class="text-dark">
                <th class="text-center" colspan="999">${errorMessage}</th>
            </tr>`;
                $('#listData').html(errorRow);
                $('#countPage').text("0 - 0");
                $('#totalPage').text("0");
            }
        }

        async function setListData(dataObject, pagination) {
            totalPage = pagination.total_pages;
            currentPage = pagination.current_page;

            const dataListArray = Object.entries(dataObject);
            const dataCount = dataListArray.length;

            let display_from = ((defaultLimitPage * (currentPage - 1)) + 1);
            let display_to = Math.min(display_from + dataCount - 1, pagination.total);

            let classCol = 'align-center text-dark text-wrap';
            let tableHead = '';
            let subHeader = '';
            let getDataTable = '';
            let rowIndex = display_from;

            if (dataCount === 0) {
                $('#listData').html(`
            <tr class="text-dark">
                <td class="${classCol} text-center" colspan="999">Tidak ada data</td>
            </tr>
        `);
                $('#tableHeader').html('');
                $('#countPage').text("0 - 0");
                $('#totalPage').text("0");
                return;
            }

            const firstBarang = dataListArray[0][0];
            const isSingleToko = dataObject[firstBarang]?.hasOwnProperty('Jumlah Item Terjual');
            const isMultiToko = dataObject[firstBarang]?.hasOwnProperty('Jumlah Item Terjual Per Toko');

            let tokoList = [];

            if (isMultiToko) {
                tokoList = Object.keys(dataObject[firstBarang]['Jumlah Item Terjual Per Toko']);
            }

            tableHead += `<tr class="tb-head text-dark">
                <th class="${classCol} text-center" rowspan="${isMultiToko ? 2 : 1}">NO</th>
                <th class="${classCol}" rowspan="${isMultiToko ? 2 : 1}">NAMA BARANG</th>`;

                    if (isSingleToko) {
                        tableHead += `<th class="${classCol} text-center" rowspan="1">Jumlah Item Terjual</th>`;
                    } else if (isMultiToko) {
                        tableHead +=
                            `<th class="${classCol} text-center" colspan="${tokoList.length}">Jumlah Item Terjual Per Toko</th>`;
                    }

                    tableHead += `
                <th class="${classCol} text-center" rowspan="${isMultiToko ? 2 : 1}">Stock Sekarang</th>
                <th class="${classCol} text-center" rowspan="${isMultiToko ? 2 : 1}">HPP</th>
                <th class="${classCol} text-center" rowspan="${isMultiToko ? 2 : 1}">Action</th>
            </tr>`;

            if (isMultiToko) {
                subHeader += `<tr class="tb-subhead text-dark">`;
                tokoList.forEach(toko => {
                    subHeader += `<th class="${classCol} text-center">${toko}</th>`;
                });
                subHeader += `</tr>`;
            }

            $('#tableHeader').html(tableHead + subHeader);

            dataListArray.forEach(([namaBarang, tokoData]) => {
                let row = `<tr class="text-dark">
            <td class="${classCol} text-center">${rowIndex++}</td>
            <td class="${classCol}">${namaBarang}</td>`;

                if (isSingleToko) {
                    const jumlahTerjual = tokoData['Jumlah Item Terjual'] ?? 0;
                    row +=
                        `<td class="${classCol} text-center"><span class="font-weight-bold">${jumlahTerjual}</span></td>`;
                } else if (isMultiToko) {
                    tokoList.forEach(toko => {
                        const jumlah = tokoData['Jumlah Item Terjual Per Toko'][toko] ?? 0;
                        row +=
                            `<td class="${classCol} text-center"><span class="font-weight-bold">${jumlah}</span></td>`;
                    });
                }

                const stockNow = tokoData['Stock Sekarang'] ?? 0;
                row += `<td class="${classCol} text-center">${stockNow}</td>`;

                const hppJual = tokoData['HPP Jual'] ?? 0;
                row += `<td class="${classCol} text-center">${formatRupiah(hppJual)}</td>
                        <td class="${classCol} text-center">
                            <button class="btn btn-sm btn-outline-primary btn-add-plan"
                                data-nama="${namaBarang}"
                                data-hpp="${hppJual}">
                                <i class="fa fa-plus"></i> Tambah
                            </button>
                        </td>`;

                row += `</tr>`;
                getDataTable += row;
            });

            $('#listData').html(getDataTable);
            $('#totalPage').text(pagination.total);
            $('#countPage').text(`${display_from} - ${display_to}`);
            $('[data-toggle="tooltip"]').tooltip();
            renderPagination();
        }

        let planList = [];

        function renderPlanTable() {
            const tbody = $('#planList');
            tbody.empty();

            if (planList.length === 0) {
                tbody.html(`<tr><td colspan="5" class="text-center text-muted">Belum ada item</td></tr>`);
                $('#grandTotal').text('Rp 0');
                return;
            }

            let totalAll = 0;
            planList.forEach((item, i) => {
                const total = item.hpp * item.qty;
                totalAll += total;

                tbody.append(`
                    <tr>
                        <td class="text-center">${i + 1}</td>
                        <td style="white-space: normal; word-wrap: break-word; max-width: 200px;">${item.nama}</td>
                        <td><input type="number" min="1" class="form-control form-control-sm qty-input"
                            data-index="${i}" value="${item.qty}"></td>
                        <td class="text-right">${formatRupiah(item.hpp)}</td>
                        <td class="text-right total-hpp" id="total-${i}">${formatRupiah(total)}</td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-danger btn-remove" data-index="${i}">
                                <i class="fa fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `);
            });

            $('#grandTotal').text(formatRupiah(totalAll));
        }

        $(document).on('click', '.btn-add-plan', function() {
            const nama = $(this).data('nama');
            const hpp = parseFloat($(this).data('hpp'));

            // Cek apakah sudah ada
            const existing = planList.findIndex(p => p.nama === nama);
            if (existing !== -1) {
                planList[existing].qty += 1;
            } else {
                planList.push({
                    nama,
                    qty: 1,
                    hpp
                });
            }
            renderPlanTable();
        });

        $(document).on('input', '.qty-input', function() {
            const index = $(this).data('index');
            const qty = parseInt($(this).val()) || 0;
            planList[index].qty = qty;
            renderPlanTable();
        });

        $(document).on('click', '.btn-remove', function() {
            const index = $(this).data('index');
            planList.splice(index, 1);
            renderPlanTable();
        });

        $('#btn-save-plan').on('click', async function() {
            if (planList.length === 0) {
                alert('Belum ada item yang ditambahkan.');
                return;
            }
            // TODO: kirim ke API (contoh simulasi)
            console.log('Simpan plan:', planList);
            alert('Plan order berhasil disimpan!');
        });

        // ====== Fungsi utilitas tanggal sekarang ======
        function getCurrentDateTime() {
            const now = new Date();
            const day = String(now.getDate()).padStart(2, '0');
            const month = now.toLocaleString('id-ID', {
                month: 'long'
            });
            const year = now.getFullYear();
            const time = now.toLocaleTimeString('id-ID', {
                hour12: false
            });
            return `Tanggal ${day} ${month} ${year} - Pukul ${time}`;
        }

        // ====== PRINT PDF (pop-up window seperti sebelumnya) ======
        $('#btn-print-pdf').on('click', function() {
            if (planList.length === 0) {
                alert('Belum ada data untuk dicetak.');
                return;
            }

            const printWindow = window.open('', '_blank');
            const currentDate = getCurrentDateTime();

            let html = `
        <html>
        <head>
            <title>Plan Order PDF</title>
            <style>
                body { font-family: Arial; padding: 20px; }
                h3 { margin-bottom: 5px; }
                p.date-text { margin-top: 0; font-size: 14px; color: #555; }
                table { border-collapse: collapse; width: 100%; margin-top: 20px; }
                th, td { border: 1px solid #000; padding: 8px; text-align: center; }
                th { background: #f0f0f0; }
                tfoot td { font-weight: bold; }
            </style>
        </head>
        <body>
            <h3>Plan Order Barang</h3>
            <p class="date-text">${currentDate}</p>
            <table>
                <thead>
                    <tr>
                        <th>No</th><th>Nama Barang</th><th>Qty</th><th>HPP</th><th>Total HPP</th>
                    </tr>
                </thead>
                <tbody>
    `;

            let totalAll = 0;
            planList.forEach((p, i) => {
                const total = p.qty * p.hpp;
                totalAll += total;
                html += `
            <tr>
                <td>${i + 1}</td>
                <td style="text-align: left;">${p.nama}</td>
                <td>${p.qty}</td>
                <td style="text-align: right;">${formatRupiah(p.hpp)}</td>
                <td style="text-align: right;">${formatRupiah(total)}</td>
            </tr>
        `;
            });

            html += `
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4">Total</td>
                        <td style="text-align: right;">${formatRupiah(totalAll)}</td>
                    </tr>
                </tfoot>
            </table>
        </body>
        </html>
    `;

            printWindow.document.write(html);
            printWindow.document.close();
            printWindow.print();
        });

        $('#btn-download-pdf').on('click', async function() {
            if (planList.length === 0) {
                alert('Belum ada data untuk diunduh.');
                return;
            }

            const {
                jsPDF
            } = window.jspdf;
            const doc = new jsPDF();
            const currentDate = getCurrentDateTime();

            doc.setFontSize(14);
            doc.text('Plan Order Barang', 14, 15);
            doc.setFontSize(11);
            doc.text(currentDate, 14, 22);

            const tableData = planList.map((p, i) => [
                i + 1,
                p.nama,
                p.qty,
                formatRupiah(p.hpp),
                formatRupiah(p.qty * p.hpp)
            ]);

            const totalAll = planList.reduce((sum, p) => sum + (p.qty * p.hpp), 0);

            doc.autoTable({
                startY: 30,
                head: [
                    ['No', 'Nama Barang', 'Qty', 'HPP', 'Total HPP']
                ],
                body: tableData,
                foot: [
                    ['', '', '', 'Total', formatRupiah(totalAll)]
                ],
                theme: 'grid',
                styles: {
                    fontSize: 10,
                    cellPadding: 3
                },
                headStyles: {
                    fillColor: [66, 135, 245],
                    halign: 'center'
                },
                columnStyles: {
                    0: {
                        halign: 'center',
                        cellWidth: 15
                    }, // No
                    1: {
                        halign: 'left',
                        cellWidth: 60
                    }, // Nama Barang
                    2: {
                        halign: 'center',
                        cellWidth: 20
                    }, // Qty
                    3: {
                        halign: 'right',
                        cellWidth: 35
                    }, // HPP
                    4: {
                        halign: 'right',
                        cellWidth: 35
                    } // Total HPP
                },
                footStyles: {
                    fillColor: [66, 135, 245],
                    fontStyle: 'bold',
                    halign: 'right'
                }
            });

            doc.save(`Plan Order_${currentDate}.pdf`);
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
                    startDate: $("#daterange").val() != '' ? startDate : '',
                    endDate: $("#daterange").val() != '' ? endDate : '',
                    jenis_barang: $("#jenis_barang").val() || '',
                };

                defaultSearch = $('.tb-search').val();
                defaultLimitPage = $("#limitPage").val();
                currentPage = 1;

                $('#time-report').html(
                    `<i class="fa fa-file-text mr-1"></i><b>${title}</b> <br>(<b class="text-primary">${startDate}</b> s/d <b class="text-primary">${endDate}</b>)`
                );

                await getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch,
                    customFilter);
            });

            document.getElementById('tb-reset').addEventListener('click', async function() {
                $('#daterange').val('');
                $('#custom-filter select').val(null).trigger('change');
                customFilter = {};
                defaultSearch = $('.tb-search').val();
                defaultLimitPage = $("#limitPage").val();
                currentPage = 1;
                await setTimeReport();
                await getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch,
                    customFilter);
            });
        }

        function setTimeReport() {
            const now = new Date();
            const formattedNow =
                `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')} ${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}:${String(now.getSeconds()).padStart(2, '0')}`;

            $('#time-report').html(
                `<i class="fa fa-file-text mr-1"></i><b>${title}</b> saat ini <br>(<b class="text-primary">${formattedNow}</b>)`
            );
        }

        async function initPageLoad() {
            await selectData(selectOptions);
            await setTimeReport();
            await setDynamicButton();
            await getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch, customFilter);
            await searchList();
            await filterList();
        }
    </script>
@endsection
