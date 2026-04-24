<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use OpenApi\Attributes as OA;
use Phalcon\Http\ResponseInterface;

/**
 * Unauthenticated liveness probe.
 */
final class HealthController extends BaseApiController
{
    /**
     * Return a simple 200 OK payload.
     *
     * @return ResponseInterface JSON `{ "status": "ok" }`.
     */
    #[OA\Get(
        path: '/api/health',
        summary: 'Liveness probe',
        security: [],
        tags: ['Meta'],
        responses: [
            new OA\Response(response: 200, description: 'OK'),
        ],
    )]
    public function indexAction(): ResponseInterface
    {
        return $this->respond(['status' => 'ok']);
    }
}
