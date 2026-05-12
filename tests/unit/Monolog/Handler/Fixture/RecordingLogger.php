<?php

declare(strict_types=1);

namespace MaxShamaev\LoggerBundle\Test\Unit\Monolog\Handler\Fixture;

use Override;
use Psr\Log\AbstractLogger;
use RuntimeException;
use Stringable;

/**
 * In-memory PSR-3 logger that captures every call for assertion.
 */
final class RecordingLogger extends AbstractLogger
{
    /** @var list<array{level: string, message: string|Stringable, context: array<array-key, mixed>}> */
    public array $records = [];

    #[Override]
    public function log($level, string|Stringable $message, array $context = []): void
    {
        if (!is_string($level)) {
            throw new RuntimeException('Level must be a string.');
        }

        $this->records[] = ['level' => $level, 'message' => $message, 'context' => $context];
    }
}
