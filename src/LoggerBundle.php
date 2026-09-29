<?php

declare(strict_types=1);

namespace Msstc4Symfony\LoggerBundle;

use Msstc4Symfony\LoggerBundle\DependencyInjection\Compiler\AddExceptionFilterPass;
use Msstc4Symfony\LoggerBundle\DependencyInjection\LoggerExtension;
use Override;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class LoggerBundle extends Bundle
{
    #[Override]
    public function getContainerExtension(): ExtensionInterface
    {
        return new LoggerExtension();
    }

    #[Override]
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new AddExceptionFilterPass());
    }
}
