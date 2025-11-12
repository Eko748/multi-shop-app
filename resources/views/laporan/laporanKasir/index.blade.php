@extends('layouts.main')

@section('title')
    Laporan Kasir
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('css/button-action.css') }}">
    <link rel="stylesheet" href="{{ asset('css/table.css') }}">
    <link rel="stylesheet" href="{{ asset('css/daterange-picker.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sweetalert2.css') }}">
    <style>
        #daterange[readonly] {
            background-color: white !important;
            cursor: pointer !important;
            color: inherit !important;
        }

        .glass {
            background: rgb(241, 241, 241);
            border-radius: 1rem;
            padding: 1rem;
            backdrop-filter: blur(8px);
            flex: 1 1 auto;
            word-wrap: break-word;
        }
    </style>
@endsection

@section('content')
    <div class="pcoded-main-container">
        <div class="pcoded-content pt-1 mt-1">
            @include('components.breadcrumbs')
            <div class="card">
                <div class="card-header">
                    <form id="custom-filter" class="d-flex flex-wrap p-0 m-0 align-items-center">
                        <div class="col-xl-2 col-lg-2 col-12">
                            <input class="form-control w-100" type="text" id="daterange" name="daterange"
                                placeholder="Pilih rentang tanggal">
                        </div>
                        <div class="col-xl-3 col-lg-3 col-12">
                            <div class="row">
                                <div class="col-6 px-3 px-lg-1">
                                    <button class="btn btn-info w-100" id="tb-filter" type="submit">
                                        <i class="fa fa-magnifying-glass mr-2"></i>Cari
                                    </button>
                                </div>
                                <div class="col-6 px-3 px-lg-1">
                                    <button type="button" class="btn btn-secondary w-100" id="tb-reset">
                                        <i class="fa fa-rotate mr-2"></i>Reset
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-body p-3">
                    <div class="font-weight-bold mb-3"><i class="fa fa-file-text mr-1"></i>Laporan kasir periode <span
                            id="time-report" class="font-weight-bold"></span></div>
                    <div id="laporan-content"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('asset_js')
    <script src="{{ asset('js/moment.js') }}"></script>
    <script src="{{ asset('js/daterange-picker.js') }}"></script>
    <script src="{{ asset('js/daterange-custom.js') }}"></script>
@endsection

@section('js')
    <script>
        let customFilter = {
            start_date: '',
            end_date: ''
        };

        async function getListData(filter = {
            start_date: '',
            end_date: ''
        }) {
            $('#laporan-content').html(`
                <div class="text-center p-4">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
            `);

            const response = await renderAPI('GET', '{{ route('rekapitulasi.laporan-penjualan') }}', {
                start_date: filter.start_date,
                end_date: filter.end_date,
                id_toko: '{{ auth()->user()->id_toko }}'
            }).then(res => res).catch(err => err.response);

            if (response && response.status === 200 && response.data?.data) {
                setListData(response.data.data);
            } else {
                $('#laporan-content').html('<div class="alert alert-danger">Gagal mengambil data laporan.</div>');
            }
        }

        function setListData(data) {
            let html = `
    <div class="table-responsive mb-4">
        <table class="table table-bordered w-100">
            <thead class="thead-light">
                <tr>
                    <th style="width: 30%">Area Toko</th>
                    <th style="width: 15%">Jml Trx</th>
                    <th style="width: 15%">Nilai Trx</th>
                </tr>
            </thead>
            <tbody>`;

            if (data.summary_per_toko?.length > 0) {
                data.summary_per_toko.forEach(toko => {
                    html += `
            <tr>
                <td>${toko.area_toko}</td>
                <td>${toko.jml_trx}</td>
                <td>${formatRupiah(toko.nilai_trx)}</td>
            </tr>`;
                });
            } else {
                html += `<tr><td colspan="5" class="text-center">Tidak ada data toko.</td></tr>`;
            }

            html += `
            </tbody>
            <tfoot class="thead-light">
                <tr>
                    <th>TOTAL</th>
                    <th>${data.total.jml_trx}</th>
                    <th>${formatRupiah(data.total.nilai_trx)}</th>
                </tr>
            </tfoot>
        </table>
    </div>`;

            // TYPE ITEM (JIKA ADA)
            if (data.type_barang?.length > 0) {
                html += `
        <div class="table-responsive mb-4">
            <table class="table table-bordered w-100">
                <thead class="thead-light">
                    <tr>
                        <th style="width: 30%">Type Item</th>
                        <th style="width: 8%">Jml Trx</th>
                        <th style="width: 7%">Jml Qty</th>
                        <th style="width: 15%">Nilai Trx</th>
                    </tr>
                </thead>
                <tbody>`;

                data.type_barang.forEach(barang => {
                    html += `
            <tr>
                <td>${barang.nama}</td>
                <td>${barang.jml_trx}</td>
                <td>${barang.item_qty}</td>
                <td>${formatRupiah(barang.nilai_trx)}</td>
            </tr>`;
                });

                html += `
                </tbody>
            </table>
        </div>`;
            }

            // SET PERIODE LAPORAN
            if (data.laporan_penjualan_periode?.trim() === 's/d' || data.laporan_penjualan_periode?.trim() === ' s/d ') {
                setTimeReport();
            } else {
                $('#time-report').html(data.laporan_penjualan_periode);
            }

            $('#laporan-content').html(html);
        }


        async function filterList() {
            let dateRangePickerList = initializeDateRangePicker();

            document.getElementById('custom-filter').addEventListener('submit', async function(e) {
                e.preventDefault();
                let startDate = dateRangePickerList.data('daterangepicker').startDate;
                let endDate = dateRangePickerList.data('daterangepicker').endDate;

                if (!startDate || !endDate) {
                    customFilter.start_date = '';
                    customFilter.end_date = '';
                } else {
                    customFilter.start_date = startDate.startOf('day').format('YYYY-MM-DD HH:mm:ss');
                    customFilter.end_date = endDate.endOf('day').format('YYYY-MM-DD HH:mm:ss');
                }

                await getListData(customFilter);
            });

            document.getElementById('tb-reset').addEventListener('click', async function() {
                $('#daterange').val('');
                customFilter.start_date = '';
                customFilter.end_date = '';
                await getListData(customFilter);
            });
        }

        function setTimeReport() {
            const now = new Date();
            const formattedNow =
                `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')} ${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}:${String(now.getSeconds()).padStart(2, '0')}`;

            $('#time-report').html(formattedNow);
        }

        async function initPageLoad() {
            await Promise.all([
                getListData(),
                filterList()
            ]);
        }
    </script>
@endsection
