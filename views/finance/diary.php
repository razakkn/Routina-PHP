<?php ob_start(); ?>

<div class="routina-wrap">
    <div class="routina-header">
       <div>
           <div class="routina-title">Finance Diary</div>
           <div class="routina-sub">Record financial thoughts and notes.</div>
       </div>
       <div>
           <a class="btn btn-outline-secondary btn-sm" href="/finance">Back</a>
       </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger mt-3">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <link rel="stylesheet" href="/css/master-detail.css" />

    <div class="md-shell" data-module="finance-diary">
        <div class="md-list card">
            <div class="md-list-header">
                <div>
                    <div class="card-kicker">Entries</div>
                    <div class="text-muted small">Tap an entry to view details.</div>
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm md-new-btn" data-detail-template="diary-new">
                    New Entry
                </button>
            </div>

            <?php if (empty($entries)): ?>
                <div class="text-muted text-center py-4">No entries yet.</div>
            <?php else: ?>
                <div class="list-group list-group-flush mt-3 md-list-items">
                    <?php foreach ($entries as $e): ?>
                        <?php
                            $preview = trim((string)($e['preview'] ?? ''));
                            if ($preview !== '' && mb_strlen($preview) >= 140) {
                                $preview .= '…';
                            }
                        ?>
                        <button type="button"
                                class="list-group-item list-group-item-action md-item"
                                data-detail-url="/finance/diary/detail?id=<?php echo (int)$e['id']; ?>">
                            <div class="fw-semibold"><?php echo htmlspecialchars($e['entry_date']); ?></div>
                            <div class="text-muted mt-1"><?php echo htmlspecialchars($preview); ?></div>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="md-detail card">
            <div class="md-detail-top">
                <button type="button" class="btn btn-outline-secondary btn-sm md-back">Back</button>
            </div>
            <div class="md-detail-content">
                <div class="text-muted">Select an entry to see details.</div>
            </div>
        </div>
    </div>

    <div id="md-template-diary-new" class="md-template" hidden>
        <div class="card-kicker">New Entry</div>
        <form method="post" class="mt-3">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Date</label>
                <input name="entry_date" type="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required />
            </div>
            <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="4" required></textarea>
            </div>
            <button class="btn btn-primary w-100">Save Entry</button>
        </form>
    </div>

    <script src="/js/master-detail.js" defer></script>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
