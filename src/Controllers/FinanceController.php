<?php

namespace Routina\Controllers;

use Routina\Models\Transaction;
use Routina\Models\FinanceAsset;
use Routina\Models\FinanceBill;
use Routina\Models\FinanceBudget;
use Routina\Models\FinanceIncome;
use Routina\Models\FinanceSaving;
use Routina\Models\FinanceReflection;
use Routina\Models\FinanceDiary;

class FinanceController {
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $desc = trim($_POST['description'] ?? '');
            $amountRaw = $_POST['amount'] ?? '';
            $type = $_POST['type'] ?? 'expense';
            $date = $_POST['date'] ?? date('Y-m-d');

            $allowedTypes = ['expense', 'income'];
            if ($desc === '' || $amountRaw === '' || !is_numeric($amountRaw) || !in_array($type, $allowedTypes, true)) {
                $transactions = Transaction::getAll($_SESSION['user_id']);
                view('finance/index', [
                    'transactions' => $transactions,
                    'error' => 'Please provide a description, valid amount, and type.'
                ]);
                return;
            }

            Transaction::create($_SESSION['user_id'], $desc, (float)$amountRaw, $type, $date);
            header('Location: /finance');
            exit;
        }

        $transactions = Transaction::getAll($_SESSION['user_id']);
        view('finance/index', ['transactions' => $transactions]);
    }

    public function assets() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $type = trim($_POST['asset_type'] ?? '');
            $valueRaw = $_POST['value'] ?? '';
            $notes = trim($_POST['notes'] ?? '');

            if ($name === '' || $type === '' || $valueRaw === '' || !is_numeric($valueRaw)) {
                $assets = FinanceAsset::getAll($_SESSION['user_id']);
                view('finance/assets', ['assets' => $assets, 'error' => 'Please provide a name, type, and valid value.']);
                return;
            }

            FinanceAsset::create($_SESSION['user_id'], $name, $type, (float)$valueRaw, $notes);
            header('Location: /finance/assets');
            exit;
        }

        $assets = FinanceAsset::getAll($_SESSION['user_id']);
        view('finance/assets', ['assets' => $assets]);
    }

    public function bills() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $amountRaw = $_POST['amount'] ?? '';
            $dueDate = $_POST['due_date'] ?? '';
            $status = $_POST['status'] ?? 'unpaid';

            if ($name === '' || $amountRaw === '' || !is_numeric($amountRaw) || $dueDate === '') {
                $bills = FinanceBill::getAll($_SESSION['user_id']);
                view('finance/bills', ['bills' => $bills, 'error' => 'Please provide a name, amount, and due date.']);
                return;
            }

            FinanceBill::create($_SESSION['user_id'], $name, (float)$amountRaw, $dueDate, $status);
            header('Location: /finance/bills');
            exit;
        }

        $bills = FinanceBill::getAll($_SESSION['user_id']);
        view('finance/bills', ['bills' => $bills]);
    }

    public function budgets() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $category = trim($_POST['category'] ?? '');
            $amountRaw = $_POST['amount'] ?? '';
            $month = $_POST['month'] ?? '';

            if ($category === '' || $amountRaw === '' || !is_numeric($amountRaw) || $month === '') {
                $budgets = FinanceBudget::getAll($_SESSION['user_id']);
                view('finance/budgets', ['budgets' => $budgets, 'error' => 'Please provide a category, amount, and month.']);
                return;
            }

            FinanceBudget::create($_SESSION['user_id'], $category, (float)$amountRaw, $month);
            header('Location: /finance/budgets');
            exit;
        }

        $budgets = FinanceBudget::getAll($_SESSION['user_id']);
        view('finance/budgets', ['budgets' => $budgets]);
    }

    public function income() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $source = trim($_POST['source'] ?? '');
            $amountRaw = $_POST['amount'] ?? '';
            $date = $_POST['received_date'] ?? '';

            if ($source === '' || $amountRaw === '' || !is_numeric($amountRaw) || $date === '') {
                $income = FinanceIncome::getAll($_SESSION['user_id']);
                view('finance/income', ['income' => $income, 'error' => 'Please provide a source, amount, and date.']);
                return;
            }

            FinanceIncome::create($_SESSION['user_id'], $source, (float)$amountRaw, $date);
            header('Location: /finance/income');
            exit;
        }

        $income = FinanceIncome::getAll($_SESSION['user_id']);
        view('finance/income', ['income' => $income]);
    }

    public function savings() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $goal = trim($_POST['goal'] ?? '');
            $targetRaw = $_POST['target_amount'] ?? '';
            $currentRaw = $_POST['current_amount'] ?? '';

            if ($goal === '' || $targetRaw === '' || !is_numeric($targetRaw)) {
                $savings = FinanceSaving::getAll($_SESSION['user_id']);
                view('finance/savings', ['savings' => $savings, 'error' => 'Please provide a goal and target amount.']);
                return;
            }

            $current = ($currentRaw !== '' && is_numeric($currentRaw)) ? (float)$currentRaw : 0;
            FinanceSaving::create($_SESSION['user_id'], $goal, (float)$targetRaw, $current);
            header('Location: /finance/savings');
            exit;
        }

        $savings = FinanceSaving::getAll($_SESSION['user_id']);
        view('finance/savings', ['savings' => $savings]);
    }

    public function reflection() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $month = $_POST['month'] ?? '';
            $summary = trim($_POST['summary'] ?? '');

            if ($month === '' || $summary === '') {
                $reflections = FinanceReflection::getAll($_SESSION['user_id']);
                view('finance/reflection', ['reflections' => $reflections, 'error' => 'Please provide a month and summary.']);
                return;
            }

            FinanceReflection::create($_SESSION['user_id'], $month, $summary);
            header('Location: /finance/reflection');
            exit;
        }

        $reflections = FinanceReflection::getAll($_SESSION['user_id']);
        view('finance/reflection', ['reflections' => $reflections]);
    }

    public function diary() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $date = $_POST['entry_date'] ?? date('Y-m-d');
            $notes = trim($_POST['notes'] ?? '');

            if ($notes === '') {
                $entries = FinanceDiary::getAll($_SESSION['user_id']);
                view('finance/diary', ['entries' => $entries, 'error' => 'Please write a note before saving.']);
                return;
            }

            FinanceDiary::create($_SESSION['user_id'], $date, $notes);
            header('Location: /finance/diary');
            exit;
        }

        $entries = FinanceDiary::getAll($_SESSION['user_id']);
        view('finance/diary', ['entries' => $entries]);
    }
}
