<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\OpenApi\OpenApiGenerator;
use OpenApi\Attributes as OA;
use Phalcon\Http\ResponseInterface;

/**
 * Serves the generated OpenAPI specification and a Swagger UI page.
 */
final class DocsController extends BaseApiController
{
    /**
     * Return the OpenAPI 3 document generated from PHPDoc attributes.
     *
     * @return ResponseInterface JSON specification.
     */
    #[OA\Get(
        path: '/api/docs/openapi.json',
        summary: 'OpenAPI 3 specification (JSON)',
        description: 'Публичный эндпоинт; авторизация не требуется.',
        security: [],
        tags: ['Meta'],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
        ],
    )]
    public function openapiAction(): ResponseInterface
    {
        $spec = (new OpenApiGenerator())->generate();
        return $this->respond($spec);
    }

    /**
     * Render a minimal Swagger UI page that loads the specification.
     *
     * @return ResponseInterface HTML page.
     */
    #[OA\Get(
        path: '/api/docs',
        summary: 'Swagger UI (HTML)',
        description: 'Публичная страница документации. Токен вводится в диалоге Authorize.',
        security: [],
        tags: ['Meta'],
        responses: [
            new OA\Response(response: 200, description: 'HTML', content: new OA\MediaType(mediaType: 'text/html')),
        ],
    )]
    public function uiAction(): ResponseInterface
    {
        $html = <<<'HTML'
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8" />
  <title>Документация API</title>
  <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css" />
</head>
<body>
  <p style="margin:0;padding:12px 16px;font:14px system-ui,sans-serif;background:#1b1b1b;color:#e0e0e0;border-bottom:1px solid #333;">
    Чтобы вызывать защищённые методы, нажмите <strong>Authorize</strong> и введите значение токена (как в <code>Authorization: Bearer …</code>, без слова Bearer).
  </p>
  <div id="swagger"></div>
  <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
  <script>
    window.ui = SwaggerUIBundle({
      url: '/api/docs/openapi.json',
      dom_id: '#swagger',
      deepLinking: true,
      persistAuthorization: true,
    });
  </script>
</body>
</html>
HTML;

        $this->response->setStatusCode(200);
        $this->response->setContentType('text/html', 'UTF-8');
        $this->response->setContent($html);
        return $this->response;
    }
}
