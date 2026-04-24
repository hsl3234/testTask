<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Product;
use Phalcon\Di\Di;

/**
 * Data-access layer for `products`.
 *
 * Centralizes the list / aggregate / CRUD queries used by {@see \App\Services\ProductService}
 * so that controllers stay free of raw SQL.
 */
final class ProductRepository
{
    /**
     * Paginated listing with optional filters.
     *
     * @param array{
     *     category_ids?: list<int>,
     *     in_stock?: bool
     * }   $filters  Filter map produced by the service.
     * @param int $page Page number, 1-based.
     * @param int $perPage Page size (>= 1).
     *
     * @return array{
     *     items: list<array<string, mixed>>,
     *     total: int
     * } Rows with joined category name/path and the total row count for the filter set.
     */
    public function paginate(array $filters, int $page, int $perPage): array
    {
        [$where, $binds] = $this->buildWhere($filters);
        $offset = max(0, ($page - 1) * $perPage);
        $perPage = max(1, $perPage);

        $sql = <<<SQL
            SELECT p.id, p.category_id, p.name, p.content, p.price, p.in_stock,
                   c.name AS category_name, c.path AS category_path
              FROM products p
              JOIN categories c ON c.id = p.category_id
             {$where}
             ORDER BY p.id ASC
             LIMIT {$perPage} OFFSET {$offset}
        SQL;

        $items = $this->fetchAll($sql, $binds);

        $countSql = "SELECT COUNT(*) AS c FROM products p {$where}";
        $countRow = $this->fetchOne($countSql, $binds);
        $total = (int) ($countRow['c'] ?? 0);

        return ['items' => $items, 'total' => $total];
    }

    /**
     * Aggregate count and total price over the same filter set, restricted to `in_stock = 1`.
     *
     * @param array{
     *     category_ids?: list<int>,
     *     in_stock?: bool
     * } $filters Filter map produced by the service.
     *
     * @return array{count: int, total_price: string} Aggregated values (total as fixed-precision string).
     */
    public function aggregateInStock(array $filters): array
    {
        if (isset($filters['in_stock']) && $filters['in_stock'] === false) {
            return ['count' => 0, 'total_price' => '0.00'];
        }

        $filters['in_stock'] = true;
        [$where, $binds] = $this->buildWhere($filters);

        $sql = <<<SQL
            SELECT COUNT(*) AS c, COALESCE(SUM(p.price), 0) AS s
              FROM products p
             {$where}
        SQL;

        $row = $this->fetchOne($sql, $binds);

        return [
            'count'       => (int) ($row['c'] ?? 0),
            'total_price' => number_format((float) ($row['s'] ?? 0), 2, '.', ''),
        ];
    }

    /**
     * Fetch a single product by id, including joined category data.
     *
     * @param int $id Product id.
     *
     * @return array<string, mixed>|null Row with category name/path, or null if not found.
     */
    public function findById(int $id): ?array
    {
        $sql = <<<SQL
            SELECT p.id, p.category_id, p.name, p.content, p.price, p.in_stock,
                   c.name AS category_name, c.path AS category_path
              FROM products p
              JOIN categories c ON c.id = p.category_id
             WHERE p.id = :id
        SQL;

        return $this->fetchOne($sql, ['id' => $id]) ?: null;
    }

    /**
     * Insert a product.
     *
     * @param array{
     *     category_id: int,
     *     name: string,
     *     content: string|null,
     *     price: string,
     *     in_stock: bool
     * } $data Normalized attributes.
     *
     * @return int The new product id.
     */
    public function insert(array $data): int
    {
        $product = new Product();
        $product->category_id = $data['category_id'];
        $product->name = $data['name'];
        $product->content = $data['content'];
        $product->price = $data['price'];
        $product->in_stock = $data['in_stock'] ? 1 : 0;

        if (!$product->save()) {
            throw new \RuntimeException('Failed to insert product');
        }
        return (int) $product->id;
    }

