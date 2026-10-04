<?php

declare(strict_types=1);

namespace Msstc4Symfony\LoggerBundle\Test\Integration;

use LogicException;
use Msstc4Symfony\LoggerBundle\LoggerBundle;
use Msstc4Symfony\LoggerBundle\Sentry\Integration\LoggerIntegration;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[CoversNothing]
final class LoggerBundleConfigTest extends TestCase
{
    private const array DEFAULT_EXCEPTION_CLASSES = [
        NotFoundHttpException::class,
        BadRequestHttpException::class,
        AccessDeniedHttpException::class,
        UnsupportedMediaTypeHttpException::class,
        AccessDeniedException::class,
    ];

    public function testAliasIsMsstc4symfonyLogger(): void
    {
        self::assertSame('msstc4symfony_logger', new LoggerBundle()->getContainerExtension()?->getAlias());
    }

    public function testDefaultsApplyWithoutAnyConfiguration(): void
    {
        $container = $this->load([]);

        self::assertSame(self::DEFAULT_EXCEPTION_CLASSES, $container->getParameter('msstc4symfony_logger.exception_classes'));
        self::assertSame('%env(default:msstc4symfony_logger.unknown:APPLICATION_NAME)%', $container->getParameter('msstc4symfony_logger.application_name'));
        self::assertSame('%env(default:msstc4symfony_logger.unknown:COMPONENT_NAME)%', $container->getParameter('msstc4symfony_logger.component_name'));
        self::assertSame('unknown', $container->getParameter('msstc4symfony_logger.unknown'));
    }

    public function testConfiguredValuesReplaceDefaults(): void
    {
        $container = $this->load([
            'application_name' => 'shop',
            'component_name' => 'worker',
            'exception_classes' => [NotFoundHttpException::class],
        ]);

        self::assertSame('shop', $container->getParameter('msstc4symfony_logger.application_name'));
        self::assertSame('worker', $container->getParameter('msstc4symfony_logger.component_name'));
        self::assertSame([NotFoundHttpException::class], $container->getParameter('msstc4symfony_logger.exception_classes'));
    }

    /**
     * @return iterable<string, array{'application_name'|'component_name', int|bool|string, class-string<InvalidConfigurationException>}>
     */
    public static function invalidNameProvider(): iterable
    {
        foreach (['application_name', 'component_name'] as $option) {
            yield $option . ' int' => [$option, 42, InvalidTypeException::class];
            yield $option . ' bool' => [$option, true, InvalidTypeException::class];
            yield $option . ' empty' => [$option, '', InvalidConfigurationException::class];
        }
    }

    /**
     * @param 'application_name'|'component_name' $option
     * @param class-string<InvalidConfigurationException> $exception
     */
    #[DataProvider('invalidNameProvider')]
    public function testNameRejectsNonStringOrEmptyValue(string $option, int|bool|string $value, string $exception): void
    {
        $this->expectException($exception);
        $this->expectExceptionMessage('msstc4symfony_logger.' . $option);

        $this->load([$option => $value]);
    }

    public function testUnknownExceptionClassIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(addslashes('App\Missing\Boom'));

        $this->load(['exception_classes' => ['App\Missing\Boom']]);
    }

    public function testNonThrowableClassIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(addslashes(stdClass::class));

        $this->load(['exception_classes' => [stdClass::class]]);
    }

    public function testSentryIntegrationIsRegistered(): void
    {
        self::assertTrue($this->load([])->hasDefinition(LoggerIntegration::class));
    }

    public function testLoadExtensionRejectsUnprocessedConfig(): void
    {
        $builder = new ContainerBuilder();
        $instanceof = [];
        $configurator = new ContainerConfigurator($builder, new PhpFileLoader($builder, new FileLocator()), $instanceof, '', '');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('application_name');

        new LoggerBundle()->loadExtension(['component_name' => 'x', 'exception_classes' => []], $configurator, $builder);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function load(array $config): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.environment', 'test');
        $container->setParameter('kernel.build_dir', sys_get_temp_dir());

        $extension = new LoggerBundle()->getContainerExtension();
        self::assertNotNull($extension);
        $extension->load([$config], $container);

        return $container;
    }
}
