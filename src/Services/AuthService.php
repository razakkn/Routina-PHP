<?php

namespace Routina\Services;

use Routina\Config\Database;
use Routina\Models\Family;

/**
 * Authentication service for user registration, login, and identity management.
 */
class AuthService
{
    /**
     * Cache of users table columns for the active DB connection.
     * @var array<string, bool>|null
     */
    private static ?array $usersColumnMap = null;

    /**
     * @return array<string, bool> map of columnName => true
     */
    private static function getUsersColumnMap(): array
    {
        if (self::$usersColumnMap !== null) {
            return self::$usersColumnMap;
        }

        $db = Database::getConnection();
        $cols = [];

        try {
            $driver = (string)$db->getAttribute(\PDO::ATTR_DRIVER_NAME);
        } catch (\Throwable $e) {
            $driver = '';
        }

        try {
            if ($driver === 'mysql') {
                $stmt = $db->query('DESCRIBE users');
                foreach (($stmt ? $stmt->fetchAll() : []) as $row) {
                    $name = (string)($row['Field'] ?? '');
                    if ($name !== '') $cols[$name] = true;
                }
            } elseif ($driver === 'sqlite') {
                $stmt = $db->query("PRAGMA table_info('users')");
                foreach (($stmt ? $stmt->fetchAll() : []) as $row) {
                    $name = (string)($row['name'] ?? '');
                    if ($name !== '') $cols[$name] = true;
                }
            } elseif ($driver === 'pgsql') {
                $stmt = $db->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = 'users'");
                $stmt->execute();
                foreach ($stmt->fetchAll() as $row) {
                    $name = (string)($row['column_name'] ?? '');
                    if ($name !== '') $cols[$name] = true;
                }
            }
        } catch (\Throwable $e) {
            // If introspection fails (permissions / driver quirks), fall back to common columns.
            $cols = [
                'id' => true,
                'email' => true,
                'phone' => true,
                'display_name' => true,
                'dob' => true,
                'gender' => true,
                'relationship_status' => true,
                'routina_id' => true,
                'google_id' => true,
            ];
        }

        self::$usersColumnMap = $cols;
        return self::$usersColumnMap;
    }

    private static function hasUserColumn(string $column): bool
    {
        $map = self::getUsersColumnMap();
        return isset($map[$column]);
    }

