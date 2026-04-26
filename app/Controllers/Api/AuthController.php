<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Services\AuthTokenService;
use OpenApi\Attributes as OA;
use Phalcon\Http\ResponseInterface;
use Throwable;

/**
 * HTTP controller for `/api/auth/*` (login + refresh-token rotation).
 */
#[OA\Tag(name: 'Auth')]
final class AuthController extends BaseApiController
{
    /**
     * Issue an access/refresh pair for a valid login/password.
     *
     * @return ResponseInterface JSON token pair on success, 401 / 422 otherwise.
     */
    #[OA\Post(
        path: '/api/auth/login',
        summary: 'Authenticate with login and password',
        security: [],
        tags: ['Auth'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/AuthLoginRequest')),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/AuthTokenResponse')),
            new OA\Response(response: 401, description: 'Invalid credentials', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function loginAction(): ResponseInterface
    {
        try {
            /** @var AuthTokenService $service */
            $service = $this->di->getShared(AuthTokenService::class);
            return $this->respond($service->login($this->jsonBody()));
        } catch (Throwable $e) {
            return $this->respondException($e);
        }
    }

    /**
     * Exchange a refresh token for a new pair (single-use rotation).
     *
     * @return ResponseInterface JSON token pair on success, 401 / 422 otherwise.
     */
    #[OA\Post(
        path: '/api/auth/refresh',
        summary: 'Rotate refresh token for a new access/refresh pair',
        security: [],
        tags: ['Auth'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/AuthRefreshRequest')),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/AuthTokenResponse')),
            new OA\Response(response: 401, description: 'Invalid or expired refresh', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function refreshAction(): ResponseInterface
    {
        try {
            /** @var AuthTokenService $service */
            $service = $this->di->getShared(AuthTokenService::class);
            return $this->respond($service->refresh($this->jsonBody()));
        } catch (Throwable $e) {
            return $this->respondException($e);
        }
    }
}
