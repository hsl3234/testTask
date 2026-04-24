<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Products & Categories API',
    description: 'Phalcon + MySQL REST API for products and categories. JSON-only.',
)]
#[OA\Server(url: '/', description: 'Current origin')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'opaque',
    description: 'Введите токен (как в заголовке Authorization: Bearer <token>). Демо-токен из сида: demo-token-please-change',
)]
#[OA\Schema(
    schema: 'ErrorResponse',
    type: 'object',
    required: ['error'],
    properties: [
        new OA\Property(
            property: 'error',
            type: 'object',
            properties: [
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'errors', type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'string')),
            ],
        ),
    ],
)]
#[OA\Schema(
    schema: 'Category',
    type: 'object',
    required: ['id', 'name', 'path'],
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'parent_id', type: 'integer', nullable: true),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'path', type: 'string', example: '/1/2/'),
    ],
)]
#[OA\Schema(
    schema: 'CategoryNode',
    type: 'object',
    required: ['id', 'name', 'path', 'children'],
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'parent_id', type: 'integer', nullable: true),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'path', type: 'string'),
        new OA\Property(property: 'children', type: 'array', items: new OA\Items(ref: '#/components/schemas/CategoryNode')),
    ],
)]
#[OA\Schema(
    schema: 'CategoryInput',
    type: 'object',
    required: ['name'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 191),
        new OA\Property(property: 'parent_id', type: 'integer', nullable: true),
    ],
)]
#[OA\Schema(
    schema: 'Product',
    type: 'object',
    required: ['id', 'name', 'price', 'in_stock', 'category'],
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'content', type: 'string', nullable: true),
        new OA\Property(property: 'price', type: 'string', example: '1299.99'),
        new OA\Property(property: 'in_stock', type: 'boolean'),
        new OA\Property(
            property: 'category',
            type: 'object',
            properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'path', type: 'string'),
            ],
        ),
    ],
)]
#[OA\Schema(
    schema: 'ProductInput',
    type: 'object',
    required: ['name', 'content', 'price', 'in_stock', 'category_id'],
    properties: [
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'content', type: 'string', nullable: true),
        new OA\Property(property: 'price', type: 'number', format: 'float'),
        new OA\Property(property: 'in_stock', type: 'boolean'),
        new OA\Property(property: 'category_id', type: 'integer'),
    ],
)]
#[OA\Schema(
    schema: 'ProductUpdateInput',
    type: 'object',
    properties: [
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'content', type: 'string', nullable: true),
        new OA\Property(property: 'price', type: 'number', format: 'float'),
        new OA\Property(property: 'in_stock', type: 'boolean'),
        new OA\Property(property: 'category_id', type: 'integer'),
    ],
)]
#[OA\Schema(
    schema: 'ProductListResponse',
    type: 'object',
    required: ['data', 'meta'],
    properties: [
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Product')),
        new OA\Property(
            property: 'meta',
            type: 'object',
            required: ['page', 'per_page', 'total', 'aggregates'],
            properties: [
                new OA\Property(property: 'page', type: 'integer'),
                new OA\Property(property: 'per_page', type: 'integer'),
                new OA\Property(property: 'total', type: 'integer'),
                new OA\Property(
                    property: 'aggregates',
                    type: 'object',
                    required: ['in_stock_count', 'in_stock_total_price'],
                    properties: [
                        new OA\Property(property: 'in_stock_count', type: 'integer'),
                        new OA\Property(property: 'in_stock_total_price', type: 'string', example: '12345.67'),
                    ],
                ),
            ],
        ),
    ],
)]
/**
 * OpenAPI global definitions (info, security scheme, reusable schemas).
 *
 * This class holds no runtime behaviour — only attributes consumed by
 * {@see \App\OpenApi\OpenApiGenerator}.
 */
final class Schemas
{
}
