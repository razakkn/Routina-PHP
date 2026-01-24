<?php

namespace Routina\Controllers;

use Routina\Config\Database;

class AdminController {
    public function metrics() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $snapshot = $this->buildMetricsSnapshot();
        view('admin/metrics', ['snapshot' => $snapshot]);
    }

    public function metricsJson() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $snapshot = $this->buildMetricsSnapshot();
        header('Content-Type: application/json');
        echo json_encode($snapshot);
        exit;
    }

    public function metricsCsv() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $snapshot = $this->buildMetricsSnapshot();
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="metrics.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['metric', 'value']);
        fputcsv($out, ['db_can_connect', $snapshot['db']['can_connect'] ? 'true' : 'false']);
        fputcsv($out, ['db_file_size_bytes', $snapshot['db']['file_size_bytes'] ?? 'unknown']);
        fputcsv($out, ['php_version', $snapshot['process']['php_version']]);
        fputcsv($out, ['memory_usage', $snapshot['process']['memory_usage']]);
        fputcsv($out, ['peak_memory_usage', $snapshot['process']['peak_memory_usage']]);
        fputcsv($out, ['server_time', $snapshot['process']['server_time']]);
        fclose($out);
        exit;
    }

    private function buildMetricsSnapshot() {
        $dbCanConnect = false;
        try {
            Database::getConnection();
            $dbCanConnect = true;
        } catch (\Exception $e) {
            $dbCanConnect = false;
        }

        $dbPath = __DIR__ . '/../../database.sqlite';
        $dbFileSize = file_exists($dbPath) ? filesize($dbPath) : null;

        // Request latency history (recorded centrally in the router)
        $recent = $_SESSION['metrics_recent'] ?? [];
        if (!is_array($recent)) {
            $recent = [];
        }
        if (count($recent) > 20) {
            $recent = array_slice($recent, -20);
        }

        return [
            'health' => [
                'status' => $dbCanConnect ? 'Healthy' : 'Unhealthy',
                'checks' => [
                    ['name' => 'database', 'status' => $dbCanConnect ? 'Healthy' : 'Unhealthy']
                ]
            ],
            'recent_request_ms' => $recent,
            'db' => [
                'can_connect' => $dbCanConnect,
                'file_size_bytes' => $dbFileSize
            ],
            'process' => [
                'php_version' => PHP_VERSION,
                'memory_usage' => memory_get_usage(true),
                'peak_memory_usage' => memory_get_peak_usage(true),
                'server_time' => date('c')
            ]
        ];
    }
}
