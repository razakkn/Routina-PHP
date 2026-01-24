<?php

namespace Routina\Models;

use Routina\Config\Database;

class FinanceReflection {
    public static function getAll($userId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM finance_reflections WHERE user_id = :uid ORDER BY month DESC");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public static function create($userId, $month, $summary) {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO finance_reflections (user_id, month, summary) VALUES (:uid, :month, :summary)");
        return $stmt->execute([
            'uid' => $userId,
            'month' => $month,
            'summary' => $summary
        ]);
    }
}
