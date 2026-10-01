<?php

declare(strict_types=1);

namespace Msstc4Symfony\LoggerBundle\Test\Unit\Monolog\Processor;

use DateTimeImmutable;
use Monolog\Level;
use Monolog\LogRecord;
use Msstc4Symfony\LoggerBundle\Monolog\Processor\UserProcessor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class UserProcessorTest extends TestCase
{
    #[DataProvider('getDataForInvoke')]
    public function testInvoke(LogRecord $record, ?TokenStorageInterface $tokenStorage, LogRecord $expected): void
    {
        $processor = new UserProcessor($tokenStorage);
        $actual = $processor($record);
        self::assertEquals($expected, $actual);
    }

    /**
     * @return array<string, array{record: LogRecord, tokenStorage: ?TokenStorageInterface, expected: LogRecord}>
     */
    public static function getDataForInvoke(): array
    {
        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken(new NullToken());

        return [
            'simple' => [
                'record' => new LogRecord(new DateTimeImmutable('2025-12-01 10:00:00'), 'test', Level::Info, 'test message'),
                'tokenStorage' => null,
                'expected' => new LogRecord(new DateTimeImmutable('2025-12-01 10:00:00'), 'test', Level::Info, 'test message'),
            ],
            'with user' => [
                'record' => new LogRecord(new DateTimeImmutable('2025-12-01 10:00:00'), 'test', Level::Info, 'test message'),
                'tokenStorage' => $tokenStorage,
                'expected' => new LogRecord(new DateTimeImmutable('2025-12-01 10:00:00'), 'test', Level::Info, 'test message', extra: ['user_id' => '']),
            ],
        ];
    }
}
