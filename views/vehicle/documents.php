<?php ob_start(); ?>

<div class="routina-wrap">
    <div class="routina-header">
       <div>
           <div class="routina-title">Documents</div>
           <div class="routina-sub">Store links to registrations, insurance, etc.</div>
       </div>
       <div>
           <a class="btn btn-outline-secondary btn-sm" href="/vehicle/dashboard">Back</a>
       </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger mt-3">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="grid">
        <div class="card">
            <div class="card-kicker">Add Document</div>
            <form method="post" class="mt-3">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Vehicle</label>
                    <select name="vehicle_id" class="form-select" required>
                        <option value="">Select vehicle</option>
                        <?php foreach ($vehicles as $v): ?>
                            <option value="<?php echo $v['id']; ?>"><?php echo htmlspecialchars($v['year'] . ' ' . $v['make'] . ' ' . $v['model']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input name="title" class="form-control" placeholder="Insurance policy" required />
                </div>
                <div class="mb-3">
                    <label class="form-label">File URL</label>
                    <input name="file_url" class="form-control" placeholder="https://..." required />
                </div>
                <button class="btn btn-primary w-100">Save Document</button>
            </form>
        </div>

        <div class="card" style="grid-column: span 2;">
            <div class="card-kicker">Documents</div>
            <?php if (empty($documents)): ?>
                <div class="text-muted text-center py-4">No documents yet.</div>
            <?php else: ?>
                <div class="table-responsive mt-3">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Vehicle</th>
                                <th>Uploaded</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documents as $doc): ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo htmlspecialchars($doc['file_url']); ?>" target="_blank" rel="noopener">
                                            <?php echo htmlspecialchars($doc['title']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php
                                            $vehicleLabel = '';
                                            foreach ($vehicles as $v) {
                                                if ($v['id'] == $doc['vehicle_id']) {
                                                    $vehicleLabel = $v['year'] . ' ' . $v['make'] . ' ' . $v['model'];
                                                    break;
                                                }
                                            }
                                            echo htmlspecialchars($vehicleLabel);
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($doc['uploaded_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
