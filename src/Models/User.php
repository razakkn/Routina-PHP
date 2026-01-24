<?php

namespace Routina\Models;

use Routina\Config\Database;

class User {
    public $id;
    public $email;
    public $display_name;
    public $job_title;
    public $phone;
    public $address;
    public $bio;
    public $currency;
    public $spouse_count;
    public $avatar_image_url;
    public $avatar_preset_key;
    public $dob;
    public $gender;
    public $country_of_origin;
    public $current_location;

    public function __construct($data = []) {
        $this->id = $data['id'] ?? null;
        $this->email = $data['email'] ?? null;
        $this->display_name = $data['display_name'] ?? null;
        $this->job_title = $data['job_title'] ?? null;
        $this->phone = $data['phone'] ?? null;
        $this->address = $data['address'] ?? null;
        $this->bio = $data['bio'] ?? null;
        $this->currency = $data['currency'] ?? 'USD';
        $this->spouse_count = $data['spouse_count'] ?? 0;
        $this->avatar_image_url = $data['avatar_image_url'] ?? null;
        $this->avatar_preset_key = $data['avatar_preset_key'] ?? null;
        $this->dob = $data['dob'] ?? null;
        $this->gender = $data['gender'] ?? null;
        $this->country_of_origin = $data['country_of_origin'] ?? null;
        $this->current_location = $data['current_location'] ?? null;
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
                phone = :phone,
                address = :address,
                bio = :bio,
                currency = :curr, 
                spouse_count = :spouse, 
                avatar_image_url = :avatar, 
                avatar_preset_key = :preset,
                dob = :dob,
                gender = :gender,
                country_of_origin = :country,
                current_location = :location 
                WHERE id = :id");
            
            return $stmt->execute([
                'name' => $this->display_name,
                'job' => $this->job_title,
                'phone' => $this->phone,
                'address' => $this->address,
                'bio' => $this->bio,
                'curr' => $this->currency,
                'spouse' => $this->spouse_count,
                'avatar' => $this->avatar_image_url,
                'preset' => $this->avatar_preset_key,
                'dob' => $this->dob,
                'gender' => $this->gender,
                'country' => $this->country_of_origin,
                'location' => $this->current_location,
                'id' => $this->id
            ]);
        }
        return false; 
    }
}
