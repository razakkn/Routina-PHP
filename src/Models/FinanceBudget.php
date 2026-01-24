<?php

namespace Routina\Models;

use Routina\Config\Database;

class FinanceBudget {
    public static function getAll($userId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM finance_budgets WHERE user_id = :uid ORDER BY month DESC");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public static function create($userId, $category, $amount, $month) {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO finance_budgets (user_id, category, amount, month) VALUES (:uid, :category, :amount, :month)");
        return $stmt->execute([
            'uid' => $userId,
            'category' => $category,
            'amount' => $amount,
            'month' => $month
        ]);
    }
}
