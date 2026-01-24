<?php

namespace Routina\Models;

use Routina\Config\Database;

class HomeTask {
    public static function getAll($userId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM home_tasks WHERE user_id = :uid");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public static function create($userId, $title, $freq, $assignee) {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO home_tasks (user_id, title, frequency, assigned_to) VALUES (:uid, :title, :freq, :assignee)");
        return $stmt->execute([
            'uid' => $userId, 
            'title' => $title, 
            'freq' => $freq, 
            'assignee' => $assignee
        ]);
    }

    public static function toggle($id, $userId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE home_tasks SET is_completed = NOT is_completed WHERE id = :id AND user_id = :uid");
        return $stmt->execute(['id' => $id, 'uid' => $userId]);
    }

    public static function search($userId, $query, $limit = 10) {
        $db = Database::getConnection();
        $driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);

        $q = trim((string)$query);
        if ($q === '') {
            return [];
        }

        $q = str_replace(['%', '_'], ['\\%', '\\_'], $q);
        $like = '%' . $q . '%';

        $limit = (int)$limit;
        if ($limit <= 0) {
            $limit = 10;
        }
        $limit = min(50, $limit);

        if ($driver === 'pgsql') {
            $sql = "SELECT id, title, frequency, assigned_to, is_completed
                    FROM home_tasks
                    WHERE user_id = :uid
                        AND (title ILIKE :q OR frequency ILIKE :q OR assigned_to ILIKE :q)
                    ORDER BY id DESC
                    LIMIT {$limit}";
        } else {
            $sql = "SELECT id, title, frequency, assigned_to, is_completed
                    FROM home_tasks
                    WHERE user_id = :uid
                        AND (title LIKE :q ESCAPE '\\' OR frequency LIKE :q ESCAPE '\\' OR assigned_to LIKE :q ESCAPE '\\')
                    ORDER BY id DESC
                    LIMIT {$limit}";
        }

        $stmt = $db->prepare($sql);
        $stmt->execute(['uid' => $userId, 'q' => $like]);
        return $stmt->fetchAll();
    }
}
