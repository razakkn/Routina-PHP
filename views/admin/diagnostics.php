<?php ob_start(); ?>

<?php
    $snapshot = is_array($snapshot ?? null) ? $snapshot : [];
    $db = is_array($snapshot['db'] ?? null) ? $snapshot['db'] : [];
    $process = is_array($snapshot['process'] ?? null) ? $snapshot['process'] : [];
    $tables = is_array($snapshot['tables'] ?? null) ? $snapshot['tables'] : [];
    $logs = is_array($snapshot['logs'] ?? null) ? $snapshot['logs'] : [];
    $config = is_array($snapshot['config'] ?? null) ? $snapshot['config'] : [];

    $canConnect = !empty($db['can_connect']);
    $driver = (string)($db['driver'] ?? '');

    $modules = [
        'Auth' => ['users', 'password_resets', 'user_social_accounts', 'user_alternative_emails'],
        'Journal' => ['journal_entries'],
        'Calendar' => ['calendar_events'],
        'Vacation' => ['vacations', 'vacation_notes', 'vacation_checklist_items'],
        'Finance' => ['transactions', 'finance_assets', 'finance_bills', 'finance_budgets', 'finance_income', 'finance_incomes', 'finance_diary', 'finance_savings', 'finance_reflections'],
        'Vehicle' => ['vehicles', 'vehicle_vendors', 'vehicle_parts', 'vehicle_maintenance', 'vehicle_documents', 'vehicle_events', 'vehicle_plans'],
        'Home' => ['home_tasks'],
        'Health' => ['health_entries', 'health_records'],
        'Family' => ['family_members'],
        'Buzz' => ['buzz_requests'],
    ];

    $okBadge = function (bool $ok): string {
        $cls = $ok ? 'badge bg-success' : 'badge bg-danger';
        $txt = $ok ? 'OK' : 'Missing';
        return '<span class="' . $cls . '">' . $txt . '</span>';
    };

    $moduleStatus = [];
    foreach ($modules as $name => $tbls) {
        $missing = [];
        foreach ($tbls as $t) {
            $row = is_array($tables[$t] ?? null) ? $tables[$t] : null;
            $exists = $row ? !empty($row['exists']) : false;
            if (!$exists) $missing[] = $t;
        }
        $moduleStatus[$name] = [
            'ok' => empty($missing),
            'missing' => $missing,
        ];
    }
?>

<div class="routina-wrap">
    <div class="routina-header">
        <div>
            <div class="routina-title">Admin Diagnostics</div>
            <div class="routina-sub">Admin-only system checks for troubleshooting modules (no user profile inspection).</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="/admin/metrics">Metrics</a>
            <a class="btn btn-outline-secondary btn-sm" href="/admin/autofill">Autofill</a>
        </div>
    </div>

    <div class="card" style="grid-column: span 2; max-width: 1000px;">
        <div class="card-kicker">Environment</div>
        <div class="mt-2">DB connectivity: <?php echo $canConnect ? 'true' : 'false'; ?> <?php echo $canConnect ? '<span class="badge bg-success">OK</span>' : '<span class="badge bg-danger">Fail</span>'; ?></div>
        <div>DB driver: <?php echo htmlspecialchars($driver !== '' ? $driver : 'unknown'); ?></div>
        <?php if (!empty($db['error'])): ?>
            <div class="mt-2 text-danger">DB error: <?php echo htmlspecialchars((string)$db['error']); ?></div>
        <?php endif; ?>

        <div class="mt-3">PHP Version: <?php echo htmlspecialchars((string)($process['php_version'] ?? '')); ?></div>
        <div>Server Time: <?php echo htmlspecialchars((string)($process['server_time'] ?? '')); ?></div>
        <div>Memory: <?php echo htmlspecialchars((string)($process['memory_usage'] ?? '')); ?> (peak <?php echo htmlspecialchars((string)($process['peak_memory_usage'] ?? '')); ?>)</div>

        <div class="mt-3">config/config.php present: <?php echo !empty($config['config_php_present']) ? 'true' : 'false'; ?></div>
    </div>

    <div class="card" style="grid-column: span 2; max-width: 1000px;">
        <div class="card-kicker">Module Readiness (schema)</div>
        <?php if (!$canConnect): ?>
            <div class="mt-2 text-muted">Database not reachable; schema checks are unavailable.</div>
        <?php else: ?>
            <div class="mt-2 text-muted">Checks that required tables exist. If something is missing, run the schema setup script.</div>
            <table class="table table-sm mt-3">
                <thead>
                    <tr>
                        <th>Module</th>
                        <th>Status</th>
                        <th>Missing tables</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($moduleStatus as $name => $st): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($name); ?></td>
                            <td><?php echo $okBadge((bool)$st['ok']); ?></td>
                            <td class="text-muted"><?php echo htmlspecialchars(implode(', ', $st['missing'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <details class="mt-2">
                <summary>Raw table status</summary>
                <pre class="mt-2" style="background:#0b1020;color:#dfe7ff;padding:1rem;border-radius:8px;overflow-x:auto;"><?php echo htmlspecialchars(json_encode($tables, JSON_PRETTY_PRINT)); ?></pre>
            </details>
        <?php endif; ?>
    </div>

    <div class="card" style="grid-column: span 2; max-width: 1000px;">
        <div class="card-kicker">Recent Error Log (tail)</div>
        <div class="text-muted">File: <?php echo htmlspecialchars((string)($logs['error_log_path'] ?? '')); ?></div>
        <?php $tail = (string)($logs['error_log_tail'] ?? ''); ?>
        <?php if ($tail === ''): ?>
            <div class="mt-2 text-muted">No error log found or it is empty.</div>
        <?php else: ?>
            <pre class="mt-3" style="background:#0b1020;color:#dfe7ff;padding:1rem;border-radius:8px;overflow-x:auto;max-height:320px;"><?php echo htmlspecialchars($tail); ?></pre>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
