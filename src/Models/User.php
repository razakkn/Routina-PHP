<?php

namespace Routina\Models;

use Routina\Config\Database;

class User {
    public $id;
    public $email;
    public $display_name;
    public $job_title;
    public $headline;
    public $phone;
    public $address;
    public $bio;
    public $linkedin;
    public $instagram;
    public $twitter;
    public $website;
    public $currency;
    public $spouse_count;
    public $avatar_image_url;
    public $avatar_preset_key;
    public $dob;
    public $gender;
    public $country_of_origin;
    public $current_location;
    public $relationship_status;
    public $partner_member_id;

    public function __construct($data = []) {
        $this->id = $data['id'] ?? null;
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
        $this->spouse_count = $data['spouse_count'] ?? 0;
        $this->avatar_image_url = $data['avatar_image_url'] ?? null;
        $this->avatar_preset_key = $data['avatar_preset_key'] ?? null;
        $this->dob = $data['dob'] ?? null;
        $this->gender = $data['gender'] ?? null;
        $this->country_of_origin = $data['country_of_origin'] ?? null;
        $this->current_location = $data['current_location'] ?? null;
        $this->relationship_status = $data['relationship_status'] ?? 'single';
        $this->partner_member_id = $data['partner_member_id'] ?? null;
    }

    public static function find($id) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();
        
        if ($data) {
            return new self($data);
        }
        return null;
    }

    public function save() {
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
}
