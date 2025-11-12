<script>
    async function getListData3(limit = 10, page = 1, ascending = 0, search = '', customFilter = {}) {
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

        $('#detailData3, #listData3').html(loadingCard);

        let filterParams = {
            ...customFilter
        };

        let getDataRest = await renderAPI(
            'GET',
            '{{ route('td.penjualanNonfisik.getDetail') }}', {
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
            const dataArray = getDataRest.data.data.detail || [];

            if (Array.isArray(dataArray) && dataArray.length > 0) {
                let handleDataArray = await Promise.all(
                    dataArray.map(item => handleData3(item))
                );
                await setDetailData3(getDataRest.data.data.item);
                await setListData3(handleDataArray, getDataRest.data.pagination);
            } else {
                const emptyMessage = `
                <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
                    <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
                        <div class="text-center my-3" role="alert">
                            Data tidak tersedia untuk ditampilkan.
                        </div>
                    </div>
                </div>`;
                $('#listData3, #detailData3').html(emptyMessage);
                $('#countPage3').text("0 - 0");
                $('#totalPage3').text("0");
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
            $('#listData3, #detailData3').html(errorAlert);
            $('#countPage3').text("0 - 0");
            $('#totalPage3').text("0");
        }
    }

    async function setDetailData3(data) {
        let itemContent = `
            <div class="col-12">
                    <div class="row">
                        <div class="col-12 col-sm-12 col-md-12 col-lg-8 col-xl-8">
                            <table class="table table-borderless table-sm mb-2">
                                <tbody>
                                    <tr>
                                        <td class="align-middle">
                                            <i class="fa fa-box-open text-primary mr-2"></i> <strong>Total HPP</strong>
                                        </td>
                                        <td class="align-middle">: ${data.total_hpp}</td>
                                    </tr>
                                    <tr>
                                        <td class="align-middle" style="width: 20%;">
                                            <i class="fa fa-tags text-primary mr-2"></i> <strong>Total Harga Jual</strong>
                                        </td>
                                        <td class="align-middle">: ${data.total_harga_jual}</td>
                                    </tr>
                                    <tr>
                                        <td class="align-middle">
                                            <i class="fa fa-money-bill-wave text-primary mr-2"></i> <strong>Total Bayar</strong>
                                        </td>
                                        <td class="align-middle">: ${data.total_bayar}</td>
                                    </tr>
                                    <tr>
                                        <td class="align-middle">
                                            <i class="fa fa-hand-holding-usd text-primary mr-2"></i> <strong>Total Kembalian</strong>
                                        </td>
                                        <td class="align-middle">: ${data.total_kembalian}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-12 col-sm-12 col-md-12 col-lg-4 col-xl-4">
                            <div class="text-center text-sm-right small text-muted ml-auto pb-2 p-sm-2">
                                <div><strong>${data.created_by}</strong></div>
                                <div>${data.created_at}</div>
                            </div>
                        </div>
                    </div>
            </div>
    `;

        $('#detail-nota').html(`
        <strong>Nota:</strong> ${data.nota}
    `);
        $('#detailData3').html(itemContent);
    }

    async function handleData3(data) {
        return {
            id: data?.id ?? '-',
            item: data?.item ?? '-',
            tipe: data?.tipe ?? '-',
            kategori: data?.kategori ?? '-',
            format_hpp: data?.format_hpp ?? '-',
            format_harga_jual: data?.format_harga_jual ?? '-',
            qty: data?.qty ?? '-',
        };
    }

    async function setListData3(dataList, pagination) {
        totalPage3 = pagination.total_pages;
        currentPage3 = pagination.current_page;
        let display_from = ((defaultLimitPage3 * (currentPage3 - 1)) + 1);
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
                                    <th scope="col" class="${tdClass}" style="width:35%">Item</th>
                                    <th scope="col" class="${tdClass}" style="width:20%">Tipe</th>
                                    <th scope="col" class="${tdClass} text-right" style="width:10%">Qty</th>
                                    <th scope="col" class="${tdClass} text-right" style="width:15%">HPP</th>
                                    <th scope="col" class="${tdClass} text-right" style="width:15%">Harga Jual</th>
                                </tr>
                            </thead>
                            <tbody>`;

        dataList.forEach((element, index) => {
            const number = display_from + index;
            getDataTable += `
                <tr class="glossy-tr">
                    <td class="${tdClass} text-center">${number}</td>
                    <td class="${tdClass}">${element.item}</td>
                    <td class="${tdClass}">${element.tipe}</td>
                    <td class="${tdClass} text-right">${element.qty}</td>
                    <td class="${tdClass} text-right">${element.format_hpp}</td>
                    <td class="${tdClass} text-right">${element.format_harga_jual}</td>
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

        $('#listData3').html(getDataTable);
        $('#totalPage3').text(pagination.total);
        $('#countPage3').text(`${display_from} - ${display_to}`);
        $('[data-toggle="tooltip"]').tooltip();
        renderPagination('getListData3', '#pagination-js3', totalPage3);
    }

    async function renderModalDetail() {
        const modalTitle = `<i class="fa fa-book mr-1"></i>Detail ${title}`;
        $('#modalLabel').html(modalTitle);

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
                                <div class="row overflow-auto mb-0" id="detailData3" style="max-height: 50vh;"></div>
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
                                <div class="row overflow-auto" id="listData3" style="max-height: 50vh;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
                            <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
                                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center p-3">
                                    <div class="text-center text-md-start mb-2 mb-md-0">
                                        <div class="pagination">
                                            <div>Menampilkan <span id="countPage3">0</span> dari <span
                                                    id="totalPage3">0</span> data</div>
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
