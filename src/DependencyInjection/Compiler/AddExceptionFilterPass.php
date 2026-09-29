<?php

declare(strict_types=1);

namespace Msstc4Symfony\LoggerBundle\DependencyInjection\Compiler;

use Msstc4Symfony\LoggerBundle\Monolog\Handler\ExceptionFilterDecorator;
use Override;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class AddExceptionFilterPass implements CompilerPassInterface
{
    #[Override]
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->getDefinitions() as $id => $definition) {
            if (
                !str_starts_with($id, 'monolog.logger')
                || $definition->getClass() === ExceptionFilterDecorator::class
                || $definition->isAbstract()
                || $definition->getDecoratedService() !== null
            ) {
                continue;
            }

            // Visibility is propagated from the decorated service by Symfony's
            // DecoratorServicePass (>=5.3), so no setPublic() is needed here.
            $container->register($id . '.decorator.exception_filter', ExceptionFilterDecorator::class)
                ->setDecoratedService($id)
                ->setAutowired(true)
            ;
        }
    }
}
