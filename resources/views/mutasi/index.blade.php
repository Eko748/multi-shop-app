@extends('layouts.main')

@section('title')
    {{ $menu[0] }}
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('css/button-action.css') }}">
    <link rel="stylesheet" href="{{ asset('css/table.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sweetalert2.css') }}">
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
                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-12 col-md-6">
                                    <div class="row align-items-center">
                                        <div class="col-6 col-lg-4 col-xl-4 mb-2">
                                            <button class="btn btn-primary text-white add-data w-100" data-container="body"
                                                data-toggle="tooltip" data-placement="top" title="Tambah Mutasi Kas">
                                                <i class="fa fa-plus-circle"></i> Tambah
                                            </button>
                                        </div>
                                        <div class="col-6 col-lg-4 col-xl-4 mb-2">
                                            <button class="btn-dynamic btn btn-outline-primary w-100" type="button"
                                                data-toggle="collapse" data-target="#filter-collapse" aria-expanded="false"
                                                aria-controls="filter-collapse">
                                                <i class="fa fa-filter"></i> Filter
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="row justify-content-end">
                                        <div class="col-4 col-lg-2 col-xl-2">
                                            <select name="limitPage" id="limitPage" class="form-control mr-2 mb-2 mb-lg-0">
                                                <option value="10">10</option>
                                                <option value="20">20</option>
                                                <option value="30">30</option>
                                            </select>
                                        </div>
                                        <div class="col-8 col-lg-4 col-xl-4">
                                            <input id="tb-search" class="tb-search form-control mb-2 mb-lg-0" type="search"
                                                name="search" placeholder="Cari Data" aria-label="search">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="content">
                            <div class="collapse mt-2" id="filter-collapse">
                                <form id="custom-filter" class="card-body px-0 row g-2 align-items-center mx-2">
                                    <div class="col-12 col-md-6 col-lg-2 mb-2">
                                        <label for="daterange"><i class="fa fa-calendar-day mr-1"></i>Rentang
                                            Tanggal</label>
                                        <input class="form-control" type="text" id="daterange" name="daterange"
                                            placeholder="Pilih rentang tanggal">
                                    </div>

                                    @if (auth()->user()->toko_id == 1)
                                        <div class="col-12 col-md-6 col-lg-4 mb-2">
                                            <label for="toko"><i class="fa fa-shop mr-1"></i>Toko</label>
                                            <select class="form-control select2" id="toko" name="toko"></select>
                                        </div>
                                    @endif

                                    <div class="col-12 col-lg d-flex justify-content-end gap-2 mb-2" style="gap: 0.5rem;">
                                        <button form="custom-filter" class="btn btn-info" id="tb-filter" type="submit">
                                            <i class="fa fa-magnifying-glass mr-2"></i>Cari
                                        </button>
                                        <button type="button" class="btn btn-secondary" id="tb-reset">
                                            <i class="fa fa-rotate mr-2"></i>Reset
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <div class="card-body p-0">
                                <div class="table-responsive table-scroll-wrapper">
                                    <table class="table table-striped m-0">
                                        <thead>
                                            <tr class="tb-head">
                                                <th class="text-center text-wrap align-top" style="width: 5%">No</th>
                                                <th class="text-wrap align-top" style="width: 10%">Tanggal</th>
                                                <th class="text-wrap align-top" style="width: 20%">Kas Asal</th>
                                                <th class="text-wrap align-top" style="width: 20%">Kas Tujuan</th>
                                                <th class="text-wrap align-top" style="width: 20%">Keterangan</th>
                                                <th class="text-right text-wrap align-top" style="width: 10%">Nominal</th>
                                                <th class="text-center text-wrap align-top" style="width: 15%;">Action
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody id="listData">
                                        </tbody>
                                        <tfoot>
                                        </tfoot>
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
                    <h5 class="modal-title" id="modal-title">Tambah Data Mutasi</h5>
                    <button type="button" class="btn-close reset-all close" data-bs-dismiss="modal"
                        aria-label="Close"><i class="fa fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <form id="formTambahData">
                        <div class="row d-flex align-items-center">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="toko_pengirim">Kas Pengirim <sup class="text-danger">*</sup></label>
                                    <select name="toko_pengirim" id="kas_pengirim" class="form-control" required>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="toko_penerima">Kas Penerima <sup class="text-danger">*</sup></label>
                                    <select name="toko_penerima" id="kas_penerima" class="form-control" required>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row d-flex align-items-center">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="keterangan">Keterangan <sup class="text-danger">*</sup></label>
                                    <input type="text" class="form-control" id="keterangan" name="keterangan"
                                        placeholder="Masukkan keterangan" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="nominal">Nominal (Rp) <sup class="text-danger">*</sup></label>
                                    <input type="text" class="form-control rupiah" id="nominal" name="nominal"
                                        placeholder="Masukkan nominal" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="tanggal">Tanggal <sup class="text-danger">*</sup></label>
                                    <input type="datetime-local" class="form-control" id="tanggal" name="tanggal"
                                        placeholder="Masukkan tanggal" required>
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
@endsection

