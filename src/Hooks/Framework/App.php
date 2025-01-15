<?php

declare(strict_types=1);

namespace OpenTelemetry\Contrib\Instrumentation\Webman\Hooks\Framework;

use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Instrumentation\CachedInstrumentation;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\Context;
use OpenTelemetry\Contrib\Instrumentation\Webman\Hooks\CommonHookTrait;
use OpenTelemetry\Contrib\Instrumentation\Webman\Propagators\ResponsePropagationSetter;
use OpenTelemetry\SemConv\TraceAttributes;
use Webman\App as WebmanApp;
use Webman\Context as WebmanContext;
use Webman\Http\Request;
use Webman\Http\Response;
use function OpenTelemetry\Instrumentation\hook;

final class App
{
    use CommonHookTrait;

    public static function hook(CachedInstrumentation $instrumentation): void
    {
        hook(
            WebmanApp::class,
            'onMessage',
            pre: function (WebmanApp $app, array $params, string $class, string $function, ?string $filename, ?int $lineno) use ($instrumentation) {
                if (!isset($params[1]) || !$params[1] instanceof Request) {
                    return;
                }
                $request = $params[1];
                $builder = self::builder($instrumentation, $request->method(), $function, $class, $filename, $lineno)
                    ->setSpanKind(SpanKind::KIND_SERVER);

                $parent = Globals::propagator()->extract($request->header());

                $span = $builder
                    ->setParent($parent)
                    ->setAttribute(TraceAttributes::URL_FULL, $request->fullUrl())
                    ->setAttribute(TraceAttributes::HTTP_REQUEST_METHOD, $request->method())
                    ->setAttribute(TraceAttributes::HTTP_RESPONSE_BODY_SIZE, $request->header('Content-Length'))
                    //->setAttribute(TraceAttributes::URL_SCHEME, $request->getScheme())
                    ->setAttribute(TraceAttributes::NETWORK_PROTOCOL_VERSION, $request->protocolVersion())
                    ->setAttribute(TraceAttributes::NETWORK_PEER_ADDRESS, $request->getRemoteIp())
                    ->setAttribute(TraceAttributes::URL_PATH, $request->path())
                    ->setAttribute(TraceAttributes::SERVER_ADDRESS, $request->host(true))
                    ->setAttribute(TraceAttributes::SERVER_PORT, $request->getLocalPort())
                    ->setAttribute(TraceAttributes::CLIENT_PORT, $request->getRemotePort())
                    ->setAttribute(TraceAttributes::USER_AGENT_ORIGINAL, $request->header('User-Agent'))
                    ->startSpan();
                WebmanContext::set(SpanInterface::class, $span);

                Context::storage()->attach($span->storeInContext($parent));
            }
        );

        hook(
            WebmanApp::class,
            'send',
            pre: static function (string $staticClassName, array $params) {
                $scope = Context::storage()->scope();
                if (!$scope) {
                    return;
                }
                $scope->detach();
                $span = Span::fromContext($scope->context());

                if (
                    isset($params[1], $params[2])
                    && $params[1] instanceof Response
                    && $params[2] instanceof Request
                ) {
                    $response = $params[1];
                    $request = $params[2];

                    // request
                    $span->updateName($request->method() . ' /' . ltrim($request->path(), '/'));
                    $span->setAttribute(TraceAttributes::HTTP_ROUTE, $request->uri());

                    // response
                    if ($response->getStatusCode() >= 500) {
                        $span->setStatus(StatusCode::STATUS_ERROR);
                    }
                    $span->setAttribute(TraceAttributes::HTTP_RESPONSE_STATUS_CODE, $response->getStatusCode());
                    $span->setAttribute(TraceAttributes::NETWORK_PROTOCOL_VERSION, $request->protocolVersion());
                    $span->setAttribute(TraceAttributes::HTTP_RESPONSE_BODY_SIZE, $response->getHeader('Content-Length'));

                    // Propagate server-timing header to response, if ServerTimingPropagator is present
                    if (class_exists('OpenTelemetry\Contrib\Propagation\ServerTiming\ServerTimingPropagator')) {
                        /** @phan-suppress-next-line PhanUndeclaredClassMethod */
                        $prop = new \OpenTelemetry\Contrib\Propagation\ServerTiming\ServerTimingPropagator();
                        /** @phan-suppress-next-line PhanAccessMethodInternal,PhanUndeclaredClassMethod */
                        $prop->inject($response, ResponsePropagationSetter::instance(), $scope->context());
                    }

                    // Propagate traceresponse header to response, if TraceResponsePropagator is present
                    if (class_exists('OpenTelemetry\Contrib\Propagation\TraceResponse\TraceResponsePropagator')) {
                        /** @phan-suppress-next-line PhanUndeclaredClassMethod */
                        $prop = new \OpenTelemetry\Contrib\Propagation\TraceResponse\TraceResponsePropagator();
                        /** @phan-suppress-next-line PhanAccessMethodInternal,PhanUndeclaredClassMethod */
                        $prop->inject($response, ResponsePropagationSetter::instance(), $scope->context());
                    }
                }

                $span->end();
            }
        );
    }
}
