<?php

declare(strict_types=1);

use App\Repositories\ApiTokenRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;
use App\Repositories\RefreshTokenRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\AuthTokenService;
use App\Services\CategoryService;
use App\Services\ProductService;
use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\Di\DiInterface;
use Phalcon\Mvc\View;
use Phalcon\Mvc\View\Engine\Volt;
use Phalcon\Mvc\Url;

/**
 * Register framework and domain services in the DI container.
 *
 * @return callable(DiInterface, string): void
 */
return static function (DiInterface $di, string $rootPath): void {
    /** @var array{database: array<string, mixed>, app: array<string, mixed>} $config */
    $config = require $rootPath . '/app/config/config.php';

    $di->setShared('config', function () use ($config) {
        return $config;
    });

    $di->setShared('url', function () {
        $url = new Url();
        $url->setBaseUri('/');
        return $url;
    });

    $di->setShared('db', function () use ($config) {
        return new Mysql([
            'host'     => $config['database']['host'],
            'port'     => $config['database']['port'],
            'username' => $config['database']['username'],
            'password' => $config['database']['password'],
            'dbname'   => $config['database']['dbname'],
            'charset'  => $config['database']['charset'],
        ]);
    });

    $di->setShared('view', function () use ($rootPath) {
        $view = new View();
        $view->setViewsDir($rootPath . '/app/Views/');
        $view->registerEngines([
            '.volt' => function ($view) use ($rootPath) {
                $volt = new Volt($view);
                $volt->setOptions([
                    'path'      => $rootPath . '/var/cache/volt/',
                    'separator' => '_',
                ]);
                return $volt;
            },
        ]);
        return $view;
    });

    $di->setShared(ProductRepository::class, function () {
        return new ProductRepository();
    });
    $di->setShared(CategoryRepository::class, function () {
        return new CategoryRepository();
    });
    $di->setShared(ApiTokenRepository::class, function () {
        return new ApiTokenRepository();
    });
    $di->setShared(UserRepository::class, function () {
        return new UserRepository();
    });
    $di->setShared(RefreshTokenRepository::class, function () {
        return new RefreshTokenRepository();
    });

    $di->setShared(CategoryService::class, function () use ($di) {
        return new CategoryService($di->getShared(CategoryRepository::class));
    });
    $di->setShared(ProductService::class, function () use ($di) {
        return new ProductService(
            $di->getShared(ProductRepository::class),
            $di->getShared(CategoryRepository::class),
        );
    });
    $di->setShared(AuthService::class, function () use ($di) {
        return new AuthService($di->getShared(ApiTokenRepository::class));
    });
    $di->setShared(AuthTokenService::class, function () use ($di) {
        return new AuthTokenService(
            $di->getShared(UserRepository::class),
            $di->getShared(ApiTokenRepository::class),
            $di->getShared(RefreshTokenRepository::class),
        );
    });
};
