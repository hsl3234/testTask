<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ApiToken;

/**
 * Data-access layer for `api_tokens`.
 */
final class ApiTokenRepository
{
    /**
     * Look up a token by its value.
     *
     * @param string $token Raw token string from the Authorization header.
     *
     * @return ApiToken|null Model instance, or null when unknown.
     */
    public function findByToken(string $token): ?ApiToken
    {
        /** @var ApiToken|null $result */
        $result = ApiToken::findFirst([
            'conditions' => 'token = :t:',
            'bind'       => ['t' => $token],
        ]);
        return $result ?: null;
    }
}
