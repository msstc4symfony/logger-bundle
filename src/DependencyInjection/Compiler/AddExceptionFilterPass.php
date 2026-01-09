<?php

declare(strict_types=1);

namespace MaxShamaev\LoggerBundle\DependencyInjection\Compiler;

use MaxShamaev\LoggerBundle\Monolog\Handler\ExceptionFilterDecorator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AddExceptionFilterPass implements CompilerPassInterface
{
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

            $container->register($id . '.decorator.exception_filter', ExceptionFilterDecorator::class)
                ->setDecoratedService($id)
                ->setAutowired(true)
                ->setPublic(true)
            ;
        }
    }
}
