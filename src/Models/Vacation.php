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

    public static function create($userId, $destination, $start, $end, $status, $budget = null, $notes = null) {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO vacations (user_id, destination, start_date, end_date, status, budget, notes)
            VALUES (:uid, :dest, :start, :end, :status, :budget, :notes)");
        return $stmt->execute([
            'uid' => $userId, 
            'dest' => $destination, 
            'start' => $start, 
            'end' => $end, 
            'status' => $status,
            'budget' => $budget,
            'notes' => $notes
        ]);
    }

    public static function find($userId, $id) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM vacations WHERE user_id = :uid AND id = :id");
        $stmt->execute(['uid' => $userId, 'id' => $id]);
        return $stmt->fetch();
    }

    public static function update($userId, $id, $destination, $start, $end, $status, $budget = null, $notes = null) {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE vacations SET destination = :dest, start_date = :start, end_date = :end, status = :status, budget = :budget, notes = :notes WHERE id = :id AND user_id = :uid");
        return $stmt->execute([
            'uid' => $userId,
            'id' => $id,
            'dest' => $destination,
            'start' => $start,
            'end' => $end,
            'status' => $status,
            'budget' => $budget,
            'notes' => $notes
        ]);
    }
}
