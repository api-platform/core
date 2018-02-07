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

use ApiPlatform\Core\Bridge\Symfony\Bundle\ApiPlatformBundle;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\User;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\TestBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use FOS\UserBundle\FOSUserBundle;
use Nelmio\ApiDocBundle\NelmioApiDocBundle;
use Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Firewall\ContextListener;

/**
 * AppKernel for tests.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AppKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): array
    {
        $bundles = [
            new FrameworkBundle(),
            new TwigBundle(),
            new DoctrineBundle(),
            new SensioFrameworkExtraBundle(),
            new ApiPlatformBundle(),
            new SecurityBundle(),
            new FOSUserBundle(),
            new TestBundle(),
        ];

        if ($_SERVER['LEGACY'] ?? true) {
            $bundles[] = new NelmioApiDocBundle();
        }

        return $bundles;
    }

    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
        $routes->import('config/routing.yml');

        if ($_SERVER['LEGACY'] ?? true) {
            $routes->import('@NelmioApiDocBundle/Resources/config/routing.yml', '/nelmioapidoc');
        }
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader)
    {
        $environment = $this->getEnvironment();
        $c->setParameter('kernel.project_dir', __DIR__);

        // patch for behat not supporting %env(APP_ENV)% in older versions
        if (($appEnv = $_SERVER['APP_ENV'] ?? 'test') && $appEnv !== $environment) {
            $environment = $appEnv;
        }

        $loader->load("{$this->getRootDir()}/config/config_{$environment}.yml");

        $securityConfig = [
            'encoders' => [
                User::class => 'bcrypt',
                // Don't use plaintext in production!
                UserInterface::class => 'plaintext',
            ],
            'providers' => [
                'chain_provider' => [
                    'chain' => [
                        'providers' => ['in_memory', 'fos_userbundle'],
                    ],
                ],
                'in_memory' => [
                    'memory' => [
                        'users' => [
                            'dunglas' => ['password' => 'kevin', 'roles' => 'ROLE_USER'],
                            'admin' => ['password' => 'kitten', 'roles' => 'ROLE_ADMIN'],
                        ],
                    ],
                ],
                'fos_userbundle' => ['id' => 'fos_user.user_provider.username_email'],
            ],
            'firewalls' => [
                'dev' => [
                    'pattern' => '^/(_(profiler|wdt|error)|css|images|js)/',
                    'security' => false,
                ],
                'default' => [
                    'provider' => 'chain_provider',
                    'http_basic' => null,
                    'anonymous' => null,
                ],
            ],
            'access_control' => [
                ['path' => '^/', 'role' => 'IS_AUTHENTICATED_ANONYMOUSLY'],
            ],
        ];

        if (method_exists(ContextListener::class, 'setLogoutOnUserChange')) {
            $securityConfig['firewalls']['default']['logout_on_user_change'] = true;
        }
        $c->loadFromExtension('security', $securityConfig);

        if ($_SERVER['LEGACY'] ?? true) {
            $c->loadFromExtension('nelmio_api_doc', [
                'sandbox' => [
                    'accept_type' => 'application/json',
                    'body_format' => [
                        'formats' => ['json'],
                        'default_format' => 'json',
                    ],
                    'request_format' => [
                        'formats' => ['json' => 'application/json'],
                    ],
                ],
            ]);
            $c->loadFromExtension('api_platform', ['enable_nelmio_api_doc' => true]);
        }
    }
}
