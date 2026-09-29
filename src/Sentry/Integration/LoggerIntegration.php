<?php

declare(strict_types=1);

namespace Msstc4Symfony\LoggerBundle\Sentry\Integration;

use Sentry\Event;
use Sentry\Integration\IntegrationInterface;
use Sentry\SentrySdk;
use Sentry\State\Scope;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class LoggerIntegration implements IntegrationInterface
{
    public function __construct(
        #[Autowire(param: 'logger_bundle.applicationName')]
        private readonly string $applicationName,
        #[Autowire(param: 'logger_bundle.componentName')]
        private readonly string $componentName,
    ) {
    }

    public function setupOnce(): void
    {
        Scope::addGlobalEventProcessor(
            static function (Event $event): Event {
                $integration = SentrySdk::getCurrentHub()->getIntegration(self::class);

                if ($integration instanceof self) {
                    $event->setTag('application', $integration->applicationName);
                    $event->setTag('component', $integration->componentName);
                }

                return $event;
            },
        );
    }
}
