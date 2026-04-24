<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Generator;

/**
 * Generates the OpenAPI 3 specification by scanning PHP attributes in the app.
 */
final class OpenApiGenerator
{
    /**
     * @return array<string, mixed> Decoded OpenAPI document.
     */
    public function generate(): array
    {
        $sources = [
            dirname(__DIR__) . '/Controllers',
            dirname(__DIR__) . '/OpenApi',
        ];
        $openapi = Generator::scan($sources);
        return json_decode($openapi->toJson(), true, 64, JSON_THROW_ON_ERROR);
    }
}
