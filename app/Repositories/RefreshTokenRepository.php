<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\RefreshToken;

/**
 * Data-access layer for `refresh_tokens` (single-use refresh rotation).
 */
final class RefreshTokenRepository
{
    /**
     * Persist a freshly generated refresh token.
     *
     * @param int    $userId  Owner.
     * @param string $token   Opaque token string.
     * @param string $expires Expiration timestamp (`Y-m-d H:i:s`, UTC).
     */
    public function create(int $userId, string $token, string $expires): void
    {
        $model = new RefreshToken();
        $model->user_id = $userId;
        $model->token = $token;
        $model->expires_at = $expires;
        $model->save();
    }

    /**
     * Look up a non-expired refresh token by value.
     *
     * @param string $token Raw token string from the request body.
     *
     * @return RefreshToken|null Model, or null when unknown / expired.
     */
    public function findActive(string $token): ?RefreshToken
    {
        /** @var RefreshToken|null $row */
        $row = RefreshToken::findFirst([
            'conditions' => 'token = :token: AND expires_at > :now:',
            'bind'       => [
                'token' => $token,
                'now'   => gmdate('Y-m-d H:i:s'),
            ],
        ]);
        return $row ?: null;
    }

    /**
     * Delete a refresh row by token value (used during rotation / logout).
     */
    public function deleteByToken(string $token): void
    {
        /** @var RefreshToken|null $row */
        $row = RefreshToken::findFirst([
            'conditions' => 'token = :token:',
            'bind'       => ['token' => $token],
        ]);
        if ($row !== null) {
            $row->delete();
        }
    }
}
