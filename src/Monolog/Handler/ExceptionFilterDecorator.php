<?php

declare(strict_types=1);

namespace Msstc4Symfony\LoggerBundle\Monolog\Handler;

use Override;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Stringable;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class ExceptionFilterDecorator extends AbstractLogger
{
    /**
     * @param list<class-string> $exceptionClasses
     */
    public function __construct(
        private readonly LoggerInterface $inner,
        #[Autowire(param: 'logger_bundle.exception_classes')]
        private readonly array $exceptionClasses = [],
    ) {
    }

    #[Override]
    public function log($level, string|Stringable $message, array $context = []): void
    {
        if ($level !== LogLevel::DEBUG && $this->mustSkip($context)) {
            return;
        }

        $this->inner->log($level, $message, $context);
    }

    /**
     * @param array<array-key, mixed> $context
     */
    private function mustSkip(array $context): bool
    {
        if (!isset($context['exception']) || !is_object($context['exception'])) {
            return false;
        }

        return array_any(
            $this->exceptionClasses,
            static fn (string $class): bool => $context['exception'] instanceof $class,
        );
    }
}
