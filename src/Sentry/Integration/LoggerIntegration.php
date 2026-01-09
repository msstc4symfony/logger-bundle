<?php

declare(strict_types=1);

namespace MaxShamaev\LoggerBundle\Sentry\Integration;

use Sentry\Event;
use Sentry\Integration\IntegrationInterface;
use Sentry\SentrySdk;
use Sentry\State\Scope;

final class LoggerIntegration implements IntegrationInterface
{
    public function __construct(
        private readonly string $applicationName,
        private readonly string $componentName,
    ) {
    }

    public function setupOnce(): void
    {
        Scope::addGlobalEventProcessor(
            static function (Event $event): Event {
                $integration = SentrySdk::getCurrentHub()->getIntegration(self::class);

                if ($integration instanceof IntegrationInterface) {
                    $event->setTag('application', $integration->applicationName);
                    $event->setTag('component', $integration->componentName);
                }

                return $event;
            },
        );
    }
}
