document.addEventListener('DOMContentLoaded', function () {
    // Graphique 1 : Revenu par taille de box
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

    // Graphique 2 : Taux d'occupation
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

    // Graphique 3 : Répartition des locations
    new Chart(document.getElementById('distributionChart'), {
        type: 'pie',
        data: {
            labels: statsData.labels.map(label => label + ' m³'),
            datasets: [{
                data: statsData.occupation,
                backgroundColor: ['#0072bc', '#ff6600', '#2c3e50', '#4CAF50', '#9C27B0']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                tooltip: { callbacks: { label: (ctx) => ctx.raw + ' locations' } }
            }
        }
    });

    // Graphique 4 : Capacité utilisée
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
});