<?php

declare(strict_types=1);

namespace App\Http;

use Phalcon\Http\Response;

/**
 * Small helper that converts PHP values into a well-formed JSON {@see Response}.
 */
final class JsonResponse
{
    /**
     * Build a JSON response.
     *
     * @param mixed $payload    Any JSON-serializable value.
     * @param int   $statusCode HTTP status code.
     *
     * @return Response Configured Phalcon response (not yet sent).
     */
    public static function create(mixed $payload, int $statusCode = 200): Response
    {
        $response = new Response();
        $response->setStatusCode($statusCode);
        $response->setContentType('application/json', 'UTF-8');
        $response->setJsonContent($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $response;
    }

    /**
     * Build a JSON error response in the `{ error: { message, details? } }` envelope.
     *
     * @param int                  $statusCode HTTP status code.
     * @param string               $message    Human-readable reason.
     * @param array<string, mixed> $details    Optional structured payload.
     *
     * @return Response Configured Phalcon response (not yet sent).
     */
    public static function error(int $statusCode, string $message, array $details = []): Response
    {
        $error = ['message' => $message];
        if ($details !== []) {
            $error += $details;
        }
        return self::create(['error' => $error], $statusCode);
    }
}
