(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};
    global.PosV3.components = global.PosV3.components || {};

    global.PosV3.components.PosV3Clock = {
        name: 'PosV3Clock',
        template: `
            <div class="pos-v3-clock">
                <div class="pos-v3-clock__card">
                    <small>Date</small>
                    <strong>{{ nowDate }}</strong>
                </div>
                <div class="pos-v3-clock__card">
                    <small>Time</small>
                    <strong>{{ nowTime }}</strong>
                </div>
                <div class="pos-v3-clock__card pos-v3-clock__card--hours">
                    <small>Hours left today</small>
                    <strong>{{ hoursLeftInDay }} hrs</strong>
                </div>
            </div>
        `,
        data: function () {
            return {
                clockNow: Date.now(),
                clockInterval: null,
            };
        },
        computed: {
            nowDate: function () {
                const date = new Date(this.clockNow);

                return date.toLocaleDateString(undefined, {
                    weekday: 'short',
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                });
            },
            nowTime: function () {
                const date = new Date(this.clockNow);

                return date.toLocaleTimeString(undefined, {
                    hour12: true,
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                });
            },
            hoursLeftInDay: function () {
                const now = new Date(this.clockNow);
                const end = new Date(now);

                end.setHours(23, 59, 59, 999);

                return Math.max(0, (end - now) / (1000 * 60 * 60)).toFixed(1);
            },
        },
        mounted: function () {
            this.clockInterval = setInterval(function () {
                this.clockNow = Date.now();
            }.bind(this), 1000);
        },
        beforeUnmount: function () {
            if (this.clockInterval) {
                clearInterval(this.clockInterval);
            }
        },
    };
})(window);