@section('asset_js')
    <script src="{{ asset('js/moment.js') }}"></script>
    <script src="{{ asset('js/daterange-picker.js') }}"></script>
    <script src="{{ asset('js/daterange-custom.js') }}"></script>
    <script src="{{ asset('js/pagination.js') }}"></script>
@endsection

@section('js')
    <script>
        let title = '{{ $menu[0] }}';
        let defaultLimitPage = 10;
        let currentPage = 1;
        let totalPage = 1;
        let defaultAscending = 0;
        let defaultSearch = '';
        let customFilter = {};
        let selectOptions = [{
            id: '#toko',
            isUrl: '{{ route('master.toko') }}',
            multiple: true
        }, {
            id: '#toko_penerima',
            isUrl: '{{ route('master.toko') }}',
            isModal: '#modal-form',
            isFilter: {
                is_delete: '{{ auth()->user()->toko_id }}',
            },
            placeholder: 'Pilih Nama Toko',
        }];

        function initSelectKas(selector, excludeSelector, mode) {
            $(selector).select2({
                ajax: {
                    url: "{{ route('total.kas-hirarki') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            search: params.term || '',
                            id_toko: "{{ auth()->user()->toko_id }}",
                            mode: mode,
                            kas_asal_id: $(excludeSelector).val() || null,
                        };
                    },
                    processResults: function(res) {
                        let data = res.data || [];

                        return {
                            results: data.map(item => ({
                                id: item.id,
                                text: item.text,
                                jenis_id: item.jenis_id,
                                tipe_kas: item.tipe_kas,
                                saldo_kas: item.saldo_kas
                            }))
                        };
                    }
                },
                placeholder: "Pilih Kas",
                dropdownParent: $("#modal-form"),
                allowClear: true,
                width: '100%'
            });
        }

        $(document).ready(function() {
            initSelectKas('#kas_pengirim', '#kas_penerima', 'pengirim');
            initSelectKas('#kas_penerima', '#kas_pengirim', 'penerima');

            $('#kas_pengirim').on('change', function() {
                let val = $(this).val();
                if ($('#kas_penerima').val() === val) {
                    $('#kas_penerima').val(null).trigger('change.select2');
                }
            });

            $('#kas_penerima').on('change', function() {
                let val = $(this).val();
                if ($('#kas_pengirim').val() === val) {
                    $('#kas_pengirim').val(null).trigger('change.select2');
                }
            });
        });

        async function getListData(limit = 10, page = 1, ascending = 0, search = '', customFilter = {}) {
            $('#listData').html(loadingData());

            let filterParams = {};

            if (customFilter['startDate'] && customFilter['endDate']) {
                filterParams.startDate = customFilter['startDate'];
                filterParams.endDate = customFilter['endDate'];
            }

            if (customFilter['toko']) {
                filterParams.toko = customFilter['toko'];
            }
            if (customFilter['kas_penerima']) {
                filterParams.kas_penerima = customFilter['kas_penerima'];
            }
            if (customFilter['kas_pengirim']) {
                filterParams.kas_pengirim = customFilter['kas_pengirim'];
            }

            let getDataRest = await renderAPI(
                'GET',
                '{{ route('jk.mutasi.get') }}', {
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

            if (getDataRest && getDataRest.status == 200 && Array.isArray(getDataRest.data.data.data)) {
                let data = getDataRest.data.data.data;

                let handleDataArray = await Promise.all(
                    data.map(async item => await handleData(item))
                );
                await setListData(handleDataArray, getDataRest.data.pagination, getDataRest.data.data.total_nilai);
            } else {
                let errorMessage = 'Tidak ada Data';
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
            let elementData = encodeURIComponent(JSON.stringify(data));
            let action_buttons = '';

            let delete_button = `
                <a class="p-1 btn delete-data action_button"
                    data-container="body" data-toggle="tooltip" data-placement="top"
                    title="Hapus ${title}" data="${elementData}">
                    <span class="text-dark">Hapus</span>
                    <div class="icon text-danger">
                        <i class="fa fa-trash"></i>
                    </div>
                </a>`;

            if (delete_button) {
                action_buttons = `
                <div class="d-flex justify-content-center">
                    ${delete_button ? `<div class="hovering p-1">${delete_button}</div>` : ''}
                </div>`;
            } else {
                action_buttons = `
                <div class="d-flex justify-content-center">
                    <span class="badge badge-secondary">Tidak Ada Aksi</span>
                </div>`;
            }

            const badge = (type, color, name) => `
                <span class="custom-badge badge badge-${color}">
                    <i class="fa fa-info-circle"></i> ${name ?? '-'}
                </span>
            `;

            let kasOUT = badge('out', data.attr_out, data.nama_toko_pengirim);
            let kasIN = badge('in', data.attr_in, data.nama_toko_penerima);

            return {
                id: data?.id ?? '-',
                tanggal: data?.tanggal ?? '-',
                id_toko_pengirim: data?.id_toko_pengirim ?? '-',
                nama_toko_pengirim: kasOUT,
                id_toko_penerima: data?.id_toko_penerima ?? '-',
                nama_toko_penerima: kasIN,
                nominal: data?.nominal ?? '-',
                keterangan: data?.keterangan ?? '-',
                action_buttons,
            };
        }

        async function setListData(dataList, pagination, total) {
            totalPage = pagination.total_pages;
            currentPage = pagination.current_page;
            let display_from = ((defaultLimitPage * (currentPage - 1)) + 1);
            let display_to = Math.min(display_from + dataList.length - 1, pagination.total);

            let getDataTable = '';
            let classCol = 'align-center text-dark text-nowrap';

            dataList.forEach((element, index) => {
                getDataTable += `
                <tr class="text-dark">
                    <td class="${classCol} text-center">${display_from + index}.</td>
                    <td class="${classCol}">${element.tanggal}</td>
                    <td class="${classCol}">${element.nama_toko_pengirim}</td>
                    <td class="${classCol}">${element.nama_toko_penerima}</td>
                    <td class="${classCol}">${element.keterangan}</td>
                    <td class="${classCol} text-right">${element.nominal}</td>
                    <td class="${classCol}">${element.action_buttons}</td>
                </tr>`;
            });

            let totalRow = `
            <tr class="bg-primary">
                <td class="${classCol}" colspan="4"></td>
                <td class="${classCol}" style="font-size: 1rem;"><strong class="text-white fw-bold">Total</strong></td>
                <td class="${classCol} text-right"><strong class="text-white" id="totalData">${total}</strong></td>
                <td class="${classCol}"></td>
            </tr>`;

            $('#listData').html(getDataTable);
            $('#listData').closest('table').find('tfoot').html(totalRow);

            $('#totalPage').text(pagination.total);
            $('#countPage').text(`${display_from} - ${display_to}`);
            $('[data-toggle="tooltip"]').tooltip();
            renderPagination();
        }

        async function addData() {
            $(document).on("click", ".add-data", async function() {
                $("#modal-title").html(`<i class="fa fa-circle-plus mr-1"></i>Form Tambah ${title}`);
                $("#modal-form").modal("show");
                $("form").find("input, select, textarea").val("").prop("checked", false).trigger("change");
                $("#formTambahData").data("action-url", '{{ route('jk.mutasi.post') }}');

                setDatePicker();
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

                let formData = {
                    kas_asal_id: $('#kas_pengirim').val(),
                    kas_tujuan_id: $('#kas_penerima').prop('disabled', false).val(),
                    tanggal: $('#tanggal').val(),
                    nominal: $('#nominal').val().replace(/\./g, ''),
                    keterangan: $('#keterangan').val(),
                    created_by: {{ auth()->user()->id }}
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
            $(document).on("click", ".delete-data", async function() {
                let rawData = $(this).attr("data");
                let data = JSON.parse(decodeURIComponent(rawData));

                swal({
                    title: `Hapus ${title}`,
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
                        '{{ route('jk.mutasi.delete') }}', {
                            id: data.id,
                            deleted_by: {{ auth()->user()->id }}
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
                            $('#listData').closest('table').find('tfoot').html('');
                        }, 500);
                        notificationAlert('success', 'Pemberitahuan', postDataRest.data
                            .message);
                    }
                }).catch(swal.noop);
            })
        }

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

                let customFilter = {
                    startDate: $("#daterange").val() !== '' ? startDate : '',
                    endDate: $("#daterange").val() !== '' ? endDate : ''
                };

                if ($('#toko').length) {
                    customFilter.toko = $('#toko').val() || '';
                }

                if ($('#kas_pengirim_f').length) {
                    customFilter.kas_pengirim = $('#kas_pengirim_f').val() || '';
                }

                if ($('#kas_penerima_f').length) {
                    customFilter.kas_penerima = $('#kas_penerima_f').val() || '';
                }

                defaultSearch = $('.tb-search').val();
                defaultLimitPage = $("#limitPage").val();
                currentPage = 1;

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
                await getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch,
                    customFilter);
            });
        }

        async function initPageLoad() {
            await Promise.all([
                setDynamicButton(),
                getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch, customFilter),
                selectData(selectOptions),
                searchList(),
                filterList(),
                addData(),
                submitForm(),
                deleteData(),
            ]);
        }
    </script>
@endsection
