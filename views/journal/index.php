<?php ob_start(); ?>

<div class="routina-wrap">
    <div class="routina-header">
       <div>
           <div class="routina-title">Daily Journal</div>
           <div class="routina-sub">Capture your thoughts and moments.</div>
       </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="/journal/today">Today</a>
            <a class="btn btn-outline-secondary btn-sm" href="/journal/history">History</a>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger mt-3">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="grid">
        <div class="card" style="grid-column: span 2;">
            <div class="card-kicker">New Entry</div>
            <form method="post" class="mt-3">
                <?= csrf_field() ?>
                <div class="row g-3">
                     <div class="col-md-3">
                        <label class="form-label">Date</label>
                        <input name="entry_date" type="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Mood</label>
                        <select name="mood" class="form-select">
                            <option value="Happy">😊 Happy</option>
                            <option value="Calm">😌 Calm</option>
                            <option value="Productive">⚡ Productive</option>
                            <option value="Tired">🥱 Tired</option>
                            <option value="Stressed">😫 Stressed</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                     <label class="form-label">Thoughts</label>
                     <textarea name="content" class="form-control" rows="4" placeholder="How was your day?" required></textarea>
                </div>
                <button class="btn btn-primary mt-3">Save Entry</button>
            </form>
        </div>

        <?php foreach($entries as $entry): ?>
            <div class="thought">
                <div class="d-flex justify-content-between align-items-center mb-2">
                     <div class="thought-kicker"><?php echo htmlspecialchars($entry['entry_date']); ?></div>
                     <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($entry['mood']); ?></span>
                </div>
                <div class="thought-text">
                    <?php echo nl2br(htmlspecialchars($entry['content'])); ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (empty($entries)): ?>
             <div class="text-muted text-center py-5">
                 No journal entries yet. Start writing today!
             </div>
        <?php endif; ?>
    </div>
</div>

<?php 
$content = ob_get_clean(); 
include __DIR__ . '/../layouts/main.php'; 
?>
