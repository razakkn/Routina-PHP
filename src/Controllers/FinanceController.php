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
use Routina\Models\User;
use Routina\Models\Vacation;
use Routina\Services\CurrencyService;

class FinanceController {
    private function getUserCurrencyCode(): string {
        $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
        if (!$userId) {
            return 'USD';
        }

        $user = User::find($userId);
        if (!$user) {
            return 'USD';
        }

        $code = CurrencyService::normalizeCode($user->currency ?? 'USD');
        return CurrencyService::isValidCode($code) ? $code : 'USD';
    }

    private function httpGetJsonLocal(string $url, int $timeoutSeconds = 5): ?array {
        if (function_exists('http_get_json')) {
            $doc = http_get_json($url, $timeoutSeconds);
            if (is_array($doc)) {
                return $doc;
            }
        }

        $baseHttp = [
            'method' => 'GET',
            'timeout' => $timeoutSeconds,
            'header' => "Accept: application/json\r\nUser-Agent: RoutinaApp/1.0 (finance)\r\n"
        ];

        $contexts = [
            stream_context_create([
                'http' => $baseHttp,
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true
                ]
            ]),
            // Retry with relaxed SSL verification (common on Windows without CA bundle).
            stream_context_create([
                'http' => $baseHttp,
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ])
        ];

        foreach ($contexts as $context) {
            $raw = @file_get_contents($url, false, $context);
            if (!is_string($raw) || $raw === '') {
                continue;
            }

            $doc = json_decode($raw, true);
            if (is_array($doc)) {
                return $doc;
            }
        }

