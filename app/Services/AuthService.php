<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\UnauthorizedException;
use App\Repositories\ApiTokenRepository;

/**
 * Bearer-token authentication service.
 */
final class AuthService
{
    /**
     * @param ApiTokenRepository $tokens Repository used to resolve token strings.
     */
    public function __construct(private readonly ApiTokenRepository $tokens)
    {
    }

    /**
     * Validate a raw `Authorization` header value.
     *
     * @param string|null $authorizationHeader Full header, e.g. `Bearer abc123`.
     *
     * @throws UnauthorizedException When the header is missing, malformed or unknown.
     *
     * @return void
     */
    public function assertBearer(?string $authorizationHeader): void
    {
        if ($authorizationHeader === null || $authorizationHeader === '') {
            throw new UnauthorizedException('Missing Authorization header');
        }

        if (!preg_match('/^Bearer\s+(.+)$/i', trim($authorizationHeader), $m)) {
            throw new UnauthorizedException('Malformed Authorization header');
        }

        $token = trim($m[1]);
        if ($token === '') {
            throw new UnauthorizedException('Empty bearer token');
        }

        if ($this->tokens->findByToken($token) === null) {
            throw new UnauthorizedException('Invalid bearer token');
        }
    }
}
