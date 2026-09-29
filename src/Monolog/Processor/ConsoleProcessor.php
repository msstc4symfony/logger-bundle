<?php

declare(strict_types=1);

namespace Msstc4Symfony\LoggerBundle\Monolog\Processor;

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

        $record->extra['cmd'] = implode(' ', array_filter($_SERVER['argv'], is_string(...)));

        return $record;
    }
}
