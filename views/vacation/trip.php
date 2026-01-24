<?php ob_start(); ?>

<div class="routina-wrap">
    <div class="routina-header">
       <div>
           <div class="routina-title">Trip: <?php echo htmlspecialchars($vacation['destination']); ?></div>
           <div class="routina-sub">
               <?php echo date('M d', strtotime($vacation['start_date'])); ?> -
               <?php echo date('M d, Y', strtotime($vacation['end_date'])); ?>
           </div>
           <?php if (!empty($vacation['budget'])): ?>
               <div class="routina-sub">Budget: <?php echo number_format((float)$vacation['budget'], 2); ?></div>
           <?php endif; ?>
           <?php if (!empty($vacation['notes'])): ?>
               <div class="routina-sub"><?php echo htmlspecialchars($vacation['notes']); ?></div>
           <?php endif; ?>
       </div>
       <div>
           <a class="btn btn-outline-secondary btn-sm" href="/vacation/edit?id=<?php echo (int)$vacation['id']; ?>">Edit Trip</a>
       </div>
    </div>

    <div class="grid">
        <div class="card">
            <div class="card-kicker">Checklist</div>
            <form method="post" class="mt-3">
                <?= csrf_field() ?>
                <div class="input-group">
                    <input name="checklist_text" class="form-control" placeholder="Passport, tickets, hotel..." />
                    <button class="btn btn-primary" type="submit">Add</button>
                </div>
            </form>

            <ul class="list-group list-group-flush mt-3">
                <?php foreach ($checklist as $item): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <input class="form-check-input me-2" type="checkbox" onchange="this.parentElement.parentElement.querySelector('form').submit()" <?php echo $item['is_done'] ? 'checked' : ''; ?>>
                            <span class="<?php echo $item['is_done'] ? 'text-decoration-line-through text-muted' : ''; ?>">
                                <?php echo htmlspecialchars($item['text']); ?>
                            </span>
                        </div>
                        <form method="post" style="display:none;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="toggle_checklist_id" value="<?php echo $item['id']; ?>">
                        </form>
                    </li>
                <?php endforeach; ?>
                <?php if (empty($checklist)): ?>
                    <li class="list-group-item text-muted">No checklist items yet.</li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="card" style="grid-column: span 2;">
            <div class="card-kicker">Trip Notes</div>
            <form method="post" class="mt-3">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Title (optional)</label>
                    <input name="note_title" class="form-control" placeholder="Hotel confirmation" />
                </div>
                <div class="mb-3">
                    <label class="form-label">Note</label>
                    <textarea name="note_body" class="form-control" rows="3" placeholder="Add details about your trip..." required></textarea>
                </div>
                <button class="btn btn-primary">Save Note</button>
            </form>

            <div class="mt-4">
                <?php foreach ($notes as $note): ?>
                    <div class="border rounded p-3 mb-3">
                        <div class="fw-semibold">
                            <?php echo htmlspecialchars($note['title'] ?: 'Trip note'); ?>
                        </div>
                        <div class="text-muted small mb-2">
                            <?php echo htmlspecialchars($note['created_at']); ?>
                        </div>
                        <div><?php echo nl2br(htmlspecialchars($note['body'])); ?></div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($notes)): ?>
                    <div class="text-muted">No notes yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean(); 
include __DIR__ . '/../layouts/main.php'; 
?>
