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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Bundle\DependencyInjection;

use ApiPlatform\Core\Api\FilterInterface;
use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\OperationAwareFormatsProviderInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\AggregationCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\AggregationItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\FilterExtension as MongoDbOdmFilterExtension;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\OrderExtension as MongoDbOdmOrderExtension;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\PaginationExtension as MongoDbOdmPaginationExtension;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\AbstractFilter as DoctrineMongoDbOdmAbstractFilter;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\BooleanFilter as MongoDbOdmBooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\DateFilter as MongoDbOdmDateFilter;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\ExistsFilter as MongoDbOdmExistsFilter;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\NumericFilter as MongoDbOdmNumericFilter;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\OrderFilter as MongoDbOdmOrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\RangeFilter as MongoDbOdmRangeFilter;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\SearchFilter as MongoDbOdmSearchFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\EagerLoadingExtension;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\FilterEagerLoadingExtension;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\FilterExtension;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\OrderExtension;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractContextAwareFilter as DoctrineOrmAbstractContextAwareFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\NumericFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Bridge\Elasticsearch\Api\IdentifierExtractorInterface;
use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Extension\RequestBodySearchCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter\MatchFilter;
use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter\OrderFilter as ElasticsearchOrderFilter;
use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter\TermFilter;
use ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Document\Factory\DocumentMetadataFactoryInterface;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\ApiPlatformExtension;
use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Exception\FilterValidationException;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use ApiPlatform\Core\GraphQl\Resolver\QueryCollectionResolverInterface;
use ApiPlatform\Core\GraphQl\Resolver\QueryItemResolverInterface;
use ApiPlatform\Core\GraphQl\Type\Definition\TypeInterface as GraphQlTypeInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Security\ResourceAccessCheckerInterface;
use ApiPlatform\Core\Serializer\Filter\GroupFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\TestBundle;
use ApiPlatform\Core\Validator\ValidatorInterface;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\ORM\OptimisticLockException;
use FOS\UserBundle\FOSUserBundle;
use Nelmio\ApiDocBundle\NelmioApiDocBundle;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Exception\Doubler\MethodNotFoundException;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\Config\Resource\ResourceInterface;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\Extension\ConfigurationExtensionInterface;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\EnvPlaceholderParameterBag;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

