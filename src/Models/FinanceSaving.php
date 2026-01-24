<?php

namespace Routina\Models;

use Routina\Config\Database;

class FinanceSaving {
    public static function getAll($userId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM finance_savings WHERE user_id = :uid ORDER BY id DESC");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public static function create($userId, $goal, $target, $current) {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO finance_savings (user_id, goal, target_amount, current_amount) VALUES (:uid, :goal, :target, :current)");
        return $stmt->execute([
            'uid' => $userId,
            'goal' => $goal,
            'target' => $target,
            'current' => $current
        ]);
    }
}
