<?php

namespace Routina\Http;

/**
 * API Response builder for consistent JSON responses.
 * 
 * Provides standardized response format for all API endpoints with
 * proper HTTP status codes, error handling, and pagination support.
 */
class ApiResponse
{
    /**
     * Send a successful response.
     *
     * @param mixed $data Response data
     * @param string|null $message Optional success message
     * @param int $statusCode HTTP status code (default 200)
     * @return never
     */
    public static function success(mixed $data = null, ?string $message = null, int $statusCode = 200): never
    {
        self::send([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'error' => null
        ], $statusCode);
    }

    /**
     * Send a created response (HTTP 201).
     *
     * @param mixed $data Created resource data
     * @param string|null $message Optional message
     * @return never
     */
    public static function created(mixed $data = null, ?string $message = 'Resource created successfully'): never
    {
        self::success($data, $message, 201);
    }

    /**
     * Send an error response.
     *
     * @param string $message Error message
     * @param int $statusCode HTTP status code (default 400)
     * @param array<string, mixed>|null $errors Detailed error information
     * @param string|null $code Error code for client handling
     * @return never
     */
    public static function error(
        string $message,
        int $statusCode = 400,
        ?array $errors = null,
        ?string $code = null
    ): never {
        self::send([
            'success' => false,
            'message' => $message,
            'data' => null,
            'error' => [
                'code' => $code ?? self::getDefaultErrorCode($statusCode),
                'details' => $errors
            ]
        ], $statusCode);
    }

    /**
     * Send a validation error response (HTTP 422).
     *
     * @param array<string, string> $errors Validation errors keyed by field
     * @param string $message Error message
     * @return never
     */
    public static function validationError(array $errors, string $message = 'Validation failed'): never
    {
        self::error($message, 422, $errors, 'VALIDATION_ERROR');
    }

    /**
     * Send a not found response (HTTP 404).
     *
     * @param string $message Error message
     * @return never
     */
    public static function notFound(string $message = 'Resource not found'): never
    {
        self::error($message, 404, null, 'NOT_FOUND');
    }

    /**
     * Send an unauthorized response (HTTP 401).
     *
     * @param string $message Error message
     * @return never
     */
    public static function unauthorized(string $message = 'Unauthorized'): never
    {
        self::error($message, 401, null, 'UNAUTHORIZED');
    }

    /**
     * Send a forbidden response (HTTP 403).
     *
     * @param string $message Error message
     * @return never
     */
    public static function forbidden(string $message = 'Access denied'): never
    {
        self::error($message, 403, null, 'FORBIDDEN');
    }

    /**
     * Send a server error response (HTTP 500).
     *
     * @param string $message Error message
     * @param \Throwable|null $exception Optional exception for logging
     * @return never
     */
    public static function serverError(string $message = 'Internal server error', ?\Throwable $exception = null): never
    {
        // Log exception if provided (in production, don't expose details)
        if ($exception !== null) {
            error_log(sprintf(
                "[API Error] %s in %s:%d\n%s",
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
                $exception->getTraceAsString()
            ));
        }

        self::error($message, 500, null, 'SERVER_ERROR');
    }

    /**
     * Send a rate limit exceeded response (HTTP 429).
     *
     * @param int $retryAfter Seconds until retry is allowed
     * @param string $message Error message
     * @return never
     */
    public static function rateLimited(int $retryAfter = 60, string $message = 'Too many requests'): never
    {
        header("Retry-After: {$retryAfter}");
        self::error($message, 429, ['retry_after' => $retryAfter], 'RATE_LIMITED');
    }

    /**
     * Send a paginated response.
     *
     * @param array<int, mixed> $items Items for current page
     * @param int $total Total number of items
     * @param int $page Current page number
     * @param int $perPage Items per page
     * @param string|null $message Optional message
     * @return never
     */
    public static function paginated(
        array $items,
        int $total,
        int $page,
        int $perPage,
        ?string $message = null
    ): never {
        $totalPages = (int)ceil($total / max(1, $perPage));

        self::send([
            'success' => true,
            'message' => $message,
            'data' => $items,
            'error' => null,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $totalPages,
                'has_more' => $page < $totalPages
            ]
        ], 200);
    }

    /**
     * Send raw JSON response with custom structure.
     *
     * @param array<string, mixed> $data Response data
     * @param int $statusCode HTTP status code
     * @return never
     */
    public static function send(array $data, int $statusCode = 200): never
    {
        // Add metadata
        $data['timestamp'] = date('c');
        $data['request_id'] = self::getRequestId();

        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        exit;
    }

    /**
     * Get default error code for HTTP status.
     *
     * @param int $statusCode HTTP status code
     * @return string Error code
     */
    private static function getDefaultErrorCode(int $statusCode): string
    {
        return match ($statusCode) {
            400 => 'BAD_REQUEST',
            401 => 'UNAUTHORIZED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            405 => 'METHOD_NOT_ALLOWED',
            409 => 'CONFLICT',
            422 => 'VALIDATION_ERROR',
            429 => 'RATE_LIMITED',
            500 => 'SERVER_ERROR',
            502 => 'BAD_GATEWAY',
            503 => 'SERVICE_UNAVAILABLE',
            default => 'ERROR'
        };
    }

    /**
     * Get or generate request ID for tracing.
     *
     * @return string Request ID
     */
    private static function getRequestId(): string
    {
        static $requestId = null;

        if ($requestId === null) {
            $requestId = $_SERVER['HTTP_X_REQUEST_ID'] 
                ?? bin2hex(random_bytes(8));
        }

        return $requestId;
    }
}
