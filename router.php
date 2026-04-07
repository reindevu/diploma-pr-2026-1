<?php

declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$publicPath = __DIR__ . '/public';
$filePath = realpath($publicPath . $uri);

if ($uri !== '/' && $filePath !== false && str_starts_with($filePath, realpath($publicPath)) && is_file($filePath)) {
    return false;
}

$routes = [
    '/' => '/index.php',
    '/menu' => '/menu.php',
    '/photos' => '/photos.php',
    '/contact' => '/contact.php',
    '/cart' => '/cart.php',
    '/login' => '/login.php',
    '/register' => '/register.php',
    '/logout' => '/logout.php',
    '/account' => '/account.php',
    '/admin' => '/admin.php',
    '/error' => '/error.php',
];

if (isset($routes[$uri])) {
    require $publicPath . $routes[$uri];
    return true;
}

if (preg_match('#^/product/([^/]+)/?$#', $uri, $matches) === 1) {
    $_GET['slug'] = urldecode($matches[1]);
    require $publicPath . '/product.php';
    return true;
}

http_response_code(404);
require $publicPath . '/error.php';
return true;
