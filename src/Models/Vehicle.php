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

    public static function create($userId, $make, $model, $year, $plate, array $details = []) {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO vehicles (
                user_id, make, model, year, license_plate,
                trim, engine, transmission, fuel_type, drivetrain, color,
                owned_date, registration_date, registration_expiry,
                insurance_provider, insurance_policy_number, insurance_start_date, insurance_end_date, insurance_notes
            ) VALUES (
                :uid, :make, :model, :year, :plate,
                :trim, :engine, :transmission, :fuel_type, :drivetrain, :color,
                :owned_date, :registration_date, :registration_expiry,
                :insurance_provider, :insurance_policy_number, :insurance_start_date, :insurance_end_date, :insurance_notes
            )");
        return $stmt->execute([
            'uid' => $userId, 
            'make' => $make, 
            'model' => $model, 
            'year' => $year, 
            'plate' => $plate,
            'trim' => $details['trim'] ?? null,
            'engine' => $details['engine'] ?? null,
            'transmission' => $details['transmission'] ?? null,
            'fuel_type' => $details['fuel_type'] ?? null,
            'drivetrain' => $details['drivetrain'] ?? null,
            'color' => $details['color'] ?? null,
            'owned_date' => $details['owned_date'] ?? null,
            'registration_date' => $details['registration_date'] ?? null,
            'registration_expiry' => $details['registration_expiry'] ?? null,
            'insurance_provider' => $details['insurance_provider'] ?? null,
            'insurance_policy_number' => $details['insurance_policy_number'] ?? null,
            'insurance_start_date' => $details['insurance_start_date'] ?? null,
            'insurance_end_date' => $details['insurance_end_date'] ?? null,
            'insurance_notes' => $details['insurance_notes'] ?? null
        ]);
    }

    public static function update($userId, $id, $make, $model, $year, $plate, $status, array $details = []) {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE vehicles SET
                make = :make,
                model = :model,
                year = :year,
                license_plate = :plate,
                status = :status,
                trim = :trim,
                engine = :engine,
                transmission = :transmission,
                fuel_type = :fuel_type,
                drivetrain = :drivetrain,
                color = :color,
                owned_date = :owned_date,
                registration_date = :registration_date,
                registration_expiry = :registration_expiry,
                insurance_provider = :insurance_provider,
                insurance_policy_number = :insurance_policy_number,
                insurance_start_date = :insurance_start_date,
                insurance_end_date = :insurance_end_date,
                insurance_notes = :insurance_notes
            WHERE id = :id AND user_id = :uid");
        return $stmt->execute([
            'uid' => $userId,
            'id' => $id,
            'make' => $make,
            'model' => $model,
            'year' => $year,
            'plate' => $plate,
            'status' => $status,
            'trim' => $details['trim'] ?? null,
            'engine' => $details['engine'] ?? null,
            'transmission' => $details['transmission'] ?? null,
            'fuel_type' => $details['fuel_type'] ?? null,
            'drivetrain' => $details['drivetrain'] ?? null,
            'color' => $details['color'] ?? null,
            'owned_date' => $details['owned_date'] ?? null,
            'registration_date' => $details['registration_date'] ?? null,
            'registration_expiry' => $details['registration_expiry'] ?? null,
            'insurance_provider' => $details['insurance_provider'] ?? null,
            'insurance_policy_number' => $details['insurance_policy_number'] ?? null,
            'insurance_start_date' => $details['insurance_start_date'] ?? null,
            'insurance_end_date' => $details['insurance_end_date'] ?? null,
            'insurance_notes' => $details['insurance_notes'] ?? null
        ]);
    }
}
