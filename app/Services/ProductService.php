<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Http\RequestKeys;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;

/**
 * Application service for products.
 *
 * Handles paginated listing with filters, aggregate computation over the same
 * filter set restricted to in-stock products, and CRUD orchestration.
 */
final class ProductService
{
    private const DEFAULT_PER_PAGE = 20;
    private const MAX_PER_PAGE = 100;

    /**
     * @param ProductRepository  $products   Product repository.
     * @param CategoryRepository $categories Category repository (for subtree resolution / FK checks).
     */
    public function __construct(
        private readonly ProductRepository $products,
        private readonly CategoryRepository $categories,
    ) {
    }

    /**
     * Paginate products and compute aggregates consistent with the same filters.
     *
     * The aggregate block always restricts to `in_stock = true`. If the caller
     * explicitly requests `in_stock=false`, the aggregates are empty (count 0,
     * sum 0) — see README for details.
     *
     * @param array{
     *     category_id?: mixed,
     *     in_stock?: mixed,
     *     page?: mixed,
     *     per_page?: mixed
     * } $query Raw query parameters (as provided by the HTTP layer).
     *
     * @throws ValidationException On invalid query parameters.
     * @throws NotFoundException   When `category_id` refers to an unknown category.
     *
     * @return array{
     *     data: list<array<string, mixed>>,
     *     meta: array{
     *         page: int,
     *         perPage: int,
     *         total: int,
     *         aggregates: array{inStockCount: int, inStockTotalPrice: string}
     *     }
     * } Response envelope ready for JSON serialization.
     */
    public function paginate(array $query): array
    {
        $query = RequestKeys::mergeQueryInput($query);
        $page = $this->positiveInt($query['page'] ?? 1, 'page', 1);
        $perPage = $this->positiveInt($query['per_page'] ?? self::DEFAULT_PER_PAGE, 'per_page', self::DEFAULT_PER_PAGE);
        if ($perPage > self::MAX_PER_PAGE) {
            $perPage = self::MAX_PER_PAGE;
        }

        $filters = $this->buildFilters($query);

        $result = $this->products->paginate($filters, $page, $perPage);
        $aggregates = $this->products->aggregateInStock($filters);

        return [
            'data' => array_map([$this, 'presentRow'], $result['items']),
            'meta' => [
                'page'     => $page,
                'perPage'  => $perPage,
                'total'    => $result['total'],
                'aggregates' => [
                    'inStockCount'     => $aggregates['count'],
                    'inStockTotalPrice' => $aggregates['total_price'],
                ],
            ],
        ];
    }

    /**
     * Fetch a single product by id.
     *
     * @param int $id Product id.
     *
     * @throws NotFoundException When no such product exists.
     *
     * @return array<string, mixed> Representation of the product.
     */
    public function find(int $id): array
    {
        $row = $this->products->findById($id);
        if ($row === null) {
            throw new NotFoundException('Product not found');
        }
        return $this->presentRow($row);
    }

    /**
     * Create a new product.
     *
     * @param array<string, mixed> $input Raw request payload.
     *
     * @throws ValidationException On invalid input.
     *
     * @return array<string, mixed> Created product representation.
     */
    public function create(array $input): array
    {
        $data = $this->validate($input, create: true);
        $id = $this->products->insert($data);
        return $this->find($id);
    }

    /**
     * Update an existing product.
     *
     * @param int                  $id    Product id.
     * @param array<string, mixed> $input Partial request payload.
     *
     * @throws NotFoundException   When the product does not exist.
     * @throws ValidationException On invalid input.
     *
     * @return array<string, mixed> Updated product representation.
     */
    public function update(int $id, array $input): array
    {
        if ($this->products->findById($id) === null) {
            throw new NotFoundException('Product not found');
        }
        $data = $this->validate($input, create: false);
        if ($data === []) {
            throw new ValidationException([], 'No fields provided');
        }
        $this->products->update($id, $data);
        return $this->find($id);
    }

    /**
     * Delete a product.
     *
     * @param int $id Product id.
     *
     * @throws NotFoundException When no such product exists.
     *
     * @return void
     */
    public function delete(int $id): void
    {
        if (!$this->products->delete($id)) {
            throw new NotFoundException('Product not found');
        }
    }

    /**
     * Count the products that belong directly to a given category id.
     *
     * @param int $categoryId Category id.
     *
     * @return int Number of products.
     */
    public function countByCategory(int $categoryId): int
    {
        return $this->products->countByCategory($categoryId);
    }

