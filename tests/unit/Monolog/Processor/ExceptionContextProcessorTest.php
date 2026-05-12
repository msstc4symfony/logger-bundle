<?php

declare(strict_types=1);

namespace MaxShamaev\LoggerBundle\Test\Unit\Monolog\Processor;

use DateTimeImmutable;
use Exception;
use MaxShamaev\LoggerBundle\Monolog\ContextAwareExceptionInterface;
use MaxShamaev\LoggerBundle\Monolog\ContextAwareExceptionTrait;
use MaxShamaev\LoggerBundle\Monolog\Processor\ExceptionContextProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ExceptionContextProcessorTest extends TestCase
{
    #[DataProvider('getDataForInvoke')]
    public function testInvoke(LogRecord $record, LogRecord $expected): void
    {
        $processor = new ExceptionContextProcessor();
        $actual = $processor($record);
        self::assertEquals($expected, $actual);
    }

    /**
     * @return array<string, array{record: LogRecord, expected: LogRecord}>
     */
    public static function getDataForInvoke(): array
    {
        $exception = new class extends Exception implements ContextAwareExceptionInterface {
            use ContextAwareExceptionTrait;
        };
        $exception->setContext(['testk' => 'testv']);

        return [
            'simple' => [
                'record' => new LogRecord(new DateTimeImmutable('2025-12-01 10:00:00'), 'test', Level::Info, 'test message'),
                'expected' => new LogRecord(new DateTimeImmutable('2025-12-01 10:00:00'), 'test', Level::Info, 'test message'),
            ],
            'with context' => [
                'record' => new LogRecord(new DateTimeImmutable('2025-12-01 10:00:00'), 'test', Level::Info, 'test message', ['exception' => $exception]),
                'expected' => new LogRecord(new DateTimeImmutable('2025-12-01 10:00:00'), 'test', Level::Info, 'test message', ['exception' => $exception, 'testk' => 'testv']),
            ],
        ];
    }
}
