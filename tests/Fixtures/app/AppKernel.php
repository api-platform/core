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

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Symfony\Bundle\ApiPlatformBundle;
use ApiPlatform\Tests\Behat\DoctrineContext;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\User as UserDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\User;
use ApiPlatform\Tests\Fixtures\TestBundle\TestBundle;
use Doctrine\Bundle\DoctrineBundle\ConnectionFactory;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MongoDBBundle\Command\TailCursorDoctrineODMCommand;
use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use FriendsOfBehat\SymfonyExtension\Bundle\FriendsOfBehatSymfonyExtensionBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MakerBundle\MakerBundle;
use Symfony\Bundle\MercureBundle\MercureBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\ErrorHandler\ErrorRenderer\ErrorRendererInterface;
use Symfony\Component\HttpClient\Messenger\PingWebhookMessageHandler;
use Symfony\Component\HttpFoundation\Session\SessionFactory;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;
use Symfony\Component\Security\Core\Authorization\Strategy\AccessDecisionStrategyInterface;
use Symfony\Component\Security\Core\User\User as SymfonyCoreUser;
use Symfony\Component\Security\Core\User\UserInterface;

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
        $bundles = [
            new ApiPlatformBundle(),
            new TwigBundle(),
            new DoctrineBundle(),
            new MercureBundle(),
            new SecurityBundle(),
            new WebProfilerBundle(),
            new FrameworkBundle(),
            new MakerBundle(),
        ];

        if (null === ($_ENV['APP_PHPUNIT'] ?? null)) {
            $bundles[] = new FriendsOfBehatSymfonyExtensionBundle();
        }

        // if (class_exists(DoctrineMongoDBBundle::class)) {
        //     $bundles[] = new DoctrineMongoDBBundle();
        // }

        $bundles[] = new TestBundle();

        return $bundles;
    }

    public function shutdown(): void
    {
        parent::shutdown();
        restore_exception_handler();
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    protected function configureRoutes($routes): void
    {
        $routes->import(__DIR__."/config/routing_{$this->getEnvironment()}.yml");
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        $c->setParameter('kernel.project_dir', __DIR__);

        $loader->load(__DIR__."/config/config_{$this->getEnvironment()}.yml");

        $c->getDefinition(DoctrineContext::class)->setArgument('$passwordHasher', class_exists(NativePasswordHasher::class) ? 'security.user_password_encoder' : 'security.user_password_hasher');

        $messengerConfig = [
            'default_bus' => 'messenger.bus.default',
            'buses' => [
                'messenger.bus.default' => ['default_middleware' => 'allow_no_handlers'],
            ],
        ];

        $cookie = ['cookie_secure' => true, 'cookie_samesite' => 'lax', 'handler_id' => 'session.handler.native_file'];
        // This class is introduced in Symfony 6.4 just using it to use the new configuration and to avoid unnecessary deprecations
        if (class_exists(PingWebhookMessageHandler::class)) {
            $config = [
                'secret' => 'dunglas.fr',
                'validation' => ['enable_attributes' => true, 'email_validation_mode' => 'html5'],
                'serializer' => ['enable_attributes' => true],
                'test' => null,
                'session' => class_exists(SessionFactory::class) ? ['storage_factory_id' => 'session.storage.factory.mock_file'] + $cookie : ['storage_id' => 'session.storage.mock_file'] + $cookie,
                'profiler' => [
                    'enabled' => true,
                    'collect' => false,
                ],
                'php_errors' => ['log' => true],
                'messenger' => $messengerConfig,
                'router' => ['utf8' => true],
                'http_method_override' => false,
                'annotations' => false,
                'handle_all_throwables' => true,
                'uid' => ['default_uuid_version' => 7, 'time_based_uuid_version' => 7],
            ];
        } else {
            $config = [
                'secret' => 'dunglas.fr',
                'validation' => ['enable_annotations' => true],
                'serializer' => ['enable_annotations' => true],
                'test' => null,
                'session' => class_exists(SessionFactory::class) ? ['storage_factory_id' => 'session.storage.factory.mock_file'] : ['storage_id' => 'session.storage.mock_file'],
                'profiler' => [
                    'enabled' => true,
                    'collect' => false,
                ],
                'messenger' => $messengerConfig,
                'router' => ['utf8' => true],
                'http_method_override' => false,
                'annotations' => false,
            ];
        }

        $c->prependExtensionConfig('framework', $config);

        $alg = class_exists(NativePasswordHasher::class, false) || class_exists('Symfony\Component\Security\Core\Encoder\NativePasswordEncoder') ? 'auto' : 'bcrypt';
        $securityConfig = [
            class_exists(NativePasswordHasher::class) ? 'password_hashers' : 'encoders' => [
                User::class => $alg,
                UserDocument::class => $alg,
                // Don't use plaintext in production!
                UserInterface::class => 'plaintext',
            ],
            'providers' => [
                'chain_provider' => [
                    'chain' => [
                        'providers' => ['in_memory', 'entity'],
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
                'entity' => [
                    'entity' => [
                        'class' => User::class,
                        'property' => 'email',
                    ],
                ],
            ],
            'firewalls' => [
                'dev' => [
                    'pattern' => '^/(_(profiler|wdt|error)|css|images|js)/',
                    'security' => false,
                ],
                'default' => [
                    'provider' => 'chain_provider',
                    'stateless' => true,
                    'http_basic' => null,
                    'anonymous' => null,
                    'entry_point' => 'app.security.authentication_entrypoint',
                ],
            ],
            'access_control' => [
                ['path' => '^/', 'role' => interface_exists(AccessDecisionStrategyInterface::class) ? 'PUBLIC_ACCESS' : 'IS_AUTHENTICATED_ANONYMOUSLY'],
            ],
        ];

        if (!class_exists(SymfonyCoreUser::class)) {
            $securityConfig['role_hierarchy'] = [
                'ROLE_ADMIN' => ['ROLE_USER'],
            ];
            unset($securityConfig['firewalls']['default']['anonymous']);
            $securityConfig['firewalls']['default']['http_basic'] = [
                'realm' => 'Secured Area',
            ];
        }

        if (class_exists(NativePasswordHasher::class)) {
            unset($securityConfig['firewalls']['default']['anonymous']);
        }

        $c->prependExtensionConfig('security', $securityConfig);

        if (class_exists(DoctrineMongoDBBundle::class)) {
            $c->prependExtensionConfig('doctrine_mongodb', [
                'connections' => [
                    'default' => null,
                ],
                'document_managers' => [
                    'default' => [
                        'auto_mapping' => true,
                    ],
                ],
            ]);
        }

        $twigConfig = ['strict_variables' => '%kernel.debug%'];
        if (interface_exists(ErrorRendererInterface::class)) {
            $twigConfig['exception_controller'] = null;
        }
        $c->prependExtensionConfig('twig', $twigConfig);

        $useSymfonyListeners = (bool) ($_SERVER['USE_SYMFONY_LISTENERS'] ?? false);

        $c->prependExtensionConfig('api_platform', [
            'mapping' => [
                'paths' => ['%kernel.project_dir%/../TestBundle/Resources/config/api_resources'],
            ],
            'graphql' => [
                'graphql_playground' => false,
                'max_query_depth' => 200,
            ],
            'use_symfony_listeners' => $useSymfonyListeners,
            'defaults' => [
                'pagination_client_enabled' => true,
                'pagination_client_items_per_page' => true,
                'pagination_client_partial' => true,
                'pagination_items_per_page' => 3,
                'cache_headers' => [
                    'max_age' => 60,
                    'shared_max_age' => 3600,
                    'vary' => ['Accept', 'Cookie'],
                    'public' => true,
                ],
                'normalization_context' => ['skip_null_values' => false],
                'operations' => [
                    Get::class,
                    GetCollection::class,
                    Post::class,
                    Put::class,
                    Patch::class,
                    Delete::class,
                ],
            ],
            'serializer' => [
                'hydra_prefix' => true,
            ],
        ]);

        // TODO: remove this check and move this config in config_common.yml when dropping support for DoctrineBundle <2.10
        if (defined(ConnectionFactory::class.'::DEFAULT_SCHEME_MAP')) {
            $c->prependExtensionConfig('doctrine', [
                'orm' => [
                    'report_fields_where_declared' => true,
                    'controller_resolver' => ['auto_mapping' => false],
                    'enable_lazy_ghost_objects' => true,
                ],
            ]);
        }

        $loader->load(__DIR__.'/config/config_swagger.php');

        // We reduce the amount of resources to the strict minimum to speed up tests
        if (null !== ($_ENV['APP_PHPUNIT'] ?? null)) {
            $loader->load(__DIR__.'/config/phpunit.yml');
        }

        if ('mongodb' === $this->environment) {
            $c->prependExtensionConfig('api_platform', [
                'mapping' => [
                    'paths' => ['%kernel.project_dir%/../TestBundle/Resources/config/api_resources_odm'],
                ],
            ]);

            return;
        }

        $c->prependExtensionConfig('api_platform', [
            'mapping' => [
                'paths' => ['%kernel.project_dir%/../TestBundle/Resources/config/api_resources_orm'],
            ],
        ]);
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new class implements CompilerPassInterface {
            public function process(ContainerBuilder $container): void
            {
                if ($container->hasDefinition(TailCursorDoctrineODMCommand::class)) { // @phpstan-ignore-line
                    // Deprecated command triggering a Symfony depreciation
                    $container->removeDefinition(TailCursorDoctrineODMCommand::class); // @phpstan-ignore-line
                }
            }
        });
    }
}
