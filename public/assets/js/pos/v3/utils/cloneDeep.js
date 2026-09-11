(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};

    global.PosV3.cloneDeep = function cloneDeep(value) {
        if (value == null) {
            return value;
        }

        return JSON.parse(JSON.stringify(value));
    };
})(window);
