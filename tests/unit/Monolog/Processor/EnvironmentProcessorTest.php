<?php

declare(strict_types=1);

namespace unit\Monolog\Processor;

use DateTimeImmutable;
use MaxShamaev\LoggerBundle\Monolog\Processor\EnvironmentProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EnvironmentProcessorTest extends TestCase
{
    #[DataProvider('getDataForInvoke')]
    public function testInvoke(LogRecord $record, LogRecord $expected): void
    {
        $_ENV['POD_NAME'] = 'test';

        $processor = new EnvironmentProcessor();
        $actual = $processor($record);
        self::assertEquals($expected, $actual);
    }

    /**
     * @return array<string, array{record: LogRecord, expected: LogRecord}>
     */
    public static function getDataForInvoke(): array
    {
        return [
            'simple' => [
                'record' => new LogRecord(new DateTimeImmutable('2025-12-01 10:00:00'), 'test', Level::Info, 'test message'),
                'expected' => new LogRecord(new DateTimeImmutable('2025-12-01 10:00:00'), 'test', Level::Info, 'test message', extra: ['sapi' => 'cli', 'container_id' => 'test']),
            ],
        ];
    }
}
