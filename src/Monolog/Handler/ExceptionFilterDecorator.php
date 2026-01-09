<?php

declare(strict_types=1);

namespace MaxShamaev\LoggerBundle\Monolog\Handler;

use Override;
use Psr\Log\LoggerInterface;
use Stringable;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class ExceptionFilterDecorator implements LoggerInterface
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
    public function emergency(Stringable|string $message, array $context = []): void
    {
        if ($this->mustSkip($context)) {
            return;
        }

        $this->inner->emergency($message, $context);
    }

    #[Override]
    public function alert(Stringable|string $message, array $context = []): void
    {
        if ($this->mustSkip($context)) {
            return;
        }

        $this->inner->alert($message, $context);
    }

    #[Override]
    public function critical(Stringable|string $message, array $context = []): void
    {
        if ($this->mustSkip($context)) {
            return;
        }

        $this->inner->critical($message, $context);
    }

    #[Override]
    public function error(Stringable|string $message, array $context = []): void
    {
        if ($this->mustSkip($context)) {
            return;
        }

        $this->inner->error($message, $context);
    }

    #[Override]
    public function warning(Stringable|string $message, array $context = []): void
    {
        if ($this->mustSkip($context)) {
            return;
        }

        $this->inner->warning($message, $context);
    }

    #[Override]
    public function notice(Stringable|string $message, array $context = []): void
    {
        if ($this->mustSkip($context)) {
            return;
        }

        $this->inner->notice($message, $context);
    }

    #[Override]
    public function info(Stringable|string $message, array $context = []): void
    {
        if ($this->mustSkip($context)) {
            return;
        }

        $this->inner->info($message, $context);
    }

    #[Override]
    public function debug(Stringable|string $message, array $context = []): void
    {
        $this->inner->debug($message, $context);
    }

    #[Override]
    public function log($level, Stringable|string $message, array $context = []): void
    {
        if ($this->mustSkip($context)) {
            return;
        }

        $this->inner->log($level, $message, $context);
    }

    /**
     * @param array<array-key, mixed> $context
     */
    private function mustSkip(array $context): bool
    {
        if (isset($context['exception']) && is_object($context['exception'])) {
            foreach ($this->exceptionClasses as $class) {
                if ($context['exception'] instanceof $class || is_subclass_of($context['exception'], $class)) {
                    return true;
                }
            }
        }

        return false;
    }
}
