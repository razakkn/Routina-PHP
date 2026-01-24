<?php

namespace Routina\Models;

use Routina\Config\Database;

class Health {
    public static function getAll($userId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM health_entries WHERE user_id = :uid ORDER BY entry_date DESC LIMIT 30");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public static function create($userId, $date, $weight, $steps, $sleep, $water) {
        $db = Database::getConnection();
        // Check if entry exists for date
        $check = $db->prepare("SELECT id FROM health_entries WHERE user_id = :uid AND entry_date = :date");
        $check->execute(['uid' => $userId, 'date' => $date]);
        
        if ($check->fetch()) {
            $stmt = $db->prepare("UPDATE health_entries SET weight = :w, steps = :s, sleep_hours = :sl, water_glasses = :wa WHERE user_id = :uid AND entry_date = :date");
        } else {
            $stmt = $db->prepare("INSERT INTO health_entries (user_id, entry_date, weight, steps, sleep_hours, water_glasses) VALUES (:uid, :date, :w, :s, :sl, :wa)");
        }
        
        return $stmt->execute([
            'uid' => $userId, 
            'date' => $date, 
            'w' => $weight,
            's' => $steps,
            'sl' => $sleep,
            'wa' => $water
        ]);
    }
}
