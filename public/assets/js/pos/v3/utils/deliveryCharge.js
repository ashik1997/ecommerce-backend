(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};

    global.PosV3.deliveryCharge = {
        getSteadfastDeliveryCharge: function getSteadfastDeliveryCharge(insideCity, weights) {
            const baseInside = 70;
            const baseOutside = 130;
            const extraPerKg = 20;
            let total = 0;

            (weights || []).forEach(function (weight) {
                if (weight <= 0) {
                    return;
                }

                const base = insideCity ? baseInside : baseOutside;
                let charge = 0;

                if (weight <= 1) {
                    charge = base;
                } else {
                    charge = base + (Math.ceil(weight - 1) * extraPerKg);
                }

                total += charge;
            });

            return total;
        },
    };
})(window);
