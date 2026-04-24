<?php

declare(strict_types=1);

use App\Bootstrap;

require_once __DIR__ . '/../vendor/autoload.php';

(new Bootstrap(dirname(__DIR__)))->run();