    /**
     * Update an existing product.
     *
     * @param int                                                                             $id   Product id to update.
     * @param array{category_id?: int, name?: string, content?: string|null, price?: string, in_stock?: bool} $data Partial attributes to overwrite.
     *
     * @return bool True if a row was updated; false if the product does not exist.
     */
    public function update(int $id, array $data): bool
    {
        $product = Product::findFirst(['conditions' => 'id = :id:', 'bind' => ['id' => $id]]);
        if (!$product) {
            return false;
        }

        foreach ($data as $key => $value) {
            if ($key === 'in_stock') {
                $product->in_stock = $value ? 1 : 0;
                continue;
            }
            $product->{$key} = $value;
        }

        if (!$product->save()) {
            throw new \RuntimeException('Failed to update product');
        }
        return true;
    }

    /**
     * Delete a product by id.
     *
     * @param int $id Product id.
     *
     * @return bool True if a row was deleted; false if no such product.
     */
    public function delete(int $id): bool
    {
        $product = Product::findFirst(['conditions' => 'id = :id:', 'bind' => ['id' => $id]]);
        if (!$product) {
            return false;
        }
        return (bool) $product->delete();
    }

    /**
     * Count how many products belong to a given category id (direct, non-recursive).
     *
     * @param int $categoryId Category id.
     *
     * @return int Number of products directly attached to the category.
     */
    public function countByCategory(int $categoryId): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS c FROM products WHERE category_id = :id',
            ['id' => $categoryId],
        );
        return (int) ($row['c'] ?? 0);
    }

    /**
     * Build the shared WHERE clause + bind values for the list and aggregate queries.
     *
     * @param array{category_ids?: list<int>, in_stock?: bool} $filters Filter map.
     *
     * @return array{0: string, 1: array<string, mixed>} The WHERE SQL fragment and bound parameters.
     */
    private function buildWhere(array $filters): array
    {
        $clauses = [];
        $binds = [];

        if (!empty($filters['category_ids'])) {
            $placeholders = [];
            foreach (array_values($filters['category_ids']) as $i => $cid) {
                $key = "cid{$i}";
                $placeholders[] = ':' . $key;
                $binds[$key] = $cid;
            }
            $clauses[] = 'p.category_id IN (' . implode(',', $placeholders) . ')';
        }

        if (isset($filters['in_stock'])) {
            $clauses[] = 'p.in_stock = :in_stock';
            $binds['in_stock'] = $filters['in_stock'] ? 1 : 0;
        }

        $where = $clauses === [] ? '' : 'WHERE ' . implode(' AND ', $clauses);
        return [$where, $binds];
    }

    /**
     * @param string               $sql   Raw SQL query with named placeholders.
     * @param array<string, mixed> $binds Named bind values.
     *
     * @return list<array<string, mixed>> Resulting rows as associative arrays.
     */
    private function fetchAll(string $sql, array $binds): array
    {
        /** @var \Phalcon\Db\Adapter\Pdo\AbstractPdo $db */
        $db = Di::getDefault()->getShared('db');
        $result = $db->query($sql, $binds);
        $result->setFetchMode(\Phalcon\Db\Enum::FETCH_ASSOC);
        return $result->fetchAll();
    }

    /**
     * @param string               $sql   Raw SQL query with named placeholders.
     * @param array<string, mixed> $binds Named bind values.
     *
     * @return array<string, mixed>|null Single row or null when empty.
     */
    private function fetchOne(string $sql, array $binds): ?array
    {
        /** @var \Phalcon\Db\Adapter\Pdo\AbstractPdo $db */
        $db = Di::getDefault()->getShared('db');
        $result = $db->query($sql, $binds);
        $result->setFetchMode(\Phalcon\Db\Enum::FETCH_ASSOC);
        $row = $result->fetch();
        return $row ?: null;
    }
}
