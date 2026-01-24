<?php

namespace Routina\Services;

/**
 * Input validation service.
 * 
 * Provides reusable validation methods for common input types
 * with consistent error messages and sanitization.
 */
class ValidationService
{
    /** @var array<string, string> Validation errors keyed by field name */
    private array $errors = [];

    /**
     * Get all validation errors.
     *
     * @return array<string, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if validation passed (no errors).
     *
     * @return bool
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Check if validation failed.
     *
     * @return bool
     */
    public function fails(): bool
    {
        return !$this->passes();
    }

    /**
     * Get first error message or null.
     *
     * @return string|null
     */
    public function firstError(): ?string
    {
        return $this->errors ? reset($this->errors) : null;
    }

    /**
     * Clear all errors.
     *
     * @return self
     */
    public function clear(): self
    {
        $this->errors = [];
        return $this;
    }

    /**
     * Add a custom error.
     *
     * @param string $field Field name
     * @param string $message Error message
     * @return self
     */
    public function addError(string $field, string $message): self
    {
        $this->errors[$field] = $message;
        return $this;
    }

    /**
     * Validate required field.
     *
     * @param string $field Field name
     * @param mixed $value Value to check
     * @param string|null $label Human-readable label
     * @return self
     */
    public function required(string $field, mixed $value, ?string $label = null): self
    {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if ($value === null || (is_string($value) && trim($value) === '')) {
            $this->errors[$field] = "{$label} is required.";
        }
        
        return $this;
    }

    /**
     * Validate email format.
     *
     * @param string $field Field name
     * @param string|null $value Email to validate
     * @param string|null $label Human-readable label
     * @return self
     */
    public function email(string $field, ?string $value, ?string $label = null): self
    {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "{$label} must be a valid email address.";
        }
        
