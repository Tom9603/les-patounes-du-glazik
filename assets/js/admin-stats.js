import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);

document.addEventListener('DOMContentLoaded', () => {
    const revenueEl = document.getElementById('chart-revenue');
    const bookingsEl = document.getElementById('chart-bookings');

    if (revenueEl) {
        const data = JSON.parse(revenueEl.dataset.chart);
        new Chart(revenueEl, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Chiffre d\'affaires (€)',
                    data: data.values,
                    backgroundColor: 'rgba(124, 92, 191, 0.7)',
                    borderColor: 'rgba(124, 92, 191, 1)',
                    borderWidth: 1,
                    borderRadius: 6,
                }],
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } },
            },
        });
    }

    if (bookingsEl) {
        const data = JSON.parse(bookingsEl.dataset.chart);
        new Chart(bookingsEl, {
            type: 'doughnut',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.values,
                    backgroundColor: [
                        'rgba(124, 92, 191, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                    ],
                    borderWidth: 2,
                }],
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } },
            },
        });
    }
});
