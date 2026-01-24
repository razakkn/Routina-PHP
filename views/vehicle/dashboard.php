<?php ob_start(); ?>

<div class="routina-wrap">
    <div class="routina-header">
       <div>
           <div class="routina-title">Vehicle Dashboard</div>
           <div class="routina-sub">Quick overview of your fleet.</div>
       </div>
       <div>
           <a class="btn btn-outline-secondary btn-sm" href="/vehicle">Back</a>
       </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a class="btn btn-outline-secondary btn-sm" href="/vehicle/maintenance">Maintenance</a>
        <a class="btn btn-outline-secondary btn-sm" href="/vehicle/events">Events</a>
        <a class="btn btn-outline-secondary btn-sm" href="/vehicle/plans">Plans</a>
        <a class="btn btn-outline-secondary btn-sm" href="/vehicle/parts">Parts</a>
        <a class="btn btn-outline-secondary btn-sm" href="/vehicle/vendors">Vendors</a>
        <a class="btn btn-outline-secondary btn-sm" href="/vehicle/documents">Documents</a>
    </div>

    <div class="grid">
        <?php if (empty($vehicles)): ?>
            <div class="card d-flex align-items-center justify-content-center text-muted p-5">
                No vehicles yet.
            </div>
        <?php else: ?>
            <?php foreach ($vehicles as $v): ?>
                <div class="card">
                    <div class="card-kicker"><?php echo htmlspecialchars($v['status']); ?></div>
                    <div class="card-title"><?php echo htmlspecialchars($v['year'] . ' ' . $v['make'] . ' ' . $v['model']); ?></div>
                    <div class="muted">Plate: <?php echo htmlspecialchars($v['license_plate']); ?></div>
                    <div class="card-buttons">
                        <a href="/vehicle/edit?id=<?php echo $v['id']; ?>" class="btn-soft">Edit</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
