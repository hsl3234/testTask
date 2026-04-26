<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Exceptions\HttpException;
use App\Exceptions\ValidationException;
use App\Http\ErrorHandler;
use App\Http\JsonResponse;
use App\Services\AuthService;
use Phalcon\Http\Response;
use Phalcon\Http\ResponseInterface;
use Phalcon\Mvc\Controller;
use Phalcon\Mvc\Dispatcher;
use Throwable;

/**
 * Base for all JSON API controllers.
 *
 * Provides Bearer-token enforcement, consistent exception → JSON mapping,
 * and a helper to read the request body as an associative array.
 */
abstract class BaseApiController extends Controller
{
    /**
     * Actions / controllers that must be reachable without authentication.
     *
     * Format: "<controller>/<action>" (lowercased).
     *
     * @var list<string>
     */
    private const PUBLIC_ROUTES = [
        'health/index',
        'docs/openapi',
        'docs/ui',
        'notfound/index',
        'auth/login',
        'auth/refresh',
    ];

    /**
     * Phalcon lifecycle hook: disable the view for every API controller so
     * actions are responsible for the full response body.
     *
     * @return void
     */
    public function initialize(): void
    {
        $this->view->disable();
    }

    /**
     * Enforce Bearer authentication before any protected action runs.
     *
     * @param Dispatcher $dispatcher Active dispatcher.
     *
     * @return bool True to continue, false to short-circuit with an error response.
     */
    public function beforeExecuteRoute(Dispatcher $dispatcher): bool
    {
        $this->view->disable();

        $key = strtolower((string) $dispatcher->getControllerName())
             . '/'
             . strtolower((string) $dispatcher->getActionName());

        if (in_array($key, self::PUBLIC_ROUTES, true)) {
            return true;
        }

        try {
            /** @var AuthService $auth */
            $auth = $this->di->getShared(AuthService::class);
            $auth->assertBearer($this->request->getHeader('Authorization'));
            return true;
        } catch (Throwable $e) {
            $this->respondException($e);
            return false;
        }
    }

    /**
     * Read the request body and decode it as JSON.
     *
     * @return array<string, mixed> Decoded JSON body, or an empty array if no body was sent.
     *
     * @throws ValidationException When the body is not valid JSON.
     */
    protected function jsonBody(): array
    {
        $raw = $this->request->getRawBody();
        if ($raw === '' || $raw === null) {
            return [];
        }
        try {
            $decoded = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            throw new ValidationException([], 'Invalid JSON body');
        }
        if (!is_array($decoded)) {
            throw new ValidationException([], 'JSON body must be an object');
        }
        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    /**
     * Write a JSON payload to the active response.
     *
     * @param mixed $payload    JSON-serializable value.
     * @param int   $statusCode HTTP status code.
     *
     * @return ResponseInterface Phalcon response, ready to be returned from an action.
     */
    protected function respond(mixed $payload, int $statusCode = 200): ResponseInterface
    {
        $response = JsonResponse::create($payload, $statusCode);
        return $this->copyInto($response);
    }

    /**
     * Convert any exception into the configured JSON error response.
     *
     * @param Throwable $e Exception.
     *
     * @return ResponseInterface JSON error response, ready to be returned.
     */
    protected function respondException(Throwable $e): ResponseInterface
    {
        $response = (new ErrorHandler())->handle($e);
        return $this->copyInto($response);
    }

    /**
     * Assert that `$e` is an {@see HttpException}; rethrow it otherwise wrapped in the proper mapping.
     *
     * Provided as a small helper so action bodies stay flat.
     *
     * @param Throwable $e Incoming exception.
     *
     * @throws HttpException Always: mapped HTTP exception.
     *
     * @return never
     */
    protected function rethrow(Throwable $e): never
    {
        if ($e instanceof HttpException) {
            throw $e;
        }
        throw new HttpException(500, $e->getMessage(), [], $e);
    }

    /**
     * Copy a freshly built Response into the DI-managed response used by Phalcon.
     *
     * @param Response $source Freshly built response.
     *
     * @return ResponseInterface The DI-managed response, with headers/body copied.
     */
    private function copyInto(Response $source): ResponseInterface
    {
        $this->response->setStatusCode($source->getStatusCode());
        $this->response->setContentType('application/json', 'UTF-8');
        $this->response->setContent($source->getContent());
        return $this->response;
    }
}
