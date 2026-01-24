<?php

namespace Routina\Models;

use Routina\Config\Database;

/**
 * Transaction model for managing financial transactions.
 * 
 * Handles CRUD operations for income/expense transactions with
 * multi-currency support and vacation expense linking.
 */
class Transaction
{
    /**
     * Get all transactions for a user.
     *
     * @param int $userId User ID
     * @return array<int, array<string, mixed>> Array of transaction records
     */
    public static function getAll(int $userId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM transactions WHERE user_id = :uid ORDER BY date DESC");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Get transactions for a specific month.
     *
     * @param int $userId User ID
     * @param string $month Month in Y-m format (e.g., "2026-01")
     * @return array<int, array<string, mixed>> Array of transaction records
     */
    public static function getByMonth(int $userId, string $month): array
    {
        $db = Database::getConnection();
        $prefix = $month . '%';
        $stmt = $db->prepare("SELECT * FROM transactions WHERE user_id = :uid AND date LIKE :prefix ORDER BY date DESC, id DESC");
        $stmt->execute(['uid' => $userId, 'prefix' => $prefix]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Summarize transactions by original currency for a month.
     *
     * @param int $userId User ID
     * @param string $month Month in Y-m format
     * @param string $type Transaction type ('income' or 'expense')
     * @return array<int, array{currency: string, total: float}> Totals per currency
     */
    public static function summarizeByOriginalCurrencyForMonth(int $userId, string $month, string $type): array
    {
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

    /**
     * Get totals (income and expense) in base currency for a month.
     *
     * @param int $userId User ID
     * @param string $month Month in Y-m format
     * @return array{income: float, expense: float} Totals by type
     */
    public static function totalsBaseForMonth(int $userId, string $month): array
    {
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

    /**
     * Create a new transaction.
     *
     * @param int $userId User ID
     * @param string $description Transaction description
     * @param float $amountBase Amount in base currency
     * @param string $type Transaction type ('income' or 'expense')
     * @param string $date Transaction date (Y-m-d)
     * @param float|null $originalAmount Original amount before conversion
     * @param string|null $originalCurrency Original currency code
     * @param string|null $baseCurrency Base currency code
     * @param float|null $exchangeRate Exchange rate used
     * @param int|null $vacationId Associated vacation ID
     * @return bool True on success
     */
    public static function create(
        int $userId,
        string $description,
        float $amountBase,
        string $type,
        string $date,
        ?float $originalAmount = null,
        ?string $originalCurrency = null,
        ?string $baseCurrency = null,
        ?float $exchangeRate = null,
        ?int $vacationId = null
    ): bool {
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

    /**
     * Get total expenses for a vacation.
     *
     * @param int $userId User ID
     * @param int $vacationId Vacation ID
     * @return float Total expense amount in base currency
     */
    public static function totalsBaseByVacation(int $userId, int $vacationId): float
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT SUM(amount) AS total FROM transactions WHERE user_id = :uid AND vacation_id = :vid AND type = 'expense'");
        $stmt->execute(['uid' => $userId, 'vid' => $vacationId]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (float)$val : 0.0;
    }

    /**
     * Delete a transaction by ID for a user.
     *
     * @param int $userId User ID
     * @param int $id Transaction ID
     * @return bool True on success
     */
    public static function deleteByIdForUser(int $userId, int $id): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM transactions WHERE id = :id AND user_id = :uid");
        return $stmt->execute([
            'id' => $id,
            'uid' => $userId
        ]);
    }
}
