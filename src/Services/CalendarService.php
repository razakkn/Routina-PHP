<?php

namespace Routina\Services;

use Routina\Config\Database;
use Routina\Models\User;
use Routina\Services\HolidayService;

/**
 * Calendar aggregation service.
 * 
 * Combines events from multiple sources into a unified calendar view:
 * - User-created calendar events
 * - Vacation trips (start/end)
 * - Family birthdays (recurring annually)
 * - Bill due dates
 * - Vehicle maintenance
 * - Vehicle insurance/registration expiry
 */
class CalendarService
{
    /**
     * Get all events for a user in a given month.
     *
     * @param int $userId User ID
     * @param int $year Year (e.g., 2026)
     * @param int $month Month (1-12)
     * @return array<string, array<int, array<string, mixed>>> Events keyed by date (Y-m-d)
     */
    public static function getMonthEvents(int $userId, int $year, int $month): array
    {
        $events = [];
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        // User calendar events
        $calendarEvents = self::getCalendarEvents($userId, $startDate, $endDate);
        foreach ($calendarEvents as $e) {
            $date = date('Y-m-d', strtotime($e['start_datetime']));
            $eventType = $e['type'] ?? 'event';
            $events[$date][] = [
                'type' => $eventType,
                'subtype' => $eventType,
                'title' => $e['title'],
                'time' => date('H:i', strtotime($e['start_datetime'])),
                'end_time' => date('H:i', strtotime($e['end_datetime'])),
                'color' => self::getEventColor($eventType),
                'icon' => self::getEventIcon($eventType),
                'id' => $e['id'],
                'source' => 'calendar',
                'is_recurring' => !empty($e['is_recurring']) ? 1 : 0
            ];
        }

        // Recurring user events (annual)
        $recurring = self::getRecurringEvents($userId);
        foreach ($recurring as $e) {
            $start = $e['start_datetime'] ?? null;
            if (!$start) continue;
            $monthNum = (int)date('m', strtotime($start));
            $dayNum = (int)date('d', strtotime($start));
            if (!checkdate($monthNum, $dayNum, $year)) {
                continue;
            }
            $occDate = sprintf('%04d-%02d-%02d', $year, $monthNum, $dayNum);
            if ($occDate < $startDate || $occDate > $endDate) {
                continue;
            }
            $events[$occDate][] = [
                'type' => $e['type'] ?? 'event',
                'subtype' => 'recurring',
                'title' => $e['title'] ?? 'Recurring event',
                'time' => date('H:i', strtotime($e['start_datetime'])),
                'end_time' => date('H:i', strtotime($e['end_datetime'] ?? $e['start_datetime'])),
                'color' => self::getEventColor($e['type'] ?? 'event'),
                'icon' => self::getEventIcon($e['type'] ?? 'event'),
                'id' => $e['id'] ?? null,
                'source' => 'calendar',
                'is_recurring' => 1
            ];
        }

        // Vacations
        $vacations = self::getVacations($userId, $startDate, $endDate);
        foreach ($vacations as $v) {
            $vStart = $v['start_date'] ?? null;
            $vEnd = $v['end_date'] ?? null;
            $status = strtolower($v['status'] ?? 'planned');
            
            if ($vStart) {
                $startDt = date('Y-m-d', strtotime($vStart));
                if ($startDt >= $startDate && $startDt <= $endDate) {
                    $events[$startDt][] = [
                        'type' => 'vacation',
                        'subtype' => 'start',
                        'title' => '✈️ ' . ($v['destination'] ?? 'Trip') . ' starts',
                        'status' => $status,
                        'color' => self::getVacationColor($status),
                        'icon' => '✈️',
                        'id' => $v['id'],
                        'source' => 'vacation'
                    ];
                }
            }
            
            if ($vEnd) {
                $endDt = date('Y-m-d', strtotime($vEnd));
                if ($endDt >= $startDate && $endDt <= $endDate && $endDt !== ($vStart ? date('Y-m-d', strtotime($vStart)) : null)) {
                    $events[$endDt][] = [
                        'type' => 'vacation',
                        'subtype' => 'end',
                        'title' => '🏠 ' . ($v['destination'] ?? 'Trip') . ' ends',
                        'status' => $status,
                        'color' => self::getVacationColor($status),
                        'icon' => '🏠',
                        'id' => $v['id'],
                        'source' => 'vacation'
                    ];
                }
            }
        }

        // Family birthdays (recurring annually)
        $birthdays = self::getBirthdays($userId, $month);
        foreach ($birthdays as $b) {
            $bday = $b['birthdate'] ?? null;
            if (!$bday) continue;
            
            $bdayMonth = (int)date('m', strtotime($bday));
            $bdayDay = (int)date('d', strtotime($bday));
            
            if ($bdayMonth !== $month) continue;
            
            $thisYearBday = sprintf('%04d-%02d-%02d', $year, $bdayMonth, $bdayDay);
            if ($thisYearBday >= $startDate && $thisYearBday <= $endDate) {
                $birthYear = (int)date('Y', strtotime($bday));
                $age = $year - $birthYear;
                $isDeceased = !empty($b['deathdate']);
                
                $events[$thisYearBday][] = [
                    'type' => 'birthday',
                    'subtype' => $isDeceased ? 'memorial' : 'birthday',
                    'title' => '🎂 ' . ($b['name'] ?? 'Family member') . ($age > 0 ? " turns {$age}" : "'s birthday"),
                    'relation' => $b['relation'] ?? '',
                    'age' => $age,
                    'color' => $isDeceased ? '#9ca3af' : '#ec4899',
                    'icon' => '🎂',
                    'id' => $b['id'],
                    'source' => 'family',
                    'deceased' => $isDeceased
                ];
            }
        }

        // Bills due
        $bills = self::getBills($userId, $startDate, $endDate);
        foreach ($bills as $bill) {
            $dueDate = $bill['due_date'] ?? null;
            if (!$dueDate) continue;
            
            $dueDt = date('Y-m-d', strtotime($dueDate));
            if ($dueDt >= $startDate && $dueDt <= $endDate) {
                $status = strtolower($bill['status'] ?? 'unpaid');
                $events[$dueDt][] = [
                    'type' => 'bill',
                    'subtype' => $status,
                    'title' => '💰 ' . ($bill['name'] ?? 'Bill') . ' due',
                    'amount' => (float)($bill['amount'] ?? 0),
                    'status' => $status,
                    'color' => $status === 'paid' ? '#10b981' : ($dueDt < date('Y-m-d') ? '#ef4444' : '#f59e0b'),
                    'icon' => '💰',
                    'id' => $bill['id'],
                    'source' => 'bill'
                ];
            }
        }

        // Vehicle maintenance
        $maintenance = self::getMaintenance($userId, $startDate, $endDate);
        foreach ($maintenance as $m) {
            $dueDate = $m['due_date'] ?? null;
            if (!$dueDate) continue;
            
            $dueDt = date('Y-m-d', strtotime($dueDate));
            if ($dueDt >= $startDate && $dueDt <= $endDate) {
                $status = strtolower($m['status'] ?? 'open');
                $events[$dueDt][] = [
                    'type' => 'maintenance',
                    'subtype' => $status,
                    'title' => '🔧 ' . ($m['title'] ?? 'Maintenance'),
                    'status' => $status,
                    'color' => in_array($status, ['done', 'completed']) ? '#10b981' : '#6366f1',
                    'icon' => '🔧',
                    'id' => $m['id'],
                    'vehicle_id' => $m['vehicle_id'] ?? null,
                    'source' => 'maintenance'
                ];
            }
        }

        // Vehicle insurance/registration expiry
        $vehicleReminders = self::getVehicleReminders($userId, $startDate, $endDate);
        foreach ($vehicleReminders as $r) {
            $events[$r['date']][] = $r;
        }

        // Public holidays (per user country)
        $holidayCountry = self::getUserHolidayCountry($userId);
        if ($holidayCountry !== '') {
            $holidays = HolidayService::getPublicHolidays($holidayCountry, $year);
            foreach ($holidays as $h) {
                $hDate = (string)($h['date'] ?? '');
                if ($hDate === '' || $hDate < $startDate || $hDate > $endDate) {
                    continue;
                }
                $name = (string)($h['localName'] ?? ($h['name'] ?? 'Holiday'));
                $events[$hDate][] = [
                    'type' => 'holiday',
                    'subtype' => 'public',
                    'title' => '🎉 ' . $name,
                    'color' => self::getEventColor('holiday'),
                    'icon' => self::getEventIcon('holiday'),
                    'source' => 'holiday',
                    'country' => $holidayCountry
                ];
            }
        }

        // Sort events within each day
        foreach ($events as $date => &$dayEvents) {
            usort($dayEvents, function ($a, $b) {
                $order = ['birthday' => 0, 'holiday' => 1, 'anniversary' => 2, 'occasion' => 3, 'vacation' => 4, 'event' => 5, 'bill' => 6, 'maintenance' => 7, 'vehicle' => 8];
                $aOrder = $order[$a['type']] ?? 99;
                $bOrder = $order[$b['type']] ?? 99;
                return $aOrder <=> $bOrder;
            });
        }

        return $events;
    }

