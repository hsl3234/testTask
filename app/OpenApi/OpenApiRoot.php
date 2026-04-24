<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Корневые настройки OpenAPI: глобальная схема безопасности Bearer.
 *
 * Благодаря {@see OA\OpenApi::security} Swagger UI показывает кнопку «Authorize»
 * и поле ввода токена; токен подставляется во все защищённые операции.
 * Публичные маршруты помечаются {@code security: [[]]} (пустой массив).
 */
#[OA\OpenApi(
    openapi: '3.0.0',
    security: [['bearerAuth' => []]],
)]
final class OpenApiRoot
{
}
