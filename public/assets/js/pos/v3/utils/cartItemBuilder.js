(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};

    function extractUnitData(product) {
        if (!product || !product.unit) {
            return {};
        }

        const unit = product.unit;
        const today = new Date().toISOString().split('T')[0];

        return {
            unit_code: unit.code || '',
            warehouse_name: unit.warehouse_name || '',
            cartoon_name: unit.cartoon_name || '',
            room_name: unit.room_name || '',
            warehouse_id: unit.warehouse_id || null,
            room_id: unit.room_id || null,
            cartoon_id: unit.cartoon_id || null,
            purchase_price: unit.purchase_price || 0,
            serial_no: unit.serial_no || '',
            imei_1: unit.imei_1 || '',
            imei_2: unit.imei_2 || '',
            supplier_warranty_start_date: unit.supplier_warranty_start_date || '',
            supplier_warranty_end_date: unit.supplier_warranty_end_date || '',
            customer_warranty_start_date: unit.customer_warranty_start_date || today,
            customer_warranty_end_date: unit.customer_warranty_end_date || unit.supplier_warranty_end_date || '',
            warranty_note: unit.warranty_note || '',
        };
    }

    function buildVariantCartItem(product, variantPayload) {
        const payload = variantPayload || product;
        const variantId = payload.product_variant_id || payload.variant_id || null;
        const variantKey = payload.variant_combination_key || payload.variant_name || '';
        const variantMainPrice = Number(payload.main_price || payload.unit_price || 0);
        const variantDiscountPrice = Number(payload.discount_price || 0);
        const discountFixed = variantDiscountPrice > 0
            ? Math.max(0, variantMainPrice - variantDiscountPrice)
            : Number((payload.discount && payload.discount.fixed) || 0);

        return Object.assign({
            product_id: product.product_id || product.id,
            variant_id: variantId,
            product_variant_id: variantId,
            variant_combination_key: variantKey,
            variant_name: payload.variant_name || variantKey,
            qty: 1,
            max_qty: payload.max_qty != null ? Number(payload.max_qty) : Number(product.stock || 0),
            title: variantKey ? ((product.name || product.title) + ' - ' + variantKey) : (product.name || product.title),
            image_url: product.image_url || '',
            unit_price: variantMainPrice,
            main_price: variantMainPrice,
            discount_price: variantDiscountPrice > 0 ? variantDiscountPrice : variantMainPrice,
            pos_main_price: variantMainPrice,
            pos_wholesale_price: variantMainPrice,
            pos_retail_price: variantMainPrice,
            pos_catalog_discount: {
                type: 'fixed',
                percent: 0,
                fixed: discountFixed,
                value: discountFixed,
                amount: 0,
            },
            discount: {
                type: 'fixed',
                percent: 0,
                fixed: discountFixed,
                value: discountFixed,
                amount: 0,
            },
            product_note: '',
        }, extractUnitData(product));
    }

    function buildSimpleCartItem(product) {
        return Object.assign({
            product_id: product.product_id || product.id,
            variant_id: null,
            product_variant_id: null,
            variant_combination_key: null,
            variant_name: null,
            qty: 1,
            max_qty: product.max_qty != null && product.max_qty !== ''
                ? Number(product.max_qty)
                : Number(product.stock || 0),
            title: product.name || product.title,
            image_url: product.image_url || '',
            product_note: '',
        }, global.PosV3.cartPricing.getPosPricingSnapshot(product), extractUnitData(product));
    }

    global.PosV3.cartItemBuilder = {
        buildFromProduct: function buildFromProduct(product, variantPayload) {
            if (!product) {
                return null;
            }

            const isVariantProduct = !!(
                product.has_variants &&
                (
                    product.product_variant_id ||
                    product.variant_combination_key ||
                    product.variant_name ||
                    (variantPayload && variantPayload.variant_combination_key)
                )
            );

            if (isVariantProduct) {
                return buildVariantCartItem(product, variantPayload || product);
            }

            return buildSimpleCartItem(product);
        },
        findExistingItem: function findExistingItem(items, incoming) {
            const trimUnit = function (value) {
                return value != null && String(value).trim() !== '' ? String(value).trim() : '';
            };
            const normVariantKey = function (value) {
                return value == null || String(value).trim() === '' ? '' : String(value).trim();
            };
            const normVariantId = function (value) {
                if (value == null || value === '' || value === false) {
                    return null;
                }

                const number = Number(value);
                if (!Number.isFinite(number) || number <= 0) {
                    return null;
                }

                return number;
            };
            const pidEq = function (a, b) {
                return String(a ?? '') === String(b ?? '');
            };

            const unitCode = trimUnit(incoming.unit_code);
            let existing = null;

            if (unitCode !== '') {
                existing = items.find(function (item) {
                    return pidEq(item.product_id, incoming.product_id) && trimUnit(item.unit_code) === unitCode;
                });
            }

            if (existing) {
                return existing;
            }

            return items.find(function (item) {
                if (!pidEq(item.product_id, incoming.product_id)) {
                    return false;
                }

                const keyA = normVariantKey(item.variant_combination_key);
                const keyB = normVariantKey(incoming.variant_combination_key);
                if (keyA !== '' || keyB !== '') {
                    return keyA === keyB;
                }

                const unitA = trimUnit(item.unit_code);
                const unitB = trimUnit(incoming.unit_code);
                if (unitA !== '' && unitB !== '' && unitA !== unitB) {
                    return false;
                }

                return normVariantId(item.variant_id) === normVariantId(incoming.variant_id);
            }) || null;
        },
    };
})(window);
