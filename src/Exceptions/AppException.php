<?php

namespace Routina\Exceptions;

/**
 * Base application exception with HTTP status support.
 * 
 * Provides a consistent way to throw exceptions that map
 * to specific HTTP status codes and error responses.
 */
class AppException extends \Exception
{
    protected int $statusCode = 500;
    protected ?array $errors = null;
    protected ?string $errorCode = null;

    /**
     * Create a new application exception.
     *
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @param array<string, mixed>|null $errors Additional error details
     * @param string|null $errorCode Error code for client handling
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message = '',
        int $statusCode = 500,
        ?array $errors = null,
        ?string $errorCode = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        $this->statusCode = $statusCode;
        $this->errors = $errors;
        $this->errorCode = $errorCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrors(): ?array
    {
        return $this->errors;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }
}

/**
 * Validation exception (HTTP 422).
 */
class ValidationException extends AppException
{
    /**
     * @param array<string, string> $errors Validation errors keyed by field
     * @param string $message Error message
     */
    public function __construct(array $errors, string $message = 'Validation failed')
    {
        parent::__construct($message, 422, $errors, 'VALIDATION_ERROR');
    }
}

/**
 * Not found exception (HTTP 404).
 */
class NotFoundException extends AppException
{
    public function __construct(string $message = 'Resource not found')
    {
        parent::__construct($message, 404, null, 'NOT_FOUND');
    }
}

/**
 * Unauthorized exception (HTTP 401).
 */
class UnauthorizedException extends AppException
{
    public function __construct(string $message = 'Unauthorized')
    {
        parent::__construct($message, 401, null, 'UNAUTHORIZED');
    }
}

/**
 * Forbidden exception (HTTP 403).
 */
class ForbiddenException extends AppException
{
    public function __construct(string $message = 'Access denied')
    {
        parent::__construct($message, 403, null, 'FORBIDDEN');
    }
}

/**
 * Bad request exception (HTTP 400).
 */
class BadRequestException extends AppException
{
    public function __construct(string $message = 'Bad request', ?array $errors = null)
    {
        parent::__construct($message, 400, $errors, 'BAD_REQUEST');
    }
}

/**
 * Conflict exception (HTTP 409).
 */
class ConflictException extends AppException
{
    public function __construct(string $message = 'Resource conflict')
    {
        parent::__construct($message, 409, null, 'CONFLICT');
    }
}

/**
 * Rate limit exception (HTTP 429).
 */
class RateLimitException extends AppException
{
    private int $retryAfter;

    public function __construct(int $retryAfter = 60, string $message = 'Too many requests')
    {
        parent::__construct($message, 429, ['retry_after' => $retryAfter], 'RATE_LIMITED');
        $this->retryAfter = $retryAfter;
    }

    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}
