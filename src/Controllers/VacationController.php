<?php

namespace Routina\Controllers;

use Routina\Models\Vacation;
use Routina\Models\VacationChecklistItem;
use Routina\Models\VacationNote;
use Routina\Models\Transaction;
use Routina\Models\User;
use Routina\Services\CurrencyService;

class VacationController {
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dest = trim($_POST['destination'] ?? '');
            $start = $_POST['start_date'] ?? '';
            $end = $_POST['end_date'] ?? '';
            $status = $_POST['status'] ?? 'Planned';
            $budgetRaw = $_POST['budget'] ?? '';
            $notes = trim((string)($_POST['notes'] ?? ''));
            $budget = ($budgetRaw !== '' && is_numeric($budgetRaw)) ? (float)$budgetRaw : null;

            $startTs = $start ? strtotime($start) : false;
            $endTs = $end ? strtotime($end) : false;
            if ($dest === '' || !$startTs || !$endTs || $endTs < $startTs) {
                $vacations = Vacation::getAll($_SESSION['user_id']);
                view('vacation/index', [
                    'vacations' => $vacations,
                    'error' => 'Please enter a destination and a valid date range.'
                ]);
                return;
            }

            Vacation::create($_SESSION['user_id'], $dest, $start, $end, $status, $budget, $notes);
            header('Location: /vacation');
            exit;
        }

        $vacations = Vacation::getAll($_SESSION['user_id']);
        view('vacation/index', ['vacations' => $vacations]);
    }

    public function newTrip() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dest = trim($_POST['destination'] ?? '');
            $start = $_POST['start_date'] ?? '';
            $end = $_POST['end_date'] ?? '';
            $status = $_POST['status'] ?? 'Planned';
            $budgetRaw = $_POST['budget'] ?? '';
            $notes = trim((string)($_POST['notes'] ?? ''));
            $budget = ($budgetRaw !== '' && is_numeric($budgetRaw)) ? (float)$budgetRaw : null;

            $startTs = $start ? strtotime($start) : false;
            $endTs = $end ? strtotime($end) : false;
            if ($dest === '' || !$startTs || !$endTs || $endTs < $startTs) {
                view('vacation/new', ['error' => 'Please enter a destination and a valid date range.']);
                return;
            }

            Vacation::create($_SESSION['user_id'], $dest, $start, $end, $status, $budget, $notes);
            header('Location: /vacation');
            exit;
        }

        view('vacation/new');
    }

    public function trip() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $id = $_GET['id'] ?? '';
        if (!is_numeric($id)) {
            header('Location: /vacation');
            exit;
        }

        $vacation = Vacation::find($_SESSION['user_id'], (int)$id);
        if (!$vacation) {
            header('Location: /vacation');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!empty($_POST['checklist_text'])) {
                $text = trim($_POST['checklist_text']);
                if ($text !== '') {
                    VacationChecklistItem::create($_SESSION['user_id'], $vacation['id'], $text);
                }
            }

            if (!empty($_POST['toggle_checklist_id'])) {
                $toggleId = $_POST['toggle_checklist_id'];
                if (is_numeric($toggleId)) {
                    VacationChecklistItem::toggle($_SESSION['user_id'], (int)$toggleId);
                }
            }

            if (!empty($_POST['note_body'])) {
                $title = trim($_POST['note_title'] ?? '');
                $body = trim($_POST['note_body']);
                if ($body !== '') {
                    VacationNote::create($_SESSION['user_id'], $vacation['id'], $title, $body);
                }
            }

            header('Location: /vacation/trip?id=' . $vacation['id']);
            exit;
        }

        $checklist = VacationChecklistItem::getAll($_SESSION['user_id'], $vacation['id']);
        $notes = VacationNote::getAll($_SESSION['user_id'], $vacation['id']);

        $user = User::find((int)$_SESSION['user_id']);
        $currencyCode = CurrencyService::normalizeCode($user->currency ?? 'USD');
        if (!CurrencyService::isValidCode($currencyCode)) {
            $currencyCode = 'USD';
        }
        $currencySymbol = CurrencyService::symbolFor($currencyCode);
        $vacationActual = Transaction::totalsBaseByVacation($_SESSION['user_id'], (int)$vacation['id']);

        view('vacation/trip', [
            'vacation' => $vacation,
            'checklist' => $checklist,
            'notes' => $notes,
            'currencySymbol' => $currencySymbol,
            'currencyCode' => $currencyCode,
            'vacationActual' => $vacationActual
        ]);
    }

    public function edit() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $id = $_GET['id'] ?? '';
        if (!is_numeric($id)) {
            header('Location: /vacation');
            exit;
        }

        $vacation = Vacation::find($_SESSION['user_id'], (int)$id);
        if (!$vacation) {
            header('Location: /vacation');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dest = trim($_POST['destination'] ?? '');
            $start = $_POST['start_date'] ?? '';
            $end = $_POST['end_date'] ?? '';
            $status = $_POST['status'] ?? 'Planned';
            $budgetRaw = $_POST['budget'] ?? '';
            $notes = trim((string)($_POST['notes'] ?? ''));
            $budget = ($budgetRaw !== '' && is_numeric($budgetRaw)) ? (float)$budgetRaw : null;

            $startTs = $start ? strtotime($start) : false;
            $endTs = $end ? strtotime($end) : false;
            if ($dest === '' || !$startTs || !$endTs || $endTs < $startTs) {
                view('vacation/edit', [
                    'vacation' => $vacation,
                    'error' => 'Please enter a destination and a valid date range.'
                ]);
                return;
            }

            Vacation::update($_SESSION['user_id'], (int)$id, $dest, $start, $end, $status, $budget, $notes);
            header('Location: /vacation/trip?id=' . (int)$id);
            exit;
        }

        view('vacation/edit', ['vacation' => $vacation]);
    }

    public function delete() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /vacation');
            exit;
        }

        $id = $_POST['id'] ?? '';
        if (!is_numeric($id)) {
            header('Location: /vacation');
            exit;
        }

        // Also delete related checklist items and notes
        VacationChecklistItem::deleteAll($_SESSION['user_id'], (int)$id);
        VacationNote::deleteAll($_SESSION['user_id'], (int)$id);
        Vacation::delete($_SESSION['user_id'], (int)$id);

        header('Location: /vacation');
        exit;
    }
}
