(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};

    global.PosV3.variantHelpers = {
        normalizeToken: function normalizeToken(value) {
            return (value || '').trim().toLowerCase().replace(/\s+/g, '-');
        },
        variantAttributeKeys: function variantAttributeKeys(variantValues) {
            if (!Array.isArray(variantValues) || !variantValues.length) {
                return [];
            }

            return variantValues.reduce(function (keys, variantGroup) {
                Object.keys(variantGroup || {}).forEach(function (key) {
                    if (keys.indexOf(key) === -1) {
                        keys.push(key);
                    }
                });
                return keys;
            }, []);
        },
        variantValuesFromCombo: function variantValuesFromCombo(combo, attributeKeys) {
            return attributeKeys
                .map(function (key) {
                    return combo && combo[key] != null ? String(combo[key]).trim() : '';
                })
                .filter(Boolean);
        },
        variantKeyFromCombo: function variantKeyFromCombo(combo, attributeKeys) {
            if (!combo) {
                return '';
            }

            if (combo.combination_key) {
                return String(combo.combination_key).trim();
            }

            return global.PosV3.variantHelpers
                .variantValuesFromCombo(combo, attributeKeys)
                .join('-');
        },
    };
})(window);
