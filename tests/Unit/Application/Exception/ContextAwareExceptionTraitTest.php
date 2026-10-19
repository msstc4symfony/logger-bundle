<?php

declare(strict_types=1);

namespace Msstc4Symfony\LoggerBundle\Test\Unit\Application\Exception;

use Msstc4Symfony\LoggerBundle\Application\Exception\ContextAwareExceptionTrait;
use Msstc4Symfony\LoggerBundle\Test\Unit\Application\Exception\Fixture\ContextAwareException;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;

#[CoversTrait(ContextAwareExceptionTrait::class)]
final class ContextAwareExceptionTraitTest extends TestCase
{
    public function testStartsWithAnEmptyContext(): void
    {
        self::assertSame([], $this->exception()->getContext());
    }

    public function testSetContextReplacesTheContextAndReturnsTheSameException(): void
    {
        $exception = $this->exception();

        self::assertSame($exception, $exception->setContext(['order' => 42]));
        self::assertSame(['order' => 42], $exception->getContext());
        self::assertSame(['user' => 'u-1'], $exception->setContext(['user' => 'u-1'])->getContext());
    }

    private function exception(): ContextAwareException
    {
        return new ContextAwareException('failed');
    }
}
