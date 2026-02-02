<?php ob_start(); ?>

<div class="dashv2">
  <div class="dashv2-shell">
    <div class="dashv2-main">
      <section class="dashv2-hero">
        <div>
          <div class="dashv2-title">
            <span id="dash-greeting-text"><?php echo htmlspecialchars($Model->GreetingLine ?? 'Welcome back'); ?></span>
            <span class="dashv2-name" id="dash-user-name"><?php echo htmlspecialchars((string)($Model->DisplayName ?? 'User')); ?></span>
          </div>
          <div class="dashv2-sub">
            <span id="dash-datetime">--:--</span>
            <span class="dashv2-sep">·</span>
            <span id="dash-timezone">Time zone</span>
            <span class="dashv2-sep">·</span>
            <span id="dash-city">City</span>
          </div>
        </div>
      </section>

      <section class="dashv2-grid">
        <div class="cardv2">
          <div class="cardv2-head">
            <div class="cardv2-title">Journal</div>
            <div class="cardv2-meta">
              Reflections this week: <?php echo (int)($Model->ReflectionsThisWeek ?? 0); ?>
            </div>
          </div>
          <div class="cardv2-body">
            <?php if (!empty($Model->LatestJournal)): ?>
              <?php echo htmlspecialchars(mb_strimwidth($Model->LatestJournal->content ?? '', 0, 140, '…')); ?>
            <?php else: ?>
              No entries yet. Your future self is waiting. 🙂
            <?php endif; ?>

            <a class="primary-link" href="/journal">Write now</a>
          </div>
        </div>

        <div class="cardv2">
          <div class="cardv2-head">
            <div class="cardv2-title">Tasks & Events</div>
            <div class="cardv2-meta">Today</div>
          </div>
          <div class="cardv2-body">
            <div><b><?php echo (int)($Model->PendingTasks ?? 0); ?></b> pending tasks</div>
            <div style="margin-top:6px;">
              <?php echo !empty($Model->NextEventLabel) ? htmlspecialchars($Model->NextEventLabel) : 'No upcoming events'; ?>
            </div>
            <a class="primary-link" href="/calendar">Open calendar</a>
          </div>
        </div>

        <div class="cardv2">
          <div class="cardv2-head">
            <div class="cardv2-title">Health Snapshot</div>
            <div class="cardv2-meta">Today</div>
          </div>
          <div class="cardv2-body">
            <div>Steps: <b><?php echo (int)($Model->StepsToday ?? 0); ?></b></div>
            <div style="margin-top:6px;">
              Sleep: <?php echo htmlspecialchars($Model->SleepToday ?? '—'); ?> ·
              Water: <?php echo htmlspecialchars($Model->WaterToday ?? '—'); ?>
            </div>
            <a class="primary-link" href="/health">Log health</a>
          </div>
        </div>
      </section>
    </div>

    <aside class="dashv2-aside">
      <div class="cardv2 quick-card">
        <div class="cardv2-head">
          <div class="cardv2-title">Quick actions</div>
          <div class="cardv2-meta">Shortcuts</div>
        </div>
        <div class="quick-list">
          <a class="quick-item" href="/journal">Write <span>✍️</span></a>
          <a class="quick-item" href="/vacation">Plan <span>🧭</span></a>
          <a class="quick-item" href="/home_task">Tasks <span>✅</span></a>
          <a class="quick-item" href="/health">Health <span>❤️</span></a>
        </div>
      </div>
    </aside>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
