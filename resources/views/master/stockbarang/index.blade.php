@extends('layouts.main')

@section('title')
    Stok Barang
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('css/button-action.css') }}">
    <link rel="stylesheet" href="{{ asset('css/table.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sweetalert2.css') }}">
    <link rel="stylesheet" href="{{ asset('css/notyf.min.css') }}">
    <style>
        .scroll-section {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 0.5rem;
            background-color: #f8f9fa;
        }

        .scroll-section table {
            margin-bottom: 0;
        }

        .section-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .divider-col {
            border-right: 1px solid #dee2e6;
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
                                <div class="col-sm-12 col-md-3 col-lg-3 col-xl-2 mb-2">
                                    @if (hasPermission('GET /pembelianbarang'))
                                        <a href="{{ route('transaksi.pembelianbarang.index') }}"
                                            class="mr-2 btn btn-primary w-100" data-container="body" data-toggle="tooltip"
                                            data-placement="top" title="Tambah Data Stok Barang">
                                            <i class="fa fa-circle-plus"></i> Tambah
                                        </a>
                                    @endif
                                </div>
                                <div class="col-sm-12 col-md-9 col-lg-9 col-xl-10 mb-2">
                                    <div class="row justify-content-end">
                                        <div class="col-4 col-sm-4 col-md-2 col-lg-2">
                                            <select name="limitPage" id="limitPage" class="form-control mr-2 mb-2 mb-lg-0">
                                                <option value="10">10</option>
                                                <option value="20">20</option>
                                                <option value="30">30</option>
                                            </select>
                                        </div>
                                        <div class="col-8 col-sm-8 col-md-4 col-lg-4 justify-content-end">
                                            <input id="tb-search" class="tb-search form-control mb-2 mb-lg-0" type="search"
                                                name="search" placeholder="Cari Data" aria-label="search">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="content">
                            <x-adminlte-alerts />
                            <div class="card-body p-0">
                                <div class="table-responsive table-scroll-wrapper">
                                    <table class="table table-striped m-0">
                                        <thead>
                                            <tr class="tb-head">
                                                <th class="text-center text-wrap align-top">No</th>
                                                <th class="text-wrap align-top">Barcode</th>
                                                <th class="text-wrap align-top">Nama Barang</th>
                                                <th class="text-wrap align-top">
                                                    Stok
                                                    <button class="btn btn-link p-0" id="sortAscStock">▲</button>
                                                    <button class="btn btn-link p-0" id="sortDescStock">▼</button>
                                                </th>
                                                @if (Auth::user()->id_level == 1)
                                                    <th class="text-wrap align-top">Hpp Baru</th>
                                                @endif
                                                <th class="text-wrap align-top">Level Harga</th>
                                                <th class="text-center text-wrap align-top">Action</th>
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
    </div>

    <div class="modal fade" id="modal-form" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalLabel"><i class="fa fa-edit mr-1"></i>Form Pengurangan Stok Barang</h5>
                    <button type="button" class="btn-close reset-all close" data-bs-dismiss="modal" aria-label="Close"><i
                            class="fa fa-xmark"></i></button>
                </div>
                <div class="modal-body card-body">
                    <form id="form-data">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <div class="font-weight-bold">Barang: <span id="label_barang"></span></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="stock">Stok<span class="text-danger ml-1">*</span></label>
                                    <input type="number" class="form-control" id="stock" name="stock"
                                        placeholder="Masukkan stok barang" required>
                                    <small class="font-weight-bold">Maks: <span id="maks-stock"
                                            class="text-danger"></span></small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status">Status<span class="text-danger ml-1">*</span></label>
                                    <select name="status" id="status" class="form-control">
                                        <option value="hilang">Barang Hilang</option>
                                        <option value="mati">Barang Mati</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="input-pesan">Pesan</label>
                                    <textarea id="input-pesan" class="form-control" placeholder="Masukkan Pesan (Opsional)" rows="4"></textarea>
                                </div>
                            </div>
                        </div>
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
@endsection

@section('asset_js')
    <script src="{{ asset('js/pagination.js') }}"></script>
    <script src="{{ asset('js/notyf.min.js') }}"></script>
@endsection

@section('js')
    <script>
        let title = 'Stok Barang';
        let defaultLimitPage = 10;
        let currentPage = 1;
        let totalPage = 1;
        let defaultAscending = 0;
        let defaultSearch = '';
        let customFilter = {};
        let notyf = new Notyf({
            duration: 3000,
            position: {
                x: 'center',
                y: 'top',
            }
        });

        async function getListData(limit = 10, page = 1, ascending = 0, search = '', customFilter = {}) {
            $('#listData').html(loadingData());

            let filterParams = {};

            let getDataRest = await renderAPI(
                'GET',
                '{{ route('master.getstockbarang') }}', {
                    page: page,
                    limit: limit,
                    ascending: ascending,
                    search: search,
                    id_toko: '{{ auth()->user()->id_toko }}',
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
            let levelHargaText = '';
            if (data.level_harga && typeof data.level_harga === 'object') {
                levelHargaText = Object.entries(data.level_harga).map(([key, value]) => {
                    return `${key} (${value})`;
                }).join(', ');
            }

            let level_harga_html = `
            <div class="mb-1 text-dark font-weight-bold small">
                ${levelHargaText}
            </div>`;

            let detail_button = `
            <button id="detail-${data.id}" class="p-1 btn detail-data action_button atur-harga-btn"
                data-id='${data.id}' data-id-barang='${data.id_barang}' data-container="body" data-toggle="tooltip" data-placement="top"
                title="Detail ${title}: ${data.nama_barang}"
                onclick="showModal(${data.id_barang}, '${btoa(encodeURIComponent(data.nama_barang))}', '${data.hpp_baru ?? 0}')">
                <span class="text-dark">Detail</span>
                <div class="icon text-info">
                    <i class="fa fa-book"></i>
                </div>
            </button>`;

            let edit_button = `
            <button id="detail-${data.id}" class="p-1 btn detail-data action_button atur-harga-btn"
                data-container="body" data-toggle="tooltip" data-placement="top"
                title="Kosongkan ${title}: ${data.nama_barang}"
                onclick="editData('${encodeURIComponent(JSON.stringify(data))}')">
                <span class="text-dark">Kosongkan</span>
                <div class="icon text-danger">
                    <i class="fa fa-rotate"></i>
                </div>
            </button>`;

            let stock_button = `
            <a class="p-1 btn edit-data action_button" onClick="openEditModal('${encodeURIComponent(JSON.stringify(data))}')">
                <span class="text-dark" title="Edit ${title}: ${data.nama_barang}">Edit</span>
                <div class="icon text-warning" title="Edit ${title}: ${data.nama_barang}">
                    <i class="fa fa-edit"></i>
                </div>
            </a>`;

            return {
                id: data?.id ?? '-',
                barcode: data?.barcode ?? '-',
                nama_barang: data?.nama_barang ?? '-',
                stock: data?.stock ?? '-',
                hpp_baru: formatRupiah(data?.hpp_baru),
                level_harga: level_harga_html,
                detail_button,
                edit_button,
                stock_button
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
                        <td class="${classCol}">${element.barcode}</td>
                        <td class="${classCol}">${element.nama_barang}</td>
                        <td class="${classCol}">${element.stock}</td>
                        @if (Auth::user()->id_level == 1)
                        <td class="${classCol}">${element.hpp_baru}</td>
                        @endif
                        <td class="${classCol}">${element.level_harga}</td>
                        <td class="${classCol}">
                            <div class="d-flex justify-content-center w-100">
                                <div class="hovering p-1">
                                    ${element.detail_button}
                                </div>
                                <div class="hovering p-1">
                                    ${element.stock_button}
                                </div>
                                <div class="hovering p-1">
                                    ${element.edit_button}
                                </div>
                            </div>
                        </td>
                    </tr>`;
            });

            $('#listData').html(getDataTable);
            $('#totalPage').text(pagination.total);
            $('#countPage').text(`${display_from} - ${display_to}`);
            $('[data-toggle="tooltip"]').tooltip();
            renderPagination();
        }

        function openEditModal(encodedData) {
            try {
                let item = JSON.parse(decodeURIComponent(encodedData));
                let currentStock = item.stock;

                $('#label_barang').text(item.nama_barang);
                $('#maks-stock').text(currentStock);
                $('#input-pesan').val('');
                $('#stock')
                    .val(currentStock)
                    .attr('max', currentStock)
                    .attr('min', 0);

                $('#stock').on('input', function() {
                    const max = parseInt($(this).attr('max'), 10);
                    const value = parseInt($(this).val(), 10);
                    if (value > max) {
                        $(this).val(max);
                        notificationAlert('warning', 'Peringatan', 'Stok tidak boleh melebihi stok saat ini.');
                    }
                });

                saveData(encodedData);

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

        async function saveData(encodedData) {
            $(document).on("click", "#save-btn", async function(e) {
                e.preventDefault();

                let data = JSON.parse(decodeURIComponent(encodedData));

                const btn = $(this);
                const saveButton = this;

                const userId = '{{ auth()->user()->id }}';
                const status = $('#status').val();
                const qty = $('#stock').val();
                const message = $('#input-pesan').val();

                const payload = {
                    id: data.id,
                    user_id: userId,
                    status: status,
                    qty: qty,
                    message: message
                };

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

                    try {
                        const response = await renderAPI('PUT',
                            '{{ route('master.stockbarang.edit-stok') }}',
                            payload);
                        loadingPage(false);

                        if (response.status >= 200 && response.status < 300) {
                            notificationAlert('success', 'Pemberitahuan', response.data
                                .message || 'Data berhasil disimpan.');
                            isDataSaved = true;

                            setTimeout(async function() {
                                await getListData(defaultLimitPage, currentPage,
                                    defaultAscending, defaultSearch,
                                    customFilter);
                            }, 500);

                            setTimeout(() => {
                                $('#modal-form').modal('hide');
                            }, 500);
                        } else {
                            notificationAlert('info', 'Pemberitahuan', response.data.message ||
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

        async function showModal(id_barang, encoded_nama_barang, hpp_baru) {
            const nama_barang = decodeURIComponent(atob(encoded_nama_barang));
            const modalId = '#dynamicModal';
            const modalTitle = `${nama_barang} : ${formatRupiah(hpp_baru)}`;

            const canAturHarga = hasPermission('POST /update-level-harga');
            const canDetailBarang = hasPermission('GET /get-detail-barang/{id_barang}');

            if (!document.querySelector(modalId)) {
                createModalSkeleton(canAturHarga, canDetailBarang);
            }

            $('.modal-barang-title').text(modalTitle);

            const resp = await renderAPI('GET', `/admin/get-stock-details/${id_barang}`, {}).then(r => r).catch(e => e
                .response);

            if (resp.status !== 200) {
                notyf.error(resp.data?.message || 'Gagal memuat data stok barang.');
                return;
            }

            const stokData = resp.data;

            await renderTabStokBarang(stokData);

            if (canAturHarga) {
                await renderTabAturHarga(id_barang, stokData);
            }

            if (canDetailBarang) {
                await renderTabDetailBarang(id_barang);
            }

            $(modalId).modal('show');
        }

        async function editData(encodedData) {
            let data = JSON.parse(decodeURIComponent(encodedData));

            swal({
                title: `Kosongkan ${title}`,
                html: `
                    <p class="font-weight-bold">Stok ${data.nama_barang} akan dikosongkan!</p>
                    <hr>
                    <div class="px-4" style="gap: 0.5rem;">
                        <div class="form-group">
                            <label for="input-pin" class="form-control-label d-flex justify-content-start">PIN<span class="text-danger ml-1">*</span></label>
                            <input type="text" id="input-pin" class="swal-content__input form-control mb-2" placeholder="Masukkan PIN Toko">
                        </div>
                        <div class="form-group">
                            <label for="input-message" class="form-control-label d-flex justify-content-start">Pesan<span class="text-danger ml-1">*</span></label>
                            <textarea id="input-message" class="swal-content__input form-control mb-2" placeholder="Masukkan Pesan" rows="4"></textarea>
                        </div>
                    </div>
                `,
                type: "question",
                showCancelButton: true,
                confirmButtonText: "Konfirmasi",
                cancelButtonText: "Batal",
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                reverseButtons: true,
                confirmButtonClass: "btn btn-danger",
                cancelButtonClass: "btn btn-secondary"
            }).then(async (result) => {
                const pin = document.getElementById('input-pin')?.value;
                const message = document.getElementById('input-message')?.value;

                if (!pin) {
                    notificationAlert("error", "Gagal", "PIN tidak boleh kosong!");
                    return;
                }
                if (!message) {
                    notificationAlert("error", "Gagal", "Pesan tidak boleh kosong!");
                    return;
                }

                let postDataRest = await renderAPI(
                        'PUT',
                        '{{ route('master.stockbarang.refresh-stok') }}', {
                            id: data.id,
                            id_barang: data.id_barang,
                            id_toko: '{{ auth()->user()->id_toko }}',
                            user_id: '{{ auth()->user()->id }}',
                            message: message,
                            pin: pin
                        }
                    ).then(response => response)
                    .catch(error => error.response);

                swal.close();

                if (postDataRest.status == 200) {
                    setTimeout(() => {
                        getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch,
                            customFilter);
                    }, 500);
                    notificationAlert('success', 'Pemberitahuan', postDataRest.data.message);
                } else {
                    notificationAlert('error', 'Gagal', postDataRest.data?.message ||
                        'Terjadi kesalahan.');
                }
            }).catch(swal.noop);
        }

        function createModalSkeleton(canAturHarga, canDetailBarang) {
            const fullCol = 'col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12';
            const leftCol = 'col-12 col-sm-12 col-md-12 col-lg-8 col-xl-8 divider-col';
            const rightCol = 'col-12 col-sm-12 col-md-12 col-lg-4 col-xl-4';

            const detailBarangHTML = canDetailBarang ? `
        <div class="mb-2">
            <div class="section-title">Detail Barang</div>
            <div class="scroll-section">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="thead-light">
                            <tr class="tb-head">
                                <th>#</th>
                                <th>QR Code Pembelian</th>
                                <th>Tgl Nota</th>
                                <th>Stok</th>
                                <th>Hpp Baru</th>
                            </tr>
                        </thead>
                        <tbody id="modal-detail-barang-body"></tbody>
                    </table>
                </div>
            </div>
        </div>` : '';

            const barangTokoHTML = `
        <div class="mb-2">
            <div class="section-title">Barang di Toko</div>
            <div class="scroll-section">
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead id="modal-stock-table-head">
                            <tr>
                                <th class="toko-col">Nama Toko</th>
                                <th>Stok</th>
                                <th class="level-header">Level Harga</th>
                            </tr>
                        </thead>
                        <tbody id="modal-stock-table-body"></tbody>
                    </table>
                </div>
            </div>
        </div>`;

            const aturHargaHTML = `
        <div class="${rightCol} mb-2">
            <div class="section-title">Atur Harga</div>
            <div id="dynamic-harga-form" class="harga-form mt-1"></div>
        </div>`;

            let content = '';

            if (canAturHarga) {
                content = `
            <div class="${leftCol}">
                ${canDetailBarang ? detailBarangHTML : ''}
                ${barangTokoHTML}
            </div>
            ${aturHargaHTML}`;
            } else if (canDetailBarang) {
                content = `
            <div class="${fullCol}">
                ${detailBarangHTML}
                ${barangTokoHTML}
            </div>`;
            } else {
                content = `
            <div class="${fullCol}">
                ${barangTokoHTML}
            </div>`;
            }

            $('body').append(`
        <div class="modal fade" id="dynamicModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title modal-barang-title"></h5>
                        <button type="button" class="btn-close reset-all close" data-bs-dismiss="modal"
                                aria-label="Close"><i class="fa fa-xmark"></i></button>
                    </div>
                    <div class="modal-body">
                        <div class="container-fluid">
                            <div class="row">
                                ${content}
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer"></div>
                </div>
            </div>
        </div>
    `);
        }

        async function renderTabStokBarang(stokData) {
            let rows = '';
            let showTokoColumn = false;

            if (Array.isArray(stokData.per_toko) && stokData.per_toko.length > 0) {
                showTokoColumn = true;
                stokData.per_toko.forEach(toko => {
                    const levelHargaToko = toko.level_harga?.length > 0 ?
                        toko.level_harga.join(', ') :
                        'Tidak Ada Level Harga';

                    rows += `
                <tr>
                    <td>${toko.nama_toko}</td>
                    <td>${toko.stock}</td>
                    <td>${levelHargaToko}</td>
                </tr>`;
                });
            } else {
                const levelHargaList = stokData.level_harga && Object.keys(stokData.level_harga).length > 0 ?
                    Object.entries(stokData.level_harga).map(([key, value]) => `${key} (${value})`).join(', ') :
                    'Tidak Ada Level Harga';

                rows += `
            <tr>
                <td class="d-none">Toko Utama</td>
                <td>${stokData.stock}</td>
                <td>${levelHargaList}</td>
            </tr>`;
            }

            $('#modal-stock-table-body').html(rows);
            $('#modal-stock-table-head th.toko-col').toggleClass('d-none', !showTokoColumn);
        }


        async function renderTabAturHarga(id_barang, stokData) {
            const hppValue = stokData.hpp_awal || 0;

            let formContent = `
        <form class="level-harga-form">
            <input type="hidden" name="id_barang" value="${id_barang}">`;

            let i = 0;
            for (const [levelName, levelVal] of Object.entries(stokData.level_harga || {})) {
                const cleanValue = parseInt(levelVal.replace(/[^\d]/g, '')) || 0;
                const inputId = `harga-${id_barang}-${levelName.replace(/\s+/g, '-')}`;
                const hiddenId = `level_harga_raw_${i}`;
                const nameAttr = `harga_level_${levelName.replace(/\s+/g, '_')}_barang_${id_barang}`;
                const persenId = `persen-${id_barang}-${levelName.replace(/\s+/g, '-')}`;
                const persen = hppValue > 0 ? (((cleanValue - hppValue) / hppValue) * 100).toFixed(2) : '0';

                formContent += `
            <div class="input-group mb-3">
                <div class="input-group-prepend"><span class="input-group-text">${levelName}</span></div>
                <input type="text" name="level_harga[]" id="${inputId}" class="form-control level-harga"
                    placeholder="Atur harga baru" value="${cleanValue.toLocaleString('id-ID')}"
                    data-raw-value="${cleanValue}" data-hpp-baru="${hppValue}">
                <input type="hidden" id="${hiddenId}" name="${nameAttr}" value="${cleanValue}">
                <input type="hidden" name="level_nama[]" value="${levelName}">
                <div class="input-group-append"><span class="input-group-text" id="${persenId}">${persen}%</span></div>
            </div>`;
                i++;
            }

            formContent += `
        <input type="hidden" id="hpp-baru-${id_barang}" value="${hppValue}">
        <button type="submit" class="btn btn-primary w-100" id="btn-update-level-harga">
            <i class="fa fa-save mr-1"></i>Update
        </button>
    </form>`;

            $('#dynamic-harga-form').html(formContent);

            // Event input
            document.querySelectorAll('.level-harga').forEach(input => {
                input.addEventListener('input', function() {
                    let rawValue = this.value.replace(/[^0-9]/g, '');
                    this.setAttribute('data-raw-value', rawValue);
                    this.value = rawValue ? parseInt(rawValue).toLocaleString('id-ID') : '';
                    calculatePercentage(this);
                });
            });

            // Hitung awal
            document.querySelectorAll('.level-harga').forEach(input => calculatePercentage(input));

            // Submit handler
            document.querySelector('.level-harga-form').addEventListener('submit', async function(e) {
                e.preventDefault();
                const form = this;
                const formData = new FormData(form);
                const btnSubmit = document.querySelector('#btn-update-level-harga');
                const originalBtnHTML = btnSubmit.innerHTML;
                btnSubmit.innerHTML =
                    `<span class="spinner-border spinner-border-sm mr-1"></span> Mengupdate...`;
                btnSubmit.disabled = true;

                form.querySelectorAll('.level-harga').forEach(input => {
                    const hidden = form.querySelector(
                        `#${input.id.replace(/^harga/, 'level_harga_raw')}`);
                    if (hidden) {
                        hidden.value = input.getAttribute('data-raw-value') || '0';
                        formData.set(hidden.name, hidden.value);
                    }
                });

                const resp = await renderAPI('POST', '/admin/update-level-harga', formData);

                if (resp.status === 200) {
                    notyf.success(resp.data.message || 'Harga berhasil diperbarui');
                    setTimeout(() => {
                        getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch,
                            customFilter);
                    }, 1000);
                } else {
                    notyf.error(resp.data?.message || 'Gagal memperbarui harga');
                }

                btnSubmit.innerHTML = originalBtnHTML;
                btnSubmit.disabled = false;
            });

            function calculatePercentage(input) {
                const hpp = parseFloat(input.getAttribute('data-hpp-baru')) || 0;
                const raw = parseFloat(input.getAttribute('data-raw-value')) || 0;
                const persen = hpp > 0 ? ((raw - hpp) / hpp) * 100 : 0;
                const idParts = input.id.split('-');
                const idSuffix = idParts.slice(2).join('-');
                const persenElem = document.getElementById(`persen-${idParts[1]}-${idSuffix}`);
                if (persenElem) persenElem.textContent = `${persen.toFixed(2)}%`;
            }
        }

        async function renderTabDetailBarang(id_barang) {
            const detailResp = await renderAPI('GET', `/admin/get-detail-barang/${id_barang}`, {}).then(r => r).catch(
                e => e.response);
            const target = '#modal-detail-barang-body';

            if (detailResp && detailResp.status === 200 && Array.isArray(detailResp.data.data)) {
                const rows = detailResp.data.data.map((el, idx) => `
            <tr class="text-dark">
                <td class="text-center">${idx + 1}.</td>
                <td>
                    <div class="d-flex flex-wrap align-items-center justify-content-between">
                        <span class="mr-1 mb-1 text-break" id="qrcode-text-${idx}">${el.qrcode}</span>
                        <button type="button" class="btn btn-sm btn-outline-primary copy-btn"
                            data-toggle="tooltip" title="Salin: ${el.qrcode}" data-target="qrcode-text-${idx}">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </td>
                <td class="text-break" style="max-width: 250px; word-break: break-word;">${el.tgl_nota}</td>
                <td>${el.qty}</td>
                <td>${el.harga}</td>
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
                $(target).html(`<tr><td colspan="5">Tidak ada stok detail barang</td></tr>`);
            }
        }

        function updateRawValue(el, index) {
            const value = el.value.replace(/\./g, '');
            document.getElementById(`level_harga_raw_${index}`).value = value;
        }

        function formatCurrency(input) {
            let value = input.value.replace(/\D/g, '');
            input.value = new Intl.NumberFormat('id-ID').format(value);
        }

        async function initPageLoad() {
            await getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch, customFilter);
            await searchList();
        }

        document.getElementById('sortAscStock').addEventListener('click', function() {
            handleSort('asc');
        });

        document.getElementById('sortDescStock').addEventListener('click', function() {
            handleSort('desc');
        });

        function handleSort(orderBy) {
            const currentSearch = document.getElementById('searchInput')?.value ||
                ''; // Adjust based on your search input ID
            const currentPage = 1; // Reset to the first page when sorting
            getListData(defaultLimitPage, currentPage, orderBy === 'asc' ? 1 : 0, currentSearch);
        }
    </script>
@endsection
