<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Thrown when a requested resource does not exist.
 */
final class NotFoundException extends HttpException
{
    /**
     * @param string $message Human-readable description of the missing resource.
     */
    public function __construct(string $message = 'Resource not found')
    {
        parent::__construct(404, $message);
    }
}
