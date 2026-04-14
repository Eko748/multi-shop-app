async function renderAPI(method, url, body) {
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    const config = {
        headers: {
            'X-CSRF-TOKEN': csrf
        },
        withCredentials: true
    };

    if (method === 'GET' || method === 'DELETE') {
        return axios[method.toLowerCase()](url, {
            ...config,
            params: body
        });
    }

    return axios[method.toLowerCase()](url, body, config);
}
