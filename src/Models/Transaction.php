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

    public static function create($userId, $description, $amount, $type, $date) {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO transactions (user_id, description, amount, type, date) VALUES (:uid, :desc, :amt, :type, :date)");
        return $stmt->execute([
            'uid' => $userId, 
            'desc' => $description, 
            'amt' => $amount, 
            'type' => $type, 
            'date' => $date
        ]);
    }
}
