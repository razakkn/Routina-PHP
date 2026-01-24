<?php

namespace Routina\Controllers;

use Routina\Models\Health;

class HealthController {
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $date = $_POST['date'] ?? date('Y-m-d');
            $weightRaw = $_POST['weight'] ?? '';
            $stepsRaw = $_POST['steps'] ?? '';
            $sleepRaw = $_POST['sleep'] ?? '';
            $waterRaw = $_POST['water'] ?? '';

            $weight = ($weightRaw === '') ? null : (is_numeric($weightRaw) ? (float)$weightRaw : null);
            $steps = ($stepsRaw === '') ? null : (is_numeric($stepsRaw) ? (int)$stepsRaw : null);
            $sleep = ($sleepRaw === '') ? null : (is_numeric($sleepRaw) ? (float)$sleepRaw : null);
            $water = ($waterRaw === '') ? null : (is_numeric($waterRaw) ? (int)$waterRaw : null);

            if ($weightRaw !== '' && $weight === null || $stepsRaw !== '' && $steps === null || $sleepRaw !== '' && $sleep === null || $waterRaw !== '' && $water === null) {
                $logs = Health::getAll($_SESSION['user_id']);
                view('health/index', [
                    'logs' => $logs,
                    'error' => 'Please enter valid numeric values for the health log.'
                ]);
                return;
            }

            if ($weight === null && $steps === null && $sleep === null && $water === null) {
                $logs = Health::getAll($_SESSION['user_id']);
                view('health/index', [
                    'logs' => $logs,
                    'error' => 'Please provide at least one metric to save.'
                ]);
                return;
            }

            Health::create(
                $_SESSION['user_id'],
                $date,
                $weight,
                $steps,
                $sleep,
                $water
            );
            header('Location: /health');
            exit;
        }

        $logs = Health::getAll($_SESSION['user_id']);
        view('health/index', ['logs' => $logs]);
    }
}
