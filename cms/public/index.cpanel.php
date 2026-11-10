<?php

/**
 * Place this file at: /home/USER/public_html/index.php
 * Laravel app lives at: /home/USER/portfolio-app
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$appPath = dirname(__DIR__).DIRECTORY_SEPARATOR.'portfolio-app';

if (file_exists($maintenance = $appPath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $appPath.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $appPath.'/bootstrap/app.php';

// public_html is the web root: public_path() (published media, assets) must resolve here,
// not to portfolio-app/public which is not web-accessible on cPanel.
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
