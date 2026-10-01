<?php

declare(strict_types=1);

namespace Msstc4Symfony\LoggerBundle\Test\Integration\Kernel;

use Msstc4Symfony\LoggerBundle\LoggerBundle;
use Override;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;

final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    // Per process: parallel PHPUnit runs must not wipe each other's container.
    public static function cacheRoot(): string
    {
        return sys_get_temp_dir() . '/msstc4symfony-logger-bundle-test-' . getmypid();
    }

    #[Override]
    public function registerBundles(): iterable
    {
        return [new FrameworkBundle(), new MonologBundle(), new LoggerBundle()];
    }

    #[Override]
    public function getCacheDir(): string
    {
        return self::cacheRoot() . '/cache/' . $this->environment;
    }

    #[Override]
    public function getLogDir(): string
    {
        return self::cacheRoot() . '/log';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'test',
            'http_method_override' => false,
            // Symfony 6.4 deprecates leaving it unset.
            'handle_all_throwables' => true,
            'test' => true,
            // The php_errors logger installs a global handler that outlives the kernel and trips failOnRisky.
            'php_errors' => ['log' => false],
        ]);
        $container->extension('monolog', ['handlers' => ['main' => ['type' => 'test']]]);

        // Unused services are removed on compile; the tests fetch these.
        $container->services()->alias('test.main_handler', 'monolog.handler.main')->public();
        $container->services()->alias('test.logger', 'logger')->public();
    }
}
