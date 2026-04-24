<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Thrown when an operation would violate domain invariants (HTTP 409).
 */
final class ConflictException extends HttpException
{
    /**
     * @param string $message Reason for the conflict (e.g. "category has children").
     */
    public function __construct(string $message = 'Conflict')
    {
        parent::__construct(409, $message);
    }
}
