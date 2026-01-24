<?php

namespace Routina\Models;

use Routina\Config\Database;

class FinanceBill {
    public static function getAll($userId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM finance_bills WHERE user_id = :uid ORDER BY due_date ASC");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public static function create($userId, $name, $amount, $dueDate, $status) {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO finance_bills (user_id, name, amount, due_date, status) VALUES (:uid, :name, :amount, :due, :status)");
        return $stmt->execute([
            'uid' => $userId,
            'name' => $name,
            'amount' => $amount,
            'due' => $dueDate,
            'status' => $status
        ]);
    }
}
