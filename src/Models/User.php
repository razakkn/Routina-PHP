<?php

namespace Routina\Models;

use Routina\Config\Database;

/**
 * User model for managing user accounts and profiles.
 * 
 * Handles user authentication, profile management, and preferences.
 */
class User
{
    public ?int $id = null;
    public ?string $email = null;
    public ?string $display_name = null;
    public ?string $job_title = null;
    public ?string $headline = null;
    public ?string $phone = null;
    public ?string $address = null;
    public ?string $bio = null;
    public ?string $linkedin = null;
    public ?string $instagram = null;
    public ?string $twitter = null;
    public ?string $website = null;
    public string $currency = 'USD';
    public int $spouse_count = 0;
    public ?string $avatar_image_url = null;
    public ?string $avatar_preset_key = null;
    public ?string $dob = null;
    public ?string $gender = null;
    public ?string $country_of_origin = null;
    public ?string $current_location = null;
    public ?string $holiday_country = null;
    public string $relationship_status = 'single';
    public ?int $partner_member_id = null;
    public ?string $family_relation = null;
    public bool $share_profile_publicly = false;

    /**
     * Create a new User instance from data array.
     *
     * @param array<string, mixed> $data User data
     */
    public function __construct(array $data = [])
    {
        $this->id = isset($data['id']) ? (int)$data['id'] : null;
        $this->email = $data['email'] ?? null;
        $this->display_name = $data['display_name'] ?? null;
        $this->job_title = $data['job_title'] ?? null;
        $this->headline = $data['headline'] ?? null;
        $this->phone = $data['phone'] ?? null;
        $this->address = $data['address'] ?? null;
        $this->bio = $data['bio'] ?? null;
        $this->linkedin = $data['linkedin'] ?? null;
        $this->instagram = $data['instagram'] ?? null;
        $this->twitter = $data['twitter'] ?? null;
        $this->website = $data['website'] ?? null;
        $this->currency = $data['currency'] ?? 'USD';
        $this->spouse_count = (int)($data['spouse_count'] ?? 0);
        $this->avatar_image_url = $data['avatar_image_url'] ?? null;
        $this->avatar_preset_key = $data['avatar_preset_key'] ?? null;
        $this->dob = $data['dob'] ?? null;
        $this->gender = $data['gender'] ?? null;
        $this->country_of_origin = $data['country_of_origin'] ?? null;
        $this->current_location = $data['current_location'] ?? null;
        $this->holiday_country = $data['holiday_country'] ?? null;
        $this->relationship_status = $data['relationship_status'] ?? 'single';
        $this->partner_member_id = isset($data['partner_member_id']) ? (int)$data['partner_member_id'] : null;
        $this->family_relation = $data['family_relation'] ?? null;
        $this->share_profile_publicly = !empty($data['share_profile_publicly']) ? true : false;
    }

    /**
     * Find a user by ID.
     *
     * @param int $id User ID
     * @return self|null User instance or null if not found
     */
    public static function find(int $id): ?self
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();
        
