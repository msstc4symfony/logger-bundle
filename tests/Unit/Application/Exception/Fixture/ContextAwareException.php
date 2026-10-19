<?php

declare(strict_types=1);

namespace Msstc4Symfony\LoggerBundle\Test\Unit\Application\Exception\Fixture;

use Msstc4Symfony\LoggerBundle\Application\Exception\ContextAwareExceptionInterface;
use Msstc4Symfony\LoggerBundle\Application\Exception\ContextAwareExceptionTrait;
use RuntimeException;

final class ContextAwareException extends RuntimeException implements ContextAwareExceptionInterface
{
    use ContextAwareExceptionTrait;
}
