(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};

    function positiveOrMain(value, main) {
        const parsed = Number(value);
        const fallback = Number(main || 0);

        return parsed > 0 ? parsed : fallback;
    }

    function resolveTierPrice(primary, secondary, main) {
        const primaryValue = positiveOrMain(primary, 0);
        if (primaryValue > 0) {
            return primaryValue;
        }

        const secondaryValue = positiveOrMain(secondary, 0);
        if (secondaryValue > 0) {
            return secondaryValue;
        }

        return Number(main || 0);
    }

    global.PosV3.cartPricing = {
        getPosPricingSnapshot: function getPosPricingSnapshot(product) {
            const main = Number(product.main_price != null ? product.main_price : product.unit_price || 0);
            const wholesale = resolveTierPrice(product.wholesale_price, null, main);
            const retail = resolveTierPrice(product.retail_price, null, main);
            const discountPrice = Number(product.discount_price || 0);
            let catalog = { percent: 0, fixed: 0, value: 0 };

            if (discountPrice > 0 && discountPrice < main) {
                catalog = {
                    type: 'fixed',
                    percent: 0,
                    fixed: Math.max(0, main - discountPrice),
                    value: Math.max(0, main - discountPrice),
                    amount: 0,
                };
            } else if (product.discount && typeof product.discount === 'object') {
                catalog = global.PosV3.cloneDeep(product.discount);
            }

            return {
                pos_main_price: main,
                pos_wholesale_price: wholesale,
                pos_retail_price: retail,
                pos_catalog_discount: catalog,
            };
        },
        applyPricingForSelectedType: function applyPricingForSelectedType(item, priceType) {
            const main = Number(
                item.pos_main_price != null
                    ? item.pos_main_price
                    : item.main_price != null
                        ? item.main_price
                        : item.unit_price || 0
            );
            const wholesale = resolveTierPrice(
                item.pos_wholesale_price,
                item.wholesale_price,
                main
            );
            const retail = resolveTierPrice(
                item.pos_retail_price,
                item.retail_price,
                main
            );
            const catalog = item.pos_catalog_discount || { percent: 0, fixed: 0, value: 0 };

            if (priceType === 'wholesale_price') {
                item.unit_price = wholesale;
                item.discount = { type: 'fixed', percent: 0, fixed: 0, value: 0, amount: 0 };
            } else if (priceType === 'retail_price') {
                item.unit_price = retail;
                item.discount = { type: 'fixed', percent: 0, fixed: 0, value: 0, amount: 0 };
            } else {
                item.unit_price = main;

                const percent = Number(catalog.percent || 0);
                const fixed = Number(catalog.fixed || 0);

                if (percent > 0) {
                    item.discount = {
                        type: 'percent',
                        percent: percent,
                        fixed: fixed,
                        value: percent,
                        amount: 0,
                    };
                } else if (fixed > 0) {
                    item.discount = {
                        type: 'fixed',
                        percent: 0,
                        fixed: fixed,
                        value: fixed,
                        amount: 0,
                    };
                } else {
                    item.discount = {
                        type: 'fixed',
                        percent: 0,
                        fixed: 0,
                        value: 0,
                        amount: 0,
                    };
                }
            }

            global.PosV3.cartPricing.recalcCartItem(item);
        },
        recalcCartItem: function recalcCartItem(item) {
            const qty = Number(item.qty || 0);
            const unitPrice = Number(item.unit_price || 0);

            if (!item.discount) {
                item.discount = {
                    type: 'percent',
                    percent: 0,
                    fixed: 0,
                    value: 0,
                    amount: 0,
                };
            }

            let discountPerUnit = 0;

            if (item.discount.type === 'percent') {
                const percent = Math.max(0, Number(item.discount.percent || 0));
                discountPerUnit = unitPrice * (percent / 100);
                item.discount.value = percent;
                item.discount.fixed = Math.round(discountPerUnit);
            }

            if (item.discount.type === 'fixed') {
                discountPerUnit = Math.max(0, Number(item.discount.fixed || 0));
                discountPerUnit = Math.min(discountPerUnit, unitPrice);
                item.discount.value = discountPerUnit;
                item.discount.percent = unitPrice > 0
                    ? Number(((discountPerUnit / unitPrice) * 100).toFixed(1))
                    : 0;
            }

            const roundedDiscountPerUnit = Math.round(discountPerUnit);
            const finalUnitPrice = Math.max(0, Math.round(Math.max(0, unitPrice - roundedDiscountPerUnit) / 5) * 5);
            const finalDiscountPerUnit = Math.max(0, unitPrice - finalUnitPrice);

            item.discount.fixed = finalDiscountPerUnit;
            item.discount_price = finalUnitPrice;
            item.final_price = finalUnitPrice * qty;
            item.line_total = finalUnitPrice * qty;
            item.discount.amount = finalDiscountPerUnit * qty;
        },
    };
})(window);
