<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;

/**
 * Data-access layer for `users` (admin accounts).
 */
final class UserRepository
{
    /**
     * Look up a user by their unique login.
     *
     * @param string $login Login string (UNIQUE).
     *
     * @return User|null Model instance, or null when unknown.
     */
    public function findByLogin(string $login): ?User
    {
        /** @var User|null $user */
        $user = User::findFirst([
            'conditions' => 'login = :login:',
            'bind'       => ['login' => $login],
        ]);
        return $user ?: null;
    }

    /**
     * Look up a user by id.
     *
     * @param int $id User id.
     *
     * @return User|null Model instance, or null when unknown.
     */
    public function findById(int $id): ?User
    {
        /** @var User|null $user */
        $user = User::findFirst($id);
        return $user ?: null;
    }
}
