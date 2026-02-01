<?php

namespace Routina\Models;

use Routina\Config\Database;

class FinanceDiary {
    public static function getSummaries($userId, $limit = 200) {
        $db = Database::getConnection();
        $driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $limit = (int)$limit;
        if ($limit <= 0) {
            $limit = 200;
        }
        $limit = min(500, $limit);

        if ($driver === 'pgsql') {
            $sql = "SELECT id, entry_date, SUBSTRING(notes FROM 1 FOR 140) AS preview
                    FROM finance_diary
                    WHERE user_id = :uid
                    ORDER BY entry_date DESC
                    LIMIT {$limit}";
        } elseif ($driver === 'mysql') {
            $sql = "SELECT id, entry_date, SUBSTRING(notes, 1, 140) AS preview
                    FROM finance_diary
                    WHERE user_id = :uid
                    ORDER BY entry_date DESC
                    LIMIT {$limit}";
        } else {
            $sql = "SELECT id, entry_date, SUBSTR(notes, 1, 140) AS preview
                    FROM finance_diary
                    WHERE user_id = :uid
                    ORDER BY entry_date DESC
                    LIMIT {$limit}";
        }

        $stmt = $db->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    public static function findByIdForUser($userId, $id) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM finance_diary WHERE user_id = :uid AND id = :id");
        $stmt->execute(['uid' => $userId, 'id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

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
