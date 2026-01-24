<?php

namespace Routina\Controllers;

/**
 * Base Controller class providing common functionality for all controllers.
 * 
 * Provides authentication checks, CSRF validation, JSON responses,
 * input sanitization, and other shared controller behaviors.
 */
abstract class BaseController
{
    /** @var int|null Current authenticated user ID */
    protected ?int $userId = null;

    /**
     * Initialize the controller and set the current user if authenticated.
     */
    public function __construct()
    {
        if (isset($_SESSION['user_id'])) {
            $this->userId = (int)$_SESSION['user_id'];
        }
    }

    /**
     * Check if the current user is authenticated.
     *
     * @return bool True if user is logged in
     */
    protected function isAuthenticated(): bool
    {
        return $this->userId !== null && $this->userId > 0;
    }

    /**
     * Require authentication, redirecting to login if not authenticated.
     *
     * @return void
     */
    protected function requireAuth(): void
    {
        if (!$this->isAuthenticated()) {
            header('Location: /login');
            exit;
        }
    }

    /**
     * Get the current user ID.
     *
     * @return int
     * @throws \RuntimeException If user is not authenticated
     */
    protected function getUserId(): int
    {
        if ($this->userId === null) {
            throw new \RuntimeException('User not authenticated');
        }
        return $this->userId;
    }

    /**
     * Validate CSRF token for POST requests.
     *
     * @return bool True if token is valid
     */
    protected function validateCsrf(): bool
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        
        return hash_equals($sessionToken, $token);
    }

    /**
     * Require valid CSRF token, sending 403 if invalid.
     *
     * @return void
     */
    protected function requireCsrf(): void
    {
        if (!$this->validateCsrf()) {
            $this->jsonError('Invalid CSRF token', 403);
        }
    }

    /**
     * Send a JSON success response.
     *
     * @param mixed $data Response data
     * @param int $statusCode HTTP status code
     * @return never
     */
    protected function jsonSuccess(mixed $data = null, int $statusCode = 200): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'data' => $data,
            'error' => null,
            'timestamp' => date('c')
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Send a JSON error response.
     *
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @param array<string, mixed> $errors Additional error details
     * @return never
     */
    protected function jsonError(string $message, int $statusCode = 400, array $errors = []): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'data' => null,
            'error' => [
                'message' => $message,
                'details' => $errors
            ],
            'timestamp' => date('c')
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Redirect to a URL with optional flash message.
     *
     * @param string $url Target URL
     * @param string|null $flashMessage Optional flash message
     * @param string $flashType Flash message type (success, error, info, warning)
     * @return never
     */
    protected function redirect(string $url, ?string $flashMessage = null, string $flashType = 'info'): never
    {
        if ($flashMessage !== null) {
            $_SESSION['flash'] = [
                'message' => $flashMessage,
                'type' => $flashType
            ];
        }
        header("Location: {$url}");
        exit;
    }

    /**
     * Get and clear flash message from session.
     *
     * @return array{message: string, type: string}|null
     */
    protected function getFlash(): ?array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }

    /**
     * Sanitize a string input.
     *
     * @param string $input Raw input
     * @return string Sanitized string
     */
    protected function sanitizeString(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Get a POST parameter with optional default.
     *
     * @param string $key Parameter key
     * @param mixed $default Default value if not present
     * @return mixed
     */
    protected function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    /**
     * Get a GET parameter with optional default.
     *
     * @param string $key Parameter key
     * @param mixed $default Default value if not present
     * @return mixed
     */
    protected function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Check if request method is POST.
     *
     * @return bool
     */
    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * Check if request method is GET.
     *
     * @return bool
     */
    protected function isGet(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    /**
     * Check if the request expects JSON response.
     *
     * @return bool
     */
    protected function expectsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return str_contains($accept, 'application/json');
    }

    /**
     * Render a view with data.
     *
     * @param string $viewName View path (without .php)
     * @param array<string, mixed> $data Data to pass to view
     * @return void
     */
    protected function render(string $viewName, array $data = []): void
    {
        view($viewName, $data);
    }
}
