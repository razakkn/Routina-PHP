<?php

namespace Routina\Models;

use Routina\Config\Database;

class VacationNote {
    public static function getAll($userId, $vacationId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM vacation_notes WHERE user_id = :uid AND vacation_id = :vid ORDER BY created_at DESC");
        $stmt->execute(['uid' => $userId, 'vid' => $vacationId]);
        return $stmt->fetchAll();
    }

    public static function create($userId, $vacationId, $title, $body) {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO vacation_notes (user_id, vacation_id, title, body, created_at) VALUES (:uid, :vid, :title, :body, :created)");
        return $stmt->execute([
            'uid' => $userId,
            'vid' => $vacationId,
            'title' => $title,
            'body' => $body,
            'created' => date('Y-m-d H:i:s')
        ]);
    }

    public static function deleteAll($userId, $vacationId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM vacation_notes WHERE user_id = :uid AND vacation_id = :vid");
        return $stmt->execute(['uid' => $userId, 'vid' => $vacationId]);
    }
}
