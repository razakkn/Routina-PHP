<?php

namespace Routina\Models;

use Routina\Config\Database;

class VehicleVendor {
    public static function getAll($userId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM vehicle_vendors WHERE user_id = :uid ORDER BY name ASC");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public static function create($userId, $name, $phone, $email, $notes) {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO vehicle_vendors (user_id, name, phone, email, notes) VALUES (:uid, :name, :phone, :email, :notes)");
        return $stmt->execute([
            'uid' => $userId,
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'notes' => $notes
        ]);
    }

    public static function existsForUser($userId, $id) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT 1 FROM vehicle_vendors WHERE user_id = :uid AND id = :id");
        $stmt->execute(['uid' => $userId, 'id' => $id]);
        return (bool)$stmt->fetchColumn();
    }
}
