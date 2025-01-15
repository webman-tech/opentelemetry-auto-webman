<?php

declare(strict_types=1);

namespace OpenTelemetry\Contrib\Instrumentation\Webman\Hooks\Common;

use OpenTelemetry\API\Instrumentation\CachedInstrumentation;
use OpenTelemetry\Context\Context;
use OpenTelemetry\Contrib\Instrumentation\Webman\Hooks\CommonHookTrait;
use Throwable;
use Webman\MiddlewareInterface;
use function OpenTelemetry\Instrumentation\hook;

final class ClassFunction
{
    use CommonHookTrait;

    public static function hook(CachedInstrumentation $instrumentation, string $clasName, string $functionName): void
    {
        hook(
            $clasName,
            $functionName,
            pre: function (MiddlewareInterface $middleware, array $params, string $class, string $function, ?string $filename, ?int $lineno) use ($instrumentation) {
                $span = self::builder($instrumentation, sprintf('%s::%s', $class, $function), $function, $class, $filename, $lineno)
                    ->startSpan();

                Context::storage()->attach($span->storeInContext(Context::getCurrent()));
            },
            post: function (MiddlewareInterface $middleware, array $params, $returnValue, ?Throwable $exception) {
                self::end($exception);
            }
        );
    }
}
