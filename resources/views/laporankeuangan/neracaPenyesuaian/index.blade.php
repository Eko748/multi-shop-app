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
                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xl-6 mb-2">
                                    <button type="button" class="btn btn-primary w-100" id="btn-add-data"
                                        onclick="openAddModal()">
                                        <i class="fa fa-circle-plus"></i><span> Tambah Data</span>
                                    </button>
                                </div>
                                <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xl-6">
                                    <form id="custom-filter" class="row justify-content-end">
                                        <div class="col-6 col-sm-6 col-md-4 col-xl-4 col-lg-5 mb-2">
                                            <select id="filter_bulan" class="form-control">
                                                <option value="">Pilih Bulan</option>
                                                <option value="01">Januari</option>
                                                <option value="02">Februari</option>
                                                <option value="03">Maret</option>
                                                <option value="04">April</option>
                                                <option value="05">Mei</option>
                                                <option value="06">Juni</option>
                                                <option value="07">Juli</option>
                                                <option value="08">Agustus</option>
                                                <option value="09">September</option>
                                                <option value="10">Oktober</option>
                                                <option value="11">November</option>
                                                <option value="12">Desember</option>
                                            </select>
                                        </div>

                                        <div class="col-6 col-sm-6 col-md-4 col-xl-4 col-lg-5 mb-2">
                                            <select id="filter_tahun" class="form-control">
                                                <option value="">Pilih Tahun</option>
                                            </select>
                                        </div>

                                        <div class="col-12 col-sm-12 col-md-4 col-xl-3 col-lg-2 mb-2 d-flex justify-content-end"
                                            style="gap: 0.5rem;">
                                            <button form="custom-filter" class="btn btn-info btn-md" id="tb-filter"
                                                type="submit">
                                                <i class="fa fa-magnifying-glass"></i>
                                            </button>
                                            <button type="button" class="btn btn-secondary btn-md" id="tb-reset">
                                                <i class="fa fa-rotate"></i>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="content">
                            <div class="card-body p-0">
                                <div class="row px-3 my-1" id="listData">
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

    <div class="modal fade" id="modal-form" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <form id="form-neraca">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalLabel">Form Neraca</h5>
                        <button type="button" class="btn-close reset-all close" data-bs-dismiss="modal"
                            aria-label="Close"><i class="fa fa-xmark"></i></button>
                    </div>
                    <div class="modal-body card-body">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close"><i
                                class="fa fa-times mr-1"></i>Tutup</button>
                        <button type="submit" class="btn btn-success" id="save-btn">Simpan</button>
                    </div>
                </div>
            </form>
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

        async function getListData(limit = 12, page = 1, ascending = 0, search = '', customFilter = {}) {
            $('#listData').html(`
                <div class="d-flex justify-content-center align-items-center py-5">
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
                '{{ route('neraca.get') }}', {
                    page: page,
                    limit: limit,
                    ascending: ascending,
                    search: search,
                    ...filterParams
                }
            ).then(function(response) {
                return response;
            }).catch(function(error) {
                return error.response;
            });

            if (getDataRest && getDataRest.status === 200) {
                const dataArray = getDataRest.data.data || [];

                if (Array.isArray(dataArray) && dataArray.length > 0) {
                    let handleDataArray = await Promise.all(
                        dataArray.map(item => handleData(item))
                    );
                    await setListData(handleDataArray, getDataRest.data.pagination);
                } else {
                    $('#listData').html(`
                        <div class="alert alert-info text-center mt-4" role="alert">
                            Data tidak tersedia untuk ditampilkan.
                        </div>
                    `);
                    $('#countPage').text("0 - 0");
                    $('#totalPage').text("0");
                }
            } else {
                let errorMessage = getDataRest?.data?.message || 'Data gagal dimuat';

                $('#listData').html(`
                    <div class="alert alert-warning text-center mt-4" role="alert">
                        ${errorMessage}
                    </div>
                `);
                $('#countPage').text("0 - 0");
                $('#totalPage').text("0");
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
                nilai: data?.nilai ?? '-',
                creator_name: data?.creator_name ?? '-',
                created_at: data?.created_at ?? '-',
                edit_button,
                delete_button,
            };
        }

        async function setListData(dataList, pagination) {
            totalPage = pagination.total_pages;
            currentPage = pagination.current_page;
            let display_from = ((defaultLimitPage * (currentPage - 1)) + 1);
            let display_to = Math.min(display_from + dataList.length - 1, pagination.total);

            let getDataCard = '';
            dataList.forEach((element, index) => {
                const date = new Date(element.tanggal);
                const monthNames = [
                    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
                ];
                const monthYear = `${monthNames[date.getMonth()]} ${date.getFullYear()}`;

                getDataCard += `
                    <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-3">
                        <div class="card shadow-sm border-0 m-0 mt-3 mx-2 rounded glossy-card bg-light h-100">
                            <div class="card-body d-flex flex-column justify-content-between">
                                <div>
                                    <h6 class="text-uppercase text-secondary fw-semibold mb-1">
                                        <i class="fa fa-wallet me-1 text-primary"></i> Saldo Bulan ${monthYear}
                                    </h6>
                                    <p style="color: #212529; font-weight: bold; font-size: 2.25rem;">${formatRupiah(element.nilai)}</p>
                                </div>
                                <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center">
                                    <div class="d-flex align-items-center mb-2 mb-md-0">
                                        <i class="fa fa-user text-muted mr-2" style="font-size: 1.50rem;"></i>
                                        <div>
                                            <small class="d-block text-muted">Dibuat oleh:</small>
                                            <small class="d-block text-bold">${element.creator_name || '-'}, ${element.created_at}</small>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-end" style="gap: 0.5rem;">
                                        ${element.edit_button}
                                        ${element.delete_button}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>`;
            });

            $('#listData').html(getDataCard);
            $('#totalPage').text(pagination.total);
            $('#countPage').text(`${display_from} - ${display_to}`);
            renderPagination();
        }

        async function saveData() {
            $(document).on("click", "#save-btn", async function(e) {
                e.preventDefault();

                const btn = $(this);
                const saveButton = this;
                const form = btn.closest("form")[0];
                const formData = new FormData(form);

                const userId = '{{ auth()->user()->id }}';
                formData.append('user_id', userId);

                if (saveButton.disabled) return;

                swal({
                    title: "Konfirmasi",
                    text: "Apakah Anda yakin ingin menyimpan data neraca ini?",
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
                '<i class="fa fa-edit mr-1"></i>Edit Data Neraca' :
                '<i class="fa fa-circle-plus mr-1"></i>Tambah Data Neraca';

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
                    <label for="nilai">Nilai</label>
                    <input type="number" class="form-control" id="nilai" name="nilai" step="0.01" value="${saldoValue}" required>
                </div>
                <div class="form-group">
                    <label for="tanggal">Tanggal</label>
                    <input type="datetime-local" class="form-control" id="tanggal" name="tanggal" value="${dateFinalValue}" required>
                </div>
                ${mode === 'edit' ? `<input type="hidden" name="id" value="${data.id}">` : ''}
            `;

            $('#modal-form .modal-body').html(formContent);
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
                selectYear(),
                getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch, customFilter),
                filterList(),
                searchList(),
                saveData(),
            ])
        }
    </script>
@endsection
