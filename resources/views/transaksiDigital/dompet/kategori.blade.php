<script>
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
            '{{ route('td.dompetKategori.get') }}', {
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
            dompetKategoriData = dataArray;

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
                dompetKategoriData = [];
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
            dompetKategoriData = [];
        }
    }

    async function handleData2(data) {
        const edit_button = `
                <button onClick="openEditModal2('${encodeURIComponent(JSON.stringify(data))}')" class="btn btn-outline-warning btn-sm" title="Edit data ${data.nama}" data-id="${data?.id}">
                    <i class="fas fa-edit"></i>
                </button>
            `;

        const delete_button = `
                <button onClick="deleteData2('${encodeURIComponent(JSON.stringify(data))}')" class="btn btn-outline-danger btn-sm" title="Hapus data ${data.nama}" data-id="${data?.id}">
                    <i class="fas fa-trash-alt"></i>
                </button>
            `;

        return {
            id: data?.id ?? '-',
            nama: data?.nama ?? '-',
            created_by: data?.created_by ?? '-',
            created_at: data?.created_at ?? '-',
            edit_button,
            delete_button,
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
                                    <th scope="col">Nama Saldo</th>
                                    <th scope="col">Dibuat Oleh</th>
                                    <th scope="col">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>`;

        dataList.forEach((element, index) => {
            const number = display_from + index;
            getDataTable += `
                <tr class="glossy-tr">
                    <td class="${tdClass} text-center">${number}</td>
                    <td class="${tdClass}">${element.nama}</td>
                    <td class="${tdClass}">
                        <div>${element.created_by}</div>
                        <div>${element.created_at}</div>
                    </td>
                    <td class="${tdClass} text-end">
                        ${element.edit_button || ''}
                        ${element.delete_button || ''}
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

        $('#listData2').html(getDataTable);
        $('#totalPage2').text(pagination.total);
        $('#countPage2').text(`${display_from} - ${display_to}`);
        renderPagination('getListData2', '#pagination-js2', totalPage2);
    }

    async function saveData2(mode, encodedData) {
        $(document).off("click", "#save-btn").on("click", "#save-btn", async function(e) {
            e.preventDefault();

            let method = 'POST';
            let url = `{{ route('td.dompetKategori.post') }}`;

            const btn = $(this);
            const saveButton = this;

            let formData = {
                nama: $('#nama_saldo').val(),
            };

            if (mode === 'edit') {
                let data = {};

                if (encodedData && typeof encodedData === 'string' && encodedData.trim() !== '') {
                    try {
                        data = JSON.parse(decodeURIComponent(encodedData));
                    } catch (err) {
                        notificationAlert('error', 'Error',
                            'Terjadi kesalahan saat membaca data enkripsi.');
                    }
                }

                if (data.id) {
                    formData.public_id = data.id;
                    formData.updated_by = '{{ auth()->user()->id }}';
                }

                url = `{{ route('td.dompetKategori.put') }}`;
                method = 'PUT';
            } else {
                formData.created_by = '{{ auth()->user()->id }}';
            }

            if (saveButton.disabled) return;

            swal({
                title: "Konfirmasi",
                text: `Apakah Anda yakin ingin menyimpan data ${title2} ini?`,
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
                            await getListData2(defaultLimitPage2, currentPage2,
                                defaultAscending2,
                                defaultSearch2, customFilter2);
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

    async function deleteData2(encodedData) {
        let data = JSON.parse(decodeURIComponent(encodedData));

        swal({
            title: `Hapus Data ${title2} ${data?.nama}`,
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
                `{{ route('td.dompetKategori.delete') }}`, {
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
                    getListData2(defaultLimitPage2, currentPage2, defaultAscending2,
                        defaultSearch2, customFilter2);
                }, 500);
                notificationAlert('success', 'Pemberitahuan', postDataRest.data
                    .message);
            }
        }).catch(swal.noop);
    }

    function openAddModal2() {
        renderModalForm2('add');
        $('#save-btn')
            .removeClass('btn-primary')
            .addClass('btn-success')
            .prop('disabled', false)
            .html('<i class="fa fa-save mr-1"></i>Simpan');

        $('#modal-form').modal('show');
    }

    function openEditModal2(data) {
        try {
            renderModalForm2('edit', data);

            $('#save-btn')
                .removeClass('btn-success')
                .addClass('btn-primary')
                .prop('disabled', false)
                .html('<i class="fa fa-edit mr-1"></i>Update');

            $('#modal-form').modal('show');
        } catch (e) {
            notificationAlert('error', 'Error', 'Terjadi kesalahan saat memuat data untuk diedit.');
        }
    }

    async function renderModalForm2(mode = 'add', encodedData = '') {
        let data = {};

        if (encodedData && typeof encodedData === 'string' && encodedData.trim() !== '') {
            try {
                data = JSON.parse(decodeURIComponent(encodedData));
            } catch (err) {
                notificationAlert('error', 'Error', 'Terjadi kesalahan saat membaca data enkripsi.');
            }
        }

        const modalTitle = mode === 'edit' ?
            `<i class="fa fa-edit mr-1"></i>Edit ${title2}` :
            `<i class="fa fa-circle-plus mr-1"></i>Tambah ${title2}`;

        $('#modalLabel').html(modalTitle);

        const formContent = `
        <form id="form-data">
            <div class="row">
                <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
                    <div class="form-group">
                        <label for="nama_saldo">Nama Saldo</label>
                        <input type="text" class="form-control" id="nama_saldo" name="nama_saldo" placeholder="Masukkan Nama Saldo" required>
                    </div>
                </div>
            </div>
        </form>`;

        $('#modal-data').html(formContent);

        await selectData(selectOptions);

        if (mode === 'edit' && data && Object.keys(data).length > 0) {
            $('#nama_saldo').val(data.nama);
        }

        await saveData2(mode, encodedData);
    }
</script>
