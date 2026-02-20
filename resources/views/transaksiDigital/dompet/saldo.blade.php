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
            '{{ route('td.dompetSaldo.get') }}', {
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
                    dataArray.map(item => handleData(item))
                );
                await setListData(handleDataArray, getDataRest.data.pagination);
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
        const edit_button = `
                <button onClick="openEditModal('${encodeURIComponent(JSON.stringify(data))}')" class="btn btn-outline-warning btn-sm" title="Edit data ${data.format_saldo}" data-id="${data?.id}">
                    <i class="fas fa-edit"></i>
                </button>
            `;

        const delete_button = `
                <button onClick="deleteData('${encodeURIComponent(JSON.stringify(data))}')" class="btn btn-outline-danger btn-sm" title="Hapus data ${data.format_saldo}" data-id="${data?.id}">
                    <i class="fas fa-trash-alt"></i>
                </button>
            `;

        let infoText = 'Dibuat oleh:';
        let infoUser = `${data.created_by || '-'}, ${data.created_at}`;
        if (data.updated_by != null) {
            infoText = 'Diperbarui oleh:';
            infoUser = `${data.updated_by || '-'}, ${data.updated_at}`;
        }

        const info = `
            <div>
                <small class="d-block text-muted">${infoText}</small>
                <small class="d-block text-bold">${infoUser}</small>
            </div>`;

        return {
            id: data?.id ?? '-',
            format_saldo: data?.format_saldo ?? '-',
            format_harga_beli: data?.format_harga_beli ?? '-',
            kategori: data?.kategori ?? '-',
            edit_button,
            delete_button,
            info
        };
    }

    async function setListData(dataList, pagination) {
        totalPage = pagination.total_pages;
        currentPage = pagination.current_page;
        let display_from = ((defaultLimitPage * (currentPage - 1)) + 1);
        let display_to = Math.min(display_from + dataList.length - 1, pagination.total);

        let getDataCard = '';
        dataList.forEach((element, index) => {
            getDataCard += `
                    <div class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-6">
                        <div class="card shadow-sm border-0 m-0 rounded glossy-card bg-light h-100">
                            <div class="card-body d-flex flex-column justify-content-between">
                                <div>
                                    <h6 class="text-uppercase text-secondary fw-semibold mb-1">
                                        <i class="fa fa-wallet mr-1 text-primary"></i> Saldo ${element.kategori}
                                    </h6>
                                    <p style="color: #212529; font-weight: bold; font-size: 2.25rem;">${element.format_saldo}</p>
                                </div>
                                <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center">
                                    <div class="d-flex align-items-center mb-2 mb-md-0">
                                        <i class="fa fa-coins text-muted mr-2" style="font-size: 1.25rem;"></i>
                                        <div>
                                            <small class="text-muted">Harga: </small><small class="text-bold">${element.format_harga_beli || '-'}</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center">
                                    <div class="d-flex align-items-center mb-2 mb-md-0">
                                        <i class="fa fa-user text-muted mr-2" style="font-size: 1.50rem;"></i>
                                        ${element.info}
                                    </div>
                                    <div class="d-flex justify-content-end" style="gap: 0.5rem;">
                                        ${element.edit_button}
                                        ${element.delete_button}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>`;
        });

        $('#listData').html(getDataCard);
        $('#totalPage').text(pagination.total);
        $('#countPage').text(`${display_from} - ${display_to}`);
        renderPagination();
    }

    async function saveData(mode, encodedData) {
        $(document).off("click", "#save-btn").on("click", "#save-btn", async function(e) {
            e.preventDefault();

            let method = 'POST';
            let url = `{{ route('td.dompetSaldo.post') }}`;

            const btn = $(this);
            const saveButton = this;

            let formData = {
                toko_id: {{ auth()->user()->toko_id }},
                dompet_kategori_id: $('#dompet_kategori').val(),
                kas_id: $('#kas_id').val(),
                jenis_barang_id: $("#kas_id").select2('data')[0].jenis_id,
                tipe_kas: $("#kas_id").select2('data')[0].tipe_kas,
                saldo_kas: $("#kas_id").select2('data')[0].saldo_kas,
                harga_beli: $('#harga_beli').val(),
                saldo: $('#saldo').val(),
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

                url = `{{ route('td.dompetSaldo.put') }}`;
                method = 'PUT';
            } else {
                formData.created_by = '{{ auth()->user()->id }}';
            }

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
                        btn.html(originalContent);
                    }
                } catch (error) {
                    loadingPage(false);

                    const errorsObj = error?.response?.data?.errors || null;
                    let firstError = null;

                    if (errorsObj) {
                        const allErrors = Object.values(errorsObj).flat();
                        firstError = allErrors.length > 0 ? allErrors[0] : null;
                    }

                    const message = firstError || error?.response?.data?.message ||
                        'Terjadi kesalahan saat menyimpan data.';

                    notificationAlert(
                        'error',
                        firstError ? 'Validasi Gagal' : 'Kesalahan',
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
            title: `Hapus Data ${title} ${data?.kategori} ${data?.format_saldo}`,
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
                `{{ route('td.dompetSaldo.delete') }}`, {
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
                }, 500);
                notificationAlert('success', 'Pemberitahuan', postDataRest.data
                    .message);
            }
        }).catch(swal.noop);
    }

    function openAddModal() {
        renderModalForm('add');
        $('#save-btn')
            .removeClass('btn-primary')
            .addClass('btn-success')
            .prop('disabled', false)
            .html('<i class="fa fa-save mr-1"></i>Simpan');

        $('#modal-form').modal('show');
    }

    function openEditModal(data) {
        try {
            renderModalForm('edit', data);

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
            `<i class="fa fa-circle-plus mr-1"></i>Tambah ${title}`;

        $('#modalLabel').html(modalTitle);

        const formContent = `
        <form id="form-data">
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label for="dompet_kategori">${title2}</label>
                        <select class="form-control select2" id="dompet_kategori" name="dompet_kategori" required></select>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label for="kas_id">Sumber Dana</label>
                        <select id="kas_id" name="kas_id" class="form-control select2 w-100"></select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label for="saldo">Saldo</label>
                        <input type="number" class="form-control" id="saldo" name="saldo" step="0.01" placeholder="Masukkan nominal saldo" required>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label for="harga_beli">Harga Beli</label>
                        <input type="number" class="form-control" id="harga_beli" name="harga_beli" step="0.01" placeholder="Masukkan nominal harga beli" required>
                    </div>
                </div>
            </div>
        </form>`;

        $('#modal-data').html(formContent);

        await selectData(selectOptions);

        if (mode === 'edit' && data && Object.keys(data).length > 0) {
            if ($('#dompet_kategori option[value="' + data.id_kategori + '"]').length === 0) {
                const newOption = new Option(data.kategori, data.id_kategori, true, true);
                $('#dompet_kategori').append(newOption).trigger('change');
            } else {
                $('#dompet_kategori').val(data.id_kategori).trigger('change');
            }

            $('#saldo').val(data.saldo);
            $('#harga_beli').val(data.harga_beli);
        }

        await saveData(mode, encodedData);
    }
</script>
