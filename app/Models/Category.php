<?php

declare(strict_types=1);

namespace App\Models;

use Phalcon\Mvc\Model;

/**
 * ORM model for the `categories` table.
 *
 * Categories form a tree via `parent_id`; the materialized `path` stores the
 * slash-delimited chain of ancestor ids (e.g. `/1/2/3/`) to make subtree
 * lookups a single indexed prefix scan.
 *
 * @property int         $id
 * @property int|null    $parent_id
 * @property string      $name
 * @property string      $path
 * @property string      $created_at
 * @property string      $updated_at
 */
final class Category extends Model
{
    /** @var int */
    public $id;

    /** @var int|null */
    public $parent_id;

    /** @var string */
    public $name;

    /** @var string */
    public $path;

    /** @var string */
    public $created_at;

    /** @var string */
    public $updated_at;

    /**
     * @return void
     */
    public function initialize(): void
    {
        $this->setSource('categories');
    }
}
