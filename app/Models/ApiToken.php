<?php

declare(strict_types=1);

namespace App\Models;

use Phalcon\Mvc\Model;

/**
 * ORM model for the `api_tokens` table.
 *
 * @property int    $id
 * @property string $token
 * @property string $name
 * @property string $created_at
 */
final class ApiToken extends Model
{
    /** @var int */
    public $id;

    /** @var string */
    public $token;

    /** @var string */
    public $name;

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
