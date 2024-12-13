<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Doctrine\Odm\Tests;

use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Kernel;

/**
 * AppKernel for tests.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AppKernel extends Kernel
{
    use MicroKernelTrait;

    public function __construct(string $environment, bool $debug)
    {
        parent::__construct($environment, $debug);

        // patch for behat/symfony2-extension not supporting %env(APP_ENV)%
        $this->environment = $_SERVER['APP_ENV'] ?? $environment;
    }

    public function registerBundles(): array
    {
        return [
            new FrameworkBundle(),
            new DoctrineMongoDBBundle(),
            new class extends Bundle {
                public function shutdown(): void
                {
                    restore_exception_handler();
                }
            },
        ];
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    protected function configureRoutes($routes): void
    {
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        $c->setParameter('kernel.project_dir', __DIR__);

        $cookie = ['cookie_secure' => true, 'cookie_samesite' => 'lax', 'handler_id' => 'session.handler.native_file'];
        $config = [
            'secret' => 'dunglas.fr',
            'validation' => ['enable_attributes' => true, 'email_validation_mode' => 'html5'],
            'serializer' => ['enable_attributes' => true],
            'test' => null,
            'session' => ['storage_factory_id' => 'session.storage.factory.mock_file'] + $cookie,
            'profiler' => ['enabled' => false],
            'property_access' => ['enabled' => true],
            'php_errors' => ['log' => true],
            'router' => ['utf8' => true],
            'http_method_override' => false,
            'annotations' => false,
            'handle_all_throwables' => true,
            'uid' => ['default_uuid_version' => 7, 'time_based_uuid_version' => 7],
        ];

        $c->prependExtensionConfig('framework', $config);

        $loader->load(__DIR__.'/config.yml');
    }

    protected function build(ContainerBuilder $container): void
    {
    }
}
