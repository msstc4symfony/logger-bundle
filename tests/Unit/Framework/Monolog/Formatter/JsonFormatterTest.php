<?php

declare(strict_types=1);

namespace Msstc4Symfony\LoggerBundle\Test\Unit\Framework\Monolog\Formatter;

use DateTimeImmutable;
use Monolog\Level;
use Monolog\LogRecord;
use Msstc4Symfony\LoggerBundle\DependencyInjection\JsonRecordFields;
use Msstc4Symfony\LoggerBundle\Framework\Monolog\Formatter\JsonFormatter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class JsonFormatterTest extends TestCase
{
    public function testListedExtraKeysMoveToTheTopLevel(): void
    {
        $record = new LogRecord(
            new DateTimeImmutable('2026-10-22T10:00:00+00:00'),
            'app',
            Level::Info,
            'Handled',
            [],
            ['trace_id' => '4bf92f3577b34da6a3ce929d0e0e4736', 'span_id' => '00f067aa0ba902b7', 'url' => '/orders'],
        );

        $formatter = new JsonFormatter(topLevelExtra: ['trace_id', 'span_id', 'parent_span_id']);
        $decoded = json_decode($formatter->format($record), true, flags: JSON_THROW_ON_ERROR);

        self::assertIsArray($decoded);
        self::assertSame('4bf92f3577b34da6a3ce929d0e0e4736', $decoded['trace_id'] ?? null);
        self::assertSame('00f067aa0ba902b7', $decoded['span_id'] ?? null);
        self::assertArrayNotHasKey('parent_span_id', $decoded);
        self::assertSame(['url' => '/orders'], $decoded['extra'] ?? null);
    }

    public function testExtraEmptiedByTheMoveIsAnEmptyObject(): void
    {
        $record = new LogRecord(new DateTimeImmutable(), 'app', Level::Info, 'Handled', [], ['trace_id' => 'abc']);

        $json = (new JsonFormatter(topLevelExtra: ['trace_id']))->format($record);

        self::assertStringContainsString('"extra":{}', $json);
        self::assertStringContainsString('"trace_id":"abc"', $json);
    }

    public function testArrayAndNullValuesMoveAsTheyAre(): void
    {
        $record = new LogRecord(new DateTimeImmutable(), 'app', Level::Info, 'Handled', [], ['tags' => ['a' => [1, 2]], 'parent_span_id' => null]);

        $json = (new JsonFormatter(appendNewline: false, topLevelExtra: ['tags', 'parent_span_id']))->format($record);

        self::assertStringEndsWith('"tags":{"a":[1,2]},"parent_span_id":null}', $json);
    }

    public function testMovedKeysFollowTheOtherFieldsWhenEmptyContextAndExtraAreIgnored(): void
    {
        $record = new LogRecord(new DateTimeImmutable('2026-10-22T10:00:00+00:00'), 'app', Level::Info, 'Handled', [], ['trace_id' => 'abc']);

        $decoded = json_decode(
            (new JsonFormatter(ignoreEmptyContextAndExtra: true, topLevelExtra: ['trace_id']))->format($record),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        self::assertIsArray($decoded);
        self::assertSame('trace_id', array_key_last($decoded));
        self::assertSame([], $decoded['extra'] ?? null);
    }

    public function testTheReservedFieldsAreTheFieldsOfARecord(): void
    {
        $record = new LogRecord(new DateTimeImmutable(), 'app', Level::Info, 'Handled');

        $decoded = json_decode((new JsonFormatter())->format($record), true, flags: JSON_THROW_ON_ERROR);

        self::assertIsArray($decoded);
        self::assertEqualsCanonicalizing(JsonRecordFields::ALL, array_keys($decoded));
    }

    public function testNothingMovesByDefault(): void
    {
        $record = new LogRecord(new DateTimeImmutable(), 'app', Level::Info, 'Handled', [], ['trace_id' => 'abc']);

        $decoded = json_decode((new JsonFormatter())->format($record), true, flags: JSON_THROW_ON_ERROR);

        self::assertIsArray($decoded);
        self::assertArrayNotHasKey('trace_id', $decoded);
        self::assertSame(['trace_id' => 'abc'], $decoded['extra'] ?? null);
    }

    public function testFormatAppendsNewlineByDefault(): void
    {
        $record = new LogRecord(new DateTimeImmutable('2025-12-01 10:00:00'), 'test', Level::Info, 'test message');

        self::assertStringEndsWith("}\n", (new JsonFormatter())->format($record));
    }

    public function testFormatCanOmitTrailingNewline(): void
    {
        $record = new LogRecord(new DateTimeImmutable('2025-12-01 10:00:00'), 'test', Level::Info, 'test message');

        self::assertStringEndsNotWith("\n", (new JsonFormatter(appendNewline: false))->format($record));
    }

    #[DataProvider('getDataForFormat')]
    public function testFormat(LogRecord $record, string $expected): void
    {
        $formater = new JsonFormatter();
        $actual = $formater->format($record);

        self::assertSame($expected . "\n", $actual);
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
