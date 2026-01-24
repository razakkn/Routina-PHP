<?php

namespace Routina\Models;

use Routina\Config\Database;

class Vehicle {
    public static function getAll($userId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM vehicles WHERE user_id = :uid");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public static function find($userId, $id) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM vehicles WHERE user_id = :uid AND id = :id");
        $stmt->execute(['uid' => $userId, 'id' => $id]);
        return $stmt->fetch();
    }

    public static function existsForUser($userId, $id) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT 1 FROM vehicles WHERE user_id = :uid AND id = :id");
        $stmt->execute(['uid' => $userId, 'id' => $id]);
        return (bool)$stmt->fetchColumn();
    }

    public static function create($userId, $make, $model, $year, $plate) {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO vehicles (user_id, make, model, year, license_plate) VALUES (:uid, :make, :model, :year, :plate)");
        return $stmt->execute([
            'uid' => $userId, 
            'make' => $make, 
            'model' => $model, 
            'year' => $year, 
            'plate' => $plate
        ]);
    }

    public static function update($userId, $id, $make, $model, $year, $plate, $status) {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE vehicles SET make = :make, model = :model, year = :year, license_plate = :plate, status = :status WHERE id = :id AND user_id = :uid");
        return $stmt->execute([
            'uid' => $userId,
            'id' => $id,
            'make' => $make,
            'model' => $model,
            'year' => $year,
            'plate' => $plate,
            'status' => $status
        ]);
    }
}
