function loadingPage(value) {
    if (value == true) {
        document.getElementById('load-screen').style.display = '';
    } else {
        document.getElementById('load-screen').style.display = 'none';
    }
    return;
}

function loadingData() {
    let html = `
            <tr class="text-dark loading-row">
                <td class="text-center" colspan="${$('.tb-head th').length}">
                    <svg xmlns:svg="http://www.w3.org/2000/svg" xmlns="http://www.w3.org/2000/svg"
                        xmlns:xlink="http://www.w3.org/1999/xlink" version="1.0" width="162px" height="24px"
                        viewBox="0 0 128 19" xml:space="preserve"><rect x="0" y="0" width="100%" height="100%" fill="#FFFFFF" /><path fill="#1abc9c" d="M0.8,2.375H15.2v14.25H0.8V2.375Zm16,0H31.2v14.25H16.8V2.375Zm16,0H47.2v14.25H32.8V2.375Zm16,0H63.2v14.25H48.8V2.375Zm16,0H79.2v14.25H64.8V2.375Zm16,0H95.2v14.25H80.8V2.375Zm16,0h14.4v14.25H96.8V2.375Zm16,0h14.4v14.25H112.8V2.375Z"/><g><path fill="#c7efe7" d="M128.8,2.375h14.4v14.25H128.8V2.375Z"/><path fill="#c7efe7" d="M144.8,2.375h14.4v14.25H144.8V2.375Z"/><path fill="#9fe3d5" d="M160.8,2.375h14.4v14.25H160.8V2.375Z"/><path fill="#72d6c2" d="M176.8,2.375h14.4v14.25H176.8V2.375Z"/><animateTransform attributeName="transform" type="translate" values="0 0;0 0;0 0;0 0;0 0;0 0;0 0;0 0;0 0;0 0;0 0;0 0;-16 0;-32 0;-48 0;-64 0;-80 0;-96 0;-112 0;-128 0;-144 0;-160 0;-176 0;-192 0" calcMode="discrete" dur="2160ms" repeatCount="indefinite"/></g><g><path fill="#c7efe7" d="M-15.2,2.375H-0.8v14.25H-15.2V2.375Z"/><path fill="#c7efe7" d="M-31.2,2.375h14.4v14.25H-31.2V2.375Z"/><path fill="#9fe3d5" d="M-47.2,2.375h14.4v14.25H-47.2V2.375Z"/><path fill="#72d6c2" d="M-63.2,2.375h14.4v14.25H-63.2V2.375Z"/><animateTransform attributeName="transform" type="translate" values="16 0;32 0;48 0;64 0;80 0;96 0;112 0;128 0;144 0;160 0;176 0;192 0;0 0;0 0;0 0;0 0;0 0;0 0;0 0;0 0;0 0;0 0;0 0;0 0" calcMode="discrete" dur="2160ms" repeatCount="indefinite"/></g>
                    </svg>
                </td>
            </tr>`;

    return html;
}

function formatRupiah(value) {
    let number = parseFloat(value) || 0;
    let roundedNumber = Math.round(number);
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(roundedNumber);
}

function notificationAlert(tipe, title, message) {
    swal(
        title,
        message,
        tipe
    );
}

async function selectList(selectors, placeholders = null) {
    if (!Array.isArray(selectors)) {
        console.error("Selectors must be an array of element IDs.");
        return;
    }

    selectors.forEach((selector, index) => {
        const element = document.getElementById(selector);
        if (element) {
            if (element.choicesInstance) {
                element.choicesInstance.destroy();
            }

            const placeholderValue = placeholders?.[index] ?? '';

            const choicesInstance = new Choices(element, {
                removeItemButton: true,
                searchEnabled: true,
                shouldSort: false,
                allowHTML: true,
                placeholder: true,
                placeholderValue: placeholderValue,
                noResultsText: 'Tidak ada hasil',
                itemSelectText: '',
            });

            element.choicesInstance = choicesInstance;
        } else {
            console.warn(`Element with ID "${selector}" not found.`);
        }
    });
}

async function setDynamicButton(first = 'btn-primary', second = 'btn-outline-primary') {
    const buttons = document.querySelectorAll('.btn-dynamic');

    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            if (button.classList.contains(first)) {
                button.classList.remove(first);
                button.classList.add(second);
            } else {
                button.classList.remove(second);
                button.classList.add(first);
            }
        });
    });
}

async function selectMulti(optionsArray) {
    const auth_token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    for (const {
        id,
        isUrl,
        placeholder
    }
        of optionsArray) {
        let selectOption = {
            ajax: {
                url: isUrl,
                dataType: 'json',
                delay: 500,
                headers: {
                    Authorization: `Bearer ` + auth_token
                },
                data: function (params) {
                    let query = {
                        search: params.term,
                        page: params.page || 1,
                        limit: 30,
                        ascending: 1,
                    };
                    return query;
                },
                processResults: function (res, params) {
                    let data = res.data;
                    let filteredData = $.map(data, function (item) {
                        return {
                            id: item.id,
                            text: item.optional ? `${item.optional} / ${item.text}` : item.text
                        };
                    });
                    return {
                        results: filteredData,
                        pagination: {
                            more: res.pagination && res.pagination.more
                        }
                    };
                },
            },
            allowClear: true,
            placeholder: placeholder,
            multiple: true,
        };

        await $(id).select2(selectOption);
    }
}

