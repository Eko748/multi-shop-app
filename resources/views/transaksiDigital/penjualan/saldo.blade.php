<script>
    async function getCountData() {
        $('#countData').html(`
            <div class="d-flex justify-content-center align-items-center my-2">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
        `);

        let getDataRest = await renderAPI(
            'GET',
            '{{ route('td.dompetSaldo.getSisaSaldo') }}', {}
        ).then(function(response) {
            return response;
        }).catch(function(error) {
            return error.response;
        });

        if (getDataRest && getDataRest.status === 200) {
            const data = getDataRest.data.data;
            $('#countData').html(`
                <p id="countData" class="mb-0" style="color: #212529; font-weight: bold; font-size: 2.25rem;">
                    ${data.format}
                </p>
            `)
        } else {
            let errorMessage = getDataRest?.data?.message || 'Data gagal dimuat';

            $('#countData').html(`
                <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
                    <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
                        <div class="alert alert-warning text-center mt-4" role="alert">
                            ${errorMessage}
                        </div>
                    </div>
                </div>
                `);
        }
    }

    async function getListData2(limit = 10, page = 1, ascending = 0, search = '', customFilter = {}) {
        $('#listData2').html(`
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
            '{{ route('td.dompetSaldo.getTotalPerKategori') }}', {
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
                    dataArray.map(item => handleData2(item))
                );
                await setListData2(handleDataArray, getDataRest.data.pagination);
            } else {
                $('#listData2').html(`
                    <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
                        <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
                            <div class="text-center my-3" role="alert">
                                Data tidak tersedia untuk ditampilkan.
                            </div>
                        </div>
                    </div>
                    `);
                $('#countPage2').text("0 - 0");
                $('#totalPage2').text("0");
            }
        } else {
            let errorMessage = getDataRest?.data?.message || 'Data gagal dimuat';

            $('#listData2').html(`
                <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
                    <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
                        <div class="alert alert-warning text-center mt-4" role="alert">
                            ${errorMessage}
                        </div>
                    </div>
                </div>
                `);
            $('#countPage2').text("0 - 0");
            $('#totalPage2').text("0");
        }
    }

    async function handleData2(data) {
        return {
            nama_kategori: data?.nama_kategori ?? '-',
            total_saldo: data?.total_saldo ?? '-',
            format_total_saldo: data?.format_total_saldo ?? '-',
        };
    }

    async function setListData2(dataList, pagination) {
        totalPage2 = pagination.total_pages;
        currentPage2 = pagination.current_page;
        let display_from = ((defaultLimitPage2 * (currentPage2 - 1)) + 1);
        let display_to = Math.min(display_from + dataList.length - 1, pagination.total);
        let tdClass = 'text-nowrap align-top';
        let getDataTable = `
            <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
                <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover m-0">
                                <thead class="glossy-thead">
                                    <tr>
                                        <th scope="col" class="text-center">No</th>
                                        <th scope="col" class="text-left">Kategori</th>
                                        <th scope="col" class="text-right">Sisa Saldo</th>
                                    </tr>
                                </thead>
                                <tbody>`;

        dataList.forEach((element, index) => {
            const number = display_from + index;
            getDataTable += `
                <tr class="glossy-tr">
                    <td class="${tdClass} text-center">${number}</td>
                    <td class="${tdClass}">${element.nama_kategori}</td>
                    <td class="${tdClass} text-right">${element.format_total_saldo}</td>
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
</script>
