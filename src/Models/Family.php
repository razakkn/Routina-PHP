<?php

namespace Routina\Models;

use Routina\Config\Database;

class Family {
        /**
         * Find all family member records where the given email matches (across all users).
         * Used to show 'linked family' for a user whose email is referenced in someone else's tree.
         */
        public static function findLinkedToEmail($email) {
            $db = Database::getConnection();
            $email = strtolower(trim((string)$email));
            if ($email === '') return [];
            $stmt = $db->prepare("SELECT fm.*, u.display_name as owner_name, u.id as owner_user_id FROM family_members fm JOIN users u ON fm.user_id = u.id WHERE LOWER(fm.email) = :email ORDER BY fm.created_at DESC");
            $stmt->execute(['email' => $email]);
            return $stmt->fetchAll();
        }
    private static function isMissingColumnError(\Throwable $e, string $column): bool {
        $msg = strtolower($e->getMessage());
        $col = strtolower($column);
        // MySQL: "Unknown column 'spouse_member_id'"
        // SQLite: "no such column: spouse_member_id"
        // PostgreSQL: "column ... does not exist"
        return (strpos($msg, 'unknown column') !== false && strpos($msg, $col) !== false)
            || (strpos($msg, 'no such column') !== false && strpos($msg, $col) !== false)
            || (strpos($msg, 'does not exist') !== false && strpos($msg, $col) !== false);
    }

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
        $spouseMemberId = isset($data['spouse_member_id']) ? (int)$data['spouse_member_id'] : 0;
        $createdAt = trim((string)($data['created_at'] ?? ''));

        if ($createdAt === '') {
            $createdAt = gmdate('Y-m-d H:i:s');
        }

        $stmt = $db->prepare("INSERT INTO family_members (
            user_id, name, relation, birthdate, deathdate, gender, side_of_family, email, phone, no_email, mother_id, father_id, spouse_member_id, created_at
        ) VALUES (
            :uid, :name, :rel, :bday, :dday, :gender, :side, :email, :phone, :no_email, :mother_id, :father_id, :spouse_mid, :created_at
        )");

        $params = [
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
            'spouse_mid' => ($spouseMemberId > 0 ? $spouseMemberId : null),
            'created_at' => $createdAt
        ];

        try {
            $ok = $stmt->execute($params);
        } catch (\PDOException $e) {
            if (self::isMissingColumnError($e, 'spouse_member_id')) {
                // Backward compatibility: DB not migrated yet
                $stmt = $db->prepare("INSERT INTO family_members (
                    user_id, name, relation, birthdate, deathdate, gender, side_of_family, email, phone, no_email, mother_id, father_id, created_at
                ) VALUES (
                    :uid, :name, :rel, :bday, :dday, :gender, :side, :email, :phone, :no_email, :mother_id, :father_id, :created_at
                )");
                unset($params['spouse_mid']);
                $ok = $stmt->execute($params);
            } else {
                throw $e;
            }
        }

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
        $spouseMemberId = isset($data['spouse_member_id']) ? (int)$data['spouse_member_id'] : 0;

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
            father_id = :father_id,
            spouse_member_id = :spouse_mid
            WHERE id = :id AND user_id = :uid");

        $params = [
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
            'spouse_mid' => ($spouseMemberId > 0 ? $spouseMemberId : null),
            'id' => $memberId,
            'uid' => $userId
        ];

        try {
            return $stmt->execute($params);
        } catch (\PDOException $e) {
            if (self::isMissingColumnError($e, 'spouse_member_id')) {
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
                unset($params['spouse_mid']);
                return $stmt->execute($params);
            }
            throw $e;
        }
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

