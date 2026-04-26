<?php

declare(strict_types=1);

namespace App\Models;

use Phalcon\Mvc\Model;

/**
 * ORM model for the `refresh_tokens` table (single-use refresh tokens).
 *
 * @property int    $id
 * @property int    $user_id
 * @property string $token
 * @property string $expires_at
 * @property string $created_at
 */
final class RefreshToken extends Model
{
    /** @var int */
    public $id;

    /** @var int */
    public $user_id;

    /** @var string */
    public $token;

    /** @var string */
    public $expires_at;

    /** @var string */
    public $created_at;

    /**
     * @return void
     */
    public function initialize(): void
    {
        $this->setSource('refresh_tokens');
    }
}
