<?php

namespace Routina\Models;

use Routina\Config\Database;

class Journal {
    public static function getAll($userId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM journal_entries WHERE user_id = :uid ORDER BY entry_date DESC");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public static function latest($userId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM journal_entries WHERE user_id = :uid ORDER BY entry_date DESC LIMIT 1");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetch();
    }

    public static function countSince($userId, $sinceDate) {
        $db = Database::getConnection();
        $driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);

        if ($driver === 'pgsql') {
            $sql = "SELECT COUNT(*) FROM journal_entries WHERE user_id = :uid AND entry_date::date >= CAST(:since AS date)";
        } else {
            $sql = "SELECT COUNT(*) FROM journal_entries WHERE user_id = :uid AND date(entry_date) >= date(:since)";
        }

        $stmt = $db->prepare($sql);
        $stmt->execute(['uid' => $userId, 'since' => $sinceDate]);
        return (int)$stmt->fetchColumn();
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
            $sql = "SELECT id, entry_date, mood, content
                    FROM journal_entries
                    WHERE user_id = :uid
                        AND (content ILIKE :q OR mood ILIKE :q)
                    ORDER BY entry_date DESC
                    LIMIT {$limit}";
        } else {
            $sql = "SELECT id, entry_date, mood, content
                    FROM journal_entries
                    WHERE user_id = :uid
                        AND (content LIKE :q ESCAPE '\\' OR mood LIKE :q ESCAPE '\\')
                    ORDER BY entry_date DESC
                    LIMIT {$limit}";
        }

        $stmt = $db->prepare($sql);
        $stmt->execute(['uid' => $userId, 'q' => $like]);
        return $stmt->fetchAll();
    }

    public static function getByDate($userId, $date) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM journal_entries WHERE user_id = :uid AND entry_date = :date ORDER BY entry_date DESC");
        $stmt->execute(['uid' => $userId, 'date' => $date]);
        return $stmt->fetchAll();
    }

    public static function find($userId, $id) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM journal_entries WHERE user_id = :uid AND id = :id");
        $stmt->execute(['uid' => $userId, 'id' => $id]);
        return $stmt->fetch();
    }

    public static function create($userId, $date, $content, $mood) {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO journal_entries (user_id, entry_date, content, mood) VALUES (:uid, :date, :content, :mood)");
        return $stmt->execute([
            'uid' => $userId, 
            'date' => $date, 
            'content' => $content, 
            'mood' => $mood
        ]);
    }
}
