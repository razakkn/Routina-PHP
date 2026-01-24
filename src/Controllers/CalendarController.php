<?php

namespace Routina\Controllers;

use Routina\Models\Calendar;
use Routina\Services\CalendarService;

class CalendarController
{
    public function index()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $userId = (int)$_SESSION['user_id'];

        // Handle event creation
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($_POST['title'] ?? '');
            $start = $_POST['start'] ?? '';
            $end = $_POST['end'] ?? '';
            $type = $_POST['type'] ?? 'event';

            $allowedTypes = ['event', 'meeting', 'reminder'];
            $startTs = $start ? strtotime($start) : false;
            $endTs = $end ? strtotime($end) : false;

            if ($title === '' || !$startTs || !$endTs || $endTs < $startTs || !in_array($type, $allowedTypes, true)) {
                return $this->renderCalendar($userId, [
                    'error' => 'Please provide a title, valid time range, and type.'
                ]);
            }

            Calendar::create($userId, $title, $start, $end, $type);
            header('Location: /calendar');
            exit;
        }

        return $this->renderCalendar($userId);
    }

    /**
     * Delete a calendar event.
     */
    public function delete()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $userId = (int)$_SESSION['user_id'];
        $eventId = (int)($_POST['event_id'] ?? $_GET['id'] ?? 0);

        if ($eventId > 0) {
            Calendar::delete($userId, $eventId);
        }

        header('Location: /calendar');
        exit;
    }

    /**
     * API endpoint for getting events (JSON).
     */
    public function apiEvents()
    {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $userId = (int)$_SESSION['user_id'];
        $year = (int)($_GET['year'] ?? date('Y'));
        $month = (int)($_GET['month'] ?? date('m'));

        $events = CalendarService::getMonthEvents($userId, $year, $month);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'year' => $year,
            'month' => $month,
            'events' => $events
        ]);
        exit;
    }

    /**
     * Render the calendar view.
     */
    private function renderCalendar(int $userId, array $extra = []): void
    {
        // Get year/month from query params or default to current
        $year = (int)($_GET['year'] ?? date('Y'));
        $month = (int)($_GET['month'] ?? date('m'));

        // Clamp values
        if ($month < 1) { $month = 1; $year--; }
        if ($month > 12) { $month = 12; $year++; }
        if ($year < 2000) $year = 2000;
        if ($year > 2100) $year = 2100;

        // Filter type
        $filterType = $_GET['filter'] ?? 'all';
        $validFilters = ['all', 'event', 'vacation', 'birthday', 'bill', 'maintenance', 'vehicle'];
        if (!in_array($filterType, $validFilters)) {
            $filterType = 'all';
        }

        // Get events for the month
        $events = CalendarService::getMonthEvents($userId, $year, $month);

        // Apply filter
        if ($filterType !== 'all') {
            foreach ($events as $date => &$dayEvents) {
                $dayEvents = array_filter($dayEvents, fn($e) => ($e['type'] ?? '') === $filterType);
                if (empty($dayEvents)) {
                    unset($events[$date]);
                }
            }
        }

        // Get months with events for quick navigation
        $monthsWithEvents = CalendarService::getMonthsWithEvents($userId);

        // Calendar grid data
        $firstDay = strtotime("{$year}-{$month}-01");
        $daysInMonth = (int)date('t', $firstDay);
        $startWeekday = (int)date('w', $firstDay); // 0 = Sunday
        $monthName = date('F', $firstDay);

        // Previous/next month links
        $prevMonth = $month - 1;
        $prevYear = $year;
        if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }

        $nextMonth = $month + 1;
        $nextYear = $year;
        if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

        // Build calendar grid
        $calendarWeeks = [];
        $currentWeek = [];

        // Fill empty cells before first day
        for ($i = 0; $i < $startWeekday; $i++) {
            $currentWeek[] = null;
        }

        // Fill days
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $currentWeek[] = [
                'day' => $day,
                'date' => $date,
                'isToday' => $date === date('Y-m-d'),
                'events' => $events[$date] ?? []
            ];

            if (count($currentWeek) === 7) {
                $calendarWeeks[] = $currentWeek;
                $currentWeek = [];
            }
        }

        // Fill remaining cells
        if (!empty($currentWeek)) {
            while (count($currentWeek) < 7) {
                $currentWeek[] = null;
            }
            $calendarWeeks[] = $currentWeek;
        }

        // Upcoming events list (next 10)
        $upcomingEvents = Calendar::upcoming($userId);

        view('calendar/index', array_merge([
            'year' => $year,
            'month' => $month,
            'monthName' => $monthName,
            'calendarWeeks' => $calendarWeeks,
            'events' => $events,
            'upcomingEvents' => $upcomingEvents,
            'monthsWithEvents' => $monthsWithEvents,
            'filterType' => $filterType,
            'prevMonth' => $prevMonth,
            'prevYear' => $prevYear,
            'nextMonth' => $nextMonth,
            'nextYear' => $nextYear,
            'today' => date('Y-m-d')
        ], $extra));
    }
}
