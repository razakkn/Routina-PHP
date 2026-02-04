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
use Routina\Models\FinanceDebt;
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
                    'debtTotals' => FinanceDebt::totals($_SESSION['user_id']),
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
                    'debtTotals' => FinanceDebt::totals($_SESSION['user_id']),
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
            'incomeByCurrency' => Transaction::summarizeByOriginalCurrencyForMonth($_SESSION['user_id'], $month, 'income'),
            'debtTotals' => FinanceDebt::totals($_SESSION['user_id'])
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
        $totalsAll = Transaction::totalsBaseAll($_SESSION['user_id']);
        $availableBalance = (float)($totalsAll['income'] ?? 0) - (float)($totalsAll['expense'] ?? 0);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $goal = trim($_POST['goal'] ?? '');
            $targetRaw = $_POST['target_amount'] ?? '';

            if ($goal === '' || $targetRaw === '' || !is_numeric($targetRaw)) {
                $savings = FinanceSaving::getAll($_SESSION['user_id']);
                view('finance/savings', [
                    'savings' => $savings,
                    'currencyCode' => $currencyCode,
                    'currencySymbol' => CurrencyService::symbolFor($currencyCode),
                    'availableBalance' => $availableBalance,
                    'error' => 'Please provide a goal and target amount.'
                ]);
                return;
            }

            FinanceSaving::create($_SESSION['user_id'], $goal, (float)$targetRaw, $availableBalance);
            header('Location: /finance/savings');
            exit;
        }

        $savings = FinanceSaving::getAll($_SESSION['user_id']);
        view('finance/savings', [
            'savings' => $savings,
            'currencyCode' => $currencyCode,
            'currencySymbol' => CurrencyService::symbolFor($currencyCode),
            'availableBalance' => $availableBalance
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
                $entries = FinanceDiary::getSummaries($_SESSION['user_id']);
                view('finance/diary', ['entries' => $entries, 'error' => 'Please write a note before saving.']);
                return;
            }

            FinanceDiary::create($_SESSION['user_id'], $date, $notes);
            header('Location: /finance/diary');
            exit;
        }

        $entries = FinanceDiary::getSummaries($_SESSION['user_id']);
        view('finance/diary', ['entries' => $entries]);
    }

    public function diaryDetail() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo 'Unauthorized';
            exit;
        }

        $id = $_GET['id'] ?? '';
        if (!is_numeric($id)) {
            http_response_code(400);
            echo 'Invalid request';
            exit;
        }

        $entry = FinanceDiary::findByIdForUser($_SESSION['user_id'], (int)$id);
        if (!$entry) {
            http_response_code(404);
            echo 'Not found';
            exit;
        }

        header('Content-Type: text/html; charset=utf-8');
        view('finance/partials/diary_detail', ['entry' => $entry]);
        exit;
    }

    public function debts() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $userId = (int)$_SESSION['user_id'];
        $filterEmail = isset($_GET['person_email']) ? trim((string)$_GET['person_email']) : '';
        $editId = isset($_GET['edit']) && is_numeric($_GET['edit']) ? (int)$_GET['edit'] : 0;
        $editEntry = null;
        $entries = [];
        $error = '';

        $export = isset($_GET['export']) ? (string)$_GET['export'] : '';
        if ($export === 'csv' || $export === 'excel' || $export === 'print') {
            $exportEmail = ($filterEmail !== '' && filter_var($filterEmail, FILTER_VALIDATE_EMAIL)) ? $filterEmail : '';
            if ($exportEmail !== '') {
                $entries = FinanceDebt::getByEmail($userId, $exportEmail);
            } else {
                $entries = FinanceDebt::getAll($userId);
            }

            $calcTotals = function (array $rows): array {
                $totals = ['debt' => 0.0, 'credit' => 0.0];
                foreach ($rows as $row) {
                    $type = strtolower((string)($row['debt_type'] ?? ''));
                    if ($type === 'debt' || $type === 'credit') {
                        $totals[$type] += (float)($row['amount'] ?? 0);
                    }
                }
                return $totals;
            };
            $exportTotals = $calcTotals($entries);
            $exportOutstanding = (float)$exportTotals['credit'] - (float)$exportTotals['debt'];

            if ($export === 'csv') {
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="debt_statement.csv"');
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Statement', $exportEmail !== '' ? $exportEmail : 'All']);
                fputcsv($out, ['Total debt (I owe)', number_format((float)$exportTotals['debt'], 2, '.', '')]);
                fputcsv($out, ['Total credit (They owe)', number_format((float)$exportTotals['credit'], 2, '.', '')]);
                fputcsv($out, ['Outstanding', number_format((float)$exportOutstanding, 2, '.', '')]);
                fputcsv($out, []);
                fputcsv($out, ['Date', 'Person Email', 'Type', 'Amount', 'Description']);
                foreach ($entries as $row) {
                    fputcsv($out, [
                        (string)($row['entry_date'] ?? ''),
                        (string)($row['person_email'] ?? ''),
                        (string)($row['debt_type'] ?? ''),
                        number_format((float)($row['amount'] ?? 0), 2, '.', ''),
                        (string)($row['description'] ?? '')
                    ]);
                }
                fclose($out);
                exit;
            }

            if ($export === 'excel') {
                header('Content-Type: application/vnd.ms-excel; charset=utf-8');
                header('Content-Disposition: attachment; filename="debt_statement.xls"');
                echo "<table border=\"1\">";
                echo "<tr><th colspan=\"4\">Statement</th></tr>";
                echo "<tr><td colspan=\"4\">" . htmlspecialchars($exportEmail !== '' ? $exportEmail : 'All', ENT_QUOTES) . "</td></tr>";
                echo "<tr><td>Total debt (I owe)</td><td colspan=\"3\">" . number_format((float)$exportTotals['debt'], 2, '.', '') . "</td></tr>";
                echo "<tr><td>Total credit (They owe)</td><td colspan=\"3\">" . number_format((float)$exportTotals['credit'], 2, '.', '') . "</td></tr>";
                echo "<tr><td>Outstanding</td><td colspan=\"3\">" . number_format((float)$exportOutstanding, 2, '.', '') . "</td></tr>";
                echo "<tr><th>Date</th><th>Person Email</th><th>Type</th><th>Amount</th><th>Description</th></tr>";
                foreach ($entries as $row) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars((string)($row['entry_date'] ?? ''), ENT_QUOTES) . "</td>";
                    echo "<td>" . htmlspecialchars((string)($row['person_email'] ?? ''), ENT_QUOTES) . "</td>";
                    echo "<td>" . htmlspecialchars((string)($row['debt_type'] ?? ''), ENT_QUOTES) . "</td>";
                    echo "<td>" . number_format((float)($row['amount'] ?? 0), 2, '.', '') . "</td>";
                    echo "<td>" . htmlspecialchars((string)($row['description'] ?? ''), ENT_QUOTES) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                exit;
            }

            if ($export === 'print') {
                header('Content-Type: text/html; charset=utf-8');
                $title = $exportEmail !== '' ? ('Debt statement for ' . $exportEmail) : 'Debt statement';
                echo "<!doctype html><html><head><meta charset=\"utf-8\"><title>" . htmlspecialchars($title, ENT_QUOTES) . "</title>";
                echo "<style>body{font-family:Arial, sans-serif;margin:24px;color:#111;}h1{font-size:20px;margin-bottom:8px;}table{width:100%;border-collapse:collapse;margin-top:12px;}th,td{border:1px solid #ccc;padding:8px;text-align:left;}th{text-align:left;background:#f3f4f6;} .totals{margin-top:12px;display:flex;gap:24px;}</style>";
                echo "</head><body>";
                echo "<h1>" . htmlspecialchars($title, ENT_QUOTES) . "</h1>";
                echo "<div class=\"totals\">";
                echo "<div>Total debt (I owe): <strong>" . number_format((float)$exportTotals['debt'], 2) . "</strong></div>";
                echo "<div>Total credit (They owe): <strong>" . number_format((float)$exportTotals['credit'], 2) . "</strong></div>";
                echo "<div>Outstanding: <strong>" . number_format($exportOutstanding, 2) . "</strong></div>";
                echo "</div>";
                echo "<div style=\"margin-top:8px;color:#666;font-size:12px;\">Tip: use your browser’s Print dialog to save as PDF.</div>";
                echo "<table><thead><tr><th>Date</th><th>Person Email</th><th>Type</th><th>Amount</th><th>Description</th></tr></thead><tbody>";
                foreach ($entries as $row) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars((string)($row['entry_date'] ?? ''), ENT_QUOTES) . "</td>";
                    echo "<td>" . htmlspecialchars((string)($row['person_email'] ?? ''), ENT_QUOTES) . "</td>";
                    echo "<td>" . htmlspecialchars((string)($row['debt_type'] ?? ''), ENT_QUOTES) . "</td>";
                    echo "<td>" . number_format((float)($row['amount'] ?? 0), 2, '.', '') . "</td>";
                    echo "<td>" . htmlspecialchars((string)($row['description'] ?? ''), ENT_QUOTES) . "</td>";
                    echo "</tr>";
                }
                echo "</tbody></table>";
                echo "</body></html>";
                exit;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = (string)($_POST['action'] ?? 'create');
            if ($action === 'delete') {
                $deleteId = isset($_POST['id']) && is_numeric($_POST['id']) ? (int)$_POST['id'] : 0;
                if ($deleteId > 0) {
                    FinanceDebt::deleteByIdForUser($userId, $deleteId);
                }
                header('Location: /finance/debts' . ($filterEmail !== '' ? '?person_email=' . urlencode($filterEmail) : ''));
                exit;
            }

            $type = strtolower(trim((string)($_POST['debt_type'] ?? '')));
            $amountRaw = $_POST['amount'] ?? '';
            $date = trim((string)($_POST['date'] ?? date('Y-m-d')));
            $email = strtolower(trim((string)($_POST['person_email'] ?? '')));
            $description = trim((string)($_POST['description'] ?? ''));
            $allowed = ['debt', 'credit'];

            if (!in_array($type, $allowed, true)) {
                $error = 'Please choose Debt or Credit.';
            } elseif ($amountRaw === '' || !is_numeric($amountRaw)) {
                $error = 'Please provide a valid amount.';
            } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $error = 'Please provide a valid date.';
            } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please provide a valid email address.';
            } else {
                if ($action === 'update') {
                    $updateId = isset($_POST['id']) && is_numeric($_POST['id']) ? (int)$_POST['id'] : 0;
                    if ($updateId > 0) {
                        FinanceDebt::update($userId, $updateId, $type, (float)$amountRaw, $date, $email, $description);
                    }
                } else {
                    FinanceDebt::create($userId, $type, (float)$amountRaw, $date, $email, $description);
                }
                header('Location: /finance/debts' . ($filterEmail !== '' ? '?person_email=' . urlencode($filterEmail) : ''));
                exit;
            }
        }

        if ($filterEmail !== '' && filter_var($filterEmail, FILTER_VALIDATE_EMAIL)) {
            $entries = FinanceDebt::getByEmail($userId, $filterEmail);
        } else {
            $entries = FinanceDebt::getAll($userId);
        }

        if ($editId > 0) {
            $editEntry = FinanceDebt::findByIdForUser($userId, $editId);
        }

        $totals = FinanceDebt::totals($userId);
        $byPerson = FinanceDebt::totalsByEmail($userId);

        view('finance/debts', [
            'entries' => $entries,
            'totals' => $totals,
            'byPerson' => $byPerson,
            'filterEmail' => $filterEmail,
            'editEntry' => $editEntry,
            'error' => $error
        ]);
    }
}
