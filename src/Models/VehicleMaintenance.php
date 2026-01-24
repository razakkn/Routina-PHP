<?php

namespace Routina\Models;

use Routina\Config\Database;

class VehicleMaintenance {
    public static function getAll($userId, $vehicleId = null) {
        $db = Database::getConnection();
        if ($vehicleId) {
            $stmt = $db->prepare("SELECT * FROM vehicle_maintenance WHERE user_id = :uid AND vehicle_id = :vid ORDER BY due_date ASC");
            $stmt->execute(['uid' => $userId, 'vid' => $vehicleId]);
        } else {
            $stmt = $db->prepare("SELECT * FROM vehicle_maintenance WHERE user_id = :uid ORDER BY due_date ASC");
            $stmt->execute(['uid' => $userId]);
        }
        return $stmt->fetchAll();
    }

    public static function create($userId, $vehicleId, $title, $status, $dueDate, $notes) {
        $db = Database::getConnection();

        if (!Vehicle::existsForUser($userId, (int)$vehicleId)) {
            return false;
        }

        $stmt = $db->prepare("INSERT INTO vehicle_maintenance (user_id, vehicle_id, title, status, due_date, notes) VALUES (:uid, :vid, :title, :status, :due, :notes)");
        return $stmt->execute([
            'uid' => $userId,
            'vid' => $vehicleId,
            'title' => $title,
            'status' => $status,
            'due' => $dueDate,
            'notes' => $notes
        ]);
    }
}
