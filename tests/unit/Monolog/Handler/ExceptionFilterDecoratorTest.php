<?php

declare(strict_types=1);

namespace Msstc4Symfony\LoggerBundle\Test\Unit\Monolog\Handler;

use Msstc4Symfony\LoggerBundle\Monolog\Handler\ExceptionFilterDecorator;
use Msstc4Symfony\LoggerBundle\Test\Unit\Monolog\Handler\Fixture\RecordingLogger;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ExceptionFilterDecoratorTest extends TestCase
{
    /**
     * @return iterable<string, array{level: string}>
     */
    public static function provideFilteringLevels(): iterable
    {
        yield 'emergency' => ['level' => LogLevel::EMERGENCY];
        yield 'alert' => ['level' => LogLevel::ALERT];
        yield 'critical' => ['level' => LogLevel::CRITICAL];
        yield 'error' => ['level' => LogLevel::ERROR];
        yield 'warning' => ['level' => LogLevel::WARNING];
        yield 'notice' => ['level' => LogLevel::NOTICE];
        yield 'info' => ['level' => LogLevel::INFO];
    }

    #[DataProvider('provideFilteringLevels')]
    public function testSkipsFilteredExceptionForNonDebugLevel(string $level): void
    {
        $inner = new RecordingLogger();
        $decorator = new ExceptionFilterDecorator($inner, [NotFoundHttpException::class]);

        $decorator->log($level, 'lost', ['exception' => new NotFoundHttpException()]);

        self::assertSame([], $inner->records);
    }

    public function testDebugAlwaysPassesEvenForFilteredException(): void
    {
        $inner = new RecordingLogger();
        $decorator = new ExceptionFilterDecorator($inner, [NotFoundHttpException::class]);
        $exception = new NotFoundHttpException();

        $decorator->debug('lost', ['exception' => $exception]);

        self::assertCount(1, $inner->records);
        self::assertSame(LogLevel::DEBUG, $inner->records[0]['level']);
        self::assertSame($exception, $inner->records[0]['context']['exception']);
    }

    public function testNamedMethodForwardsThroughLog(): void
    {
        $inner = new RecordingLogger();
        $decorator = new ExceptionFilterDecorator($inner, [NotFoundHttpException::class]);

        $decorator->error('boom', ['exception' => new RuntimeException()]);

        self::assertCount(1, $inner->records);
        self::assertSame(LogLevel::ERROR, $inner->records[0]['level']);
    }

    public function testNamedMethodDropsFilteredException(): void
    {
        $inner = new RecordingLogger();
        $decorator = new ExceptionFilterDecorator($inner, [NotFoundHttpException::class]);

        $decorator->error('lost', ['exception' => new NotFoundHttpException()]);

        self::assertSame([], $inner->records);
    }

    public function testFiltersSubclassOfConfiguredException(): void
    {
        $inner = new RecordingLogger();
        $decorator = new ExceptionFilterDecorator($inner, [HttpException::class]);

        $decorator->warning('skip', ['exception' => new BadRequestHttpException()]);

        self::assertSame([], $inner->records);
    }

    public function testPassesThroughWhenNoExceptionInContext(): void
    {
        $inner = new RecordingLogger();
        $decorator = new ExceptionFilterDecorator($inner, [NotFoundHttpException::class]);

        $decorator->error('plain');

        self::assertCount(1, $inner->records);
    }

    public function testPassesThroughForUnfilteredException(): void
    {
        $inner = new RecordingLogger();
        $decorator = new ExceptionFilterDecorator($inner, [NotFoundHttpException::class]);

        $decorator->error('boom', ['exception' => new RuntimeException()]);

        self::assertCount(1, $inner->records);
    }

    public function testPassesThroughWhenExceptionContextIsNotObject(): void
    {
        $inner = new RecordingLogger();
        $decorator = new ExceptionFilterDecorator($inner, [NotFoundHttpException::class]);

        $decorator->error('plain', ['exception' => 'not-an-object']);

        self::assertCount(1, $inner->records);
        self::assertSame('not-an-object', $inner->records[0]['context']['exception']);
    }
}
