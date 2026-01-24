<?php

namespace Routina\Controllers;

use Routina\Models\Calendar;
use Routina\Models\Buzz;
use Routina\Models\Family;
use Routina\Models\FinanceBill;
use Routina\Models\Health;
use Routina\Models\HomeTask;
use Routina\Models\Journal;
use Routina\Models\Transaction;
use Routina\Models\Vacation;
use Routina\Models\VehicleMaintenance;
use Routina\Services\QuoteService;

class DashboardController {
    private function greetingLine() {
        $hour = (int)date('G');
        if ($hour < 12) {
            return 'Good morning';
        }
        if ($hour < 18) {
            return 'Good afternoon';
        }
        return 'Good evening';
    }

    private function daysUntil($dateYmd) {
        if (!$dateYmd) {
            return null;
        }

        $ts = strtotime($dateYmd);
        if ($ts === false) {
            return null;
        }

        $today = strtotime(date('Y-m-d'));
        return (int)floor(($ts - $today) / 86400);
    }

    private function isTruthy($value) {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value)) {
            return $value !== 0;
        }
        $s = strtolower(trim((string)$value));
        return in_array($s, ['1', 'true', 't', 'yes', 'y', 'on'], true);
    }

    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
        $quote = QuoteService::quoteOfTheDay($userId);

        $searchQuery = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        if (strlen($searchQuery) > 120) {
            $searchQuery = substr($searchQuery, 0, 120);
        }

        $todayYmd = date('Y-m-d');
        $weekStartYmd = date('Y-m-d', strtotime('-6 days'));

        $latestJournal = Journal::latest($userId);
        $reflectionsThisWeek = Journal::countSince($userId, $weekStartYmd);

        $upcomingEvents = Calendar::upcoming($userId);
        $todayEvents = array_values(array_filter($upcomingEvents, function ($evt) use ($todayYmd) {
            $start = $evt['start_datetime'] ?? '';
            $ts = strtotime($start);
            if ($ts === false) {
                return false;
            }
            return date('Y-m-d', $ts) === $todayYmd;
        }));
        $todayEventsCount = count($todayEvents);

        $nextEvent = $upcomingEvents[0] ?? null;
        $nextEventLabel = 'No upcoming events';
        if ($nextEvent) {
            $startTs = strtotime($nextEvent['start_datetime'] ?? '');
            $title = trim((string)($nextEvent['title'] ?? 'Event'));
            if ($startTs !== false) {
                $when = date('D g:i A', $startTs);
                $nextEventLabel = $when . ' · ' . $title;
            } else {
                $nextEventLabel = $title;
            }
        }

        $tasks = HomeTask::getAll($userId);
        $pendingTasks = 0;
        foreach ($tasks as $t) {
            $completed = $this->isTruthy($t['is_completed'] ?? false);
            if (!$completed) {
                $pendingTasks++;
            }
        }

        $healthEntries = Health::getAll($userId);
        $todayHealth = null;
        foreach ($healthEntries as $h) {
            if (($h['entry_date'] ?? null) === $todayYmd) {
                $todayHealth = $h;
                break;
            }
        }

        $stepsToday = (int)($todayHealth['steps'] ?? 0);
        $sleepToday = $todayHealth['sleep_hours'] ?? null;
        $waterToday = $todayHealth['water_glasses'] ?? null;

        $stepsByDate = [];
        foreach ($healthEntries as $h) {
            if (!isset($h['entry_date'])) {
                continue;
            }
            $stepsByDate[$h['entry_date']] = (int)($h['steps'] ?? 0);
        }

        $steps7 = [];
        $maxSteps = 0;
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime('-' . $i . ' days'));
            $val = (int)($stepsByDate[$d] ?? 0);
            $maxSteps = max($maxSteps, $val);
            $steps7[] = [
                'date' => $d,
                'label' => date('D', strtotime($d)),
                'steps' => $val
            ];
        }

        $transactions = Transaction::getAll($userId);
        $spent30 = 0.0;
        $income30 = 0.0;
        $since30 = strtotime('-29 days');
        foreach ($transactions as $tx) {
            $txDate = strtotime((string)($tx['date'] ?? ''));
            if ($txDate === false || $txDate < $since30) {
                continue;
            }

            $amount = (float)($tx['amount'] ?? 0);
            $type = strtolower(trim((string)($tx['type'] ?? '')));

            $isExpense = ($type === 'expense') || ($amount < 0);
            $isIncome = ($type === 'income') || ($amount > 0);

            if ($isExpense) {
                $spent30 += abs($amount);
            } elseif ($isIncome) {
                $income30 += abs($amount);
            }
        }

        $bills = FinanceBill::getAll($userId);
        $nextBill = null;
        foreach ($bills as $b) {
            $dueTs = strtotime((string)($b['due_date'] ?? ''));
            if ($dueTs === false) {
                continue;
            }
            $status = strtolower(trim((string)($b['status'] ?? '')));
            if ($status === 'paid') {
                continue;
            }
            if ($dueTs < strtotime($todayYmd)) {
                continue;
            }
            $nextBill = $b;
            break;
        }

        $maintenance = VehicleMaintenance::getAll($userId);
        $nextMaintenance = null;
        foreach ($maintenance as $m) {
            $dueTs = strtotime((string)($m['due_date'] ?? ''));
            if ($dueTs === false) {
                continue;
            }
            $status = strtolower(trim((string)($m['status'] ?? '')));
            if (in_array($status, ['done', 'completed', 'complete'], true)) {
                continue;
            }
            if ($dueTs < strtotime($todayYmd)) {
                continue;
            }
            $nextMaintenance = $m;
            break;
        }

        $vacations = Vacation::getAll($userId);
        $nextTrip = null;
        foreach ($vacations as $v) {
            $startTs = strtotime((string)($v['start_date'] ?? ''));
            if ($startTs === false) {
                continue;
            }
            $status = strtolower(trim((string)($v['status'] ?? '')));
            if (in_array($status, ['completed', 'complete', 'done'], true)) {
                continue;
            }
            if ($startTs < strtotime($todayYmd)) {
                continue;
            }
            $nextTrip = $v;
            break;
        }

        $insights = [];
        if ($pendingTasks > 0) {
            $insights[] = "You have {$pendingTasks} pending task" . ($pendingTasks === 1 ? '' : 's') . ".";
        } else {
            $insights[] = "No pending tasks — nice.";
        }
        if ($todayEventsCount > 0) {
            $insights[] = "{$todayEventsCount} event" . ($todayEventsCount === 1 ? '' : 's') . " today.";
        } elseif ($nextEvent) {
            $insights[] = "Next event: {$nextEventLabel}.";
        }
        if ($nextBill) {
            $days = $this->daysUntil($nextBill['due_date'] ?? null);
            if ($days !== null) {
                $insights[] = "Upcoming bill: " . ($nextBill['name'] ?? 'Bill') . " in {$days} day" . ($days === 1 ? '' : 's') . ".";
            }
        }

        if ($nextMaintenance) {
            $days = $this->daysUntil($nextMaintenance['due_date'] ?? null);
            if ($days !== null) {
                $insights[] = "Vehicle: " . ($nextMaintenance['title'] ?? 'Maintenance') . " due in {$days} day" . ($days === 1 ? '' : 's') . ".";
            }
        }

        $insights = array_values(array_unique(array_filter($insights, function ($s) {
            return is_string($s) && trim($s) !== '';
        })));
        if (count($insights) > 5) {
            $insights = array_slice($insights, 0, 5);
        }

        $moodLabel = trim((string)($latestJournal['mood'] ?? ''));
        if ($moodLabel === '') {
            $moodLabel = '—';
        }

        $searchResults = null;
        $searchCounts = null;
        if ($searchQuery !== '') {
            $journalHits = Journal::search($userId, $searchQuery, 8);
            $eventHits = Calendar::search($userId, $searchQuery, 8);
            $taskHits = HomeTask::search($userId, $searchQuery, 8);
            $familyHits = Family::search($userId, $searchQuery, 8);

            $searchResults = [
                'journal' => $journalHits,
                'events' => $eventHits,
                'tasks' => $taskHits,
                'family' => $familyHits
            ];

            $searchCounts = [
                'journal' => count($journalHits),
                'events' => count($eventHits),
                'tasks' => count($taskHits),
                'family' => count($familyHits)
            ];
        }

        $buzzUnread = 0;
        try {
            $buzzUnread = Buzz::unreadCount($userId);
        } catch (\Throwable $e) {
            $buzzUnread = 0;
        }

        $model = (object)[
            'GreetingLine' => $this->greetingLine(),
            'TodayLabel' => date('l, M j'),
            'QuoteOfTheDay' => $quote,

            'BuzzUnread' => $buzzUnread,

            'MoodLabel' => $moodLabel,
            'ReflectionsThisWeek' => $reflectionsThisWeek,
            'LatestJournal' => $latestJournal,

            'TodayEventsCount' => $todayEventsCount,
            'NextEventLabel' => $nextEventLabel,

            'PendingTasks' => $pendingTasks,

            'StepsToday' => $stepsToday,
            'SleepToday' => $sleepToday,
            'WaterToday' => $waterToday,
            'Steps7' => $steps7,
            'Steps7Max' => max(1, $maxSteps),

            'Spent30' => $spent30,
            'Income30' => $income30,
            'NextBill' => $nextBill,

            'NextMaintenance' => $nextMaintenance,
            'NextTrip' => $nextTrip,

            'Insights' => $insights,

            'SearchQuery' => $searchQuery,
            'SearchResults' => $searchResults,
            'SearchCounts' => $searchCounts
        ];
        view('dashboard/index', ['Model' => $model]);
    }
}
