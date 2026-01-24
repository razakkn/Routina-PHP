<?php ob_start(); ?>

<div class="routina-wrap" style="max-width: 1180px; margin: 0 auto;">
  <div class="routina-header">
    <div>
      <div class="routina-title">Buzz</div>
      <div class="routina-sub">Reach other Routina users instantly (in-app request).</div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-sm <?php echo ($tab ?? 'inbox') === 'inbox' ? 'btn-primary' : 'btn-outline-secondary'; ?>" href="/buzz?tab=inbox">Inbox</a>
      <a class="btn btn-sm <?php echo ($tab ?? 'inbox') === 'outbox' ? 'btn-primary' : 'btn-outline-secondary'; ?>" href="/buzz?tab=outbox">Outbox</a>
    </div>
  </div>

  <?php if (!empty($sent)): ?>
    <div class="alert alert-success mt-3">Buzz sent.</div>
  <?php endif; ?>

  <?php if (!empty($dbError)): ?>
    <div class="alert alert-warning mt-3">
      Buzz database is not ready yet. Run <strong>php setup_database.php</strong> once to create the <strong>buzz_requests</strong> table.
      <div class="text-muted" style="margin-top: 6px; font-size: 0.9rem;">Details: <?php echo htmlspecialchars((string)$dbError); ?></div>
    </div>
  <?php endif; ?>

  <div class="card" style="margin-top: 14px;">
    <div class="d-flex align-items-center justify-content-between" style="gap: 12px;">
      <div class="card-kicker"><?php echo ($tab ?? 'inbox') === 'outbox' ? 'Sent requests' : 'Incoming requests'; ?></div>

      <?php if (($tab ?? 'inbox') === 'inbox'): ?>
        <?php
          $pendingCount = 0;
          foreach (($inbox ?? []) as $rr) {
            if (($rr['status'] ?? '') === 'pending') {
              $pendingCount++;
            }
          }
        ?>
        <?php if ($pendingCount > 0): ?>
          <form method="post" action="/buzz/mark-all" style="margin: 0;">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-outline-secondary" type="submit">Mark all as acknowledged (<?php echo (int)$pendingCount; ?>)</button>
          </form>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <?php
      $rows = (($tab ?? 'inbox') === 'outbox') ? ($outbox ?? []) : ($inbox ?? []);
    ?>

    <?php if (empty($rows)): ?>
      <div class="text-muted" style="padding-top: 10px;">No buzz requests yet.</div>
    <?php else: ?>
      <div class="table-responsive" style="margin-top: 10px;">
        <table class="table table-sm align-middle">
          <thead>
            <tr>
              <th style="min-width: 200px;">From/To</th>
              <th style="min-width: 160px;">When</th>
              <th>Channel</th>
              <th style="min-width: 360px;">Message</th>
              <th>Status</th>
              <th style="width: 160px;"></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <?php
                $isOut = (($tab ?? 'inbox') === 'outbox');
                $who = $isOut
                  ? (string)($r['to_display_name'] ?? 'User')
                  : (string)($r['from_display_name'] ?? 'User');
                $status = (string)($r['status'] ?? 'pending');
                $id = (int)($r['id'] ?? 0);
                $createdAtRaw = (string)($r['created_at'] ?? '');
                $createdAtTs = $createdAtRaw !== '' ? strtotime($createdAtRaw . ' UTC') : false;
                $createdAtLabel = $createdAtTs ? date('M j, g:i A', $createdAtTs) : $createdAtRaw;
              ?>
              <tr<?php echo (!$isOut && $status === 'pending') ? ' style="background: rgba(var(--module-accent-1-rgb),0.08);"' : ''; ?>>
                <td><?php echo htmlspecialchars($who); ?></td>
                <td class="text-muted"><?php echo htmlspecialchars($createdAtLabel); ?></td>
                <td><?php echo htmlspecialchars((string)($r['channel'] ?? 'in_app')); ?></td>
                <td><?php echo htmlspecialchars((string)($r['message'] ?? '')); ?></td>
                <td><?php echo htmlspecialchars($status); ?></td>
                <td class="text-end">
                  <?php if (!$isOut): ?>
                    <form method="post" action="/buzz/mark?id=<?php echo $id; ?>" style="display:inline;">
                      <?= csrf_field() ?>
                      <input type="hidden" name="status" value="acknowledged" />
                      <button class="btn btn-sm btn-outline-primary" type="submit">Acknowledge</button>
                    </form>
                    <form method="post" action="/buzz/mark?id=<?php echo $id; ?>" style="display:inline;">
                      <?= csrf_field() ?>
                      <input type="hidden" name="status" value="done" />
                      <button class="btn btn-sm btn-outline-secondary" type="submit">Done</button>
                    </form>
                  <?php else: ?>
                    <a class="btn btn-sm btn-outline-secondary" href="/family">Back to Family</a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
