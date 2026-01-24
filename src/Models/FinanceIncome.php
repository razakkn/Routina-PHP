<?php

namespace Routina\Models;

use Routina\Config\Database;

class FinanceIncome {
    public static function getAll($userId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM finance_income WHERE user_id = :uid ORDER BY received_date DESC");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public static function create($userId, $source, $amount, $date) {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO finance_income (user_id, source, amount, received_date) VALUES (:uid, :source, :amount, :date)");
        return $stmt->execute([
            'uid' => $userId,
            'source' => $source,
            'amount' => $amount,
            'date' => $date
        ]);
    }
}
