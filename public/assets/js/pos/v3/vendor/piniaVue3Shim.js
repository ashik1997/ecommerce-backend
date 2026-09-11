(function (global) {
    'use strict';

    if (!global.Vue) {
        console.error('[POS v3] Vue must load before the Pinia Vue 3 shim.');
        return;
    }

    // Pinia's IIFE bundle expects vue-demi as a global when used via CDN.
    global.VueDemi = global.Vue;
})(window);
