<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ApiToken;

/**
 * Data-access layer for `api_tokens`.
 *
 * Combines short-lived access tokens (with `user_id` + `expires_at`) issued
 * by login/refresh and long-lived static keys (`user_id IS NULL`,
 * `expires_at IS NULL`) used by curl/demo scripts.
 */
final class ApiTokenRepository
{
    /**
     * Look up a non-expired token by value.
     *
     * @param string $token Raw token string from the Authorization header.
     *
     * @return ApiToken|null Model instance, or null when unknown / expired.
     */
    public function findActive(string $token): ?ApiToken
    {
        /** @var ApiToken|null $result */
        $result = ApiToken::findFirst([
            'conditions' => 'token = :t: AND (expires_at IS NULL OR expires_at > :now:)',
            'bind'       => [
                't'   => $token,
                'now' => gmdate('Y-m-d H:i:s'),
            ],
        ]);
        return $result ?: null;
    }

    /**
     * Create an access token bound to a user with a hard expiration.
     *
     * @param int    $userId  Owner of the token.
     * @param string $token   Opaque token string.
     * @param string $expires Expiration timestamp (`Y-m-d H:i:s`, UTC).
     * @param string $name    Logical name (e.g. `access`).
     */
    public function createForUser(int $userId, string $token, string $expires, string $name = 'access'): void
    {
        $model = new ApiToken();
        $model->user_id = $userId;
        $model->token = $token;
        $model->name = $name;
        $model->expires_at = $expires;
        $model->save();
    }

    /**
     * Delete a token row by value (used on logout / rotation).
     */
    public function deleteByToken(string $token): void
    {
        /** @var ApiToken|null $row */
        $row = ApiToken::findFirst([
            'conditions' => 'token = :t:',
            'bind'       => ['t' => $token],
        ]);
        if ($row !== null) {
            $row->delete();
        }
    }
}
