<?php

declare(strict_types=1);

namespace MaxShamaev\LoggerBundle\DependencyInjection\Compiler;

use MaxShamaev\LoggerBundle\Monolog\Handler\ExceptionFilterDecorator;
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

            $decorator = $container->register($id . '.decorator.exception_filter', ExceptionFilterDecorator::class)
                ->setDecoratedService($id)
                ->setAutowired(true)
            ;

            if ($definition->isPublic()) {
                $decorator->setPublic(true);
            }
        }
    }
}
