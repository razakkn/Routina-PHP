<?php

namespace Routina\Controllers;

use Routina\Models\User;
use Routina\Config\Database;
use Routina\Services\AuthService;

class AuthController {
    public function login() {
        try {
            $routinaId = strtolower(trim($_POST['routina_id'] ?? ''));
            $password = $_POST['password'] ?? '';
            error_log('Login attempt: rid=' . $routinaId . ' sid=' . session_id());

            // Validate input
            if (empty($routinaId)) {
                view('account/login', ['error' => 'Please enter your Routina ID']);
                return;
            }
            if (empty($password)) {
                view('account/login', ['error' => 'Please enter your password', 'routina_id' => $routinaId]);
                return;
            }

            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT * FROM users WHERE LOWER(routina_id) = :rid");
            $stmt->execute(['rid' => $routinaId]);
            $user = $stmt->fetch();

            // User not found
            if (!$user) {
                view('account/login', ['error' => 'Account not found. Please check your Routina ID or create a new account.', 'routina_id' => $routinaId]);
                return;
            }

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

            // Invalid password
            if (!$ok) {
                view('account/login', ['error' => 'Invalid password. Please try again.', 'routina_id' => $routinaId]);
                return;
            }

            // Login successful - continue with MFA check
            // Check if MFA is enabled
            if (!empty($user['mfa_enabled']) && !empty($user['mfa_secret'])) {
                // Store pending login in session, redirect to MFA verification
                $_SESSION['mfa_pending_user_id'] = (int)$user['id'];
                session_write_close();
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
                session_write_close();
                header('Location: /setup-recovery-email');
                exit;
            }

            // Check if user needs to set Routina ID (for Google OAuth users)
            if (empty($user['routina_id'])) {
                session_write_close();
                header('Location: /setup-routina-id');
                exit;
            }

            session_write_close();
            error_log('Login success: rid=' . $routinaId . ' uid=' . $user['id'] . ' sid=' . session_id());
            header('Location: /dashboard');
            exit;

        } catch (\Throwable $e) {
            error_log("Login error: " . $e->getMessage());
            view('account/login', ['error' => 'An error occurred during login. Please try again.']);
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

            session_write_close();
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
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        if (!$routinaId || !$password || !$email) {
            view('account/register', [
                'error' => 'Routina ID, Email, and Password are required',
                'routina_id' => $routinaId,
                'email' => $email
            ]);
            return;
        }

        // Validate Routina ID format (3-20 chars, start with letter, alphanumeric + underscore)
        if (!preg_match('/^[a-z][a-z0-9_]{2,19}$/', $routinaId)) {
            $suggestions = AuthService::suggestRoutinaIds($routinaId, $email);
            view('account/register', [
                'error' => 'Routina ID must be 3-20 characters, start with a letter, and contain only letters, numbers, and underscores.',
                'routina_id' => $routinaId,
                'email' => $email,
                'suggestions' => $suggestions
            ]);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            view('account/register', [
                'error' => 'Please enter a valid email address.',
                'routina_id' => $routinaId,
                'email' => $email
            ]);
            return;
        }

        // Check if Routina ID is available
        if (!AuthService::isRoutinaIdAvailable($routinaId)) {
            $suggestions = AuthService::suggestRoutinaIds($routinaId, $email);
            view('account/register', [
                'error' => 'This Routina ID is already taken. Please choose another.',
                'routina_id' => $routinaId,
                'email' => $email,
                'suggestions' => $suggestions
            ]);
            return;
        }

        // Check if email is already in use
        if (AuthService::findByEmail($email)) {
            view('account/register', [
                'error' => 'This email is already associated with an account.',
                'routina_id' => $routinaId,
                'email' => $email
            ]);
            return;
        }

        // Validate password strength
        $validation = AuthService::validatePassword($password);
        if (!$validation['valid']) {
            view('account/register', [
                'error' => $validation['errors'][0],
                'routina_id' => $routinaId,
                'email' => $email
            ]);
            return;
        }

        $db = Database::getConnection();
        
        // Insert new user with routina_id and email
        $sql = "INSERT INTO users (routina_id, email, password, currency, spouse_count, created_at) 
                VALUES (:rid, :email, :pass, 'USD', 0, CURRENT_TIMESTAMP)";
        $stmt = $db->prepare($sql);
        $hash = password_hash((string)$password, PASSWORD_DEFAULT);
        $dbError = null;
        try {
            $success = $stmt->execute([
                'rid' => $routinaId,
                'email' => $email,
                'pass' => $hash
            ]);
        } catch (\PDOException $e) {
            $success = false;
            $dbError = $e->getMessage();
            error_log('Register failed: ' . $dbError);
        }

        if ($success) {
            $id = $db->lastInsertId();
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$id;
            $_SESSION['user_data'] = [
                'name' => $routinaId,
                'email' => $email,
                'routina_id' => $routinaId
            ];
            
            // Redirect to dashboard with success message
            view('account/register', ['success' => 'Account created successfully! You can now log in.']);
        } else {
            $message = 'Registration failed. Please try again.';
            if (is_string($dbError)) {
                $lower = strtolower($dbError);
                if (strpos($lower, "doesn't exist") !== false || strpos($lower, 'does not exist') !== false) {
                    $message = 'Registration system is not initialized. Please contact support.';
                } elseif (strpos($lower, 'unknown column') !== false) {
                    $message = 'Registration schema is outdated. Please contact support.';
                }
            }
            view('account/register', [
                'error' => $message,
                'routina_id' => $routinaId,
                'email' => $email
            ]);
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

            // Auto-populate profile from family tree data if this email exists there
            $populated = AuthService::autoPopulateFromFamilyTree($userId, $email);
            if ($populated) {
                // Refresh session data with populated name
                $stmt = $db->prepare("SELECT display_name FROM users WHERE id = :id");
                $stmt->execute(['id' => $userId]);
                $refreshed = $stmt->fetch();
                if ($refreshed && !empty($refreshed['display_name'])) {
                    $_SESSION['user_data']['name'] = $refreshed['display_name'];
                }
            }

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
            error_log("Google callback: no code received");
            view('account/login', ['error' => 'Google authentication failed.']);
            return;
        }
        error_log("Google callback: code received, length " . strlen($code));

        // Exchange code for tokens
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'client_id' => $googleClientId,
            'client_secret' => $googleClientSecret,
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code'
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        $tokenResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$tokenResponse || $httpCode !== 200) {
            error_log("Google callback: token exchange failed, HTTP $httpCode, response: " . substr($tokenResponse, 0, 200));
            view('account/login', ['error' => 'Failed to verify Google authentication.']);
            return;
        }
        error_log("Google callback: token response received, HTTP $httpCode");

        $tokens = json_decode($tokenResponse, true);
        $accessToken = $tokens['access_token'] ?? '';

        if (empty($accessToken)) {
            error_log("Google callback: no access token in response: " . $tokenResponse);
            view('account/login', ['error' => 'Failed to get access token from Google.']);
            return;
        }
        error_log("Google callback: access token obtained");

        // Get user info
        $ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer {$accessToken}"]);
        $userResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$userResponse || $httpCode !== 200) {
            error_log("Google callback: user info request failed, HTTP $httpCode, response: " . substr($userResponse, 0, 200));
            view('account/login', ['error' => 'Failed to get user information from Google.']);
            return;
        }
        error_log("Google callback: user info received, HTTP $httpCode");

