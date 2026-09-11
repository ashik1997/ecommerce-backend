(function (global) {
    'use strict';

    function mountPosV3App() {
        if (!global.Vue || !global.Pinia || !global.PosV3) {
            console.error('[POS v3] Vue 3, Pinia, or PosV3 namespace is missing.');
            return;
        }

        const { createApp } = global.Vue;
        const { createPinia } = global.Pinia;
        const rootComponent = global.PosV3.components && global.PosV3.components.PosV3App;

        if (!rootComponent) {
            console.error('[POS v3] Root component PosV3App is not registered.');
            return;
        }

        const mountEl = document.getElementById('pos-v3-app');

        if (!mountEl) {
            console.error('[POS v3] Mount element #pos-v3-app was not found.');
            return;
        }

        const app = createApp(rootComponent);
        const pinia = createPinia();

        app.use(pinia);
        global.PosV3.registerComponents(app);

        const bootstrapPromise = global.PosV3.bootstrapStores();

        app.mount(mountEl);

        if (global.PosV3.afterMount) {
            global.PosV3.afterMount();
        }

        if (bootstrapPromise && typeof bootstrapPromise.catch === 'function') {
            bootstrapPromise.catch(function (error) {
                console.warn('[POS v3] Bootstrap failed', error);
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', mountPosV3App);
    } else {
        mountPosV3App();
    }
})(window);
