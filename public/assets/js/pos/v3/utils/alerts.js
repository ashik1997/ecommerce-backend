(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};

    global.PosV3.alerts = {
        show: function show(title, icon, text) {
            const opts = {
                title: title || '',
                icon: icon || 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
            };

            if (text) {
                opts.text = text;
            }

            if (typeof global.Swal !== 'undefined') {
                return global.Swal.fire(opts);
            }

            global.alert(text ? title + '\n' + text : title);
            return Promise.resolve();
        },
        confirm: function confirm(title, text, icon) {
            if (typeof global.Swal !== 'undefined') {
                return global.Swal.fire({
                    title: title || 'Are you sure?',
                    text: text || '',
                    icon: icon || 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'Cancel',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                }).then(function (result) {
                    return !!(result && result.isConfirmed);
                });
            }

            return Promise.resolve(global.confirm(text ? title + '\n' + text : title));
        },
    };
})(window);
