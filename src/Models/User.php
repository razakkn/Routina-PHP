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
    public string $relationship_status = 'single';
    public ?int $partner_member_id = null;

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
        $this->relationship_status = $data['relationship_status'] ?? 'single';
        $this->partner_member_id = isset($data['partner_member_id']) ? (int)$data['partner_member_id'] : null;
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
                relationship_status = :rel_status,
                partner_member_id = :partner_id
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
                'rel_status' => $this->relationship_status,
                'partner_id' => $this->partner_member_id,
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
}
