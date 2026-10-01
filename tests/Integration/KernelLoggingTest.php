<?php

declare(strict_types=1);

namespace Msstc4Symfony\LoggerBundle\Test\Integration;

use Monolog\Handler\TestHandler;
use Monolog\LogRecord;
use Msstc4Symfony\LoggerBundle\Test\Integration\Kernel\TestKernel;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[CoversNothing]
final class KernelLoggingTest extends TestCase
{
    private TestKernel $kernel;

    protected function setUp(): void
    {
        new Filesystem()->remove(TestKernel::cacheRoot());
        $this->kernel = new TestKernel('test', false);
        $this->kernel->boot();
    }

    protected function tearDown(): void
    {
        $this->kernel->shutdown();
        new Filesystem()->remove(TestKernel::cacheRoot());
    }

    public function testBundleProcessorsEnrichEveryRecord(): void
    {
        $this->logger()->info('order created');

        $record = $this->handler()->getRecords()[0] ?? null;
        self::assertNotNull($record);
        self::assertSame(PHP_SAPI, $record->extra['sapi'] ?? null);
        self::assertArrayHasKey('container_id', $record->extra);
    }

    public function testConfiguredExceptionsAreFilteredOthersAreLogged(): void
    {
        $this->logger()->error('not found', ['exception' => new NotFoundHttpException()]);
        $this->logger()->error('broken', ['exception' => new RuntimeException('boom')]);

        self::assertSame(['broken'], array_map(static fn (LogRecord $record): string => $record->message, $this->handler()->getRecords()));
    }

    private function logger(): LoggerInterface
    {
        $logger = $this->kernel->getContainer()->get('test.logger');
        self::assertInstanceOf(LoggerInterface::class, $logger);

        return $logger;
    }

    private function handler(): TestHandler
    {
        $handler = $this->kernel->getContainer()->get('test.main_handler');
        self::assertInstanceOf(TestHandler::class, $handler);

        return $handler;
    }
}