    /**
     * Build the repository-level filter map from raw query parameters.
     *
     * @param array<string, mixed> $query Query parameters from the HTTP layer.
     *
     * @throws ValidationException On invalid filter values.
     * @throws NotFoundException   When `category_id` does not exist.
     *
     * @return array{category_ids?: list<int>, in_stock?: bool} Filter map for the repository.
     */
    private function buildFilters(array $query): array
    {
        $filters = [];

        if (isset($query['category_id']) && $query['category_id'] !== '') {
            if (!is_numeric($query['category_id']) || (int) $query['category_id'] <= 0) {
                throw new ValidationException(['category_id' => 'must be a positive integer']);
            }
            $ids = $this->categories->findSubtreeIds((int) $query['category_id']);
            if ($ids === []) {
                throw new NotFoundException('Category not found');
            }
            $filters['category_ids'] = $ids;
        }

        if (isset($query['in_stock']) && $query['in_stock'] !== '') {
            $bool = $this->parseBool($query['in_stock']);
            if ($bool === null) {
                throw new ValidationException(['in_stock' => 'must be 0/1/true/false']);
            }
            $filters['in_stock'] = $bool;
        }

        return $filters;
    }

    /**
     * Validate product attributes for create/update.
     *
     * @param array<string, mixed> $input  Raw input.
     * @param bool                 $create True for create (all required); false for update (partial).
     *
     * @throws ValidationException On invalid fields.
     *
     * @return array<string, mixed> Normalized attribute map to pass to the repository.
     */
    private function validate(array $input, bool $create): array
    {
        $input = RequestKeys::mergeJsonInput($input);
        $errors = [];
        $out = [];

        $requireOr = function (string $key, callable $validator) use (&$input, &$errors, &$out, $create): void {
            if (!array_key_exists($key, $input)) {
                if ($create) {
                    $errors[$key] = 'is required';
                }
                return;
            }
            $validator($input[$key], $errors, $out);
        };

        $requireOr('name', function ($v, array &$errors, array &$out): void {
            if (!is_string($v) || trim($v) === '' || mb_strlen(trim($v)) > 255) {
                $errors['name'] = 'must be a non-empty string up to 255 characters';
                return;
            }
            $out['name'] = trim($v);
        });

        $requireOr('content', function ($v, array &$errors, array &$out): void {
            if ($v !== null && !is_string($v)) {
                $errors['content'] = 'must be a string or null';
                return;
            }
            $out['content'] = $v === null ? null : (string) $v;
        });

        $requireOr('price', function ($v, array &$errors, array &$out): void {
            if (!is_numeric($v) || (float) $v < 0) {
                $errors['price'] = 'must be a non-negative number';
                return;
            }
            $out['price'] = number_format((float) $v, 2, '.', '');
        });

        $requireOr('in_stock', function ($v, array &$errors, array &$out): void {
            $bool = $this->parseBool($v);
            if ($bool === null) {
                $errors['in_stock'] = 'must be a boolean';
                return;
            }
            $out['in_stock'] = $bool;
        });

        $requireOr('category_id', function ($v, array &$errors, array &$out): void {
            if (!is_numeric($v) || (int) $v <= 0) {
                $errors['category_id'] = 'must be a positive integer';
                return;
            }
            $categoryId = (int) $v;
            if ($this->categories->findById($categoryId) === null) {
                $errors['category_id'] = 'category does not exist';
                return;
            }
            $out['category_id'] = $categoryId;
        });

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
        return $out;
    }

    /**
     * @param mixed  $raw     Query value.
     * @param string $field   Field name for error messages.
     * @param int    $default Default returned when value is null/empty.
     *
     * @throws ValidationException When non-empty value is not a positive integer.
     *
     * @return int Validated positive integer (>= 1) or default.
     */
    private function positiveInt(mixed $raw, string $field, int $default): int
    {
        if ($raw === null || $raw === '') {
            return $default;
        }
        if (!is_numeric($raw) || (int) $raw <= 0) {
            throw new ValidationException([$field => 'must be a positive integer']);
        }
        return (int) $raw;
    }

    /**
     * @param mixed $raw Incoming value.
     *
     * @return bool|null Parsed boolean, or null if the value cannot be coerced.
     */
    private function parseBool(mixed $raw): ?bool
    {
        if (is_bool($raw)) {
            return $raw;
        }
        if (is_int($raw)) {
            return $raw === 1 ? true : ($raw === 0 ? false : null);
        }
        if (is_string($raw)) {
            $s = strtolower(trim($raw));
            if (in_array($s, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }
            if (in_array($s, ['0', 'false', 'no', 'off'], true)) {
                return false;
            }
        }
        return null;
    }

    /**
     * @param array<string, mixed> $row Repository row.
     *
     * @return array<string, mixed> JSON-friendly product representation.
     */
    private function presentRow(array $row): array
    {
        return [
            'id'      => (int) $row['id'],
            'name'    => (string) $row['name'],
            'content' => $row['content'] !== null ? (string) $row['content'] : null,
            'price'   => number_format((float) $row['price'], 2, '.', ''),
            'inStock' => (bool) $row['in_stock'],
            'category' => [
                'id'   => (int) $row['category_id'],
                'name' => (string) ($row['category_name'] ?? ''),
                'path' => (string) ($row['category_path'] ?? ''),
            ],
        ];
    }
}