    /**
     * Get upcoming reminders for dashboard widget.
     *
     * @param int $userId User ID
     * @param int $daysAhead Number of days to look ahead
     * @return array<string, array<int, array<string, mixed>>> Categorized reminders
     */
    public static function getUpcomingReminders(int $userId, int $daysAhead = 14): array
    {
        $today = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime("+{$daysAhead} days"));
        
        $reminders = [
            'today' => [],
            'tomorrow' => [],
            'this_week' => [],
            'upcoming' => [],
            'overdue' => []
        ];

        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $weekEnd = date('Y-m-d', strtotime('+7 days'));

        // Birthdays this month and next
        $currentMonth = (int)date('m');
        $nextMonth = $currentMonth === 12 ? 1 : $currentMonth + 1;
        $year = (int)date('Y');
        
        $birthdays = self::getBirthdays($userId, $currentMonth);
        $birthdaysNext = self::getBirthdays($userId, $nextMonth);
        
        foreach (array_merge($birthdays, $birthdaysNext) as $b) {
            $bday = $b['birthdate'] ?? null;
            if (!$bday || !empty($b['deathdate'])) continue;
            
            $bdayMonth = (int)date('m', strtotime($bday));
            $bdayDay = (int)date('d', strtotime($bday));
            $bdayYear = $bdayMonth < $currentMonth ? $year + 1 : $year;
            
            $thisYearBday = sprintf('%04d-%02d-%02d', $bdayYear, $bdayMonth, $bdayDay);
            
            if ($thisYearBday >= $today && $thisYearBday <= $endDate) {
                $birthYear = (int)date('Y', strtotime($bday));
                $age = $bdayYear - $birthYear;
                
                $item = [
                    'type' => 'birthday',
                    'title' => ($b['name'] ?? 'Family member') . "'s birthday" . ($age > 0 ? " ({$age})" : ''),
                    'date' => $thisYearBday,
                    'icon' => '🎂',
                    'color' => '#ec4899',
                    'link' => '/family'
                ];
                
                self::categorizeReminder($reminders, $item, $today, $tomorrow, $weekEnd);
            }
        }

