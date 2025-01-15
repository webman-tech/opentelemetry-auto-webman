<?php

declare(strict_types=1);

namespace OpenTelemetry\Contrib\Instrumentation\Webman;

use OpenTelemetry\API\Instrumentation\CachedInstrumentation;
use OpenTelemetry\SDK\Common\Configuration\Configuration;
use Webman\MiddlewareInterface;

class WebmanInstrumentation
{
    public const NAME = 'webman';

    public static function register(): void
    {
        $instrumentation = new CachedInstrumentation('io.opentelemetry.contrib.php.webman');

        Hooks\Framework\App::hook($instrumentation);

        if (class_exists(Configuration::class)) {
            $features = Configuration::getList('OTEL_PHP_INSTRUMENTATION_WEBMAN_FEATURES', []);
        } else {
            $features = (array) get_cfg_var('opentelemetry.instrumentation.webman.features') ?: [];
        }

        if (in_array('middleware', $features, true)) {
            Hooks\Common\ClassFunction::hook($instrumentation, MiddlewareInterface::class, 'process');
        }
    }
}
