<?php

declare(strict_types=1);

namespace Msstc4Symfony\LoggerBundle\Test\Unit\Monolog\Formatter;

use DateTimeImmutable;
use Monolog\Formatter\LineFormatter;
use Monolog\Level;
use Monolog\LogRecord;
use Msstc4Symfony\LoggerBundle\Monolog\Formatter\JsonFormatter;
use Msstc4Symfony\LoggerBundle\Monolog\Formatter\SwitchFormatter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;

final class SwitchFormatterTest extends TestCase
{
    #[DataProvider('getDataForFormat')]
    public function testFormat(LogRecord $record, bool $humanReadable, string $expected): void
    {
        $formater = new SwitchFormatter(
            new LineFormatter(),
            new JsonFormatter(JsonFormatter::BATCH_MODE_NEWLINES),
            new RequestStack(),
            $humanReadable ? '1' : null,
        );
        $actual = $formater->format($record);

        self::assertSame($expected, $actual);
    }

    /**
     * @param LogRecord[] $records
     */
    #[DataProvider('getDataForFormatBatch')]
    public function testFormatBatch(array $records, bool $humanReadable, string $expected): void
    {
        $formater = new SwitchFormatter(
            new LineFormatter(),
            new JsonFormatter(JsonFormatter::BATCH_MODE_NEWLINES),
            new RequestStack(),
            $humanReadable ? '1' : null,
        );
        $actual = $formater->formatBatch($records);

        self::assertSame($expected, $actual);
    }

    /**
     * @return array<string, array{record: LogRecord, humanReadable: bool, expected: string}>
     */
    public static function getDataForFormat(): array
    {
        return [
            'human readable' => [
                'record' => new LogRecord(new DateTimeImmutable('2025-12-01 10:00:00'), 'test', Level::Info, 'test message'),
                'humanReadable' => true,
                'expected' => '[2025-12-01T10:00:00+00:00] test.INFO: test message [] []' . PHP_EOL,
            ],
            'regular' => [
                'record' => new LogRecord(new DateTimeImmutable('2025-12-01 10:00:00'), 'test', Level::Info, 'test message'),
                'humanReadable' => false,
                'expected' => '{"message":"test message","context":{},"level":200,"level_name":"INFO","channel":"test","datetime":"2025-12-01T10:00:00+00:00","extra":{},"application":"unknown","component":"unknown","metrics":{}}',
            ],
        ];
    }

    /**
     * @return array<string, array{records: LogRecord[], humanReadable: bool, expected: string}>
     */
    public static function getDataForFormatBatch(): array
    {
        return [
            'human readable' => [
                'records' => [new LogRecord(new DateTimeImmutable('2025-12-01 10:00:00'), 'test', Level::Info, 'test message')],
                'humanReadable' => true,
                'expected' => '[2025-12-01T10:00:00+00:00] test.INFO: test message [] []' . PHP_EOL,
            ],
            'regular' => [
                'records' => [new LogRecord(new DateTimeImmutable('2025-12-01 10:00:00'), 'test', Level::Info, 'test message')],
                'humanReadable' => false,
                'expected' => '{"message":"test message","context":{},"level":200,"level_name":"INFO","channel":"test","datetime":"2025-12-01T10:00:00+00:00","extra":{},"application":"unknown","component":"unknown","metrics":{}}',
            ],
        ];
    }
}
