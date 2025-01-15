# OpenTelemetry Webman auto-instrumentation

Please read https://opentelemetry.io/docs/instrumentation/php/automatic/ for instructions on how to
install and configure the extension and SDK.

## Overview

## Configuration

The extension can be disabled via [runtime configuration](https://opentelemetry.io/docs/instrumentation/php/sdk/#configuration):

```shell
OTEL_PHP_DISABLED_INSTRUMENTATIONS=webman
```

extra features can be enabled via configuration:

```shell
OTEL_PHP_INSTRUMENTATION_WEBMAN_FEATURES=middleware
```
