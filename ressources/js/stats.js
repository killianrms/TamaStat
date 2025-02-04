document.addEventListener('DOMContentLoaded', function () {
    // Initialisation des graphiques
    initCharts();

    function initCharts() {
        new Chart(document.getElementById('revenueChart'), {
            type: 'bar',
            data: {
                labels: statsData.labels.map(label => label + ' m³'),
                datasets: [{
                    label: 'Revenu (€)',
                    data: statsData.revenus,
                    backgroundColor: '#0072bc',
                    borderColor: '#005f9e',
                    borderWidth: 2
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
        });

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

        document.querySelector('.stats-container').innerHTML =
            new DOMParser().parseFromString(html, 'text/html')
                .querySelector('.stats-container').innerHTML;

        initCharts();
    });
});