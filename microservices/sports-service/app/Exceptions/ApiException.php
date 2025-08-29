<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class ApiException extends Exception
{
    protected $statusCode;
    protected $errorCode;
    protected $errors;

    public function __construct(
        string $message = 'An error occurred',
        int $statusCode = 400,
        string $errorCode = null,
        $errors = null,
        Exception $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        
        $this->statusCode = $statusCode;
        $this->errorCode = $errorCode;
        $this->errors = $errors;
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $this->getMessage(),
        ];

        if ($this->errorCode) {
            $response['error_code'] = $this->errorCode;
        }

        if ($this->errors) {
            $response['errors'] = $this->errors;
        }

        return response()->json($response, $this->statusCode);
    }

    /**
     * Get the status code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get the error code.
     */
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * Get the errors.
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Create a validation exception.
     */
    public static function validation(string $message = 'Validation failed', $errors = null): self
    {
        return new self($message, 422, 'VALIDATION_ERROR', $errors);
    }

    /**
     * Create a not found exception.
     */
    public static function notFound(string $message = 'Resource not found'): self
    {
        return new self($message, 404, 'NOT_FOUND');
    }

    /**
     * Create an unauthorized exception.
     */
    public static function unauthorized(string $message = 'Unauthorized'): self
    {
        return new self($message, 401, 'UNAUTHORIZED');
    }

    /**
     * Create a forbidden exception.
     */
    public static function forbidden(string $message = 'Forbidden'): self
    {
        return new self($message, 403, 'FORBIDDEN');
    }

    /**
     * Create an internal server error exception.
     */
    public static function internalError(string $message = 'Internal server error'): self
    {
        return new self($message, 500, 'INTERNAL_ERROR');
    }

    /**
     * Create a bad request exception.
     */
    public static function badRequest(string $message = 'Bad request'): self
    {
        return new self($message, 400, 'BAD_REQUEST');
    }

    /**
     * Create a conflict exception.
     */
    public static function conflict(string $message = 'Conflict'): self
    {
        return new self($message, 409, 'CONFLICT');
    }
}