<?php

declare(strict_types=1);

namespace OpenTelemetry\Contrib\Instrumentation\Webman\Propagators;

use function assert;
use OpenTelemetry\Context\Propagation\PropagationSetterInterface;
use Webman\Http\Response;

/**
 * @internal
 */
final class ResponsePropagationSetter implements PropagationSetterInterface
{
    public static function instance(): self
    {
        static $instance;

        return $instance ??= new self();
    }

    public function set(&$carrier, string $key, string $value): void
    {
        assert($carrier instanceof Response);

        $carrier->header($key, $value);
    }
}
