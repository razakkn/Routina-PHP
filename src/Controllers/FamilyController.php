<?php

namespace Routina\Controllers;

use Routina\Models\Family;

class FamilyController {
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $relation = $_POST['relation'] ?? 'Child';
            $birthdate = $_POST['birthdate'] ?? '';

            if ($name === '' || $birthdate === '') {
                $members = Family::getAll($_SESSION['user_id']);
                view('family/index', [
                    'members' => $members,
                    'error' => 'Please provide a name and birthdate.'
                ]);
                return;
            }

            Family::create(
                $_SESSION['user_id'],
                $name,
                $relation,
                $birthdate
            );
            header('Location: /family');
            exit;
        }

        $members = Family::getAll($_SESSION['user_id']);
        view('family/index', ['members' => $members]);
    }
}
