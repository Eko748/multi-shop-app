@extends('layouts.main')

@section('title')
    {{ $menu[0] }}
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('css/button-action.css') }}">
    <link rel="stylesheet" href="{{ asset('css/table.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sweetalert2.css') }}">
    <style>
        .glossy-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.8), rgba(240, 240, 255, 0.9));
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .glossy-card:hover {
            transform: scale(1.02);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        #listData {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        @media (min-width: 768px) {
            #btn-add-data {
                width: auto !important;
            }
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
                                    <h5 class="m-0">List Penyesuaian</h5>
                                    <div class="d-flex align-items-center" style="gap: 0.5rem;">
                                        <button
                                            class="btn-dynamic btn btn-md btn-outline-secondary d-flex align-items-center justify-content-center"
                                            type="button" data-toggle="collapse" data-target="#filter-collapse"
                                            aria-expanded="false" aria-controls="filter-collapse" data-container="body"
                                            data-toggle="tooltip" data-placement="top"
                                            style="flex: 0 0 45px; max-width: 45px;" title="Filter Data">
                                            <i class="fa fa-filter my-1"></i>
                                        </button>
                                        <button type="button"
                                            class="btn btn-md btn-outline-primary d-flex align-items-center justify-content-center"
                                            id="btn-add-data" onclick="openAddModal()" data-container="body"
                                            data-toggle="tooltip" data-placement="top"
                                            style="flex: 1 1 45px; max-width: 150px;"
                                            title="Tambah Data {{ $menu[0] }}">
                                            <i class="fa fa-circle-plus my-1"></i>
                                            <span class="d-none d-sm-inline ml-1">Tambah Data</span>
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
                            <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
                                <div class="row overflow-auto" id="listData" style="max-height: 50vh;"></div>
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

    <div class="modal fade" id="modal-form" tabindex="-1" role="dialog" aria-labelledby="modalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalLabel">Form Penyesuaian</h5>
                    <button type="button" class="btn-close reset-all close" data-bs-dismiss="modal"
                        aria-label="Close"><i class="fa fa-xmark"></i></button>
                </div>
                <div class="modal-body card-body">
                    <form id="form-neraca">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close"><i
                            class="fa fa-times mr-1"></i>Tutup</button>
                    <button type="submit" class="btn btn-success" id="save-btn" form="#form-neraca">Simpan</button>
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
        let title = '{{ $menu[0] }}';
        let defaultLimitPage = 12;
        let currentPage = 1;
        let totalPage = 1;
        let defaultAscending = 0;
        let defaultSearch = '';
        let customFilter = {};

        async function getListData(limit = 30, page = 1, ascending = 0, search = '', customFilter = {}) {
            $('#listData').html(`
                <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
                    <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
                        <div class="d-flex justify-content-center align-items-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            `);

            let filterParams = {
                ...customFilter
            };
            let getDataRest = await renderAPI(
                'GET',
                '{{ route('neraca.get') }}', {
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
                if (getDataRest.data.data.length == 0) {
                    $('#listData').html(`
                    <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
                        <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
                            <div class="text-center my-3" role="alert">
                                Tidak ada Penyesuaian.
                            </div>
                        </div>
                    </div>
                    `);
                }
            } else {
                $('#listData').html(`
                    <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
                        <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
                            <div class="text-center my-3" role="alert">
                                Data tidak tersedia untuk ditampilkan.
                            </div>
                        </div>
                    </div>
                    `);
                $('#countPage').text("0 - 0");
                $('#totalPage').text("0");
                $('#totalData').text(getDataRest?.data?.total ?? 0);
            }
        }

        async function handleData(data) {
            const tanggalFormatted = data?.tanggal ?? '-';

            const edit_button = `
                <button onClick="openEditModal('${encodeURIComponent(JSON.stringify(data))}')" class="btn btn-outline-primary btn-sm" title="Edit data ${tanggalFormatted}" data-id="${data?.id}">
                    <i class="fas fa-edit"></i>
                </button>
            `;

            const delete_button = `
                <button onClick="deleteData('${encodeURIComponent(JSON.stringify(data))}')" class="btn btn-outline-danger btn-sm" title="Hapus data ${tanggalFormatted}" data-id="${data?.id}">
                    <i class="fas fa-trash-alt"></i>
                </button>
            `;

            return {
                id: data?.id ?? '-',
                tanggal: tanggalFormatted,
                nominal: data?.nominal ?? '-',
                pesan: data?.pesan ?? '-',
                creator_name: data?.creator_name ?? '-',
                created_at: data?.created_at ?? '-',
                edit_button,
                delete_button,
            };
        }

        async function setListData(dataList, pagination, total) {
            totalPage = pagination.total_pages;
            currentPage = pagination.current_page;
            let display_from = ((defaultLimitPage * (currentPage - 1)) + 1);
            let display_to = Math.min(display_from + dataList.length - 1, pagination.total);
            let tdClass = 'text-wrap align-top';
            let getDataTable = `
            <div class="col-12">
                <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover m-0">
                                <thead class="glossy-thead">
                                    <tr>
                                        <th scope="col" class="${tdClass} text-center" style="width:5%">No</th>
                                        <th scope="col" class="${tdClass}" style="width:15%">Tanggal</th>
                                        <th scope="col" class="${tdClass} text-right" style="width:15%">Nominal</th>
                                        <th scope="col" class="${tdClass}" style="width:15%">Pesan</th>
                                        <th scope="col" class="${tdClass}" style="width:10%">Dibuat Oleh</th>
                                        <th scope="col" class="${tdClass} text-center" style="width:25%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>`;

            dataList.forEach((element, index) => {
                const number = display_from + index;
                const hasButtons = element.edit_button || element.delete_button;
                const actionHTML = `
                    <div class="d-flex justify-content-center flex-column flex-sm-row align-items-center align-items-sm-start mx-3" style="gap: 0.5rem;">
                        ${hasButtons
                            ? `
                                                    ${element.edit_button || ''}
                                                    ${element.delete_button || ''}
                                    `
                            : `<i class="text-muted">Tidak ada aksi</span>`
                        }
                    </div>
                `;

                getDataTable += `
                    <tr class="glossy-tr">
                        <td class="${tdClass} text-center">${number}</td>
                        <td class="${tdClass}">${element.tanggal}</td>
                        <td class="${tdClass} text-right">${element.nominal}</td>
                        <td class="${tdClass}">${element.pesan}</td>
                        <td class="${tdClass}">${element.creator_name}</td>
                        <td class="${tdClass}">${actionHTML}</td>
                    </tr>
                `;
            });

            getDataTable += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>`;

            $('#listData').html(getDataTable);
            $('#totalPage').text(pagination.total);
            $('#countPage').text(`${display_from} - ${display_to}`);
            $('[data-toggle="tooltip"]').tooltip();
            renderPagination();
        }

        async function saveData() {
            $(document).on("click", "#save-btn", async function(e) {
                e.preventDefault();

                const btn = $(this);
                const saveButton = this;
                const form = btn.closest("form")[0];
                const formData = new FormData(form);

                const userId = {{ auth()->user()->id }};
                const tokoId = {{ auth()->user()->toko_id }};
                formData.append('user_id', userId);
                formData.append('toko_id', tokoId);

                if (saveButton.disabled) return;

                swal({
                    title: "Konfirmasi",
                    text: "Apakah Anda yakin ingin menyimpan data penyesuaian ini?",
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
                    const originalContent = btn.html();
                    btn.data('original-content', originalContent);
                    btn.html(
                        `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan`
                    );

                    loadingPage(true);

                    const isEdit = formData.get('id') !== null && formData.get('id') !== '';
                    const url = isEdit ?
                        `{{ route('neraca.put') }}?id=${formData.get('id')}` :
                        `{{ route('neraca.post') }}`;
                    const method = 'POST';

                    if (isEdit) {
                        formData.append('_method', 'PUT');
                    }

                    try {
                        const response = await renderAPI(method, url, formData);
                        loadingPage(false);

                        if (response.status >= 200 && response.status < 300) {
                            notificationAlert('success', 'Pemberitahuan', response.data
                                .message || 'Data berhasil disimpan.');
                            isDataSaved = true;

                            setTimeout(async function() {
                                await getListData(defaultLimitPage, currentPage,
                                    defaultAscending,
                                    defaultSearch, customFilter);
                            }, 500);

                            setTimeout(() => {
                                $('#modal-form').modal('hide');
                            }, 500);

                        } else {
                            notificationAlert('info', 'Pemberitahuan', response.data.message ||
                                'Terjadi kesalahan saat menyimpan.');
                            saveButton.disabled = false;
                            btn.html(originalContent);
                        }
                    } catch (error) {
                        loadingPage(false);
                        notificationAlert('error', 'Kesalahan', error?.response?.data
                            ?.message || 'Terjadi kesalahan saat menyimpan data.');
                        saveButton.disabled = false;
                        btn.html(originalContent);
                    }
                });
            });
        }

        async function deleteData(rawData) {
            let data = JSON.parse(decodeURIComponent(rawData));

            swal({
                title: `Hapus Data ${data?.read_tanggal}`,
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
                    `{{ route('neraca.delete') }}?id=${data.id}`, {}
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
                console.error("Gagal memuat data untuk edit:", e);
                alert("Terjadi kesalahan saat memuat data untuk diedit.");
            }
        }

        function renderModalForm(mode = 'add', data = {}) {
            const title = mode === 'edit' ?
                '<i class="fa fa-edit mr-1"></i>Edit Data Penyesuaian Neraca' :
                '<i class="fa fa-circle-plus mr-1"></i>Tambah Data Penyesuaian Neraca';

            $('#modalLabel').html(title);

            const saldoValue = typeof data.nilai !== 'undefined' ? data.nilai : 0;

            const getDateTimeLocalValue = (inputDate) => {
                const date = inputDate ? new Date(inputDate) : new Date();
                const pad = (n) => n.toString().padStart(2, '0');
                const year = date.getFullYear();
                const month = pad(date.getMonth() + 1);
                const day = pad(date.getDate());
                const hours = pad(date.getHours());
                const minutes = pad(date.getMinutes());
                return `${year}-${month}-${day}T${hours}:${minutes}`;
            };

            const dateValue = getDateTimeLocalValue(data.tanggal);
            const dateFinalValue = data.tanggal ? dateValue : getDateTimeLocalValue();

            const formContent = `
                <div class="form-group">
                    <label for="nominal">Nominal</label>
                    <input type="number" class="form-control" id="nominal" name="nominal" step="0.000001" value="${saldoValue}" required>
                    <small
                                class="text-muted font-italic d-block text-left"
                                style="font-size: 11px;"
                            >
                                Gunakan tanda titik (.) sebagai pemisah desimal.
                            </small>
                            <small
                                class="text-muted font-italic d-block text-left"
                                style="font-size: 11px;"
                            >
                                Maksimal nilai desimal: 6 angka dibelakang koma. Contoh: 12500.333333
                            </small>
                            <small
                                class="text-muted font-italic d-block text-left"
                                style="font-size: 11px;"
                            >
                                Bisa isi dengan angka negatif dengan tambahkan tanda minus (-). Contoh: -12500.333333
                            </small>
                </div>
                <div class="form-group">
                    <label for="pesan">Pesan</label>
                    <textarea class="form-control" id="pesan" name="pesan"  value="${saldoValue}" placeholder="Masukkan pesan" required></textarea>
                </div>
                <div class="form-group">
                    <label for="tanggal">Tanggal</label>
                    <input type="datetime-local" class="form-control" id="tanggal" name="tanggal" value="${dateFinalValue}" required>
                </div>
                ${mode === 'edit' ? `<input type="hidden" name="id" value="${data.id}">` : ''}
            `;

            $('#form-neraca').html(formContent);
        }

        function selectYear() {
            const currentYear = new Date().getFullYear();
            const startYear = currentYear - 10;
            const endYear = currentYear + 1;
            const yearSelect = document.getElementById('filter_tahun');

            for (let year = endYear; year >= startYear; year--) {
                const option = document.createElement('option');
                option.value = year;
                option.textContent = year;
                yearSelect.appendChild(option);
            }
        }

        async function filterList() {
            document.getElementById('custom-filter').addEventListener('submit', async function(e) {
                e.preventDefault();

                customFilter = {
                    month: $("#filter_bulan").val() || '',
                    year: $("#filter_tahun").val() || '',
                };

                currentPage = 1;

                await getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch,
                    customFilter);
            });

            document.getElementById('tb-reset').addEventListener('click', async function() {
                $('#custom-filter select').val(null).trigger('change');
                customFilter = {};
                currentPage = 1;
                await getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch,
                    customFilter);
            });
        }

        async function initPageLoad() {
            await Promise.all([
                getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch, customFilter),
                filterList(),
                searchList(),
                saveData(),
            ])
        }
    </script>
@endsection
