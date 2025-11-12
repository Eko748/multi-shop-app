<script>
    async function getListData(limit = 10, page = 1, ascending = 0, search = '', customFilter = {}) {
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
            '{{ route('retur.supplier.get') }}', {
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
            const dataArray = getDataRest.data.data.item || [];
            const total = getDataRest.data.data.total || null;

            if (Array.isArray(dataArray) && dataArray.length > 0) {
                let handleDataArray = await Promise.all(
                    dataArray.map(item => handleData(item))
                );
                await setListData(handleDataArray, getDataRest.data.pagination, total);
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
            }
        } else {
            let errorMessage = getDataRest?.data?.message || 'Data gagal dimuat';

            $('#listData').html(`
                <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
                    <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
                        <div class="alert alert-warning text-center mt-4" role="alert">
                            ${errorMessage}
                        </div>
                    </div>
                </div>
                `);
            $('#countPage').text("0 - 0");
            $('#totalPage').text("0");
        }
    }

    async function handleData(data) {
        const detail_button = `
        <button onClick="openDetailModal('${encodeURIComponent(JSON.stringify(data))}')"
            class="action_button btn btn-outline-secondary btn-md"
            title="Detail retur ${data.tanggal}"
            data-id="${data?.id}" data-container="body" data-toggle="tooltip" data-placement="top">
            <span class="text-dark">Detail</span>
            <div class="icon text-info">
                <i class="mb-1 fa fa-book"></i>
            </div>
        </button>
    `;

        // === Tombol Verify (muncul hanya jika verified === true) ===
        let verify_button = '';
        if (data.verified === true) {
            verify_button = `
            <button onClick="verifyRetur(${data.id})"
                class="action_button btn btn-outline-success btn-md"
                title="Verifikasi retur ${data.tanggal}"
                data-id="${data?.id}" data-container="body" data-toggle="tooltip" data-placement="top">
                <span class="text-success">Verifikasi</span>
                <div class="icon text-success">
                    <i class="mb-1 fa fa-check-circle"></i>
                </div>
            </button>
        `;
        }

        let statusBadge = '';
        let totalSelisih = '';

        if (data.status === 'rugi') {
            statusBadge = `<span class="badge badge-danger p-2">
            <i class="fa fa-arrow-down"></i> Rugi - ${data.total_selisih}
        </span>`;
            totalSelisih = `<span class="ml-2 text-danger">${data.total_selisih}</span>`;
        } else if (data.status === 'untung') {
            statusBadge = `<span class="badge badge-success p-2">
            <i class="fa fa-arrow-up"></i> Untung - ${data.total_selisih}
        </span>`;
            totalSelisih = `<span class="ml-2 text-success">${data.total_selisih}</span>`;
        } else {
            statusBadge = `<span class="badge badge-info p-2">
            <i class="fa fa-balance-scale"></i> Seimbang
        </span>`;
        }

        const keterangan = `
        <div class="d-flex align-items-center">
            ${statusBadge}
            ${totalSelisih}
        </div>
    `;

        // === Info tambahan ===
        let infoText = 'Dibuat oleh:';
        let infoUser = `${data.created_by || '-'}`;
        let infoTime = `${data.tanggal || '-'}`;

        return {
            id: data?.id ?? '-',
            supplier: data?.supplier ?? '-',
            tipe_retur: data?.tipe_retur ?? '-',
            total_refund: data?.total_refund ?? '-',
            total_hpp: data?.total_hpp ?? '-',
            total_selisih: data?.total_selisih ?? '-',
            tanggal: infoTime ?? '-',
            detail_button,
            verify_button, // tambahkan properti ini untuk digunakan di tabel
            keterangan,
            statusBadge,
            totalSelisih,
            info: `
            <div>
                <small class="text-muted">${infoText}</small>
                <small class="text-bold">${infoUser}</small>
            </div>
        `
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
                                <th scope="col" class="${tdClass}" style="width:10%">Tanggal</th>
                                <th scope="col" class="${tdClass}" style="width:10%">Informasi</th>
                                <th scope="col" class="${tdClass}" style="width:10%">Status</th>
                                <th scope="col" class="${tdClass}" style="width:10%">Supplier</th>
                                <th scope="col" class="${tdClass}" style="width:10%">Tipe Retur</th>
                                <th scope="col" class="${tdClass} text-right" style="width:15%">Total Hpp</th>
                                <th scope="col" class="${tdClass} text-right" style="width:15%">Total Refund</th>
                                <th scope="col" class="${tdClass} text-center" style="width:15%">Aksi</th>
                            </tr>
                        </thead>
                        <thead>
                            <tr>
                                <th colspan="6"></th>
                                <th colspan="1" class="${tdClass} text-right"><span class="badge badge-primary">${total.hpp.format || 0}</span></th>
                                <th colspan="1" class="${tdClass} text-right"><span class="badge badge-primary">${total.refund.format || 0}</span></th>
                                <th colspan="1"></th>
                            </tr>
                        </thead>
                        <tbody>`;

        dataList.forEach((element, index) => {
            const number = display_from + index;
            getDataTable += `
            <tr class="glossy-tr">
                <td class="${tdClass} text-center">${number}</td>
                <td class="${tdClass}">${element.tanggal}</td>
                <td class="${tdClass}">${element.info}</td>
                <td class="${tdClass}">${element.statusBadge}</td>
                <td class="${tdClass}">${element.supplier}</td>
                <td class="${tdClass}">${element.tipe_retur}</td>
                <td class="${tdClass} text-right">${element.total_hpp}</td>
                <td class="${tdClass} text-right">${element.total_refund}</td>
                <td class="${tdClass}">
                    <div class="d-flex justify-content-center flex-column flex-sm-row align-items-center align-items-sm-start mx-3" style="gap: 0.5rem;">
                        ${element.detail_button || ''}
                                ${element.verify_button || ''}

                    </div>
                </td>
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

    async function saveData(mode, encodedData) {
        $(document).off("click", "#save-btn").on("click", "#save-btn", async function(e) {
            e.preventDefault();

            let method = 'POST';
            let url = `{{ route('retur.supplier.post') }}`;

            const btn = $(this);
            const saveButton = this;

            // ambil payload retur
            const payload = getPayloadRetur();

            if (!payload.items.length) {
                notificationAlert('warning', 'Peringatan', 'Tidak ada item yang dipilih.');
                return;
            }

            // ambil tipe retur
            const tipe_retur = $('#tipe_retur').val();

            // grupkan items berdasarkan supplier_id
            let groupedData = {};
            payload.items.forEach(item => {
                if (!groupedData[item.supplier_id]) {
                    groupedData[item.supplier_id] = [];
                }
                groupedData[item.supplier_id].push(item);
            });

            // buat array data sesuai supplier
            let retur = [];
            Object.keys(groupedData).forEach(supplier_id => {
                const items = groupedData[supplier_id];

                // hitung total per supplier
                let total_refund = 0;
                let total_hpp = 0;

                items.forEach(item => {
                    total_refund += item.total_refund || 0;

                    // fallback jika qty_refund + qty_barang kosong
                    let qtyDipakai = (item.qty_refund || 0) + (item.qty_barang || 0);
                    if (qtyDipakai === 0) {
                        qtyDipakai = item.qty || 0;
                    }

                    total_hpp += (item.hpp || 0) * qtyDipakai;
                });

                const selisih = Math.abs(total_refund - total_hpp);

                let status = "seimbang";
                if (total_refund < total_hpp) {
                    status = "rugi";
                } else if (total_refund > total_hpp) {
                    status = "untung";
                }

                retur.push({
                    tanggal: $('#tanggal').val(),
                    created_by: '{{ auth()->user()->id }}',
                    toko_id: '{{ auth()->user()->id_toko }}',
                    tipe_retur: tipe_retur,
                    supplier_id: supplier_id,
                    items: items,
                    total_refund: total_refund,
                    total_hpp: total_hpp,
                    selisih: selisih,
                    status: status
                });
            });

            // bentuk payload final (object, bukan array)
            let formData = {
                retur: retur,
                summary: payload.summary
            };

            if (saveButton.disabled) return;

            swal({
                title: "Konfirmasi",
                text: `Apakah Anda yakin ingin menyimpan data retur ini?`,
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

                try {
                    const response = await renderAPI(method, url, formData);
                    loadingPage(false);

                    if (response.status >= 200 && response.status < 300) {
                        notificationAlert('success', 'Pemberitahuan', response.data
                            .message || 'Data berhasil disimpan.');
                        isDataSaved = true;

                        setTimeout(async function() {
                            await Promise.all([
                                getListData(defaultLimitPage,
                                    currentPage, defaultAscending,
                                    defaultSearch, customFilter),
                                getListData2(defaultLimitPage2,
                                    currentPage2, defaultAscending2,
                                    defaultSearch2, customFilter2),
                                getCountData()
                            ]);
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

                    const message = error?.response?.data?.message ||
                        'Terjadi kesalahan saat menyimpan data.';
                    notificationAlert('error', 'Kesalahan', message);

                    saveButton.disabled = false;
                    btn.html(originalContent);
                }
            });
        });
    }

    async function deleteData(encodedData) {
        let data = JSON.parse(decodeURIComponent(encodedData));

        swal({
            title: `Hapus Data Retur`,
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
                `{{ route('retur.member.delete') }}`, {
                    id: data.id,
                    deleted_by: '{{ auth()->user()->id }}'
                }
            ).then(res => res).catch(err => err.response);

            if (postDataRest.status == 200) {
                setTimeout(function() {
                    getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch,
                        customFilter);
                }, 500);
                notificationAlert('success', 'Pemberitahuan', postDataRest.data.message);
            }
        }).catch(swal.noop);
    }

    function openAddModal() {
        renderModalForm('add');
        $('#save-btn')
            .removeClass('btn-primary d-none')
            .addClass('btn-success')
            .prop('disabled', false)
            .html('<i class="fa fa-save mr-1"></i>Simpan');

        $('#modal-form').modal('show');
    }

    async function openDetailModal(encodedData) {
        let data = JSON.parse(decodeURIComponent(encodedData));
        await renderModalDetail();

        await $('#save-btn')
            .removeClass('btn-success')
            .addClass('btn-primary d-none')
            .prop('disabled', true)
            .html('<i class="fa fa-edit mr-1"></i>Update');

        await $('#modal-form').modal('show');

        fetchConfigMap.getListData2 = {
            fn: getListData2,
            getConfig: () => ({
                limit: defaultLimitPage2,
                page: currentPage2,
                asc: defaultAscending2,
                search: defaultSearch2,
                filter: customFilter2,
                id: data.id
            }),
            setPage: (val) => currentPage2 = val,
            getTotalPage: () => totalPage3,
            setSearch: (val) => defaultSearch2 = val,
            setLimit: (val) => {
                defaultLimitPage2 = val;
            }
        };

        await Promise.all([
            getListData2(
                defaultLimitPage2,
                currentPage2,
                defaultAscending2,
                defaultSearch2,
                customFilter2 = {
                    id: data.id
                },
            ),
            searchList('getListData2', '#limitPage2', '.tb-search2'),
        ]);
    }

    async function renderModalForm(mode = 'add', encodedData = '') {
        let data = {};

        if (encodedData && typeof encodedData === 'string' && encodedData.trim() !== '') {
            try {
                data = JSON.parse(decodeURIComponent(encodedData));
            } catch (err) {
                notificationAlert('error', 'Error', 'Terjadi kesalahan saat membaca data enkripsi.');
            }
        }

        const modalTitle = mode === 'edit' ?
            `<i class="fa fa-edit mr-1"></i>Edit Retur` :
            `<i class="fa fa-undo mr-1"></i>Form Retur`;

        $('#modalLabel').html(modalTitle);

        const tdClass = 'text-wrap align-top';
        const now = new Date();
        const pad = (n) => n.toString().padStart(2, '0');
        const currentDateTime =
            `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`;

        const formContent = `
        <form id="form-retur">
            <style>
                #form-retur .select2-container{width:100% !important; max-width:100%;}
                #form-retur .select2-selection{min-height:35px;}
                #form-retur .select2-selection__rendered{line-height:33px;}
                #form-retur .select2-selection__arrow{height:33px;}
                .table-responsive{overflow-x:auto;}
                #tableReturItems{min-width: 900px;}
            </style>

            <div class="form-group">
            <div class="row">
                <div class="col-md-8 mb-2">
                    <label for="tipe_retur"><i class="fa fa-random mr-1"></i>Tipe Retur <sup class="text-danger">*</sup></label>
                    <select class="form-control" id="tipe_retur" name="tipe_retur" required>
                        <option value="">- Pilih -</option>
                        <option value="pembelian">Pembelian Barang</option>
                        <option value="member">Retur Member</option>
                    </select>
                </div>
                <div class="col-md-4 mb-2">
                    <label for="tanggal"><i class="fa fa-calendar mr-1"></i>Tanggal <sup class="text-danger">*</sup></label>
                    <input type="datetime-local" class="form-control" id="tanggal" name="tanggal" value="${currentDateTime}" required>
                </div>
            </div>
        </div>

        <div id="extra-select" class="form-group">
            <div class="row">
                <div class="col-md-12 d-none" id="supplier-wrapper">
                    <label for="supplier"><i class="fa fa-user mr-1"></i>Suplier <sup class="text-danger">*</sup></label>
                    <select class="form-control select2" id="supplier" name="supplier"></select>
                </div>
                <div class="col-md-12 d-none" id="qrcode-wrapper">
                    <label for="qrcode_pembelian"><i class="fa fa-qrcode mr-1"></i>QR Code Pembelian <sup class="text-danger">*</sup></label>
                    <select class="form-control select2" id="qrcode_pembelian" name="qrcode_pembelian"></select>
                </div>
            </div>
        </div>

            <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover m-0" id="tableReturItems">
                            <thead class="glossy-thead">
                                <tr>
                                    <th class="text-center" style="width:5%">No</th>
                                    <th class="text-left" style="width:15%">Barang</th>
                                    <th class="text-left" style="width:15%">Kompensasi</th>
                                    <th class="text-left" style="width:15%">Qty</th>
                                    <th class="text-right" style="width:15%">Refund (Rp)</th>
                                    <th class="text-right" style="width:15%">Total Refund</th>
                                    <th class="text-right" style="width:15%">Hpp</th>
                                    <th class="text-right" style="width:10%">Selisih</th>
                                    <th class="text-center" style="width:5%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="no-data">
                                    <td class="${tdClass} text-center" colspan="9">
                                        <div class="text-center my-3">
                                            <i class="fa fa-circle-info mr-1"></i>Tidak ada data retur.
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </form>`;

        $('#modal-data').html(formContent);

        $('#tipe_retur').on('change', async function() {
            const val = $(this).val();
            if (val === 'member') {
                $('#supplier-wrapper').removeClass('d-none');
                $('#qrcode-wrapper').addClass('d-none');
                await getHarga('#supplier');
            } else if (val === 'pembelian') {
                $('#qrcode-wrapper').removeClass('d-none');
                $('#supplier-wrapper').addClass('d-none');
                await getHarga('#qrcode_pembelian');
            } else {
                $('#supplier-wrapper, #qrcode-wrapper').addClass('d-none');
            }
        });

        let selectOptions = [{
                id: '#supplier',
                isUrl: '{{ route('retur.supplier.getSupplier') }}',
                placeholder: 'Pilih Suplier',
                isModal: '#modal-form',
            },
            {
                id: '#qrcode_pembelian',
                isUrl: '{{ route('retur.supplier.getQRCode') }}',
                placeholder: 'Isi dari QR Code pada menu Detail Pembelian Barang',
                isModal: '#modal-form',
                isMinimun: 3,
            }
        ];

        await selectData(selectOptions);
        await saveData(mode, encodedData);
        await checkEmptyTable();
    }

    async function getHarga(selector) {
        $(selector).on('change', async function() {
            const selectedId = $(this).val();
            if (!selectedId) return;

            try {
                let response = await renderAPI(
                        'GET',
                        '{{ route('retur.supplier.getHargaBarang') }}', {
                            id: selectedId,
                            tipe: $('#tipe_retur').val()
                        }
                    ).then(res => res)
                    .catch(err => err.response);

                if (response?.data) {
                    const items = response.data.data; // bisa lebih dari 1
                    renderTable(items);

                    // reset select biar tidak double
                    $(selector).val(null).trigger('change');
                    checkEmptyTable();
                } else {
                    notificationAlert('warning', 'Perhatian', 'Data harga barang tidak ditemukan.');
                }
            } catch (err) {
                notificationAlert('error', 'Error', 'Gagal mengambil harga barang.');
            }
        });
    }

    function renderTable(items) {
        const tbody = $('#tableReturItems tbody');
        tbody.find('.no-data').remove(); // hapus row kosong default

        items.forEach(item => {
            let supplierGroup = tbody.find(`tr.supplier-row[data-supplier="${item.supplier_id}"]`);

            // kalau belum ada supplier, buat row supplier baru
            if (supplierGroup.length === 0) {
                const supplierRow = `
            <tr class="supplier-row table-active font-weight-bold" data-supplier="${item.supplier_id}">
                <td colspan="8">
                    <i class="fa fa-truck mr-1"></i> Supplier: ${item.nama_supplier}
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger remove-supplier" data-supplier="${item.supplier_id}">
                        <i class="fa fa-trash-alt"></i>
                    </button>
                </td>
            </tr>
        `;
                tbody.append(supplierRow);
            }

            // cek apakah barang ini sudah ada di bawah supplier
            let existingItem = tbody.find(
                `tr.item-row[data-id="${item.id}"][data-supplier="${item.supplier_id}"]`);
            if (existingItem.length > 0) {
                notificationAlert('warning', 'Perhatian', 'Barang ini sudah ada di daftar retur.');
                return;
            }

            const rowCount = tbody.find(`tr.item-row[data-supplier="${item.supplier_id}"]`).length + 1;
            const newRow = `
                <tr class="item-row" data-id="${item.id}" data-supplier="${item.supplier_id}" data-barang="${item.barang_id}" data-qtymax="${item.qty_now}" data-hpp="${item.hpp}" data-harga_jual="${item.harga_jual}">
                    <td class="align-top text-center">${rowCount}</td>
                    <td class="align-top text-wrap"><details><summary>${item.barang}</summary><hr><p>${item.tgl}</p></details></td>
                    <td class="align-top">
                        <select class="form-control kompensasi">
                            <option value="refund">Refund</option>
                            <option value="barang">Barang Sejenis</option>
                            <option value="kombinasi">Kombinasi</option>
                        </select>
                    </td>
                    <td class="align-top align-items-start">
                        <input type="number" class="form-control qty qty-origin" value="1" min="1" max="${item.qty_now}">
                        <div class="justify-content-end qty-info">
                            <small class="text-danger qty-info">Maks: ${item.qty_now}</small>
                        </div>
                        <div class="kombinasi-inputs d-none">
                            <div class="d-flex flex-column">
                                <input type="number" class="form-control qty qty-refund" value="0" min="0" max="${item.qty_now}">
                                <div class="d-flex justify-content-between">
                                    <label class="small">Qty Refund</label>
                                    <small class="text-danger qty-refund-info">Maks: ${item.qty_now}</small>
                                </div>
                            </div>
                            <div class="d-flex flex-column">
                                <input type="number" class="form-control qty-barang" value="0" min="0" max="${item.qty_now}">
                                <div class="d-flex justify-content-between">
                                    <label class="small">Qty Barang</label>
                                    <small class="text-danger qty-barang-info">Maks: ${item.qty_now}</small>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="align-top text-right jumlah-refund-wrapper">
                        <input type="number" class="form-control jumlah-refund" value="0" min="0">
                    </td>
                    <td class="align-top text-right total-refund">${formatRupiah(0)}</td>
                    <td class="align-top text-right hpp">${formatRupiah(item.hpp)}</td>
                    <td class="align-top text-right selisih">${formatRupiah(0)}</td>
                    <td class="align-top text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-item"><i class="fa fa-times"></i></button>
                    </td>
                </tr>
            `;
            tbody.find(`tr.supplier-row[data-supplier="${item.supplier_id}"]`).last().after(newRow);
        });

        // Event change kompensasi
        tbody.off('change', '.kompensasi').on('change', '.kompensasi', function() {
            const row = $(this).closest('tr.item-row');
            const komp = $(this).val();

            if (komp === 'barang') {
                // Refund Rp jadi teks
                row.find('.jumlah-refund-wrapper').html(`<span class="text-muted">-</span>`);
                // qty origin tetap aktif
                row.find('.kombinasi-inputs').addClass('d-none');
                row.find('.qty-origin').removeClass('d-none');
                row.find('.qty-info').removeClass('d-none');
            } else if (komp === 'kombinasi') {
                row.find('.kombinasi-inputs').removeClass('d-none');
                row.find('.qty-origin').addClass('d-none');
                row.find('.qty-info').addClass('d-none');
                row.find('.jumlah-refund-wrapper').html(`
                    <div class="d-flex flex-column justify-content-between h-100">
                        <input type="number" class="form-control jumlah-refund" value="0" min="0">
                        <small id="hppInfo" class="text-muted mt-1"></small>
                    </div>
                `);
            } else { // refund
                row.find('.kombinasi-inputs').addClass('d-none');
                row.find('.qty-origin').removeClass('d-none');
                row.find('.qty-info').removeClass('d-none');
                row.find('.jumlah-refund-wrapper').html(`
            <input type="number" class="form-control jumlah-refund" value="0" min="0">
        `);
            }

            hitungSubtotal();
        });

        // Event input qty / kombinasi / jumlah refund
        tbody.off('input', '.qty, .qty-barang, .qty-refund, .jumlah-refund').on('input',
            '.qty, .qty-barang, .qty-refund, .jumlah-refund',
            function() {
                const row = $(this).closest('tr.item-row');
                const max = parseInt(row.data('qtymax'));
                const hpp = parseInt(row.data('hpp'));

                let total = 0;

                if (row.find('.kompensasi').val() === 'kombinasi') {
                    let qBarang = parseInt(row.find('.qty-barang').val()) || 0;
                    let qRefund = parseInt(row.find('.qty-refund').val()) || 0;

                    // Pastikan gabungan tidak lebih dari max
                    if (qBarang + qRefund > max) {
                        if ($(this).hasClass('qty-barang')) {
                            qBarang = max - qRefund;
                            row.find('.qty-barang').val(qBarang);
                        } else {
                            qRefund = max - qBarang;
                            row.find('.qty-refund').val(qRefund);
                        }
                    }

                    row.find('.qty-barang-info').text(`Maks ${max - qRefund}`);
                    row.find('.qty-refund-info').text(`Maks ${max - qBarang}`);

                    const jmlRefund = parseInt(row.find('.jumlah-refund').val()) || 0;
                    total = jmlRefund * hpp;
                } else {
                    const qty = parseInt(row.find('.qty').val()) || 0;
                    const jmlRefund = parseInt(row.find('.jumlah-refund').val()) || 0;
                    total = jmlRefund * hpp;
                    if (qty > max) {
                        row.find('.qty').val(max);
                    }
                }

                row.find('.total').text(formatRupiah(total));
                hitungSubtotal();
            });

        // Event hapus item
        tbody.off('click', '.remove-item').on('click', '.remove-item', function() {
            const row = $(this).closest('tr.item-row');
            const supplierId = row.data('supplier');
            row.remove();

            if (tbody.find(`tr.item-row[data-supplier="${supplierId}"]`).length === 0) {
                tbody.find(`tr.supplier-row[data-supplier="${supplierId}"]`).remove();
            }

            checkEmptyTable();
            reIndexRows();
            hitungSubtotal();
        });

        // Event hapus supplier
        tbody.off('click', '.remove-supplier').on('click', '.remove-supplier', function() {
            const supplierId = $(this).data('supplier');
            tbody.find(`tr[data-supplier="${supplierId}"]`).remove();
            checkEmptyTable();
            reIndexRows();
            hitungSubtotal();
        });

        reIndexRows();
        hitungSubtotal();
    }

    function hitungSubtotal() {
        let totalRefund = 0;
        let totalHpp = 0;
        let totalSelisih = 0;

        $('#tableReturItems tbody .item-row').each(function() {
            const hpp = Number($(this).data('hpp')) || 0;
            const komp = $(this).find('.kompensasi').val();

            let qty = Number($(this).find('.qty').val()) || 0;
            let totalRefundItem = 0;

            if (komp === 'refund') {
                const jmlRefundRp = Number($(this).find('.jumlah-refund').val()) || 0;
                totalRefundItem = jmlRefundRp * qty;
            } else if (komp === 'kombinasi') {
                const qRefund = Number($(this).find('.qty-refund').val()) || 0;
                const qBarang = Number($(this).find('.qty-barang').val()) || 0;
                const jmlRefundRp = Number($(this).find('.jumlah-refund').val()) || 0;

                totalRefundItem = (jmlRefundRp * qRefund) + (qBarang * hpp);
                qty = qRefund + qBarang;

                $(this).find('#hppInfo').text(
                    `Qty Barang × HPP = ${formatRupiah(qBarang * hpp)}`
                );
            } else if (komp === 'barang') {
                totalRefundItem = qty * hpp;
            }

            const totalHppItem = qty * hpp;
            const selisihItem = totalHppItem - totalRefundItem;

            // update tampilan row
            $(this).find('.total-refund').text(formatRupiah(totalRefundItem));
            $(this).find('.hpp').text(formatRupiah(totalHppItem));
            $(this).find('.selisih').text(formatRupiah(Math.abs(selisihItem)));

            // simpan nilai di data-row (pastikan numeric)
            $(this).data('total-refund', Number(totalRefundItem));
            $(this).data('total-hpp', Number(totalHppItem));
            $(this).data('selisih', Number(selisihItem));

            // akumulasi juga pastikan numeric
            totalRefund += Number(totalRefundItem);
            totalHpp += Number(totalHppItem);
            totalSelisih += Number(selisihItem);
        });

        // tentukan status selisih
        let labelSelisih = "Seimbang";
        let status = "seimbang";
        if (totalRefund < totalHpp) {
            labelSelisih = "Total Kerugian";
            status = "rugi";
        } else if (totalRefund > totalHpp) {
            labelSelisih = "Total Keuntungan";
            status = "untung";
        }

        // simpan payload keseluruhan di tfoot (atau hidden input)
        $('#tableReturItems').data('summary', {
            totalRefund: totalRefund,
            totalHpp: totalHpp,
            totalSelisih: totalSelisih,
            status: status
        });

        // render ulang footer
        $('#tableReturItems tfoot').html(`
        <tr>
            <td colspan="6" class="text-right font-weight-bold">Total Refund:</td>
            <td class="text-right font-weight-bold">${formatRupiah(totalRefund)}</td>
            <td colspan="2"></td>
        </tr>
        <tr>
            <td colspan="6" class="text-right font-weight-bold">Total HPP:</td>
            <td class="text-right font-weight-bold">${formatRupiah(totalHpp)}</td>
            <td colspan="2"></td>
        </tr>
        <tr>
            <td colspan="6" class="text-right font-weight-bold">${labelSelisih}:</td>
            <td class="text-right font-weight-bold">${formatRupiah(Math.abs(totalSelisih))}</td>
            <td colspan="2" class="text-capitalize text-muted">${status}</td>
        </tr>
    `);
    }

    function getPayloadRetur() {
        const items = [];

        $('#tableReturItems tbody .item-row').each(function() {
            const total_refund = $(this).data('total-refund') || 0;
            const total_hpp = $(this).data('total-hpp') || 0;
            const selisih = Math.abs($(this).data('selisih') || 0);

            let status = "seimbang";
            if (total_refund < total_hpp) {
                status = "rugi";
            } else if (total_refund > total_hpp) {
                status = "untung";
            }

            items.push({
                id: $(this).data('id'),
                supplier_id: $(this).data('supplier'),
                barang_id: $(this).data('barang'),
                kompensasi: $(this).find('.kompensasi').val(),
                qty: parseInt($(this).find('.qty').val()) || 0,
                qty_refund: parseInt($(this).find('.qty-refund').val()) || 0,
                qty_barang: parseInt($(this).find('.qty-barang').val()) || 0,
                jumlah_refund: parseInt($(this).find('.jumlah-refund').val()) || 0,
                hpp: parseInt($(this).data('hpp')) || 0,
                harga_jual: parseInt($(this).data('harga_jual')) || 0,
                total_refund: total_refund,
                total_hpp: total_hpp,
                selisih: selisih,
                status: status // tambahkan status per item
            });
        });

        // ambil summary dari data yang sudah diset di hitungSubtotal
        const rawSummary = $('#tableReturItems').data('summary') || {
            totalRefund: 0,
            totalHpp: 0,
            totalSelisih: 0,
            status: "seimbang"
        };

        const summary = {
            sub_total_refund: rawSummary.totalRefund,
            sub_total_hpp: rawSummary.totalHpp,
            sub_total_selisih: Math.abs(rawSummary.totalSelisih),
            status: rawSummary.status,
            tanggal: $('#tanggal').val(),
            created_by: '{{ auth()->user()->id }}',
        };

        return {
            items: items,
            summary: summary
        };
    }

    function reIndexRows() {
        let no = 1;
        $('#tableReturItems tbody .item-row').each(function() {
            $(this).find('td:first').text(no++);
        });
    }

    function checkEmptyTable() {
        const tbody = $('#tableReturItems tbody');
        const rows = tbody.find('tr.item-row');

        if (rows.length === 0) {
            // kalau tidak ada item, aktifkan tipe retur
            $('#tipe_retur').prop('disabled', false);
            tbody.html(`
            <tr class="no-data">
                <td class="text-center align-top" colspan="9">
                    <div class="text-center my-3">
                        <i class="fa fa-circle-info mr-1"></i>Tidak ada data retur.
                    </div>
                </td>
            </tr>
        `);
        } else {
            // kalau ada item, matikan tipe retur
            $('#tipe_retur').prop('disabled', true);
        }
    }
</script>
