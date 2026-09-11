(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};

    global.PosV3.debounce = function debounce(func, wait) {
        let timeoutId = null;

        return function debounced() {
            const context = this;
            const args = arguments;

            clearTimeout(timeoutId);
            timeoutId = setTimeout(function () {
                func.apply(context, args);
            }, wait);
        };
    };
})(window);
