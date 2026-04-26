<?php

declare(strict_types=1);

namespace App\Models;

use Phalcon\Mvc\Model;

/**
 * ORM model for the `users` table (admin accounts).
 *
 * @property int    $id
 * @property string $login
 * @property string $password_hash
 * @property string $created_at
 * @property string $updated_at
 */
final class User extends Model
{
    /** @var int */
    public $id;

    /** @var string */
    public $login;

    /** @var string */
    public $password_hash;

    /** @var string */
    public $created_at;

    /** @var string */
    public $updated_at;

    /**
     * @return void
     */
    public function initialize(): void
    {
        $this->setSource('users');
    }
}
