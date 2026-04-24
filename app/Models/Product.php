<?php

declare(strict_types=1);

namespace App\Models;

use Phalcon\Mvc\Model;

/**
 * ORM model for the `products` table.
 *
 * @property int    $id
 * @property int    $category_id
 * @property string $name
 * @property string $content
 * @property string $price
 * @property int    $in_stock
 * @property string $created_at
 * @property string $updated_at
 */
final class Product extends Model
{
    /** @var int */
    public $id;

    /** @var int */
    public $category_id;

    /** @var string */
    public $name;

    /** @var string|null */
    public $content;

    /** @var string */
    public $price;

    /** @var int */
    public $in_stock;

    /** @var string */
    public $created_at;

    /** @var string */
    public $updated_at;

    /**
     * @return void
     */
    public function initialize(): void
    {
        $this->setSource('products');
    }
}
