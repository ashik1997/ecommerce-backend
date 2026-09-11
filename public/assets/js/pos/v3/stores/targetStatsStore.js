(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};

    global.PosV3.useTargetStatsStore = Pinia.defineStore('posV3TargetStats', {
        state: function () {
            return {
                stats: null,
            };
        },
        actions: {
            load: function () {
                const configStore = global.PosV3.useConfigStore();
                const uiStore = global.PosV3.useUiStore();
                const route = configStore.routes.targetStats;

                if (!route) {
                    return Promise.resolve();
                }

                uiStore.setLoading('targetStats', true);

                return global.PosV3.api.get(route)
                    .then(function (response) {
                        this.stats = response.data && response.data.success && response.data.data
                            ? response.data.data
                            : null;
                    }.bind(this))
                    .catch(function () {
                        this.stats = null;
                    }.bind(this))
                    .finally(function () {
                        uiStore.setLoading('targetStats', false);
                    });
            },
        },
    });
})(window);
