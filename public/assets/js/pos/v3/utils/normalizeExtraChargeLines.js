(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};

    global.PosV3.normalizeExtraChargeLines = function normalizeExtraChargeLines(lines) {
        if (!Array.isArray(lines)) {
            return [];
        }

        return lines.map(function (line) {
            return {
                temp_id: line.temp_id || ('charge-' + Date.now() + '-' + Math.random().toString(36).slice(2)),
                charge_type_id: line.charge_type_id || null,
                title: (line.title || 'Extra Charge').toString(),
                amount: Math.max(0, Number(line.amount || 0)),
            };
        }).filter(function (line) {
            return line.title || line.amount > 0;
        });
    };
})(window);
