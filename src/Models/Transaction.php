<?php

namespace Routina\Models;

use Routina\Config\Database;

class Transaction {
    public static function getAll($userId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM transactions WHERE user_id = :uid ORDER BY date DESC");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public static function getByMonth($userId, string $month) {
        $db = Database::getConnection();
        $prefix = $month . '%';
        $stmt = $db->prepare("SELECT * FROM transactions WHERE user_id = :uid AND date LIKE :prefix ORDER BY date DESC, id DESC");
        $stmt->execute(['uid' => $userId, 'prefix' => $prefix]);
        return $stmt->fetchAll();
    }

    public static function summarizeByOriginalCurrencyForMonth($userId, string $month, string $type): array {
        $db = Database::getConnection();
        $prefix = $month . '%';

        // If original_currency is null (legacy rows), treat it as base_currency.
        $sql = "SELECT COALESCE(NULLIF(original_currency, ''), NULLIF(base_currency, ''), 'USD') AS currency, SUM(COALESCE(original_amount, amount)) AS total
                FROM transactions
                WHERE user_id = :uid AND type = :type AND date LIKE :prefix
                GROUP BY COALESCE(NULLIF(original_currency, ''), NULLIF(base_currency, ''), 'USD')
                ORDER BY currency ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute(['uid' => $userId, 'type' => $type, 'prefix' => $prefix]);
        $rows = $stmt->fetchAll();

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'currency' => (string)($r['currency'] ?? 'USD'),
                'total' => (float)($r['total'] ?? 0)
            ];
        }
        return $out;
    }

    public static function totalsBaseForMonth($userId, string $month): array {
        $db = Database::getConnection();
        $prefix = $month . '%';
        $sql = "SELECT type, SUM(amount) AS total
                FROM transactions
                WHERE user_id = :uid AND date LIKE :prefix
                GROUP BY type";
        $stmt = $db->prepare($sql);
        $stmt->execute(['uid' => $userId, 'prefix' => $prefix]);
        $rows = $stmt->fetchAll();

        $totals = ['income' => 0.0, 'expense' => 0.0];
        foreach ($rows as $r) {
            $t = (string)($r['type'] ?? '');
            if (isset($totals[$t])) {
                $totals[$t] = (float)($r['total'] ?? 0);
            }
        }
        return $totals;
    }

    public static function create($userId, $description, $amountBase, $type, $date, $originalAmount = null, $originalCurrency = null, $baseCurrency = null, $exchangeRate = null, $vacationId = null) {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO transactions (user_id, description, amount, original_amount, original_currency, base_currency, exchange_rate, vacation_id, type, date)
            VALUES (:uid, :desc, :amt, :oamt, :occy, :bccy, :rate, :vacation_id, :type, :date)");
        return $stmt->execute([
            'uid' => $userId, 
            'desc' => $description, 
            'amt' => $amountBase,
            'oamt' => $originalAmount,
            'occy' => $originalCurrency,
            'bccy' => $baseCurrency,
            'rate' => $exchangeRate,
            'vacation_id' => $vacationId,
            'type' => $type, 
            'date' => $date
        ]);
    }

    public static function totalsBaseByVacation($userId, $vacationId): float {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT SUM(amount) AS total FROM transactions WHERE user_id = :uid AND vacation_id = :vid AND type = 'expense'");
        $stmt->execute(['uid' => $userId, 'vid' => $vacationId]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (float)$val : 0.0;
    }

    public static function deleteByIdForUser($userId, $id): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM transactions WHERE id = :id AND user_id = :uid");
        return $stmt->execute([
            'id' => (int)$id,
            'uid' => (int)$userId
        ]);
    }
}
