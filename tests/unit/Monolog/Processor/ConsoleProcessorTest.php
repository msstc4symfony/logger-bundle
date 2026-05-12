<?php

declare(strict_types=1);

namespace MaxShamaev\LoggerBundle\Test\Unit\Monolog\Processor;

use DateTimeImmutable;
use MaxShamaev\LoggerBundle\Monolog\Processor\ConsoleProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ConsoleProcessorTest extends TestCase
{
    /**
     * @var string[]
     */
    private array $argv = [];

    protected function setUp(): void
    {
        parent::setUp();

        /** @var array{argv?: string[]} $_SERVER */
        $this->argv = isset($_SERVER['argv']) && is_array($_SERVER['argv']) ? $_SERVER['argv'] : [];
    }

    protected function tearDown(): void
    {
        $_SERVER['argv'] = $this->argv;

        parent::tearDown();
    }

    #[DataProvider('getDataForInvoke')]
    public function testInvoke(LogRecord $record, LogRecord $expected): void
    {
        $_SERVER['argv'] = ['php', 'test.php'];

        $processor = new ConsoleProcessor();
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
                'expected' => new LogRecord(new DateTimeImmutable('2025-12-01 10:00:00'), 'test', Level::Info, 'test message', extra: ['cmd' => 'php test.php']),
            ],
        ];
    }
}
