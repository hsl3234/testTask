<?php

declare(strict_types=1);

namespace App;

use App\Http\ErrorHandler;
use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\Application;
use Throwable;

/**
 * Application entry point.
 *
 * Wires Phalcon DI, router and views, then dispatches the current HTTP request.
 */
final class Bootstrap
{
    /**
     * @param string $rootPath Absolute path to the project root (the directory containing app/, public/, etc.).
     */
    public function __construct(private readonly string $rootPath)
    {
    }

    /**
     * Run the HTTP application and emit the response.
     *
     * @return void
     */
    public function run(): void
    {
        $di = new FactoryDefault();

        $configLoader = require $this->rootPath . '/app/config/services.php';
        $configLoader($di, $this->rootPath);

        $routerLoader = require $this->rootPath . '/app/config/router.php';
        $routerLoader($di);

        $app = new Application($di);

        try {
            $response = $app->handle($_SERVER['REQUEST_URI'] ?? '/');
            $response->send();
            return;
        } catch (Throwable $e) {
            (new ErrorHandler())->handle($e)->send();
        }
    }
}
