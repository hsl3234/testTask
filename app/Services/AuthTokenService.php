<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\UnauthorizedException;
use App\Exceptions\ValidationException;
use App\Http\RequestKeys;
use App\Repositories\ApiTokenRepository;
use App\Repositories\RefreshTokenRepository;
use App\Repositories\UserRepository;

/**
 * Login + refresh-token rotation orchestration.
 *
 * Issues opaque access/refresh string pairs. Access lifetime is stored in
 * `api_tokens.expires_at`; refresh lifetime in `refresh_tokens.expires_at`.
 * Refresh is single-use: the previous row is deleted on every successful
 * `refresh` and replaced with a freshly generated one.
 */
final class AuthTokenService
{
    private const DEFAULT_ACCESS_TTL = 900;
    private const DEFAULT_REFRESH_TTL = 1209600;

    public function __construct(
        private readonly UserRepository $users,
        private readonly ApiTokenRepository $accessTokens,
        private readonly RefreshTokenRepository $refreshTokens,
    ) {
    }

    /**
     * Authenticate a user with login/password and return a fresh token pair.
     *
     * @param array<string, mixed> $input Request body (camelCase or snake_case).
     *
     * @throws ValidationException   When required fields are missing.
     * @throws UnauthorizedException When credentials do not match.
     *
     * @return array{accessToken: string, refreshToken: string, tokenType: string, expiresIn: int}
     */
    public function login(array $input): array
    {
        $input = RequestKeys::mergeJsonInput($input);

        $errors = [];
        $login = isset($input['login']) && is_string($input['login']) ? trim($input['login']) : '';
        $password = isset($input['password']) && is_string($input['password']) ? $input['password'] : '';
        if ($login === '') {
            $errors['login'] = 'is required';
        }
        if ($password === '') {
            $errors['password'] = 'is required';
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $user = $this->users->findByLogin($login);
        if ($user === null || !password_verify($password, (string) $user->password_hash)) {
            throw new UnauthorizedException('Invalid login or password');
        }

        return $this->issuePair((int) $user->id);
    }

    /**
     * Exchange a refresh token for a new pair (the previous one is consumed).
     *
     * @param array<string, mixed> $input Request body (camelCase or snake_case).
     *
     * @throws ValidationException   When `refreshToken` is missing.
     * @throws UnauthorizedException When the refresh token is unknown / expired.
     *
     * @return array{accessToken: string, refreshToken: string, tokenType: string, expiresIn: int}
     */
    public function refresh(array $input): array
    {
        $input = RequestKeys::mergeJsonInput($input);

        $token = isset($input['refresh_token']) && is_string($input['refresh_token'])
            ? trim($input['refresh_token'])
            : '';
        if ($token === '') {
            throw new ValidationException(['refresh_token' => 'is required']);
        }

        $row = $this->refreshTokens->findActive($token);
        if ($row === null) {
            throw new UnauthorizedException('Invalid or expired refresh token');
        }

        $this->refreshTokens->deleteByToken($token);
        return $this->issuePair((int) $row->user_id);
    }

    /**
     * Generate, persist and return a fresh access/refresh pair for a user.
     *
     * @return array{accessToken: string, refreshToken: string, tokenType: string, expiresIn: int}
     */
    private function issuePair(int $userId): array
    {
        $accessTtl = $this->envInt('ACCESS_TOKEN_TTL', self::DEFAULT_ACCESS_TTL);
        $refreshTtl = $this->envInt('REFRESH_TOKEN_TTL', self::DEFAULT_REFRESH_TTL);

        $access = bin2hex(random_bytes(32));
        $refresh = bin2hex(random_bytes(32));
        $now = time();

        $this->accessTokens->createForUser(
            $userId,
            $access,
            gmdate('Y-m-d H:i:s', $now + $accessTtl),
        );
        $this->refreshTokens->create(
            $userId,
            $refresh,
            gmdate('Y-m-d H:i:s', $now + $refreshTtl),
        );

        return [
            'accessToken'  => $access,
            'refreshToken' => $refresh,
            'tokenType'    => 'Bearer',
            'expiresIn'    => $accessTtl,
        ];
    }

    /**
     * Read a positive int from the environment, falling back to a default.
     */
    private function envInt(string $name, int $default): int
    {
        $raw = getenv($name);
        if ($raw === false || $raw === '') {
            return $default;
        }
        $value = (int) $raw;
        return $value > 0 ? $value : $default;
    }
}
