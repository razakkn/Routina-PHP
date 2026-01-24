<?php

namespace Routina\Controllers;

use Routina\Models\Calendar;

class CalendarController {
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($_POST['title'] ?? '');
            $start = $_POST['start'] ?? '';
            $end = $_POST['end'] ?? '';
            $type = $_POST['type'] ?? 'event';

            $allowedTypes = ['event', 'meeting', 'reminder'];
            $startTs = $start ? strtotime($start) : false;
            $endTs = $end ? strtotime($end) : false;

            if ($title === '' || !$startTs || !$endTs || $endTs < $startTs || !in_array($type, $allowedTypes, true)) {
                $events = Calendar::upcoming($_SESSION['user_id']);
                view('calendar/index', [
                    'events' => $events,
                    'error' => 'Please provide a title, valid time range, and type.'
                ]);
                return;
            }

            Calendar::create(
                $_SESSION['user_id'],
                $title,
                $start,
                $end,
                $type
            );
            header('Location: /calendar');
            exit;
        }

        $events = Calendar::upcoming($_SESSION['user_id']);
        view('calendar/index', ['events' => $events]);
    }
}
