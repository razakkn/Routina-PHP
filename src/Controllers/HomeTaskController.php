<?php

namespace Routina\Controllers;

use Routina\Models\HomeTask;

class HomeTaskController {
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['toggle_id'])) {
                $toggleId = $_POST['toggle_id'];
                if (is_numeric($toggleId)) {
                    HomeTask::toggle((int)$toggleId, $_SESSION['user_id']);
                } else {
                    $tasks = HomeTask::getAll($_SESSION['user_id']);
                    view('home_task/index', [
                        'tasks' => $tasks,
                        'error' => 'Invalid task selected.'
                    ]);
                    return;
                }
            } else {
                $title = trim($_POST['title'] ?? '');
                $frequency = $_POST['frequency'] ?? 'One-time';
                $assignee = trim($_POST['assignee'] ?? '');
                $plannedDate = null;
                if ($frequency === 'Planned') {
                    $plannedDate = trim((string)($_POST['planned_date'] ?? ''));
                    if ($plannedDate === '') {
                        $tasks = HomeTask::getAll($_SESSION['user_id']);
                        view('home_task/index', [
                            'tasks' => $tasks,
                            'error' => 'Please choose a planned date.'
                        ]);
                        return;
                    }
                }

                if ($title === '') {
                    $tasks = HomeTask::getAll($_SESSION['user_id']);
                    view('home_task/index', [
                        'tasks' => $tasks,
                        'error' => 'Please provide a task name.'
                    ]);
                    return;
                }

                HomeTask::create(
                    $_SESSION['user_id'],
                    $title,
                    $frequency,
                    $assignee,
                    $plannedDate !== '' ? $plannedDate : null
                );

                if ($frequency === 'Planned' && $plannedDate) {
                    try {
                        \Routina\Models\Calendar::create(
                            $_SESSION['user_id'],
                            'Home: ' . $title,
                            $plannedDate,
                            $plannedDate,
                            'task'
                        );
                    } catch (\Throwable $e) {
                        // Best-effort: ignore calendar errors
                    }
                }
            }
            header('Location: /home');
            exit;
        }

        $tasks = HomeTask::getAll($_SESSION['user_id']);
        view('home_task/index', ['tasks' => $tasks]);
    }
}
