<?php

declare(strict_types=1);

namespace Msstc4Symfony\LoggerBundle;

use LogicException;
use Msstc4Symfony\LoggerBundle\DependencyInjection\Compiler\AddExceptionFilterPass;
use Override;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

final class LoggerBundle extends AbstractBundle
{
    private const array DEFAULT_EXCEPTION_CLASSES = [
        NotFoundHttpException::class,
        BadRequestHttpException::class,
        AccessDeniedHttpException::class,
        UnsupportedMediaTypeHttpException::class,
        AccessDeniedException::class,
    ];

    protected string $extensionAlias = 'msstc4symfony_logger';

    #[Override]
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new AddExceptionFilterPass());
    }

    #[Override]
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->stringNode('application_name')
                    ->info('Value of the "application" field in JSON logs and Sentry tags.')
                    ->cannotBeEmpty()
                    ->defaultValue('%env(default:msstc4symfony_logger.unknown:APPLICATION_NAME)%')
                ->end()
                ->stringNode('component_name')
                    ->info('Value of the "component" field in JSON logs and Sentry tags.')
                    ->cannotBeEmpty()
                    ->defaultValue('%env(default:msstc4symfony_logger.unknown:COMPONENT_NAME)%')
                ->end()
                ->arrayNode('exception_classes')
                    ->info('Logged exceptions of these classes (and subclasses) are dropped, except at debug level.')
                    ->defaultValue(self::DEFAULT_EXCEPTION_CLASSES)
                    ->scalarPrototype()
                        ->cannotBeEmpty()
                        ->validate()
                            ->ifTrue(static fn (mixed $class): bool => !is_string($class) || !is_a($class, Throwable::class, true))
                            ->thenInvalid('%s is not an existing class implementing Throwable.')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    /**
     * @param array<array-key, mixed> $config processed by configure()
     */
    #[Override]
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        foreach (['application_name', 'component_name', 'exception_classes'] as $option) {
            $value = $config[$option] ?? null;
            if (!is_string($value) && !is_array($value)) {
                throw new LogicException(sprintf('Option "%s" must be processed by configure() before loading.', $option));
            }

            $builder->setParameter('msstc4symfony_logger.' . $option, $value);
        }

        $container->import(__DIR__ . '/Resources/config/services.php');
    }
}
