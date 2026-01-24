<?php

namespace Routina\Models;

use Routina\Config\Database;

class VehicleEvent {
    public static function getAll($userId, $vehicleId = null) {
        $db = Database::getConnection();
        if ($vehicleId) {
            $stmt = $db->prepare("SELECT * FROM vehicle_events WHERE user_id = :uid AND vehicle_id = :vid ORDER BY event_date DESC");
            $stmt->execute(['uid' => $userId, 'vid' => $vehicleId]);
        } else {
            $stmt = $db->prepare("SELECT * FROM vehicle_events WHERE user_id = :uid ORDER BY event_date DESC");
            $stmt->execute(['uid' => $userId]);
        }
        return $stmt->fetchAll();
    }

    public static function create($userId, $vehicleId, $type, $date, $notes) {
        $db = Database::getConnection();

        if (!Vehicle::existsForUser($userId, (int)$vehicleId)) {
            return false;
        }

        $stmt = $db->prepare("INSERT INTO vehicle_events (user_id, vehicle_id, event_type, event_date, notes) VALUES (:uid, :vid, :type, :date, :notes)");
        return $stmt->execute([
            'uid' => $userId,
            'vid' => $vehicleId,
            'type' => $type,
            'date' => $date,
            'notes' => $notes
        ]);
    }
}
