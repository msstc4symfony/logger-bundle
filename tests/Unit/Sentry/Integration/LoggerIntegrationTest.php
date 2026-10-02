<?php

declare(strict_types=1);

namespace Msstc4Symfony\LoggerBundle\Test\Unit\Sentry\Integration;

use Msstc4Symfony\LoggerBundle\Sentry\Integration\LoggerIntegration;
use Override;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Sentry\ClientBuilder;
use Sentry\Event;
use Sentry\SentrySdk;
use Sentry\State\Hub;
use Sentry\State\Scope;

final class LoggerIntegrationTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        $this->resetGlobalEventProcessors();
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->resetGlobalEventProcessors();
        SentrySdk::init();
    }

    public function testSetupOnceTagsEventsWithApplicationAndComponent(): void
    {
        $integration = new LoggerIntegration('billing', 'worker');
        $this->bindHubWith($integration);

        $integration->setupOnce();
        $event = Event::createEvent();
        $processed = new Scope()->applyToEvent($event);

        self::assertSame($event, $processed);
        self::assertSame(['application' => 'billing', 'component' => 'worker'], $event->getTags());
    }

    public function testSetupOnceRegistersExactlyOneGlobalEventProcessor(): void
    {
        new LoggerIntegration('billing', 'worker')->setupOnce();

        $processors = $this->globalEventProcessors()->getValue();
        self::assertIsArray($processors);
        self::assertCount(1, $processors);
    }

    public function testTagsComeFromTheIntegrationRegisteredOnTheCurrentHub(): void
    {
        $this->bindHubWith(new LoggerIntegration('current-app', 'current-component'));

        new LoggerIntegration('stale-app', 'stale-component')->setupOnce();
        $event = Event::createEvent();
        $processed = new Scope()->applyToEvent($event);

        self::assertSame($event, $processed);
        self::assertSame(['application' => 'current-app', 'component' => 'current-component'], $event->getTags());
    }

    public function testEventIsLeftUntaggedWhenTheCurrentHubHasNoLoggerIntegration(): void
    {
        SentrySdk::setCurrentHub(new Hub(ClientBuilder::create(['default_integrations' => false])->getClient()));

        new LoggerIntegration('billing', 'worker')->setupOnce();
        $event = Event::createEvent();
        $processed = new Scope()->applyToEvent($event);

        self::assertSame($event, $processed);
        self::assertSame([], $event->getTags());
    }

    public function testEventIsLeftUntaggedWhenTheCurrentHubHasNoClient(): void
    {
        SentrySdk::setCurrentHub(new Hub());

        new LoggerIntegration('billing', 'worker')->setupOnce();
        $event = Event::createEvent();
        $processed = new Scope()->applyToEvent($event);

        self::assertSame($event, $processed);
        self::assertSame([], $event->getTags());
    }

    private function bindHubWith(LoggerIntegration $integration): void
    {
        $client = ClientBuilder::create([
            'default_integrations' => false,
            'integrations' => [$integration],
        ])->getClient();

        SentrySdk::setCurrentHub(new Hub($client));
        // Building the client may run setupOnce() through Sentry's process-wide registry; start each test clean.
        $this->resetGlobalEventProcessors();
    }

    private function resetGlobalEventProcessors(): void
    {
        $this->globalEventProcessors()->setValue(null, []);
    }

    // The SDK has no public reset for its process-wide processor list; fail loudly if the internal field moves.
    private function globalEventProcessors(): ReflectionProperty
    {
        if (!property_exists(Scope::class, 'globalEventProcessors')) {
            self::fail(Scope::class . '::$globalEventProcessors is gone; update how this test resets global event processors.');
        }

        return new ReflectionProperty(Scope::class, 'globalEventProcessors');
    }
}
