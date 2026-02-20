<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/Router.php';
require __DIR__ . '/../src/Http/PageController.php';
require __DIR__ . '/../src/Http/AuthController.php';
require __DIR__ . '/../src/Http/EventController.php';

$router = new Router();

$router->add('GET', '/', [PageController::class, 'calendar']);
$router->add('GET', '/login', [PageController::class, 'login']);
$router->add('GET', '/register', [PageController::class, 'register']);
$router->add('GET', '/verify-email', [PageController::class, 'verifyEmail']);
$router->add('GET', '/forgot-password', [PageController::class, 'forgotPassword']);
$router->add('GET', '/reset-password', [PageController::class, 'resetPassword']);

$router->add('GET', '/api/csrf', [AuthController::class, 'csrf']);
$router->add('GET', '/api/me', [AuthController::class, 'me']);
$router->add('POST', '/api/register', [AuthController::class, 'register']);
$router->add('POST', '/api/login', [AuthController::class, 'login']);
$router->add('POST', '/api/logout', [AuthController::class, 'logout']);
$router->add('POST', '/api/verify-email/resend', [AuthController::class, 'resendVerification']);
$router->add('POST', '/api/verify-email/confirm', [AuthController::class, 'confirmVerification']);
$router->add('POST', '/api/password/forgot', [AuthController::class, 'forgotPassword']);
$router->add('POST', '/api/password/reset', [AuthController::class, 'resetPassword']);

$router->add('GET', '/api/events', [EventController::class, 'list']);
$router->add('POST', '/api/events', [EventController::class, 'create']);
$router->add('POST', '/api/events/join', [EventController::class, 'join']);
$router->add('POST', '/api/events/leave', [EventController::class, 'leave']);
$router->add('POST', '/api/events/delete', [EventController::class, 'delete']);

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'];

if ($path === '/styles.css') {
    header('Content-Type: text/css');
    readfile(__DIR__ . '/styles.css');

    return;
}

if ($path === '/app.js') {
    header('Content-Type: application/javascript');
    readfile(__DIR__ . '/app.js');

    return;
}

$router->dispatch($method, $path);
