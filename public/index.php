<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Http\Controller\AuthController;
use App\Infrastructure\Http\Controller\DocsController;
use App\Infrastructure\Http\Controller\StoreController;
use App\Infrastructure\Http\Response\ApiResponse;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$container = require __DIR__ . '/../config/container.php';

$method = $_SERVER['REQUEST_METHOD'];
$uri    = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

match (true) {
    $method === 'POST' && $uri === '/auth/register'
        => $container->get(AuthController::class)->register(),

    $method === 'POST' && $uri === '/auth/login'
        => $container->get(AuthController::class)->login(),

    $method === 'GET' && $uri === '/stores'
        => $container->get(StoreController::class)->list(),

    $method === 'GET' && preg_match('#^/stores/([^/]+)$#', $uri, $m)
        => $container->get(StoreController::class)->get($m[1]),

    $method === 'POST' && $uri === '/stores'
        => $container->get(StoreController::class)->create(),

    $method === 'PUT' && preg_match('#^/stores/([^/]+)$#', $uri, $m)
        => $container->get(StoreController::class)->update($m[1]),

    $method === 'DELETE' && preg_match('#^/stores/([^/]+)$#', $uri, $m)
        => $container->get(StoreController::class)->delete($m[1]),

    $method === 'GET' && $uri === '/openapi.json'
        => $container->get(DocsController::class)->spec(),

    default => ApiResponse::error('Route not found.', 404),
};