        // Bills
        $bills = self::getBills($userId, date('Y-m-d', strtotime('-30 days')), $endDate);
        foreach ($bills as $bill) {
            $dueDate = $bill['due_date'] ?? null;
            $status = strtolower($bill['status'] ?? 'unpaid');
            
            if (!$dueDate || $status === 'paid') continue;
            
            $item = [
                'type' => 'bill',
                'title' => ($bill['name'] ?? 'Bill') . ' - $' . number_format((float)($bill['amount'] ?? 0), 2),
                'date' => $dueDate,
                'icon' => '💰',
                'color' => $dueDate < $today ? '#ef4444' : '#f59e0b',
                'link' => '/finance/bills'
            ];
            
            if ($dueDate < $today) {
                $item['overdue'] = true;
                $reminders['overdue'][] = $item;
            } elseif ($dueDate <= $endDate) {
                self::categorizeReminder($reminders, $item, $today, $tomorrow, $weekEnd);
            }
        }

        // Vehicle maintenance
        $maintenance = self::getMaintenance($userId, date('Y-m-d', strtotime('-30 days')), $endDate);
        foreach ($maintenance as $m) {
            $dueDate = $m['due_date'] ?? null;
            $status = strtolower($m['status'] ?? 'open');
            
            if (!$dueDate || in_array($status, ['done', 'completed'])) continue;
            
            $item = [
                'type' => 'maintenance',
                'title' => $m['title'] ?? 'Vehicle maintenance',
                'date' => $dueDate,
                'icon' => '🔧',
                'color' => $dueDate < $today ? '#ef4444' : '#6366f1',
                'link' => '/vehicle'
            ];
            
            if ($dueDate < $today) {
                $item['overdue'] = true;
                $reminders['overdue'][] = $item;
            } elseif ($dueDate <= $endDate) {
                self::categorizeReminder($reminders, $item, $today, $tomorrow, $weekEnd);
            }
        }

