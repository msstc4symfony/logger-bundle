<?php

declare(strict_types=1);

namespace MaxShamaev\LoggerBundle\Monolog;

interface ContextAwareExceptionInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getContext(): array;
}
