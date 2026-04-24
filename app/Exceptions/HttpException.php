<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Base class for exceptions that must be rendered as an HTTP error response.
 */
class HttpException extends RuntimeException
{
    /**
     * @param int                  $statusCode HTTP status code to send to the client.
     * @param string               $message    Human-readable error message for API consumers.
     * @param array<string, mixed> $details    Structured details (e.g. field-specific validation errors).
     * @param Throwable|null       $previous   Underlying cause, if any.
     */
    public function __construct(
        private readonly int $statusCode,
        string $message,
        private readonly array $details = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * @return int HTTP status code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, mixed> Structured details.
     */
    public function getDetails(): array
    {
        return $this->details;
    }
}
