<?php

/**
 * Bridge to the Laravel application.
 *
 * The application lives OUTSIDE the document root (~/apps/goodtriplove) so
 * .env, storage/ and vendor/ are never reachable over HTTP. The DirectAdmin
 * vhost docroot cannot be changed without root, so this file stands in for
 * public/index.php.
 */

define('LARAVEL_START', microtime(true));

$base = dirname(__DIR__, 3).'/apps/goodtriplove';

if (! is_file($base.'/vendor/autoload.php')) {
    http_response_code(503);
    exit('Application not deployed.');
}

if (file_exists($maintenance = $base.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $base.'/vendor/autoload.php';

$app = require_once $base.'/bootstrap/app.php';

$app->handleRequest(Illuminate\Http\Request::capture());
