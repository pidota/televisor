<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

/**
 * When the app is served from a subdirectory (e.g. /televisor), strip that prefix
 * so Laravel routes like /login and /dashboard match correctly.
 */
$subdirectory = '';

$envFile = __DIR__.'/../.env';

if (is_readable($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
            continue;
        }

        if (str_starts_with($line, 'APP_URL=')) {
            $url = trim(substr($line, strlen('APP_URL=')), " \t\n\r\0\x0B\"'");
            $subdirectory = rtrim(parse_url($url, PHP_URL_PATH) ?: '', '/');

            break;
        }
    }
}

if ($subdirectory !== '' && isset($_SERVER['REQUEST_URI'])) {
    $requestUri = $_SERVER['REQUEST_URI'];
    $path = parse_url($requestUri, PHP_URL_PATH) ?? $requestUri;
    $query = parse_url($requestUri, PHP_URL_QUERY);

    foreach ([$subdirectory.'/public', $subdirectory] as $prefix) {
        if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
            $path = substr($path, strlen($prefix)) ?: '/';
            $_SERVER['REQUEST_URI'] = $path.($query ? '?'.$query : '');
            $_SERVER['SCRIPT_NAME'] = $subdirectory.'/index.php';
            $_SERVER['PHP_SELF'] = $subdirectory.'/index.php';

            break;
        }
    }
}

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
