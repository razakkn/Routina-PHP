<?php ob_start(); ?>

<style>
.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
    background: var(--border-color, #e5e7eb);
    border: 1px solid var(--border-color, #e5e7eb);
    border-radius: 8px;
    overflow: hidden;
}

.calendar-header-cell {
    background: var(--card-bg, #fff);
    padding: 10px;
    text-align: center;
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    color: var(--text-muted, #6b7280);
}

.calendar-day {
    background: var(--card-bg, #fff);
    min-height: 100px;
    padding: 6px;
    position: relative;
    vertical-align: top;
}

.calendar-day.empty {
    background: var(--bg-muted, #f9fafb);
}

.calendar-day.today {
    background: rgba(99, 102, 241, 0.08);
}

.calendar-day.today .day-number {
    background: #6366f1;
    color: white;
    border-radius: 50%;
    width: 26px;
    height: 26px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.day-number {
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--text-color, #111827);
    margin-bottom: 4px;
}

.day-events {
    display: flex;
    flex-direction: column;
    gap: 2px;
    max-height: 80px;
    overflow-y: auto;
}

.day-event {
    font-size: 0.7rem;
    padding: 2px 4px;
    border-radius: 3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    cursor: pointer;
    transition: transform 0.1s;
}

.day-event:hover {
    transform: scale(1.02);
}

.event-dot {
    display: inline-block;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    margin-right: 4px;
}

.month-nav {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 16px;
}

.month-nav h2 {
    margin: 0;
    min-width: 200px;
    text-align: center;
}

.filter-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 16px;
}

.filter-pill {
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 0.8rem;
    text-decoration: none;
    border: 1px solid var(--border-color, #e5e7eb);
    background: var(--card-bg, #fff);
    color: var(--text-color, #374151);
    transition: all 0.15s;
}

.filter-pill:hover {
    border-color: #6366f1;
}

.filter-pill.active {
    background: #6366f1;
    color: white;
    border-color: #6366f1;
}

.quick-months {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 12px;
}

.quick-month {
    font-size: 0.75rem;
    padding: 4px 8px;
    border-radius: 4px;
    text-decoration: none;
    background: var(--bg-muted, #f3f4f6);
    color: var(--text-muted, #6b7280);
}

.quick-month:hover {
    background: #e0e7ff;
    color: #4338ca;
}

.quick-month.current {
    background: #6366f1;
    color: white;
}

.event-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 12px;
    font-size: 0.8rem;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 4px;
}

.upcoming-section {
    margin-top: 24px;
}

.upcoming-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px;
    border-bottom: 1px solid var(--border-color, #e5e7eb);
}

.upcoming-item:last-child {
    border-bottom: none;
}

.upcoming-icon {
    font-size: 1.25rem;
}

.upcoming-details {
    flex: 1;
}

.upcoming-title {
    font-weight: 500;
}

.upcoming-meta {
    font-size: 0.8rem;
    color: var(--text-muted, #6b7280);
}

.event-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.event-modal-overlay.show {
    display: flex;
}

.event-modal {
    background: var(--card-bg, #fff);
    border-radius: 12px;
    padding: 24px;
    max-width: 400px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
}

@media (max-width: 768px) {
    .calendar-day {
        min-height: 60px;
        padding: 4px;
    }
    
    .day-events {
        max-height: 40px;
    }
    
    .day-event {
        font-size: 0.65rem;
        padding: 1px 3px;
    }
}
</style>

<div class="routina-wrap">
    <div class="routina-header">
        <div>
            <div class="routina-title">Calendar</div>
            <div class="routina-sub">All your events, vacations, birthdays, and reminders in one view.</div>
        </div>
        <div>
            <button class="btn btn-primary btn-sm" onclick="document.getElementById('addEventModal').classList.add('show')">
                + Add Event
            </button>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger mt-3"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Month Navigation -->
    <div class="month-nav">
        <a href="?year=<?= $prevYear ?>&month=<?= $prevMonth ?>&filter=<?= urlencode($filterType) ?>" class="btn btn-outline-secondary btn-sm">
            ← Prev
        </a>
        <h2><?= htmlspecialchars($monthName) ?> <?= $year ?></h2>
        <a href="?year=<?= $nextYear ?>&month=<?= $nextMonth ?>&filter=<?= urlencode($filterType) ?>" class="btn btn-outline-secondary btn-sm">
            Next →
        </a>
        <a href="?year=<?= date('Y') ?>&month=<?= date('m') ?>&filter=<?= urlencode($filterType) ?>" class="btn btn-outline-primary btn-sm">
            Today
        </a>
    </div>

    <!-- Filter Pills -->
    <div class="filter-pills">
        <a href="?year=<?= $year ?>&month=<?= $month ?>&filter=all" class="filter-pill <?= $filterType === 'all' ? 'active' : '' ?>">All</a>
        <a href="?year=<?= $year ?>&month=<?= $month ?>&filter=event" class="filter-pill <?= $filterType === 'event' ? 'active' : '' ?>">📅 Events</a>
        <a href="?year=<?= $year ?>&month=<?= $month ?>&filter=vacation" class="filter-pill <?= $filterType === 'vacation' ? 'active' : '' ?>">✈️ Vacations</a>
        <a href="?year=<?= $year ?>&month=<?= $month ?>&filter=birthday" class="filter-pill <?= $filterType === 'birthday' ? 'active' : '' ?>">🎂 Birthdays</a>
        <a href="?year=<?= $year ?>&month=<?= $month ?>&filter=bill" class="filter-pill <?= $filterType === 'bill' ? 'active' : '' ?>">💰 Bills</a>
        <a href="?year=<?= $year ?>&month=<?= $month ?>&filter=maintenance" class="filter-pill <?= $filterType === 'maintenance' ? 'active' : '' ?>">🔧 Maintenance</a>
        <a href="?year=<?= $year ?>&month=<?= $month ?>&filter=vehicle" class="filter-pill <?= $filterType === 'vehicle' ? 'active' : '' ?>">🚗 Vehicle</a>
    </div>

    <!-- Calendar Grid -->
    <div class="calendar-grid">
        <!-- Header -->
        <div class="calendar-header-cell">Sun</div>
        <div class="calendar-header-cell">Mon</div>
        <div class="calendar-header-cell">Tue</div>
        <div class="calendar-header-cell">Wed</div>
        <div class="calendar-header-cell">Thu</div>
        <div class="calendar-header-cell">Fri</div>
        <div class="calendar-header-cell">Sat</div>

        <!-- Days -->
        <?php foreach ($calendarWeeks as $week): ?>
            <?php foreach ($week as $dayData): ?>
                <?php if ($dayData === null): ?>
                    <div class="calendar-day empty"></div>
                <?php else: ?>
                    <div class="calendar-day <?= $dayData['isToday'] ? 'today' : '' ?>" data-date="<?= $dayData['date'] ?>">
                        <div class="day-number"><?= $dayData['day'] ?></div>
                        <div class="day-events">
                            <?php foreach (array_slice($dayData['events'], 0, 4) as $event): ?>
                                <?php $eventTitle = $event['title'] ?? ''; ?>
                                <?php $shortTitle = function_exists('mb_substr') ? mb_substr($eventTitle, 0, 15) : substr($eventTitle, 0, 15); ?>
                                <div class="day-event" 
                                     style="background: <?= htmlspecialchars($event['color'] ?? '#6b7280') ?>20; color: <?= htmlspecialchars($event['color'] ?? '#6b7280') ?>; border-left: 2px solid <?= htmlspecialchars($event['color'] ?? '#6b7280') ?>;"
                                     title="<?= htmlspecialchars($eventTitle) ?>"
                                     onclick="showEventDetails(<?= htmlspecialchars(json_encode($event)) ?>)">
                                    <?= htmlspecialchars($event['icon'] ?? '📌') ?>
                                    <?= htmlspecialchars($shortTitle) ?>
                                </div>
                            <?php endforeach; ?>
                            <?php if (count($dayData['events']) > 4): ?>
                                <div class="day-event" style="background: #f3f4f6; color: #6b7280;">
                                    +<?= count($dayData['events']) - 4 ?> more
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>

    <!-- Legend -->
    <div class="event-legend">
        <div class="legend-item"><span class="event-dot" style="background: #3b82f6;"></span> Event</div>
        <div class="legend-item"><span class="event-dot" style="background: #6366f1;"></span> Meeting</div>
        <div class="legend-item"><span class="event-dot" style="background: #f59e0b;"></span> Reminder</div>
        <div class="legend-item"><span class="event-dot" style="background: #14b8a6;"></span> Vacation</div>
        <div class="legend-item"><span class="event-dot" style="background: #ec4899;"></span> Birthday</div>
        <div class="legend-item"><span class="event-dot" style="background: #f59e0b;"></span> Bill Due</div>
        <div class="legend-item"><span class="event-dot" style="background: #6366f1;"></span> Maintenance</div>
        <div class="legend-item"><span class="event-dot" style="background: #8b5cf6;"></span> Registration</div>
        <div class="legend-item"><span class="event-dot" style="background: #0ea5e9;"></span> Insurance</div>
    </div>

    <!-- Quick Month Navigation -->
    <?php if (!empty($monthsWithEvents)): ?>
        <div class="card mt-4">
            <div class="card-kicker">Months with Events</div>
            <div class="quick-months">
                <?php foreach ($monthsWithEvents as $m): ?>
                    <?php $isCurrent = $m['year'] === $year && $m['month'] === $month; ?>
                    <a href="?year=<?= $m['year'] ?>&month=<?= $m['month'] ?>&filter=<?= urlencode($filterType) ?>" 
                       class="quick-month <?= $isCurrent ? 'current' : '' ?>">
                        <?= htmlspecialchars($m['label']) ?>
                        <span style="opacity: 0.7;">(<?= $m['count'] ?>)</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Upcoming Events List -->
    <div class="grid mt-4" style="grid-template-columns: 1fr 1fr;">
        <div class="card">
            <div class="card-kicker">Upcoming User Events</div>
            <?php if (empty($upcomingEvents)): ?>
                <p class="text-muted mt-3">No upcoming events.</p>
            <?php else: ?>
                <?php foreach ($upcomingEvents as $e): ?>
                    <div class="upcoming-item">
                        <div class="upcoming-icon">
                            <?php
                            $icon = match($e['type'] ?? 'event') {
                                'meeting' => '👥',
                                'reminder' => '⏰',
                                default => '📅'
                            };
                            echo $icon;
                            ?>
                        </div>
                        <div class="upcoming-details">
                            <div class="upcoming-title"><?= htmlspecialchars($e['title']) ?></div>
                            <div class="upcoming-meta">
                                <?= date('M d, Y · H:i', strtotime($e['start_datetime'])) ?>
                                <span class="badge bg-secondary ms-2"><?= htmlspecialchars($e['type']) ?></span>
                            </div>
                        </div>
                        <form method="post" action="/calendar/delete" style="display: inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="event_id" value="<?= (int)$e['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this event?');">×</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-kicker">Add Event</div>
            <form method="post" class="mt-3">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input name="title" class="form-control" required placeholder="Event title" />
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label">Start</label>
                        <input name="start" type="datetime-local" class="form-control" required />
                    </div>
                    <div class="col-6">
                        <label class="form-label">End</label>
                        <input name="end" type="datetime-local" class="form-control" required />
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">
                        <option value="event">📅 Event</option>
                        <option value="meeting">👥 Meeting</option>
                        <option value="reminder">⏰ Reminder</option>
                    </select>
                </div>
                <button class="btn btn-primary w-100">Add Event</button>
            </form>
        </div>
    </div>
</div>

<!-- Add Event Modal (for quick add) -->
<div id="addEventModal" class="event-modal-overlay" onclick="if(event.target === this) this.classList.remove('show');">
    <div class="event-modal">
        <h4 class="mb-3">Add Event</h4>
        <form method="post">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Title</label>
                <input name="title" class="form-control" required placeholder="Event title" />
            </div>
            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label">Start</label>
                    <input name="start" type="datetime-local" class="form-control" required />
                </div>
                <div class="col-6">
                    <label class="form-label">End</label>
                    <input name="end" type="datetime-local" class="form-control" required />
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Type</label>
                <select name="type" class="form-select">
                    <option value="event">📅 Event</option>
                    <option value="meeting">👥 Meeting</option>
                    <option value="reminder">⏰ Reminder</option>
                </select>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">Add Event</button>
                <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('addEventModal').classList.remove('show');">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Event Details Modal -->
<div id="eventDetailsModal" class="event-modal-overlay" onclick="if(event.target === this) this.classList.remove('show');">
    <div class="event-modal">
        <h4 class="mb-3" id="eventDetailTitle">Event Details</h4>
        <div id="eventDetailContent"></div>
        <div class="mt-3">
            <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('eventDetailsModal').classList.remove('show');">Close</button>
        </div>
    </div>
</div>

<script>
function showEventDetails(event) {
    const modal = document.getElementById('eventDetailsModal');
    const titleEl = document.getElementById('eventDetailTitle');
    const contentEl = document.getElementById('eventDetailContent');
    
    titleEl.textContent = event.title || 'Event';
    
    let html = '<div class="upcoming-item" style="border: none; padding: 0;">';
    html += '<div class="upcoming-icon" style="font-size: 2rem;">' + (event.icon || '📌') + '</div>';
    html += '<div class="upcoming-details">';
    
    if (event.time) {
        html += '<p class="mb-1"><strong>Time:</strong> ' + event.time;
        if (event.end_time) html += ' - ' + event.end_time;
        html += '</p>';
    }
    
    if (event.status) {
        html += '<p class="mb-1"><strong>Status:</strong> <span class="badge" style="background: ' + (event.color || '#6b7280') + ';">' + event.status + '</span></p>';
    }
    
    if (event.relation) {
        html += '<p class="mb-1"><strong>Relation:</strong> ' + event.relation + '</p>';
    }
    
    if (event.age !== undefined && event.age > 0) {
        html += '<p class="mb-1"><strong>Turning:</strong> ' + event.age + ' years old</p>';
    }
    
    if (event.amount) {
        html += '<p class="mb-1"><strong>Amount:</strong> $' + event.amount.toFixed(2) + '</p>';
    }
    
    if (event.source) {
        const links = {
            'calendar': '/calendar',
            'vacation': '/vacation/trip/' + event.id,
            'family': '/family',
            'bill': '/finance/bills',
            'maintenance': '/vehicle',
            'vehicle': '/vehicle'
        };
        if (links[event.source]) {
            html += '<p class="mt-2"><a href="' + links[event.source] + '" class="btn btn-sm btn-outline-primary">View Details →</a></p>';
        }
    }
    
    html += '</div></div>';
    
    contentEl.innerHTML = html;
    modal.classList.add('show');
}
</script>

<?php 
$content = ob_get_clean(); 
include __DIR__ . '/../layouts/main.php'; 
?>