        // Vacations starting soon
        $vacations = self::getVacations($userId, $today, $endDate);
        foreach ($vacations as $v) {
            $startDate = $v['start_date'] ?? null;
            $status = strtolower($v['status'] ?? 'planned');
            
            if (!$startDate || $status === 'completed') continue;
            
            $item = [
                'type' => 'vacation',
                'title' => '✈️ ' . ($v['destination'] ?? 'Trip'),
                'date' => $startDate,
                'icon' => '✈️',
                'color' => '#14b8a6',
                'link' => '/vacation/trip/' . $v['id']
            ];
            
            self::categorizeReminder($reminders, $item, $today, $tomorrow, $weekEnd);
        }

        // Calendar events
        $events = self::getCalendarEvents($userId, $today, $endDate);
        foreach ($events as $e) {
            $eventDate = date('Y-m-d', strtotime($e['start_datetime']));
            
            $item = [
                'type' => 'event',
                'title' => $e['title'] ?? 'Event',
                'date' => $eventDate,
                'time' => date('H:i', strtotime($e['start_datetime'])),
                'icon' => self::getEventIcon($e['type'] ?? 'event'),
                'color' => self::getEventColor($e['type'] ?? 'event'),
                'link' => '/calendar'
            ];
            
            self::categorizeReminder($reminders, $item, $today, $tomorrow, $weekEnd);
        }

        // Recurring events (next occurrence)
        $recurring = self::getRecurringEvents($userId);
        foreach ($recurring as $e) {
            $start = $e['start_datetime'] ?? null;
            if (!$start) continue;
            $monthNum = (int)date('m', strtotime($start));
            $dayNum = (int)date('d', strtotime($start));
            $next = self::nextOccurrenceDate($monthNum, $dayNum, $today, $endDate);
            if ($next === '') continue;

            $item = [
                'type' => $e['type'] ?? 'event',
                'title' => $e['title'] ?? 'Recurring event',
                'date' => $next,
                'icon' => self::getEventIcon($e['type'] ?? 'event'),
                'color' => self::getEventColor($e['type'] ?? 'event'),
                'link' => '/calendar'
            ];
            self::categorizeReminder($reminders, $item, $today, $tomorrow, $weekEnd);
        }

