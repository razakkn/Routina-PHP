<?php ob_start(); ?>

<div class="routina-wrap">
    <div class="routina-header">
       <div>
           <div class="routina-title">Home Management</div>
           <div class="routina-sub">Household chores and tasks.</div>
       </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger mt-3">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="grid">
        <div class="card">
            <div class="card-kicker">New Task</div>
            <form method="post" class="mt-3">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Task Name</label>
                    <input name="title" class="form-control" placeholder="Mow the lawn" required />
                </div>
                <div class="row g-3 mb-3">
                     <div class="col-6">
                        <label class="form-label">Frequency</label>
                        <select name="frequency" class="form-select">
                            <option value="One-time">One-time</option>
                            <option value="Daily">Daily</option>
                            <option value="Weekly">Weekly</option>
                        </select>
                    </div>
                     <div class="col-6">
                        <label class="form-label">Assignee</label>
                        <input name="assignee" class="form-control" placeholder="Name" />
                    </div>
                </div>
                <button class="btn btn-primary w-100">Add Task</button>
            </form>
        </div>

        <div class="card" style="grid-column: span 2;">
            <div class="card-kicker">Tasks</div>
            <ul class="list-group list-group-flush mt-3">
                <?php foreach($tasks as $t): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <input class="form-check-input me-2" type="checkbox" onchange="this.parentElement.parentElement.querySelector('form').submit()" <?php echo $t['is_completed'] ? 'checked' : ''; ?>>
                            <span class="<?php echo $t['is_completed'] ? 'text-decoration-line-through text-muted' : ''; ?>">
                                <?php echo htmlspecialchars($t['title']); ?>
                            </span>
                            <small class="text-muted ms-2"><?php echo htmlspecialchars($t['assigned_to']); ?> - <?php echo htmlspecialchars($t['frequency']); ?></small>
                        </div>
                         <!-- Hidden form for toggle -->
                        <form method="post" style="display:none;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="toggle_id" value="<?php echo $t['id']; ?>">
                        </form>
                    </li>
                <?php endforeach; ?>
                 <?php if (empty($tasks)): ?>
                    <li class="list-group-item text-muted">No tasks active.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean(); 
include __DIR__ . '/../layouts/main.php'; 
?>
