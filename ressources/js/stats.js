document.addEventListener('DOMContentLoaded', function () {
    let charts = [];

    function destroyCharts() {
        charts.forEach(chart => chart.destroy());
        charts = [];
    }


    function initCharts() {
        // Fonction de génération de couleurs
        const generateColors = (count) => {
            const colors = [];
            const hueStep = 360 / count;
            for (let i = 0; i < count; i++) {
                colors.push(`hsl(${hueStep * i}, 70%, 50%)`);
            }
            return colors;
        };

        charts.push(new Chart(document.getElementById('revenueChart'), {
            type: 'bar',
            data: {
                labels: statsData.labels.map(label => label + ' m³'),
                datasets: [{
                    label: 'Revenu (€)',
                    data: statsData.revenus,
                    backgroundColor: generateColors(statsData.labels.length),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (ctx) => ctx.raw.toFixed(2) + ' €' } }
                },
                scales: { y: { beginAtZero: true } }
            }
        }));

        new Chart(document.getElementById('occupationChart'), {
            type: 'doughnut',
            data: {
                labels: ['Occupé', 'Disponible'],
                datasets: [{
                    data: [statsData.capaciteUtilisee, statsData.capaciteTotale - statsData.capaciteUtilisee],
                    backgroundColor: ['#ff6600', '#0072bc']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    tooltip: { callbacks: { label: (ctx) => ctx.raw.toFixed(2) + ' m³' } }
                }
            }
        });

        new Chart(document.getElementById('distributionChart'), {
            type: 'pie',
            data: {
                labels: statsData.labels.map(label => label + ' m³'),
                datasets: [{
                    data: statsData.occupation,
                    backgroundColor: ['#ff6600', '#0072bc', '#ffcc00', '#009e49', '#ff6600', '#0072bc', '#ffcc00', '#009e49', '#ff6600', '#0072bc', '#ffcc00', '#009e49']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    tooltip: { callbacks: { label: (ctx) => ctx.raw + ' locations' } }
                }
            }
        });

        new Chart(document.getElementById('capacityChart'), {
            type: 'line',
            data: {
                labels: statsData.labels.map(label => label + ' m³'),
                datasets: [{
                    label: 'Capacité utilisée (m³)',
                    data: statsData.labels.map((label, index) => statsData.occupation[index] * label),
                    borderColor: '#ff6600',
                    fill: false
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    tooltip: { callbacks: { label: (ctx) => ctx.raw.toFixed(2) + ' m³' } }
                },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    document.querySelector('form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(e.target);
        const params = new URLSearchParams(formData);

        const response = await fetch(`routeur.php?route=stats&${params}`);
        const html = await response.text();

        const parser = new DOMParser();
        const newDoc = parser.parseFromString(html, 'text/html');

        const newStatsData = JSON.parse(newDoc.querySelector('script').innerHTML.replace('const statsData = ', '').replace(';', ''));
        Object.assign(statsData, newStatsData);

        destroyCharts();
        initCharts();
    });

    initCharts();
});