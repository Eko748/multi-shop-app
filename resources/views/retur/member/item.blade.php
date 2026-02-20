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
            '{{ route('retur.member.get') }}', {
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

        let infoText = 'Dibuat oleh:';
        let infoUser = `${data.created_by || '-'}`;
        let infoTime = `${data.tanggal || '-'}`;

        // mapping status ke badge
        let statusBadge = '-';
        switch (data?.status) {
            case 'draft':
                statusBadge =
                    `<span class="badge bg-secondary text-white p-2"><i class="fa fa-file mr-1"></i>Draft</span>`;
                break;
            case 'proses':
                statusBadge =
                    `<span class="badge bg-info text-white p-2"><i class="fa fa-spinner mr-1"></i>Proses</span>`;
                break;
            case 'selesai':
                statusBadge =
                    `<span class="badge bg-success text-white p-2"><i class="fa fa-circle-check mr-1"></i>Selesai</span>`;
                break;
            default:
                statusBadge = `<span class="badge bg-light text-white p-2">${data?.status ?? '-'}</span>`;
                break;
        }

        return {
            id: data?.id ?? '-',
            status: statusBadge,
            toko: data?.toko ?? '-',
            member: data?.member ?? '-',
            total_hpp_barang: data?.total_hpp_barang ?? '-',
            total_refund: data?.total_refund ?? '-',
            keterangan: data?.keterangan ?? '-',
            tanggal: infoTime ?? '-',
            detail_button,
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
                                <th scope="col" class="${tdClass}" style="width:5%">Toko</th>
                                <th scope="col" class="${tdClass}" style="width:10%">Member</th>
                                <th scope="col" class="${tdClass}" style="width:10%">Keterangan Qty</th>
                                <th scope="col" class="${tdClass} text-right" style="width:15%">Total Hpp</th>
                                <th scope="col" class="${tdClass} text-right" style="width:15%">Total Refund</th>
                                <th scope="col" class="${tdClass} text-center" style="width:10%">Aksi</th>
                            </tr>
                        </thead>
                        <thead>
                            <tr>
                                <th colspan="7"></th>
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
                <td class="${tdClass}">${element.status}</td>
                <td class="${tdClass}">${element.toko}</td>
                <td class="${tdClass}">${element.member}</td>
                <td class="${tdClass}">${element.keterangan}</td>
                <td class="${tdClass} text-right">${element.total_hpp_barang}</td>
                <td class="${tdClass} text-right">${element.total_refund}</td>
                <td class="${tdClass}">
                    <div class="d-flex justify-content-center flex-column flex-sm-row align-items-center align-items-sm-start mx-3" style="gap: 0.5rem;">
                        ${element.detail_button || ''}
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
            let url = `{{ route('retur.member.post') }}`;

            const btn = $(this);
            const saveButton = this;

            let selectedItems = [];
            $('#tableReturItems tbody tr[data-id]').each(function() {
                const $tr = $(this);

                const qty = parseInt($tr.find('.qty').val()) || 0;
                const kompensasi = $tr.find('.kompensasi').val();
                const stok = parseInt($tr.data('qty_now')) || 0;
                const harga = parseInt($tr.data('harga')) || 0;
                const hpp = parseInt($tr.data('hpp')) || 0;
                const supplier_id = parseInt($tr.data('supplier_id')) || 0;
                const barang_id = parseInt($tr.data('barang_id')) || 0;
                const kasir = parseInt($tr.data('id')) || 0;

                const jumlah_refund = parseFloat($tr.find('.jumlah_refund').text().replace(
                    /[^\d]/g, '')) || 0;
                const total_refund = parseFloat($tr.find('.total_refund').text().replace(
                    /[^\d]/g, '')) || 0;

                let qty_barang = 0;
                let qty_refund = 0;

                if (kompensasi === 'barang') {
                    if (qty <= stok) {
                        qty_barang = qty;
                        qty_refund = 0;
                    } else {
                        qty_barang = stok;
                        qty_refund = qty - stok;
                    }
                } else if (kompensasi === 'refund') {
                    qty_barang = 0;
                    qty_refund = qty;
                }

                selectedItems.push({
                    supplier_id: supplier_id,
                    barang_id: barang_id,
                    transaksi_kasir_detail_id: kasir,
                    tipe_kompensasi: kompensasi,
                    qty_request: qty,
                    qty_barang: qty_barang,
                    hpp: hpp,
                    harga_jual: harga,
                    total_hpp_barang: qty_barang * hpp,
                    qty_refund: qty_refund,
                    jumlah_refund: jumlah_refund,
                    total_refund: total_refund,
                });
            });

            if (selectedItems.length === 0) {
                notificationAlert('warning', 'Peringatan', 'Tidak ada item yang dipilih.');
                return;
            }

            let memberId = $('#member').val();

            let formData = {
                status: "{{ auth()->user()->toko_id == 1 ? 'selesai' : 'draft' }}",
                tanggal: $('#tanggal').val(),
                created_by: '{{ auth()->user()->id }}',
                toko_id: '{{ auth()->user()->toko_id }}',
                items: selectedItems,
            };

            if (memberId && memberId !== 'Guest' && memberId !== '0') {
                formData.member_id = memberId;
            }

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

        // Dapatkan tanggal & waktu saat ini
        const now = new Date();
        const pad = (n) => n.toString().padStart(2, '0');

        // format yyyy-mm-ddThh:mm (24 jam)
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
                    <div class="col-md-8">
                        <label for="member"><i class="fa fa-user mr-1"></i>Member <sup class="text-danger">*</sup></label>
                        <select class="form-control select2" id="member" name="member" required></select>
                    </div>
                    <div class="col-md-4">
                        <label for="tanggal"><i class="fa fa-calendar mr-1"></i>Tanggal <sup class="text-danger">*</sup></label>
                        <input type="datetime-local" class="form-control" id="tanggal" name="tanggal" value="${currentDateTime}" required>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-12">
                        <label for="barang"><i class="fa fa-qrcode mr-1"></i>QR Code Transaksi Kasir <sup class="text-danger">*</sup></label>
                        <select class="form-control select2" id="barang" name="barang" required></select>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover m-0" id="tableReturItems">
                            <thead class="glossy-thead">
                                <tr>
                                    <th class="${tdClass} text-center" style="width:5%">No</th>
                                    <th class="${tdClass} text-left" style="width:25%">Barang</th>
                                    <th class="${tdClass} text-left" style="width:15%">Kompensasi</th>
                                    <th class="${tdClass} text-left" style="width:15%">Qty</th>
                                    <th class="${tdClass} text-right" style="width:15%">Jumlah Refund</th>
                                    <th class="${tdClass} text-right" style="width:15%">Total Refund</th>
                                    <th class="${tdClass} text-center" style="width:5%">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="no-data">
                                    <td class="${tdClass} text-center" colspan="7">
                                        <div class="text-center my-3">
                                            <i class="fa fa-circle-info mr-1"></i>Tidak ada data retur.
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-left"><sup class="text-danger mr-1">**</sup>Jika permintaan kompensasi Barang Sejenis kurang dari sisa stok, maka sisanya dianggap kompensasi Refund.</td>
                                    <td colspan="1" class="text-right font-weight-bold">SubTotal:</td>
                                    <td id="total_harga" class="text-right font-weight-bold">Rp 0</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </form>`;

        $('#modal-data').html(formContent);

        // PRELOAD DATA JIKA MODE EDIT
        if (mode === 'edit' && data?.detail) {
            renderTable(data.detail);
        }

        $('#barang').on('change', async function() {
            const selectedId = $(this).val();
            if (!selectedId) return;

            try {
                let response = await renderAPI(
                        'GET',
                        '{{ route('retur.member.getHargaBarang') }}', {
                            id: selectedId,
                        }
                    ).then(res => res)
                    .catch(err => err.response);

                if (response?.data) {
                    const item = response.data.data[0]; // asumsi 1 item per QR Code
                    const tbody = $('#tableReturItems tbody');
                    const existingRow = tbody.find(`tr[data-id="${item.id}"]`);

                    if (existingRow.length) {
                        // Barang sudah ada → tambah qty
                        const inputQty = existingRow.find('.qty');
                        const currentQty = parseInt(inputQty.val()) || 0;

                        let maxQty = item.qty;
                        if (currentQty < maxQty) {
                            inputQty.val(currentQty + 1).trigger('change');
                        } else {
                            notificationAlert('warning', 'Perhatian',
                                'Qty barang ini sudah mencapai batas maksimal.');
                        }
                    } else {
                        // Barang baru → render row
                        renderTable([item], true);
                    }

                    // hapus dari select setelah dipilih (supaya tidak dobel di list select2)
                    $('#barang').val(null).trigger('change');

                } else {
                    notificationAlert('warning', 'Perhatian',
                        'Data harga barang tidak ditemukan.');
                }
            } catch (err) {
                notificationAlert('error', 'Error', 'Gagal mengambil harga barang.');
            }
        });

        function renderTable(itemList, append = false) {
            const tbody = $('#tableReturItems tbody');

            // Kalau tidak append, berarti render ulang total → hapus dulu
            if (!append) {
                tbody.find('tr').remove();
            } else {
                // Kalau append, hapus hanya row "Tidak ada data retur."
                tbody.find('tr.no-data').remove();
            }

            if (!itemList || itemList.length === 0) {
                if (!append) {
                    tbody.html(`
                <tr class="no-data">
                    <td class="text-center" colspan="8">
                        <div class="text-center my-3">
                            <i class="fa fa-circle-info mr-1"></i> Tidak ada data retur.
                        </div>
                    </td>
                </tr>
            `);
                }
                return;
            }

            // Tambahkan row data
            itemList.forEach((item) => {
                let maxQty = item.qty ?? 0;

                const rowCount = tbody.find('tr').length; // hitung row yang sudah ada
                const row = `
            <tr data-id="${item.id}"
                data-supplier_id="${item.supplier_id}"
                data-barang_id="${item.barang_id}"
                data-harga="${item.harga}"
                data-hpp="${item.hpp}"
                data-qty="${item.qty}"
                data-qty_now="${item.qty_now}">
                <td class="text-center align-top">${rowCount + 1}</td>
                <td class="align-top">
                    <div><b>${item.qrcode}</b></div>
                    <div>${item.barang}</div>
                </td>
                <td class="align-top">
                    <select class="form-control kompensasi">
                        <option value="">- Pilih -</option>
                        <option value="refund">Refund</option>
                        <option value="barang">Barang Sejenis</option>
                    </select>
                </td>
                <td class="align-top">
                    <input type="number" class="form-control qty" value="1" min="1" max="${maxQty}">
                    <div class="d-flex justify-content-between">
                        <small class="text-danger">Maks: ${maxQty}</small>
                        <small class="text-success">Stok: ${item.qty_now ?? 0}</small>
                    </div>
                    <div class="ket_refund text-muted small mt-1"></div>
                </td>
                <td class="text-right jumlah_refund align-top">Rp 0</td>
                <td class="text-right total_refund align-top">Rp 0</td>
                <td class="text-center align-top">
                    <button type="button" class="btn btn-sm btn-danger btn-delete-row">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
            `;
                tbody.append(row);
            });

            $('#tableReturItems .kompensasi, #tableReturItems .qty').off('change keyup')
                .on('change keyup', function() {
                    const tr = $(this).closest('tr');
                    const harga = parseFloat(tr.data('harga')) || 0;
                    const qtyBeli = parseInt(tr.data('qty')) || 0;
                    const qtyNow = parseInt(tr.data('qty_now')) || 0;
                    const qtyInput = parseInt(tr.find('.qty').val()) || 0;
                    const kompensasi = tr.find('.kompensasi').val();
                    const referensi = $('#referensi').val();

                    let jumlahRefund = 0;
                    let totalRefund = 0;
                    let ket = "";

                    if (kompensasi === 'refund') {
                        jumlahRefund = harga;
                        totalRefund = qtyInput * harga;
                        ket = `${qtyInput} barang direfund.`;
                    } else if (kompensasi === 'barang') {
                        if (qtyNow >= qtyInput) {
                            ket = `${qtyInput} barang diganti sejenis.`;
                        } else {
                            const barangDiganti = qtyNow;
                            const barangRefund = qtyInput - qtyNow;
                            jumlahRefund = harga;
                            totalRefund = barangRefund * harga;
                            ket = `${barangDiganti} barang diganti, ${barangRefund} direfund.`;
                        }
                    }

                    tr.find('.jumlah_refund').text(`Rp ${numberFormat(jumlahRefund)}`);
                    tr.find('.total_refund').text(`Rp ${numberFormat(totalRefund)}`);
                    tr.find('.ket_refund').text(ket);

                    hitungGrandTotal();
                });

            // Event handler hapus row
            $('#tableReturItems .btn-delete-row').off('click').on('click', function() {
                $(this).closest('tr').remove();

                if ($('#tableReturItems tbody tr').length === 0) {
                    $('#tableReturItems tbody').html(`
                <tr class="no-data">
                    <td class="text-center" colspan="8">
                        <div class="text-center my-3">
                            <i class="fa fa-circle-info mr-1"></i> Tidak ada data retur.
                        </div>
                    </td>
                </tr>
            `);
                }
                hitungGrandTotal();
                toggleMemberSelect();
            });

            function hitungGrandTotal() {
                let grandTotal = 0;
                $('#tableReturItems .total_refund').each(function() {
                    const val = $(this).text().replace(/[^\d]/g, '');
                    grandTotal += parseInt(val) || 0;
                });
                $('#total_harga').text(`Rp ${numberFormat(grandTotal)}`);
            }
            toggleMemberSelect();
        }

        function numberFormat(num) {
            return (Number(num) || 0).toLocaleString('id-ID');
        }

        // Sembunyikan select barang dulu
        $('#barang').closest('.row').hide();

        // render select2 untuk member
        await selectData([{
            id: '#member',
            isFilter: {
                toko_id: '{{ auth()->user()->toko_id }}',
            },
            isUrl: '{{ route('master.member') }}',
            placeholder: 'Pilih Member',
            isModal: '#modal-form',
        }]);

        // listen perubahan member
        $('#member').on('change', async function() {
            const memberId = $(this).val();

            if (memberId) {
                // tampilkan barang kalau sudah pilih member
                $('#barang').closest('.row').show();

                // inisialisasi / refresh select2 barang dengan member_id
                await selectData([{
                    id: '#barang',
                    isFilter: {
                        member_id: memberId === 'Guest' ? '0' : memberId,
                    },
                    isUrl: '{{ route('retur.member.getQRCode') }}',
                    placeholder: 'Isi dengan QR Code di menu detail Transaksi Kasir',
                    isModal: '#modal-form',
                    isMinimum: 3,
                }]);

            } else {
                // kalau member dibatalkan → sembunyikan lagi barang
                $('#barang').val(null).trigger('change');
                $('#barang').closest('.row').hide();
            }
        });

        await saveData(mode, encodedData);
    }

    function toggleMemberSelect() {
        const hasItem = $('#tableReturItems tbody tr').not('.no-data').length > 0;
        if (hasItem) {
            $('#member').prop('disabled', true).trigger('change.select2');
        } else {
            $('#member').prop('disabled', false).trigger('change.select2');
        }
    }
</script>
