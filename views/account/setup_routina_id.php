<?php ob_start(); ?>

<div class="routina-wrap" style="max-width: 500px; margin: 60px auto;">
    <div class="card">
        <div class="card-kicker">Choose Your Routina ID</div>
        <div class="routina-title">Create Your Identity</div>
        <p class="text-muted mt-2">Your Routina ID is your unique identifier in the app. It will appear as <strong>@username</strong>.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger mt-3"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" class="mt-4">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Routina ID</label>
                <div class="input-group">
                    <span class="input-group-text">@</span>
                    <input type="text" name="routina_id" id="routina-id-input" class="form-control" 
                           placeholder="yourname" required pattern="[a-z][a-z0-9_]{2,19}"
                           title="3-20 characters, starting with a letter">
                </div>
                <small class="text-muted">3-20 characters. Letters, numbers, and underscores only. Must start with a letter.</small>
            </div>

            <?php if (!empty($suggestions)): ?>
                <div class="mb-3">
                    <label class="form-label">Suggested IDs</label>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($suggestions as $s): ?>
                            <button type="button" class="btn btn-outline-primary btn-sm suggestion-btn" data-id="<?= htmlspecialchars($s) ?>">
                                @<?= htmlspecialchars($s) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div id="availability-status" class="mb-3" style="display: none;"></div>

            <button type="submit" class="btn btn-primary w-100">Confirm ID</button>
        </form>

        <div class="mt-3 text-center">
            <small class="text-muted">You can change this later in your profile settings.</small>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('routina-id-input');
    const status = document.getElementById('availability-status');
    const suggestionBtns = document.querySelectorAll('.suggestion-btn');
    let timeout = null;

    // Click suggestion to fill
    suggestionBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            input.value = this.dataset.id;
            checkAvailability(this.dataset.id);
        });
    });

    // Check availability on input
    input.addEventListener('input', function() {
        const value = this.value.toLowerCase().replace(/[^a-z0-9_]/g, '');
        this.value = value;

        if (timeout) clearTimeout(timeout);
        
        if (value.length < 3) {
            status.style.display = 'none';
            return;
        }

        timeout = setTimeout(() => checkAvailability(value), 300);
    });

    function checkAvailability(id) {
        status.style.display = 'block';
        status.innerHTML = '<span class="text-muted">Checking availability...</span>';

        fetch('/api/check-routina-id?id=' + encodeURIComponent(id))
            .then(r => r.json())
            .then(data => {
                if (data.available) {
                    status.innerHTML = '<span class="text-success">✓ @' + id + ' is available!</span>';
                } else {
                    status.innerHTML = '<span class="text-danger">✕ @' + id + ' is already taken</span>';
                }
            })
            .catch(() => {
                status.style.display = 'none';
            });
    }
});
</script>

<?php 
$content = ob_get_clean(); 
include __DIR__ . '/../layouts/main.php'; 
?>
