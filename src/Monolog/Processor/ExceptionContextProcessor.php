<?php

declare(strict_types=1);

namespace MaxShamaev\LoggerBundle\Monolog\Processor;

use MaxShamaev\LoggerBundle\Monolog\ContextAwareExceptionInterface;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Override;
use Throwable;

final class ExceptionContextProcessor implements ProcessorInterface
{
    #[Override]
    public function __invoke(LogRecord $record): LogRecord
    {
        if (!isset($record->context['exception'])) {
            return $record;
        }

        $originalException = $record->context['exception'];

        if (!$originalException instanceof Throwable) {
            return $record;
        }

        foreach ($this->prepareException($originalException) as $exception) {
            if ($exception instanceof ContextAwareExceptionInterface) {
                $data = [
                    'context' => array_merge($record->context, $exception->getContext()),
                ];
                $record = $record->with(...$data);
            }
        }

        return $record;
    }

    /**
     * @return Throwable[]
     */
    private function prepareException(Throwable $exception): array
    {
        $exceptions = [];

        do {
            // prevents infinite recursion with chained objects
            if (isset($exceptions[spl_object_hash($exception)])) {
                return $exceptions;
            }
            $exceptions[spl_object_hash($exception)] = $exception;
            $exception = $exception->getPrevious();
        } while ($exception instanceof Throwable);

        return $exceptions;
    }
}
