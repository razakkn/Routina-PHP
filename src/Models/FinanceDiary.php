<?php

namespace Routina\Models;

use Routina\Config\Database;

class FinanceDiary {
    public static function getAll($userId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM finance_diary WHERE user_id = :uid ORDER BY entry_date DESC");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public static function create($userId, $date, $notes) {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO finance_diary (user_id, entry_date, notes) VALUES (:uid, :date, :notes)");
        return $stmt->execute([
            'uid' => $userId,
            'date' => $date,
            'notes' => $notes
        ]);
    }
}
