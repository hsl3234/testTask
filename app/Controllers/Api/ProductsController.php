<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Services\ProductService;
use OpenApi\Attributes as OA;
use Phalcon\Http\ResponseInterface;
use Throwable;

/**
 * HTTP controller for `/api/products`.
 */
#[OA\Tag(name: 'Products')]
final class ProductsController extends BaseApiController
{
    /**
     * List products with pagination, filters and in-stock aggregates.
     *
     * @return ResponseInterface JSON envelope `{ data, meta }`.
     */
    #[OA\Get(
        path: '/api/products',
        summary: 'List products with pagination and aggregates',
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 20)),
            new OA\Parameter(name: 'category_id', in: 'query', required: false, description: 'Filter by category id including its whole subtree.', schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\Parameter(name: 'in_stock', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/ProductListResponse')),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Category not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function indexAction(): ResponseInterface
    {
        try {
            /** @var ProductService $service */
            $service = $this->di->getShared(ProductService::class);
            $payload = $service->paginate($this->request->getQuery());
            return $this->respond($payload);
        } catch (Throwable $e) {
            return $this->respondException($e);
        }
    }

    /**
     * Fetch a single product.
     *
     * @param int $id Product id from the route.
     *
     * @return ResponseInterface JSON product representation.
     */
    #[OA\Get(
        path: '/api/products/{id}',
        summary: 'Get a product by id',
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Product')),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function showAction(int $id): ResponseInterface
    {
        try {
            /** @var ProductService $service */
            $service = $this->di->getShared(ProductService::class);
            return $this->respond($service->find($id));
        } catch (Throwable $e) {
            return $this->respondException($e);
        }
    }

    /**
     * Create a product.
     *
     * @return ResponseInterface JSON representation with status 201.
     */
    #[OA\Post(
        path: '/api/products',
        summary: 'Create a product',
        tags: ['Products'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ProductInput')),
        responses: [
            new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: '#/components/schemas/Product')),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function createAction(): ResponseInterface
    {
        try {
            /** @var ProductService $service */
            $service = $this->di->getShared(ProductService::class);
            $product = $service->create($this->jsonBody());
            return $this->respond($product, 201);
        } catch (Throwable $e) {
            return $this->respondException($e);
        }
    }

    /**
     * Update a product.
     *
     * @param int $id Product id from the route.
     *
     * @return ResponseInterface Updated product representation.
     */
    #[OA\Put(
        path: '/api/products/{id}',
        summary: 'Update a product',
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ProductUpdateInput')),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Product')),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function updateAction(int $id): ResponseInterface
    {
        try {
            /** @var ProductService $service */
            $service = $this->di->getShared(ProductService::class);
            return $this->respond($service->update($id, $this->jsonBody()));
        } catch (Throwable $e) {
            return $this->respondException($e);
        }
    }

    /**
     * Delete a product.
     *
     * @param int $id Product id from the route.
     *
     * @return ResponseInterface Empty response with status 204.
     */
    #[OA\Delete(
        path: '/api/products/{id}',
        summary: 'Delete a product',
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Deleted'),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function deleteAction(int $id): ResponseInterface
    {
        try {
            /** @var ProductService $service */
            $service = $this->di->getShared(ProductService::class);
            $service->delete($id);
            $this->response->setStatusCode(204);
            $this->response->setContent('');
            return $this->response;
        } catch (Throwable $e) {
            return $this->respondException($e);
        }
    }
}
