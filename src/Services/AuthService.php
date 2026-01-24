<?php

namespace Routina\Services;

use Routina\Config\Database;

/**
 * Authentication service for user registration, login, and identity management.
 */
class AuthService
{
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
        $sql = "SELECT id, email, routina_id, display_name FROM users WHERE LOWER(email) = :email";
        if ($excludeUserId) {
            $sql .= " AND id != :exclude";
        }
        $stmt = $db->prepare($sql);
        $params = ['email' => $email];
        if ($excludeUserId) {
            $params['exclude'] = $excludeUserId;
        }
        $stmt->execute($params);
        $user = $stmt->fetch();
        
        if ($user) {
            return $user;
        }

        // Check alternative emails table
        $sql = "SELECT user_id, email FROM user_alternative_emails WHERE LOWER(email) = :email";
        if ($excludeUserId) {
            $sql .= " AND user_id != :exclude";
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $alt = $stmt->fetch();

        if ($alt) {
            // Get the actual user
            $stmt = $db->prepare("SELECT id, email, routina_id, display_name FROM users WHERE id = :id");
            $stmt->execute(['id' => $alt['user_id']]);
            return $stmt->fetch() ?: null;
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
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        if (strlen($phone) < 7) {
            return null;
        }

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
        
        return $stmt->fetch() ?: null;
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
