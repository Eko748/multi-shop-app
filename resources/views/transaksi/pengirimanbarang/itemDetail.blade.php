<script>
    async function getListData2(limit = 10, page = 1, ascending = 0, search = '', customFilter = {}) {
        const loadingCard = `
            <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
                <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
                    <div class="d-flex justify-content-center align-items-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('#listData2').html(loadingCard);

        let filterParams = {
            ...customFilter
        };

        let getDataRest = await renderAPI(
            'GET',
            '{{ route('distribusi.pengiriman.detail') }}', {
                page: page,
                limit: limit,
                ascending: ascending,
                search: search,
                toko_id: {{ auth()->user()->id }},
                ...filterParams
            }
        ).then(function(response) {
            return response;
        }).catch(function(error) {
            return error.response;
        });

        if (getDataRest && getDataRest.status === 200) {
            const dataArray = getDataRest.data.data.item || [];

            if (Array.isArray(dataArray) && dataArray.length > 0) {
                let handleDataArray = await Promise.all(
                    dataArray.map(item => handleData2(item))
                );
                await setListData2(handleDataArray, getDataRest.data.pagination);
                $('#hargaBeli').text(formatRupiah(getDataRest.data.data.total.total_send))
            } else {
                const emptyMessage = `
                <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
                    <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
                        <div class="text-center my-3" role="alert">
                            Data tidak tersedia untuk ditampilkan.
                        </div>
                    </div>
                </div>`;
                $('#listData2, #detailData2').html(emptyMessage);
                $('#countPage2').text("0 - 0");
                $('#totalPage2').text("0");
            }
        } else {
            let errorMessage = getDataRest?.data?.message || 'Data gagal dimuat';
            const errorAlert = `
                <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
                    <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
                        <div class="alert alert-warning text-center mt-4" role="alert">
                            ${errorMessage}
                        </div>
                    </div>
                </div>`;
            $('#listData2, #detailData2').html(errorAlert);
            $('#countPage2').text("0 - 0");
            $('#totalPage2').text("0");
        }
    }

    async function setDetailData2(encodedData) {
        let data = JSON.parse(decodeURIComponent(encodedData));

        let status = '';
        if (data?.status === 'Sukses') {
            status =
                `<span class="badge badge-success custom-badge"><i class="mx-1 fa fa-circle-check"></i>Sukses</span>`;
        } else if (data?.status === 'Pending') {
            status =
                `<span class="badge badge-info custom-badge"><i class="mx-1 fa fa-circle-half-stroke "></i>Pending</span>`;
        } else if (data?.status === 'Progress') {
            status =
                `<span class="badge badge-warning custom-badge"><i class="mx-1 fa fa-spinner fa-spin"></i>Progress</span>`;
        } else if (data?.status === 'Gagal') {
            status =
                `<span class="badge badge-danger custom-badge"><i class="mx-1 fa fa-circle-xmark"></i>Gagal</span>`;
        } else {
            status = `<span class="badge badge-secondary custom-badge">Tidak Diketahui</span>`;
        }

        let itemContent = `
            <div class="col-12">
                    <div class="row">
                        <div class="col-12 col-sm-12 col-md-12 col-lg-8 col-xl-8">
                            <table class="table table-borderless table-sm mb-2">
                                <tbody>
                                    <tr>
                                        <td class="align-middle">
                                            <i class="fa fa-tag text-primary mr-2"></i> <strong>Status</strong>
                                        </td>
                                        <td class="align-middle">: ${status}</td>
                                    </tr>
                                    <tr>
                                        <td class="align-middle" style="width: 20%;">
                                            <i class="fa fa-map-marker text-primary mr-2"></i> <strong>Asal</strong>
                                        </td>
                                        <td class="align-middle">: ${data.toko_asal}</td>
                                    </tr>
                                    <tr>
                                        <td class="align-middle">
                                            <i class="fa fa-calendar-day text-primary mr-2"></i> <strong>Tanggal Kirim</strong>
                                        </td>
                                        <td class="align-middle">: ${data.tgl_kirim}</td>
                                    </tr>
                                    <tr>
                                        <td class="align-middle" style="width: 20%;">
                                            <i class="fa fa-map-marker text-primary mr-2"></i> <strong>Tujuan</strong>
                                        </td>
                                        <td class="align-middle">: ${data.toko_tujuan}</td>
                                    </tr>
                                    <tr>
                                        <td class="align-middle">
                                            <i class="fa fa-calendar-day text-primary mr-2"></i> <strong>Tanggal Terima</strong>
                                        </td>
                                        <td class="align-middle">: ${data.tgl_terima ?? '<span class="badge badge-secondary"><i>Belum diterima</i></span>'}</td>
                                    </tr>
                                    <tr>
                                        <td class="align-middle" style="width: 20%;">
                                            <i class="fa fa-money-bill-wave text-primary mr-2"></i> <strong>Total Harga</strong>
                                        </td>
                                        <td class="align-middle">: <span id="hargaBeli"></span></td>
                                    </tr>
                                    <tr>
                                        <td class="align-middle">
                                            <i class="fa fa-box text-primary mr-2"></i> <strong>Total Qty Kirim</strong>
                                        </td>
                                        <td class="align-middle">: ${data.total_item}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-12 col-sm-12 col-md-12 col-lg-4 col-xl-4">
                            <div class="text-center text-sm-right small text-muted ml-auto pb-2 p-sm-2">
                                <div>Pengirim: <strong>${data.nama_pengirim}</strong></div>
                                <div>Ekspedisi: <strong>${data.ekspedisi || '-'}</strong></div>
                            </div>
                        </div>
                    </div>
            </div>
    `;

        $('#detail-nota').html(`
        <strong>No Resi:</strong> ${data.no_resi}
    `);
        $('#detailData2').html(itemContent);
    }

    async function handleData2(data) {
        return {
            id: data?.id ?? '-',
            barang: data?.barang ?? '-',
            text: data?.text ?? '-',
            qty_send: data?.qty_send ?? '-',
            harga_beli: data?.harga_beli ?? '-',
            qty_verified: data?.qty_verified ?? '-',
        };
    }

    async function setListData2(dataList, pagination) {
        totalPage2 = pagination.total_pages;
        currentPage2 = pagination.current_page;
        let display_from = ((defaultLimitPage2 * (currentPage2 - 1)) + 1);
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
                                    <th scope="col" class="${tdClass}" style="width:35%">Barang</th>
                                    <th scope="col" class="${tdClass} text-center" style="width:20%">Qty Kirim</th>
                                    <th scope="col" class="${tdClass} text-right" style="width:20%">Harga</th>
                                    <th scope="col" class="${tdClass} text-center" style="width:20%">Qty Terima</th>
                                </tr>
                            </thead>
                            <tbody>`;

        dataList.forEach((element, index) => {
            const number = display_from + index;
            getDataTable += `
                <tr class="glossy-tr">
                    <td class="${tdClass} text-center">${number}</td>
                    <td class="${tdClass}">${element.text}</td>
                    <td class="${tdClass} text-center">${element.qty_send}</td>
                    <td class="${tdClass} text-right">${element.harga_beli}</td>
                    <td class="${tdClass} text-center">${element.qty_verified}</td>
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

        $('#listData2').html(getDataTable);
        $('#totalPage2').text(pagination.total);
        $('#countPage2').text(`${display_from} - ${display_to}`);
        $('[data-toggle="tooltip"]').tooltip();
        renderPagination('getListData2', '#pagination-js3', totalPage2);
    }

    async function renderModalDetail() {
        const modalTitle = `<i class="fa fa-folder-open mr-1"></i>Detail ${title}`;
        $('#modalDetail').html(modalTitle);

        const htmlContent = `
            <div class="row">
                <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12 mb-2">
                    <div class="row">
                        <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
                            <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
                                <div class="d-flex flex-row justify-content-between align-items-center p-3 flex-wrap">
                                    <h5 class="m-0">Data Item <span id="detail-nota"></span></h5>
                                </div>
                                <hr class="m-0">
                            </div>
                        </div>
                    </div>
                    <div class="row mb-0">
                        <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
                            <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
                                <div class="row overflow-auto mb-0" id="detailData2" style="max-height: 50vh;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12 mb-2">
                    <div class="row">
                        <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
                            <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
                                <div class="d-flex flex-row justify-content-between align-items-center p-3 flex-wrap">
                                    <h5 class="m-0">Detail Item</h5>
                                </div>
                                <hr class="m-0">
                                <div class="d-flex flex-row justify-content-between align-items-center p-3 flex-wrap"
                                    style="gap: 0.5rem;">
                                    <select name="limitPage" id="limitPage3" class="form-control"
                                        style="flex: 1 1 80px; max-width: 80px;">
                                        <option value="10">10</option>
                                        <option value="20">20</option>
                                        <option value="30">30</option>
                                    </select>
                                    <input class="tb-search3 form-control ms-auto" type="search" name="search"
                                        placeholder="Cari Data" aria-label="search"
                                        style="flex: 1 1 100px; max-width: 200px;">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
                            <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
                                <div class="row overflow-auto" id="listData2" style="max-height: 50vh;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
                            <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
                                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center p-3">
                                    <div class="text-center text-md-start mb-2 mb-md-0">
                                        <div class="pagination">
                                            <div>Menampilkan <span id="countPage2">0</span> dari <span
                                                    id="totalPage2">0</span> data</div>
                                        </div>
                                    </div>
                                    <nav class="text-center text-md-end">
                                        <ul class="pagination justify-content-center justify-content-md-end"
                                            id="pagination-js3">
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;

        $('#modal-data').html(htmlContent);
    }
</script>
