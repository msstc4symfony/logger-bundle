<?php

declare(strict_types=1);

namespace MaxShamaev\LoggerBundle\Test\Unit\Monolog\Handler;

use MaxShamaev\LoggerBundle\Monolog\Handler\ExceptionFilterDecorator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use RuntimeException;
use Stringable;
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
        $inner = $this->makeRecordingLogger();
        $decorator = new ExceptionFilterDecorator($inner, [NotFoundHttpException::class]);

        $decorator->log($level, 'lost', ['exception' => new NotFoundHttpException()]);

        self::assertSame([], $inner->records);
    }

    public function testDebugAlwaysPassesEvenForFilteredException(): void
    {
        $inner = $this->makeRecordingLogger();
        $decorator = new ExceptionFilterDecorator($inner, [NotFoundHttpException::class]);
        $exception = new NotFoundHttpException();

        $decorator->debug('lost', ['exception' => $exception]);

        self::assertCount(1, $inner->records);
        self::assertSame(LogLevel::DEBUG, $inner->records[0]['level']);
        self::assertSame($exception, $inner->records[0]['context']['exception']);
    }

    public function testNamedMethodForwardsThroughLog(): void
    {
        $inner = $this->makeRecordingLogger();
        $decorator = new ExceptionFilterDecorator($inner, [NotFoundHttpException::class]);

        $decorator->error('boom', ['exception' => new RuntimeException()]);

        self::assertCount(1, $inner->records);
        self::assertSame(LogLevel::ERROR, $inner->records[0]['level']);
    }

    public function testFiltersSubclassOfConfiguredException(): void
    {
        $inner = $this->makeRecordingLogger();
        $decorator = new ExceptionFilterDecorator($inner, [HttpException::class]);

        $decorator->warning('skip', ['exception' => new BadRequestHttpException()]);

        self::assertSame([], $inner->records);
    }

    public function testPassesThroughWhenNoExceptionInContext(): void
    {
        $inner = $this->makeRecordingLogger();
        $decorator = new ExceptionFilterDecorator($inner, [NotFoundHttpException::class]);

        $decorator->error('plain');

        self::assertCount(1, $inner->records);
    }

    public function testPassesThroughForUnfilteredException(): void
    {
        $inner = $this->makeRecordingLogger();
        $decorator = new ExceptionFilterDecorator($inner, [NotFoundHttpException::class]);

        $decorator->error('boom', ['exception' => new RuntimeException()]);

        self::assertCount(1, $inner->records);
    }

    /**
     * @return LoggerInterface&object{records: list<array{level: string, message: string|Stringable, context: array<array-key, mixed>}>}
     */
    private function makeRecordingLogger(): LoggerInterface
    {
        return new class extends AbstractLogger {
            /** @var list<array{level: string, message: string|Stringable, context: array<array-key, mixed>}> */
            public array $records = [];

            public function log($level, string|Stringable $message, array $context = []): void
            {
                if (!is_string($level)) {
                    throw new RuntimeException('Level must be a string.');
                }
                $this->records[] = ['level' => $level, 'message' => $message, 'context' => $context];
            }
        };
    }
}
