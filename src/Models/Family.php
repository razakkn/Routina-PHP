<?php

namespace Routina\Models;

use Routina\Config\Database;

class Family {
    public static function getAll($userId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM family_members WHERE user_id = :uid ORDER BY birthdate ASC");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public static function create($userId, $name, $relation, $bday) {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO family_members (user_id, name, relation, birthdate) VALUES (:uid, :name, :rel, :bday)");
        return $stmt->execute([
            'uid' => $userId, 
            'name' => $name, 
            'rel' => $relation, 
            'bday' => $bday
        ]);
    }
}
