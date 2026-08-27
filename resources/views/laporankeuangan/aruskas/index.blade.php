@extends('layouts.main')

@section('title')
    Arus Kas
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('css/button-action.css') }}">
    <link rel="stylesheet" href="{{ asset('css/table.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sweetalert2.css') }}">
    <link rel="stylesheet" href="{{ asset('css/flatpickr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/month-select.css') }}">
    <style>
        .th-data {
            width: 160px;
        }

        .td-data {
            width: 100px;
        }

        .data-total {
            min-width: 140px;
        }

        .table-responsive {
            max-height: none !important;
            overflow-y: visible !important;
            overflow-x: auto;
        }

        #bulan_tahun[readonly] {
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
                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-12 col-xl-6 col-lg-8">
                                    <div class="row align-items-center">
                                        <div class="col-12 col-xl-3 col-lg-4 mb-2">
                                            <button class="btn-dynamic btn btn-outline-primary w-100" type="button"
                                                data-toggle="collapse" data-target="#filter-collapse" aria-expanded="false"
                                                aria-controls="filter-collapse">
                                                <i class="fa fa-filter"></i> Filter
                                            </button>
                                        </div>
                                        <div class="col-12 col-xl-9 col-lg-8 mb-2">
                                            <span id="time-report" class="font-weight-bold"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-xl-6 col-lg-4">
                                    <div class="row justify-content-end">
                                        <div class="col-12 col-xl-4 col-lg-6">
                                            <input id="tb-search" class="tb-search form-control mb-2 mb-lg-0" type="search"
                                                name="search" placeholder="Cari Data" aria-label="search">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="content">
                            <div class="card-body p-0">
                                <div class="collapse mt-2" id="filter-collapse">
                                    <form id="custom-filter">
                                        <div class="row">
                                            <div class="col-12 col-xl-8 col-lg-8">
                                                <div class="row mx-2">
                                                    <div class="col-12 col-xl-4 col-lg-4 mb-2">
                                                        <input type="text" id="bulan_tahun" class="form-control"
                                                            placeholder="Pilih Bulan & Tahun" readonly>
                                                    </div>
                                                    <div class="col-12 col-xl-4 col-lg-4 mb-2">
                                                        <select name="f_toko" id="f_toko" class="form-control"
                                                            data-placeholder="Pilih Toko"></select>
                                                    </div>
                                                    <!-- FILTER KATEGORI BARU -->
                                                    <div class="col-12 col-xl-4 col-lg-4 mb-2">
                                                        <select name="f_kategori" id="f_kategori" class="form-control"
                                                            data-placeholder="Pilih Kategori">
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-xl-4 col-lg-4">
                                                <div class="row mx-2 justify-content-end">
                                                    <div class="col-6 col-xl-6 col-lg-6 mb-2 text-end">
                                                        <button form="custom-filter" class="btn btn-info w-100"
                                                            id="tb-filter" type="submit">
                                                            <i class="fa fa-magnifying-glass mr-2"></i>Cari
                                                        </button>
                                                    </div>
                                                    <div class="col-6 col-xl-6 col-lg-6 mb-2 text-end">
                                                        <button type="button" class="btn btn-secondary w-100"
                                                            id="tb-reset">
                                                            <i class="fa fa-rotate mr-2"></i>Reset
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped m-0">
                                        <thead>
                                            <tr class="tb-head" id="head-table">
                                                <th class="text-center text-nowrap align-middle" rowspan="5">No</th>

                                                <!-- HEADER SORTABLE: TANGGAL -->
                                                <th class="text-nowrap align-middle sortable cursor-pointer" rowspan="5"
                                                    data-sort-field="tanggal">
                                                    Tanggal <i class="fa fa-sort sort-icon ml-1"></i>
                                                </th>

                                                <th class="text-nowrap align-middle" rowspan="5">Subjek</th>
                                                <th class="text-nowrap align-middle" rowspan="5">Kategori</th>
                                                <th class="text-nowrap align-middle" rowspan="5">Item</th>

                                                <!-- HEADER SORTABLE: NOMINAL -->
                                                <th class="text-nowrap align-middle sortable cursor-pointer" rowspan="5"
                                                    data-sort-field="nominal">
                                                    Nominal <i class="fa fa-sort sort-icon ml-1"></i>
                                                </th>

                                                <th class="text-nowrap th-data align-middle text-white bg-info">Saldo Akhir
                                                </th>
                                                <th class="text-nowrap th-data align-middle text-white bg-info text-right"
                                                    id="akhir_kas_kecil">-</th>
                                                <th class="text-nowrap th-data align-middle text-white bg-success">Saldo
                                                    Akhir</th>
                                                <th class="text-nowrap th-data align-middle text-white bg-success text-right"
                                                    id="akhir_kas_besar">-</th>
                                                <th class="text-nowrap th-data align-middle text-white bg-warning">Saldo
                                                    Akhir</th>
                                                <th class="text-nowrap th-data align-middle text-white bg-warning text-right"
                                                    id="akhir_piutang">-</th>
                                                <th class="text-nowrap th-data align-middle text-white bg-danger">Saldo
                                                    Akhir</th>
                                                <th class="text-nowrap th-data align-middle text-white bg-danger text-right"
                                                    id="akhir_hutang">-</th>
                                            </tr>
                                            <!-- TR SISA KAS TETAP SAMA -->
                                            <tr class="tb-head">
                                                <th class="text-nowrap align-middle text-white bg-info">Saldo Berjalan</th>
                                                <th class="text-nowrap align-middle text-white bg-info text-right"
                                                    id="berjalan_kas_kecil">-</th>
                                                <th class="text-nowrap align-middle text-white bg-success">Saldo Berjalan
                                                </th>
                                                <th class="text-nowrap align-middle text-white bg-success text-right"
                                                    id="berjalan_kas_besar">-</th>
                                                <th class="text-nowrap align-middle text-white bg-warning">Saldo Berjalan
                                                </th>
                                                <th class="text-nowrap align-middle text-white bg-warning text-right"
                                                    id="berjalan_piutang">-</th>
                                                <th class="text-nowrap align-middle text-white bg-danger">Saldo Berjalan
                                                </th>
                                                <th class="text-nowrap align-middle text-white bg-danger text-right"
                                                    id="berjalan_hutang">-</th>
                                            </tr>
                                            <tr class="tb-head">
                                                <th class="text-nowrap align-middle text-white bg-info">Saldo Awal</th>
                                                <th class="text-nowrap align-middle text-white bg-info text-right"
                                                    id="awal_kas_kecil">-</th>
                                                <th class="text-nowrap align-middle text-white bg-success">Saldo Awal</th>
                                                <th class="text-nowrap align-middle text-white bg-success text-right"
                                                    id="awal_kas_besar">-</th>
                                                <th class="text-nowrap align-middle text-white bg-warning">Saldo Awal</th>
                                                <th class="text-nowrap align-middle text-white bg-warning text-right"
                                                    id="awal_piutang">-</th>
                                                <th class="text-nowrap align-middle text-white bg-danger">Saldo Awal</th>
                                                <th class="text-nowrap align-middle text-white bg-danger text-right"
                                                    id="awal_hutang">-</th>
                                            </tr>
                                            <tr class="tb-head">
                                                <th class="text-nowrap align-middle text-white bg-info">Kas Kecil In</th>
                                                <th class="text-nowrap align-middle text-white bg-info data-total">Kas
                                                    Kecil Out</th>
                                                <th class="text-nowrap align-middle text-white bg-success">Kas Besar In
                                                </th>
                                                <th class="text-nowrap align-middle text-white bg-success data-total">Kas
                                                    Besar Out</th>
                                                <th class="text-nowrap align-middle text-white bg-warning">Piutang In</th>
                                                <th class="text-nowrap align-middle text-white bg-warning data-total">
                                                    Piutang Out</th>
                                                <th class="text-nowrap align-middle text-white bg-danger">Hutang In</th>
                                                <th class="text-nowrap align-middle text-white bg-danger data-total">Hutang
                                                    Out</th>
                                            </tr>
                                            <tr class="tb-head">
                                                <th class="text-nowrap th-data align-middle text-white text-right bg-info"
                                                    id="total_kas_kecil_in">-</th>
                                                <th class="text-nowrap th-data align-middle text-white text-right bg-info"
                                                    id="total_kas_kecil_out">-</th>
                                                <th class="text-nowrap th-data align-middle text-white text-right bg-success"
                                                    id="total_kas_besar_in">-</th>
                                                <th class="text-nowrap th-data align-middle text-white text-right bg-success"
                                                    id="total_kas_besar_out">-</th>
                                                <th class="text-nowrap th-data align-middle text-white text-right bg-warning"
                                                    id="total_piutang_in">-</th>
                                                <th class="text-nowrap th-data align-middle text-white text-right bg-warning"
                                                    id="total_piutang_out">-</th>
                                                <th class="text-nowrap th-data align-middle text-white text-right bg-danger"
                                                    id="total_hutang_in">-</th>
                                                <th class="text-nowrap th-data align-middle text-white text-right bg-danger"
                                                    id="total_hutang_out">-</th>
                                            </tr>
                                        </thead>
                                        <tbody id="listData"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('asset_js')
    <script src="{{ asset('js/pagination.js') }}"></script>
    <script src="{{ asset('js/flatpickr.js') }}"></script>
    <script src="{{ asset('js/month-select.js') }}"></script>