/**
 * @group resource-hog
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ApiPlatformExtensionTest extends TestCase
{
    public const DEFAULT_CONFIG = ['api_platform' => [
        'title' => 'title',
        'description' => 'description',
        'version' => 'version',
        'formats' => [
            'jsonld' => ['mime_types' => ['application/ld+json']],
            'jsonhal' => ['mime_types' => ['application/hal+json']],
        ],
        'http_cache' => ['invalidation' => [
            'enabled' => true,
            'varnish_urls' => ['test'],
            'request_options' => [
                'allow_redirects' => [
                    'max' => 5,
                    'protocols' => ['http', 'https'],
                    'stric' => false,
                    'referer' => false,
                    'track_redirects' => false,
                ],
                'http_errors' => true,
                'decode_content' => false,
                'verify' => false,
                'cookies' => true,
                'headers' => [
                    'User-Agent' => 'none',
                ],
            ],
        ]],
        'doctrine_mongodb_odm' => [
            'enabled' => false,
        ],
    ]];

    private $extension;
    private $childDefinitionProphecy;

    protected function setUp(): void
    {
        $this->extension = new ApiPlatformExtension();
        $this->childDefinitionProphecy = $this->prophesize(ChildDefinition::class);
    }

    protected function tearDown(): void
    {
        $this->extension = null;
    }

    public function testConstruct()
    {
        $this->extension = new ApiPlatformExtension();

        $this->assertInstanceOf(PrependExtensionInterface::class, $this->extension);
        $this->assertInstanceOf(ExtensionInterface::class, $this->extension);
        $this->assertInstanceOf(ConfigurationExtensionInterface::class, $this->extension);
    }

    public function testPrependWhenNoFrameworkExtension()
    {
        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getExtensions()->willReturn([]);
        $containerBuilderProphecy->prependExtensionConfig('framework', Argument::type('array'))->shouldNotBeCalled();

        $this->extension->prepend($containerBuilderProphecy->reveal());
    }

    public function testPrepend()
    {
        $frameworkExtensionProphecy = $this->prophesize(ExtensionInterface::class);

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->getExtensions()->willReturn(['framework' => $frameworkExtensionProphecy->reveal()]);
        $containerBuilderProphecy->prependExtensionConfig('framework', [
            'serializer' => [
                'enabled' => true,
            ],
        ])->shouldBeCalled();
        $containerBuilderProphecy->prependExtensionConfig('framework', [
            'property_info' => [
                'enabled' => true,
            ],
        ])->shouldBeCalled();

        $this->extension->prepend($containerBuilderProphecy->reveal());
    }

    public function testLoadDefaultConfig()
    {
        $containerBuilderProphecy = $this->getBaseContainerBuilderProphecy();
        $containerBuilderProphecy->setParameter('api_platform.enable_swagger', '1')->shouldBeCalled();
        $containerBuilderProphecy->hasParameter('kernel.debug')->willReturn(true);
        $containerBuilderProphecy->getParameter('kernel.debug')->willReturn(false);
        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->load(self::DEFAULT_CONFIG, $containerBuilder);
    }

    /**
     * @group mongodb
     */
    public function testLoadDefaultConfigWithOdm()
    {
        $containerBuilderProphecy = $this->getBaseContainerBuilderProphecy(['odm']);
        $containerBuilderProphecy->setParameter('api_platform.enable_swagger', '1')->shouldBeCalled();
        $containerBuilderProphecy->hasParameter('kernel.debug')->willReturn(true);
        $containerBuilderProphecy->getParameter('kernel.debug')->willReturn(false);
        $containerBuilder = $containerBuilderProphecy->reveal();

        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['doctrine_mongodb_odm']['enabled'] = true;

        $this->extension->load($config, $containerBuilder);
    }

    public function testSetNameConverter()
    {
        $nameConverterId = 'test.name_converter';

        $containerBuilderProphecy = $this->getBaseContainerBuilderProphecy();
        $containerBuilderProphecy->hasParameter('kernel.debug')->willReturn(true);
        $containerBuilderProphecy->getParameter('kernel.debug')->willReturn(false);
        $containerBuilderProphecy->setAlias('api_platform.name_converter', $nameConverterId)->shouldBeCalled();
        $containerBuilderProphecy->setParameter('api_platform.enable_swagger', '1')->shouldBeCalled();

        $containerBuilder = $containerBuilderProphecy->reveal();

        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['name_converter'] = $nameConverterId;

        $this->extension->load($config, $containerBuilder);
    }

    public function testEnableFosUser()
    {
        $containerBuilderProphecy = $this->getBaseContainerBuilderProphecy();
        $containerBuilderProphecy->hasParameter('kernel.debug')->willReturn(true);
        $containerBuilderProphecy->getParameter('kernel.debug')->willReturn(false);
        $containerBuilderProphecy->getParameter('kernel.bundles')->willReturn([
            'DoctrineBundle' => DoctrineBundle::class,
            'FOSUserBundle' => FOSUserBundle::class,
        ])->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.fos_user.event_listener', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setParameter('api_platform.enable_swagger', '1')->shouldBeCalled();

        $containerBuilder = $containerBuilderProphecy->reveal();

        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['enable_fos_user'] = true;

        $this->extension->load($config, $containerBuilder);
    }

    public function testDisableProfiler()
    {
        $containerBuilderProphecy = $this->getBaseContainerBuilderProphecy();
        $containerBuilder = $containerBuilderProphecy->reveal();
        $containerBuilderProphecy->setDefinition('api_platform.data_collector.request', Argument::type(Definition::class))->shouldNotBeCalled();

        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['enable_profiler'] = false;

        $this->extension->load($config, $containerBuilder);
    }

    public function testEnableProfilerWithDebug()
    {
        $containerBuilderProphecy = $this->getBaseContainerBuilderProphecy();
        $containerBuilderProphecy->hasParameter('kernel.debug')->willReturn(true);
        $containerBuilderProphecy->getParameter('kernel.debug')->willReturn(true);
        $containerBuilderProphecy->setDefinition('debug.api_platform.collection_data_provider', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('debug.api_platform.item_data_provider', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('debug.api_platform.subresource_data_provider', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('debug.api_platform.data_persister', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['enable_profiler'] = true;

        $this->extension->load($config, $containerBuilder);
    }

    public function testFosUserPriority()
    {
        $builder = new ContainerBuilder();

        $loader = new XmlFileLoader($builder, new FileLocator(\dirname(__DIR__).'/../../../../src/Bridge/Symfony/Bundle/Resources/config'));
        $loader->load('api.xml');
        $loader->load('fos_user.xml');

        $fosListener = $builder->getDefinition('api_platform.fos_user.event_listener');
        $viewListener = $builder->getDefinition('api_platform.listener.view.serialize');

        // Ensure FOSUser event listener priority is always greater than the view serialize listener
        $this->assertGreaterThan(
            $viewListener->getTag('kernel.event_listener')[0]['priority'],
            $fosListener->getTag('kernel.event_listener')[0]['priority'],
            'api_platform.fos_user.event_listener priority needs to be greater than that of api_platform.listener.view.serialize'
        );
    }

    /**
     * @group legacy
     * @expectedDeprecation Enabling the NelmioApiDocBundle integration has been deprecated in 2.2 and will be removed in 3.0. NelmioApiDocBundle 3 has native support for API Platform.
     */
    public function testEnableNelmioApiDoc()
    {
        $containerBuilderProphecy = $this->getBaseContainerBuilderProphecy();
        $containerBuilderProphecy->getParameter('kernel.bundles')->willReturn([
            'DoctrineBundle' => DoctrineBundle::class,
            'NelmioApiDocBundle' => NelmioApiDocBundle::class,
        ])->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.nelmio_api_doc.annotations_provider', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.nelmio_api_doc.parser', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setParameter('api_platform.enable_swagger', '1')->shouldBeCalled();

        $containerBuilder = $containerBuilderProphecy->reveal();

        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['doctrine_mongodb_odm']['enabled'] = false;
        $config['api_platform']['enable_nelmio_api_doc'] = true;

        $this->extension->load($config, $containerBuilder);
    }

    public function testDisableGraphQl()
    {
        $containerBuilderProphecy = $this->getBaseContainerBuilderProphecy();
        $containerBuilderProphecy->setDefinition('api_platform.graphql.action.entrypoint', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.graphql.resolver.factory.collection', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.graphql.resolver.factory.item_mutation', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.graphql.resolver.factory.item', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.graphql.resolver.resource_field', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.graphql.executor', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.graphql.type_builder', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.graphql.fields_builder', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.graphql.fields_builder_locator', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.graphql.schema_builder', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.graphql.normalizer.item', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.graphql.normalizer.object', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.graphql.iterable_type', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.graphql.type_locator', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.graphql.types_container', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.graphql.types_factory', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.graphql.type_converter', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.graphql.query_resolver_locator', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.graphql.mutation_resolver_locator', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.graphql.command.export_command', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setParameter('api_platform.graphql.enabled', true)->shouldNotBeCalled();
        $containerBuilderProphecy->setParameter('api_platform.graphql.enabled', false)->shouldBeCalled();
        $containerBuilderProphecy->setParameter('api_platform.graphql.graphiql.enabled', true)->shouldNotBeCalled();
        $containerBuilderProphecy->setParameter('api_platform.graphql.graphiql.enabled', false)->shouldNotBeCalled();
        $containerBuilderProphecy->registerForAutoconfiguration(GraphQlTypeInterface::class)->shouldNotBeCalled();
        $this->childDefinitionProphecy->addTag('api_platform.graphql.type')->shouldNotBeCalled();
        $containerBuilderProphecy->registerForAutoconfiguration(QueryItemResolverInterface::class)->shouldNotBeCalled();
        $containerBuilderProphecy->registerForAutoconfiguration(QueryCollectionResolverInterface::class)->shouldNotBeCalled();
        $this->childDefinitionProphecy->addTag('api_platform.graphql.query_resolver')->shouldNotBeCalled();
        $containerBuilderProphecy->registerForAutoconfiguration(MutationResolverInterface::class)->shouldNotBeCalled();
        $this->childDefinitionProphecy->addTag('api_platform.graphql.mutation_resolver')->shouldNotBeCalled();

        $containerBuilder = $containerBuilderProphecy->reveal();

        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['graphql']['enabled'] = false;

        $this->extension->load($config, $containerBuilder);
    }

    public function testEnableSecurity()
    {
        $containerBuilderProphecy = $this->getBaseContainerBuilderProphecy();
        $containerBuilderProphecy->getParameter('kernel.bundles')->willReturn([
            'DoctrineBundle' => DoctrineBundle::class,
            'SecurityBundle' => SecurityBundle::class,
        ])->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.security.resource_access_checker', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(ResourceAccessCheckerInterface::class, 'api_platform.security.resource_access_checker')->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.security.listener.request.deny_access', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.security.expression_language_provider', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias('api_platform.security.expression_language', Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->load(self::DEFAULT_CONFIG, $containerBuilder);
    }

    public function testAddResourceClassDirectories()
    {
        $containerBuilderProphecy = $this->getBaseContainerBuilderProphecy();
        $containerBuilderProphecy->getParameter('api_platform.resource_class_directories')->shouldBeCalled()->willReturn([]);
        $i = 0;
        // it's called once from getResourcesToWatch and then if the configuration exists
        $containerBuilderProphecy->setParameter('api_platform.resource_class_directories', Argument::that(function ($arg) use (&$i) {
            if (0 === $i++) {
                return $arg;
            }

            if (!\in_array('foobar', $arg, true)) {
                throw new \Exception('"foobar" should be in "resource_class_directories"');
            }

            return $arg;
        }))->shouldBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['resource_class_directories'] = ['foobar'];

        $this->extension->load($config, $containerBuilder);
    }

    public function testResourcesToWatchWithUnsupportedMappingType()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageRegExp('/Unsupported mapping type in ".+", supported types are XML & YAML\\./');

        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['mapping']['paths'] = [__FILE__];

        $this->extension->load(
            $config,
            $this->getPartialContainerBuilderProphecy()->reveal()
        );
    }

    public function testResourcesToWatchWithNonExistentFile()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not open file or directory "fake_file.xml".');

        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['mapping']['paths'] = ['fake_file.xml'];

        $this->extension->load(
            $config,
            $this->getPartialContainerBuilderProphecy()->reveal()
        );
    }

    public function testDisableEagerLoadingExtension()
    {
        $containerBuilderProphecy = $this->getBaseContainerBuilderProphecy();
        $containerBuilderProphecy->setParameter('api_platform.eager_loading.enabled', false)->shouldBeCalled();
        $containerBuilderProphecy->removeAlias(EagerLoadingExtension::class)->shouldBeCalled();
        $containerBuilderProphecy->removeAlias(FilterEagerLoadingExtension::class)->shouldBeCalled();
        $containerBuilderProphecy->removeDefinition('api_platform.doctrine.orm.query_extension.eager_loading')->shouldBeCalled();
        $containerBuilderProphecy->removeDefinition('api_platform.doctrine.orm.query_extension.filter_eager_loading')->shouldBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['eager_loading']['enabled'] = false;

        $this->extension->load($config, $containerBuilder);
    }

    public function testNotRegisterHttpCacheWhenEnabledWithNoVarnishServer()
    {
        $containerBuilderProphecy = $this->getBaseContainerBuilderProphecy();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['http_cache']['invalidation']['varnish_urls'] = [];

        $this->extension->load($config, $containerBuilder);
    }

    public function testRegisterHttpCacheWhenEnabledWithNoRequestOption()
    {
        $containerBuilderProphecy = $this->getBaseContainerBuilderProphecy();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $config = self::DEFAULT_CONFIG;
        unset($config['api_platform']['http_cache']['invalidation']['request_options']);

        $this->extension->load($config, $containerBuilder);
    }

    public function testDisabledDocsRemovesAddLinkHeaderService()
    {
        $containerBuilderProphecy = $this->getBaseContainerBuilderProphecy();
        $containerBuilderProphecy->removeDefinition('api_platform.hydra.listener.response.add_link_header')->shouldBeCalled();
        $containerBuilderProphecy->setParameter('api_platform.enable_docs', false)->shouldBeCalled();
        $containerBuilderProphecy->setParameter('api_platform.enable_docs', true)->shouldNotBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['enable_docs'] = false;

        $this->extension->load($config, $containerBuilder);
    }

    public function testDisabledSwaggerUIAndRedoc()
    {
        $containerBuilderProphecy = $this->getBaseContainerBuilderProphecy();
        $containerBuilderProphecy->setDefinition('api_platform.swagger.action.ui', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.swagger.listener.ui', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setParameter('api_platform.enable_swagger_ui', true)->shouldNotBeCalled();
        $containerBuilderProphecy->setParameter('api_platform.enable_swagger_ui', true)->shouldNotBeCalled();
        $containerBuilderProphecy->setParameter('api_platform.enable_swagger_ui', false)->shouldNotBeCalled();
        $containerBuilderProphecy->setParameter('api_platform.enable_re_doc', true)->shouldNotBeCalled();
        $containerBuilderProphecy->setParameter('api_platform.enable_re_doc', false)->shouldNotBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['enable_swagger_ui'] = false;
        $config['api_platform']['enable_re_doc'] = false;

        $this->extension->load($config, $containerBuilder);
    }

    public function testDisabledMessenger()
    {
        $containerBuilderProphecy = $this->getBaseContainerBuilderProphecy();
        $containerBuilderProphecy->setAlias('api_platform.message_bus', 'messenger.default_bus')->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.messenger.data_persister', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.messenger.data_transformer', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['messenger']['enabled'] = false;

        $this->extension->load($config, $containerBuilder);
    }

    public function testDisableDoctrine()
    {
        $this->runDisableDoctrineTests();
    }

    /**
     * @group mongodb
     */
    public function testDisableDoctrineWithMongoDbOdm()
    {
        $this->runDisableDoctrineTests();
    }

    private function runDisableDoctrineTests()
    {
        $containerBuilderProphecy = $this->getBaseContainerBuilderProphecy([]);
        $containerBuilderProphecy->registerForAutoconfiguration(QueryItemExtensionInterface::class)->shouldNotBeCalled();
        $this->childDefinitionProphecy->addTag('api_platform.doctrine.orm.query_extension.item')->shouldNotBeCalled();
        $containerBuilderProphecy->registerForAutoconfiguration(QueryCollectionExtensionInterface::class)->shouldNotBeCalled();
        $this->childDefinitionProphecy->addTag('api_platform.doctrine.orm.query_extension.collection')->shouldNotBeCalled();
        $containerBuilderProphecy->registerForAutoconfiguration(DoctrineOrmAbstractContextAwareFilter::class)->shouldNotBeCalled();
        $this->childDefinitionProphecy->setBindings(['$requestStack' => null])->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine.listener.http_cache.purge', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine.orm.boolean_filter', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine.orm.collection_data_provider', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine.orm.data_persister', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine.orm.date_filter', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine.orm.default.collection_data_provider', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine.orm.default.item_data_provider', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine.orm.default.subresource_data_provider', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine.orm.exists_filter', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine.orm.item_data_provider', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine.orm.metadata.property.metadata_factory', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine.orm.numeric_filter', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine.orm.order_filter', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine.orm.query_extension.eager_loading', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine.orm.query_extension.filter', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine.orm.query_extension.filter_eager_loading', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine.orm.query_extension.order', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine.orm.query_extension.pagination', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine.orm.range_filter', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine.orm.search_filter', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine.orm.subresource_data_provider', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine.listener.mercure.publish', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setAlias(EagerLoadingExtension::class, 'api_platform.doctrine.orm.query_extension.eager_loading')->shouldNotBeCalled();
        $containerBuilderProphecy->setAlias(FilterExtension::class, 'api_platform.doctrine.orm.query_extension.filter')->shouldNotBeCalled();
        $containerBuilderProphecy->setAlias(FilterEagerLoadingExtension::class, 'api_platform.doctrine.orm.query_extension.filter_eager_loading')->shouldNotBeCalled();
        $containerBuilderProphecy->setAlias(PaginationExtension::class, 'api_platform.doctrine.orm.query_extension.pagination')->shouldNotBeCalled();
        $containerBuilderProphecy->setAlias(OrderExtension::class, 'api_platform.doctrine.orm.query_extension.order')->shouldNotBeCalled();
        $containerBuilderProphecy->setAlias(SearchFilter::class, 'api_platform.doctrine.orm.search_filter')->shouldNotBeCalled();
        $containerBuilderProphecy->setAlias(OrderFilter::class, 'api_platform.doctrine.orm.order_filter')->shouldNotBeCalled();
        $containerBuilderProphecy->setAlias(RangeFilter::class, 'api_platform.doctrine.orm.range_filter')->shouldNotBeCalled();
        $containerBuilderProphecy->setAlias(DateFilter::class, 'api_platform.doctrine.orm.date_filter')->shouldNotBeCalled();
        $containerBuilderProphecy->setAlias(BooleanFilter::class, 'api_platform.doctrine.orm.boolean_filter')->shouldNotBeCalled();
        $containerBuilderProphecy->setAlias(NumericFilter::class, 'api_platform.doctrine.orm.numeric_filter')->shouldNotBeCalled();
        $containerBuilderProphecy->setAlias(ExistsFilter::class, 'api_platform.doctrine.orm.exists_filter')->shouldNotBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['doctrine']['enabled'] = false;

        $this->extension->load($config, $containerBuilder);
    }

    /**
     * @group mongodb
     */
    public function testDisableDoctrineMongoDbOdm()
    {
        $containerBuilderProphecy = $this->getBaseContainerBuilderProphecy();
        $containerBuilderProphecy->registerForAutoconfiguration(AggregationItemExtensionInterface::class)->shouldNotBeCalled();
        $this->childDefinitionProphecy->addTag('api_platform.doctrine.mongodb.aggregation_extension.item')->shouldNotBeCalled();
        $containerBuilderProphecy->registerForAutoconfiguration(AggregationCollectionExtensionInterface::class)->shouldNotBeCalled();
        $this->childDefinitionProphecy->addTag('api_platform.doctrine.mongodb.aggregation_extension.collection')->shouldNotBeCalled();
        $containerBuilderProphecy->registerForAutoconfiguration(DoctrineMongoDbOdmAbstractFilter::class)->shouldNotBeCalled();
        $this->childDefinitionProphecy->setBindings(Argument::allOf(Argument::withEntry('$managerRegistry', Argument::type(Reference::class))))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine_mongodb.odm.aggregation_extension.filter', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine_mongodb.odm.aggregation_extension.order', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine_mongodb.odm.aggregation_extension.pagination', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine_mongodb.odm.boolean_filter', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine_mongodb.odm.collection_data_provider', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine_mongodb.odm.data_persister', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine_mongodb.odm.date_filter', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine_mongodb.odm.default.collection_data_provider', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine_mongodb.odm.default.item_data_provider', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine_mongodb.odm.default.subresource_data_provider', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine_mongodb.odm.default_document_manager.property_info_extractor', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine_mongodb.odm.exists_filter', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine_mongodb.odm.item_data_provider', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine_mongodb.odm.metadata.property.metadata_factory', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine_mongodb.odm.numeric_filter', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine_mongodb.odm.order_filter', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine_mongodb.odm.range_filter', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine_mongodb.odm.search_filter', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.doctrine_mongodb.odm.subresource_data_provider', Argument::type(Definition::class))->shouldNotBeCalled();
        $containerBuilderProphecy->setAlias(MongoDbOdmFilterExtension::class, 'api_platform.doctrine_mongodb.odm.aggregation_extension.filter')->shouldNotBeCalled();
        $containerBuilderProphecy->setAlias(MongoDbOdmOrderExtension::class, 'api_platform.doctrine_mongodb.odm.aggregation_extension.order')->shouldNotBeCalled();
        $containerBuilderProphecy->setAlias(MongoDbOdmPaginationExtension::class, 'api_platform.doctrine_mongodb.odm.aggregation_extension.pagination')->shouldNotBeCalled();
        $containerBuilderProphecy->setAlias(MongoDbOdmSearchFilter::class, 'api_platform.doctrine_mongodb.odm.search_filter')->shouldNotBeCalled();
        $containerBuilderProphecy->setAlias(MongoDbOdmBooleanFilter::class, 'api_platform.doctrine_mongodb.odm.boolean_filter')->shouldNotBeCalled();
        $containerBuilderProphecy->setAlias(MongoDbOdmDateFilter::class, 'api_platform.doctrine_mongodb.odm.date_filter')->shouldNotBeCalled();
        $containerBuilderProphecy->setAlias(MongoDbOdmExistsFilter::class, 'api_platform.doctrine_mongodb.odm.exists_filter')->shouldNotBeCalled();
        $containerBuilderProphecy->setAlias(MongoDbOdmNumericFilter::class, 'api_platform.doctrine_mongodb.odm.numeric_filter')->shouldNotBeCalled();
        $containerBuilderProphecy->setAlias(MongoDbOdmOrderFilter::class, 'api_platform.doctrine_mongodb.odm.order_filter')->shouldNotBeCalled();
        $containerBuilderProphecy->setAlias(MongoDbOdmRangeFilter::class, 'api_platform.doctrine_mongodb.odm.range_filter')->shouldNotBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->load(self::DEFAULT_CONFIG, $containerBuilder);
    }

    public function testEnableElasticsearch()
    {
        $this->childDefinitionProphecy->addTag('api_platform.elasticsearch.request_body_search_extension.collection')->shouldBeCalled();

        $containerBuilderProphecy = $this->getBaseContainerBuilderProphecy();
        $containerBuilderProphecy->setParameter('api_platform.elasticsearch.enabled', false)->shouldNotBeCalled();
        $containerBuilderProphecy->setParameter('api_platform.elasticsearch.enabled', true)->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.elasticsearch.client', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.elasticsearch.metadata.resource.metadata_factory.operation', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.elasticsearch.cache.metadata.document', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.elasticsearch.metadata.document.metadata_factory.configured', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.elasticsearch.metadata.document.metadata_factory.attribute', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.elasticsearch.metadata.document.metadata_factory.cat', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.elasticsearch.metadata.document.metadata_factory.cached', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.elasticsearch.identifier_extractor', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.elasticsearch.name_converter.inner_fields', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.elasticsearch.normalizer.item', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.elasticsearch.item_data_provider', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.elasticsearch.collection_data_provider', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.elasticsearch.request_body_search_extension.filter', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.elasticsearch.request_body_search_extension.constant_score_filter', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.elasticsearch.request_body_search_extension.sort_filter', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.elasticsearch.request_body_search_extension.sort', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.elasticsearch.search_filter', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.elasticsearch.term_filter', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.elasticsearch.order_filter', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('api_platform.elasticsearch.match_filter', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias('api_platform.elasticsearch.metadata.document.metadata_factory', 'api_platform.elasticsearch.metadata.document.metadata_factory.configured')->shouldBeCalled();
        $containerBuilderProphecy->setAlias(DocumentMetadataFactoryInterface::class, 'api_platform.elasticsearch.metadata.document.metadata_factory')->shouldBeCalled();
        $containerBuilderProphecy->setAlias(IdentifierExtractorInterface::class, 'api_platform.elasticsearch.identifier_extractor')->shouldBeCalled();
        $containerBuilderProphecy->setAlias(TermFilter::class, 'api_platform.elasticsearch.term_filter')->shouldBeCalled();
        $containerBuilderProphecy->setAlias(ElasticsearchOrderFilter::class, 'api_platform.elasticsearch.order_filter')->shouldBeCalled();
        $containerBuilderProphecy->setAlias(MatchFilter::class, 'api_platform.elasticsearch.match_filter')->shouldBeCalled();
        $containerBuilderProphecy->registerForAutoconfiguration(RequestBodySearchCollectionExtensionInterface::class)->willReturn($this->childDefinitionProphecy)->shouldBeCalled();
        $containerBuilderProphecy->setParameter('api_platform.elasticsearch.hosts', ['http://elasticsearch:9200'])->shouldBeCalled();
        $containerBuilderProphecy->setParameter('api_platform.elasticsearch.mapping', [])->shouldBeCalled();

        $config = self::DEFAULT_CONFIG;
        $config['api_platform']['elasticsearch'] = [
            'enabled' => true,
            'hosts' => ['http://elasticsearch:9200'],
            'mapping' => [],
        ];

        $this->extension->load($config, $containerBuilderProphecy->reveal());
    }

    /**
     * @group legacy
     * @expectedDeprecation The "api_platform.metadata_cache" parameter is deprecated since version 2.4 and will have no effect in 3.0.
     */
    public function testDisableMetadataCache()
    {
        $containerBuilderProphecy = $this->getBaseContainerBuilderProphecy();
        $containerBuilderProphecy->hasParameter('api_platform.metadata_cache')->willReturn(true);
        $containerBuilderProphecy->getParameter('api_platform.metadata_cache')->willReturn(false);
        $containerBuilderProphecy->removeDefinition('api_platform.cache_warmer.cache_pool_clearer')->shouldBeCalled();
        $containerBuilderProphecy->register('api_platform.cache.metadata.property', ArrayAdapter::class)->shouldBeCalled();
        $containerBuilderProphecy->register('api_platform.cache.metadata.resource', ArrayAdapter::class)->shouldBeCalled();
        $containerBuilderProphecy->register('api_platform.cache.route_name_resolver', ArrayAdapter::class)->shouldBeCalled();
        $containerBuilderProphecy->register('api_platform.cache.identifiers_extractor', ArrayAdapter::class)->shouldBeCalled();
        $containerBuilderProphecy->register('api_platform.cache.subresource_operation_factory', ArrayAdapter::class)->shouldBeCalled();
        $containerBuilderProphecy->register('api_platform.elasticsearch.cache.metadata.document', ArrayAdapter::class)->shouldBeCalled();

        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->load(self::DEFAULT_CONFIG, $containerBuilder);
    }

    public function testRemoveCachePoolClearerCacheWarmerWithoutDebug()
    {
        $containerBuilderProphecy = $this->getBaseContainerBuilderProphecy();
        $containerBuilderProphecy->hasParameter('kernel.debug')->willReturn(true);
        $containerBuilderProphecy->getParameter('kernel.debug')->willReturn(false);
        $containerBuilderProphecy->removeDefinition('api_platform.cache_warmer.cache_pool_clearer')->shouldBeCalled();

        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->load(self::DEFAULT_CONFIG, $containerBuilder);
    }

    public function testKeepCachePoolClearerCacheWarmerWithDebug()
    {
        $containerBuilderProphecy = $this->getBaseContainerBuilderProphecy();
        $containerBuilderProphecy->hasParameter('kernel.debug')->willReturn(true);
        $containerBuilderProphecy->getParameter('kernel.debug')->willReturn(true);
        $containerBuilderProphecy->removeDefinition('api_platform.cache_warmer.cache_pool_clearer')->shouldNotBeCalled();

        // irrelevant, but to prevent errors
        $containerBuilderProphecy->setDefinition('debug.api_platform.collection_data_provider', Argument::type(Definition::class))->will(function () {});
        $containerBuilderProphecy->setDefinition('debug.api_platform.item_data_provider', Argument::type(Definition::class))->will(function () {});
        $containerBuilderProphecy->setDefinition('debug.api_platform.subresource_data_provider', Argument::type(Definition::class))->will(function () {});
        $containerBuilderProphecy->setDefinition('debug.api_platform.data_persister', Argument::type(Definition::class))->will(function () {});

        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->load(self::DEFAULT_CONFIG, $containerBuilder);
    }

    private function getPartialContainerBuilderProphecy()
    {
        $parameterBag = new EnvPlaceholderParameterBag();

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);

        $containerBuilderProphecy->getParameterBag()->willReturn($parameterBag);

        $containerBuilderProphecy->getParameter('kernel.bundles_metadata')->willReturn([
            'TestBundle' => [
                'parent' => null,
                'path' => realpath(__DIR__.'/../../../../Fixtures/TestBundle'),
                'namespace' => TestBundle::class,
            ],
        ])->shouldBeCalled();

        $containerBuilderProphecy->fileExists(Argument::type('string'), false)->will(function ($args) {
            return file_exists($args[0]);
        })->shouldBeCalled();

        $parameters = [
            'api_platform.collection.exists_parameter_name' => 'exists',
            'api_platform.collection.order' => 'ASC',
            'api_platform.collection.order_parameter_name' => 'order',
            'api_platform.description' => 'description',
            'api_platform.error_formats' => ['jsonproblem' => ['application/problem+json'], 'jsonld' => ['application/ld+json']],
            'api_platform.formats' => ['jsonld' => ['application/ld+json'], 'jsonhal' => ['application/hal+json']],
            'api_platform.patch_formats' => [],
            'api_platform.exception_to_status' => [
                ExceptionInterface::class => Response::HTTP_BAD_REQUEST,
                InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
                FilterValidationException::class => Response::HTTP_BAD_REQUEST,
                OptimisticLockException::class => Response::HTTP_CONFLICT,
            ],
            'api_platform.title' => 'title',
            'api_platform.version' => 'version',
            'api_platform.show_webby' => true,
            'api_platform.allow_plain_identifiers' => false,
            'api_platform.eager_loading.enabled' => Argument::type('bool'),
            'api_platform.eager_loading.max_joins' => 30,
            'api_platform.eager_loading.force_eager' => true,
            'api_platform.eager_loading.fetch_partial' => false,
            'api_platform.http_cache.etag' => true,
            'api_platform.http_cache.max_age' => null,
            'api_platform.http_cache.shared_max_age' => null,
            'api_platform.http_cache.vary' => ['Accept'],
            'api_platform.http_cache.public' => null,
            'api_platform.enable_entrypoint' => true,
            'api_platform.enable_docs' => true,
        ];

        $pagination = [
            'client_enabled' => false,
            'client_items_per_page' => false,
            'enabled' => true,
            'enabled_parameter_name' => 'pagination',
            'items_per_page' => 30,
            'items_per_page_parameter_name' => 'itemsPerPage',
            'maximum_items_per_page' => null,
            'page_parameter_name' => 'page',
            'partial' => false,
            'client_partial' => false,
            'partial_parameter_name' => 'partial',
        ];
        foreach ($pagination as $key => $value) {
            $parameters["api_platform.collection.pagination.{$key}"] = $value;
        }
        $parameters['api_platform.collection.pagination'] = $pagination;

        foreach ($parameters as $key => $value) {
            $containerBuilderProphecy->setParameter($key, $value)->shouldBeCalled();
        }

        $containerBuilderProphecy->fileExists(Argument::type('string'))->shouldBeCalled();

        try {
            $containerBuilderProphecy->fileExists(Argument::type('string'))->shouldBeCalled();
        } catch (MethodNotFoundException $e) {
            $containerBuilderProphecy->addResource(Argument::type(ResourceInterface::class))->shouldBeCalled();
        }

        $containerBuilderProphecy->hasExtension('http://symfony.com/schema/dic/services')->shouldBeCalled();

        $definitions = [
            'api_platform.action.documentation',
            'api_platform.action.entrypoint',
            'api_platform.action.exception',
            'api_platform.action.placeholder',
            'api_platform.cache.identifiers_extractor',
            'api_platform.cache.metadata.property',
            'api_platform.cache.metadata.resource',
            'api_platform.cache.route_name_resolver',
            'api_platform.cache.subresource_operation_factory',
            'api_platform.cache_warmer.cache_pool_clearer',
            'api_platform.collection_data_provider',
            'api_platform.data_persister',
            'api_platform.filter_collection_factory',
            'api_platform.filter_locator',
            'api_platform.filters',
            'api_platform.formats_provider',
            'api_platform.identifiers_extractor',
            'api_platform.identifiers_extractor.cached',
            'api_platform.iri_converter',
            'api_platform.identifier.converter',
            'api_platform.identifier.date_normalizer',
            'api_platform.identifier.integer',
            'api_platform.identifier.uuid_normalizer',
            'api_platform.item_data_provider',
            'api_platform.listener.exception',
            'api_platform.listener.exception.validation',
            'api_platform.listener.request.add_format',
            'api_platform.listener.request.deserialize',
            'api_platform.listener.request.read',
            'api_platform.listener.view.respond',
            'api_platform.listener.view.serialize',
            'api_platform.listener.view.write',
            'api_platform.metadata.extractor.xml',
            'api_platform.metadata.property.metadata_factory.cached',
            'api_platform.metadata.property.metadata_factory.inherited',
            'api_platform.metadata.property.metadata_factory.property_info',
            'api_platform.metadata.property.metadata_factory.serializer',
            'api_platform.metadata.property.metadata_factory.xml',
            'api_platform.metadata.property.name_collection_factory.cached',
            'api_platform.metadata.property.name_collection_factory.inherited',
            'api_platform.metadata.property.name_collection_factory.property_info',
            'api_platform.metadata.property.name_collection_factory.xml',
            'api_platform.metadata.resource.metadata_factory.cached',
            'api_platform.metadata.resource.metadata_factory.operation',
            'api_platform.metadata.resource.metadata_factory.formats',
            'api_platform.metadata.resource.metadata_factory.input_output',
            'api_platform.metadata.resource.metadata_factory.short_name',
            'api_platform.metadata.resource.metadata_factory.xml',
            'api_platform.metadata.resource.name_collection_factory.cached',
            'api_platform.metadata.resource.name_collection_factory.xml',
            'api_platform.negotiator',
            'api_platform.operation_method_resolver',
            'api_platform.operation_path_resolver.custom',
            'api_platform.operation_path_resolver.dash',
            'api_platform.operation_path_resolver.router',
            'api_platform.operation_path_resolver.generator',
            'api_platform.operation_path_resolver.underscore',
            'api_platform.pagination',
            'api_platform.path_segment_name_generator.underscore',
            'api_platform.path_segment_name_generator.dash',
            'api_platform.resource_class_resolver',
            'api_platform.route_loader',
            'api_platform.route_name_resolver',
            'api_platform.route_name_resolver.cached',
            'api_platform.router',
            'api_platform.serializer.context_builder',
            'api_platform.serializer.context_builder.filter',
            'api_platform.serializer.group_filter',
            'api_platform.serializer.normalizer.item',
            'api_platform.serializer.property_filter',
            'api_platform.serializer_locator',
            'api_platform.subresource_data_provider',
            'api_platform.subresource_operation_factory',
            'api_platform.subresource_operation_factory.cached',
        ];

        foreach ($definitions as $definition) {
            $containerBuilderProphecy->setDefinition($definition, Argument::type(Definition::class))->shouldBeCalled();
        }

        $aliases = [
            'api_platform.action.delete_item' => 'api_platform.action.placeholder',
            'api_platform.action.get_collection' => 'api_platform.action.placeholder',
            'api_platform.action.get_item' => 'api_platform.action.placeholder',
            'api_platform.action.get_subresource' => 'api_platform.action.placeholder',
            'api_platform.action.post_collection' => 'api_platform.action.placeholder',
            'api_platform.action.put_item' => 'api_platform.action.placeholder',
            'api_platform.action.patch_item' => 'api_platform.action.placeholder',
            'api_platform.metadata.property.metadata_factory' => 'api_platform.metadata.property.metadata_factory.xml',
            'api_platform.metadata.property.name_collection_factory' => 'api_platform.metadata.property.name_collection_factory.property_info',
            'api_platform.metadata.resource.metadata_factory' => 'api_platform.metadata.resource.metadata_factory.xml',
            'api_platform.metadata.resource.name_collection_factory' => 'api_platform.metadata.resource.name_collection_factory.xml',
            'api_platform.operation_path_resolver' => 'api_platform.operation_path_resolver.router',
            'api_platform.operation_path_resolver.default' => 'api_platform.operation_path_resolver.underscore',
            'api_platform.path_segment_name_generator' => 'api_platform.path_segment_name_generator.underscore',
            'api_platform.property_accessor' => 'property_accessor',
            'api_platform.property_info' => 'property_info',
            'api_platform.serializer' => 'serializer',
            IriConverterInterface::class => 'api_platform.iri_converter',
            UrlGeneratorInterface::class => 'api_platform.router',
            SerializerContextBuilderInterface::class => 'api_platform.serializer.context_builder',
            CollectionDataProviderInterface::class => 'api_platform.collection_data_provider',
            ItemDataProviderInterface::class => 'api_platform.item_data_provider',
            SubresourceDataProviderInterface::class => 'api_platform.subresource_data_provider',
            DataPersisterInterface::class => 'api_platform.data_persister',
            ResourceNameCollectionFactoryInterface::class => 'api_platform.metadata.resource.name_collection_factory',
            ResourceMetadataFactoryInterface::class => 'api_platform.metadata.resource.metadata_factory',
            PropertyNameCollectionFactoryInterface::class => 'api_platform.metadata.property.name_collection_factory',
            PropertyMetadataFactoryInterface::class => 'api_platform.metadata.property.metadata_factory',
            ResourceClassResolverInterface::class => 'api_platform.resource_class_resolver',
            PropertyFilter::class => 'api_platform.serializer.property_filter',
            GroupFilter::class => 'api_platform.serializer.group_filter',
            OperationAwareFormatsProviderInterface::class => 'api_platform.formats_provider',
            IdentifiersExtractorInterface::class => 'api_platform.identifiers_extractor.cached',
        ];

        foreach ($aliases as $alias => $service) {
            $containerBuilderProphecy->setAlias($alias, $service)->shouldBeCalled();
        }

        $containerBuilderProphecy->getParameter('kernel.project_dir')->willReturn(__DIR__);
        $containerBuilderProphecy->getParameter('kernel.debug')->willReturn(false);

        $containerBuilderProphecy->getDefinition('api_platform.http_cache.purger.varnish')->willReturn(new Definition());

        // irrelevant, but to prevent errors
        // https://github.com/symfony/symfony/pull/29944
        if (method_exists(ContainerBuilder::class, 'removeBindings')) {
            $containerBuilderProphecy->removeBindings(Argument::type('string'))->will(function () {});
        } elseif (method_exists(ContainerBuilder::class, 'addRemovedBindingIds')) {
            // remove this once https://github.com/symfony/symfony/pull/31173 is released
            $containerBuilderProphecy->addRemovedBindingIds(Argument::type('string'))->will(function () {});
        }

        return $containerBuilderProphecy;
    }

    private function getBaseContainerBuilderProphecy(array $doctrineIntegrationsToLoad = ['orm'])
    {
        $containerBuilderProphecy = $this->getPartialContainerBuilderProphecy();

        $containerBuilderProphecy->hasParameter('kernel.debug')->willReturn(true);
        $containerBuilderProphecy->getParameter('kernel.debug')->willReturn(false);

        $containerBuilderProphecy->getParameter('kernel.bundles')->willReturn([
            'DoctrineBundle' => DoctrineBundle::class,
        ]);

        $containerBuilderProphecy->registerForAutoconfiguration(DataPersisterInterface::class)
            ->willReturn($this->childDefinitionProphecy)->shouldBeCalledTimes(1);
        $this->childDefinitionProphecy->addTag('api_platform.data_persister')->shouldBeCalledTimes(1);

        $containerBuilderProphecy->registerForAutoconfiguration(ItemDataProviderInterface::class)
            ->willReturn($this->childDefinitionProphecy)->shouldBeCalledTimes(1);
        $this->childDefinitionProphecy->addTag('api_platform.item_data_provider')->shouldBeCalledTimes(1);

        $containerBuilderProphecy->registerForAutoconfiguration(CollectionDataProviderInterface::class)
            ->willReturn($this->childDefinitionProphecy)->shouldBeCalledTimes(1);
        $this->childDefinitionProphecy->addTag('api_platform.collection_data_provider')->shouldBeCalledTimes(1);

        $containerBuilderProphecy->registerForAutoconfiguration(SubresourceDataProviderInterface::class)
            ->willReturn($this->childDefinitionProphecy)->shouldBeCalledTimes(1);
        $this->childDefinitionProphecy->addTag('api_platform.subresource_data_provider')->shouldBeCalledTimes(1);

        $containerBuilderProphecy->registerForAutoconfiguration(FilterInterface::class)
            ->willReturn($this->childDefinitionProphecy)->shouldBeCalledTimes(1);
        $this->childDefinitionProphecy->addTag('api_platform.filter')->shouldBeCalledTimes(1);

        $containerBuilderProphecy->registerForAutoconfiguration(GraphQlTypeInterface::class)
            ->willReturn($this->childDefinitionProphecy)->shouldBeCalledTimes(1);
        $this->childDefinitionProphecy->addTag('api_platform.graphql.type')->shouldBeCalledTimes(1);

        $containerBuilderProphecy->registerForAutoconfiguration(QueryItemResolverInterface::class)
            ->willReturn($this->childDefinitionProphecy)->shouldBeCalledTimes(1);
        $containerBuilderProphecy->registerForAutoconfiguration(QueryCollectionResolverInterface::class)
            ->willReturn($this->childDefinitionProphecy)->shouldBeCalledTimes(1);
        $this->childDefinitionProphecy->addTag('api_platform.graphql.query_resolver')->shouldBeCalledTimes(2);

        $containerBuilderProphecy->registerForAutoconfiguration(MutationResolverInterface::class)
            ->willReturn($this->childDefinitionProphecy)->shouldBeCalledTimes(1);
        $this->childDefinitionProphecy->addTag('api_platform.graphql.mutation_resolver')->shouldBeCalledTimes(1);

        $containerBuilderProphecy->registerForAutoconfiguration(QueryItemExtensionInterface::class)
            ->willReturn($this->childDefinitionProphecy)->shouldBeCalledTimes(1);
        $this->childDefinitionProphecy->addTag('api_platform.doctrine.orm.query_extension.item')->shouldBeCalledTimes(1);

        $containerBuilderProphecy->registerForAutoconfiguration(QueryCollectionExtensionInterface::class)
            ->willReturn($this->childDefinitionProphecy)->shouldBeCalledTimes(1);
        $this->childDefinitionProphecy->addTag('api_platform.doctrine.orm.query_extension.collection')->shouldBeCalledTimes(1);

        $containerBuilderProphecy->registerForAutoconfiguration(DoctrineOrmAbstractContextAwareFilter::class)
            ->willReturn($this->childDefinitionProphecy)->shouldBeCalledTimes(1);
        $this->childDefinitionProphecy->setBindings(['$requestStack' => null])->shouldBeCalledTimes(1);

        if (\in_array('odm', $doctrineIntegrationsToLoad, true)) {
            $containerBuilderProphecy->registerForAutoconfiguration(AggregationItemExtensionInterface::class)
                ->willReturn($this->childDefinitionProphecy)->shouldBeCalledTimes(1);
            $this->childDefinitionProphecy->addTag('api_platform.doctrine.mongodb.aggregation_extension.item')->shouldBeCalledTimes(1);

            $containerBuilderProphecy->registerForAutoconfiguration(AggregationCollectionExtensionInterface::class)
                ->willReturn($this->childDefinitionProphecy)->shouldBeCalledTimes(1);
            $this->childDefinitionProphecy->addTag('api_platform.doctrine.mongodb.aggregation_extension.collection')->shouldBeCalledTimes(1);

            $containerBuilderProphecy->registerForAutoconfiguration(DoctrineMongoDbOdmAbstractFilter::class)
                ->willReturn($this->childDefinitionProphecy)->shouldBeCalledTimes(1);
            $this->childDefinitionProphecy->setBindings(Argument::allOf(Argument::withEntry('$managerRegistry', Argument::type(Reference::class))))->shouldBeCalledTimes(1);
        }

        $containerBuilderProphecy->registerForAutoconfiguration(DataTransformerInterface::class)
            ->willReturn($this->childDefinitionProphecy)->shouldBeCalledTimes(1);
        $this->childDefinitionProphecy->addTag('api_platform.data_transformer')->shouldBeCalledTimes(1);

        $containerBuilderProphecy->addResource(Argument::type(DirectoryResource::class))->shouldBeCalled();

        $parameters = [
            'api_platform.oauth.enabled' => false,
            'api_platform.oauth.clientId' => '',
            'api_platform.oauth.clientSecret' => '',
            'api_platform.oauth.type' => 'oauth2',
            'api_platform.oauth.flow' => 'application',
            'api_platform.oauth.tokenUrl' => '/oauth/v2/token',
            'api_platform.oauth.authorizationUrl' => '/oauth/v2/auth',
            'api_platform.oauth.scopes' => [],
            'api_platform.swagger.api_keys' => [],
            'api_platform.enable_swagger' => true,
            'api_platform.enable_swagger_ui' => true,
            'api_platform.enable_re_doc' => true,
            'api_platform.graphql.enabled' => true,
            'api_platform.graphql.graphiql.enabled' => true,
            'api_platform.resource_class_directories' => Argument::type('array'),
            'api_platform.validator.serialize_payload_fields' => [],
            'api_platform.elasticsearch.enabled' => false,
        ];

        foreach ($parameters as $key => $value) {
            $containerBuilderProphecy->setParameter($key, $value)->shouldBeCalled();
        }
        $containerBuilderProphecy->hasParameter('test.client.parameters')->wilLReturn(true);

        foreach (['yaml', 'xml'] as $format) {
            $definitionProphecy = $this->prophesize(Definition::class);
            $definitionProphecy->replaceArgument(0, Argument::type('array'))->shouldBeCalled();
            $containerBuilderProphecy->getDefinition('api_platform.metadata.extractor.'.$format)->willReturn($definitionProphecy->reveal())->shouldBeCalled();
        }

        $definitions = [
            'api_platform.data_collector.request',
            'api_platform.doctrine.listener.http_cache.purge',
            'api_platform.doctrine.listener.mercure.publish',
            'api_platform.doctrine.orm.boolean_filter',
            'api_platform.doctrine.orm.collection_data_provider',
            'api_platform.doctrine.orm.data_persister',
            'api_platform.doctrine.orm.date_filter',
            'api_platform.doctrine.orm.default.collection_data_provider',
            'api_platform.doctrine.orm.default.item_data_provider',
            'api_platform.doctrine.orm.exists_filter',
            'api_platform.doctrine.orm.default.subresource_data_provider',
            'api_platform.doctrine.orm.item_data_provider',
            'api_platform.doctrine.orm.metadata.property.metadata_factory',
            'api_platform.doctrine.orm.numeric_filter',
            'api_platform.doctrine.orm.order_filter',
            'api_platform.doctrine.orm.query_extension.eager_loading',
            'api_platform.doctrine.orm.query_extension.filter',
            'api_platform.doctrine.orm.query_extension.filter_eager_loading',
            'api_platform.doctrine.orm.query_extension.order',
            'api_platform.doctrine.orm.query_extension.pagination',
            'api_platform.doctrine.orm.range_filter',
            'api_platform.doctrine.orm.search_filter',
            'api_platform.doctrine.orm.subresource_data_provider',
            'api_platform.graphql.action.entrypoint',
            'api_platform.graphql.executor',
            'api_platform.graphql.type_builder',
            'api_platform.graphql.fields_builder',
            'api_platform.graphql.fields_builder_locator',
            'api_platform.graphql.schema_builder',
            'api_platform.graphql.resolver.factory.item',
            'api_platform.graphql.resolver.factory.collection',
            'api_platform.graphql.resolver.factory.item_mutation',
            'api_platform.graphql.resolver.resource_field',
            'api_platform.graphql.iterable_type',
            'api_platform.graphql.type_locator',
            'api_platform.graphql.types_container',
            'api_platform.graphql.types_factory',
            'api_platform.graphql.type_converter',
            'api_platform.graphql.query_resolver_locator',
            'api_platform.graphql.mutation_resolver_locator',
            'api_platform.graphql.normalizer.item',
            'api_platform.graphql.normalizer.object',
            'api_platform.graphql.command.export_command',
            'api_platform.hal.encoder',
            'api_platform.hal.normalizer.collection',
            'api_platform.hal.normalizer.entrypoint',
            'api_platform.hal.normalizer.item',
            'api_platform.hal.normalizer.object',
            'api_platform.http_cache.listener.response.add_tags',
            'api_platform.http_cache.listener.response.configure',
            'api_platform.http_cache.purger.varnish_client',
            'api_platform.http_cache.purger.varnish',
            'api_platform.hydra.listener.response.add_link_header',
            'api_platform.hydra.normalizer.collection',
            'api_platform.hydra.normalizer.collection_filters',
            'api_platform.hydra.normalizer.constraint_violation_list',
            'api_platform.hydra.normalizer.documentation',
            'api_platform.hydra.normalizer.entrypoint',
            'api_platform.hydra.normalizer.error',
            'api_platform.hydra.normalizer.partial_collection_view',
            'api_platform.jsonld.action.context',
            'api_platform.jsonld.context_builder',
            'api_platform.jsonld.encoder',
            'api_platform.jsonld.normalizer.item',
            'api_platform.jsonld.normalizer.object',
            'api_platform.listener.view.validate',
            'api_platform.listener.view.validate_query_parameters',
            'api_platform.mercure.listener.response.add_link_header',
            'api_platform.messenger.data_persister',
            'api_platform.messenger.data_transformer',
            'api_platform.metadata.extractor.yaml',
            'api_platform.metadata.property.metadata_factory.annotation',
            'api_platform.metadata.property.metadata_factory.validator',
            'api_platform.metadata.property.metadata_factory.yaml',
            'api_platform.metadata.property.name_collection_factory.yaml',
            'api_platform.metadata.resource.filter_metadata_factory.annotation',
            'api_platform.metadata.resource.metadata_factory.annotation',
            'api_platform.metadata.resource.metadata_factory.operation',
            'api_platform.metadata.resource.metadata_factory.php_doc',
            'api_platform.metadata.resource.metadata_factory.short_name',
            'api_platform.metadata.resource.metadata_factory.yaml',
            'api_platform.metadata.resource.name_collection_factory.annotation',
            'api_platform.metadata.resource.name_collection_factory.yaml',
            'api_platform.metadata.subresource.metadata_factory.annotation',
            'api_platform.problem.encoder',
            'api_platform.problem.normalizer.constraint_violation_list',
            'api_platform.problem.normalizer.error',
            'api_platform.swagger.action.ui',
            'api_platform.swagger.command.swagger_command',
            'api_platform.swagger.listener.ui',
            'api_platform.swagger.normalizer.api_gateway',
            'api_platform.swagger.normalizer.documentation',
            'api_platform.validator',
            'test.api_platform.client',
        ];

        if (\in_array('odm', $doctrineIntegrationsToLoad, true)) {
            $definitions = array_merge($definitions, [
                'api_platform.doctrine_mongodb.odm.aggregation_extension.filter',
                'api_platform.doctrine_mongodb.odm.aggregation_extension.order',
                'api_platform.doctrine_mongodb.odm.aggregation_extension.pagination',
                'api_platform.doctrine_mongodb.odm.boolean_filter',
                'api_platform.doctrine_mongodb.odm.collection_data_provider',
                'api_platform.doctrine_mongodb.odm.data_persister',
                'api_platform.doctrine_mongodb.odm.date_filter',
                'api_platform.doctrine_mongodb.odm.default.collection_data_provider',
                'api_platform.doctrine_mongodb.odm.default.item_data_provider',
                'api_platform.doctrine_mongodb.odm.default.subresource_data_provider',
                'api_platform.doctrine_mongodb.odm.default_document_manager.property_info_extractor',
                'api_platform.doctrine_mongodb.odm.exists_filter',
                'api_platform.doctrine_mongodb.odm.item_data_provider',
                'api_platform.doctrine_mongodb.odm.metadata.property.metadata_factory',
                'api_platform.doctrine_mongodb.odm.numeric_filter',
                'api_platform.doctrine_mongodb.odm.order_filter',
                'api_platform.doctrine_mongodb.odm.range_filter',
                'api_platform.doctrine_mongodb.odm.search_filter',
                'api_platform.doctrine_mongodb.odm.subresource_data_provider',
            ]);
        }

        if (0 !== \count($doctrineIntegrationsToLoad)) {
            $definitions[] = 'api_platform.doctrine.metadata_factory';
        }

        foreach ($definitions as $definition) {
            $containerBuilderProphecy->setDefinition($definition, Argument::type(Definition::class))->shouldBeCalled();
        }

        $aliases = [
            'api_platform.http_cache.purger' => 'api_platform.http_cache.purger.varnish',
            'api_platform.message_bus' => 'messenger.default_bus',
            EagerLoadingExtension::class => 'api_platform.doctrine.orm.query_extension.eager_loading',
            FilterExtension::class => 'api_platform.doctrine.orm.query_extension.filter',
            FilterEagerLoadingExtension::class => 'api_platform.doctrine.orm.query_extension.filter_eager_loading',
            PaginationExtension::class => 'api_platform.doctrine.orm.query_extension.pagination',
            OrderExtension::class => 'api_platform.doctrine.orm.query_extension.order',
            ValidatorInterface::class => 'api_platform.validator',
            SearchFilter::class => 'api_platform.doctrine.orm.search_filter',
            OrderFilter::class => 'api_platform.doctrine.orm.order_filter',
            RangeFilter::class => 'api_platform.doctrine.orm.range_filter',
            DateFilter::class => 'api_platform.doctrine.orm.date_filter',
            BooleanFilter::class => 'api_platform.doctrine.orm.boolean_filter',
            NumericFilter::class => 'api_platform.doctrine.orm.numeric_filter',
            ExistsFilter::class => 'api_platform.doctrine.orm.exists_filter',
        ];

        if (\in_array('odm', $doctrineIntegrationsToLoad, true)) {
            $aliases += [
                MongoDbOdmSearchFilter::class => 'api_platform.doctrine_mongodb.odm.search_filter',
                MongoDbOdmBooleanFilter::class => 'api_platform.doctrine_mongodb.odm.boolean_filter',
                MongoDbOdmDateFilter::class => 'api_platform.doctrine_mongodb.odm.date_filter',
                MongoDbOdmExistsFilter::class => 'api_platform.doctrine_mongodb.odm.exists_filter',
                MongoDbOdmNumericFilter::class => 'api_platform.doctrine_mongodb.odm.numeric_filter',
                MongoDbOdmOrderFilter::class => 'api_platform.doctrine_mongodb.odm.order_filter',
                MongoDbOdmRangeFilter::class => 'api_platform.doctrine_mongodb.odm.range_filter',
                MongoDbOdmFilterExtension::class => 'api_platform.doctrine_mongodb.odm.aggregation_extension.filter',
                MongoDbOdmOrderExtension::class => 'api_platform.doctrine_mongodb.odm.aggregation_extension.order',
                MongoDbOdmPaginationExtension::class => 'api_platform.doctrine_mongodb.odm.aggregation_extension.pagination',
            ];
        }

        foreach ($aliases as $alias => $service) {
            $containerBuilderProphecy->setAlias($alias, $service)->shouldBeCalled();
        }

        $containerBuilderProphecy->hasParameter('api_platform.metadata_cache')->willReturn(false);

        // irrelevant, but to prevent errors
        $containerBuilderProphecy->removeDefinition('api_platform.cache_warmer.cache_pool_clearer')->will(function () {});

        $containerBuilderProphecy->getDefinition('api_platform.mercure.listener.response.add_link_header')->willReturn(new Definition());

        return $containerBuilderProphecy;
    }
}
