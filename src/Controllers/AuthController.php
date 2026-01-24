<?php

namespace Routina\Controllers;

use Routina\Models\User;
use Routina\Config\Database;

class AuthController {
    public function login() {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        $stored = $user['password'] ?? null;
        $ok = false;

        if (is_string($stored) && $stored !== '') {
            $info = password_get_info($stored);
            if (!empty($info['algo'])) {
                $ok = password_verify($password, $stored);
            } else {
                // Legacy plaintext (demo seed). Upgrade on successful login.
                $ok = hash_equals($stored, (string)$password);
                if ($ok) {
                    $newHash = password_hash((string)$password, PASSWORD_DEFAULT);
                    $u = $db->prepare('UPDATE users SET password = :p WHERE id = :id');
                    $u->execute(['p' => $newHash, 'id' => $user['id']]);
                }
            }
        }

        if ($user && $ok) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_data'] = [
                'name' => $user['display_name'] ?? ($user['email'] ?? 'User'),
                'email' => $user['email'] ?? ''
            ];
            header('Location: /dashboard');
            exit;
        } else {
            view('account/login', ['error' => 'Invalid credentials']);
        }
    }

    public function logout() {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();
        header('Location: /logged-out');
        exit;
    }

    public function forgotPassword() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            view('account/forgot_password');
            return;
        }

        $email = trim($_POST['email'] ?? '');
        if ($email === '') {
            view('account/forgot_password', ['error' => 'Please enter your email address.']);
            return;
        }

        // Stub: In a real app, send reset email.
        view('account/forgot_password', ['success' => 'If that email exists, a reset link has been sent.']);
    }

    public function register() {
        $name = $_POST['display_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            view('account/register', ['error' => 'Email and Password required']);
            return;
        }

        $db = Database::getConnection();
        
        // Check existing
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        if ($stmt->fetchColumn() > 0) {
            view('account/register', ['error' => 'User already exists']);
            return; 
        }

        // Insert
        $sql = "INSERT INTO users (display_name, email, password, currency, spouse_count) VALUES (:name, :email, :pass, 'USD', 0)";
        $stmt = $db->prepare($sql);
        $hash = password_hash((string)$password, PASSWORD_DEFAULT);
        $success = $stmt->execute([
            'name' => $name,
            'email' => $email,
            'pass' => $hash
        ]);

        if ($success) {
            // Auto login
            $id = $db->lastInsertId();
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$id;
            $_SESSION['user_data'] = [
                'name' => $name,
                'email' => $email
            ];
            header('Location: /dashboard');
            exit;
        } else {
            view('account/register', ['error' => 'Registration failed']);
        }
    }
}
