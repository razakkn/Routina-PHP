<?php

namespace Routina\Models;

use Routina\Config\Database;

class FinanceDebt {
    public static function getAll(int $userId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM finance_debts WHERE user_id = :uid ORDER BY entry_date DESC, id DESC");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    public static function getByEmail(int $userId, string $email): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM finance_debts WHERE user_id = :uid AND LOWER(person_email) = LOWER(:email) ORDER BY entry_date DESC, id DESC");
        $stmt->execute(['uid' => $userId, 'email' => $email]);
        return $stmt->fetchAll() ?: [];
    }

    public static function findByIdForUser(int $userId, int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM finance_debts WHERE user_id = :uid AND id = :id LIMIT 1");
        $stmt->execute(['uid' => $userId, 'id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(int $userId, string $type, float $amount, string $date, string $email, string $description): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO finance_debts (user_id, debt_type, amount, entry_date, person_email, description, created_at) VALUES (:uid, :type, :amount, :date, :email, :description, :created)");
        $stmt->execute([
            'uid' => $userId,
            'type' => $type,
            'amount' => $amount,
            'date' => $date,
            'email' => $email,
            'description' => $description,
            'created' => date('Y-m-d H:i:s')
        ]);
    }

    public static function update(int $userId, int $id, string $type, float $amount, string $date, string $email, string $description): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE finance_debts SET debt_type = :type, amount = :amount, entry_date = :date, person_email = :email, description = :description WHERE user_id = :uid AND id = :id");
        $stmt->execute([
            'uid' => $userId,
            'id' => $id,
            'type' => $type,
            'amount' => $amount,
            'date' => $date,
            'email' => $email,
            'description' => $description
        ]);
    }

    public static function deleteByIdForUser(int $userId, int $id): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM finance_debts WHERE user_id = :uid AND id = :id");
        $stmt->execute(['uid' => $userId, 'id' => $id]);
    }

    public static function totals(int $userId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT debt_type, SUM(amount) as total_amount FROM finance_debts WHERE user_id = :uid GROUP BY debt_type");
        $stmt->execute(['uid' => $userId]);
        $rows = $stmt->fetchAll() ?: [];
        $totals = ['debt' => 0.0, 'credit' => 0.0];
        foreach ($rows as $row) {
            $type = strtolower((string)($row['debt_type'] ?? ''));
            if ($type === 'debt' || $type === 'credit') {
                $totals[$type] = (float)($row['total_amount'] ?? 0);
            }
        }
        return $totals;
    }

    public static function totalsByEmail(int $userId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT person_email, debt_type, SUM(amount) as total_amount FROM finance_debts WHERE user_id = :uid GROUP BY person_email, debt_type ORDER BY person_email ASC");
        $stmt->execute(['uid' => $userId]);
        $rows = $stmt->fetchAll() ?: [];
        $result = [];
        foreach ($rows as $row) {
            $email = (string)($row['person_email'] ?? '');
            $type = strtolower((string)($row['debt_type'] ?? ''));
            if ($email === '' || ($type !== 'debt' && $type !== 'credit')) {
                continue;
            }
            if (!isset($result[$email])) {
                $result[$email] = ['debt' => 0.0, 'credit' => 0.0];
            }
            $result[$email][$type] = (float)($row['total_amount'] ?? 0);
        }
        return $result;
    }
}
