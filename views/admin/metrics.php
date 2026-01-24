<?php ob_start(); ?>

<div class="routina-wrap">
    <div class="routina-header">
        <div>
            <div class="routina-title">Admin Metrics</div>
            <div class="routina-sub">System health and metrics snapshot.</div>
        </div>
    </div>

    <div class="card" style="grid-column: span 2;">
        <div class="card-kicker">Health Checks</div>
        <div class="mt-2">Status: <?php echo htmlspecialchars($snapshot['health']['status']); ?></div>
        <ul class="mt-2">
            <?php foreach ($snapshot['health']['checks'] as $check): ?>
                <li><?php echo htmlspecialchars($check['name']); ?>: <?php echo htmlspecialchars($check['status']); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="card" style="grid-column: span 2;">
        <div class="card-kicker">Metrics Snapshot</div>
        <pre class="mt-3" style="background: #0b1020; color: #dfe7ff; padding: 1rem; border-radius: 8px; overflow-x: auto;"><?php echo htmlspecialchars(json_encode($snapshot, JSON_PRETTY_PRINT)); ?></pre>
        <canvas id="latencyChart" width="600" height="200"></canvas>
        <div class="mt-3 d-flex gap-2">
            <a class="btn btn-outline-primary btn-sm" href="/metrics/json">Download JSON</a>
            <a class="btn btn-outline-secondary btn-sm" href="/metrics/export/csv">Download CSV</a>
        </div>
    </div>

    <div class="card" style="grid-column: span 2;">
        <div class="card-kicker">Database</div>
        <div class="mt-2">Can connect: <?php echo $snapshot['db']['can_connect'] ? 'true' : 'false'; ?></div>
        <div>File size: <?php echo $snapshot['db']['file_size_bytes'] !== null ? $snapshot['db']['file_size_bytes'] . ' bytes' : 'unknown'; ?></div>
    </div>

    <div class="card" style="grid-column: span 2;">
        <div class="card-kicker">Process</div>
        <div class="mt-2">PHP Version: <?php echo htmlspecialchars($snapshot['process']['php_version']); ?></div>
        <div>Memory: <?php echo htmlspecialchars((string)$snapshot['process']['memory_usage']); ?></div>
        <div>Peak Memory: <?php echo htmlspecialchars((string)$snapshot['process']['peak_memory_usage']); ?></div>
        <div>Server Time: <?php echo htmlspecialchars($snapshot['process']['server_time']); ?></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script type="application/json" id="metrics-latency-data"><?php echo htmlspecialchars(json_encode($snapshot['recent_request_ms']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></script>
<script src="/js/admin-metrics.js"></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
