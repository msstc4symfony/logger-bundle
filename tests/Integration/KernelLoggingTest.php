<?php

declare(strict_types=1);

namespace Msstc4Symfony\LoggerBundle\Test\Integration;

use Monolog\Handler\StreamHandler;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Monolog\LogRecord;
use Msstc4Symfony\LoggerBundle\Monolog\Formatter\JsonFormatter;
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

    // Log shippers (Loki, Fluent Bit, ELK) read one JSON document per line.
    public function testJsonFormatterWritesOneRecordPerLine(): void
    {
        $formatter = $this->kernel->getContainer()->get('test.json_formatter');
        self::assertInstanceOf(JsonFormatter::class, $formatter);
        $stream = fopen('php://memory', 'w+');
        self::assertIsResource($stream);
        $handler = new StreamHandler($stream);
        $handler->setFormatter($formatter);

        $logger = new Logger('app', [$handler]);

        $logger->info('first');
        $logger->info('second');
        rewind($stream);
        $output = (string) stream_get_contents($stream);
        $handler->close();

        $messages = array_map(
            static fn (string $line): array => (array) json_decode($line, true, flags: JSON_THROW_ON_ERROR),
            explode("\n", rtrim($output, "\n")),
        );
        self::assertSame(['first', 'second'], array_column($messages, 'message'));
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
