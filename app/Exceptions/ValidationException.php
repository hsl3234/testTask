<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Http\RequestKeys;

/**
 * Thrown on input validation failures (HTTP 422).
 */
final class ValidationException extends HttpException
{
    /**
     * @param array<string, string> $errors  Map of field name to error (snake_case; exposed as camelCase in JSON).
     * @param string                $message Top-level summary message.
     */
    public function __construct(array $errors, string $message = 'Validation failed')
    {
        $camel = [];
        foreach ($errors as $field => $text) {
            $camel[RequestKeys::toCamelField($field)] = $text;
        }
        parent::__construct(422, $message, ['errors' => $camel]);
    }
}
