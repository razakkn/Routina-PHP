<?php

namespace Routina\Controllers;

use Routina\Models\User;
use Routina\Config\Database;
use Routina\Services\AuthService;

class AuthController {
    public function login() {
        $routinaId = strtolower(trim($_POST['routina_id'] ?? ''));
        $password = $_POST['password'] ?? '';

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE LOWER(routina_id) = :rid");
        $stmt->execute(['rid' => $routinaId]);
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
            // Check if MFA is enabled
            if (!empty($user['mfa_enabled']) && !empty($user['mfa_secret'])) {
                // Store pending login in session, redirect to MFA verification
                $_SESSION['mfa_pending_user_id'] = (int)$user['id'];
                header('Location: /login/mfa');
                exit;
            }

            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_data'] = [
                'name' => $user['display_name'] ?? ($user['routina_id'] ?? 'User'),
                'email' => $user['email'] ?? '',
                'routina_id' => $user['routina_id'] ?? null
            ];

            // Check if user needs to set recovery email (mandatory for account verification)
            if (empty($user['email'])) {
                header('Location: /setup-recovery-email');
                exit;
            }

            // Check if user needs to set Routina ID (for Google OAuth users)
            if (empty($user['routina_id'])) {
                header('Location: /setup-routina-id');
                exit;
            }

