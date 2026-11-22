<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * @package  Laravel
 * @author   Taylor Otwell <taylor@laravel.com>
 */

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// This file allows us to emulate Apache's "mod_rewrite" functionality from the
// built-in PHP web server. This provides a convenient way to test a Laravel
// application without having installed a "real" web server software here.
//
// On desktop, never short-circuit /storage/* — product images are encrypted at
// rest and must go through Laravel's decrypt route in routes/web.php.
$isDesktop = getenv('NATIVEPHP_RUNNING') === 'true';
$isStorageUri = str_starts_with($uri, '/storage/');
if ($uri !== '/' && ! ($isDesktop && $isStorageUri) && file_exists(__DIR__.'/public'.$uri)) {
    return false;
}

if (getenv('NATIVEPHP_RUNNING') === 'true') {
    putenv('DB_CONNECTION=sqlite');
    putenv('DB_DATABASE=' . getenv('NATIVEPHP_DATABASE_PATH'));
    putenv('SESSION_DRIVER=file');
    putenv('CACHE_STORE=file');
    $_ENV['DB_CONNECTION'] = 'sqlite';
    $_ENV['DB_DATABASE'] = getenv('NATIVEPHP_DATABASE_PATH');
    $_ENV['SESSION_DRIVER'] = 'file';
    $_ENV['CACHE_STORE'] = 'file';
    $_SERVER['DB_CONNECTION'] = 'sqlite';
    $_SERVER['DB_DATABASE'] = getenv('NATIVEPHP_DATABASE_PATH');
    $_SERVER['SESSION_DRIVER'] = 'file';
    $_SERVER['CACHE_STORE'] = 'file';
}

require_once __DIR__.'/public/index.php';
