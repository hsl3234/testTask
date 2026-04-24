<?php

declare(strict_types=1);

use Phalcon\Di\DiInterface;
use Phalcon\Mvc\Router;

/**
 * Register HTTP routes.
 *
 * @return callable(DiInterface): void
 */
return static function (DiInterface $di): void {
    $di->setShared('router', function () {
        $router = new Router(false);
        $router->removeExtraSlashes(true);

        $router->add('/', [
            'namespace'  => 'App\\Controllers',
            'controller' => 'index',
            'action'     => 'index',
        ]);

        $router->addGet('/api/health', [
            'namespace'  => 'App\\Controllers\\Api',
            'controller' => 'health',
            'action'     => 'index',
        ]);

        $router->addGet('/api/docs/openapi.json', [
            'namespace'  => 'App\\Controllers\\Api',
            'controller' => 'docs',
            'action'     => 'openapi',
        ]);
        $router->addGet('/api/docs', [
            'namespace'  => 'App\\Controllers\\Api',
            'controller' => 'docs',
            'action'     => 'ui',
        ]);

        $router->addGet('/api/products', [
            'namespace'  => 'App\\Controllers\\Api',
            'controller' => 'products',
            'action'     => 'index',
        ]);
        $router->addGet('/api/products/{id:[0-9]+}', [
            'namespace'  => 'App\\Controllers\\Api',
            'controller' => 'products',
            'action'     => 'show',
        ]);
        $router->addPost('/api/products', [
            'namespace'  => 'App\\Controllers\\Api',
            'controller' => 'products',
            'action'     => 'create',
        ]);
        $router->add('/api/products/{id:[0-9]+}', [
            'namespace'  => 'App\\Controllers\\Api',
            'controller' => 'products',
            'action'     => 'update',
        ])->via(['PUT', 'PATCH']);
        $router->addDelete('/api/products/{id:[0-9]+}', [
            'namespace'  => 'App\\Controllers\\Api',
            'controller' => 'products',
            'action'     => 'delete',
        ]);

        $router->addGet('/api/categories', [
            'namespace'  => 'App\\Controllers\\Api',
            'controller' => 'categories',
            'action'     => 'index',
        ]);
        $router->addPost('/api/categories', [
            'namespace'  => 'App\\Controllers\\Api',
            'controller' => 'categories',
            'action'     => 'create',
        ]);
        $router->add('/api/categories/{id:[0-9]+}', [
            'namespace'  => 'App\\Controllers\\Api',
            'controller' => 'categories',
            'action'     => 'update',
        ])->via(['PUT', 'PATCH']);
        $router->addDelete('/api/categories/{id:[0-9]+}', [
            'namespace'  => 'App\\Controllers\\Api',
            'controller' => 'categories',
            'action'     => 'delete',
        ]);

        $router->notFound([
            'namespace'  => 'App\\Controllers\\Api',
            'controller' => 'notfound',
            'action'     => 'index',
        ]);

        return $router;
    });
};
