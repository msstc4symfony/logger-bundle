<?php

declare(strict_types=1);

namespace MaxShamaev\LoggerBundle\Monolog\Processor;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Override;

final class ConsoleProcessor implements ProcessorInterface
{
    #[Override]
    public function __invoke(LogRecord $record): LogRecord
    {
        if (PHP_SAPI !== 'cli' || !isset($_SERVER['argv']) || !is_array($_SERVER['argv']) || $_SERVER['argv'] === []) {
            return $record;
        }

        $record->extra['cmd'] = implode(' ', $_SERVER['argv']);

        return $record;
    }
}
