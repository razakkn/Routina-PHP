(function () {
    const dataEl = document.getElementById('metrics-latency-data');
    if (!dataEl) return;

    let recent = [];
    try {
        recent = JSON.parse(dataEl.textContent || '[]');
        if (!Array.isArray(recent)) recent = [];
    } catch {
        recent = [];
    }

    const canvas = document.getElementById('latencyChart');
    if (!canvas || typeof Chart === 'undefined') return;

    const ctx = canvas.getContext('2d');
    // eslint-disable-next-line no-new
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: recent.map((_, i) => i + 1),
            datasets: [
                {
                    label: 'Request latency (ms)',
                    data: recent,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.2,
                    fill: false,
                },
            ],
        },
        options: {
            scales: { y: { beginAtZero: true } },
        },
    });
})();