        return $this;
    }

    /**
     * Validate URL format.
     *
     * @param string $field Field name
     * @param string|null $value URL to validate
     * @param string|null $label Human-readable label
     * @return self
     */
    public function url(string $field, ?string $value, ?string $label = null): self
    {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->errors[$field] = "{$label} must be a valid URL.";
        }
        
        return $this;
    }

    /**
     * Validate numeric value.
     *
     * @param string $field Field name
     * @param mixed $value Value to validate
     * @param string|null $label Human-readable label
     * @return self
     */
    public function numeric(string $field, mixed $value, ?string $label = null): self
    {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if ($value !== null && $value !== '' && !is_numeric($value)) {
            $this->errors[$field] = "{$label} must be a number.";
        }
        
        return $this;
    }

    /**
     * Validate integer value.
     *
     * @param string $field Field name
     * @param mixed $value Value to validate
     * @param string|null $label Human-readable label
     * @return self
     */
    public function integer(string $field, mixed $value, ?string $label = null): self
    {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if ($value !== null && $value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false) {
            $this->errors[$field] = "{$label} must be a whole number.";
        }
        
        return $this;
    }

    /**
     * Validate minimum value (for numbers).
     *
     * @param string $field Field name
     * @param mixed $value Value to validate
     * @param float|int $min Minimum value
     * @param string|null $label Human-readable label
     * @return self
     */
    public function min(string $field, mixed $value, float|int $min, ?string $label = null): self
    {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if ($value !== null && $value !== '' && is_numeric($value) && (float)$value < $min) {
            $this->errors[$field] = "{$label} must be at least {$min}.";
        }
        
        return $this;
    }

    /**
     * Validate maximum value (for numbers).
     *
     * @param string $field Field name
     * @param mixed $value Value to validate
     * @param float|int $max Maximum value
     * @param string|null $label Human-readable label
     * @return self
     */
    public function max(string $field, mixed $value, float|int $max, ?string $label = null): self
    {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if ($value !== null && $value !== '' && is_numeric($value) && (float)$value > $max) {
            $this->errors[$field] = "{$label} must be at most {$max}.";
        }
        
        return $this;
    }

    /**
     * Validate between range (for numbers).
     *
     * @param string $field Field name
     * @param mixed $value Value to validate
     * @param float|int $min Minimum value
     * @param float|int $max Maximum value
     * @param string|null $label Human-readable label
     * @return self
     */
    public function between(string $field, mixed $value, float|int $min, float|int $max, ?string $label = null): self
    {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if ($value !== null && $value !== '' && is_numeric($value)) {
            $val = (float)$value;
            if ($val < $min || $val > $max) {
                $this->errors[$field] = "{$label} must be between {$min} and {$max}.";
            }
        }
        
        return $this;
    }

    /**
     * Validate minimum string length.
     *
     * @param string $field Field name
     * @param string|null $value String to validate
     * @param int $length Minimum length
     * @param string|null $label Human-readable label
     * @return self
     */
    public function minLength(string $field, ?string $value, int $length, ?string $label = null): self
    {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if ($value !== null && mb_strlen($value) < $length) {
            $this->errors[$field] = "{$label} must be at least {$length} characters.";
        }
        
        return $this;
    }

    /**
     * Validate maximum string length.
     *
     * @param string $field Field name
     * @param string|null $value String to validate
     * @param int $length Maximum length
     * @param string|null $label Human-readable label
     * @return self
     */
    public function maxLength(string $field, ?string $value, int $length, ?string $label = null): self
    {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if ($value !== null && mb_strlen($value) > $length) {
            $this->errors[$field] = "{$label} must be at most {$length} characters.";
        }
        
        return $this;
    }

    /**
     * Validate date format.
     *
     * @param string $field Field name
     * @param string|null $value Date string to validate
     * @param string $format Expected format (default Y-m-d)
     * @param string|null $label Human-readable label
     * @return self
     */
    public function date(string $field, ?string $value, string $format = 'Y-m-d', ?string $label = null): self
    {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if ($value !== null && $value !== '') {
            $d = \DateTime::createFromFormat($format, $value);
            if (!$d || $d->format($format) !== $value) {
                $this->errors[$field] = "{$label} must be a valid date.";
            }
        }
        
        return $this;
    }

    /**
     * Validate date is in the future.
     *
     * @param string $field Field name
     * @param string|null $value Date string to validate
     * @param string|null $label Human-readable label
     * @return self
     */
    public function futureDate(string $field, ?string $value, ?string $label = null): self
    {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if ($value !== null && $value !== '') {
            $ts = strtotime($value);
            if ($ts !== false && $ts <= strtotime('today')) {
                $this->errors[$field] = "{$label} must be a future date.";
            }
        }
        
        return $this;
    }

    /**
     * Validate date is in the past.
     *
     * @param string $field Field name
     * @param string|null $value Date string to validate
     * @param string|null $label Human-readable label
     * @return self
     */
    public function pastDate(string $field, ?string $value, ?string $label = null): self
    {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if ($value !== null && $value !== '') {
            $ts = strtotime($value);
            if ($ts !== false && $ts >= strtotime('tomorrow')) {
                $this->errors[$field] = "{$label} must be a past date.";
            }
        }
        
        return $this;
    }

    /**
     * Validate value is in allowed list.
     *
     * @param string $field Field name
     * @param mixed $value Value to validate
     * @param array<int|string, mixed> $allowed Allowed values
     * @param string|null $label Human-readable label
     * @return self
     */
    public function in(string $field, mixed $value, array $allowed, ?string $label = null): self
    {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if ($value !== null && $value !== '' && !in_array($value, $allowed, true)) {
            $this->errors[$field] = "{$label} must be one of: " . implode(', ', $allowed) . ".";
        }
        
        return $this;
    }

    /**
     * Validate value matches a regex pattern.
     *
     * @param string $field Field name
     * @param string|null $value Value to validate
     * @param string $pattern Regex pattern
     * @param string|null $label Human-readable label
     * @param string|null $message Custom error message
     * @return self
     */
    public function regex(string $field, ?string $value, string $pattern, ?string $label = null, ?string $message = null): self
    {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if ($value !== null && $value !== '' && !preg_match($pattern, $value)) {
            $this->errors[$field] = $message ?? "{$label} format is invalid.";
        }
        
        return $this;
    }

    /**
     * Validate phone number format.
     *
     * @param string $field Field name
     * @param string|null $value Phone number to validate
     * @param string|null $label Human-readable label
     * @return self
     */
    public function phone(string $field, ?string $value, ?string $label = null): self
    {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        // Basic phone validation: allows digits, spaces, dashes, parentheses, and plus sign
        if ($value !== null && $value !== '') {
            $cleaned = preg_replace('/[\s\-\(\)]+/', '', $value);
            if (!preg_match('/^\+?[0-9]{7,15}$/', $cleaned)) {
                $this->errors[$field] = "{$label} must be a valid phone number.";
            }
        }
        
        return $this;
    }

    /**
     * Validate password strength.
     *
     * @param string $field Field name
     * @param string|null $value Password to validate
     * @param int $minLength Minimum length (default 8)
     * @param bool $requireMixed Require mixed case (default true)
     * @param bool $requireNumber Require number (default true)
     * @param string|null $label Human-readable label
     * @return self
     */
    public function password(
        string $field,
        ?string $value,
        int $minLength = 8,
        bool $requireMixed = true,
        bool $requireNumber = true,
        ?string $label = null
    ): self {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if ($value === null || $value === '') {
            return $this;
        }
        
        if (mb_strlen($value) < $minLength) {
            $this->errors[$field] = "{$label} must be at least {$minLength} characters.";
            return $this;
        }
        
        if ($requireMixed && (!preg_match('/[a-z]/', $value) || !preg_match('/[A-Z]/', $value))) {
            $this->errors[$field] = "{$label} must contain both uppercase and lowercase letters.";
            return $this;
        }
        
        if ($requireNumber && !preg_match('/[0-9]/', $value)) {
            $this->errors[$field] = "{$label} must contain at least one number.";
            return $this;
        }
        
        return $this;
    }

    /**
     * Validate two fields match (e.g., password confirmation).
     *
     * @param string $field Field name
     * @param mixed $value First value
     * @param mixed $confirmValue Confirmation value
     * @param string|null $label Human-readable label
     * @return self
     */
    public function confirmed(string $field, mixed $value, mixed $confirmValue, ?string $label = null): self
    {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if ($value !== $confirmValue) {
            $this->errors[$field] = "{$label} confirmation does not match.";
        }
        
        return $this;
    }

    /**
     * Validate currency code (3 uppercase letters).
     *
     * @param string $field Field name
     * @param string|null $value Currency code to validate
     * @param string|null $label Human-readable label
     * @return self
     */
    public function currency(string $field, ?string $value, ?string $label = null): self
    {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if ($value !== null && $value !== '' && !preg_match('/^[A-Z]{3}$/', $value)) {
            $this->errors[$field] = "{$label} must be a valid 3-letter currency code.";
        }
        
        return $this;
    }

    // ─── Static Factory Methods ──────────────────────────────────────────────

    /**
     * Create a new ValidationService instance.
     *
     * @return self
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * Sanitize a string for safe output.
     *
     * @param string|null $value Value to sanitize
     * @return string
     */
    public static function sanitize(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize for SQL LIKE query (escape % and _).
     *
     * @param string $value Value to escape
     * @return string
     */
    public static function escapeLike(string $value): string
    {
        return addcslashes($value, '%_');
    }
}
