@extends('layouts.main')

@section('title')
    Neraca
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('css/table.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sweetalert2.css') }}">
    <link rel="stylesheet" href="{{ asset('css/flatpickr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/month-select.css') }}">
    <style>
        #bulan_tahun[readonly] {
            background-color: white !important;
            cursor: pointer !important;
            color: inherit !important;
        }

        .space-blank {
            width: 30px;
        }

        #listData {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }

        #listData td,
        #listData th {
            padding: 8px;
            border: 1px solid #dee2e6;
            vertical-align: top;
        }

        #listData .kategori-data td:nth-child(1),
        #listData .kategori-data td:nth-child(5) {
            width: 4%;
        }

        #listData .kategori-data td:nth-child(2),
        #listData .kategori-data td:nth-child(6) {
            width: 34%;
        }

        #listData .kategori-data td:nth-child(3),
        #listData .kategori-data td:nth-child(7) {
            width: 10%;
        }

        #listData .kategori-data td:nth-child(4) {
            width: 5%;
        }

        /* ===== Neraca table helpers ===== */
        .table-neraca td {
            vertical-align: middle;
        }

        .table-neraca .cell-wrap.invisible {
            visibility: hidden;
        }

        /* sembunyikan konten tanpa mengubah layout */
        .table-neraca .child-code {
            font-weight: 600;
            opacity: .7;
            margin-right: .35rem;
        }

        .table-neraca .toggle-child {
            border: 0;
            background: transparent;
            cursor: pointer;
        }

        .table-neraca .toggle-child .chev {
            font-size: .9rem;
        }

        .table-neraca .indent {
            padding-left: 1.25rem;
        }

        /* geser turunan */
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
                            <div class="row align-items-center">
                                <div class="col-12 col-xl-6 col-lg-4 mb-2">
                                    <span id="time-report" class="font-weight-bold"></span>
                                    <br>
                                    <a href="{{ route('laporankeuangan.neraca.penyesuaian.index') }}"
                                        class="btn btn-primary btn-md mt-2" title="Tambah Data Penyesuaian Neraca">
                                        <i class="fa fa-circle-plus"></i>
                                        <span class="d-none d-sm-inline">Penyesuaian Neraca</span>
                                    </a>
                                </div>
                                <div class="col-12 col-xl-6 col-lg-8 mb-2">
                                    <form id="custom-filter" class="row justify-content-end">
                                        <div class="col-12 col-xl-6 col-lg-6 mb-2">
                                            <input type="text" id="bulan_tahun" class="form-control"
                                                placeholder="Pilih Bulan & Tahun" readonly>
                                        </div>
                                        <div class="col-6 col-xl-3 col-lg-3">
                                            <button form="custom-filter" class="btn btn-info w-100" id="tb-filter"
                                                type="submit">
                                                <i class="fa fa-magnifying-glass mr-2"></i>Cari
                                            </button>
                                        </div>
                                        <div class="col-6 col-xl-3 col-lg-3">
                                            <button type="button" class="btn btn-secondary w-100" id="tb-reset">
                                                <i class="fa fa-rotate mr-2"></i>Reset
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="content">
                            <div class="d-flex justify-content-center">
                                <div class="card w-75">
                                    <div class="card-body p-2">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped m-0">
                                                <tbody id="listData" class="container-fluid">
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-center" id="note">
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
        let title = 'Neraca';
        let defaultLimitPage = 10;
        let currentPage = 1;
        let totalPage = 1;
        let defaultAscending = 0;
        let defaultSearch = '';
        let customFilter = {};

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

        async function getListData(limit = 10, page = 1, ascending = 0, search = '', customFilter = {}) {
            $('#listData').html(loadingData());

            let filterParams = {};

            if (customFilter['month'] && customFilter['year']) {
                filterParams.month = customFilter['month'];
                filterParams.year = customFilter['year'];
            }

            let getDataRest = await renderAPI(
                    'GET',
                    '{{ route('master.getNeraca') }}', {
                        page: page,
                        limit: limit,
                        ascending: ascending,
                        search: search,
                        id_toko: '{{ auth()->user()->id_toko }}',
                        ...filterParams
                    }
                ).then(response => response)
                .catch(error => error.response || {});

            if (getDataRest && getDataRest.status == 200 && Array.isArray(getDataRest.data.data)) {
                let handleDataArray = getDataRest.data.data;
                let note = getDataRest.data.note;
                await setListData(handleDataArray);
                $('#note').html(`
                    <div class="card w-75">
                        <div class="card-body p-2">
                            <div id="note">
                                <p class="font-weight-bold">Catatan:</p>
                                <ul>
                                    <li>Stock Hilang: <span id="stock_hilang_qty">${note.stock_hilang.qty}</span> Qty, Total HPP:
                                        <span id="stock_hilang_total_hpp">${formatRupiah(note.stock_hilang.total_hpp)}</span></li>
                                    <li>Stock Mati: <span id="stock_mati_qty">${note.stock_mati.qty}</span> Qty, Total HPP:
                                        <span id="stock_mati_total_hpp">${formatRupiah(note.stock_mati.total_hpp)}</span></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                `);
            } else {
                let errorMessage = getDataRest?.data?.message || 'Data gagal dimuat';
                let errorRow = `
                <tr class="text-dark">
                    <th class="text-center" colspan="${$('.tb-head th').length}"> ${errorMessage} </th>
                </tr>`;
                $('#listData').html(errorRow);
            }
        }

        async function setListData(dataList) {
            let html = '';

            const aktiva = dataList.find(k => k.kategori === 'AKTIVA') || {
                total: 0,
                subkategori: []
            };
            const pasiva = dataList.find(k => k.kategori === 'PASIVA') || {
                total: 0,
                subkategori: []
            };

            const subAktiva = aktiva.subkategori || [];
            const subPasiva = pasiva.subkategori || [];

            const maxSub = Math.max(subAktiva.length, subPasiva.length);

            html += `
        <tr class="font-weight-bold bg-light">
            <td colspan="2">AKTIVA</td>
            <td class="text-right">${aktiva.format}</td>
            <td></td>
            <td colspan="2">PASIVA</td>
            <td class="text-right">${pasiva.format}</td>
        </tr>
    `;

            for (let i = 0; i < maxSub; i++) {
                const subA = subAktiva[i] || {
                    judul: '',
                    total: '',
                    item: []
                };
                const subP = subPasiva[i] || {
                    judul: '',
                    total: '',
                    item: []
                };

                // Map items (normalize)
                const aRows = (subA.item || []).map(x => ({
                    kode: x.kode ?? '',
                    nama: x.nama ?? '',
                    nilai: x.nilai ?? '',
                    format: x.format ?? '',
                    sub: x.sub ?? null
                }));
                const pRows = (subP.item || []).map(x => ({
                    kode: x.kode ?? '',
                    nama: x.nama ?? '',
                    nilai: x.nilai ?? '',
                    format: x.format ?? '',
                    sub: x.sub ?? null
                }));

                // Hitung children map: berapa total per parent code (hanya dari child rows yang punya r.sub)
                const childrenMap = rows => rows.reduce((acc, r) => {
                    if (r.sub) {
                        acc[r.sub] = (acc[r.sub] || 0) + (Number(r.nilai) || 0);
                    }
                    return acc;
                }, {});
                const aChildrenMap = childrenMap(aRows);
                const pChildrenMap = childrenMap(pRows);

                // Untuk menentukan apakah ada parent rows yang memiliki child,
                // kita anggap ada parent bila ada key di childrenMap dengan nama parent.
                // (aIsParent akan dicek pada setiap row; hanya true bila row itu parent dan ada children)
                const maxRow = Math.max(aRows.length, pRows.length);
                const subABadge = Number(subA.total) < 0 ? 'text-danger' : '';
                const subPBadge = Number(subP.total) < 0 ? 'text-danger' : '';

                // Sub header (judul subkategori)
                html += `
            <tr class="font-weight-bold bg-dark text-white">
                <td colspan="2">${subA.judul}</td>
                <td class="text-right ${subABadge}">${subA.format}</td>
                <td></td>
                <td colspan="2">${subP.judul}</td>
                <td class="text-right ${subPBadge}">${subP.format}</td>
            </tr>
        `;

                // Jika tidak ada rows sama sekali, lanjut ke sub berikutnya
                if (maxRow === 0) {
                    continue;
                }

                // Rows: per baris, tampilkan left dan right
                for (let j = 0; j < maxRow; j++) {
                    const a = aRows[j] || {
                        kode: '',
                        nama: '',
                        nilai: '',
                        format: '',
                        sub: null
                    };
                    const p = pRows[j] || {
                        kode: '',
                        nama: '',
                        nilai: '',
                        format: '',
                        sub: null
                    };

                    // Parent detection: row adalah parent bila:
                    // - punya kode
                    // - bukan childnya orang lain (sub falsy)
                    // - ada entry di childrenMap untuk kode itu (ada child yang sub === kode)
                    const aIsParent = !!(a.kode && !a.sub && Object.prototype.hasOwnProperty.call(aChildrenMap, a
                    .kode));
                    const pIsParent = !!(p.kode && !p.sub && Object.prototype.hasOwnProperty.call(pChildrenMap, p
                    .kode));

                    const aBadge = Number(a.nilai) < 0 ? 'text-danger' : '';
                    const pBadge = Number(p.nilai) < 0 ? 'text-danger' : '';

                    // Toggle hanya dibuat bila benar-benar ada children (aIsParent / pIsParent).
                    // Tooltip diisi dengan total parent (berguna untuk parent yang collapse/expand).
                    const aToggle = aIsParent ? `
                <button type="button" class="toggle-child"
                        aria-expanded="true" data-side="left"
                        data-parent-code="${a.kode}"
                        data-toggle="tooltip" data-placement="left"
                        title="Total: ${a.format}">
                    <span class="chev">▾</span>
                </button>` : '';

                    const pToggle = pIsParent ? `
                <button type="button" class="toggle-child"
                        aria-expanded="true" data-side="right"
                        data-parent-code="${p.kode}"
                        data-toggle="tooltip" data-placement="left"
                        title="Total: ${p.format}">
                    <span class="chev">▾</span>
                </button>` : '';

                    // Untuk parent rows: sembunyikan angka parent pada kolom nilai (karena total ditampilkan di header/sub)
                    // Namun kalau datanya bukan parent (atau tidak ada children), tampilkan format.
                    const aValueHtml = aIsParent ? '' : (a.format ?? '');
                    const pValueHtml = pIsParent ? '' : (p.format ?? '');

                    html += `
                <tr class="kategori-data child-row"
                    data-parent-left="${a.sub ? a.sub : ''}"
                    data-parent-right="${p.sub ? p.sub : ''}">
                    <td class="text-center left-cell"><div class="cell-wrap">${a.sub ? '' : a.kode}</div></td>
                    <td class="left-cell"><div class="cell-wrap ${a.sub ? 'indent' : ''}">${a.sub ? `<span class="mr-4">${a.kode}</span>` : ''} ${a.nama}</div></td>
                    <td class="text-right ${aBadge} left-cell"><div class="cell-wrap">${aValueHtml} ${aToggle}</div></td>
                    <td></td>
                    <td class="text-center right-cell"><div class="cell-wrap">${p.sub ? '' : p.kode}</div></td>
                    <td class="right-cell"><div class="cell-wrap ${p.sub ? 'indent' : ''}">${p.sub ? p.kode : ''} ${p.nama}</div></td>
                    <td class="text-right ${pBadge} right-cell"><div class="cell-wrap">${pValueHtml} ${pToggle}</div></td>
                </tr>
            `;
                }
            }

            $('#listData').html(html);

            // Aktifkan tooltip (hanya ada untuk tombol yang benar-benar dibuat)
            $('[data-toggle="tooltip"]').tooltip({
                container: 'body'
            });

            // Event handler toggle-child: hanya ada pada tombol yang dibuat (parent yang punya child)
            $('#listData').off('click', '.toggle-child').on('click', '.toggle-child', function(e) {
                e.preventDefault();
                const $btn = $(this);
                const side = $btn.data('side');
                const parent = $btn.data('parentCode');
                const expanded = $btn.attr('aria-expanded') === 'true';

                if (side === 'left') {
                    // sembunyikan/ tampilkan cell-wrap pada kolom left untuk baris yang punya data-parent-left == parent
                    $(`#listData tr.child-row[data-parent-left="${parent}"] td.left-cell .cell-wrap`)
                        .css('display', expanded ? 'none' : 'block');
                } else {
                    $(`#listData tr.child-row[data-parent-right="${parent}"] td.right-cell .cell-wrap`)
                        .css('display', expanded ? 'none' : 'block');
                }

                $btn.attr('aria-expanded', expanded ? 'false' : 'true');
                $btn.find('.chev').text(expanded ? '▸' : '▾');
            });
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

                currentPage = 1;

                await defaultTime(monthText, year);
                await getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch,
                    customFilter);
            });

            document.getElementById('tb-reset').addEventListener('click', async function() {
                $('#bulan_tahun').val('').trigger('change');
                $('#custom-filter select').val(null).trigger('change');
                customFilter = {};
                defaultSearch = $('.tb-search').val();
                defaultLimitPage = $("#limitPage").val();
                currentPage = 1;

                await defaultTime(null, null);
                await getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch,
                    customFilter);
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
            await setInputFilter();
            await getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch, customFilter);
            await filterList();
        }
    </script>
@endsection
