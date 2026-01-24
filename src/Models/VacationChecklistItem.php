<?php

namespace Routina\Models;

use Routina\Config\Database;

class VacationChecklistItem {
    public static function getAll($userId, $vacationId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM vacation_checklist_items WHERE user_id = :uid AND vacation_id = :vid ORDER BY sort_order ASC, id ASC");
        $stmt->execute(['uid' => $userId, 'vid' => $vacationId]);
        return $stmt->fetchAll();
    }

    public static function create($userId, $vacationId, $text) {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO vacation_checklist_items (user_id, vacation_id, text, is_done, sort_order, created_at) VALUES (:uid, :vid, :text, 0, 0, :created)");
        return $stmt->execute([
            'uid' => $userId,
            'vid' => $vacationId,
            'text' => $text,
            'created' => date('Y-m-d H:i:s')
        ]);
    }

    public static function toggle($userId, $id) {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE vacation_checklist_items SET is_done = NOT is_done, completed_at = CASE WHEN is_done = 0 THEN :now ELSE NULL END WHERE id = :id AND user_id = :uid");
        return $stmt->execute([
            'id' => $id,
            'uid' => $userId,
            'now' => date('Y-m-d H:i:s')
        ]);
    }

    public static function deleteAll($userId, $vacationId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM vacation_checklist_items WHERE user_id = :uid AND vacation_id = :vid");
        return $stmt->execute(['uid' => $userId, 'vid' => $vacationId]);
    }
}
