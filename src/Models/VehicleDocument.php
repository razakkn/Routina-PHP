<?php

namespace Routina\Models;

use Routina\Config\Database;

class VehicleDocument {
    public static function getAll($userId, $vehicleId = null) {
        $db = Database::getConnection();
        if ($vehicleId) {
            $stmt = $db->prepare("SELECT * FROM vehicle_documents WHERE user_id = :uid AND vehicle_id = :vid ORDER BY uploaded_at DESC");
            $stmt->execute(['uid' => $userId, 'vid' => $vehicleId]);
        } else {
            $stmt = $db->prepare("SELECT * FROM vehicle_documents WHERE user_id = :uid ORDER BY uploaded_at DESC");
            $stmt->execute(['uid' => $userId]);
        }
        return $stmt->fetchAll();
    }

    public static function create($userId, $vehicleId, $title, $fileUrl) {
        $db = Database::getConnection();

        if (!Vehicle::existsForUser($userId, (int)$vehicleId)) {
            return false;
        }

        $stmt = $db->prepare("INSERT INTO vehicle_documents (user_id, vehicle_id, title, file_url, uploaded_at) VALUES (:uid, :vid, :title, :url, :uploaded)");
        return $stmt->execute([
            'uid' => $userId,
            'vid' => $vehicleId,
            'title' => $title,
            'url' => $fileUrl,
            'uploaded' => date('Y-m-d H:i:s')
        ]);
    }
}
