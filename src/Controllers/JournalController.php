<?php

namespace Routina\Controllers;

use Routina\Models\Journal;

class JournalController {
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $date = $_POST['entry_date'] ?? date('Y-m-d');
            $content = trim($_POST['content'] ?? '');
            $mood = $_POST['mood'] ?? 'Happy';

            if ($content === '') {
                $entries = Journal::getAll($_SESSION['user_id']);
                view('journal/index', [
                    'entries' => $entries,
                    'error' => 'Please write a journal entry before saving.'
                ]);
                return;
            }

            Journal::create($_SESSION['user_id'], $date, $content, $mood);
            header('Location: /journal');
            exit;
        }

        $entries = Journal::getAll($_SESSION['user_id']);
        view('journal/index', ['entries' => $entries]);
    }

    public function today() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $today = date('Y-m-d');
        $entries = Journal::getByDate($_SESSION['user_id'], $today);
        view('journal/today', ['entries' => $entries, 'date' => $today]);
    }

    public function history() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $entries = Journal::getAll($_SESSION['user_id']);
        view('journal/history', ['entries' => $entries]);
    }

    public function viewEntry() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $id = $_GET['id'] ?? '';
        if (!is_numeric($id)) {
            header('Location: /journal');
            exit;
        }

        $entry = Journal::find($_SESSION['user_id'], (int)$id);
        if (!$entry) {
            header('Location: /journal');
            exit;
        }

        view('journal/view', ['entry' => $entry]);
    }
}
