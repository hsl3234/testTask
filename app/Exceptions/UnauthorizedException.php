<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Thrown when the caller is missing or presents invalid credentials.
 */
final class UnauthorizedException extends HttpException
{
    /**
     * @param string $message Reason visible to API consumers.
     */
    public function __construct(string $message = 'Unauthorized')
    {
        parent::__construct(401, $message);
    }
}
