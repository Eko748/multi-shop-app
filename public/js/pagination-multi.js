function renderPagination(fetchKey = 'getListData', pagination = '#pagination-js', overrideTotalPage = null) {
    const fetchObj = fetchConfigMap[fetchKey] || {
        fn: getListData,
        getConfig: () => ({
            limit: defaultLimitPage,
            page: currentPage,
            asc: defaultAscending,
            search: defaultSearch,
            filter: customFilter
        }),
        setPage: (val) => currentPage = val,
        setSearch: (val) => defaultSearch = val
    };

    const { fn: fetchFn, getConfig, setPage } = fetchObj;
    const { page } = getConfig();

    const totalPages = overrideTotalPage !== null
        ? overrideTotalPage
        : (fetchObj.getTotalPage ? fetchObj.getTotalPage() : 1);

    let paginationHtml = '';

    if (page > 1) {
        paginationHtml += `<button class="paginate-btn prev-btn btn btn-sm btn-outline-primary mx-1" data-page="${page - 1}"><i class="fa fa-circle-chevron-left"></i></button>`;
    }

    let startPage = Math.max(1, page - 2);
    let endPage = Math.min(totalPages, page + 2);

    if (startPage > 1) {
        paginationHtml += `<button class="paginate-btn page-btn btn btn-sm btn-primary" data-page="1">1</button>`;
        if (startPage > 2) {
            paginationHtml += `<button class="btn btn-sm btn-primary" style="pointer-events: none;"><i class="fa fa-ellipsis"></i></button>`;
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        paginationHtml += `<button class="paginate-btn page-btn btn btn-sm btn-primary ${i === page ? 'active' : ''}" data-page="${i}">${i}</button>`;
    }

    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            paginationHtml += `<button class="btn btn-sm btn-primary" style="pointer-events: none;"><i class="fa fa-ellipsis"></i></button>`;
        }
        paginationHtml += `<button class="paginate-btn page-btn btn btn-sm btn-primary" data-page="${totalPages}">${totalPages}</button>`;
    }

    if (page < totalPages) {
        paginationHtml += `<button class="paginate-btn next-btn btn btn-sm btn-outline-primary mx-1" data-page="${page + 1}"><i class="fa fa-circle-chevron-right"></i></button>`;
    }

    $(pagination).html(paginationHtml);

    $(pagination).off('click', '.paginate-btn').on('click', '.paginate-btn', async function () {
        const newPage = parseInt($(this).data('page'));
        if (!isNaN(newPage)) {
            setPage(newPage);
            const { limit, asc, search, filter } = getConfig();
            await fetchFn(limit, newPage, asc, search, filter);
        }
    });
}

function searchList(fetchKey = 'getListData', limitPage = '#limitPage', searchInput = '.tb-search') {
    const fetchObj = fetchConfigMap[fetchKey] || {
        fn: getListData,
        getConfig: () => ({
            limit: defaultLimitPage,
            page: currentPage,
            asc: defaultAscending,
            search: defaultSearch,
            filter: customFilter
        }),
        setPage: (val) => { currentPage = val; },
        setSearch: (val) => { defaultSearch = val; },
        setLimit: (val) => { defaultLimitPage = val; }
    };

    const { fn: fetchFn, getConfig, setPage, setSearch, setLimit } = fetchObj;

    $(limitPage).on('change', async function () {
        setPage(1);
        const limit = parseInt($(this).val()) || defaultLimitPage;
        setLimit(limit);
        const { asc, search, filter } = getConfig();
        await fetchFn(limit, 1, asc, search, filter);
    });

    $(searchInput).on('input', debounce(async function () {
        setPage(1);
        const val = $(this).val();
        setSearch(val);
        const { limit, asc, search, filter } = getConfig();
        await fetchFn(limit, 1, asc, search, filter);
    }, 500));
}

function debounce(func, wait) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}
