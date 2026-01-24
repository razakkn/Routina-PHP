<?php ob_start(); ?>

<div class="routina-wrap">
    <div class="routina-header">
       <div>
           <div class="routina-title">Vendors</div>
           <div class="routina-sub">Manage shops and suppliers.</div>
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
            <div class="card-kicker">Add Vendor</div>
            <form method="post" class="mt-3">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input name="name" class="form-control" placeholder="AutoFix Garage" required />
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label">Phone</label>
                        <input name="phone" class="form-control" />
                    </div>
                    <div class="col-6">
                        <label class="form-label">Email</label>
                        <input name="email" type="email" class="form-control" />
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <input name="notes" class="form-control" />
                </div>
                <button class="btn btn-primary w-100">Save Vendor</button>
            </form>
        </div>

        <div class="card" style="grid-column: span 2;">
            <div class="card-kicker">Vendors</div>
            <?php if (empty($vendors)): ?>
                <div class="text-muted text-center py-4">No vendors yet.</div>
            <?php else: ?>
                <div class="table-responsive mt-3">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vendors as $v): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($v['name']); ?></td>
                                    <td><?php echo htmlspecialchars($v['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($v['email']); ?></td>
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
