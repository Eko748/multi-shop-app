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
            '{{ route('td.penjualanNonfisik.get') }}', {
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
        let detail_button = '';
        let print_button = '';
        let delete_button = '';

        if (hasPermission('GET /transaksi-nonfisik/detail')) {
            detail_button = `
                <button onClick="openDetailModal('${encodeURIComponent(JSON.stringify(data))}')" class="action_button btn btn-outline-secondary btn-md" title="Detail data ${data.nota}" data-id="${data?.id}" data-container="body" data-toggle="tooltip" data-placement="top">
                    <span class="text-dark">Detail</span>
                    <div class="icon text-info">
                        <i class="mb-1 fa fa-book"></i>
                    </div>
                </button>
            `;
        }

        if (hasPermission('GET /transaksi-nonfisik/print')) {
            print_button = `
                <button onClick="openPrintPreview('${data.id}')"
                    class="action_button btn btn-outline-secondary btn-md"
                    title="Cetak Nota ${data.nota}"
                    data-id="${data?.id}" data-container="body"
                    data-toggle="tooltip" data-placement="top">
                    <span class="text-dark">Cetak</span>
                    <div class="icon text-success">
                        <i class="mb-1 fa fa-print"></i>
                    </div>
                </button>
            `;
        }

        if (hasPermission('DELETE /transaksi-nonfisik/delete')) {
            delete_button = `
                <button onClick="deleteData('${encodeURIComponent(JSON.stringify(data))}')" class="action_button btn btn-outline-secondary btn-md" title="Hapus data ${data.nota}" data-id="${data?.id}" data-container="body" data-toggle="tooltip" data-placement="top">
                    <span class="text-dark">Hapus</span>
                    <div class="icon text-danger">
                        <i class="mb-1 fa fa-trash-alt"></i>
                    </div>
                </button>
            `;
        }

        let infoText = 'Dibuat oleh:';
        let infoUser = `${data.created_by || '-'}`;
        let infoTime = `${data.created_at || '-'}`;
        if (data.updated_by != null) {
            infoText = 'Diperbarui oleh:';
            infoUser = `${data.updated_by || '-'}`;
            infoTime = `${data.updated_at || '-'}`;
        }

        const info = `
            <div>
                <small class="text-muted">${infoText}</small>
                <small class="text-bold">${infoUser}</small>
            </div>`;

        return {
            id: data?.id ?? '-',
            nota: data?.nota ?? '-',
            dompul: data?.dompul ?? '-',
            format_total_hpp: data?.format_total_hpp ?? '-',
            format_total_harga_jual: data?.format_total_harga_jual ?? '-',
            format_total_bayar: data?.format_total_bayar ?? '-',
            created_at: infoTime ?? '-',
            detail_button,
            delete_button,
            print_button,
            info
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
                                    <th scope="col" class="${tdClass}" style="width:10%">Informasi</th>
                                    <th scope="col" class="${tdClass}" style="width:15%">Nota</th>
                                    <th scope="col" class="${tdClass}" style="width:15%">Dompet Pulsa</th>
                                    <th scope="col" class="${tdClass} text-right" style="width:15%">Total HPP</th>
                                    <th scope="col" class="${tdClass} text-right" style="width:15%">Total Harga Jual</th>
                                    <th scope="col" class="${tdClass} text-center" style="width:10%">Aksi</th>
                                </tr>
                            </thead>
                            <thead>
                                <tr>
                                    <th colspan="6" class="${tdClass} text-right"><span class="badge badge-primary">${total.hpp.format || 0}</span></th>
                                    <th class="${tdClass} text-right"><span class="badge badge-primary">${total.harga_jual.format || 0}</span></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>`;

        dataList.forEach((element, index) => {
            const number = display_from + index;
            const hasButtons = element.detail_button || element.print_button || element.delete_button;
            const actionHTML = `
                <div class="d-flex justify-content-center flex-column flex-sm-row align-items-center align-items-sm-start mx-3" style="gap: 0.5rem;">
                    ${hasButtons
                        ? `
                            ${element.detail_button || ''}
                            ${element.print_button || ''}
                            ${element.delete_button || ''}
                        `
                        : `<i class="text-muted">Tidak ada aksi</span>`
                    }
                </div>
            `;

            getDataTable += `
                <tr class="glossy-tr">
                    <td class="${tdClass} text-center">${number}</td>
                    <td class="${tdClass}">${element.created_at}</td>
                    <td class="${tdClass}">${element.info}</td>
                    <td class="${tdClass}">${element.nota}</td>
                    <td class="${tdClass}">${element.dompul}</td>
                    <td class="${tdClass} text-right">${element.format_total_hpp}</td>
                    <td class="${tdClass} text-right">${element.format_total_harga_jual}</td>
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

    async function saveData(mode, encodedData) {
        $(document).off("click", "#save-btn").on("click", "#save-btn", async function(e) {
            e.preventDefault();

            let method = 'POST';
            let url = `{{ route('td.penjualanNonfisik.post') }}`;

            const btn = $(this);
            const saveButton = this;

            // ambil data item dari table (lebih lengkap: id, qty, hpp, harga_jual)
            let selectedItems = [];
            $('#tableItems tbody tr[data-id]').each(function() {
                const $tr = $(this);
                const qty = parseInt($tr.find('.qty-input').val(), 10) || 1;
                const hpp = parseFloat($tr.find('.hpp-input').val());
                const harga_jual = parseFloat($tr.find('.price-input').val());

                selectedItems.push({
                    id: $tr.data('id'),
                    qty: qty,
                    hpp: isNaN(hpp) ? 0 : hpp,
                    harga_jual: isNaN(harga_jual) ? 0 : harga_jual
                });
            });

            if (selectedItems.length === 0) {
                notificationAlert('warning', 'Peringatan', 'Tidak ada item yang dipilih.');
                return;
            }

            // hitung totals dari selectedItems (tidak bergantung ke fungsi luar)
            const total_harga = selectedItems.reduce((sum, it) => {
                return sum + (Number(it.harga_jual) || 0) * (Number(it.qty) || 1);
            }, 0);
            const total_hpp = selectedItems.reduce((sum, it) => {
                return sum + (Number(it.hpp) || 0) * (Number(it.qty) || 1);
            }, 0);

            // ambil data dompet_pulsa dari select2 (pastikan extra field 'saldo' tersedia)
            const dompetPulsaData = $('#dompet_pulsa').select2('data')[0] || {};
            const dompet_kategori_id = dompetPulsaData.id || null;
            const saldo = dompetPulsaData.saldo ?? null;
            const kas = dompetPulsaData.kas ?? null;

            let formData = {
                total_harga_jual: total_harga,
                total_hpp: total_hpp,
                total_bayar: parseFloat($('#total_bayar').val()) || total_harga,
                dompet_kategori_id: dompet_kategori_id,
                saldo: saldo,
                items: selectedItems,
                created_by: {{ auth()->user()->id }},
                toko_id: {{ auth()->user()->toko_id }},
                kas_id: kas,
            };

            if (saveButton.disabled) return;

            swal({
                title: "Konfirmasi",
                text: `Apakah Anda yakin ingin menyimpan data ${title} ini?`,
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
                            ])
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

                    const hppError = error?.response?.data?.errors?.hpp?.[0] || null;
                    const message = error?.response?.data?.message ||
                        'Terjadi kesalahan saat menyimpan data.';

                    notificationAlert(
                        'error',
                        hppError ? 'Isi ulang Saldo Digital' : 'Kesalahan',
                        message
                    );

                    saveButton.disabled = false;
                    btn.html(originalContent);
                }
            });
        });
    }

    async function deleteData(encodedData) {
        let data = JSON.parse(decodeURIComponent(encodedData));

        swal({
            title: `Hapus Data ${title} ${data?.nota}`,
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
                `{{ route('td.penjualanNonfisik.delete') }}`, {
                    public_id: data.id,
                    deleted_by: '{{ auth()->user()->id }}'
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
                    getCountData();
                    getListData2(defaultLimitPage2, currentPage2, defaultAscending2,
                        defaultSearch2, customFilter2);
                }, 500);
                notificationAlert('success', 'Pemberitahuan', postDataRest.data
                    .message);
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

        fetchConfigMap.getListData3 = {
            fn: getListData3,
            getConfig: () => ({
                limit: defaultLimitPage3,
                page: currentPage3,
                asc: defaultAscending3,
                search: defaultSearch3,
                filter: customFilter3,
                id: data.id
            }),
            setPage: (val) => currentPage3 = val,
            getTotalPage: () => totalPage3,
            setSearch: (val) => defaultSearch3 = val,
            setLimit: (val) => {
                defaultLimitPage3 = val;
            }
        };

        await Promise.all([
            getListData3(
                defaultLimitPage3,
                currentPage3,
                defaultAscending3,
                defaultSearch3,
                customFilter3 = {
                    id: data.id
                },
            ),
            searchList('getListData3', '#limitPage3', '.tb-search3'),
        ]);
    }

    async function openPrintPreview(id) {
        try {
            const res = await renderAPI(
                'GET',
                '{{ route('td.penjualanNonfisik.getDetail') }}', {
                    page: currentPage3,
                    limit: 300,
                    ascending: defaultAscending3,
                    search: defaultSearch3,
                    id: id
                }
            );

            if (!res || !res.data.data || !res.data.data.item) {
                notificationAlert('warning', 'Info', 'Data tidak ditemukan untuk dicetak.');
                return;
            }

            const {
                item,
                detail
            } = res.data.data;

            let detailRows = detail.map(d => `
                <tr>
                    <td colspan="4" class="align-top text-left">${d.item}</td>
                </tr>
                <tr>
                    <td colspan="2" class="align-top text-left">${d.qty} x ${d.harga_jual}</td>
                    <td colspan="2" class="align-top text-right">${d.format_total_harga_jual}</td>
                </tr>
            `).join("");

            const hr = '<hr style="border:none; border-top:1px dashed #000; margin:6px 0;"/>';
            const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="black" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M2 1C1.44772 1 1 1.44772 1 2C1 2.55228 1.44772 3 2 3H3.21922L6.78345 17.2569C5.73276 17.7236 5 18.7762 5 20C5 21.6569 6.34315 23 8 23C9.65685 23 11 21.6569 11 20C11 19.6494 10.9398 19.3128 10.8293 19H15.1707C15.0602 19.3128 15 19.6494 15 20C15 21.6569 16.3431 23 18 23C19.6569 23 21 21.6569 21 20C21 18.3431 19.6569 17 18 17H8.78078L8.28078 15H18C20.0642 15 21.3019 13.6959 21.9887 12.2559C22.6599 10.8487 22.8935 9.16692 22.975 7.94368C23.0884 6.24014 21.6803 5 20.1211 5H5.78078L5.15951 2.51493C4.93692 1.62459 4.13696 1 3.21922 1H2ZM18 13H7.78078L6.28078 7H20.1211C20.6742 7 21.0063 7.40675 20.9794 7.81078C20.9034 8.9522 20.6906 10.3318 20.1836 11.3949C19.6922 12.4251 19.0201 13 18 13ZM18 20.9938C17.4511 20.9938 17.0062 20.5489 17.0062 20C17.0062 19.4511 17.4511 19.0062 18 19.0062C18.5489 19.0062 18.9938 19.4511 18.9938 20C18.9938 20.5489 18.5489 20.9938 18 20.9938ZM7.00617 20C7.00617 20.5489 7.45112 20.9938 8 20.9938C8.54888 20.9938 8.99383 20.5489 8.99383 20C8.99383 19.4511 8.54888 19.0062 8 19.0062C7.45112 19.0062 7.00617 19.4511 7.00617 20Z"/>
                        </svg>`;

            const printContent = `
                <div style="font-family: monospace; width: 300px; position: relative;">
                    <div style="display:flex; align-items:center; justify-content:center; margin-bottom:6px; position: relative; z-index: 2;">
                        <div style="flex:0 0 auto; display:flex; align-items:center;">
                            ${svg}
                        </div>
                        <div style="flex:1; text-align:center;">
                            <div style="font-size:16px; font-weight:bold;">${item.nama_toko}</div>
                            <div style="font-size:12px; margin-top:2px;">${item.alamat_toko}</div>
                        </div>
                        <div style="flex:0 0 auto; display:flex; align-items:center;">
                            ${svg}
                        </div>
                    </div>
                    ${hr}
                    <table style="width:100%; font-size:12px; table-layout:fixed; position: relative; z-index: 2;">
                        <colgroup>
                            <col style="width:25%;">
                            <col style="width:5%;">
                            <col style="width:70%;">
                        </colgroup>
                        <tbody>
                            <tr>
                                <td>No Nota</td>
                                <td>:</td>
                                <td class="text-right">${item.nota}</td>
                            </tr>
                            <tr>
                                <td>Tanggal</td>
                                <td>:</td>
                                <td class="text-right">${item.created_at}</td>
                            </tr>
                            <tr>
                                <td>Kasir</td>
                                <td>:</td>
                                <td class="text-right">${item.created_by}</td>
                            </tr>
                        </tbody>
                    </table>
                    ${hr}
                    <table style="width:100%; font-size:14px; position: relative; z-index: 2;">
                        <tbody>
                            ${detailRows}
                        </tbody>
                    </table>
                    ${hr}
                    <table style="width:100%; font-size:14px; table-layout:fixed; position: relative; z-index: 2;">
                        <colgroup>
                            <col style="width:25%;">
                            <col style="width:5%;">
                            <col style="width:70%;">
                        </colgroup>
                        <tbody>
                            <tr>
                                <td>Total</td>
                                <td>:</td>
                                <td class="text-right">${item.total_harga_jual}</td>
                            </tr>
                            <tr>
                                <td style="font-weight:bold;">Tunai</td>
                                <td>:</td>
                                <td class="text-right" style="font-weight:bold;">${item.total_bayar}</td>
                            </tr>
                            <tr>
                                <td>Kembali</td>
                                <td>:</td>
                                <td class="text-right">${item.total_kembalian}</td>
                            </tr>
                        </tbody>
                    </table>
                    ${hr}
                    <p style="text-align:center; position: relative; z-index: 2;">Terima Kasih</p>
                </div>
            `;


            let w = window.open("", "_blank", "width=400,height=600");
            w.document.write(`
                <html>
                <head>
                    <title>Print Nota</title>
                    <style>
                        body { font-family: monospace; padding:10px; }
                        table { border-collapse: collapse; width:100%; }
                        td, th { padding: 4px; }
                        .text-right { text-align:right; }
                    </style>
                </head>
                <body onload="window.print(); window.close();">
                    ${printContent}
                </body>
                </html>
            `);
            w.document.close();

        } catch (error) {
            console.error("Error print preview:", error);
        }
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
            `<i class="fa fa-edit mr-1"></i>Edit ${title}` :
            `<i class="fa fa-circle-plus mr-1"></i>Form ${title}`;

        $('#modalLabel').html(modalTitle);

        const tdClass = 'text-wrap align-top';
        const formContent = `
        <form id="form-data">
            <style>
                #form-data .select2-container{width:100% !important; max-width:100%;}
                #form-data .select2-selection{min-height:35px;}
                #form-data .select2-selection__rendered{line-height:33px;}
                #form-data .select2-selection__arrow{height:33px;}
                #btnAddItem{white-space:nowrap; height:35px; line-height:1;}
                .table-responsive{overflow-x:auto;}
                #tableItems{min-width: 900px;}
                @media (max-width: 576px){
                    #tableItems{min-width: 800px;}
                }
            </style>

            <div class="form-group">
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="item_nonfisik"><i class="fa fa-layer-group mr-1"></i>Item</label>
                            <select class="form-control select2 flex-grow-1" id="item_nonfisik" name="item_nonfisik" required></select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="dompet_pulsa"><i class="fa fa-layer-group mr-1"></i>Dompet Pulsa</label>
                            <select class="form-control select2 flex-grow-1" id="dompet_pulsa" name="dompet_pulsa" required></select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover m-0" id="tableItems">
                            <thead class="glossy-thead">
                                <tr>
                                    <th class="${tdClass} text-center" style="width:5%">Aksi</th>
                                    <th class="${tdClass} text-center" style="width:5%">No</th>
                                    <th class="${tdClass}" style="width:25%">Item</th>
                                    <th class="${tdClass}" style="width:10%">Qty</th>
                                    <th class="${tdClass}" style="width:10%">Hpp</th>
                                    <th class="${tdClass}" style="width:10%">Harga</th>
                                    <th class="${tdClass} text-right" style="width:10%">Total Hpp</th>
                                    <th class="${tdClass} text-right" style="width:15%">Total Harga</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="${tdClass} text-center" colspan="8">
                                        <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
                                            <div class="text-center my-3" role="alert">
                                                <i class="fa fa-circle-info mr-1"></i>Silahkan Tambahkan Item Terlebih Dahulu.
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6" class="text-right font-weight-bold">SubTotal:</td>
                                    <td id="total_hpp" class="text-right font-weight-bold">Rp 0</td>
                                    <td id="total_harga" class="text-right font-weight-bold">Rp 0</td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="text-right font-weight-bold">Total Bayar:</td>
                                    <td colspan="2">
                                        <input type="number" id="total_bayar" class="form-control" inputmode="numeric" value="0" placeholder="Masukkan nominal">
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-loeft text-muted"><sup class="text-danger mr-1">**</sup>Pastikan kembali data yang akan disimpan dengan benar.</td>
                                    <td colspan="1" class="text-right font-weight-bold">Kembalian:</td>
                                    <td colspan="2" id="kembalian" class="text-right font-weight-bold">Rp 0</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </form>`;

        $('#modal-data').html(formContent);

        await selectData(selectOptions);

        let itemList = [];
        let lastGrandTotal = 0;
        let totalBayarDebounceTimer = null;

        $('#item_nonfisik').off('select2:select').on('select2:select', function(e) {
            const selectedData = e.params.data;
            if (!selectedData || !selectedData.id) {
                notificationAlert('warning', 'Peringatan', 'Pilih item terlebih dahulu.');
                return;
            }

            const selectedId = selectedData.id;
            const selectedText = selectedData.text;

            const existingItem = itemList.find(item => item.id === selectedId);
            if (existingItem) {
                existingItem.qty += 1;
            } else {
                itemList.push({
                    id: selectedId,
                    name: selectedText,
                    qty: 1,
                    hpp: null,
                    price: null
                });
            }

            renderTable();

            $(this).val(null).trigger('change');
        });

        function renderTable() {
            const tbody = $('#tableItems tbody');
            tbody.empty();

            if (itemList.length === 0) {
                tbody.append(`
                    <tr>
                        <td class="${tdClass} text-center" colspan="8">
                            <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
                                <div class="text-center my-3" role="alert">
                                    <i class="fa fa-circle-info mr-1"></i>Silahkan Tambahkan Item Terlebih Dahulu.
                                </div>
                            </div>
                        </td>
                    </tr>
                `);
            } else {
                itemList.forEach((item, index) => {
                    const totalHppItem = item.hpp * item.qty;
                    const totalHargaItem = item.price * item.qty;
                    const row = `
                        <tr class="glossy-tr" data-id="${item.id}">
                            <td class="${tdClass} text-center">
                                <button type="button" class="btn btn-outline-danger btn-sm btn-delete-item" title="Hapus">
                                    <i class="fa fa-trash-alt"></i>
                                </button>
                            </td>
                            <td class="${tdClass} text-center">${index + 1}</td>
                            <td class="${tdClass}">${item.name}</td>
                            <td class="${tdClass}">
                                <input type="number" class="form-control qty-input" value="${item.qty}" min="1" placeholder="Qty">
                            </td>
                            <td class="${tdClass}">
                                <input type="number" class="form-control hpp-input" value="${item.hpp}" min="0" placeholder="HPP">
                            </td>
                            <td class="${tdClass}">
                                <input type="number" class="form-control price-input" value="${item.price}" min="0" placeholder="Harga">
                            </td>
                            <td class="${tdClass} td-total-hpp text-right font-weight-bold">Rp ${numberFormat(totalHppItem)}</td>
                            <td class="${tdClass} td-total-harga text-right font-weight-bold">Rp ${numberFormat(totalHargaItem)}</td>
                        </tr>
                    `;
                    tbody.append(row);
                });
            }

            updateTotals();
        }

        $('#tableItems').off('input change', '.qty-input, .hpp-input, .price-input')
            .on('input change', '.qty-input, .hpp-input, .price-input', function() {
                const tr = $(this).closest('tr');
                const id = String(tr.data('id'));
                let item = itemList.find(i => String(i.id) === id);

                if (item) {
                    const qtyVal = parseInt(tr.find('.qty-input').val(), 10);
                    const hppVal = parseFloat(tr.find('.hpp-input').val());
                    const priceVal = parseFloat(tr.find('.price-input').val());

                    item.qty = isNaN(qtyVal) || qtyVal < 1 ? 1 : qtyVal;
                    item.hpp = isNaN(hppVal) || hppVal < 0 ? 0 : hppVal;
                    item.price = isNaN(priceVal) || priceVal < 0 ? 0 : priceVal;

                    tr.find('.td-total-hpp').text(`Rp ${numberFormat(item.qty * item.hpp)}`);
                    tr.find('.td-total-harga').text(`Rp ${numberFormat(item.qty * item.price)}`);

                    updateTotals();
                }
            });

        $('#tableItems').off('click', '.btn-delete-item').on('click', '.btn-delete-item', function() {
            const tr = $(this).closest('tr');
            const id = String(tr.data('id'));
            itemList = itemList.filter(i => String(i.id) !== id);
            renderTable();
        });

        function getTotalHargaSemua() {
            return itemList.reduce((sum, item) => sum + (item.price * item.qty), 0);
        }

        function getTotalHppSemua() {
            return itemList.reduce((sum, item) => sum + (item.hpp * item.qty), 0);
        }

        function updateTotals() {
            const newGrandTotal = getTotalHargaSemua();
            const newGrandHpp = getTotalHppSemua();

            $('#total_harga').text(`Rp ${numberFormat(newGrandTotal)}`);
            $('#total_hpp').text(`Rp ${numberFormat(newGrandHpp)}`);

            let bayarSekarang = parseFloat($('#total_bayar').val()) || 0;

            if (bayarSekarang !== newGrandTotal) {
                bayarSekarang = newGrandTotal;
                $('#total_bayar').val(bayarSekarang);
            }

            lastGrandTotal = newGrandTotal;
            updateKembalian();
        }

        $('#total_bayar').on('input', function() {
            clearTimeout(totalBayarDebounceTimer);

            let bayar = parseFloat($('#total_bayar').val()) || 0;
            const total = getTotalHargaSemua();

            updateKembalian();

            // totalBayarDebounceTimer = setTimeout(() => {
            //     if (bayar < total) {
            //         $('#total_bayar').val(total);
            //         updateKembalian();
            //     }
            // }, 3000);
        });

        function updateKembalian() {
            const bayar = parseFloat($('#total_bayar').val()) || 0;
            const total = getTotalHargaSemua();
            const kembalian = bayar - total;

            $('#kembalian').text(`Rp ${numberFormat(kembalian > 0 ? kembalian : 0)}`);
        }

        function numberFormat(num) {
            return (Number(num) || 0).toLocaleString('id-ID');
        }

        await saveData(mode, encodedData);
    }
</script>
