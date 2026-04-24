<?php

declare(strict_types=1);

namespace App\Controllers;

use Phalcon\Mvc\Controller;

/**
 * Renders the single-page HTML shell that hosts the Vue 3 UI.
 */
final class IndexController extends Controller
{
    /**
     * Render the main app page using Volt.
     *
     * @return void
     */
    public function indexAction(): void
    {
        $this->view->setVar('apiBase', '/api');
        $this->view->pick('index');
    }
}
