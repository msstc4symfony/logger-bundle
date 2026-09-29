<?php

declare(strict_types=1);

namespace Msstc4Symfony\LoggerBundle\Test\Unit\Monolog\Formatter;

use DateTimeImmutable;
use Monolog\Level;
use Monolog\LogRecord;
use Msstc4Symfony\LoggerBundle\Monolog\Formatter\JsonFormatter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class JsonFormatterTest extends TestCase
{
    #[DataProvider('getDataForFormat')]
    public function testFormat(LogRecord $record, string $expected): void
    {
        $formater = new JsonFormatter();
        $actual = $formater->format($record);

        self::assertSame($expected, $actual);
    }

    /**
     * @param LogRecord[] $records
     */
    #[DataProvider('getDataForFormatBatch')]
    public function testFormatBatch(array $records, string $expected): void
    {
        $formater = new JsonFormatter(JsonFormatter::BATCH_MODE_NEWLINES);
        $actual = $formater->formatBatch($records);

        self::assertSame($expected, $actual);
    }

    /**
     * @return array<string, array{record: LogRecord, expected: string}>
     */
    public static function getDataForFormat(): array
    {
        return [
            'simple' => [
                'record' => new LogRecord(new DateTimeImmutable('2025-12-01 10:00:00'), 'test', Level::Info, 'test message'),
                'expected' => '{"message":"test message","context":{},"level":200,"level_name":"INFO","channel":"test","datetime":"2025-12-01T10:00:00+00:00","extra":{},"application":"unknown","component":"unknown","metrics":{}}',
            ],
            'context + extra' => [
                'record' => new LogRecord(new DateTimeImmutable('2025-12-01 10:00:00'), 'test', Level::Info, 'test message', ['testc' => 123], ['teste' => 123]),
                'expected' => '{"message":"test message","context":{"testc":123},"level":200,"level_name":"INFO","channel":"test","datetime":"2025-12-01T10:00:00+00:00","extra":{"teste":123},"application":"unknown","component":"unknown","metrics":{}}',
            ],
            'metrics' => [
                'record' => new LogRecord(new DateTimeImmutable('2025-12-01 10:00:00'), 'test', Level::Info, 'test message', ['id' => 123]),
                'expected' => '{"message":"test message","context":{},"level":200,"level_name":"INFO","channel":"test","datetime":"2025-12-01T10:00:00+00:00","extra":{},"application":"unknown","component":"unknown","metrics":{"id":"123"}}',
            ],
        ];
    }

    /**
     * @return array<string, array{records: LogRecord[], expected: string}>
     */
    public static function getDataForFormatBatch(): array
    {
        return [
            'complex' => [
                'records' => [
                    new LogRecord(new DateTimeImmutable('2025-12-01 10:00:00'), 'test', Level::Info, 'test message'),
                    new LogRecord(new DateTimeImmutable('2025-12-01 10:00:00'), 'test', Level::Info, 'test message', ['testc' => 123], ['teste' => 123]),
                    new LogRecord(new DateTimeImmutable('2025-12-01 10:00:00'), 'test', Level::Info, 'test message', ['id' => 123]),
                ],
                'expected' => '{"message":"test message","context":{},"level":200,"level_name":"INFO","channel":"test","datetime":"2025-12-01T10:00:00+00:00","extra":{},"application":"unknown","component":"unknown","metrics":{}}' . PHP_EOL
                    . '{"message":"test message","context":{"testc":123},"level":200,"level_name":"INFO","channel":"test","datetime":"2025-12-01T10:00:00+00:00","extra":{"teste":123},"application":"unknown","component":"unknown","metrics":{}}' . PHP_EOL
                    . '{"message":"test message","context":{},"level":200,"level_name":"INFO","channel":"test","datetime":"2025-12-01T10:00:00+00:00","extra":{},"application":"unknown","component":"unknown","metrics":{"id":"123"}}',
            ],
        ];
    }
}
