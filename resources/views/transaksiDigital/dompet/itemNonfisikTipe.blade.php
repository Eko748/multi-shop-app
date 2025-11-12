<script>
    async function getListData4(limit = 10, page = 1, ascending = 0, search = '', customFilter = {}) {
        $('#listData4').html(`
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
            '{{ route('td.itemNonfisikTipe.get') }}', {
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
                    dataArray.map(item => handleData4(item))
                );
                await setListData4(handleDataArray, getDataRest.data.pagination);
            } else {
                $('#listData4').html(`
                    <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
                        <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
                            <div class="text-center my-3" role="alert">
                                Data tidak tersedia untuk ditampilkan.
                            </div>
                        </div>
                    </div>
                    `);
                $('#countPage4').text("0 - 0");
                $('#totalPage4').text("0");
            }
        } else {
            let errorMessage = getDataRest?.data?.message || 'Data gagal dimuat';

            $('#listData4').html(`
                <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
                    <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
                        <div class="alert alert-warning text-center mt-4" role="alert">
                            ${errorMessage}
                        </div>
                    </div>
                </div>
                `);
            $('#countPage4').text("0 - 0");
            $('#totalPage4').text("0");
        }
    }

    async function handleData4(data) {
        const edit_button = `
                <button onClick="openEditModal4('${encodeURIComponent(JSON.stringify(data))}')" class="btn btn-outline-warning btn-sm" title="Edit data ${data.nama}" data-id="${data?.id}">
                    <i class="fas fa-edit"></i>
                </button>
            `;

        const delete_button = `
                <button onClick="deleteData4('${encodeURIComponent(JSON.stringify(data))}')" class="btn btn-outline-danger btn-sm" title="Hapus data ${data.nama}" data-id="${data?.id}">
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

    async function setListData4(dataList, pagination) {
        totalPage4 = pagination.total_pages;
        currentPage4 = pagination.current_page;
        let display_from = ((defaultLimitPage4 * (currentPage4 - 1)) + 1);
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
                                    <th scope="col">Tipe Item</th>
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

        $('#listData4').html(getDataTable);
        $('#totalPage4').text(pagination.total);
        $('#countPage4').text(`${display_from} - ${display_to}`);
        renderPagination('getListData4', '#pagination-js4', totalPage4);
    }

    async function saveData4(mode, encodedData) {
        $(document).off("click", "#save-btn").on("click", "#save-btn", async function(e) {
            e.preventDefault();

            let method = 'POST';
            let url = `{{ route('td.itemNonfisikTipe.post') }}`;

            const btn = $(this);
            const saveButton = this;

            let formData = {
                nama: $('#nama_tipe_item').val(),
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

                url = `{{ route('td.itemNonfisikTipe.put') }}`;
                method = 'PUT';
            } else {
                formData.created_by = '{{ auth()->user()->id }}';
            }

            if (saveButton.disabled) return;

            swal({
                title: "Konfirmasi",
                text: `Apakah Anda yakin ingin menyimpan data ${title4} ini?`,
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
                            await getListData4(defaultLimitPage4, currentPage4,
                                defaultAscending4, defaultSearch4,
                                customFilter4);
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

    async function deleteData4(encodedData) {
        let data = JSON.parse(decodeURIComponent(encodedData));

        swal({
            title: `Hapus Data ${title4} ${data?.nama}`,
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
                `{{ route('td.itemNonfisikTipe.delete') }}`, {
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
                    getListData4(defaultLimitPage4, currentPage4, defaultAscending4,
                        defaultSearch4, customFilter4);
                }, 500);
                notificationAlert('success', 'Pemberitahuan', postDataRest.data
                    .message);
            }
        }).catch(swal.noop);
    }

    function openAddModal4() {
        renderModalForm4('add');
        $('#save-btn')
            .removeClass('btn-primary')
            .addClass('btn-success')
            .prop('disabled', false)
            .html('<i class="fa fa-save mr-1"></i>Simpan');

        $('#modal-form').modal('show');
    }

    function openEditModal4(data) {
        try {
            renderModalForm4('edit', data);

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

    async function renderModalForm4(mode = 'add', encodedData = '') {
        let data = {};

        if (encodedData && typeof encodedData === 'string' && encodedData.trim() !== '') {
            try {
                data = JSON.parse(decodeURIComponent(encodedData));
            } catch (err) {
                notificationAlert('error', 'Error', 'Terjadi kesalahan saat membaca data enkripsi.');
            }
        }

        const modalTitle = mode === 'edit' ?
            `<i class="fa fa-edit mr-1"></i>Edit ${title4}` :
            `<i class="fa fa-circle-plus mr-1"></i>Tambah ${title4}`;

        $('#modalLabel').html(modalTitle);

        const formContent = `
        <form id="form-data">
            <div class="row">
                <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
                    <div class="form-group">
                        <label for="nama_tipe_item">Tipe Item</label>
                        <input type="text" class="form-control" id="nama_tipe_item" name="nama_tipe_item" placeholder="Masukkan Tipe Item" required>
                    </div>
                </div>
            </div>
        </form>`;

        $('#modal-data').html(formContent);

        await selectData(selectOptions);

        if (mode === 'edit' && data && Object.keys(data).length > 0) {
            $('#nama_tipe_item').val(data.nama);
        }

        await saveData4(mode, encodedData);
    }
</script>
