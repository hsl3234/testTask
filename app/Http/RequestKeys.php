<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Map JSON/query keys: camelCase in API, snake_case for internal / DB.
 */
final class RequestKeys
{
    /**
     * @var array<string, string> alias => canonical
     */
    private const JSON_ALIASES = [
        'categoryId' => 'category_id',
        'inStock'     => 'in_stock',
        'parentId'    => 'parent_id',
    ];

    private const QUERY_ALIASES = [
        'categoryId' => 'category_id',
        'inStock'    => 'in_stock',
        'perPage'    => 'per_page',
    ];

    /**
     * @param array<string, mixed> $input Raw JSON object from client.
     *
     * @return array<string, mixed> Merged: canonical snake keys; alias wins if both absent duplicate check.
     */
    public static function mergeJsonInput(array $input): array
    {
        $out = $input;
        foreach (self::JSON_ALIASES as $alias => $canonical) {
            if (array_key_exists($alias, $input) && !array_key_exists($canonical, $out)) {
                $out[$canonical] = $input[$alias];
            }
        }
        return $out;
    }

    /**
     * @param array<string, mixed> $query Request query array.
     *
     * @return array<string, mixed> Canonical query keys for list/filter.
     */
    public static function mergeQueryInput(array $query): array
    {
        $out = $query;
        foreach (self::QUERY_ALIASES as $alias => $canonical) {
            if (!array_key_exists($alias, $query)) {
                continue;
            }
            $v = $query[$alias];
            if ($v === null || $v === '') {
                continue;
            }
            if (!array_key_exists($canonical, $out) || $out[$canonical] === null || $out[$canonical] === '') {
                $out[$canonical] = $v;
            }
        }
        return $out;
    }

    /**
     * Convert snake_case field name to camelCase (for `error.errors` in responses).
     */
    public static function toCamelField(string $key): string
    {
        if (!str_contains($key, '_')) {
            return $key;
        }
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key))));
    }
}
