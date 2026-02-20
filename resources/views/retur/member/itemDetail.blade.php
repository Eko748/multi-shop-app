<script>
    let expectedQRCodes = [];

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

        $('#detailData2, #listData2').html(loadingCard);

        let filterParams = {
            ...customFilter
        };

        let getDataRest = await renderAPI(
            'GET',
            '{{ route('retur.member.getDetail') }}', {
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
                    dataArray.map(item => handleData2(item))
                );
                await setDetailData2(getDataRest.data.data.item);
                await setListData2(handleDataArray, getDataRest.data.pagination);
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

    async function setDetailData2(data) {

        let itemContent = `
        <div class="col-12">
            <div class="row">
                <div class="col-lg-8">
                    <table class="table table-borderless table-sm mb-2">
                        <tbody>
                            <tr>
                                <td><strong>Member</strong></td>
                                <td>: ${data.member}</td>
                            </tr>
                            <tr>
                                <td><strong>Toko</strong></td>
                                <td>: ${data.nama_toko}</td>
                            </tr>
                            <tr>
                                <td><strong>Status</strong></td>
                                <td>: ${data.status}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="col-lg-4 text-right small text-muted">
                    <div><strong>${data.created_by}</strong></div>
                    <div>${data.tanggal}</div>

                    <div class="mt-2">
                        <input
                            type="text"
                            id="scanQrcode"
                            class="form-control form-control-sm text-center"
                            placeholder="Cek Barang dengan Scan QR Code..."
                            autocomplete="off"
                        >
                    </div>
                </div>
            </div>
        </div>
    `;

        $('#detailData2').html(itemContent);

        initScannerInput();
    }

    function initScannerInput() {
        const $input = $('#scanQrcode');

        $input.focus();

        $input.on('keydown', function(e) {
            if (e.key === 'Enter') {

                e.preventDefault();
                e.stopPropagation();

                let scannedCode = $(this).val().trim();

                if (scannedCode !== '') {

                    let found = expectedQRCodes.find(item => item.qrcode === scannedCode);

                    if (found) {
                        notificationAlert(
                            'success',
                            'Sesuai',
                            `QR Code sesuai dengan barang: ${found.barang}`,
                        );
                    } else {
                        notificationAlert(
                            'warning',
                            'Salah',
                            `QR Code tidak termasuk dalam daftar barang yang harus disiapkan`,
                        );
                    }

                    $('#scanQrcode').prop('disabled', true);

                    setTimeout(() => {
                        $('#scanQrcode').val('').prop('disabled', false).focus();
                    }, 500);
                }
            }

        });
    }


    async function handleData2(data) {
        return {
            id: data?.id ?? '-',
            barang: data?.barang ?? '-',
            supplier: data?.supplier ?? '-',
            tipe_kompensasi: data?.tipe_kompensasi ?? '-',
            format_harga_jual: data?.format_harga_jual ?? '-',
            format_hpp: data?.format_hpp ?? '-',
            format_total_hpp_barang: data?.format_total_hpp_barang ?? '-',
            format_jumlah_refund: data?.format_jumlah_refund ?? '-',
            format_total_refund: data?.format_total_refund ?? '-',
            qty_request: data?.qty_request ?? '-',
            qty_barang: data?.qty_barang ?? '-',
            qty_refund: data?.qty_refund ?? '-',
            qty_ke_supplier: data?.qty_ke_supplier ?? '-',
            stock: data?.stock
        };
    }

    async function setListData2(dataList, pagination) {
        expectedQRCodes = [];
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
                                    <th scope="col" class="${tdClass}" style="width:30%">Barang</th>
                                    <th scope="col" class="${tdClass}" style="width:15%">Kompensasi</th>
                                    <th scope="col" class="${tdClass}" style="width:15%">Qty Retur</th>
                                    <th scope="col" class="${tdClass} text-right" style="width:10%">Harga Jual</th>
                                    <th scope="col" class="${tdClass} text-right" style="width:10%">Hpp</th>
                                    <th scope="col" class="${tdClass} text-right" style="width:10%">Refund</th>
                                    <th scope="col" class="${tdClass} text-right" style="width:20%">Qty ke Suplier</th>
                                </tr>
                            </thead>
                            <tbody>`;

        dataList.forEach((element, index) => {
            if (element.stock && element.stock.length) {
                element.stock.forEach(s => {
                    expectedQRCodes.push({
                        qrcode: s.qrcode,
                        barang: element.barang
                    });
                });
            }
            const number = display_from + index;
            getDataTable += `
                <tr class="glossy-tr">
                    <td class="${tdClass} text-center">${number}</td>
                    <td class="${tdClass}">
                        <details>
                            <summary>${element.barang}</summary>
                            <p>Supplier: ${element.supplier}</p>
                            ${
                               element.stock && element.stock.length
                                ? `
                                    <div>
                                        <div class="font-weight-bold">Siapkan Barang Berikut.</div>
                                        ${element.stock.map(s => s.html).join('')}
                                    </div>
                                `
                                : '<p>Tidak ada stok</p>'
                            }
                        </details>
                    </td>
                    <td class="${tdClass} text-left">${element.tipe_kompensasi}</td>
                    <td class="${tdClass} text-left"><details><summary>${element.qty_request}</summary><ul><li>Refund: ${element.qty_refund} (Total ${element.format_total_refund})</li><li>Barang: ${element.qty_barang} (Total${element.format_total_hpp_barang})</li></ul></details></td>
                    <td class="${tdClass} text-right">${element.format_harga_jual}</td>
                    <td class="${tdClass} text-right">${element.format_hpp}</td>
                    <td class="${tdClass} text-right">${element.format_jumlah_refund}</td>
                    <td class="${tdClass} text-right">${element.qty_ke_supplier}</td>
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
        renderPagination('getListData2', '#pagination-js2', totalPage2);
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
                                    <h5 class="m-0">Data Item <span id="detail-data"></span></h5>
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