    /**
     * Normalize phone to digits only, and use last 10 digits for tolerant matching.
     */
    private static function phoneMatchKey(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string)$phone);
        if (!is_string($digits) || $digits === '') {
            return '';
        }
        if (strlen($digits) >= 10) {
            return substr($digits, -10);
        }
        return $digits;
    }

    private static function maskEmail(?string $email): string
    {
        $e = strtolower(trim((string)$email));
        if ($e === '') return '';
        return substr(hash('sha256', $e), 0, 12);
    }

    private static function maskPhone(?string $phone): string
    {
        $key = self::phoneMatchKey((string)$phone);
        if ($key === '') return '';
        $len = strlen($key);
        return ($len <= 4) ? $key : (str_repeat('*', $len - 4) . substr($key, -4));
    }

    /**
     * Execute an UPDATE users ... for the provided update expressions and parameters.
     * Automatically filters out columns that do not exist.
     *
     * @param int $userId
     * @param array<string, mixed> $candidateUpdates column => value
     */
    private static function updateUserColumns(int $userId, array $candidateUpdates): bool
    {
        $db = Database::getConnection();

        if ($userId <= 0 || empty($candidateUpdates)) {
            return false;
        }

        $updates = [];
        $params = ['id' => $userId];

        foreach ($candidateUpdates as $col => $val) {
            if (!self::hasUserColumn((string)$col)) {
                continue;
            }
            $paramKey = 'c_' . preg_replace('/[^a-zA-Z0-9_]/', '_', (string)$col);
            $updates[] = "{$col} = :{$paramKey}";
            $params[$paramKey] = $val;
        }

        if (empty($updates)) {
            return false;
        }

        $attempts = 0;
        while (true) {
            $attempts++;
            $sql = 'UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = :id';
            try {
                $stmt = $db->prepare($sql);
                return (bool)$stmt->execute($params);
            } catch (\PDOException $e) {
                $missing = self::extractMissingColumnName($e);
                if ($missing === null || $attempts >= 3) {
                    throw $e;
                }

                // Drop the missing column from this update and retry.
                $missing = (string)$missing;
                $filteredUpdates = [];
                $filteredParams = ['id' => $userId];
                foreach ($candidateUpdates as $col => $val) {
                    if ((string)$col === $missing) {
                        continue;
                    }
                    if (!self::hasUserColumn((string)$col)) {
                        continue;
                    }
                    $paramKey = 'c_' . preg_replace('/[^a-zA-Z0-9_]/', '_', (string)$col);
                    $filteredUpdates[] = "{$col} = :{$paramKey}";
                    $filteredParams[$paramKey] = $val;
                }
                if (empty($filteredUpdates)) {
                    return false;
                }
                $updates = $filteredUpdates;
                $params = $filteredParams;
            }
        }
    }

    /**
     * Returns missing column name for "unknown column"-style errors, else null.
     */
    private static function extractMissingColumnName(\PDOException $e): ?string
    {
        $msg = strtolower($e->getMessage());

        // MySQL: Unknown column 'dob' in 'field list'
        if (strpos($msg, 'unknown column') !== false) {
            if (preg_match("/unknown column\s+'([^']+)'/i", $e->getMessage(), $m)) {
                return $m[1] ?? null;
            }
        }

        // SQLite: no such column: dob
        if (strpos($msg, 'no such column') !== false) {
            if (preg_match('/no such column:\s*([a-zA-Z0-9_]+)/i', $e->getMessage(), $m)) {
                return $m[1] ?? null;
            }
        }

        // Postgres: column "dob" does not exist
        if (strpos($msg, 'does not exist') !== false && strpos($msg, 'column') !== false) {
            if (preg_match('/column\s+"([^"]+)"\s+does not exist/i', $e->getMessage(), $m)) {
                return $m[1] ?? null;
            }
        }

        // SQLSTATE 42S22 is "Column not found" on many drivers
        if ((string)$e->getCode() === '42S22') {
            return null;
        }

        return null;
    }

    /**
     * Auto-populate a newly registered user's profile from family member data.
     * When someone signs up with an email that exists in another user's family tree,
     * their profile gets pre-filled with that information.
     * 
     * @param int $userId The new user's ID
     * @param string $email The user's email address
     * @param string|null $phone The user's phone number (optional)
     * @return bool True if any data was populated
     */
    public static function autoPopulateFromFamilyTree(int $userId, string $email, ?string $phone = null): bool
    {
        $db = Database::getConnection();

        $trace = bin2hex(random_bytes(3));
        error_log('AUTOFILL[new] trace=' . $trace . ' user=' . $userId . ' emailHash=' . self::maskEmail($email) . ' phone=' . self::maskPhone($phone));
        
        // First try to find by email
        $familyData = Family::findAllByEmail($email);
        
        // If no email match and phone provided, try phone
        if (empty($familyData) && $phone !== null && $phone !== '') {
            $familyData = Family::findAllByPhone($phone);
        }
        
        if (empty($familyData)) {
            error_log('AUTOFILL[new] trace=' . $trace . ' no family match');
            return false;
        }
        
        // Use the most recent entry (first in the array since ordered by created_at DESC)
        $familyMember = $familyData[0];
        

        // Map family member fields to user profile fields
        $candidate = [];
        if (!empty($familyMember['name'])) {
            $candidate['display_name'] = $familyMember['name'];
        }
        if (!empty($familyMember['birthdate'])) {
            $candidate['dob'] = $familyMember['birthdate'];
        }
        if (!empty($familyMember['gender'])) {
            $candidate['gender'] = $familyMember['gender'];
        }
        if (!empty($familyMember['phone'])) {
            $candidate['phone'] = $familyMember['phone'];
        }
        if (!empty($familyMember['relation'])) {
            $candidate['family_relation'] = $familyMember['relation'];
        }

        $relation = strtolower(trim((string)($familyMember['relation'] ?? '')));
        if (in_array($relation, ['spouse', 'wife', 'husband'], true)) {
            $candidate['relationship_status'] = 'married';
        } elseif (in_array($relation, ['girlfriend', 'boyfriend'], true)) {
            $candidate['relationship_status'] = 'in_relationship';
        }

        if (empty($candidate)) {
            error_log('AUTOFILL[new] trace=' . $trace . ' match found but no candidate fields');
            return false;
        }

        $result = self::updateUserColumns($userId, $candidate);
        if ($result) {
            $ownerId = (int)($familyMember['owner_user_id'] ?? 0);
            error_log("Auto-populated profile for user {$userId} from family member data" . ($ownerId > 0 ? " (owner: {$ownerId})" : ''));
        } else {
            error_log("Auto-populate skipped for user {$userId}: no updatable columns matched current schema");
        }

        return $result;
    }

    /**
     * Auto-populate an existing user's profile from family member data.
     * Called when someone adds a family member with an email that belongs to an existing user.
     * 
     * @param array $familyMemberData The family member data being added/updated
     * @return bool True if any data was populated
     */
    public static function populateExistingUserFromFamilyData(array $familyMemberData): bool
    {
        $email = strtolower(trim((string)($familyMemberData['email'] ?? '')));
        $phone = trim((string)($familyMemberData['phone'] ?? ''));

        $trace = bin2hex(random_bytes(3));
        error_log('AUTOFILL[existing] trace=' . $trace . ' emailHash=' . self::maskEmail($email) . ' phone=' . self::maskPhone($phone));
        
        if ($email === '' && $phone === '') {
            error_log('AUTOFILL[existing] trace=' . $trace . ' aborted: no email/phone');
            return false;
        }
        
        $db = Database::getConnection();
        $existingUser = null;

        // Try to find existing user by email
        if ($email !== '') {
            $existingUser = self::findByEmail($email);
        }

        // If not found by email, try by phone (tolerant matching)
        if (!$existingUser && $phone !== '') {
            $existingUser = self::findByPhone($phone);
            if (!$existingUser && self::hasUserColumn('phone')) {
                $phoneKey = self::phoneMatchKey($phone);
                if ($phoneKey !== '') {
                    $stmt = $db->query("SELECT id, email, phone, display_name FROM users WHERE phone IS NOT NULL AND phone <> ''");
                    foreach (($stmt ? $stmt->fetchAll() : []) as $u) {
                        $uKey = self::phoneMatchKey((string)($u['phone'] ?? ''));
                        if ($uKey !== '' && $uKey === $phoneKey) {
                            $existingUser = $u;
                            break;
                        }
                    }
                }
            }
        }
        
        if (!$existingUser) {
            error_log('AUTOFILL[existing] trace=' . $trace . ' no matching user found');
            return false;
        }
        
        $userId = (int)$existingUser['id'];
        error_log('AUTOFILL[existing] trace=' . $trace . ' matchedUser=' . $userId);
        
        // Get current user data to check which fields are empty (only select columns that exist)
        $selectCols = [];
        foreach (['display_name', 'dob', 'gender', 'phone', 'relationship_status'] as $col) {
            if (self::hasUserColumn($col)) {
                $selectCols[] = $col;
            }
        }
        if (empty($selectCols)) {
            error_log('AUTOFILL[existing] trace=' . $trace . ' aborted: no selectable columns');
            return false;
        }
        $stmt = $db->prepare('SELECT ' . implode(', ', $selectCols) . ' FROM users WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        $currentData = $stmt->fetch();
        
        if (!$currentData) {
            error_log('AUTOFILL[existing] trace=' . $trace . ' aborted: could not load current user row');
            return false;
        }
        

        // Build update data - only populate empty fields
        $candidate = [];

        if (self::hasUserColumn('display_name') && empty($currentData['display_name']) && !empty($familyMemberData['name'])) {
            $candidate['display_name'] = $familyMemberData['name'];
        }
        if (self::hasUserColumn('dob') && empty($currentData['dob']) && !empty($familyMemberData['birthdate'])) {
            $candidate['dob'] = $familyMemberData['birthdate'];
        }
        if (self::hasUserColumn('gender') && empty($currentData['gender']) && !empty($familyMemberData['gender'])) {
            $candidate['gender'] = $familyMemberData['gender'];
        }
        if (self::hasUserColumn('phone') && empty($currentData['phone']) && !empty($familyMemberData['phone'])) {
            $candidate['phone'] = $familyMemberData['phone'];
        }
        if (self::hasUserColumn('family_relation') && empty($currentData['family_relation']) && !empty($familyMemberData['relation'])) {
            $candidate['family_relation'] = $familyMemberData['relation'];
        }

        // relationship_status: only set when empty OR when it is the default and a stronger signal exists
        $curRel = (string)($currentData['relationship_status'] ?? '');
        $relation = strtolower(trim((string)($familyMemberData['relation'] ?? '')));
        $isDefaultSingle = ($curRel === 'single');
        if (self::hasUserColumn('relationship_status') && (empty($curRel) || $isDefaultSingle)) {
            if (in_array($relation, ['spouse', 'wife', 'husband'], true)) {
                $candidate['relationship_status'] = 'married';
            } elseif (in_array($relation, ['girlfriend', 'boyfriend'], true)) {
                $candidate['relationship_status'] = 'in_relationship';
            }
        }

        if (empty($candidate)) {
            error_log('AUTOFILL[existing] trace=' . $trace . ' no candidate updates (likely fields already set or missing family data)');
            return false;
        }

        $result = self::updateUserColumns($userId, $candidate);
        if ($result) {
            error_log("Populated existing user {$userId} profile from family member data");
        } else {
            error_log('AUTOFILL[existing] trace=' . $trace . ' update returned false');
        }
        return $result;
    }

    /**
     * Check if an email exists in users or alternative_emails tables.
     * 
     * @param string $email Email to check
     * @param int|null $excludeUserId User ID to exclude (for updates)
     * @return array|null User data if found, null otherwise
     */
    public static function findByEmail(string $email, ?int $excludeUserId = null): ?array
    {
        $db = Database::getConnection();
        $email = strtolower(trim($email));

        // Check main users table
        $params = ['email' => $email];
        if ($excludeUserId) {
            $params['exclude'] = $excludeUserId;
        }

        $sql = "SELECT id, email, routina_id, display_name FROM users WHERE LOWER(email) = :email";
        if ($excludeUserId) {
            $sql .= " AND id != :exclude";
        }
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $user = $stmt->fetch();
        } catch (\PDOException $e) {
            // Backward-compatible: some deployments may not have routina_id
            $msg = strtolower($e->getMessage());
            if (strpos($msg, 'unknown column') !== false || strpos($msg, 'no such column') !== false || strpos($msg, 'does not exist') !== false || $e->getCode() === '42S22') {
                $sql = "SELECT id, email, display_name FROM users WHERE LOWER(email) = :email";
                if ($excludeUserId) {
                    $sql .= " AND id != :exclude";
                }
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $user = $stmt->fetch();
                if (is_array($user)) {
                    $user['routina_id'] = null;
                }
            } else {
                throw $e;
            }
        }
        
        if ($user) {
            return $user;
        }

        // Check alternative emails table (best-effort)
        try {
            $sql = "SELECT user_id, email FROM user_alternative_emails WHERE LOWER(email) = :email";
            if ($excludeUserId) {
                $sql .= " AND user_id != :exclude";
            }
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $alt = $stmt->fetch();
        } catch (\PDOException $e) {
            $alt = null;
        }

        if ($alt) {
            // Get the actual user
            try {
                $stmt = $db->prepare("SELECT id, email, routina_id, display_name FROM users WHERE id = :id");
                $stmt->execute(['id' => $alt['user_id']]);
                $u = $stmt->fetch();
            } catch (\PDOException $e) {
                $stmt = $db->prepare("SELECT id, email, display_name FROM users WHERE id = :id");
                $stmt->execute(['id' => $alt['user_id']]);
                $u = $stmt->fetch();
                if (is_array($u)) {
                    $u['routina_id'] = null;
                }
            }
            return $u ?: null;
        }

        return null;
    }

    /**
     * Check if a phone number is already registered.
     * 
     * @param string $phone Phone number to check
     * @param int|null $excludeUserId User ID to exclude
     * @return array|null User data if found
     */
    public static function findByPhone(string $phone, ?int $excludeUserId = null): ?array
    {
        $db = Database::getConnection();
        if (!self::hasUserColumn('phone')) {
            return null;
        }

        $phoneRaw = (string)$phone;
        $phone = preg_replace('/[^0-9+]/', '', $phoneRaw);

        if (!is_string($phone) || strlen($phone) < 7) {
            return null;
        }

        // 1) Prefer exact match (fast path)
        $sql = "SELECT id, email, phone, display_name FROM users WHERE phone = :phone";
        if ($excludeUserId) {
            $sql .= " AND id != :exclude";
        }
        $stmt = $db->prepare($sql);
        $params = ['phone' => $phone];
        if ($excludeUserId) {
            $params['exclude'] = $excludeUserId;
        }
        $stmt->execute($params);

        $user = $stmt->fetch();
        if ($user) {
            return $user;
        }

        // 2) Fallback: tolerant match on last-10 digits
        $needleKey = self::phoneMatchKey($phoneRaw);
        if ($needleKey === '') {
            return null;
        }

        $sql = "SELECT id, email, phone, display_name FROM users WHERE phone IS NOT NULL AND phone <> ''";
        if ($excludeUserId) {
            $sql .= " AND id != :exclude";
        }
        $stmt = $db->prepare($sql);
        $params = [];
        if ($excludeUserId) {
            $params['exclude'] = $excludeUserId;
        }
        $stmt->execute($params);

        foreach ($stmt->fetchAll() as $row) {
            $rowKey = self::phoneMatchKey((string)($row['phone'] ?? ''));
            if ($rowKey !== '' && $rowKey === $needleKey) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Check if a Routina ID is available.
     * 
     * @param string $routinaId The username part (without @routina.app)
     * @return bool True if available
     */
    public static function isRoutinaIdAvailable(string $routinaId): bool
    {
        $db = Database::getConnection();
        $routinaId = strtolower(trim($routinaId));
        
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE LOWER(routina_id) = :rid");
        $stmt->execute(['rid' => $routinaId]);
        
        return (int)$stmt->fetchColumn() === 0;
    }

    /**
     * Generate a suggested Routina ID from a name or email.
     * 
     * @param string $name Display name
     * @param string $email Email address
     * @return array Suggested IDs
     */
    public static function suggestRoutinaIds(string $name, string $email): array
    {
        $suggestions = [];
        
        // From name
        $nameBase = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
        if (strlen($nameBase) >= 3) {
            $suggestions[] = $nameBase;
            $suggestions[] = $nameBase . rand(10, 99);
        }

        // From email local part
        $emailParts = explode('@', $email);
        $emailBase = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $emailParts[0]));
        if (strlen($emailBase) >= 3 && $emailBase !== $nameBase) {
            $suggestions[] = $emailBase;
        }

        // Filter to available ones
        $available = [];
        foreach ($suggestions as $s) {
            if (self::isRoutinaIdAvailable($s)) {
                $available[] = $s;
            }
        }

        // If none available, add random suffixes
        if (empty($available) && !empty($nameBase)) {
            for ($i = 0; $i < 3; $i++) {
                $candidate = $nameBase . rand(100, 999);
                if (self::isRoutinaIdAvailable($candidate)) {
                    $available[] = $candidate;
                }
            }
        }

        return array_slice($available, 0, 5);
    }

    /**
     * Validate password strength.
     * 
     * @param string $password Password to validate
     * @return array ['valid' => bool, 'errors' => string[]]
     */
    public static function validatePassword(string $password): array
    {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain an uppercase letter';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain a lowercase letter';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain a number';
        }
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>_\-+=\[\]\\\\\/`~]/', $password)) {
            $errors[] = 'Password must contain a special character';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Create a password reset token.
     * 
     * @param string $email User's email
     * @return string|null Token if created, null if email not found
     */
    public static function createPasswordResetToken(string $email): ?string
    {
        $db = Database::getConnection();
        $email = strtolower(trim($email));

        // Check if user exists
        $stmt = $db->prepare("SELECT id FROM users WHERE LOWER(email) = :email");
        $stmt->execute(['email' => $email]);
        if (!$stmt->fetch()) {
            return null;
        }

        // Invalidate any existing tokens
        $stmt = $db->prepare("UPDATE password_resets SET used_at = CURRENT_TIMESTAMP WHERE email = :email AND used_at IS NULL");
        $stmt->execute(['email' => $email]);

        // Create new token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt = $db->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (:email, :token, :expires)");
        $stmt->execute([
            'email' => $email,
            'token' => hash('sha256', $token),
            'expires' => $expiresAt
        ]);

        return $token;
    }

    /**
     * Verify and consume a password reset token.
     * 
     * @param string $token Raw token from URL
     * @return string|null Email if valid, null otherwise
     */
    public static function verifyPasswordResetToken(string $token): ?string
    {
        $db = Database::getConnection();
        $hashedToken = hash('sha256', $token);

        $stmt = $db->prepare(
            "SELECT email FROM password_resets 
             WHERE token = :token 
             AND used_at IS NULL 
             AND expires_at > CURRENT_TIMESTAMP"
        );
        $stmt->execute(['token' => $hashedToken]);
        $row = $stmt->fetch();

        if ($row) {
            // Mark as used
            $stmt = $db->prepare("UPDATE password_resets SET used_at = CURRENT_TIMESTAMP WHERE token = :token");
            $stmt->execute(['token' => $hashedToken]);
            return $row['email'];
        }

        return null;
    }

    /**
     * Update user password.
     * 
     * @param string $email User email
     * @param string $newPassword New password
     * @return bool Success
     */
    public static function updatePassword(string $email, string $newPassword): bool
    {
        $db = Database::getConnection();
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt = $db->prepare("UPDATE users SET password = :pass WHERE LOWER(email) = :email");
        return $stmt->execute(['pass' => $hash, 'email' => strtolower($email)]);
    }

    /**
     * Find user by Google ID.
     * 
     * @param string $googleId Google user ID
     * @return array|null User data
     */
    public static function findByGoogleId(string $googleId): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE google_id = :gid");
        $stmt->execute(['gid' => $googleId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Create a user from Google OAuth.
     * 
     * @param array $googleUser Google user data
     * @return int|null New user ID
     */
    public static function createFromGoogle(array $googleUser): ?int
    {
        $db = Database::getConnection();
        
        $stmt = $db->prepare(
            "INSERT INTO users (email, google_id, display_name, email_verified_at, created_at) 
             VALUES (:email, :gid, :name, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)"
        );
        
        $success = $stmt->execute([
            'email' => $googleUser['email'],
            'gid' => $googleUser['id'],
            'name' => $googleUser['name'] ?? ''
        ]);

        if ($success) {
            return (int)$db->lastInsertId();
        }
        
        return null;
    }

    /**
     * Link a social account to a user.
     * 
     * @param int $userId User ID
     * @param string $provider Provider name (google, facebook, etc.)
     * @param string $providerId Provider's user ID
     * @param string|null $providerEmail Email from provider
     * @param string|null $profileUrl Profile URL
     * @return bool Success
     */
    public static function linkSocialAccount(
        int $userId,
        string $provider,
        string $providerId,
        ?string $providerEmail = null,
        ?string $profileUrl = null
    ): bool {
        $db = Database::getConnection();

        // Check if already linked to another user
        $stmt = $db->prepare(
            "SELECT user_id FROM user_social_accounts WHERE provider = :p AND provider_id = :pid"
        );
        $stmt->execute(['p' => $provider, 'pid' => $providerId]);
        $existing = $stmt->fetch();
        
        if ($existing && (int)$existing['user_id'] !== $userId) {
            return false; // Already linked to different user
        }

        if ($existing) {
            // Update existing link
            $stmt = $db->prepare(
                "UPDATE user_social_accounts 
                 SET provider_email = :email, profile_url = :url, linked_at = CURRENT_TIMESTAMP 
                 WHERE provider = :p AND provider_id = :pid"
            );
        } else {
            // Create new link
            $stmt = $db->prepare(
                "INSERT INTO user_social_accounts (user_id, provider, provider_id, provider_email, profile_url) 
                 VALUES (:uid, :p, :pid, :email, :url)"
            );
        }

        $params = [
            'p' => $provider,
            'pid' => $providerId,
            'email' => $providerEmail,
            'url' => $profileUrl
        ];
        
        if (!$existing) {
            $params['uid'] = $userId;
        }

        return $stmt->execute($params);
    }

    /**
     * Get user's linked social accounts.
     * 
     * @param int $userId User ID
     * @return array Social accounts
     */
    public static function getSocialAccounts(int $userId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "SELECT provider, provider_id, provider_email, profile_url, linked_at 
             FROM user_social_accounts WHERE user_id = :uid"
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Add an alternative email to user's profile.
     * 
     * @param int $userId User ID
     * @param string $email Alternative email
     * @return bool|string True on success, error message on failure
     */
    public static function addAlternativeEmail(int $userId, string $email): bool|string
    {
        $email = strtolower(trim($email));
        
        // Check if email exists anywhere
        $existing = self::findByEmail($email, $userId);
        if ($existing) {
            return 'This email is already associated with an account';
        }

        $db = Database::getConnection();
        
        try {
            $stmt = $db->prepare(
                "INSERT INTO user_alternative_emails (user_id, email) VALUES (:uid, :email)"
            );
            $stmt->execute(['uid' => $userId, 'email' => $email]);
            return true;
        } catch (\PDOException $e) {
            return 'Email already exists';
        }
    }

    /**
     * Get user's alternative emails.
     * 
     * @param int $userId User ID
     * @return array Alternative emails
     */
    public static function getAlternativeEmails(int $userId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "SELECT id, email, is_verified, verified_at, created_at 
             FROM user_alternative_emails WHERE user_id = :uid ORDER BY created_at"
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Remove an alternative email.
     * 
     * @param int $userId User ID
     * @param int $emailId Alternative email ID
     * @return bool Success
     */
    public static function removeAlternativeEmail(int $userId, int $emailId): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "DELETE FROM user_alternative_emails WHERE id = :id AND user_id = :uid"
        );
        return $stmt->execute(['id' => $emailId, 'uid' => $userId]);
    }

    /**
     * Generate TOTP secret for MFA setup.
     * 
     * @return string Base32-encoded secret
     */
    public static function generateMfaSecret(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 16; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }
        return $secret;
    }

    /**
     * Verify TOTP code.
     * 
     * @param string $secret Base32 secret
     * @param string $code 6-digit code
     * @return bool Valid
     */
    public static function verifyTotpCode(string $secret, string $code): bool
    {
        // Simple TOTP implementation (time-step 30 seconds, 6 digits)
        $timeStep = floor(time() / 30);
        
        // Check current and adjacent time steps for clock drift
        for ($i = -1; $i <= 1; $i++) {
            $expected = self::generateTotpCode($secret, $timeStep + $i);
            if (hash_equals($expected, $code)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Generate TOTP code for a time step.
     * 
     * @param string $secret Base32 secret
     * @param int $timeStep Time step
     * @return string 6-digit code
     */
    private static function generateTotpCode(string $secret, int $timeStep): string
    {
        // Decode base32
        $base32Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binary = '';
        foreach (str_split($secret) as $char) {
            $binary .= str_pad(decbin(strpos($base32Chars, strtoupper($char))), 5, '0', STR_PAD_LEFT);
        }
        $key = '';
        foreach (str_split($binary, 8) as $byte) {
            if (strlen($byte) === 8) {
                $key .= chr(bindec($byte));
            }
        }

        // HMAC-SHA1
        $time = pack('N*', 0, $timeStep);
        $hash = hash_hmac('sha1', $time, $key, true);
        
        // Dynamic truncation
        $offset = ord($hash[19]) & 0xf;
        $code = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;

        return str_pad((string)$code, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Enable MFA for a user.
     * 
     * @param int $userId User ID
     * @param string $secret TOTP secret
     * @return bool Success
     */
    public static function enableMfa(int $userId, string $secret): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE users SET mfa_enabled = TRUE, mfa_secret = :secret WHERE id = :id");
        return $stmt->execute(['secret' => $secret, 'id' => $userId]);
    }

    /**
     * Disable MFA for a user.
     * 
     * @param int $userId User ID
     * @return bool Success
     */
    public static function disableMfa(int $userId): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE users SET mfa_enabled = FALSE, mfa_secret = NULL WHERE id = :id");
        return $stmt->execute(['id' => $userId]);
    }

    /**
     * Get user's MFA status.
     * 
     * @param int $userId User ID
     * @return array ['enabled' => bool, 'secret' => string|null]
     */
    public static function getMfaStatus(int $userId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT mfa_enabled, mfa_secret FROM users WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch();
        
        return [
            'enabled' => (bool)($row['mfa_enabled'] ?? false),
            'secret' => $row['mfa_secret'] ?? null
        ];
    }
}
