<?php

declare(strict_types=1);

namespace MaxShamaev\LoggerBundle\Test\Unit\Monolog\Processor;

use DateTimeImmutable;
use MaxShamaev\LoggerBundle\Monolog\Processor\EnvironmentProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EnvironmentProcessorTest extends TestCase
{
    #[DataProvider('getDataForInvoke')]
    public function testInvoke(?string $podName, ?string $podUid, string $expectedContainerId): void
    {
        $processor = new EnvironmentProcessor($podName, $podUid);
        $record = new LogRecord(new DateTimeImmutable('2025-12-01 10:00:00'), 'test', Level::Info, 'test message');
        $expected = new LogRecord(
            new DateTimeImmutable('2025-12-01 10:00:00'),
            'test',
            Level::Info,
            'test message',
            extra: ['sapi' => 'cli', 'container_id' => $expectedContainerId],
        );

        self::assertEquals($expected, $processor($record));
    }

    /**
     * @return array<string, array{podName: ?string, podUid: ?string, expectedContainerId: string}>
     */
    public static function getDataForInvoke(): array
    {
        $hostname = gethostname();
        self::assertIsString($hostname);

        return [
            'pod name wins' => [
                'podName' => 'pod-name-1',
                'podUid' => 'pod-uid-1',
                'expectedContainerId' => 'pod-name-1',
            ],
            'pod uid as fallback' => [
                'podName' => null,
                'podUid' => 'pod-uid-1',
                'expectedContainerId' => 'pod-uid-1',
            ],
            'empty pod name falls through to uid' => [
                'podName' => '',
                'podUid' => 'pod-uid-1',
                'expectedContainerId' => 'pod-uid-1',
            ],
            'hostname as last resort' => [
                'podName' => null,
                'podUid' => null,
                'expectedContainerId' => $hostname,
            ],
        ];
    }
}
