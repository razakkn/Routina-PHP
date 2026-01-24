<?php

namespace Routina\Models;

use Routina\Config\Database;

class Family {
    public static function getAll($userId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM family_members WHERE user_id = :uid ORDER BY (birthdate IS NULL OR birthdate = ''), birthdate ASC, id ASC");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public static function create($userId, $name, $relation, $bday) {
        return self::createExtended($userId, [
            'name' => $name,
            'relation' => $relation,
            'birthdate' => $bday
        ]);
    }

    public static function findByIdForUser($userId, $memberId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM family_members WHERE id = :id AND user_id = :uid");
        $stmt->execute(['id' => $memberId, 'uid' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function createExtended($userId, $data) {
        $db = Database::getConnection();

        $name = trim((string)($data['name'] ?? ''));
        $relation = trim((string)($data['relation'] ?? ''));
        $birthdate = trim((string)($data['birthdate'] ?? ''));
        $deathdate = trim((string)($data['deathdate'] ?? ''));
        $gender = trim((string)($data['gender'] ?? ''));
        $side = trim((string)($data['side_of_family'] ?? ''));
        $email = isset($data['email']) ? trim((string)$data['email']) : '';
        $phone = trim((string)($data['phone'] ?? ''));
        $noEmail = !empty($data['no_email']) ? 1 : 0;
        $motherId = isset($data['mother_id']) ? (int)$data['mother_id'] : 0;
        $fatherId = isset($data['father_id']) ? (int)$data['father_id'] : 0;
        $createdAt = trim((string)($data['created_at'] ?? ''));

        if ($createdAt === '') {
            $createdAt = gmdate('Y-m-d H:i:s');
        }

        $stmt = $db->prepare("INSERT INTO family_members (
            user_id, name, relation, birthdate, deathdate, gender, side_of_family, email, phone, no_email, mother_id, father_id, created_at
        ) VALUES (
            :uid, :name, :rel, :bday, :dday, :gender, :side, :email, :phone, :no_email, :mother_id, :father_id, :created_at
        )");

        $ok = $stmt->execute([
            'uid' => $userId,
            'name' => $name,
            'rel' => $relation,
            'bday' => ($birthdate === '' ? null : $birthdate),
            'dday' => ($deathdate === '' ? null : $deathdate),
            'gender' => ($gender === '' ? null : $gender),
            'side' => ($side === '' ? null : $side),
            'email' => ($noEmail ? null : ($email === '' ? null : $email)),
            'phone' => ($phone === '' ? null : $phone),
            'no_email' => $noEmail,
            'mother_id' => ($motherId > 0 ? $motherId : null),
            'father_id' => ($fatherId > 0 ? $fatherId : null),
            'created_at' => $createdAt
        ]);

        if (!$ok) {
            return null;
        }

        try {
            return (int)$db->lastInsertId();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function updateByIdForUser($userId, $memberId, $data) {
        $db = Database::getConnection();

        $name = trim((string)($data['name'] ?? ''));
        $relation = trim((string)($data['relation'] ?? ''));
        $birthdate = trim((string)($data['birthdate'] ?? ''));
        $deathdate = trim((string)($data['deathdate'] ?? ''));
        $gender = trim((string)($data['gender'] ?? ''));
        $side = trim((string)($data['side_of_family'] ?? ''));
        $email = isset($data['email']) ? trim((string)$data['email']) : '';
        $phone = trim((string)($data['phone'] ?? ''));
        $noEmail = !empty($data['no_email']) ? 1 : 0;
        $motherId = isset($data['mother_id']) ? (int)$data['mother_id'] : 0;
        $fatherId = isset($data['father_id']) ? (int)$data['father_id'] : 0;

        $stmt = $db->prepare("UPDATE family_members SET
            name = :name,
            relation = :rel,
            birthdate = :bday,
            deathdate = :dday,
            gender = :gender,
            side_of_family = :side,
            email = :email,
            phone = :phone,
            no_email = :no_email,
            mother_id = :mother_id,
            father_id = :father_id
            WHERE id = :id AND user_id = :uid");

        return $stmt->execute([
            'name' => $name,
            'rel' => $relation,
            'bday' => ($birthdate === '' ? null : $birthdate),
            'dday' => ($deathdate === '' ? null : $deathdate),
            'gender' => ($gender === '' ? null : $gender),
            'side' => ($side === '' ? null : $side),
            'email' => ($noEmail ? null : ($email === '' ? null : $email)),
            'phone' => ($phone === '' ? null : $phone),
            'no_email' => $noEmail,
            'mother_id' => ($motherId > 0 ? $motherId : null),
            'father_id' => ($fatherId > 0 ? $fatherId : null),
            'id' => $memberId,
            'uid' => $userId
        ]);
    }

    public static function updateParentsByIdForUser($userId, $memberId, $motherId, $fatherId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE family_members
            SET mother_id = :mother_id,
                father_id = :father_id
            WHERE id = :id AND user_id = :uid");

        return $stmt->execute([
            'mother_id' => ($motherId > 0 ? (int)$motherId : null),
            'father_id' => ($fatherId > 0 ? (int)$fatherId : null),
            'id' => (int)$memberId,
            'uid' => (int)$userId
        ]);
    }

    public static function clearParentRefsForUser($userId, $memberId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE family_members
            SET mother_id = CASE WHEN mother_id = :mid THEN NULL ELSE mother_id END,
                father_id = CASE WHEN father_id = :mid THEN NULL ELSE father_id END
            WHERE user_id = :uid");
        return $stmt->execute(['mid' => (int)$memberId, 'uid' => (int)$userId]);
    }

    public static function deleteByIdForUser($userId, $memberId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM family_members WHERE id = :id AND user_id = :uid");
        return $stmt->execute(['id' => $memberId, 'uid' => $userId]);
    }

    private static function normalizeEmail($email) {
        $e = strtolower(trim((string)$email));
        return $e === '' ? '' : $e;
    }

    private static function phoneDigits($phone) {
        $p = (string)$phone;
        $digits = preg_replace('/\D+/', '', $p);
        return is_string($digits) ? $digits : '';
    }

    // Country-code tolerant key: compares the last 10 digits when possible.
    private static function phoneMatchKey($phone) {
        $digits = self::phoneDigits($phone);
        if ($digits === '') {
            return '';
        }
        if (strlen($digits) >= 10) {
            return substr($digits, -10);
        }
        return $digits;
    }

    /**
     * Returns matches keyed by family member id.
     * A match means: that member's email or phone matches another registered user (case-insensitive for email,
     * and phone is compared by digits with a last-10-digits key to tolerate country codes).
     */
    public static function getContactMatches($currentUserId, $members) {
        $db = Database::getConnection();
        $currentUserId = (int)$currentUserId;

        $emailByMemberId = [];
        $phoneKeyByMemberId = [];
        foreach (($members ?? []) as $m) {
            $mid = (int)($m['id'] ?? 0);
            if ($mid <= 0) continue;

            $noEmail = !empty($m['no_email']);
            $email = $noEmail ? '' : self::normalizeEmail($m['email'] ?? '');
            $phoneKey = self::phoneMatchKey($m['phone'] ?? '');

            $emailByMemberId[$mid] = $email;
            $phoneKeyByMemberId[$mid] = $phoneKey;
        }

        // Pull other users and build lookup maps.
        $stmt = $db->prepare("SELECT id, email, display_name, phone FROM users WHERE id <> :id");
        $stmt->execute(['id' => $currentUserId]);
        $users = $stmt->fetchAll();

        $emailMap = [];      // normalized email => user row
        $phoneKeyMap = [];   // last10 digits => user row
        foreach ($users as $u) {
            $uid = (int)($u['id'] ?? 0);
            if ($uid <= 0) continue;

            $uEmail = self::normalizeEmail($u['email'] ?? '');
            if ($uEmail !== '' && !isset($emailMap[$uEmail])) {
                $emailMap[$uEmail] = $u;
            }

            $uPhoneKey = self::phoneMatchKey($u['phone'] ?? '');
            if ($uPhoneKey !== '' && !isset($phoneKeyMap[$uPhoneKey])) {
                $phoneKeyMap[$uPhoneKey] = $u;
            }
        }

        $matches = [];
        foreach ($emailByMemberId as $mid => $em) {
            $pk = $phoneKeyByMemberId[$mid] ?? '';

            if ($em !== '' && isset($emailMap[$em])) {
                $u = $emailMap[$em];
                $matches[$mid] = [
                    'type' => 'email',
                    'user_id' => (int)($u['id'] ?? 0),
                    'display_name' => (string)($u['display_name'] ?? 'User')
                ];
                continue;
            }

            if ($pk !== '' && isset($phoneKeyMap[$pk])) {
                $u = $phoneKeyMap[$pk];
                $matches[$mid] = [
                    'type' => 'phone',
                    'user_id' => (int)($u['id'] ?? 0),
                    'display_name' => (string)($u['display_name'] ?? 'User')
                ];
                continue;
            }
        }

        return $matches;
    }

    private static function escapeLike($s) {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $s);
    }

    public static function search($userId, $query, $limit = 10) {
        $db = Database::getConnection();
        $q = trim((string)$query);
        if ($q === '') {
            return [];
        }

        $limit = max(1, min(50, (int)$limit));

        $driver = '';
        try {
            $driver = (string)$db->getAttribute(\PDO::ATTR_DRIVER_NAME);
        } catch (\Throwable $e) {
            $driver = '';
        }

        if ($driver === 'pgsql') {
            $stmt = $db->prepare("SELECT *
                FROM family_members
                WHERE user_id = :uid
                  AND (
                    name ILIKE :q
                    OR relation ILIKE :q
                    OR COALESCE(email, '') ILIKE :q
                    OR COALESCE(phone, '') ILIKE :q
                  )
                ORDER BY name ASC
                LIMIT {$limit}");
            $stmt->execute(['uid' => $userId, 'q' => '%' . $q . '%']);
            return $stmt->fetchAll();
        }

        $escaped = self::escapeLike($q);
        $stmt = $db->prepare("SELECT *
            FROM family_members
            WHERE user_id = :uid
              AND (
                name LIKE :q ESCAPE '\\\\'
                OR relation LIKE :q ESCAPE '\\\\'
                OR IFNULL(email, '') LIKE :q ESCAPE '\\\\'
                OR IFNULL(phone, '') LIKE :q ESCAPE '\\\\'
              )
            ORDER BY name ASC
            LIMIT {$limit}");
        $stmt->execute(['uid' => $userId, 'q' => '%' . $escaped . '%']);
        return $stmt->fetchAll();
    }
}
