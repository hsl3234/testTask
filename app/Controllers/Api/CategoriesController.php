<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Services\CategoryService;
use App\Services\ProductService;
use OpenApi\Attributes as OA;
use Phalcon\Http\ResponseInterface;
use Throwable;

/**
 * HTTP controller for `/api/categories`.
 */
#[OA\Tag(name: 'Categories')]
final class CategoriesController extends BaseApiController
{
    /**
     * List categories, optionally as a nested tree.
     *
     * @return ResponseInterface JSON list or tree.
     */
    #[OA\Get(
        path: '/api/categories',
        summary: 'List categories',
        tags: ['Categories'],
        parameters: [
            new OA\Parameter(name: 'tree', in: 'query', required: false, description: 'Set to 1 to receive a nested tree response.', schema: new OA\Schema(type: 'integer', enum: [0, 1])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(oneOf: [
                new OA\Schema(type: 'array', items: new OA\Items(ref: '#/components/schemas/Category')),
                new OA\Schema(type: 'array', items: new OA\Items(ref: '#/components/schemas/CategoryNode')),
            ])),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function indexAction(): ResponseInterface
    {
        try {
            /** @var CategoryService $service */
            $service = $this->di->getShared(CategoryService::class);
            $tree = (string) $this->request->getQuery('tree', null, '0') === '1';
            $payload = $tree ? $service->listTree() : $service->listFlat();
            return $this->respond($payload);
        } catch (Throwable $e) {
            return $this->respondException($e);
        }
    }

    /**
     * Create a category.
     *
     * @return ResponseInterface JSON representation with status 201.
     */
    #[OA\Post(
        path: '/api/categories',
        summary: 'Create a category',
        tags: ['Categories'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CategoryInput')),
        responses: [
            new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: '#/components/schemas/Category')),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function createAction(): ResponseInterface
    {
        try {
            /** @var CategoryService $service */
            $service = $this->di->getShared(CategoryService::class);
            return $this->respond($service->create($this->jsonBody()), 201);
        } catch (Throwable $e) {
            return $this->respondException($e);
        }
    }

    /**
     * Update a category.
     *
     * @param int $id Category id from the route.
     *
     * @return ResponseInterface Updated representation.
     */
    #[OA\Put(
        path: '/api/categories/{id}',
        summary: 'Update a category',
        tags: ['Categories'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CategoryInput')),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Category')),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Conflict', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function updateAction(int $id): ResponseInterface
    {
        try {
            /** @var CategoryService $service */
            $service = $this->di->getShared(CategoryService::class);
            return $this->respond($service->update($id, $this->jsonBody()));
        } catch (Throwable $e) {
            return $this->respondException($e);
        }
    }

    /**
     * Delete a category.
     *
     * Fails with 409 if the category has children or products.
     *
     * @param int $id Category id from the route.
     *
     * @return ResponseInterface Empty response with status 204.
     */
    #[OA\Delete(
        path: '/api/categories/{id}',
        summary: 'Delete a category',
        tags: ['Categories'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Deleted'),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Conflict', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function deleteAction(int $id): ResponseInterface
    {
        try {
            /** @var CategoryService  $categories */
            $categories = $this->di->getShared(CategoryService::class);
            /** @var ProductService   $products */
            $products = $this->di->getShared(ProductService::class);
            $categories->delete($id, $products->countByCategory($id));
            $this->response->setStatusCode(204);
            $this->response->setContent('');
            return $this->response;
        } catch (Throwable $e) {
            return $this->respondException($e);
        }
    }
}
