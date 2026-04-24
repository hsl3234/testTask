<?php

declare(strict_types=1);

namespace App\Http;

use App\Exceptions\HttpException;
use Phalcon\Http\Response;
use Throwable;

/**
 * Converts any uncaught {@see Throwable} into a JSON error response.
 */
final class ErrorHandler
{
    /**
     * @param Throwable $e Exception to render.
     *
     * @return Response JSON response with the matching HTTP status code.
     */
    public function handle(Throwable $e): Response
    {
        if ($e instanceof HttpException) {
            return JsonResponse::error($e->getStatusCode(), $e->getMessage(), $e->getDetails());
        }

        $isDev = (getenv('APP_ENV') ?: 'prod') === 'dev';
        $details = $isDev ? ['exception' => $e::class, 'trace' => $e->getMessage()] : [];
        return JsonResponse::error(500, 'Internal Server Error', $details);
    }
}
