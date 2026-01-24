<?php ob_start(); ?>

<div class="routina-wrap dash-wrap">
    <div class="routina-header">
        <div>
            <div class="routina-title"><?php echo htmlspecialchars($Model->GreetingLine ?? 'Welcome back'); ?></div>
            <div class="routina-sub">Your overview for <?php echo htmlspecialchars($Model->TodayLabel ?? 'today'); ?>.</div>
        </div>
    </div>

    <?php $buzzUnread = (int)($Model->BuzzUnread ?? 0); ?>
    <?php if ($buzzUnread > 0): ?>
        <div class="card dash-card" style="margin-bottom: 12px;">
            <div class="dash-card__header">
                <h3>Buzz</h3>
                <a href="/buzz">Open</a>
            </div>
            <div class="card-title" style="margin-bottom: 6px;">You have <?php echo $buzzUnread; ?> new request<?php echo $buzzUnread === 1 ? '' : 's'; ?>.</div>
            <div class="muted">Someone you matched with is trying to connect.</div>
        </div>
    <?php endif; ?>

    <?php $q = trim((string)($Model->SearchQuery ?? '')); ?>
    <div class="dash-layout <?php echo $q !== '' ? 'dash-layout--single' : ''; ?>">
        <div>
            <?php if ($q !== ''): ?>
                <?php $sr = $Model->SearchResults ?? null; ?>
                <?php $sc = $Model->SearchCounts ?? null; ?>
                <div class="card dash-card dash-search">
                    <div class="dash-card__header">
                        <h3>Search results</h3>
                        <span class="muted">for “<?php echo htmlspecialchars($q); ?>”</span>
                    </div>

                    <?php
                        $journalHits = is_array($sr) && isset($sr['journal']) ? $sr['journal'] : [];
                        $eventHits = is_array($sr) && isset($sr['events']) ? $sr['events'] : [];
                        $taskHits = is_array($sr) && isset($sr['tasks']) ? $sr['tasks'] : [];
                        $familyHits = is_array($sr) && isset($sr['family']) ? $sr['family'] : [];
                        $totalHits = count($journalHits) + count($eventHits) + count($taskHits) + count($familyHits);
                    ?>

                    <?php if ($totalHits === 0): ?>
                        <div class="muted">No matches found. Try a different keyword.</div>
                    <?php else: ?>
                        <div class="dash-search__grid">
                            <div class="dash-search__col">
                                <div class="dash-search__title">Journal (<?php echo (int)count($journalHits); ?>)</div>
                                <?php if (!empty($journalHits)): ?>
                                    <ul class="dash-search__list">
                                        <?php foreach ($journalHits as $hit): ?>
                                            <?php
                                                $content = trim((string)($hit['content'] ?? ''));
                                                $excerpt = function_exists('mb_substr') ? mb_substr($content, 0, 120) : substr($content, 0, 120);
                                                if ($content !== '' && (function_exists('mb_strlen') ? mb_strlen($content) : strlen($content)) > 120) {
                                                    $excerpt .= '…';
                                                }
                                            ?>
                                            <li>
                                                <a class="link" href="/journal/view?id=<?php echo (int)($hit['id'] ?? 0); ?>">
                                                    <?php echo htmlspecialchars((string)($hit['entry_date'] ?? '')); ?> · <?php echo htmlspecialchars((string)($hit['mood'] ?? '')); ?>
                                                </a>
                                                <div class="muted" style="margin-top: 4px; line-height: 1.4;">
                                                    <?php echo htmlspecialchars($excerpt); ?>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <div class="muted">No journal matches.</div>
                                <?php endif; ?>
                            </div>

                            <div class="dash-search__col">
                                <div class="dash-search__title">Events (<?php echo (int)count($eventHits); ?>)</div>
                                <?php if (!empty($eventHits)): ?>
                                    <ul class="dash-search__list">
                                        <?php foreach ($eventHits as $hit): ?>
                                            <?php $ts = strtotime((string)($hit['start_datetime'] ?? '')); ?>
                                            <li>
                                                <a class="link" href="/calendar">
                                                    <?php echo htmlspecialchars((string)($hit['title'] ?? 'Event')); ?>
                                                </a>
                                                <div class="muted" style="margin-top: 4px;">
                                                    <?php echo $ts ? htmlspecialchars(date('D, M j · g:i A', $ts)) : htmlspecialchars((string)($hit['start_datetime'] ?? '')); ?>
                                                    <?php if (!empty($hit['type'])): ?> · <?php echo htmlspecialchars((string)$hit['type']); ?><?php endif; ?>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <div class="muted">No event matches.</div>
                                <?php endif; ?>
                            </div>

                            <div class="dash-search__col">
                                <div class="dash-search__title">Tasks (<?php echo (int)count($taskHits); ?>)</div>
                                <?php if (!empty($taskHits)): ?>
                                    <ul class="dash-search__list">
                                        <?php foreach ($taskHits as $hit): ?>
                                            <li>
                                                <a class="link" href="/home"><?php echo htmlspecialchars((string)($hit['title'] ?? 'Task')); ?></a>
                                                <div class="muted" style="margin-top: 4px;">
                                                    <?php
                                                        $bits = [];
                                                        if (!empty($hit['frequency'])) { $bits[] = (string)$hit['frequency']; }
                                                        if (!empty($hit['assigned_to'])) { $bits[] = 'Assigned: ' . (string)$hit['assigned_to']; }
                                                        echo htmlspecialchars(implode(' · ', $bits));
                                                    ?>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <div class="muted">No task matches.</div>
                                <?php endif; ?>
                            </div>

                            <div class="dash-search__col">
                                <div class="dash-search__title">Family (<?php echo (int)count($familyHits); ?>)</div>
                                <?php if (!empty($familyHits)): ?>
                                    <ul class="dash-search__list">
                                        <?php foreach ($familyHits as $hit): ?>
                                            <li>
                                                <a class="link" href="/family"><?php echo htmlspecialchars((string)($hit['name'] ?? 'Person')); ?></a>
                                                <div class="muted" style="margin-top: 4px;">
                                                    <?php
                                                        $bits = [];
                                                        if (!empty($hit['relation'])) { $bits[] = (string)$hit['relation']; }
                                                        if (!empty($hit['phone'])) { $bits[] = (string)$hit['phone']; }
                                                        if (!empty($hit['email'])) { $bits[] = (string)$hit['email']; }
                                                        echo htmlspecialchars(implode(' · ', $bits));
                                                    ?>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <div class="muted">No family matches.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>

            <div class="dash-section-title">
                <h2>Today at a glance</h2>
                <div class="muted">Reflections this week: <strong><?php echo (int)($Model->ReflectionsThisWeek ?? 0); ?></strong></div>
            </div>

            <div class="dash-glance-grid">
                <div class="card dash-mini">
                    <div class="dash-mini__kicker">Mood / Quote</div>
                    <div class="dash-mini__value"><?php echo htmlspecialchars($Model->MoodLabel ?? '—'); ?></div>
                    <div class="dash-mini__sub">“<?php echo htmlspecialchars($Model->QuoteOfTheDay['quote'] ?? ''); ?>”</div>
                    <div class="muted" style="margin-top: 8px; font-size: 0.9rem;">
                        <?php echo htmlspecialchars($Model->QuoteOfTheDay['signature'] ?? ''); ?>
                    </div>
                </div>

                <div class="card dash-mini">
                    <div class="dash-mini__kicker">Today’s events</div>
                    <div class="dash-mini__value"><?php echo (int)($Model->TodayEventsCount ?? 0); ?></div>
                    <div class="dash-mini__sub"><?php echo htmlspecialchars($Model->NextEventLabel ?? ''); ?></div>
                    <div style="margin-top: 10px;"><a class="link" href="/calendar">Open Calendar</a></div>
                </div>

                <div class="card dash-mini">
                    <div class="dash-mini__kicker">Tasks to do</div>
                    <div class="dash-mini__value"><?php echo (int)($Model->PendingTasks ?? 0); ?></div>
                    <div class="dash-mini__sub">Pending home tasks</div>
                    <div style="margin-top: 10px;"><a class="link" href="/home">View Tasks</a></div>
                </div>

                <div class="card dash-mini">
                    <div class="dash-mini__kicker">Health stats</div>
                    <div class="dash-mini__value"><?php echo (int)($Model->StepsToday ?? 0); ?></div>
                    <div class="dash-mini__sub">Steps today</div>
                    <div class="muted" style="margin-top: 8px; font-size: 0.9rem;">
                        Sleep: <?php echo htmlspecialchars((string)($Model->SleepToday ?? '—')); ?>h · Water: <?php echo htmlspecialchars((string)($Model->WaterToday ?? '—')); ?>
                    </div>
                </div>
            </div>

            <div style="margin-top: 10px;" class="dash-panels">
                <div class="card dash-card">
                    <div class="dash-card__header">
                        <h3>Recent journal</h3>
                        <a href="/journal">Open</a>
                    </div>

                    <?php $latest = $Model->LatestJournal ?? null; ?>
                    <?php if ($latest): ?>
                        <div class="muted" style="margin-bottom: 8px;">
                            <?php echo htmlspecialchars((string)($latest['entry_date'] ?? '')); ?> · Mood: <?php echo htmlspecialchars((string)($latest['mood'] ?? '—')); ?>
                        </div>
                        <div style="line-height: 1.6;">
                            <?php
                                $content = (string)($latest['content'] ?? '');
                                $trimmed = trim($content);
                                if (function_exists('mb_substr') && function_exists('mb_strlen')) {
                                    $excerpt = mb_substr($trimmed, 0, 220);
                                    if (mb_strlen($trimmed) > 220) {
                                        $excerpt .= '…';
                                    }
                                } else {
                                    $excerpt = substr($trimmed, 0, 220);
                                    if (strlen($trimmed) > 220) {
                                        $excerpt .= '…';
                                    }
                                }
                                echo nl2br(htmlspecialchars($excerpt));
                            ?>
                        </div>
                    <?php else: ?>
                        <div class="muted">No journal entries yet.</div>
                        <div style="margin-top: 10px;"><a class="btn-primary" href="/journal">Write your first entry</a></div>
                    <?php endif; ?>
                </div>

                <div class="card dash-card">
                    <div class="dash-card__header">
                        <h3>Vehicle status</h3>
                        <a href="/vehicle">Open</a>
                    </div>

                    <?php $m = $Model->NextMaintenance ?? null; ?>
                    <?php if ($m): ?>
                        <div class="card-title" style="margin-bottom: 6px;"><?php echo htmlspecialchars((string)($m['title'] ?? 'Maintenance')); ?></div>
                        <div class="muted">Due: <?php echo htmlspecialchars((string)($m['due_date'] ?? '')); ?> · Status: <?php echo htmlspecialchars((string)($m['status'] ?? '')); ?></div>
                    <?php else: ?>
                        <div class="muted">No upcoming maintenance items.</div>
                    <?php endif; ?>
                </div>

                <div class="card dash-card">
                    <div class="dash-card__header">
                        <h3>Finance summary</h3>
                        <a href="/finance">Open</a>
                    </div>

                    <div class="recent-list" style="margin-top: 0;">
                        <div><span class="muted">Spent (30d):</span> <strong><?php echo number_format((float)($Model->Spent30 ?? 0), 2); ?></strong></div>
                        <div><span class="muted">Income (30d):</span> <strong><?php echo number_format((float)($Model->Income30 ?? 0), 2); ?></strong></div>
                    </div>

                    <?php $bill = $Model->NextBill ?? null; ?>
                    <?php if ($bill): ?>
                        <div class="muted" style="margin-top: 10px;">
                            Next bill: <?php echo htmlspecialchars((string)($bill['name'] ?? 'Bill')); ?> · Due <?php echo htmlspecialchars((string)($bill['due_date'] ?? '')); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card dash-card">
                    <div class="dash-card__header">
                        <h3>Quick actions</h3>
                        <span class="muted">Shortcuts</span>
                    </div>

                    <div class="card-buttons" style="flex-wrap: wrap;">
                        <a href="/journal" class="btn-primary">Write</a>
                        <a href="/calendar" class="btn-soft">Plan</a>
                        <a href="/home" class="btn-soft">Tasks</a>
                        <a href="/health" class="btn-soft">Log Health</a>
                    </div>
                </div>
            </div>

            <div style="margin-top: 10px;" class="dash-two">
                <div class="card dash-card">
                    <div class="dash-card__header">
                        <h3>Upcoming trip</h3>
                        <a href="/vacation">Open</a>
                    </div>

                    <?php $trip = $Model->NextTrip ?? null; ?>
                    <?php if ($trip): ?>
                        <div class="card-title" style="margin-bottom: 6px;"><?php echo htmlspecialchars((string)($trip['destination'] ?? 'Trip')); ?></div>
                        <div class="muted">Start: <?php echo htmlspecialchars((string)($trip['start_date'] ?? '')); ?> · End: <?php echo htmlspecialchars((string)($trip['end_date'] ?? '')); ?></div>
                        <div class="muted" style="margin-top: 8px;">Status: <?php echo htmlspecialchars((string)($trip['status'] ?? '')); ?></div>
                    <?php else: ?>
                        <div class="muted">No upcoming trips scheduled.</div>
                    <?php endif; ?>
                </div>

                <div class="card dash-card">
                    <div class="dash-card__header">
                        <h3>Health tracker</h3>
                        <a href="/health">Open</a>
                    </div>

                    <div class="muted">Steps (last 7 days)</div>
                    <div class="dash-chart" aria-label="Steps chart">
                        <?php
                            $max = (int)($Model->Steps7Max ?? 1);
                            $max = max(1, $max);
                            $items = $Model->Steps7 ?? [];
                            foreach ($items as $it):
                                $steps = (int)($it['steps'] ?? 0);
                                $pct = (int)round(($steps / $max) * 100);
                                $pct = max(4, $pct);
                        ?>
                            <div>
                                <div class="dash-bar" style="height: <?php echo $pct; ?>%;" title="<?php echo htmlspecialchars((string)$steps); ?> steps"></div>
                                <div class="dash-bar__label"><?php echo htmlspecialchars((string)($it['label'] ?? '')); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($q === ''): ?>
        <aside class="dash-aside">
            <div class="card dash-card">
                <div class="dash-card__header">
                    <h3>Insights &amp; reminders</h3>
                    <span class="muted">Today</span>
                </div>

                <?php $ins = $Model->Insights ?? []; ?>
                <?php if (!empty($ins)): ?>
                    <ul class="dash-list">
                        <?php foreach ($ins as $msg): ?>
                            <li><?php echo htmlspecialchars((string)$msg); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="muted">No reminders right now.</div>
                <?php endif; ?>
            </div>
        </aside>
        <?php endif; ?>
    </div>
</div>

<?php 
$content = ob_get_clean(); 
include __DIR__ . '/../layouts/main.php'; 
?>
