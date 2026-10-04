<?php

declare(strict_types=1);

use Monolog\Formatter\JsonFormatter as BaseJsonFormatter;
use Msstc4Symfony\LoggerBundle\Monolog\Formatter\JsonFormatter;
use Msstc4Symfony\LoggerBundle\Monolog\Formatter\SwitchFormatter;
use Msstc4Symfony\LoggerBundle\Monolog\Processor\ConsoleProcessor;
use Msstc4Symfony\LoggerBundle\Monolog\Processor\EnvironmentProcessor;
use Msstc4Symfony\LoggerBundle\Monolog\Processor\ExceptionContextProcessor;
use Msstc4Symfony\LoggerBundle\Monolog\Processor\UserProcessor;
use Msstc4Symfony\LoggerBundle\Monolog\Processor\WebProcessor;
use Msstc4Symfony\LoggerBundle\Sentry\Integration\LoggerIntegration;
use Sentry\Integration\IntegrationInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->parameters()->set('msstc4symfony_logger.unknown', 'unknown');

    $services = $container->services();
    $services->defaults()
        ->autowire()
        ->autoconfigure()
        ->bind('$batchMode', BaseJsonFormatter::BATCH_MODE_NEWLINES)
        ->bind('$includeStacktraces', '%kernel.debug%')
    ;

    $services->set(JsonFormatter::class);
    $services->set(SwitchFormatter::class);

    // Opt-in: consumers reference this service id in sentry.options.integrations.
    if (interface_exists(IntegrationInterface::class)) {
        $services->set(LoggerIntegration::class);
    }

    foreach ([
        ConsoleProcessor::class,
        EnvironmentProcessor::class,
        ExceptionContextProcessor::class,
        UserProcessor::class,
        WebProcessor::class,
    ] as $processor) {
        $services->set($processor)->tag('monolog.processor');
    }
};
