<?php

namespace Routina\Models;

use Routina\Config\Database;

class FinanceAsset {
    public static function getAll($userId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM finance_assets WHERE user_id = :uid ORDER BY id DESC");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public static function create($userId, $name, $type, $value, $notes) {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO finance_assets (user_id, name, asset_type, value, notes) VALUES (:uid, :name, :type, :value, :notes)");
        return $stmt->execute([
            'uid' => $userId,
            'name' => $name,
            'type' => $type,
            'value' => $value,
            'notes' => $notes
        ]);
    }
}
