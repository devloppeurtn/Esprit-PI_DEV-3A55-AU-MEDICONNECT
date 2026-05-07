<?php

use App\Kernel;

// Avoid 30s timeouts on slow dev pages.
if (\PHP_SAPI !== 'cli') {
    @ini_set('max_execution_time', '120');
    @set_time_limit(120);
}

$appTimezone = $_SERVER['APP_TIMEZONE'] ?? getenv('APP_TIMEZONE') ?: 'UTC';
date_default_timezone_set($appTimezone);

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
