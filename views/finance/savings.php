<?php ob_start(); ?>

<?php
    $currencySymbol = isset($currencySymbol) ? (string)$currencySymbol : '$';
    $currencySpacer = (preg_match('/^[A-Z]{3}$/', $currencySymbol) ? ' ' : '');
    $availableBalance = (float)($availableBalance ?? 0);
    $fmtAmount = function ($amount) use ($currencySymbol, $currencySpacer) {
        return htmlspecialchars($currencySymbol) . htmlspecialchars($currencySpacer) . number_format((float)$amount, 2);
    };
?>

<div class="routina-wrap">
    <div class="routina-header">
       <div>
           <div class="routina-title">Savings Goals</div>
           <div class="routina-sub">Track savings targets and progress.</div>
       </div>
       <div class="d-flex flex-wrap gap-2">
           <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="savingsWidgetShow">Show savings widget</button>
           <a class="btn btn-outline-secondary btn-sm" href="/finance">Back</a>
       </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger mt-3">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="grid">
        <div class="card">
            <div class="card-kicker">Add Goal</div>
            <form method="post" class="mt-3">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Goal</label>
                    <input name="goal" class="form-control" placeholder="Emergency Fund" required />
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label">Target Amount</label>
                        <input name="target_amount" type="number" step="0.01" class="form-control" required />
                    </div>
                    <div class="col-6">
                        <label class="form-label">Current Amount (auto)</label>
                        <input type="text" class="form-control" value="<?php echo $fmtAmount($availableBalance); ?>" readonly />
                        <div class="form-text">Calculated from income minus expenses.</div>
                    </div>
                </div>
                <button class="btn btn-primary w-100">Add Goal</button>
            </form>
        </div>

        <div class="card savings-widget" data-widget="savings-status">
            <div class="d-flex justify-content-between align-items-center">
                <div class="card-kicker">Savings status</div>
                <button type="button" class="btn btn-link btn-sm text-decoration-none savings-widget-close">Hide</button>
            </div>
            <div class="mt-2">
                <div class="text-muted">Available balance</div>
                <div class="h5 fw-bold">
                    <?php echo ($availableBalance >= 0 ? '+' : '-') . $fmtAmount(abs($availableBalance)); ?>
                </div>
                <div class="text-muted small">Based on your total income minus expenses.</div>
            </div>
        </div>

        <div class="card" style="grid-column: span 2;">
            <div class="card-kicker">Goals</div>
            <?php if (empty($savings)): ?>
                <div class="text-muted text-center py-4">No savings goals yet.</div>
            <?php else: ?>
                <div class="table-responsive mt-3">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Goal</th>
                                <th class="text-end">Current</th>
                                <th class="text-end">Target</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($savings as $s): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($s['goal']); ?></td>
                                    <td class="text-end"><?php echo htmlspecialchars($currencySymbol); ?><?php echo htmlspecialchars($currencySpacer); ?><?php echo number_format($s['current_amount'], 2); ?></td>
                                    <td class="text-end"><?php echo htmlspecialchars($currencySymbol); ?><?php echo htmlspecialchars($currencySpacer); ?><?php echo number_format($s['target_amount'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var widget = document.querySelector('[data-widget="savings-status"]');
  var closeBtn = widget ? widget.querySelector('.savings-widget-close') : null;
  var showBtn = document.getElementById('savingsWidgetShow');
  var key = 'routina.savingsWidgetHidden';

  function setHidden(hidden) {
    if (!widget || !showBtn) return;
    widget.classList.toggle('is-hidden', hidden);
    showBtn.classList.toggle('d-none', !hidden);
  }

  setHidden(localStorage.getItem(key) === '1');

  if (closeBtn) {
    closeBtn.addEventListener('click', function () {
      localStorage.setItem(key, '1');
      setHidden(true);
    });
  }

  if (showBtn) {
    showBtn.addEventListener('click', function () {
      localStorage.removeItem(key);
      setHidden(false);
    });
  }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
