<?php

namespace Routina\Models;

use Routina\Config\Database;

/**
 * Vacation model for managing trip planning.
 * 
 * Handles CRUD operations for vacation/trip records including
 * budget tracking, status management, and notes.
 */
class Vacation
{
    /**
     * Get all vacations for a user ordered by start date.
     *
     * @param int $userId User ID
     * @return array<int, array<string, mixed>> Array of vacation records
     */
    public static function getAll(int $userId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM vacations WHERE user_id = :uid ORDER BY start_date ASC");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Create a new vacation record.
     *
     * @param int $userId User ID
     * @param string $destination Destination name/location
     * @param string|null $start Start date (Y-m-d)
     * @param string|null $end End date (Y-m-d)
     * @param string $status Status (idea, planned, booked, completed)
     * @param float|null $budget Budget amount
     * @param string|null $notes Additional notes
     * @return bool True on success
     */
    public static function create(
        int $userId,
        string $destination,
        ?string $start,
        ?string $end,
        string $status,
        ?float $budget = null,
        ?string $notes = null
    ): bool {
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

    /**
     * Find a vacation by ID for a user.
     *
     * @param int $userId User ID
     * @param int $id Vacation ID
     * @return array<string, mixed>|false Vacation record or false if not found
     */
    public static function find(int $userId, int $id): array|false
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM vacations WHERE user_id = :uid AND id = :id");
        $stmt->execute(['uid' => $userId, 'id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Update an existing vacation.
     *
     * @param int $userId User ID
     * @param int $id Vacation ID
     * @param string $destination Destination name/location
     * @param string|null $start Start date (Y-m-d)
     * @param string|null $end End date (Y-m-d)
     * @param string $status Status (idea, planned, booked, completed)
     * @param float|null $budget Budget amount
     * @param string|null $notes Additional notes
     * @return bool True on success
     */
    public static function update(
        int $userId,
        int $id,
        string $destination,
        ?string $start,
        ?string $end,
        string $status,
        ?float $budget = null,
        ?string $notes = null
    ): bool {
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

    /**
     * Delete a vacation by ID for a user.
     *
     * @param int $userId User ID
     * @param int $id Vacation ID
     * @return bool True on success
     */
    public static function delete(int $userId, int $id): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM vacations WHERE id = :id AND user_id = :uid");
        return $stmt->execute(['id' => $id, 'uid' => $userId]);
    }

    /**
     * Get active (non-completed) vacations for a user.
     *
     * @param int $userId User ID
     * @return array<int, array<string, mixed>> Array of vacation records
     */
    public static function getActive(int $userId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM vacations WHERE user_id = :uid AND status != 'completed' ORDER BY start_date ASC");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll() ?: [];
    }
}
