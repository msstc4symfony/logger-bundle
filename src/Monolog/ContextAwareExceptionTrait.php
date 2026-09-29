<?php

declare(strict_types=1);

namespace Msstc4Symfony\LoggerBundle\Monolog;

trait ContextAwareExceptionTrait
{
    /**
     * @var array<string, mixed>
     */
    private array $context = [];

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function setContext(array $context): static
    {
        $this->context = $context;

        return $this;
    }
}
