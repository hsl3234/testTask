<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Category;
use App\Repositories\CategoryRepository;

/**
 * Application service for categories.
 *
 * Encapsulates validation, hierarchy rules, and CRUD orchestration on top of
 * {@see CategoryRepository}, so controllers only deal with HTTP concerns.
 */
final class CategoryService
{
    /**
     * @param CategoryRepository $categories Underlying repository.
     */
    public function __construct(private readonly CategoryRepository $categories)
    {
    }

    /**
     * List all categories as a flat list (sorted by materialized path).
     *
     * @return list<array{id: int, parent_id: int|null, name: string, path: string}> All categories.
     */
    public function listFlat(): array
    {
        $rows = $this->categories->findAll();
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id'        => (int) $row['id'],
                'parent_id' => $row['parent_id'] !== null ? (int) $row['parent_id'] : null,
                'name'      => (string) $row['name'],
                'path'      => (string) $row['path'],
            ];
        }
        return $out;
    }

    /**
     * List all categories assembled into a nested tree.
     *
     * @return list<array<string, mixed>> Tree of root categories, each with a `children` array.
     */
    public function listTree(): array
    {
        $flat = $this->listFlat();
        $byId = [];
        foreach ($flat as $node) {
            $byId[$node['id']] = $node + ['children' => []];
        }
        $roots = [];
        foreach ($byId as $id => &$node) {
            $parentId = $node['parent_id'];
            if ($parentId !== null && isset($byId[$parentId])) {
                $byId[$parentId]['children'][] = &$node;
            } else {
                $roots[] = &$node;
            }
        }
        unset($node);
        return $roots;
    }

    /**
     * Return the ids of a category and all its descendants.
     *
     * @param int $rootId Root category id.
     *
     * @throws NotFoundException If the root does not exist.
     *
     * @return list<int> Ids in the subtree, including the root.
     */
    public function subtreeIds(int $rootId): array
    {
        $ids = $this->categories->findSubtreeIds($rootId);
        if ($ids === []) {
            throw new NotFoundException('Category not found');
        }
        return $ids;
    }

    /**
     * Create a new category (optionally under a parent).
     *
     * @param array{name?: mixed, parent_id?: mixed} $input Raw request payload.
     *
     * @throws ValidationException On missing/invalid fields or unknown parent.
     *
     * @return array<string, mixed> Representation of the created category.
     */
    public function create(array $input): array
    {
        $name = $this->validateName($input['name'] ?? null);
        $parentId = $this->validateParentId($input['parent_id'] ?? null, null);

        $category = $this->categories->create($name, $parentId);
        return $this->present($category);
    }

    /**
     * Update an existing category.
     *
     * @param int                                     $id    Category id.
     * @param array{name?: mixed, parent_id?: mixed} $input Partial payload.
     *
     * @throws NotFoundException  When the category does not exist.
     * @throws ValidationException On invalid fields.
     * @throws ConflictException  When the move would create a cycle.
     *
     * @return array<string, mixed> Representation of the updated category.
     */
    public function update(int $id, array $input): array
    {
        $category = $this->categories->findById($id);
        if ($category === null) {
            throw new NotFoundException('Category not found');
        }

        $name = array_key_exists('name', $input)
            ? $this->validateName($input['name'])
            : null;

        $parentId = false;
        if (array_key_exists('parent_id', $input)) {
            $parentId = $this->validateParentId($input['parent_id'], $id);
            if ($parentId !== null) {
                $this->assertNotDescendant($id, $parentId, $category->path);
            }
        }

        $updated = $this->categories->update($category, $name, $parentId);
        return $this->present($updated);
    }

    /**
     * Delete a category, refusing if it has children or products.
     *
     * @param int $id                   Category id.
     * @param int $productsOnCategory   Count of products directly attached to the category.
     *
     * @throws NotFoundException When the category does not exist.
     * @throws ConflictException When the category has children or products.
     *
     * @return void
     */
    public function delete(int $id, int $productsOnCategory): void
    {
        $category = $this->categories->findById($id);
        if ($category === null) {
            throw new NotFoundException('Category not found');
        }

        if ($this->categories->countChildren($id) > 0) {
            throw new ConflictException('Category has child categories');
        }

        if ($productsOnCategory > 0) {
            throw new ConflictException('Category has products');
        }

        $this->categories->delete($category);
    }

    /**
     * Assert that the candidate parent is not the category itself or one of its descendants.
     *
     * @param int    $selfId          Category being moved.
     * @param int    $candidateParent Proposed new parent id.
     * @param string $selfPath        Current materialized path of the category being moved.
     *
     * @throws ConflictException When the move would create a cycle.
     *
     * @return void
     */
    private function assertNotDescendant(int $selfId, int $candidateParent, string $selfPath): void
    {
        if ($candidateParent === $selfId) {
            throw new ConflictException('Category cannot be its own parent');
        }
        $parent = $this->categories->findById($candidateParent);
        if ($parent === null) {
            throw new ConflictException('Parent category not found');
        }
        if (str_starts_with($parent->path, $selfPath)) {
            throw new ConflictException('Cannot move a category under its own descendant');
        }
    }

    /**
     * Validate a name field.
     *
     * @param mixed $raw Incoming value.
     *
     * @throws ValidationException On missing/invalid value.
     *
     * @return string Trimmed, non-empty name.
     */
    private function validateName(mixed $raw): string
    {
        if (!is_string($raw)) {
            throw new ValidationException(['name' => 'must be a string']);
        }
        $name = trim($raw);
        if ($name === '' || mb_strlen($name) > 191) {
            throw new ValidationException(['name' => 'must be 1..191 characters']);
        }
        return $name;
    }

    /**
     * Validate a parent_id field.
     *
     * @param mixed    $raw      Incoming value (null, int-like, or invalid).
     * @param int|null $selfId   The id of the category being updated (to prevent self-parenting), or null on create.
     *
     * @throws ValidationException On invalid type or unknown parent.
     *
     * @return int|null Validated parent id (or null for root).
     */
    private function validateParentId(mixed $raw, ?int $selfId): ?int
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (!is_numeric($raw) || (int) $raw <= 0) {
            throw new ValidationException(['parent_id' => 'must be a positive integer or null']);
        }
        $parentId = (int) $raw;
        if ($selfId !== null && $parentId === $selfId) {
            throw new ValidationException(['parent_id' => 'cannot be the category itself']);
        }
        if ($this->categories->findById($parentId) === null) {
            throw new ValidationException(['parent_id' => 'parent category does not exist']);
        }
        return $parentId;
    }

    /**
     * @param Category $category Persisted category.
     *
     * @return array<string, mixed> JSON-ready representation.
     */
    private function present(Category $category): array
    {
        return [
            'id'        => (int) $category->id,
            'parent_id' => $category->parent_id !== null ? (int) $category->parent_id : null,
            'name'      => (string) $category->name,
            'path'      => (string) $category->path,
        ];
    }
}
