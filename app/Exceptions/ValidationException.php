<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Thrown on input validation failures (HTTP 422).
 */
final class ValidationException extends HttpException
{
    /**
     * @param array<string, string> $errors  Map of field name to error description.
     * @param string                $message Top-level summary message.
     */
    public function __construct(array $errors, string $message = 'Validation failed')
    {
        parent::__construct(422, $message, ['errors' => $errors]);
    }
}
