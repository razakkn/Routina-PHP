<?php

namespace Routina\Controllers;

use Routina\Config\Database;
use Routina\Models\Family;
use Routina\Services\AuthService;

class AdminController {
    public function diagnostics() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $snapshot = $this->buildDiagnosticsSnapshot();
        view('admin/diagnostics', ['snapshot' => $snapshot]);
    }

    public function metrics() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $snapshot = $this->buildMetricsSnapshot();
        view('admin/metrics', ['snapshot' => $snapshot]);
    }

    public function metricsJson() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $snapshot = $this->buildMetricsSnapshot();
        header('Content-Type: application/json');
        echo json_encode($snapshot);
        exit;
    }

    public function metricsCsv() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $snapshot = $this->buildMetricsSnapshot();
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="metrics.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['metric', 'value']);
        fputcsv($out, ['db_can_connect', $snapshot['db']['can_connect'] ? 'true' : 'false']);
        fputcsv($out, ['db_file_size_bytes', $snapshot['db']['file_size_bytes'] ?? 'unknown']);
        fputcsv($out, ['php_version', $snapshot['process']['php_version']]);
        fputcsv($out, ['memory_usage', $snapshot['process']['memory_usage']]);
        fputcsv($out, ['peak_memory_usage', $snapshot['process']['peak_memory_usage']]);
        fputcsv($out, ['server_time', $snapshot['process']['server_time']]);
        fclose($out);
        exit;
    }

    public function autofillDiagnostics() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $email = strtolower(trim((string)($_GET['email'] ?? '')));
        $phone = trim((string)($_GET['phone'] ?? ''));

        $familyName = trim((string)($_GET['name'] ?? ''));
        $familyBirthdate = trim((string)($_GET['birthdate'] ?? ''));
        $familyGender = trim((string)($_GET['gender'] ?? ''));
        $familyRelation = trim((string)($_GET['relation'] ?? ''));

        $targetUser = null;
        $targetUserRow = null;
        $familyMatches = [];
        $dryRunUpdates = [];
        $notes = [];

        if ($email !== '' || $phone !== '') {
            // Find target user
            if ($email !== '') {
                $targetUser = AuthService::findByEmail($email);
            }
            if (!$targetUser && $phone !== '') {
                $targetUser = AuthService::findByPhone($phone);
            }

            // Find family entries that match this identity (used for new-user autofill)
            if ($email !== '') {
                $familyMatches = Family::findAllByEmail($email);
            }
            if (empty($familyMatches) && $phone !== '') {
                $familyMatches = Family::findAllByPhone($phone);
            }

            if (!$targetUser) {
                $notes[] = 'No user matched by email/phone. Auto-populate cannot update a profile unless a user account exists.';
            } else {
                $db = Database::getConnection();
                $stmt = $db->prepare('SELECT * FROM users WHERE id = :id');
                $stmt->execute(['id' => (int)$targetUser['id']]);
                $targetUserRow = $stmt->fetch() ?: null;
            }

            // Dry-run: simulate existing-user autofill for the provided family fields.
            if ($targetUserRow && ($familyName !== '' || $familyBirthdate !== '' || $familyGender !== '' || $familyRelation !== '' || $phone !== '')) {
                $cur = $targetUserRow;

                $has = function (string $k) use ($cur) {
                    return is_array($cur) && array_key_exists($k, $cur);
                };

                if ($has('display_name') && empty($cur['display_name']) && $familyName !== '') {
                    $dryRunUpdates['display_name'] = $familyName;
                }
                if ($has('dob') && empty($cur['dob']) && $familyBirthdate !== '') {
                    $dryRunUpdates['dob'] = $familyBirthdate;
                }
                if ($has('gender') && empty($cur['gender']) && $familyGender !== '') {
                    $dryRunUpdates['gender'] = $familyGender;
                }
                if ($has('phone') && empty($cur['phone']) && $phone !== '') {
                    $dryRunUpdates['phone'] = $phone;
                }

                $curRel = $has('relationship_status') ? (string)($cur['relationship_status'] ?? '') : '';
                $isDefaultSingle = ($curRel === 'single');
                $rel = strtolower(trim($familyRelation));
                if ($has('relationship_status') && (empty($curRel) || $isDefaultSingle)) {
                    if (in_array($rel, ['spouse', 'wife', 'husband'], true)) {
                        $dryRunUpdates['relationship_status'] = 'married';
                    } elseif (in_array($rel, ['girlfriend', 'boyfriend'], true)) {
                        $dryRunUpdates['relationship_status'] = 'in_relationship';
                    }
                }
            }

            if (empty($familyMatches)) {
                $notes[] = 'No matching family_members records were found by email/phone. New-user autofill will be skipped.';
            } else {
                $notes[] = 'Found ' . count($familyMatches) . ' matching family_members record(s). New-user autofill uses the most recent one.';
            }

            if ($targetUserRow && empty($dryRunUpdates)) {
                $notes[] = 'Dry-run shows no updates would be applied (either fields are already filled, relation doesn\'t map to relationship_status, or family data fields were not provided).';
            }
        }

        // Always show a sample of family members with emails for debugging
        $allFamilyWithEmail = [];
        try {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT fm.id, fm.user_id, fm.name, fm.email, fm.phone, fm.relation, fm.no_email, u.display_name as owner_name 
                                FROM family_members fm 
                                JOIN users u ON fm.user_id = u.id 
                                WHERE fm.email IS NOT NULL AND fm.email != '' 
                                ORDER BY fm.id DESC LIMIT 20");
            $allFamilyWithEmail = $stmt->fetchAll();
        } catch (\Throwable $e) {
            $notes[] = 'Error fetching family_members sample: ' . $e->getMessage();
        }

        view('admin/autofill', [
            'email' => $email,
            'phone' => $phone,
            'family_name' => $familyName,
            'family_birthdate' => $familyBirthdate,
            'family_gender' => $familyGender,
            'family_relation' => $familyRelation,
            'target_user' => $targetUser,
            'target_user_row' => $targetUserRow,
            'family_matches' => $familyMatches,
            'dry_run_updates' => $dryRunUpdates,
            'notes' => $notes,
            'all_family_with_email' => $allFamilyWithEmail,
        ]);
    }

    private function buildMetricsSnapshot() {
        $dbCanConnect = false;
        try {
            Database::getConnection();
            $dbCanConnect = true;
        } catch (\Exception $e) {
            $dbCanConnect = false;
        }

        $dbPath = __DIR__ . '/../../database.sqlite';
        $dbFileSize = file_exists($dbPath) ? filesize($dbPath) : null;

        // Request latency history (recorded centrally in the router)
        $recent = $_SESSION['metrics_recent'] ?? [];
        if (!is_array($recent)) {
            $recent = [];
        }
        if (count($recent) > 20) {
            $recent = array_slice($recent, -20);
        }

        return [
            'health' => [
                'status' => $dbCanConnect ? 'Healthy' : 'Unhealthy',
                'checks' => [
                    ['name' => 'database', 'status' => $dbCanConnect ? 'Healthy' : 'Unhealthy']
                ]
            ],
            'recent_request_ms' => $recent,
            'db' => [
                'can_connect' => $dbCanConnect,
                'file_size_bytes' => $dbFileSize
            ],
            'process' => [
                'php_version' => PHP_VERSION,
                'memory_usage' => memory_get_usage(true),
                'peak_memory_usage' => memory_get_peak_usage(true),
                'server_time' => date('c')
            ]
        ];
    }

    private function buildDiagnosticsSnapshot(): array
    {
        $db = null;
        $dbCanConnect = false;
        $driver = null;
        $dbError = null;

        try {
            $db = Database::getConnection();
            $dbCanConnect = true;
            $driver = (string)$db->getAttribute(\PDO::ATTR_DRIVER_NAME);
        } catch (\Throwable $e) {
            $dbCanConnect = false;
            $dbError = $e->getMessage();
        }

        $tables = [
            // Core
            'users',
            'password_resets',
            'user_alternative_emails',
            'user_social_accounts',

            // Modules
            'journal_entries',
            'calendar_events',
            'vacations',
            'vacation_notes',
            'vacation_checklist_items',
            'transactions',
            'finance_assets',
            'finance_bills',
            'finance_budgets',
            'finance_income',
            'finance_incomes',
            'finance_diary',
            'finance_debts',
            'finance_savings',
            'finance_reflections',
            'vehicles',
            'vehicle_vendors',
            'vehicle_parts',
            'vehicle_maintenance',
            'vehicle_documents',
            'vehicle_events',
            'vehicle_plans',
            'home_tasks',
            'health_entries',
            'health_records',
            'family_members',
            'buzz_requests',
            'user_memories',
        ];

        $tableStatus = [];
        if ($dbCanConnect && $db) {
            foreach ($tables as $t) {
                $tableStatus[$t] = [
                    'exists' => $this->tableExists($db, $t),
                ];
            }
        }

        $errorLogPath = __DIR__ . '/../../storage/error.log';
        $errorLogTail = null;
        if (is_file($errorLogPath)) {
            $errorLogTail = $this->tailFile($errorLogPath, 80);
        }

        $configFile = __DIR__ . '/../../config/config.php';
        $hasConfig = is_file($configFile);

        return [
            'db' => [
                'can_connect' => $dbCanConnect,
                'driver' => $driver,
                'error' => $dbError,
            ],
            'config' => [
                'config_php_present' => $hasConfig,
            ],
            'tables' => $tableStatus,
            'process' => [
                'php_version' => PHP_VERSION,
                'server_time' => date('c'),
                'memory_usage' => memory_get_usage(true),
                'peak_memory_usage' => memory_get_peak_usage(true),
            ],
            'logs' => [
                'error_log_path' => $errorLogPath,
                'error_log_tail' => $errorLogTail,
            ],
        ];
    }

    private function tableExists(\PDO $db, string $table): bool
    {
        $driver = '';
        try {
            $driver = (string)$db->getAttribute(\PDO::ATTR_DRIVER_NAME);
        } catch (\Throwable $e) {
            $driver = '';
        }

        try {
            if ($driver === 'pgsql') {
                $stmt = $db->prepare("SELECT to_regclass(:t) AS reg");
                $stmt->execute(['t' => $table]);
                $row = $stmt->fetch();
                return !empty($row['reg']);
            }

            if ($driver === 'mysql') {
                $stmt = $db->prepare('SHOW TABLES LIKE :t');
                $stmt->execute(['t' => $table]);
                return (bool)$stmt->fetch();
            }

            // sqlite
            $stmt = $db->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name = :t");
            $stmt->execute(['t' => $table]);
            return (bool)$stmt->fetch();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function tailFile(string $path, int $maxLines = 80): string
    {
        $maxLines = max(10, min(400, $maxLines));
        $lines = @file($path, FILE_IGNORE_NEW_LINES);
        if (!is_array($lines)) {
            return '';
        }
        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, -$maxLines);
        }
        return implode("\n", $lines);
    }
}
