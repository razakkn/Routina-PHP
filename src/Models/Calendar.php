<?php

namespace Routina\Models;

use Routina\Config\Database;

class Calendar {
    public static function upcoming($userId) {
        $db = Database::getConnection();

                $driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);
                if ($driver === 'pgsql') {
                        // In Postgres, start_datetime is stored as TEXT (ISO-like). Cast for comparison.
                        $sql = "SELECT * FROM calendar_events
                                        WHERE user_id = :uid
                                            AND start_datetime::timestamp >= CURRENT_DATE
                                        ORDER BY start_datetime::timestamp ASC
                                        LIMIT 10";
                } elseif ($driver === 'mysql') {
                        // MySQL: use CURDATE() for current date
                        $sql = "SELECT * FROM calendar_events
                                        WHERE user_id = :uid
                                            AND start_datetime >= CURDATE()
                                        ORDER BY start_datetime ASC
                                        LIMIT 10";
                } else {
                        // SQLite fallback
                        $sql = "SELECT * FROM calendar_events
                                        WHERE user_id = :uid
                                            AND start_datetime >= date('now')
                                        ORDER BY start_datetime ASC
                                        LIMIT 10";
                }

                $stmt = $db->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public static function create($userId, $title, $start, $end, $type) {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO calendar_events (user_id, title, start_datetime, end_datetime, type) VALUES (:uid, :title, :start, :end, :type)");
        return $stmt->execute([
            'uid' => $userId, 
            'title' => $title, 
            'start' => $start, 
            'end' => $end,
            'type' => $type
        ]);
    }

    /**
     * Delete a calendar event.
     *
     * @param int $userId User ID
     * @param int $eventId Event ID
     * @return bool True on success
     */
    public static function delete(int $userId, int $eventId): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM calendar_events WHERE id = :id AND user_id = :uid");
        return $stmt->execute(['id' => $eventId, 'uid' => $userId]);
    }

    /**
     * Find a calendar event by ID.
     *
     * @param int $userId User ID
     * @param int $eventId Event ID
     * @return array|null Event data or null
     */
    public static function find(int $userId, int $eventId): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM calendar_events WHERE id = :id AND user_id = :uid");
        $stmt->execute(['id' => $eventId, 'uid' => $userId]);
        $result = $stmt->fetch();
        return $result ?: null;
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
            $sql = "SELECT id, title, start_datetime, end_datetime, type
                    FROM calendar_events
                    WHERE user_id = :uid
                        AND (title ILIKE :q OR type ILIKE :q)
                    ORDER BY start_datetime::timestamp ASC
                    LIMIT {$limit}";
        } else {
            $sql = "SELECT id, title, start_datetime, end_datetime, type
                    FROM calendar_events
                    WHERE user_id = :uid
                        AND (title LIKE :q ESCAPE '\\' OR type LIKE :q ESCAPE '\\')
                    ORDER BY start_datetime ASC
                    LIMIT {$limit}";
        }

        $stmt = $db->prepare($sql);
        $stmt->execute(['uid' => $userId, 'q' => $like]);
        return $stmt->fetchAll();
    }
}