        if ($data) {
            return new self($data);
        }
        return null;
    }

    /**
     * Find a user by email address.
     *
     * @param string $email Email address
     * @return self|null User instance or null if not found
     */
    public static function findByEmail(string $email): ?self
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $data = $stmt->fetch();
        
        if ($data) {
            return new self($data);
        }
        return null;
    }

    /**
     * Save the user record (update only).
     *
     * @return bool True on success
     */
    public function save(): bool
    {
        $db = Database::getConnection();
        if ($this->id) {
            $stmt = $db->prepare("UPDATE users SET 
                display_name = :name, 
                job_title = :job,
                headline = :headline,
                phone = :phone,
                address = :address,
                bio = :bio,
                linkedin = :linkedin,
                instagram = :instagram,
                twitter = :twitter,
                website = :website,
                currency = :curr, 
                spouse_count = :spouse, 
                avatar_image_url = :avatar, 
                avatar_preset_key = :preset,
                dob = :dob,
                gender = :gender,
                country_of_origin = :country,
                current_location = :location,
                holiday_country = :holiday_country,
                relationship_status = :rel_status,
                partner_member_id = :partner_id,
                share_profile_publicly = :share_profile_publicly
                WHERE id = :id");
            
            return $stmt->execute([
                'name' => $this->display_name,
                'job' => $this->job_title,
                'headline' => $this->headline,
                'phone' => $this->phone,
                'address' => $this->address,
                'bio' => $this->bio,
                'linkedin' => $this->linkedin,
                'instagram' => $this->instagram,
                'twitter' => $this->twitter,
                'website' => $this->website,
                'curr' => $this->currency,
                'spouse' => $this->spouse_count,
                'avatar' => $this->avatar_image_url,
                'preset' => $this->avatar_preset_key,
                'dob' => $this->dob,
                'gender' => $this->gender,
                'country' => $this->country_of_origin,
                'location' => $this->current_location,
                'holiday_country' => $this->holiday_country,
                'rel_status' => $this->relationship_status,
                'partner_id' => $this->partner_member_id,
                'share_profile_publicly' => $this->share_profile_publicly ? 1 : 0,
                'id' => $this->id
            ]);
        }
        return false; 
    }

    /**
     * Get the display name or fallback to email.
     *
     * @return string Display name or email
     */
    public function getDisplayName(): string
    {
        return $this->display_name ?: $this->email ?: 'User';
    }

    /**
     * Check if user has a custom avatar.
     *
     * @return bool True if user has avatar
     */
    public function hasAvatar(): bool
    {
        return !empty($this->avatar_image_url) || !empty($this->avatar_preset_key);
    }

    /**
     * Permanently delete user account and all associated data.
     * This is a destructive operation that removes all user data from the system.
     * 
     * @param int $userId User ID to delete
     * @return bool True on success
     */
    public static function deleteAccount(int $userId, ?string &$errorMessage = null): bool
    {
        $db = Database::getConnection();
        $errorMessage = null;
        
        // Helper function to safely execute delete - ignores table not found errors
        $safeDelete = function ($sql, $params = []) use ($db) {
            try {
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
            } catch (\PDOException $e) {
                // Ignore "table doesn't exist" errors (MySQL: 1146, SQLite: no such table)
                $code = $e->getCode();
                $msg = strtolower($e->getMessage());
                $isMissingTable = (strpos($msg, 'no such table') !== false)
                    || (strpos($msg, "doesn't exist") !== false)
                    || (strpos($msg, 'does not exist') !== false)
                    || ($code === '42S02')
                    || ($code === '42P01');

                // Also ignore missing/unknown columns for best-effort cleanup
                // MySQL: 1054 Unknown column
                // SQLite: no such column
                // SQLSTATE: 42S22 (Column not found)
                $isMissingColumn = (strpos($msg, 'unknown column') !== false)
                    || (strpos($msg, 'no such column') !== false)
                    || ($code === '42S22');

                if (!$isMissingTable && !$isMissingColumn) {
                    throw $e; // Re-throw if it's a different error
                }

                // Log but continue for schema drift
                if ($isMissingTable) {
                    error_log("Table not found during account deletion (skipping): " . $e->getMessage());
                } else {
                    error_log("Column not found during account deletion (skipping): " . $e->getMessage());
                }
            }
        };
        
        try {
            $db->beginTransaction();

            // Best-effort: disable FK checks for this session during deletion.
            // Helps in shared-host setups where schema drift leaves orphan rows.
            $driver = '';
            try {
                $driver = (string)$db->getAttribute(\PDO::ATTR_DRIVER_NAME);
            } catch (\Throwable $e) {
                $driver = '';
            }
            if ($driver === 'mysql') {
                try { $db->exec('SET FOREIGN_KEY_CHECKS=0'); } catch (\Throwable $e) { /* ignore */ }
            } elseif ($driver === 'sqlite') {
                try { $db->exec('PRAGMA foreign_keys=OFF'); } catch (\Throwable $e) { /* ignore */ }
            }

            // Delete all user data from related tables
            // Order matters - delete child records before parent records
            
            // Vehicle related data (tables have user_id directly)
            $safeDelete("DELETE FROM vehicle_maintenance WHERE user_id = :uid", ['uid' => $userId]);
            $safeDelete("DELETE FROM vehicle_documents WHERE user_id = :uid", ['uid' => $userId]);
            $safeDelete("DELETE FROM vehicle_events WHERE user_id = :uid", ['uid' => $userId]);
            $safeDelete("DELETE FROM vehicle_parts WHERE user_id = :uid", ['uid' => $userId]);
            $safeDelete("DELETE FROM vehicle_plans WHERE user_id = :uid", ['uid' => $userId]);
            $safeDelete("DELETE FROM vehicle_vendors WHERE user_id = :uid", ['uid' => $userId]);
            $safeDelete("DELETE FROM vehicles WHERE user_id = :uid", ['uid' => $userId]);
            
            // Vacation related data (tables have user_id directly)
            $safeDelete("DELETE FROM vacation_checklist_items WHERE user_id = :uid", ['uid' => $userId]);
            $safeDelete("DELETE FROM vacation_notes WHERE user_id = :uid", ['uid' => $userId]);
            $safeDelete("DELETE FROM vacations WHERE user_id = :uid", ['uid' => $userId]);
            
            // Finance related data (fix: table is finance_income not finance_incomes)
            $safeDelete("DELETE FROM finance_bills WHERE user_id = :uid", ['uid' => $userId]);
            $safeDelete("DELETE FROM finance_income WHERE user_id = :uid", ['uid' => $userId]);
            $safeDelete("DELETE FROM finance_diary WHERE user_id = :uid", ['uid' => $userId]);
            $safeDelete("DELETE FROM finance_budgets WHERE user_id = :uid", ['uid' => $userId]);
            $safeDelete("DELETE FROM finance_assets WHERE user_id = :uid", ['uid' => $userId]);
            $safeDelete("DELETE FROM finance_savings WHERE user_id = :uid", ['uid' => $userId]);
            $safeDelete("DELETE FROM finance_reflections WHERE user_id = :uid", ['uid' => $userId]);
            $safeDelete("DELETE FROM finance_debts WHERE user_id = :uid", ['uid' => $userId]);
            $safeDelete("DELETE FROM transactions WHERE user_id = :uid", ['uid' => $userId]);
            
            // Health data (fix: table is health_entries not health_records)
            $safeDelete("DELETE FROM health_entries WHERE user_id = :uid", ['uid' => $userId]);
            
            // Journal entries
            $safeDelete("DELETE FROM journal_entries WHERE user_id = :uid", ['uid' => $userId]);
            
            // Calendar events
            $safeDelete("DELETE FROM calendar_events WHERE user_id = :uid", ['uid' => $userId]);
            
            // Family members
            $safeDelete("DELETE FROM family_members WHERE user_id = :uid", ['uid' => $userId]);
            
            // Home tasks
            $safeDelete("DELETE FROM home_tasks WHERE user_id = :uid", ['uid' => $userId]);
            
            // Buzz requests (both sent and received) - use separate params for PDO compatibility
            $safeDelete("DELETE FROM buzz_requests WHERE from_user_id = :uid1 OR to_user_id = :uid2", ['uid1' => $userId, 'uid2' => $userId]);
            
            // User memories/photos
            $safeDelete("DELETE FROM user_memories WHERE user_id = :uid", ['uid' => $userId]);
            
            // Social accounts
            $safeDelete("DELETE FROM user_social_accounts WHERE user_id = :uid", ['uid' => $userId]);
            
            // Alternative emails
            $safeDelete("DELETE FROM user_alternative_emails WHERE user_id = :uid", ['uid' => $userId]);
            
            // Password resets (by user email)
            $stmt = $db->prepare("SELECT email FROM users WHERE id = :uid");
            $stmt->execute(['uid' => $userId]);
            $userData = $stmt->fetch();
            if ($userData && !empty($userData['email'])) {
                $safeDelete("DELETE FROM password_resets WHERE email = :email", ['email' => $userData['email']]);
            }
            
            // Finally, delete the user - this one must succeed
            $stmt = $db->prepare("DELETE FROM users WHERE id = :uid");
            $stmt->execute(['uid' => $userId]);
            if ((int)$stmt->rowCount() !== 1) {
                throw new \RuntimeException('User row was not deleted (user missing or deletion blocked).');
            }

            // Re-enable FK checks (best-effort)
            if ($driver === 'mysql') {
                try { $db->exec('SET FOREIGN_KEY_CHECKS=1'); } catch (\Throwable $e) { /* ignore */ }
            } elseif ($driver === 'sqlite') {
                try { $db->exec('PRAGMA foreign_keys=ON'); } catch (\Throwable $e) { /* ignore */ }
            }
            
            $db->commit();
            return true;
            
        } catch (\Throwable $e) {
            // Re-enable FK checks (best-effort)
            try {
                $driver = (string)$db->getAttribute(\PDO::ATTR_DRIVER_NAME);
                if ($driver === 'mysql') {
                    $db->exec('SET FOREIGN_KEY_CHECKS=1');
                } elseif ($driver === 'sqlite') {
                    $db->exec('PRAGMA foreign_keys=ON');
                }
            } catch (\Throwable $ignored) { /* ignore */ }
            $db->rollBack();
            $errorMessage = $e->getMessage();
            error_log("Failed to delete user account {$userId}: " . $e->getMessage());
            return false;
        }
    }
}
