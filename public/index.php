<?php

use App\Kernel;

// Avoid 30s timeouts on slow dev pages.
if (\PHP_SAPI !== 'cli') {
    @ini_set('max_execution_time', '120');
    @set_time_limit(120);
}

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