        // Holidays (public)
        $holidayCountry = self::getUserHolidayCountry($userId);
        if ($holidayCountry !== '') {
            $years = [(int)date('Y'), (int)date('Y', strtotime($endDate))];
            $years = array_unique($years);
            foreach ($years as $y) {
                $holidays = HolidayService::getPublicHolidays($holidayCountry, $y);
                foreach ($holidays as $h) {
                    $hDate = (string)($h['date'] ?? '');
                    if ($hDate === '' || $hDate < $today || $hDate > $endDate) {
                        continue;
                    }
                    $name = (string)($h['localName'] ?? ($h['name'] ?? 'Holiday'));
                    $item = [
                        'type' => 'holiday',
                        'title' => $name,
                        'date' => $hDate,
                        'icon' => self::getEventIcon('holiday'),
                        'color' => self::getEventColor('holiday'),
                        'link' => '/calendar'
                    ];
                    self::categorizeReminder($reminders, $item, $today, $tomorrow, $weekEnd);
                }
            }
        }

        // Sort each category by date
        foreach ($reminders as &$category) {
            usort($category, fn($a, $b) => ($a['date'] ?? '') <=> ($b['date'] ?? ''));
        }

        return $reminders;
    }

    /**
     * Get months that have events (for quick navigation).
     *
     * @param int $userId User ID
     * @param int $monthsBack How many months back to look
     * @param int $monthsForward How many months forward to look
     * @return array<int, array{year: int, month: int, label: string, count: int}>
     */
    public static function getMonthsWithEvents(int $userId, int $monthsBack = 6, int $monthsForward = 12): array
    {
        $months = [];
        $startDate = date('Y-m-01', strtotime("-{$monthsBack} months"));
        $endDate = date('Y-m-t', strtotime("+{$monthsForward} months"));
        
        // Get all event dates in range
        $db = Database::getConnection();
        $driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);
        
        // Calendar events
        $eventMonths = [];
        
        // User events
        if ($driver === 'pgsql') {
            $sql = "SELECT DISTINCT TO_CHAR(start_datetime::timestamp, 'YYYY-MM') as month FROM calendar_events WHERE user_id = :uid AND start_datetime >= :start AND start_datetime <= :end";
        } elseif ($driver === 'mysql') {
            $sql = "SELECT DISTINCT DATE_FORMAT(start_datetime, '%Y-%m') as month FROM calendar_events WHERE user_id = :uid AND start_datetime >= :start AND start_datetime <= :end";
        } else {
            $sql = "SELECT DISTINCT strftime('%Y-%m', start_datetime) as month FROM calendar_events WHERE user_id = :uid AND start_datetime >= :start AND start_datetime <= :end";
        }
        $stmt = $db->prepare($sql);
        $stmt->execute(['uid' => $userId, 'start' => $startDate, 'end' => $endDate]);
        foreach ($stmt->fetchAll() as $r) {
            $eventMonths[$r['month']] = ($eventMonths[$r['month']] ?? 0) + 1;
        }
        
        // Vacations
        if ($driver === 'pgsql') {
            $sql = "SELECT DISTINCT TO_CHAR(start_date::date, 'YYYY-MM') as month FROM vacations WHERE user_id = :uid AND start_date >= :start AND start_date <= :end";
        } elseif ($driver === 'mysql') {
            $sql = "SELECT DISTINCT DATE_FORMAT(start_date, '%Y-%m') as month FROM vacations WHERE user_id = :uid AND start_date >= :start AND start_date <= :end";
        } else {
            $sql = "SELECT DISTINCT strftime('%Y-%m', start_date) as month FROM vacations WHERE user_id = :uid AND start_date >= :start AND start_date <= :end";
        }
        $stmt = $db->prepare($sql);
        $stmt->execute(['uid' => $userId, 'start' => $startDate, 'end' => $endDate]);
        foreach ($stmt->fetchAll() as $r) {
            if ($r['month']) $eventMonths[$r['month']] = ($eventMonths[$r['month']] ?? 0) + 1;
        }
        
        // Bills
        if ($driver === 'pgsql') {
            $sql = "SELECT DISTINCT TO_CHAR(due_date::date, 'YYYY-MM') as month FROM finance_bills WHERE user_id = :uid AND due_date >= :start AND due_date <= :end";
        } elseif ($driver === 'mysql') {
            $sql = "SELECT DISTINCT DATE_FORMAT(due_date, '%Y-%m') as month FROM finance_bills WHERE user_id = :uid AND due_date >= :start AND due_date <= :end";
        } else {
            $sql = "SELECT DISTINCT strftime('%Y-%m', due_date) as month FROM finance_bills WHERE user_id = :uid AND due_date >= :start AND due_date <= :end";
        }
        $stmt = $db->prepare($sql);
        $stmt->execute(['uid' => $userId, 'start' => $startDate, 'end' => $endDate]);
        foreach ($stmt->fetchAll() as $r) {
            if ($r['month']) $eventMonths[$r['month']] = ($eventMonths[$r['month']] ?? 0) + 1;
        }
        
        // Birthdays (all months with family birthdays)
        if ($driver === 'pgsql') {
            $bmonthExpr = "EXTRACT(MONTH FROM birthdate::date)::int";
        } elseif ($driver === 'mysql') {
            $bmonthExpr = "MONTH(birthdate)";
        } else {
            $bmonthExpr = "CAST(strftime('%m', birthdate) AS INTEGER)";
        }
        $sql = "SELECT DISTINCT " . $bmonthExpr . " as bmonth FROM family_members WHERE user_id = :uid AND birthdate IS NOT NULL AND birthdate != ''";
        $stmt = $db->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        $birthdayMonths = [];
        foreach ($stmt->fetchAll() as $r) {
            $birthdayMonths[] = (int)$r['bmonth'];
        }
        
        // Build result
        $current = strtotime($startDate);
        $end = strtotime($endDate);
        
        while ($current <= $end) {
            $yearMonth = date('Y-m', $current);
            $month = (int)date('m', $current);
            $year = (int)date('Y', $current);
            
            $count = $eventMonths[$yearMonth] ?? 0;
            
            // Add birthday count
            if (in_array($month, $birthdayMonths)) {
                $count++;
            }
            
            if ($count > 0) {
                $months[] = [
                    'year' => $year,
                    'month' => $month,
                    'label' => date('M Y', $current),
                    'count' => $count
                ];
            }
            
            $current = strtotime('+1 month', $current);
        }
        
        return $months;
    }

    // ─── Private Helper Methods ──────────────────────────────────────────────

    private static function categorizeReminder(array &$reminders, array $item, string $today, string $tomorrow, string $weekEnd): void
    {
        $date = $item['date'] ?? '';
        
        if ($date === $today) {
            $reminders['today'][] = $item;
        } elseif ($date === $tomorrow) {
            $reminders['tomorrow'][] = $item;
        } elseif ($date <= $weekEnd) {
            $reminders['this_week'][] = $item;
        } else {
            $reminders['upcoming'][] = $item;
        }
    }

    private static function getCalendarEvents(int $userId, string $startDate, string $endDate): array
    {
        $db = Database::getConnection();
        $driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);
        
        if ($driver === 'pgsql') {
            $sql = "SELECT * FROM calendar_events WHERE user_id = :uid AND start_datetime::date >= :start AND start_datetime::date <= :end ORDER BY start_datetime ASC";
        } elseif ($driver === 'mysql') {
            $sql = "SELECT * FROM calendar_events WHERE user_id = :uid AND DATE(start_datetime) >= :start AND DATE(start_datetime) <= :end ORDER BY start_datetime ASC";
        } else {
            $sql = "SELECT * FROM calendar_events WHERE user_id = :uid AND date(start_datetime) >= :start AND date(start_datetime) <= :end ORDER BY start_datetime ASC";
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute(['uid' => $userId, 'start' => $startDate, 'end' => $endDate]);
        return $stmt->fetchAll() ?: [];
    }

    private static function getVacations(int $userId, string $startDate, string $endDate): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM vacations WHERE user_id = :uid AND ((start_date >= :start1 AND start_date <= :end1) OR (end_date >= :start2 AND end_date <= :end2))");
        $stmt->execute(['uid' => $userId, 'start1' => $startDate, 'end1' => $endDate, 'start2' => $startDate, 'end2' => $endDate]);
        return $stmt->fetchAll() ?: [];
    }

    private static function getBirthdays(int $userId, int $month): array
    {
        $db = Database::getConnection();
        $driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);
        
        if ($driver === 'pgsql') {
            $sql = "SELECT * FROM family_members WHERE user_id = :uid AND birthdate IS NOT NULL AND birthdate != '' AND EXTRACT(MONTH FROM birthdate::date) = :month";
        } elseif ($driver === 'mysql') {
            $sql = "SELECT * FROM family_members WHERE user_id = :uid AND birthdate IS NOT NULL AND birthdate != '' AND MONTH(birthdate) = :month";
        } else {
            $sql = "SELECT * FROM family_members WHERE user_id = :uid AND birthdate IS NOT NULL AND birthdate != '' AND CAST(strftime('%m', birthdate) AS INTEGER) = :month";
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute(['uid' => $userId, 'month' => $month]);
        return $stmt->fetchAll() ?: [];
    }

    private static function getBills(int $userId, string $startDate, string $endDate): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM finance_bills WHERE user_id = :uid AND due_date >= :start AND due_date <= :end ORDER BY due_date ASC");
        $stmt->execute(['uid' => $userId, 'start' => $startDate, 'end' => $endDate]);
        return $stmt->fetchAll() ?: [];
    }

    private static function getMaintenance(int $userId, string $startDate, string $endDate): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM vehicle_maintenance WHERE user_id = :uid AND due_date >= :start AND due_date <= :end ORDER BY due_date ASC");
        $stmt->execute(['uid' => $userId, 'start' => $startDate, 'end' => $endDate]);
        return $stmt->fetchAll() ?: [];
    }

    private static function getVehicleReminders(int $userId, string $startDate, string $endDate): array
    {
        $db = Database::getConnection();
        $reminders = [];
        
        // Get vehicles with expiry dates in range
        $stmt = $db->prepare("SELECT * FROM vehicles WHERE user_id = :uid AND (
            (registration_expiry >= :start1 AND registration_expiry <= :end1) OR
            (insurance_end_date >= :start2 AND insurance_end_date <= :end2)
        )");
        $stmt->execute(['uid' => $userId, 'start1' => $startDate, 'end1' => $endDate, 'start2' => $startDate, 'end2' => $endDate]);
        $vehicles = $stmt->fetchAll() ?: [];
        
        foreach ($vehicles as $v) {
            $regExpiry = $v['registration_expiry'] ?? null;
            $insExpiry = $v['insurance_end_date'] ?? null;
            $vehicleName = trim(($v['make'] ?? '') . ' ' . ($v['model'] ?? '')) ?: 'Vehicle';
            
            if ($regExpiry && $regExpiry >= $startDate && $regExpiry <= $endDate) {
                $reminders[] = [
                    'type' => 'vehicle',
                    'subtype' => 'registration',
                    'title' => '📋 ' . $vehicleName . ' registration expires',
                    'date' => $regExpiry,
                    'color' => $regExpiry < date('Y-m-d') ? '#ef4444' : '#8b5cf6',
                    'icon' => '📋',
                    'id' => $v['id'],
                    'source' => 'vehicle'
                ];
            }
            
            if ($insExpiry && $insExpiry >= $startDate && $insExpiry <= $endDate) {
                $reminders[] = [
                    'type' => 'vehicle',
                    'subtype' => 'insurance',
                    'title' => '🛡️ ' . $vehicleName . ' insurance expires',
                    'date' => $insExpiry,
                    'color' => $insExpiry < date('Y-m-d') ? '#ef4444' : '#0ea5e9',
                    'icon' => '🛡️',
                    'id' => $v['id'],
                    'source' => 'vehicle'
                ];
            }
        }
        
        return $reminders;
    }

    private static function getEventColor(string $type): string
    {
        return match($type) {
            'meeting' => '#6366f1',
            'reminder' => '#f59e0b',
            'event' => '#3b82f6',
            'anniversary' => '#f472b6',
            'occasion' => '#22c55e',
            'holiday' => '#0ea5e9',
            default => '#6b7280'
        };
    }

    private static function getEventIcon(string $type): string
    {
        return match($type) {
            'meeting' => '👥',
            'reminder' => '⏰',
            'event' => '📅',
            'anniversary' => '💞',
            'occasion' => '🎉',
            'holiday' => '🏖️',
            default => '📌'
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function getRecurringEvents(int $userId): array
    {
        $db = Database::getConnection();
        $driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $sql = "SELECT * FROM calendar_events WHERE user_id = :uid AND is_recurring = 1";
        if ($driver === 'mysql') {
            $sql = "SELECT * FROM calendar_events WHERE user_id = :uid AND is_recurring = 1";
        } elseif ($driver === 'pgsql') {
            $sql = "SELECT * FROM calendar_events WHERE user_id = :uid AND is_recurring = true";
        }
        $stmt = $db->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    private static function getUserHolidayCountry(int $userId): string
    {
        $user = User::find($userId);
        if (!$user || empty($user->holiday_country)) {
            return '';
        }
        return strtoupper((string)$user->holiday_country);
    }

    private static function nextOccurrenceDate(int $month, int $day, string $today, string $endDate): string
    {
        $year = (int)date('Y', strtotime($today));
        $candidate = self::safeDate($year, $month, $day);
        if ($candidate !== '' && $candidate >= $today && $candidate <= $endDate) {
            return $candidate;
        }
        $candidate = self::safeDate($year + 1, $month, $day);
        if ($candidate !== '' && $candidate >= $today && $candidate <= $endDate) {
            return $candidate;
        }
        return '';
    }

    private static function safeDate(int $year, int $month, int $day): string
    {
        if (!checkdate($month, $day, $year)) {
            return '';
        }
        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    /**
     * Upcoming user events (including recurring) for the next N days.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getUpcomingUserEvents(int $userId, int $daysAhead = 30): array
    {
        $today = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime("+{$daysAhead} days"));

        $events = self::getCalendarEvents($userId, $today, $endDate);
        $items = [];
        foreach ($events as $e) {
            $items[] = $e;
        }

        $recurring = self::getRecurringEvents($userId);
        foreach ($recurring as $e) {
            $start = $e['start_datetime'] ?? null;
            if (!$start) continue;
            $monthNum = (int)date('m', strtotime($start));
            $dayNum = (int)date('d', strtotime($start));
            $next = self::nextOccurrenceDate($monthNum, $dayNum, $today, $endDate);
            if ($next === '') continue;

            $items[] = array_merge($e, [
                'start_datetime' => $next . ' ' . date('H:i', strtotime($e['start_datetime'])),
                'end_datetime' => $next . ' ' . date('H:i', strtotime($e['end_datetime'] ?? $e['start_datetime'])),
                'is_recurring' => 1
            ]);
        }

        usort($items, function ($a, $b) {
            return strcmp((string)($a['start_datetime'] ?? ''), (string)($b['start_datetime'] ?? ''));
        });

        return array_slice($items, 0, 10);
    }

    private static function getVacationColor(string $status): string
    {
        return match($status) {
            'idea' => '#9ca3af',
            'planned' => '#3b82f6',
            'booked' => '#10b981',
            'completed' => '#6b7280',
            default => '#14b8a6'
        };
    }
}
