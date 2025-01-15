<?php

declare(strict_types=1);

namespace OpenTelemetry\Contrib\Instrumentation\Webman\Hooks\Common;

use OpenTelemetry\API\Instrumentation\CachedInstrumentation;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\Context\Context;
use OpenTelemetry\Contrib\Instrumentation\Webman\Hooks\CommonHookTrait;
use Throwable;
use function OpenTelemetry\Instrumentation\hook;

final class ClassFunction
{
    use CommonHookTrait;

    public static function hook(CachedInstrumentation $instrumentation, string $clasName, string $functionName, int $kind = SpanKind::KIND_SERVER): void
    {
        hook(
            $clasName,
            $functionName,
            pre: function ($object, array $params, string $class, string $function, ?string $filename, ?int $lineno) use ($instrumentation, $kind) {
                $span = self::builder($instrumentation, sprintf('%s::%s', $class, $function), $function, $class, $filename, $lineno)
                    ->setSpanKind($kind)
                    ->startSpan();

                Context::storage()->attach($span->storeInContext(Context::getCurrent()));
            },
            post: function ($object, array $params, $returnValue, ?Throwable $exception) {
                self::end($exception);
            }
        );
    }
}
