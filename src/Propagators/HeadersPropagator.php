<?php

declare(strict_types=1);

namespace OpenTelemetry\Contrib\Instrumentation\Webman\Propagators;

use function assert;
use OpenTelemetry\Context\Propagation\PropagationGetterInterface;
use Webman\Http\Request;

/**
 * @internal
 */
final class HeadersPropagator implements PropagationGetterInterface
{
    public static function instance(): self
    {
        static $instance;

        return $instance ??= new self();
    }

    /** @psalm-suppress MoreSpecificReturnType */
    public function keys($carrier): array
    {
        assert($carrier instanceof Request);

        /** @psalm-suppress LessSpecificReturnStatement */
        return array_keys($carrier->header());
    }

    public function get($carrier, string $key) : ?string
    {
        assert($carrier instanceof Request);

        $value = $carrier->header($key);
        if (is_array($value)) {
            $value = $value[0] ?? null;
        }

        return $value;
    }
}