        $googleUser = json_decode($userResponse, true);
        $googleId = $googleUser['id'] ?? '';
        $googleEmail = $googleUser['email'] ?? '';
        $googleName = $googleUser['name'] ?? '';

        if (empty($googleId) || empty($googleEmail)) {
            error_log("Google callback: invalid user data - id: $googleId, email: $googleEmail");
            view('account/login', ['error' => 'Invalid user data from Google.']);
            return;
        }
        error_log("Google callback: user data - id: $googleId, email: $googleEmail, name: $googleName");

        // Check if user exists by Google ID
        $user = AuthService::findByGoogleId($googleId);
        $isNewUser = false;

        if (!$user) {
            error_log("Google callback: no user found by Google ID $googleId");
            // Check if email exists
            $existingByEmail = AuthService::findByEmail($googleEmail);
            
            if ($existingByEmail) {
                error_log("Google callback: linking to existing user by email $googleEmail, user ID " . $existingByEmail['id']);
                // Link Google to existing account
                $db = Database::getConnection();
                $stmt = $db->prepare("UPDATE users SET google_id = :gid, email_verified_at = CURRENT_TIMESTAMP WHERE id = :id");
                $stmt->execute(['gid' => $googleId, 'id' => $existingByEmail['id']]);
                
                // Also auto-populate profile if this email exists in someone's family tree
                AuthService::autoPopulateFromFamilyTree((int)$existingByEmail['id'], $googleEmail);
                
                $user = $existingByEmail;
            } else {
                error_log("Google callback: creating new user for email $googleEmail");
                // Create new user
                $userId = AuthService::createFromGoogle([
                    'id' => $googleId,
                    'email' => $googleEmail,
                    'name' => $googleName
                ]);
                
                if (!$userId) {
                    error_log("Google callback: failed to create user");
                    view('account/login', ['error' => 'Failed to create account.']);
                    return;
                }

                // Auto-populate profile from family tree if email exists there
                AuthService::autoPopulateFromFamilyTree($userId, $googleEmail);
                $isNewUser = true;

                $db = Database::getConnection();
                $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
                $stmt->execute(['id' => $userId]);
                $user = $stmt->fetch();
            }
        } else {
            error_log("Google callback: user found by Google ID $googleId, user ID " . $user['id']);
        }

        // Log in user
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_data'] = [
            'name' => $user['display_name'] ?? $googleName,
            'email' => $user['email'] ?? $googleEmail,
            'routina_id' => $user['routina_id'] ?? null
        ];
        error_log("Google callback: session set for user ID " . $user['id'] . ", routina_id: " . ($user['routina_id'] ?? 'null'));

        // Check if user needs to set Routina ID
        if (empty($user['routina_id'])) {
            error_log("Google callback: redirecting to setup-routina-id");
            session_write_close();
            header('Location: /setup-routina-id');
            exit;
        }

        error_log("Google callback: redirecting to dashboard");
        session_write_close();
        header('Location: /dashboard');
        exit;
    }
}