    /**
     * Find family member entries across ALL users by email.
     * This is used when a new user signs up to pre-populate their profile
     * with information already entered by their family members.
     * 
     * @param string $email Email to search for
     * @return array Array of family member records with owner user info
     */
    public static function findAllByEmail($email) {
        $db = Database::getConnection();
        $originalEmail = $email;
        $email = strtolower(trim((string)$email));
        
        error_log("Family::findAllByEmail called with email='" . substr(hash('sha256', $originalEmail), 0, 12) . "' normalized='" . substr(hash('sha256', $email), 0, 12) . "'");
        
        if ($email === '') {
            error_log("Family::findAllByEmail returning empty - email is blank");
            return [];
        }
        
        // Try query with no_email check first, fall back if column doesn't exist
        try {
            $stmt = $db->prepare("
                SELECT fm.*, u.display_name as owner_name, u.id as owner_user_id
                FROM family_members fm
                JOIN users u ON fm.user_id = u.id
                WHERE LOWER(fm.email) = :email AND (fm.no_email = 0 OR fm.no_email IS NULL)
                ORDER BY fm.created_at DESC
            ");
            $stmt->execute(['email' => $email]);
            $results = $stmt->fetchAll();
            error_log("Family::findAllByEmail query returned " . count($results) . " results for emailHash=" . substr(hash('sha256', $email), 0, 12));
            
            // Debug: also check how many total records have this email (ignoring no_email flag)
            $stmt2 = $db->prepare("SELECT COUNT(*) as cnt FROM family_members WHERE LOWER(email) = :email");
            $stmt2->execute(['email' => $email]);
            $totalCount = (int)($stmt2->fetch()['cnt'] ?? 0);
            if ($totalCount > 0 && count($results) === 0) {
                error_log("Family::findAllByEmail NOTICE: $totalCount records exist with this email but blocked by no_email flag!");
            }
            
            return $results;
        } catch (\PDOException $e) {
            $msg = strtolower($e->getMessage());
            // If no_email column doesn't exist, retry without it
            if (strpos($msg, 'no_email') !== false || strpos($msg, 'unknown column') !== false) {
                $stmt = $db->prepare("
                    SELECT fm.*, u.display_name as owner_name, u.id as owner_user_id
                    FROM family_members fm
                    JOIN users u ON fm.user_id = u.id
                    WHERE LOWER(fm.email) = :email
                    ORDER BY fm.created_at DESC
                ");
                $stmt->execute(['email' => $email]);
                $results = $stmt->fetchAll();
                error_log("Family::findAllByEmail (fallback) query returned " . count($results) . " results");
                return $results;
            }
            throw $e;
        }
    }

    /**
     * Find family member entries across ALL users by phone number.
     * Phone matching is done by comparing last 10 digits to handle country codes.
     * 
     * @param string $phone Phone number to search for
     * @return array Array of family member records with owner user info
     */
    public static function findAllByPhone($phone) {
        $db = Database::getConnection();
        $phoneKey = self::phoneMatchKey($phone);
        
        if ($phoneKey === '') {
            return [];
        }
        
        // Get all members with phones (no perfect way in SQL for last-10-digit match)
        $stmt = $db->prepare("
            SELECT fm.*, u.display_name as owner_name, u.id as owner_user_id
            FROM family_members fm
            JOIN users u ON fm.user_id = u.id
            WHERE fm.phone IS NOT NULL AND fm.phone != ''
            ORDER BY fm.created_at DESC
        ");
        $stmt->execute();
        $allWithPhone = $stmt->fetchAll();
        
        // Filter by matching last 10 digits
        $matches = [];
        foreach ($allWithPhone as $row) {
            $rowPhoneKey = self::phoneMatchKey($row['phone'] ?? '');
            if ($rowPhoneKey !== '' && $rowPhoneKey === $phoneKey) {
                $matches[] = $row;
            }
        }
        
        return $matches;
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

    /**
     * Update family member records in other users' trees that reference this user's email/phone.
     * Only updates non-relationship fields (name, birthdate, email, phone, gender).
     *
     * @param int $userId The user who updated their profile
     * @param array $fields Keys: display_name, dob, email, phone, gender
     * @return int Number of records updated
     */
    public static function updateLinkedRecordsForUser(int $userId, array $fields): int {
        $db = Database::getConnection();
        $updated = 0;

        $email = isset($fields['email']) ? strtolower(trim((string)$fields['email'])) : '';
        $phone = isset($fields['phone']) ? trim((string)$fields['phone']) : '';

        $targets = [];
        if ($email !== '') {
            $stmt = $db->prepare("SELECT * FROM family_members WHERE LOWER(email) = :email AND user_id <> :uid");
            $stmt->execute(['email' => $email, 'uid' => $userId]);
            $rows = $stmt->fetchAll();
            foreach ($rows as $r) $targets[$r['user_id'] . ':' . $r['id']] = $r;
        }

        if ($phone !== '') {
            $phoneKey = self::phoneMatchKey($phone);
            if ($phoneKey !== '') {
                // Use existing helper to fetch candidates by phone then compare keys
                $candidates = self::findAllByPhone($phone);
                foreach ($candidates as $r) {
                    if ((int)($r['user_id'] ?? 0) === $userId) continue;
                    $targets[$r['user_id'] . ':' . $r['id']] = $r;
                }
            }
        }

        foreach ($targets as $key => $row) {
            $sets = [];
            $params = ['id' => (int)$row['id'], 'uid' => (int)$row['user_id']];

            if (isset($fields['display_name'])) {
                $sets[] = 'name = :name';
                $params['name'] = $fields['display_name'];
            }
            if (array_key_exists('dob', $fields)) {
                $sets[] = 'birthdate = :bday';
                $params['bday'] = ($fields['dob'] === '' ? null : $fields['dob']);
            }
            if (array_key_exists('email', $fields)) {
                $sets[] = 'email = :email';
                $params['email'] = ($fields['email'] === '' ? null : $fields['email']);
            }
            if (array_key_exists('phone', $fields)) {
                $sets[] = 'phone = :phone';
                $params['phone'] = ($fields['phone'] === '' ? null : $fields['phone']);
            }
            if (array_key_exists('gender', $fields)) {
                $sets[] = 'gender = :gender';
                $params['gender'] = ($fields['gender'] === '' ? null : $fields['gender']);
            }

            if (empty($sets)) continue;

            $sql = 'UPDATE family_members SET ' . implode(', ', $sets) . ' WHERE id = :id AND user_id = :uid';
            $stmt = $db->prepare($sql);
            try {
                if ($stmt->execute($params)) {
                    $updated += $stmt->rowCount();
                }
            } catch (\PDOException $e) {
                // Ignore update errors per-row to be resilient against schema drift
            }
        }

        return $updated;
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
