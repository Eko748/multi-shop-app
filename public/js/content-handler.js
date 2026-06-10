class CRUDHandler {
    /**
     * Helper untuk mengambil data (GET) dengan standarisasi error handling tabel.
     */
    static async get(url, params = {}) {
        try {
            const response = await renderAPI('GET', url, params);
            return response;
        } catch (error) {
            return error.response || { status: 500, data: { message: 'Terjadi kesalahan sistem.' } };
        }
    }

    /**
     * Helper untuk menyimpan atau memperbarui data (POST/PUT menggunakan FormData).
     */
    static async save(options = {}) {
        const {
            formId = '#form-data',
            btnId = '#save-btn',
            modalId = '#modal-form',
            title = 'Data',
            routePost,
            routeUpdate,
            userId,
            tokoId,
            onSuccess
        } = options;

        $(document).off("click", btnId).on("click", btnId, async function (e) {
            e.preventDefault();

            const btn = $(this);
            const saveButton = this;
            const form = $(formId)[0];
            const formData = new FormData(form);

            if (userId) {
                formData.append('user_id', userId);
            }

            if (saveButton.disabled) return;

            const isEdit = formData.get('id') !== null && formData.get('id') !== '';
            const url = isEdit ? routeUpdate : routePost;

            swal({
                title: "Konfirmasi",
                text: `Apakah Anda yakin ingin menyimpan ${title} ini?`,
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
                const originalContent = btn.data('original-content') || btn.html();
                btn.data('original-content', originalContent);
                btn.html(`<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan`);

                if (typeof loadingPage === 'function') loadingPage(true);

                if (isEdit) {
                    formData.append('_method', 'PUT');
                }

                try {
                    const response = await renderAPI('POST', url, formData);
                    if (typeof loadingPage === 'function') loadingPage(false);

                    if (response.status >= 200 && response.status < 300) {
                        notificationAlert('success', 'Pemberitahuan', response.data.message || 'Data berhasil disimpan.');

                        setTimeout(() => {
                            $(modalId).modal('hide');
                            if (typeof onSuccess === 'function') onSuccess();
                        }, 500);
                    } else {
                        notificationAlert('info', 'Pemberitahuan', response.data.message || 'Terjadi kesalahan saat menyimpan.');
                        saveButton.disabled = false;
                        btn.html(btn.data('original-content'));
                    }
                } catch (error) {
                    if (typeof loadingPage === 'function') loadingPage(false);
                    notificationAlert('error', 'Kesalahan', error?.response?.data?.message || 'Terjadi kesalahan saat menyimpan data.');
                    saveButton.disabled = false;
                    btn.html(btn.data('original-content'));
                }
            });
        });
    }

    /**
     * Helper untuk menghapus data (DELETE) menggunakan SweetAlert konfirmasi.
     */
    static async delete(options = {}) {
        const {
            triggerClass = '.hapus-data',
            title = 'Data',
            routeDelete,
            userId,
            tokoId,
            onSuccess
        } = options;

        $(document).off("click", triggerClass).on("click", triggerClass, async function () {
            const id = $(this).attr("data-id");
            const name = $(this).attr("data-name");

            swal({
                title: `Hapus ${title} ${name}`,
                text: "Apakah anda yakin?",
                type: "warning",
                showCancelButton: true,
                confirmButtonText: "Ya, Hapus!",
                cancelButtonText: "Tidak, Batal!",
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                reverseButtons: true,
            }).then(async (result) => {
                if (!result) return;

                try {
                    const response = await renderAPI('DELETE', routeDelete, {
                        id: id,
                        user_id: userId
                    });

                    if (response.status === 200) {
                        notificationAlert('success', 'Pemberitahuan', response.data.message || 'Data berhasil dihapus.');
                        setTimeout(() => {
                            if (typeof onSuccess === 'function') onSuccess();
                        }, 500);
                    } else {
                        notificationAlert('info', 'Pemberitahuan', response.data.message || 'Gagal menghapus data.');
                    }
                } catch (error) {
                    notificationAlert('error', 'Kesalahan', error?.response?.data?.message || 'Terjadi kesalahan sistem.');
                }
            }).catch(swal.noop);
        });
    }
}

