<?php

declare(strict_types=1);

namespace App\Models;

use Phalcon\Mvc\Model;

/**
 * ORM model for the `api_tokens` table.
 *
 * @property int         $id
 * @property int|null    $user_id
 * @property string      $token
 * @property string      $name
 * @property string|null $expires_at
 * @property string      $created_at
 */
final class ApiToken extends Model
{
    /** @var int */
    public $id;

    /** @var int|null */
    public $user_id;

    /** @var string */
    public $token;

    /** @var string */
    public $name;

    /** @var string|null */
    public $expires_at;

    /** @var string */
    public $created_at;

    /**
     * @return void
     */
    public function initialize(): void
    {
        $this->setSource('api_tokens');
    }
}
