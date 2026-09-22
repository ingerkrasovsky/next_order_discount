/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a custom commercial license.
 * You may not redistribute, resell, sublicense, or share this file.
 * One license is valid for one installation (one store).
 *
 * For full license terms, contact: info@setecom.tech
 *
 * @author    Smart Ecommerce Tech
 * @copyright 2026 Smart Ecommerce Tech
 * @license   Commercial License
 */

/**
 * Renders the Dashboard "Daily dynamics" chart with Chart.js (bundled locally).
 *
 * All three metrics share one axis: Generated, Emailed and Used are nested the same
 * way set_loyalty_milestones' impressions/reached/orders are — every use needs an
 * email, every email needs a generated coupon — so a chart that shows one above the
 * other is simply wrong. Only Generated is filled; the other two run inside it.
 */
(function () {
    'use strict';

    function lineDataset(label, data, color, fill) {
        return {
            label: label,
            data: data,
            yAxisID: 'y',
            borderColor: color,
            backgroundColor: color + '14',
            borderWidth: 2,
            tension: 0.3,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: color,
            fill: !!fill
        };
    }

    function initDailyChart() {
        var canvas = document.getElementById('snod-dash-chart');
        if (!canvas || typeof Chart === 'undefined' || !window.snodDashChart) {
            return;
        }

        var d = window.snodDashChart;
        var i18n = d.i18n || {};

        new Chart(canvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: d.labels || [],
                datasets: [
                    lineDataset(i18n.generated || 'Generated', d.generated || [], '#25b9d7', true),
                    lineDataset(i18n.emailed || 'Emailed', d.emailed || [], '#72c279', false),
                    lineDataset(i18n.used || 'Used', d.used || [], '#fbbb22', false)
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8, padding: 16 } },
                    tooltip: { enabled: true, usePointStyle: true }
                },
                scales: {
                    x: { grid: { display: false } },
                    // One shared scale: the axis carries no label, the legend names the metrics.
                    y: {
                        type: 'linear',
                        position: 'left',
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    }
                }
            }
        });
    }

    function init() {
        initDailyChart();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