        return null;
    }

    private function fetchFxRate(string $from, string $to): ?float {
        if ($from === $to) {
            return 1.0;
        }

        $from = CurrencyService::normalizeCode($from);
        $to = CurrencyService::normalizeCode($to);
        if ($from === '' || $to === '') {
            return null;
        }

        $providers = [
            [
                'url' => 'https://api.frankfurter.app/latest?from=' . urlencode($from) . '&to=' . urlencode($to),
                'extract' => function ($doc) use ($to) {
                    return $doc['rates'][$to] ?? null;
                }
            ],
            [
                'url' => 'https://api.exchangerate.host/latest?base=' . urlencode($from) . '&symbols=' . urlencode($to),
                'extract' => function ($doc) use ($to) {
                    return $doc['rates'][$to] ?? null;
                }
            ],
            [
                'url' => 'https://open.er-api.com/v6/latest/' . urlencode($from),
                'extract' => function ($doc) use ($to) {
                    return $doc['rates'][$to] ?? null;
                }
            ]
        ];

        foreach ($providers as $provider) {
            $doc = $this->httpGetJsonLocal($provider['url'], 6);
            if (!is_array($doc)) {
                continue;
            }
            $rate = $provider['extract']($doc);
            if (is_numeric($rate) && (float)$rate > 0) {
                return (float)$rate;
            }
        }

        return null;
    }

    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $currencyCode = $this->getUserCurrencyCode();
        $month = isset($_GET['month']) ? (string)$_GET['month'] : date('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }

        $currencyOptions = CurrencyService::all();
        $vacations = Vacation::getAll($_SESSION['user_id']);
        if (!isset($currencyOptions[$currencyCode])) {
            $currencyOptions[$currencyCode] = $currencyCode;
            ksort($currencyOptions);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'delete') {
                $deleteId = (int)($_POST['transaction_id'] ?? 0);
                if ($deleteId > 0) {
                    Transaction::deleteByIdForUser($_SESSION['user_id'], $deleteId);
                }
                $redirectMonth = isset($_POST['month']) ? (string)$_POST['month'] : $month;
                if (!preg_match('/^\d{4}-\d{2}$/', $redirectMonth)) {
                    $redirectMonth = $month;
                }
                header('Location: /finance?month=' . urlencode($redirectMonth));
                exit;
            }

            $desc = trim($_POST['description'] ?? '');
            $amountRaw = $_POST['amount'] ?? '';
            $type = $_POST['type'] ?? 'expense';
            $date = $_POST['date'] ?? date('Y-m-d');
            $vacationId = $_POST['vacation_id'] ?? null;
            $vacationId = is_numeric($vacationId) ? (int)$vacationId : null;

            $txCurrency = CurrencyService::normalizeCode($_POST['currency'] ?? $currencyCode);
            if (!CurrencyService::isValidCode($txCurrency)) {
                $txCurrency = $currencyCode;
            }

            $exchangeRate = null;
            if ($txCurrency !== $currencyCode) {
                $rateKey = 'fx:' . $txCurrency . ':' . $currencyCode;
                $rateValue = null;

                if (function_exists('cache_get_json')) {
                    $cached = cache_get_json($rateKey, 6 * 60 * 60);
                    if (is_array($cached) && isset($cached['rate']) && is_numeric($cached['rate'])) {
                        $rateValue = (float)$cached['rate'];
                    }
                }

                if ($rateValue === null) {
                    $rateValue = $this->fetchFxRate($txCurrency, $currencyCode);
                    if ($rateValue !== null && function_exists('cache_set_json')) {
                        cache_set_json($rateKey, ['rate' => $rateValue, 'ts' => time()]);
                    }
                }

                if ($rateValue === null && function_exists('cache_get_json')) {
                    $stale = cache_get_json($rateKey, 0);
                    if (is_array($stale) && isset($stale['rate']) && is_numeric($stale['rate'])) {
                        $rateValue = (float)$stale['rate'];
                    }
                }

                if ($rateValue !== null && $rateValue > 0) {
                    $exchangeRate = $rateValue;
                }
            } else {
                $exchangeRate = 1.0;
            }

            $allowedTypes = ['expense', 'income'];
            if ($desc === '' || $amountRaw === '' || !is_numeric($amountRaw) || !in_array($type, $allowedTypes, true)) {
                $transactions = Transaction::getByMonth($_SESSION['user_id'], $month);
                view('finance/index', [
                    'transactions' => $transactions,
                    'currencyCode' => $currencyCode,
                    'currencySymbol' => CurrencyService::symbolFor($currencyCode),
                    'baseCurrencyCode' => $currencyCode,
                    'month' => $month,
                    'currencyOptions' => $currencyOptions,
                    'vacations' => $vacations,
                    'totalsBase' => Transaction::totalsBaseForMonth($_SESSION['user_id'], $month),
                    'expenseByCurrency' => Transaction::summarizeByOriginalCurrencyForMonth($_SESSION['user_id'], $month, 'expense'),
                    'incomeByCurrency' => Transaction::summarizeByOriginalCurrencyForMonth($_SESSION['user_id'], $month, 'income'),
                    'error' => 'Please provide a description, valid amount, and type.'
                ]);
                return;
            }

            if ($txCurrency !== $currencyCode && $exchangeRate === null) {
                $transactions = Transaction::getByMonth($_SESSION['user_id'], $month);
                view('finance/index', [
                    'transactions' => $transactions,
                    'currencyCode' => $currencyCode,
                    'currencySymbol' => CurrencyService::symbolFor($currencyCode),
                    'baseCurrencyCode' => $currencyCode,
                    'month' => $month,
                    'currencyOptions' => $currencyOptions,
                    'vacations' => $vacations,
                    'totalsBase' => Transaction::totalsBaseForMonth($_SESSION['user_id'], $month),
                    'expenseByCurrency' => Transaction::summarizeByOriginalCurrencyForMonth($_SESSION['user_id'], $month, 'expense'),
                    'incomeByCurrency' => Transaction::summarizeByOriginalCurrencyForMonth($_SESSION['user_id'], $month, 'income'),
                    'error' => 'Could not fetch exchange rate. Please try again in a moment.'
                ]);
                return;
            }

            $originalAmount = (float)$amountRaw;
            $baseAmount = ($txCurrency === $currencyCode) ? $originalAmount : ($originalAmount * (float)$exchangeRate);

            // Only attach vacations to expenses
            if ($type !== 'expense') {
                $vacationId = null;
            }

            if ($vacationId !== null) {
                $validVacation = null;
                foreach ($vacations as $v) {
                    if ((int)($v['id'] ?? 0) === $vacationId) {
                        $validVacation = $v;
                        break;
                    }
                }
                if (!$validVacation || (string)($validVacation['status'] ?? '') === 'Completed') {
                    $vacationId = null;
                }
            }

            Transaction::create(
                $_SESSION['user_id'],
                $desc,
                (float)$baseAmount,
                $type,
                $date,
                (float)$originalAmount,
                $txCurrency,
                $currencyCode,
                (float)$exchangeRate,
                $vacationId
            );
            header('Location: /finance?month=' . urlencode(substr($date, 0, 7)));
            exit;
        }

        $transactions = Transaction::getByMonth($_SESSION['user_id'], $month);

        $vacationSummaries = [];
        foreach ($vacations as $v) {
            $vacationSummaries[] = [
                'id' => (int)$v['id'],
                'destination' => (string)($v['destination'] ?? ''),
                'status' => (string)($v['status'] ?? ''),
                'budget' => isset($v['budget']) ? (float)$v['budget'] : null,
                'actual' => Transaction::totalsBaseByVacation($_SESSION['user_id'], (int)$v['id'])
            ];
        }
        view('finance/index', [
            'transactions' => $transactions,
            'currencyCode' => $currencyCode,
            'currencySymbol' => CurrencyService::symbolFor($currencyCode),
            'baseCurrencyCode' => $currencyCode,
            'month' => $month,
            'currencyOptions' => $currencyOptions,
            'vacations' => $vacations,
            'vacationSummaries' => $vacationSummaries,
            'totalsBase' => Transaction::totalsBaseForMonth($_SESSION['user_id'], $month),
            'expenseByCurrency' => Transaction::summarizeByOriginalCurrencyForMonth($_SESSION['user_id'], $month, 'expense'),
            'incomeByCurrency' => Transaction::summarizeByOriginalCurrencyForMonth($_SESSION['user_id'], $month, 'income')
        ]);
    }

    public function assets() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $currencyCode = $this->getUserCurrencyCode();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $type = trim($_POST['asset_type'] ?? '');
            $valueRaw = $_POST['value'] ?? '';
            $notes = trim($_POST['notes'] ?? '');

            if ($name === '' || $type === '' || $valueRaw === '' || !is_numeric($valueRaw)) {
                $assets = FinanceAsset::getAll($_SESSION['user_id']);
                view('finance/assets', [
                    'assets' => $assets,
                    'currencyCode' => $currencyCode,
                    'currencySymbol' => CurrencyService::symbolFor($currencyCode),
                    'error' => 'Please provide a name, type, and valid value.'
                ]);
                return;
            }

            FinanceAsset::create($_SESSION['user_id'], $name, $type, (float)$valueRaw, $notes);
            header('Location: /finance/assets');
            exit;
        }

        $assets = FinanceAsset::getAll($_SESSION['user_id']);
        view('finance/assets', [
            'assets' => $assets,
            'currencyCode' => $currencyCode,
            'currencySymbol' => CurrencyService::symbolFor($currencyCode)
        ]);
    }

    public function bills() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $currencyCode = $this->getUserCurrencyCode();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $amountRaw = $_POST['amount'] ?? '';
            $dueDate = $_POST['due_date'] ?? '';
            $status = $_POST['status'] ?? 'unpaid';

            if ($name === '' || $amountRaw === '' || !is_numeric($amountRaw) || $dueDate === '') {
                $bills = FinanceBill::getAll($_SESSION['user_id']);
                view('finance/bills', [
                    'bills' => $bills,
                    'currencyCode' => $currencyCode,
                    'currencySymbol' => CurrencyService::symbolFor($currencyCode),
                    'error' => 'Please provide a name, amount, and due date.'
                ]);
                return;
            }

            FinanceBill::create($_SESSION['user_id'], $name, (float)$amountRaw, $dueDate, $status);
            header('Location: /finance/bills');
            exit;
        }

        $bills = FinanceBill::getAll($_SESSION['user_id']);
        view('finance/bills', [
            'bills' => $bills,
            'currencyCode' => $currencyCode,
            'currencySymbol' => CurrencyService::symbolFor($currencyCode)
        ]);
    }

    public function budgets() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $currencyCode = $this->getUserCurrencyCode();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $category = trim($_POST['category'] ?? '');
            $amountRaw = $_POST['amount'] ?? '';
            $month = $_POST['month'] ?? '';

            if ($category === '' || $amountRaw === '' || !is_numeric($amountRaw) || $month === '') {
                $budgets = FinanceBudget::getAll($_SESSION['user_id']);
                view('finance/budgets', [
                    'budgets' => $budgets,
                    'currencyCode' => $currencyCode,
                    'currencySymbol' => CurrencyService::symbolFor($currencyCode),
                    'error' => 'Please provide a category, amount, and month.'
                ]);
                return;
            }

            FinanceBudget::create($_SESSION['user_id'], $category, (float)$amountRaw, $month);
            header('Location: /finance/budgets');
            exit;
        }

        $budgets = FinanceBudget::getAll($_SESSION['user_id']);
        view('finance/budgets', [
            'budgets' => $budgets,
            'currencyCode' => $currencyCode,
            'currencySymbol' => CurrencyService::symbolFor($currencyCode)
        ]);
    }

    public function income() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $currencyCode = $this->getUserCurrencyCode();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $source = trim($_POST['source'] ?? '');
            $amountRaw = $_POST['amount'] ?? '';
            $date = $_POST['received_date'] ?? '';

            if ($source === '' || $amountRaw === '' || !is_numeric($amountRaw) || $date === '') {
                $income = FinanceIncome::getAll($_SESSION['user_id']);
                view('finance/income', [
                    'income' => $income,
                    'currencyCode' => $currencyCode,
                    'currencySymbol' => CurrencyService::symbolFor($currencyCode),
                    'error' => 'Please provide a source, amount, and date.'
                ]);
                return;
            }

            FinanceIncome::create($_SESSION['user_id'], $source, (float)$amountRaw, $date);
            header('Location: /finance/income');
            exit;
        }

        $income = FinanceIncome::getAll($_SESSION['user_id']);
        view('finance/income', [
            'income' => $income,
            'currencyCode' => $currencyCode,
            'currencySymbol' => CurrencyService::symbolFor($currencyCode)
        ]);
    }

    public function savings() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $currencyCode = $this->getUserCurrencyCode();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $goal = trim($_POST['goal'] ?? '');
            $targetRaw = $_POST['target_amount'] ?? '';
            $currentRaw = $_POST['current_amount'] ?? '';

            if ($goal === '' || $targetRaw === '' || !is_numeric($targetRaw)) {
                $savings = FinanceSaving::getAll($_SESSION['user_id']);
                view('finance/savings', [
                    'savings' => $savings,
                    'currencyCode' => $currencyCode,
                    'currencySymbol' => CurrencyService::symbolFor($currencyCode),
                    'error' => 'Please provide a goal and target amount.'
                ]);
                return;
            }

            $current = ($currentRaw !== '' && is_numeric($currentRaw)) ? (float)$currentRaw : 0;
            FinanceSaving::create($_SESSION['user_id'], $goal, (float)$targetRaw, $current);
            header('Location: /finance/savings');
            exit;
        }

        $savings = FinanceSaving::getAll($_SESSION['user_id']);
        view('finance/savings', [
            'savings' => $savings,
            'currencyCode' => $currencyCode,
            'currencySymbol' => CurrencyService::symbolFor($currencyCode)
        ]);
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
