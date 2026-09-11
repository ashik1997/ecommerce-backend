(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};

    global.PosV3.formatMoney = function formatMoney(value, decimals) {
        const places = typeof decimals === 'number' ? decimals : 2;
        const amount = Number(value);

        if (!Number.isFinite(amount)) {
            return (0).toFixed(places);
        }

        return amount.toFixed(places);
    };
})(window);
