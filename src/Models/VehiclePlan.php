<?php

namespace Routina\Models;

use Routina\Config\Database;

class VehiclePlan {
    public static function getAll($userId, $vehicleId = null) {
        $db = Database::getConnection();
        if ($vehicleId) {
            $stmt = $db->prepare("SELECT * FROM vehicle_plans WHERE user_id = :uid AND vehicle_id = :vid ORDER BY id DESC");
            $stmt->execute(['uid' => $userId, 'vid' => $vehicleId]);
        } else {
            $stmt = $db->prepare("SELECT * FROM vehicle_plans WHERE user_id = :uid ORDER BY id DESC");
            $stmt->execute(['uid' => $userId]);
        }
        return $stmt->fetchAll();
    }

    public static function create($userId, $vehicleId, $title, $status, $notes) {
        $db = Database::getConnection();

        if (!Vehicle::existsForUser($userId, (int)$vehicleId)) {
            return false;
        }

        $stmt = $db->prepare("INSERT INTO vehicle_plans (user_id, vehicle_id, title, status, notes) VALUES (:uid, :vid, :title, :status, :notes)");
        return $stmt->execute([
            'uid' => $userId,
            'vid' => $vehicleId,
            'title' => $title,
            'status' => $status,
            'notes' => $notes
        ]);
    }
}
