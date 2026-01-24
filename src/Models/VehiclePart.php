<?php

namespace Routina\Models;

use Routina\Config\Database;

class VehiclePart {
    public static function getAll($userId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT p.*, v.name AS vendor_name FROM vehicle_parts p LEFT JOIN vehicle_vendors v ON p.vendor_id = v.id AND v.user_id = p.user_id WHERE p.user_id = :uid ORDER BY p.name ASC");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public static function create($userId, $name, $partNumber, $vendorId, $cost) {
        $db = Database::getConnection();

        if ($vendorId !== null && !VehicleVendor::existsForUser($userId, (int)$vendorId)) {
            return false;
        }

        $stmt = $db->prepare("INSERT INTO vehicle_parts (user_id, name, part_number, vendor_id, cost) VALUES (:uid, :name, :part, :vendor, :cost)");
        return $stmt->execute([
            'uid' => $userId,
            'name' => $name,
            'part' => $partNumber,
            'vendor' => $vendorId,
            'cost' => $cost
        ]);
    }
}
