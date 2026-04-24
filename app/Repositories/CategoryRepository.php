<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Category;
use Phalcon\Di\Di;

/**
 * Data-access layer for `categories`.
 *
 * Uses a materialized-path strategy (`path` column like `/1/2/3/`) so that
 * subtree queries reduce to a single indexed `LIKE '/1/2/%'` scan.
 */
final class CategoryRepository
{
    /**
     * Fetch every category ordered by path (stable, human-friendly tree order).
     *
     * @return list<array<string, mixed>> All categories with id, parent_id, name, path.
     */
    public function findAll(): array
    {
        $sql = 'SELECT id, parent_id, name, path FROM categories ORDER BY path ASC, name ASC';
        return $this->fetchAll($sql, []);
    }

    /**
     * Fetch a category by id.
     *
     * @param int $id Category id.
     *
     * @return Category|null Phalcon model or null.
     */
    public function findById(int $id): ?Category
    {
        /** @var Category|null $category */
        $category = Category::findFirst(['conditions' => 'id = :id:', 'bind' => ['id' => $id]]);
        return $category ?: null;
    }

    /**
     * Collect the ids of a category and all of its descendants.
     *
     * @param int $rootId Root category id.
     *
     * @return list<int> Ids of the subtree, including the root; empty if root does not exist.
     */
    public function findSubtreeIds(int $rootId): array
    {
        $root = $this->findById($rootId);
        if ($root === null) {
            return [];
        }

        $rows = $this->fetchAll(
            'SELECT id FROM categories WHERE path LIKE :prefix',
            ['prefix' => $root->path . '%'],
        );

        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (int) $row['id'];
        }
        return $ids;
    }

    /**
     * Create a new category and compute its materialized path.
     *
     * @param string   $name     Category display name.
     * @param int|null $parentId Parent id, or null for a root category.
     *
     * @return Category The freshly persisted category.
     */
    public function create(string $name, ?int $parentId): Category
    {
        $category = new Category();
        $category->name = $name;
        $category->parent_id = $parentId;
        $category->path = '/';

        if (!$category->save()) {
            throw new \RuntimeException('Failed to create category');
        }

        $category->path = $this->computePath($parentId, (int) $category->id);
        if (!$category->save()) {
            throw new \RuntimeException('Failed to update category path');
        }
        return $category;
    }

    /**
     * Update a category's name or move it under a different parent.
     *
     * Moving a subtree rewrites descendants' paths to reflect the new hierarchy.
     *
     * @param Category $category  Persisted category to modify.
     * @param string|null $name    New name, or null to leave unchanged.
     * @param int|null|false $parentId New parent id (`false` = do not change, `null` = make root).
     *
     * @return Category The updated category.
     */
    public function update(Category $category, ?string $name, int|null|false $parentId): Category
    {
        if ($name !== null) {
            $category->name = $name;
        }

        $pathChanged = false;
        if ($parentId !== false && $parentId !== $category->parent_id) {
            $category->parent_id = $parentId;
            $oldPath = $category->path;
            $newPath = $this->computePath($parentId, (int) $category->id);
            $category->path = $newPath;
            $pathChanged = $oldPath !== $newPath;

            if (!$category->save()) {
                throw new \RuntimeException('Failed to update category');
            }

            if ($pathChanged) {
                /** @var \Phalcon\Db\Adapter\Pdo\AbstractPdo $db */
                $db = Di::getDefault()->getShared('db');
                $db->execute(
                    'UPDATE categories SET path = REPLACE(path, :old, :new) WHERE path LIKE :prefix AND id <> :id',
                    [
                        'old'    => $oldPath,
                        'new'    => $newPath,
                        'prefix' => $oldPath . '%',
                        'id'     => $category->id,
                    ],
                );
            }
            return $category;
        }

        if (!$category->save()) {
            throw new \RuntimeException('Failed to update category');
        }
        return $category;
    }

    /**
     * Delete a category row.
     *
     * Callers must ensure the category has no children or products first.
     *
     * @param Category $category Persisted category to delete.
     *
     * @return bool True on success.
     */
    public function delete(Category $category): bool
    {
        return (bool) $category->delete();
    }

    /**
     * Count direct children of a category.
     *
     * @param int $parentId Parent category id.
     *
     * @return int Number of direct children.
     */
    public function countChildren(int $parentId): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS c FROM categories WHERE parent_id = :id',
            ['id' => $parentId],
        );
        return (int) ($row['c'] ?? 0);
    }

    /**
     * Compute a materialized path `/…/id/` given a parent id and the category id itself.
     *
     * @param int|null $parentId Parent category id, or null for a root category.
     * @param int      $selfId   Category id being positioned.
     *
     * @return string The materialized path (always ends with a trailing slash).
     */
    private function computePath(?int $parentId, int $selfId): string
    {
        if ($parentId === null) {
            return "/{$selfId}/";
        }
        $parent = $this->findById($parentId);
        if ($parent === null) {
            return "/{$selfId}/";
        }
        return $parent->path . $selfId . '/';
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
     * @return array<string, mixed>|null Single row, or null when empty.
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