async function selectData(optionsArray) {
    const auth_token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    for (const {
        id,
        isUrl,
        placeholder,
        isModal = null,
        isFilter = {},
        isDisabled = false,
        isMinimum = 0,
        extraFields = null,
        isForm = false,
        multiple = false,
        isImage = false,
    }
        of optionsArray) {

        let errorMessage = "Data tidak ditemukan!";

        let selectOption = {
            ajax: {
                url: isUrl,
                dataType: 'json',
                delay: 500,
                headers: {
                    Authorization: `Bearer ${auth_token}`
                },
                data: function (params) {
                    return {
                        search: params.term,
                        page: params.page || 1,
                        limit: 30,
                        ascending: 1,
                        ...isFilter,
                    };
                },
                processResults: function (res) {
                    let data = res.data;

                    let filteredData = $.map(data, function (item) {
                        let base = {
                            id: item.id,
                            text: item.text
                        };

                        if (extraFields) {
                            if (typeof extraFields === 'object') {
                                for (let key in extraFields) {
                                    base[key] = item[extraFields[key]] ?? null;
                                }
                            } else if (typeof extraFields === 'string') {
                                base[extraFields] = item[extraFields] ?? null;
                            }
                        }

                        return base;
                    });

                    return {
                        results: filteredData,
                        pagination: {
                            more: res.pagination && res.pagination.more
                        }
                    };
                },
                error: function (xhr) {
                    if (xhr.status === 400) {
                        errorMessage = xhr.responseJSON?.message ||
                            "Terjadi kesalahan saat memuat data!";
                    }
                }
            },
            dropdownParent: isModal ? $(isModal) : null,
            allowClear: true,
            placeholder: placeholder,
            dropdownAutoWidth: true,
            width: '100%',
            disabled: isDisabled,
            minimumInputLength: isMinimum,
            multiple: multiple,
            language: {
                errorLoading: function () {
                    return errorMessage;
                }
            }
        };

        if (isImage) {
            selectOption.escapeMarkup = function (m) {
                return m;
            };
            selectOption.templateResult = function (data) {
                return data.text;
            };
            selectOption.templateSelection = function (data) {
                return data.text;
            };
        }

        await $(id).select2(selectOption);

        if (isForm && extraFields) {
            $(id).on('select2:select', function (e) {
                let data = e.params.data;

                if (typeof extraFields === 'object') {
                    for (let key in extraFields) {
                        $(`#${key}`).remove();
                        if (data[key]) {
                            $('<input>').attr({
                                type: 'hidden',
                                id: key,
                                name: key,
                                value: data[key]
                            }).appendTo($(this).closest('form'));
                        }
                    }
                } else if (typeof extraFields === 'string') {
                    $(`#${extraFields}`).remove();
                    if (data[extraFields]) {
                        $('<input>').attr({
                            type: 'hidden',
                            id: extraFields,
                            name: extraFields,
                            value: data[extraFields]
                        }).appendTo($(this).closest('form'));
                    }
                }
            });

            $(id).on('select2:clear', function () {
                if (typeof extraFields === 'object') {
                    for (let key in extraFields) {
                        $(`#${key}`).remove();
                    }
                } else if (typeof extraFields === 'string') {
                    $(`#${extraFields}`).remove();
                }
            });
        }
    }
}

async function setDatePicker(params = 'tanggal') {
    flatpickr(`#${params}`, {
        locale: "id",
        enableTime: true,
        enableSeconds: true,
        time_24hr: true,
        secondIncrement: 1,
        dateFormat: "Y-m-d H:i:S",
        defaultDate: new Date(),
        allowInput: true,
        appendTo: document.querySelector('.modal-body'),
        position: "above",

        onDayCreate: (dObj, dStr, fp, dayElem) => {
            dayElem.addEventListener('click', () => {
                fp.calendarContainer
                    .querySelectorAll('.selected')
                    .forEach(el => {
                        el.style.backgroundColor = "#1abc9c";
                        el.style.color = "#fff";
                    });
            });
        }
    });

    const inputField = document.querySelector(`#${params}`);
    inputField.removeAttribute("readonly");
    inputField.style.backgroundColor = "";
    inputField.style.cursor = "pointer";
}

function hasPermission(identifier) {
    // Kalau array, cek apakah ada salah satu yang cocok
    if (Array.isArray(identifier)) {
        return identifier.some(id => allowedPermissions.map(String).includes(String(id)));
    }

    // Kalau string atau number biasa
    return allowedPermissions.map(String).includes(String(identifier));
}

// Cegah dropdown auto-close saat hover
$('.drp-user .dropdown-toggle').on('click', function (e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).next('.dropdown-menu').toggleClass('show');
});

$(document).on('input', '.rupiah', function () {
    let value = this.value.replace(/\D/g, '');
    this.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
});

// Klik di luar, baru nutup
$(document).on('click', function (e) {
    if (!$(e.target).closest('.drp-user').length) {
        $('.drp-user .dropdown-menu').removeClass('show');
    }
});
window.addEventListener("scroll", function () {
    const header = document.querySelector("header.site-header");
    if (window.scrollY > 10) {
        header.classList.add("scrolled");
    } else {
        header.classList.remove("scrolled");
    }
});

function toggleFullscreen() {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen();
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        }
    }
}


document.getElementById('fullscreenBtn')?.addEventListener('click', toggleFullscreen);
document.getElementById('fullscreenBtnMobile')?.addEventListener('click', toggleFullscreen);
