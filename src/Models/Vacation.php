<?php

namespace Routina\Models;

use Routina\Config\Database;

class Vacation {
    public static function getAll($userId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM vacations WHERE user_id = :uid ORDER BY start_date ASC");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public static function create($userId, $destination, $start, $end, $status) {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO vacations (user_id, destination, start_date, end_date, status) VALUES (:uid, :dest, :start, :end, :status)");
        return $stmt->execute([
            'uid' => $userId, 
            'dest' => $destination, 
            'start' => $start, 
            'end' => $end, 
            'status' => $status
        ]);
    }

    public static function find($userId, $id) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM vacations WHERE user_id = :uid AND id = :id");
        $stmt->execute(['uid' => $userId, 'id' => $id]);
        return $stmt->fetch();
    }
}
