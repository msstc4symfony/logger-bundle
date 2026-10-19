<?php

declare(strict_types=1);

namespace Msstc4Symfony\LoggerBundle\Application\Exception;

interface ContextAwareExceptionInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getContext(): array;
}
