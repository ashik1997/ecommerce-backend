(function () {
    if (!window.axios) return;
    var csrf = document.querySelector('meta[name="csrf-token"]');
    window.AppAxios = axios.create({
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrf ? csrf.getAttribute('content') : ''
        }
    });
    window.AppAxios.interceptors.response.use(function (response) {
        return response;
    }, function (error) {
        if (error.response && error.response.status === 419) alert('Session expired. Please refresh and try again.');
        if (error.response && error.response.status === 403) alert('Permission denied.');
        return Promise.reject(error);
    });
})();
