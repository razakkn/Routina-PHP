<?php ob_start(); ?>

<div class="routina-wrap">
    <div class="routina-header">
       <div>
           <div class="routina-title">Family Tree</div>
           <div class="routina-sub">Manage family members and relations.</div>
       </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger mt-3">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="grid">
        <div class="card">
            <div class="card-kicker">Add Member</div>
            <form method="post" class="mt-3">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input name="name" class="form-control" required />
                </div>
                <div class="row g-3 mb-3">
                     <div class="col-6">
                        <label class="form-label">Relation</label>
                        <select name="relation" class="form-select">
                            <option value="Spouse">Spouse</option>
                            <option value="Child">Child</option>
                            <option value="Parent">Parent</option>
                            <option value="Sibling">Sibling</option>
                        </select>
                    </div>
                     <div class="col-6">
                        <label class="form-label">Birthdate</label>
                        <input name="birthdate" type="date" class="form-control" required />
                    </div>
                </div>
                <button class="btn btn-primary w-100">Add Member</button>
            </form>
        </div>

        <?php foreach($members as $m): ?>
            <div class="card">
                <div class="card-kicker"><?php echo htmlspecialchars($m['relation']); ?></div>
                <div class="card-title"><?php echo htmlspecialchars($m['name']); ?></div>
                <div class="muted">
                    Born: <?php echo date('M d, Y', strtotime($m['birthdate'])); ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($members)): ?>
            <div class="card d-flex align-items-center justify-content-center text-muted p-5">
                No family members added.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
$content = ob_get_clean(); 
include __DIR__ . '/../layouts/main.php'; 
?>
