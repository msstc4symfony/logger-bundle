# Logger Bundle

![Build Status](https://github.com/msstc4symfony/logger-bundle/actions/workflows/checks.yml/badge.svg?branch=main)
[![codecov](https://codecov.io/github/msstc4symfony/logger-bundle/graph/badge.svg?token=Uljr8Pgeto)](https://codecov.io/github/msstc4symfony/logger-bundle)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

A Symfony bundle that extends Monolog with enhanced logging capabilities, including context-aware processors, flexible
formatters, exception filtering, and Sentry integration.

## Features

- 🔄 **Smart Log Processors** - Automatically enrich logs with contextual information
- 📊 **Flexible Formatters** - Switch between human-readable and JSON formats
- 🚫 **Exception Filtering** - Filter out specific exceptions from logs
- 🔗 **Sentry Integration** - Seamless integration with Sentry error tracking
- 🎯 **Context-Aware Exceptions** - Extract additional context from custom exceptions
- ⚙️ **Zero Configuration** - Works out of the box with sensible defaults

## Requirements

- PHP 8.4 or higher
- Symfony 6.4 LTS, 7.x, or 8.x
- Monolog 3.4 or higher

## Installation

The package is not on Packagist yet, so register its GitHub repository first:

```bash
composer config repositories.msstc4symfony-logger vcs https://github.com/msstc4symfony/logger-bundle
composer require msstc4symfony/logger-bundle
```

If you're using Symfony Flex, the bundle will be automatically registered. Otherwise, add it to your
`config/bundles.php`:

```php
return [
    // ...
    Msstc4Symfony\LoggerBundle\LoggerBundle::class => ['all' => true],
];
```

## Configuration

### Basic Configuration

The bundle works with minimal configuration. Set the following environment variables:

```bash
# .env
APPLICATION_NAME=my-app
COMPONENT_NAME=api
```

These values will be automatically added to all log entries and Sentry events.

### Monolog Configuration

Add the bundle's configuration to your `config/packages/monolog.yaml`:

```yaml
monolog:
  channels:
    - deprecation

when@prod:
  monolog:
    handlers:
      main:
        type: stream
        path: php://stdout
        level: info
        channels: [ "!doctrine", "!event", "!deprecation" ]
        formatter: Msstc4Symfony\LoggerBundle\Monolog\Formatter\SwitchFormatter
      errors:
        type: stream
        path: php://stdout
        level: notice
        channels: [ "doctrine", "event" ]
        formatter: Msstc4Symfony\LoggerBundle\Monolog\Formatter\SwitchFormatter
      console:
        type: console
        process_psr_3_messages: false
        level: notice
        channels: [ "!deprecation" ]
        formatter: Msstc4Symfony\LoggerBundle\Monolog\Formatter\SwitchFormatter

when@dev:
  monolog:
    handlers:
      main:
        type: stream
        path: php://stdout
        level: debug
        channels: [ "!doctrine", "!event", "!deprecation" ]
        formatter: Msstc4Symfony\LoggerBundle\Monolog\Formatter\SwitchFormatter
      errors:
        type: stream
        path: php://stdout
        level: info
        channels: [ "doctrine", "event" ]
        formatter: Msstc4Symfony\LoggerBundle\Monolog\Formatter\SwitchFormatter
      console:
        type: console
        process_psr_3_messages: false
        level: debug
        channels: [ "!event", "!doctrine", "!console", "!deprecation" ]
        formatter: Msstc4Symfony\LoggerBundle\Monolog\Formatter\SwitchFormatter
```

### Exception Filtering

Configure which exceptions should be filtered from logs:

```yaml
# config/packages/logger.yaml
parameters:
  logger_bundle.exception_classes:
    - Symfony\Component\HttpKernel\Exception\NotFoundHttpException
    - Symfony\Component\HttpKernel\Exception\BadRequestHttpException
    - Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
    - Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException
    - Symfony\Component\Security\Core\Exception\AccessDeniedException
```

### Sentry Integration

If you're using Sentry, add the LoggerIntegration to your Sentry configuration:

```yaml
# config/packages/sentry.yaml
sentry:
  options:
    integrations:
      - Msstc4Symfony\LoggerBundle\Sentry\Integration\LoggerIntegration
```

This will automatically add `application` and `component` tags to all Sentry events.

## Components

### Processors

The bundle includes several processors that automatically enrich log records with contextual information:

#### WebProcessor

Adds HTTP request information to logs:
- `url` - Full request URL
- `ip` - Client IP address
- `http_method` - HTTP method (GET, POST, etc.)

#### UserProcessor

Adds authenticated user information:
- `user_id` - Identifier of the authenticated user

#### ConsoleProcessor

Adds CLI command information:
- `cmd` - Full command line with arguments

#### EnvironmentProcessor

Adds environment information:
- `sapi` - PHP SAPI (cli, fpm-fcgi, etc.)
- `container_id` - Container/pod identifier (POD_NAME, POD_UID, or hostname)

#### ExceptionContextProcessor

Extracts additional context from exceptions that implement `ContextAwareExceptionInterface`. This allows you to attach
custom context data to your exceptions:

```php
use Msstc4Symfony\LoggerBundle\Monolog\ContextAwareExceptionInterface;
use Msstc4Symfony\LoggerBundle\Monolog\ContextAwareExceptionTrait;

class PaymentFailedException extends \RuntimeException implements ContextAwareExceptionInterface
{
    use ContextAwareExceptionTrait;

    public function __construct(
        string $message,
        private readonly string $transactionId,
        private readonly float $amount,
    ) {
        parent::__construct($message);

        $this->context = [
            'transaction_id' => $this->transactionId,
            'amount' => $this->amount,
        ];
    }
}


```

### Formatters

#### JsonFormatter

Formats log records as JSON with additional features:
- Adds `application` and `component` fields
- Extracts metrics (`count`, `size`, `duration`, `id`, `status`) from context into a separate `metrics` field
- Supports structured logging for log aggregation systems

#### SwitchFormatter

Intelligently switches between formatters based on the environment:
- **Human-readable format** - When running in CLI with `HUMAN_READABLE` env var set
- **JSON format** - For production environments and log aggregation

Example usage:

```bash
# Enable human-readable logs in CLI
HUMAN_READABLE=1 php bin/console your:command
```

### Handlers

#### ExceptionFilterDecorator

A PSR-3 logger decorator that filters out specific exception types from logs. This is useful for preventing log spam
from expected exceptions like 404 or validation errors.

The decorator is automatically applied to loggers based on your configuration.

## Usage Examples

### Basic Logging

```php
use Psr\Log\LoggerInterface;

class OrderService
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function processOrder(Order $order): void
    {
        $this->logger->info('Processing order', [
            'order_id' => $order->getId(),
            'amount' => $order->getTotal(),
        ]);

        // The log will automatically include:
        // - User ID (if authenticated)
        // - Request URL and IP (in HTTP context)
        // - Container ID
        // - Application and component names
    }
}
```

### Logging with Metrics

The bundle automatically extracts common metrics from log context:

```php
$this->logger->info('Database query executed', [
    'duration' => 0.245,  // Will be extracted to metrics.duration
    'count' => 42,        // Will be extracted to metrics.count
    'size' => 1024,       // Will be extracted to metrics.size
    'query' => 'SELECT * FROM users',  // Remains in context
]);
```

This produces a structured JSON log:

```json
{
  "message": "Database query executed",
  "level": "info",
  "application": "my-app",
  "component": "api",
  "metrics": {
    "duration": 0.245,
    "count": 42,
    "size": 1024
  },
  "context": {
    "query": "SELECT * FROM users"
  },
  "extra": {
    "url": "https://example.com/api/users",
    "ip": "192.168.1.1",
    "http_method": "GET",
    "user_id": "john.doe"
  }
}
```

### Context-Aware Exceptions

Create custom exceptions with additional context:

```php
use Msstc4Symfony\LoggerBundle\Monolog\ContextAwareExceptionInterface;
use Msstc4Symfony\LoggerBundle\Monolog\ContextAwareExceptionTrait;

class OrderProcessingException extends \RuntimeException implements ContextAwareExceptionInterface
{
    use ContextAwareExceptionTrait;

    public function __construct(
        string $message,
        private readonly string $orderId,
        private readonly string $status,
    ) {
        parent::__construct($message);

        $this->context = [
            'order_id' => $this->orderId,
            'status' => $this->status,
        ];
    }
}

// When logged, the exception's context will be automatically added to the log record
try {
    // ...
} catch (OrderProcessingException $e) {
    $this->logger->error('Order processing failed', ['exception' => $e]);
    // Log will include order_id and status from the exception context
}
```

### Advanced Configuration

#### Custom Application and Component Names

```yaml
# config/packages/logger.yaml
parameters:
  logger_bundle.applicationName: 'my-app'
  logger_bundle.componentName: 'php-fpm'
```

#### Adding More Exception Filters

```yaml
# config/packages/logger.yaml
parameters:
  logger_bundle.exception_classes:
    - Symfony\Component\HttpKernel\Exception\NotFoundHttpException
    - App\Exception\ExpectedBusinessException
    - App\Exception\ValidationException
```

## Development

### Running Tests

```bash
make test
```

### Code Quality

```bash
make check
```

## License

This bundle is released under the MIT License. See the [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Support

If you encounter any issues or have questions,
please [open an issue](https://github.com/msstc4symfony/logger-bundle/issues) on GitHub.