@endsection

@section('js')
    <script>
        let title = 'Arus Kas';
        let defaultLimitPage = 10;
        let totalPage = 1;
        let defaultAscending = 0;
        let defaultSearch = '';
        let customFilter = {};
        let selectOptions = [{
                id: '#f_toko',
                isUrl: '{{ route('master.toko') }}',
                placeholder: 'Pilih Toko'
            },
            {
                id: '#f_kategori',
                isUrl: '{{ route('master.aruskas.getKategori') }}',
                placeholder: 'Pilih Kategori'
            }
        ];
        let currentSortBy = 'tanggal';
        let currentOrder = 'desc';
        let currentAscending = 0;
        let currentPage = 1;
        let currentSearch = '';
        let currentFilter = {};
        let isLoading = false;
        let hasMorePages = true;
        let currentLimit = 30;
        let totalRowCount = 0;
        let skipCount = 0;

        async function getListData(limit = 30, page = 1, ascending = false, search = '', filter = {}, isAppend = false) {
            if (isLoading) return;
            isLoading = true;

            // 🔥 Tentukan nilai OFFSET (skip)
            if (!isAppend) {
                // Jika render/filter baru, hitung skip berdasarkan page (atau reset ke 0 jika page 1)
                skipCount = (page - 1) * limit;
                $('#listData').html(loadingData());
            } else {
                // Jika scroll (isAppend = true), loader di-append di bawah tabel
                $('#listData').append(`
            <tr id="loader-row">
                <td colspan="14" class="text-center py-3">
                    <i class="fa fa-spinner fa-spin"></i> Memuat data lanjutan...
                </td>
            </tr>
        `);
            }

            try {
                let getDataRest = await renderAPI('GET', '{{ route('master.aruskas.get') }}', {
                    limit: limit,
                    skip: skipCount, // 🔥 Dikirim sebagai offset ke Backend Laravel
                    search: search,
                    ascending: ascending,
                    ...filter
                });

                $('#loader-row').remove();

                let backendResponse = getDataRest?.data;
                let rowList = backendResponse?.data;

                if (getDataRest && getDataRest.status == 200 && Array.isArray(rowList)) {
                    hasMorePages = backendResponse.has_more_pages;

                    // 🔥 Tambahkan skipCount sebanyak data yang BARU SAJA didapatkan
                    skipCount += rowList.length;

                    let handleDataArray = await Promise.all(
                        rowList.map(async item => await handleData(item))
                    );

                    await totalListData(backendResponse.data_total);

                    if (isAppend) {
                        let currentCount = $('#listData tr').length;
                        await appendListData(handleDataArray, currentCount);
                    } else {
                        await setListData(handleDataArray);
                    }
                }
            } catch (err) {
                $('#loader-row').remove();
                console.error("Error fetching data:", err);
            } finally {
                isLoading = false;
            }
        }

        // --------------------------------------------------------------------------
        // INFINITE SCROLL LISTENER (Sangat Adaptif)
        // --------------------------------------------------------------------------
        function setupInfiniteScroll() {
            // 1. Tangkap target scroll (bisa div container tabel atau window)
            let scrollTarget = $('.table-responsive').length ? $('.table-responsive') : $(window);

            scrollTarget.off('scroll').on('scroll', function() {
                let container = $(this);
                let scrollTop, innerHeight, scrollHeight;

                if (container.is(window)) {
                    scrollTop = $(window).scrollTop();
                    innerHeight = $(window).height();
                    scrollHeight = $(document).height();
                } else {
                    scrollTop = container.scrollTop();
                    innerHeight = container.innerHeight();
                    scrollHeight = container[0].scrollHeight;
                }

                // Trigger jika sisa scroll tinggal 150px dari bawah
                if ((scrollTop + innerHeight) >= (scrollHeight - 150)) {
                    if (hasMorePages && !isLoading) {
                        currentPage++;
                        getListData(currentLimit, currentPage, currentAscending, currentSearch, currentFilter,
                            true);
                    }
                }
            });
        }

        // Panggil sekali saat document ready / inisialisasi awal
        $(document).ready(function() {
            setupInfiniteScroll();
        });

        function generateRowHtml(element, index) {
            // Menangani kondisi jika fungsi handleData(item) mengembalikan objek bertingkat
            let row = element?.data ? element.data : element;
            let classCol = 'align-top text-dark text-wrap';

            return `
        <tr class="text-dark">
            <td class="${classCol} text-center">${index + 1}.</td>
            <td class="${classCol} td-data">${row.tgl}</td>
            <td class="${classCol} td-data">${row.subjek}</td>
            <td class="${classCol}">${row.kategori}</td>
            <td class="${classCol} td-data">${row.item}</td>
            <td class="${classCol} text-right">${row.nilai_transaksi}</td>
            <td class="${classCol} text-right">${row.kas_kecil_in}</td>
            <td class="${classCol} text-right">${row.kas_kecil_out}</td>
            <td class="${classCol} text-right">${row.kas_besar_in}</td>
            <td class="${classCol} text-right">${row.kas_besar_out}</td>
            <td class="${classCol} text-right">${row.piutang_in}</td>
            <td class="${classCol} text-right">${row.piutang_out}</td>
            <td class="${classCol} text-right">${row.hutang_in}</td>
            <td class="${classCol} text-right">${row.hutang_out}</td>
        </tr>`;
        }

        async function handleData(data) {

            return {
                id: data?.id ?? '-',
                tgl: data?.tgl,
                subjek: data?.subjek ?? '-',
                kategori: data?.kategori ?? '-',
                item: data?.item ?? '-',
                nilai_transaksi: data?.nilai_transaksi ?? 0,
                kas_kecil_in: data?.kas_kecil_in ?? 0,
                kas_kecil_out: data?.kas_kecil_out ?? 0,
                kas_besar_in: data?.kas_besar_in ?? 0,
                kas_besar_out: data?.kas_besar_out ?? 0,
                piutang_in: data?.piutang_in ?? 0,
                piutang_out: data?.piutang_out ?? 0,
                hutang_in: data?.hutang_in ?? 0,
                hutang_out: data?.hutang_out ?? 0
            };
        }

        async function setListData(dataList) {
            let getDataTable = '';
            dataList.forEach((element, index) => {
                getDataTable += generateRowHtml(element, index);
            });
            $('#listData').html(getDataTable);
            $('[data-toggle="tooltip"]').tooltip();
        }

        async function appendListData(dataList, startIndex) {
            let getDataTable = '';
            dataList.forEach((element, index) => {
                getDataTable += generateRowHtml(element, startIndex + index);
            });
            $('#listData').append(getDataTable);
            $('[data-toggle="tooltip"]').tooltip();
        }

        function setInputFilter() {
            const now = new Date();
            const year = now.getFullYear();
            const monthText = now.toLocaleString('id-ID', {
                month: 'long'
            });

            $('#time-report').html(
                `<i class="fa fa-calendar mr-1"></i><b>${title}</b> (Bulan <b class="text-primary">${monthText}</b> Tahun <b class="text-primary">${year}</b>)`
            );

            const bulanID = [
                "Januari", "Februari", "Maret", "April", "Mei", "Juni",
                "Juli", "Agustus", "September", "Oktober", "November", "Desember"
            ];

            flatpickr("#bulan_tahun", {
                plugins: [
                    new monthSelectPlugin({
                        shorthand: false,
                        dateFormat: "F Y",
                        theme: "light"
                    })
                ],
                disableMobile: true,
                locale: {
                    firstDayOfWeek: 1,
                    months: {
                        shorthand: bulanID,
                        longhand: bulanID
                    }
                },
                onReady: function(selectedDates, dateStr, instance) {
                    setTimeout(() => translateMonthPicker(), 10);
                    if (selectedDates.length > 0) {
                        instance.setDate(
                            `${bulanID[selectedDates[0].getMonth()]} ${selectedDates[0].getFullYear()}`,
                            false);
                    }
                },
                onChange: function(selectedDates, dateStr, instance) {
                    if (selectedDates.length > 0) {
                        instance.input.value =
                            `${bulanID[selectedDates[0].getMonth()]} ${selectedDates[0].getFullYear()}`;
                    }
                },
                onOpen: function() {
                    setTimeout(() => translateMonthPicker(), 10);
                }
            });
        }

        function translateMonthPicker() {
            const bulanID = [
                "Januari", "Februari", "Maret", "April", "Mei", "Juni",
                "Juli", "Agustus", "September", "Oktober", "November", "Desember"
            ];

            setTimeout(() => {
                $(".flatpickr-monthSelect-month").each(function(index) {
                    $(this).text(bulanID[index]);
                });
            }, 50);
        }

        async function totalListData(data) {
            let kas_kecil, kas_besar, piutang, hutang;

            if (data == null) {
                kas_kecil = {};
                kas_besar = {};
                piutang = {};
                hutang = {};
            } else {
                kas_kecil = data?.kas_kecil ?? {};
                kas_besar = data?.kas_besar ?? {};
                piutang = data?.piutang ?? {};
                hutang = data?.hutang ?? {};
            }

            function formatNumber(value) {
                return (value ?? 0).toLocaleString('id-ID');
            }

            $('#akhir_kas_kecil').html(formatNumber(kas_kecil.saldo_akhir));
            $('#berjalan_kas_kecil').html(formatNumber(kas_kecil.saldo_berjalan));
            $('#awal_kas_kecil').html(formatNumber(kas_kecil.saldo_awal));
            $('#total_kas_kecil_in').html(formatNumber(kas_kecil.kas_kecil_in));
            $('#total_kas_kecil_out').html(formatNumber(kas_kecil.kas_kecil_out));

            $('#akhir_kas_besar').html(formatNumber(kas_besar.saldo_akhir));
            $('#berjalan_kas_besar').html(formatNumber(kas_besar.saldo_berjalan));
            $('#awal_kas_besar').html(formatNumber(kas_besar.saldo_awal));
            $('#total_kas_besar_in').html(formatNumber(kas_besar.kas_besar_in));
            $('#total_kas_besar_out').html(formatNumber(kas_besar.kas_besar_out));

            $('#akhir_piutang').html(formatNumber(piutang.saldo_akhir));
            $('#berjalan_piutang').html(formatNumber(piutang.saldo_berjalan));
            $('#awal_piutang').html(formatNumber(piutang.saldo_awal));
            $('#total_piutang_in').html(formatNumber(piutang.piutang_in));
            $('#total_piutang_out').html(formatNumber(piutang.piutang_out));

            $('#akhir_hutang').html(formatNumber(hutang.saldo_akhir));
            $('#berjalan_hutang').html(formatNumber(hutang.saldo_berjalan));
            $('#awal_hutang').html(formatNumber(hutang.saldo_awal));
            $('#total_hutang_in').html(formatNumber(hutang.hutang_in));
            $('#total_hutang_out').html(formatNumber(hutang.hutang_out));
        }

        async function filterList() {
            function defaultTime(monthText, year) {
                const now = new Date();
                const yearDefault = now.getFullYear();
                const monthTextDefault = now.toLocaleString('id-ID', {
                    month: 'long'
                });
                $('#time-report').html(
                    `<i class="fa fa-calendar mr-1"></i><b>${title}</b> (Bulan <b class="text-primary">${monthText || monthTextDefault}</b> Tahun <b class="text-primary">${year || yearDefault}</b>)`
                );
            }

            document.getElementById('custom-filter').addEventListener('submit', async function(e) {
                e.preventDefault();

                let bulanTahun = document.getElementById("bulan_tahun").value.trim();

                let monthText = '',
                    year = '',
                    month = '';

                if (bulanTahun) {
                    let parts = bulanTahun.split(" ");
                    if (parts.length === 2) {
                        monthText = parts[0];
                        year = parts[1];
                        month = getMonthNumber(monthText);
                    }
                }

                customFilter = {
                    year: year || '',
                    month: month || '',
                };

                let selectedTokoIds = $('#f_toko').val();
                if (selectedTokoIds && selectedTokoIds.length > 0) {
                    customFilter['toko_id'] = selectedTokoIds;
                }

                // TANGKAP FILTER KATEGORI (Mendukung Single / Multi-Select)
                let selectedKategori = $('#f_kategori').val();
                if (selectedKategori) {
                    customFilter['kategori'] = selectedKategori;
                }

                defaultSearch = $('.tb-search').val();
                currentPage = 1; // Reset halaman ke 1 saat submit filter

                await defaultTime(monthText, year);
                // IsAppend set to false (replace data)
                await getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch,
                    customFilter, false);
            });

            document.getElementById('tb-reset').addEventListener('click', async function() {
                $('#bulan_tahun').val('').trigger('change');
                $('#custom-filter select').val(null).trigger('change');
                $('.tb-search').val(''); // Reset input search juga
                skipCount = 0;
                customFilter = {};
                defaultSearch = '';
                currentPage = 1;

                // Reset Sorting
                currentSortBy = 'tanggal';
                currentOrder = 'desc';
                updateSortIcons();

                await defaultTime(null, null);
                await getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch,
                    customFilter, false);
            });

            // EVENT HANDLER UNTUK SORTING KOLOM
            $('.sortable').on('click', async function() {
                let field = $(this).data('sort-field');

                if (currentSortBy === field) {
                    currentOrder = currentOrder === 'asc' ? 'desc' : 'asc';
                } else {
                    currentSortBy = field;
                    currentOrder = 'asc';
                }

                updateSortIcons();
                currentPage = 1; // Reset ke page 1 jika sorting diubah
                await getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch,
                    customFilter, false);
            });
        }

        $('.table-responsive').off('scroll').on('scroll', async function() {
            let el = this;

            // Perhitungan murni posisi bawah elemen (tidak terpengaruh geseran horizontal)
            let isAtBottom = (el.scrollHeight - el.scrollTop) <= (el.clientHeight + 50);

            if (isAtBottom) {
                if (hasMorePages && !isLoading) {
                    currentPage++;

                    await getListData(
                        30,
                        currentPage,
                        defaultAscending,
                        defaultSearch,
                        customFilter,
                        true
                    );
                }
            }
        });

        // Hapus event lama, pasang event global yang mendeteksi window atau container utama tabel
        $(window).off('scroll.infiniteList').on('scroll.infiniteList', async function() {
            // Cek posisi scroll dari jendela browser / parent container utama
            let scrollHeight = $(document).height();
            let scrollTop = $(window).scrollTop();
            let clientHeight = $(window).height();

            // Jika sisa jarak dari bawah tinggal 100px
            if (scrollHeight - (scrollTop + clientHeight) <= 100) {
                if (hasMorePages && !isLoading) {
                    currentPage++;

                    await getListData(
                        30,
                        currentPage,
                        defaultAscending,
                        defaultSearch,
                        customFilter,
                        true // isAppend = true
                    );
                }
            }
        });

        function updateSortIcons() {
            $('.sortable').each(function() {
                let field = $(this).data('sort-field');
                let icon = $(this).find('.sort-icon');

                if (field === currentSortBy) {
                    if (currentOrder === 'asc') {
                        icon.attr('class', 'fa fa-sort-up sort-icon ml-1 text-primary');
                    } else {
                        icon.attr('class', 'fa fa-sort-down sort-icon ml-1 text-primary');
                    }
                } else {
                    icon.attr('class', 'fa fa-sort sort-icon ml-1 text-muted');
                }
            });
        }

        function getMonthNumber(monthName) {
            const monthNames = {
                "Januari": "1",
                "Februari": "2",
                "Maret": "3",
                "April": "4",
                "Mei": "5",
                "Juni": "6",
                "Juli": "7",
                "Agustus": "8",
                "September": "9",
                "Oktober": "10",
                "November": "11",
                "Desember": "12"
            };
            return monthNames[monthName] || '';
        }

        async function initPageLoad() {
            await setDynamicButton();
            await selectMulti(selectOptions);
            await setInputFilter();
            await getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch, customFilter);
            await searchList();
            await filterList();
        }
    </script>
@endsection
