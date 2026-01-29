<?php

namespace Routina\Models;

use Routina\Config\Database;

/**
 * Vehicle model for managing user vehicles.
 * 
 * Handles CRUD operations for vehicles including extended details
 * like trim, engine, transmission, and insurance information.
 */
class Vehicle
{
    /**
     * Get all vehicles for a user.
     *
     * @param int $userId User ID
     * @return array<int, array<string, mixed>> Array of vehicle records
     */
    public static function getAll(int $userId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM vehicles WHERE user_id = :uid");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Find a vehicle by ID for a user.
     *
     * @param int $userId User ID
     * @param int $id Vehicle ID
     * @return array<string, mixed>|false Vehicle record or false if not found
     */
    public static function find(int $userId, int $id): array|false
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM vehicles WHERE user_id = :uid AND id = :id");
        $stmt->execute(['uid' => $userId, 'id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Check if a vehicle exists for a user.
     *
     * @param int $userId User ID
     * @param int $id Vehicle ID
     * @return bool True if vehicle exists
     */
    public static function existsForUser(int $userId, int $id): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT 1 FROM vehicles WHERE user_id = :uid AND id = :id");
        $stmt->execute(['uid' => $userId, 'id' => $id]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Create a new vehicle.
     *
     * @param int $userId User ID
     * @param string $make Vehicle make
     * @param string $model Vehicle model
     * @param int $year Model year
     * @param string|null $plate License plate
     * @param array<string, mixed> $details Additional details (trim, engine, etc.)
     * @return bool True on success
     */
    public static function create(int $userId, string $make, string $model, int $year, ?string $plate, array $details = []): bool
    {
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

    /**
     * Update an existing vehicle.
     *
     * @param int $userId User ID
     * @param int $id Vehicle ID
     * @param string $make Vehicle make
     * @param string $model Vehicle model
     * @param int $year Model year
     * @param string|null $plate License plate
     * @param string $status Vehicle status (active, sold, scrapped)
     * @param array<string, mixed> $details Additional details (trim, engine, etc.)
     * @return bool True on success
     */
    public static function update(int $userId, int $id, string $make, string $model, int $year, ?string $plate, string $status, array $details = []): bool
    {
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
                insurance_notes = :insurance_notes,
                disposal_remarks = :disposal_remarks
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
            'insurance_notes' => $details['insurance_notes'] ?? null,
            'disposal_remarks' => $details['disposal_remarks'] ?? null
        ]);
    }

    /**
     * Delete a vehicle by ID for a user.
     *
     * @param int $userId User ID
     * @param int $id Vehicle ID
     * @return bool True on success
     */
    public static function delete(int $userId, int $id): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM vehicles WHERE id = :id AND user_id = :uid");
        return $stmt->execute(['id' => $id, 'uid' => $userId]);
    }
}