class ButtonBuilder {
    /**
     * Membuat tombol edit dengan enkapsulasi data otomatis
     */
    static edit(data, title, labelKey = 'nama_level') {
        const itemTitle = data[labelKey] ?? '';
        return `
            <button class="p-1 btn edit-data action_button" onClick="openEditModal('${encodeURIComponent(JSON.stringify(data))}')">
                <span class="text-dark" title="Edit ${title}: ${itemTitle}">Edit</span>
                <div class="icon text-warning" title="Edit ${title}: ${itemTitle}">
                    <i class="fa fa-edit"></i>
                </div>
            </button>`;
    }

    /**
     * Membuat tombol hapus bersyarat (misal: ID 1 tidak bisa dihapus)
     */
    static delete(data, title, labelKey = 'nama_level', protectedId = null) {
        // 1. Jika protectedId diisi, cek apakah ID data ini harus dilindungi
        if (protectedId !== null) {
            // Jika protectedId berupa Array (misal: [1, 2]), cek menggunakan includes
            if (Array.isArray(protectedId) && protectedId.includes(data.id)) {
                return '';
            }
            // Jika protectedId hanya berupa angka tunggal biasa (misal: 1)
            if (data.id == protectedId) {
                return '';
            }
        }

        const itemTitle = data[labelKey] ?? '';
        return `
        <a class="p-1 btn hapus-data action_button"
            data-container="body" data-toggle="tooltip" data-placement="top"
            title="Hapus ${title}: ${itemTitle}"
            data-id='${data.id}'
            data-name='${itemTitle}'>
            <span class="text-dark">Hapus</span>
            <div class="icon text-danger">
                <i class="fa fa-trash"></i>
            </div>
        </a>`;
    }

    /**
     * Membuat tombol custom seperti "Atur Akses", "Detail", dll.
     */
    static custom(options = {}) {
    const {
        url = '#',
        classBtn = 'btn-success',
        icon = 'fa-cog',
        text = 'Atur',
        titleTooltip = ''
    } = options;

    return `
            <a href='${url}' class="btn action_button ${classBtn}"
                data-container="body" data-toggle="tooltip" data-placement="top"
                title="${titleTooltip}">
                <span class="text-dark">${text}</span>
                <div class="icon">
                    <i class="fa ${icon}"></i>
                </div>
            </a>`;
}

    /**
     * Wrapper pembungkus tombol aksi dengan class pembungkus animasi (.hovering)
     */
    static buildActionGroup(buttons = []) {
    return buttons
        .filter(btn => btn && btn.trim() !== '') // Buang tombol yang kosong/tidak aktif
        .map(btn => `<div class="hovering p-1">${btn}</div>`)
        .join('');
}
}

class TableRenderer {
    static render(options = {}) {
        const {
            dataList = [],
            pagination = {},
            limit = 10,
            targetBodyId = '#listData',
            targetTotalId = '#totalPage',
            targetCountId = '#countPage',
            defaultClass = 'align-center text-dark text-wrap',
            columns = []
        } = options;

        const currentPage = pagination.current_page ?? 1;
        const totalItems = pagination.total ?? 0;
        const displayFrom = ((limit * (currentPage - 1)) + 1);
        const displayTo = Math.min(displayFrom + dataList.length - 1, totalItems);

        let htmlRows = '';

        dataList.forEach((element, index) => {
            const rowNumber = displayFrom + index;
            let columnsHtml = '';

            columns.forEach(col => {
                const finalClass = col.class ? col.class : defaultClass;

                if (col.key === '_index') {
                    columnsHtml += `<td class="${finalClass}">${rowNumber}.</td>`;
                } else {
                    const value = element[col.key] ?? '-';
                    columnsHtml += `<td class="${finalClass}">${value}</td>`;
                }
            });

            htmlRows += `<tr class="text-dark">${columnsHtml}</tr>`;
        });

        $(targetBodyId).html(htmlRows);
        $(targetTotalId).text(totalItems);
        $(targetCountId).text(totalItems > 0 ? `${displayFrom} - ${displayTo}` : "0 - 0");

        $('[data-toggle="tooltip"]').tooltip();
        if (typeof renderPagination === 'function') {
            renderPagination();
        }
    }
}
