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
        new OA\Property(property: 'parentId', type: 'integer', nullable: true),
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
        new OA\Property(property: 'parentId', type: 'integer', nullable: true),
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
        new OA\Property(property: 'parentId', type: 'integer', nullable: true),
    ],
)]
#[OA\Schema(
    schema: 'Product',
    type: 'object',
    required: ['id', 'name', 'price', 'inStock', 'category'],
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'content', type: 'string', nullable: true),
        new OA\Property(property: 'price', type: 'string', example: '1299.99'),
        new OA\Property(property: 'inStock', type: 'boolean'),
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
    required: ['name', 'content', 'price', 'inStock', 'categoryId'],
    properties: [
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'content', type: 'string', nullable: true),
        new OA\Property(property: 'price', type: 'number', format: 'float'),
        new OA\Property(property: 'inStock', type: 'boolean'),
        new OA\Property(property: 'categoryId', type: 'integer'),
    ],
)]
#[OA\Schema(
    schema: 'ProductUpdateInput',
    type: 'object',
    properties: [
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'content', type: 'string', nullable: true),
        new OA\Property(property: 'price', type: 'number', format: 'float'),
        new OA\Property(property: 'inStock', type: 'boolean'),
        new OA\Property(property: 'categoryId', type: 'integer'),
    ],
)]
#[OA\Schema(
    schema: 'AuthLoginRequest',
    type: 'object',
    required: ['login', 'password'],
    properties: [
        new OA\Property(property: 'login', type: 'string', example: 'admin'),
        new OA\Property(property: 'password', type: 'string', format: 'password'),
    ],
)]
#[OA\Schema(
    schema: 'AuthRefreshRequest',
    type: 'object',
    required: ['refreshToken'],
    properties: [
        new OA\Property(property: 'refreshToken', type: 'string'),
    ],
)]
#[OA\Schema(
    schema: 'AuthTokenResponse',
    type: 'object',
    required: ['accessToken', 'refreshToken', 'tokenType', 'expiresIn'],
    properties: [
        new OA\Property(property: 'accessToken', type: 'string', description: 'Bearer access token used in the `Authorization` header.'),
        new OA\Property(property: 'refreshToken', type: 'string', description: 'Single-use refresh token.'),
        new OA\Property(property: 'tokenType', type: 'string', example: 'Bearer'),
        new OA\Property(property: 'expiresIn', type: 'integer', description: 'Access token lifetime in seconds.', example: 900),
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
            required: ['page', 'perPage', 'total', 'aggregates'],
            properties: [
                new OA\Property(property: 'page', type: 'integer'),
                new OA\Property(property: 'perPage', type: 'integer'),
                new OA\Property(property: 'total', type: 'integer'),
                new OA\Property(
                    property: 'aggregates',
                    type: 'object',
                    required: ['inStockCount', 'inStockTotalPrice'],
                    properties: [
                        new OA\Property(property: 'inStockCount', type: 'integer'),
                        new OA\Property(property: 'inStockTotalPrice', type: 'string', example: '12345.67'),
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
