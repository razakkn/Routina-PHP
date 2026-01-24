<?php

namespace Routina\Models;

use Routina\Config\Database;

class Buzz {
    private static $unreadCountCache = [];
    private static $missingTableLogged = false;

    private static function isMissingBuzzTableError($e) {
        if (!$e) {
            return false;
        }

        $code = (string)($e->getCode() ?? '');
        // Postgres: 42P01 (undefined_table)
        if ($code === '42P01') {
            return true;
        }

        $msg = strtolower((string)($e->getMessage() ?? ''));
        // SQLite: "no such table: buzz_requests"
        if (strpos($msg, 'no such table') !== false && strpos($msg, 'buzz_requests') !== false) {
            return true;
        }
        // Postgres message often includes: relation "buzz_requests" does not exist
        if (strpos($msg, 'buzz_requests') !== false && strpos($msg, 'does not exist') !== false) {
            return true;
        }

        return false;
    }

    private static function logMissingBuzzTableOnce($e) {
        if (self::$missingTableLogged) {
            return;
        }
        self::$missingTableLogged = true;

        $msg = (string)($e->getMessage() ?? 'buzz_requests table missing');
        error_log('Buzz table missing: ' . $msg);
    }

    public static function unreadCount($userId) {
        $userId = (int)$userId;
        if ($userId > 0 && isset(self::$unreadCountCache[$userId])) {
            return (int)self::$unreadCountCache[$userId];
        }

        try {
            $db = Database::getConnection();

            $stmt = $db->prepare("SELECT COUNT(*) AS c FROM buzz_requests WHERE to_user_id = :uid AND status = :status");
            $stmt->execute([
                'uid' => $userId,
                'status' => 'pending'
            ]);
            $row = $stmt->fetch();
            $count = (int)($row['c'] ?? 0);
        } catch (\Throwable $e) {
            if (self::isMissingBuzzTableError($e)) {
                self::logMissingBuzzTableOnce($e);
                $count = 0;
            } else {
                throw $e;
            }
        }
        if ($userId > 0) {
            self::$unreadCountCache[$userId] = $count;
        }
        return $count;
    }

    public static function acknowledgeAllPending($recipientUserId) {
        $recipientUserId = (int)$recipientUserId;
        try {
            $db = Database::getConnection();

            $stmt = $db->prepare("UPDATE buzz_requests
                SET status = :status,
                    responded_at = :responded_at
                WHERE to_user_id = :uid AND status = :pending");

            return $stmt->execute([
                'status' => 'acknowledged',
                'responded_at' => gmdate('Y-m-d H:i:s'),
                'uid' => $recipientUserId,
                'pending' => 'pending'
            ]);
        } catch (\Throwable $e) {
            if (self::isMissingBuzzTableError($e)) {
                self::logMissingBuzzTableOnce($e);
                return false;
            }
            throw $e;
        }
    }

    public static function create($fromUserId, $toUserId, $familyMemberId, $channel, $message) {
        $fromUserId = (int)$fromUserId;
        $toUserId = (int)$toUserId;
        $familyMemberId = (int)$familyMemberId;
        $channel = trim((string)$channel);
        $message = trim((string)$message);

        if ($channel === '') {
            $channel = 'in_app';
        }

        if ($message === '') {
            $message = 'Buzz: can we connect?';
        }

        $createdAt = gmdate('Y-m-d H:i:s');

        try {
            $db = Database::getConnection();

            $stmt = $db->prepare("INSERT INTO buzz_requests (
                from_user_id, to_user_id, family_member_id, channel, message, status, created_at
            ) VALUES (
                :from_id, :to_id, :family_member_id, :channel, :message, :status, :created_at
            )");

            return $stmt->execute([
                'from_id' => $fromUserId,
                'to_id' => $toUserId,
                'family_member_id' => ($familyMemberId > 0 ? $familyMemberId : null),
                'channel' => $channel,
                'message' => $message,
                'status' => 'pending',
                'created_at' => $createdAt
            ]);
        } catch (\Throwable $e) {
            if (self::isMissingBuzzTableError($e)) {
                self::logMissingBuzzTableOnce($e);
                return false;
            }
            throw $e;
        }
    }

    public static function preview($userId, $limit = 5) {
        $userId = (int)$userId;
        $limit = max(1, min(20, (int)$limit));

        try {
            $db = Database::getConnection();

            $stmt = $db->prepare("SELECT b.*, u.display_name AS from_display_name
                FROM buzz_requests b
                LEFT JOIN users u ON u.id = b.from_user_id
                WHERE b.to_user_id = :uid AND b.status = 'pending'
                ORDER BY b.created_at DESC
                LIMIT {$limit}");
            $stmt->execute(['uid' => $userId]);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            if (self::isMissingBuzzTableError($e)) {
                self::logMissingBuzzTableOnce($e);
                return [];
            }
            throw $e;
        }
    }

    public static function inbox($userId, $limit = 50) {
        $userId = (int)$userId;
        $limit = max(1, min(200, (int)$limit));

        try {
            $db = Database::getConnection();

            $stmt = $db->prepare("SELECT b.*, u.display_name AS from_display_name, u.email AS from_email
                FROM buzz_requests b
                LEFT JOIN users u ON u.id = b.from_user_id
                WHERE b.to_user_id = :uid
                ORDER BY b.created_at DESC
                LIMIT {$limit}");
            $stmt->execute(['uid' => $userId]);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            if (self::isMissingBuzzTableError($e)) {
                self::logMissingBuzzTableOnce($e);
                return [];
            }
            throw $e;
        }
    }

    public static function outbox($userId, $limit = 50) {
        $userId = (int)$userId;
        $limit = max(1, min(200, (int)$limit));

        try {
            $db = Database::getConnection();

            $stmt = $db->prepare("SELECT b.*, u.display_name AS to_display_name, u.email AS to_email
                FROM buzz_requests b
                LEFT JOIN users u ON u.id = b.to_user_id
                WHERE b.from_user_id = :uid
                ORDER BY b.created_at DESC
                LIMIT {$limit}");
            $stmt->execute(['uid' => $userId]);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            if (self::isMissingBuzzTableError($e)) {
                self::logMissingBuzzTableOnce($e);
                return [];
            }
            throw $e;
        }
    }

    public static function setStatusForRecipient($recipientUserId, $buzzId, $status) {
        $recipientUserId = (int)$recipientUserId;
        $buzzId = (int)$buzzId;
        $status = trim((string)$status);

        $allowed = ['pending', 'acknowledged', 'done', 'dismissed'];
        if (!in_array($status, $allowed, true)) {
            $status = 'acknowledged';
        }

        try {
            $db = Database::getConnection();

            $stmt = $db->prepare("UPDATE buzz_requests
                SET status = :status,
                    responded_at = :responded_at
                WHERE id = :id AND to_user_id = :uid");

            return $stmt->execute([
                'status' => $status,
                'responded_at' => gmdate('Y-m-d H:i:s'),
                'id' => $buzzId,
                'uid' => $recipientUserId
            ]);
        } catch (\Throwable $e) {
            if (self::isMissingBuzzTableError($e)) {
                self::logMissingBuzzTableOnce($e);
                return false;
            }
            throw $e;
        }
    }
}