            header('Location: /dashboard');
            exit;
        } else {
            view('account/login', ['error' => 'Invalid credentials']);
        }
    }

    public function mfaVerify() {
        if (!isset($_SESSION['mfa_pending_user_id'])) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            view('account/mfa_verify');
            return;
        }

        $code = $_POST['code'] ?? '';
        $userId = (int)$_SESSION['mfa_pending_user_id'];
        
        $mfaStatus = AuthService::getMfaStatus($userId);
        
        if ($mfaStatus['enabled'] && AuthService::verifyTotpCode($mfaStatus['secret'], $code)) {
            unset($_SESSION['mfa_pending_user_id']);
            
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
            $stmt->execute(['id' => $userId]);
            $user = $stmt->fetch();

            session_regenerate_id(true);
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_data'] = [
                'name' => $user['display_name'] ?? ($user['email'] ?? 'User'),
                'email' => $user['email'] ?? '',
                'routina_id' => $user['routina_id'] ?? null
            ];

            header('Location: /dashboard');
            exit;
        }

        view('account/mfa_verify', ['error' => 'Invalid verification code']);
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

        $token = AuthService::createPasswordResetToken($email);
        
        if ($token) {
            // In production, send email with reset link
            // For now, we'll show a success message regardless for security
            $resetLink = app_config('app_url', 'http://localhost') . '/reset-password?token=' . $token;
            
            // TODO: Implement actual email sending
            // For development, log the link
            error_log("Password reset link for {$email}: {$resetLink}");
        }

        // Always show success to prevent email enumeration
        view('account/forgot_password', ['success' => 'If that email exists, a reset link has been sent.']);
    }

    public function resetPassword() {
        $token = $_GET['token'] ?? $_POST['token'] ?? '';
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if (empty($token)) {
                view('account/forgot_password', ['error' => 'Invalid or missing reset token.']);
                return;
            }
            view('account/reset_password', ['token' => $token]);
            return;
        }

        $password = $_POST['password'] ?? '';
        $confirm = $_POST['password_confirm'] ?? '';
        
        if ($password !== $confirm) {
            view('account/reset_password', ['token' => $token, 'error' => 'Passwords do not match.']);
            return;
        }

        $validation = AuthService::validatePassword($password);
        if (!$validation['valid']) {
            view('account/reset_password', ['token' => $token, 'error' => $validation['errors'][0]]);
            return;
        }

        $email = AuthService::verifyPasswordResetToken($token);
        if (!$email) {
            view('account/forgot_password', ['error' => 'This reset link has expired or is invalid.']);
            return;
        }

        if (AuthService::updatePassword($email, $password)) {
            view('account/login', ['success' => 'Password reset successful. You can now log in.']);
        } else {
            view('account/reset_password', ['token' => $token, 'error' => 'Failed to update password.']);
        }
    }

    public function register() {
        $routinaId = strtolower(trim($_POST['routina_id'] ?? ''));
        $password = $_POST['password'] ?? '';

        if (!$routinaId || !$password) {
            view('account/login', ['error' => 'Routina ID and Password required']);
            return;
        }

        // Validate Routina ID format (3-20 chars, start with letter, alphanumeric + underscore)
        if (!preg_match('/^[a-z][a-z0-9_]{2,19}$/', $routinaId)) {
            view('account/login', ['error' => 'Routina ID must be 3-20 characters, start with a letter, and contain only letters, numbers, and underscores.']);
            return;
        }

        // Check if Routina ID is available
        if (!AuthService::isRoutinaIdAvailable($routinaId)) {
            view('account/login', ['error' => 'This Routina ID is already taken. Please choose another.']);
            return;
        }

        // Validate password strength
        $validation = AuthService::validatePassword($password);
        if (!$validation['valid']) {
            view('account/login', ['error' => $validation['errors'][0]]);
            return;
        }

        $db = Database::getConnection();
        
        // Insert new user with routina_id only (no email yet)
        $sql = "INSERT INTO users (routina_id, password, currency, spouse_count, created_at) 
                VALUES (:rid, :pass, 'USD', 0, CURRENT_TIMESTAMP)";
        $stmt = $db->prepare($sql);
        $hash = password_hash((string)$password, PASSWORD_DEFAULT);
        $success = $stmt->execute([
            'rid' => $routinaId,
            'pass' => $hash
        ]);

        if ($success) {
            $id = $db->lastInsertId();
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$id;
            $_SESSION['user_data'] = [
                'name' => $routinaId,
                'email' => '',
                'routina_id' => $routinaId
            ];
            
            // Redirect to setup recovery email (mandatory)
            header('Location: /setup-recovery-email');
            exit;
        } else {
            view('account/login', ['error' => 'Registration failed']);
        }
    }

    public function setupRoutinaId() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $userId = (int)$_SESSION['user_id'];
        $db = Database::getConnection();
        
        // Check if user already has a Routina ID
        $stmt = $db->prepare("SELECT routina_id, display_name, email FROM users WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();
        
        if (!empty($user['routina_id'])) {
            header('Location: /dashboard');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $routinaId = strtolower(trim($_POST['routina_id'] ?? ''));
            
            // Validate format
            if (!preg_match('/^[a-z][a-z0-9_]{2,19}$/', $routinaId)) {
                $suggestions = AuthService::suggestRoutinaIds($user['display_name'] ?? '', $user['email'] ?? '');
                view('account/setup_routina_id', [
                    'error' => 'ID must be 3-20 characters, start with a letter, and contain only letters, numbers, and underscores.',
                    'suggestions' => $suggestions
                ]);
                return;
            }

            // Check availability
            if (!AuthService::isRoutinaIdAvailable($routinaId)) {
                $suggestions = AuthService::suggestRoutinaIds($user['display_name'] ?? '', $user['email'] ?? '');
                view('account/setup_routina_id', [
                    'error' => 'This ID is already taken. Please choose another.',
                    'suggestions' => $suggestions
                ]);
                return;
            }

            // Update user
            $stmt = $db->prepare("UPDATE users SET routina_id = :rid WHERE id = :id");
            $stmt->execute(['rid' => $routinaId, 'id' => $userId]);
            
            $_SESSION['user_data']['routina_id'] = $routinaId;

            header('Location: /dashboard');
            exit;
        }

        $suggestions = AuthService::suggestRoutinaIds($user['display_name'] ?? '', $user['email'] ?? '');
        view('account/setup_routina_id', ['suggestions' => $suggestions]);
    }

    /**
     * Setup recovery email (mandatory for account verification after registration)
     */
    public function setupRecoveryEmail() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $userId = (int)$_SESSION['user_id'];
        $db = Database::getConnection();
        
        // Check if user already has an email
        $stmt = $db->prepare("SELECT email, routina_id FROM users WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();
        
        if (!empty($user['email'])) {
            header('Location: /dashboard');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = strtolower(trim($_POST['email'] ?? ''));
            
            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                view('account/setup_recovery_email', [
                    'error' => 'Please enter a valid email address.',
                    'routina_id' => $user['routina_id']
                ]);
                return;
            }

            // Check if email is already used
            $existing = AuthService::findByEmail($email, $userId);
            if ($existing) {
                view('account/setup_recovery_email', [
                    'error' => 'This email is already associated with another account.',
                    'routina_id' => $user['routina_id']
                ]);
                return;
            }

            // Update user with email
            $stmt = $db->prepare("UPDATE users SET email = :email WHERE id = :id");
            $stmt->execute(['email' => $email, 'id' => $userId]);
            
            $_SESSION['user_data']['email'] = $email;

            // TODO: Send verification email
            // For now, proceed to dashboard
            header('Location: /dashboard');
            exit;
        }

        view('account/setup_recovery_email', ['routina_id' => $user['routina_id']]);
    }

    public function googleAuth() {
        $config = app_config();
        $googleClientId = $config['google_client_id'] ?? '';
        $googleClientSecret = $config['google_client_secret'] ?? '';
        
        if (empty($googleClientId) || empty($googleClientSecret)) {
            view('account/login', ['error' => 'Google sign-in is not configured.']);
            return;
        }

        $action = $_GET['action'] ?? 'login';
        $_SESSION['google_auth_action'] = $action;

        $redirectUri = $config['google_redirect_uri'] ?? ($config['app_url'] . '/auth/google/callback');
        $scope = urlencode('email profile');
        
        $authUrl = "https://accounts.google.com/o/oauth2/v2/auth?"
            . "client_id={$googleClientId}"
            . "&redirect_uri=" . urlencode($redirectUri)
            . "&response_type=code"
            . "&scope={$scope}"
            . "&access_type=offline";

        header('Location: ' . $authUrl);
        exit;
    }

    public function googleCallback() {
        $config = app_config();
        $googleClientId = $config['google_client_id'] ?? '';
        $googleClientSecret = $config['google_client_secret'] ?? '';
        $redirectUri = $config['google_redirect_uri'] ?? ($config['app_url'] . '/auth/google/callback');

        $code = $_GET['code'] ?? '';
        if (empty($code)) {
            view('account/login', ['error' => 'Google authentication failed.']);
            return;
        }

        // Exchange code for tokens
        $tokenResponse = file_get_contents('https://oauth2.googleapis.com/token', false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query([
                    'client_id' => $googleClientId,
                    'client_secret' => $googleClientSecret,
                    'code' => $code,
                    'redirect_uri' => $redirectUri,
                    'grant_type' => 'authorization_code'
                ])
            ]
        ]));

        if (!$tokenResponse) {
            view('account/login', ['error' => 'Failed to verify Google authentication.']);
            return;
        }

        $tokens = json_decode($tokenResponse, true);
        $accessToken = $tokens['access_token'] ?? '';

        if (empty($accessToken)) {
            view('account/login', ['error' => 'Failed to get access token from Google.']);
            return;
        }

        // Get user info
        $userResponse = file_get_contents('https://www.googleapis.com/oauth2/v2/userinfo', false, stream_context_create([
            'http' => [
                'header' => "Authorization: Bearer {$accessToken}"
            ]
        ]));

        if (!$userResponse) {
            view('account/login', ['error' => 'Failed to get user information from Google.']);
            return;
        }

        $googleUser = json_decode($userResponse, true);
        $googleId = $googleUser['id'] ?? '';
        $googleEmail = $googleUser['email'] ?? '';
        $googleName = $googleUser['name'] ?? '';

        if (empty($googleId) || empty($googleEmail)) {
            view('account/login', ['error' => 'Invalid user data from Google.']);
            return;
        }

        // Check if user exists by Google ID
        $user = AuthService::findByGoogleId($googleId);

        if (!$user) {
            // Check if email exists
            $existingByEmail = AuthService::findByEmail($googleEmail);
            
            if ($existingByEmail) {
                // Link Google to existing account
                $db = Database::getConnection();
                $stmt = $db->prepare("UPDATE users SET google_id = :gid, email_verified_at = CURRENT_TIMESTAMP WHERE id = :id");
                $stmt->execute(['gid' => $googleId, 'id' => $existingByEmail['id']]);
                
                $user = $existingByEmail;
            } else {
                // Create new user
                $userId = AuthService::createFromGoogle([
                    'id' => $googleId,
                    'email' => $googleEmail,
                    'name' => $googleName
                ]);
                
                if (!$userId) {
                    view('account/login', ['error' => 'Failed to create account.']);
                    return;
                }

                $db = Database::getConnection();
                $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
                $stmt->execute(['id' => $userId]);
                $user = $stmt->fetch();
            }
        }

        // Log in user
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_data'] = [
            'name' => $user['display_name'] ?? $googleName,
            'email' => $user['email'] ?? $googleEmail,
            'routina_id' => $user['routina_id'] ?? null
        ];

        // Check if user needs to set Routina ID
        if (empty($user['routina_id'])) {
            header('Location: /setup-routina-id');
            exit;
        }

        header('Location: /dashboard');
        exit;
    }
}
