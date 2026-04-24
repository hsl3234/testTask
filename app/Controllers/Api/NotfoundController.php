<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Exceptions\NotFoundException;
use Phalcon\Http\ResponseInterface;

/**
 * Fallback controller for unmatched routes.
 */
final class NotfoundController extends BaseApiController
{
    /**
     * Respond with a standard 404 JSON error.
     *
     * @return ResponseInterface JSON error response.
     */
    public function indexAction(): ResponseInterface
    {
        return $this->respondException(new NotFoundException('Route not found'));
    }
}
