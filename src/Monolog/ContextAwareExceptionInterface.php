<?php

declare(strict_types=1);

namespace Msstc4Symfony\LoggerBundle\Monolog;

interface ContextAwareExceptionInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getContext(): array;
}
