<?php

declare(strict_types=1);

namespace Msstc4Symfony\LoggerBundle\Test\Unit\Monolog\Processor;

use DateTimeImmutable;
use Monolog\Level;
use Monolog\LogRecord;
use Msstc4Symfony\LoggerBundle\Monolog\Processor\WebProcessor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class WebProcessorTest extends TestCase
{
    #[DataProvider('getDataForInvoke')]
    public function testInvoke(LogRecord $record, RequestStack $requestStack, LogRecord $expected): void
    {
        $processor = new WebProcessor($requestStack);
        $actual = $processor($record);
        self::assertEquals($expected, $actual);
    }

    /**
     * @return array<string, array{record: LogRecord, requestStack: RequestStack, expected: LogRecord}>
     */
    public static function getDataForInvoke(): array
    {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create('https://example.com'));

        return [
            'simple' => [
                'record' => new LogRecord(new DateTimeImmutable('2025-12-01 10:00:00'), 'test', Level::Info, 'test message'),
                'requestStack' => $requestStack,
                'expected' => new LogRecord(new DateTimeImmutable('2025-12-01 10:00:00'), 'test', Level::Info, 'test message', extra: ['url' => 'https://example.com/', 'ip' => '127.0.0.1', 'http_method' => 'GET']),
            ],
        ];
    }
}
