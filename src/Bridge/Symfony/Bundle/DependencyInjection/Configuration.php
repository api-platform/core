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

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use FOS\UserBundle\FOSUserBundle;
use GraphQL\GraphQL;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

/**
 * The configuration of the bundle.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('api_platform');

        $rootNode
            ->children()
                ->scalarNode('title')
                    ->info('The title of the API.')
                    ->cannotBeEmpty()
                    ->defaultValue('')
                ->end()
                ->scalarNode('description')
                    ->info('The description of the API.')
                    ->cannotBeEmpty()
                    ->defaultValue('')
                ->end()
                ->scalarNode('version')
                    ->info('The version of the API.')
                    ->cannotBeEmpty()
                    ->defaultValue('0.0.0')
                ->end()
                ->scalarNode('default_operation_path_resolver')
                    ->beforeNormalization()->always(function ($v) {
                        @trigger_error('The use of the `default_operation_path_resolver` has been deprecated in 2.1 and will be removed in 3.0. Use `path_segment_name_generator` instead.', E_USER_DEPRECATED);

                        return $v;
                    })->end()
                    ->defaultValue('api_platform.operation_path_resolver.underscore')
                    ->info('[Deprecated] Specify the default operation path resolver to use for generating resources operations path.')
                ->end()
                ->scalarNode('name_converter')->defaultNull()->info('Specify a name converter to use.')->end()
                ->scalarNode('path_segment_name_generator')->defaultValue('api_platform.path_segment_name_generator.underscore')->info('Specify a path name generator to use.')->end()
                ->booleanNode('allow_plain_identifiers')->defaultFalse()->info('Allow plain identifiers, for example "id" instead of "@id" when denormalizing a relation.')->end()
                ->arrayNode('validator')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->variableNode('serialize_payload_fields')->defaultValue([])->info('Enable the serialization of payload fields when a validation error is thrown.')->end()
                    ->end()
                ->end()
                ->arrayNode('eager_loading')
                    ->canBeDisabled()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultTrue()->info('To enable or disable eager loading')->end()
                        ->booleanNode('fetch_partial')->defaultFalse()->info('Fetch only partial data according to serialization groups. If enabled, Doctrine ORM entities will not work as expected if any of the other fields are used.')->end()
                        ->integerNode('max_joins')->defaultValue(30)->info('Max number of joined relations before EagerLoading throws a RuntimeException')->end()
                        ->booleanNode('force_eager')->defaultTrue()->info('Force join on every relation. If disabled, it will only join relations having the EAGER fetch mode.')->end()
                    ->end()
                ->end()
                ->booleanNode('enable_fos_user')->defaultValue(class_exists(FOSUserBundle::class))->info('Enable the FOSUserBundle integration.')->end()
                ->booleanNode('enable_nelmio_api_doc')
                    ->beforeNormalization()->always(function ($v) {
                        if ($v) {
                            @trigger_error('Enabling the NelmioApiDocBundle integration has been deprecated in 2.2 and will be removed in 3.0. NelmioApiDocBundle 3 has native support for API Platform.', E_USER_DEPRECATED);
                        }

                        return $v;
                    })->end()
                    ->defaultValue(false)
                    ->info('[Deprecated] Enable the NelmioApiDocBundle integration.')
                ->end()
                ->booleanNode('enable_swagger')->defaultValue(true)->info('Enable the Swagger documentation and export.')->end()
                ->booleanNode('enable_swagger_ui')->defaultValue(class_exists(TwigBundle::class))->info('Enable Swagger ui.')->end()
                ->booleanNode('enable_entrypoint')->defaultTrue()->info('Enable the entrypoint')->end()
                ->booleanNode('enable_docs')->defaultTrue()->info('Enable the docs')->end()

                ->arrayNode('oauth')
                    ->canBeEnabled()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultFalse()->info('To enable or disable oauth')->end()
                        ->scalarNode('clientId')->defaultValue('')->info('The oauth client id.')->end()
                        ->scalarNode('clientSecret')->defaultValue('')->info('The oauth client secret.')->end()
                        ->scalarNode('type')->defaultValue('oauth2')->info('The oauth client secret.')->end()
                        ->scalarNode('flow')->defaultValue('application')->info('The oauth flow grant type.')->end()
                        ->scalarNode('tokenUrl')->defaultValue('/oauth/v2/token')->info('The oauth token url.')->end()
                        ->scalarNode('authorizationUrl')->defaultValue('/oauth/v2/auth')->info('The oauth authentication url.')->end()
                        ->arrayNode('scopes')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('graphql')
                    ->canBeEnabled()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultValue(class_exists(GraphQL::class))->info('To enable or disable GraphQL.')->end()
                        ->arrayNode('graphiql')
                            ->canBeEnabled()
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('enabled')->defaultValue(class_exists(GraphQL::class) && class_exists(TwigBundle::class))->info('To enable or disable GraphiQL.')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('swagger')
                    ->addDefaultsIfNotSet()
                    ->children()
                             ->arrayNode('api_keys')
                                 ->prototype('array')
                                    ->children()
                                    ->scalarNode('name')
                                        ->info('The name of the header or query parameter containing the api key.')
                                    ->end()
                                    ->enumNode('type')
                                        ->info('Whether the api key should be a query parameter or a header.')
                                        ->values(['query', 'header'])
                                    ->end()
                                ->end()
                             ->end()
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('collection')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('order')->defaultValue('ASC')->info('The default order of results.')->end() // Default ORDER is required for postgresql and mysql >= 5.7 when using LIMIT/OFFSET request
                        ->scalarNode('order_parameter_name')->defaultValue('order')->cannotBeEmpty()->info('The name of the query parameter to order results.')->end()
                        ->arrayNode('pagination')
                            ->canBeDisabled()
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('enabled')->defaultTrue()->info('To enable or disable pagination for all resource collections by default.')->end()
                                ->booleanNode('partial')->defaultFalse()->info('To enable or disable partial pagination for all resource collections by default when pagination is enabled.')->end()
                                ->booleanNode('client_enabled')->defaultFalse()->info('To allow the client to enable or disable the pagination.')->end()
                                ->booleanNode('client_items_per_page')->defaultFalse()->info('To allow the client to set the number of items per page.')->end()
                                ->booleanNode('client_partial')->defaultFalse()->info('To allow the client to enable or disable partial pagination.')->end()
                                ->integerNode('items_per_page')->defaultValue(30)->info('The default number of items per page.')->end()
                                ->integerNode('maximum_items_per_page')->defaultNull()->info('The maximum number of items per page.')->end()
                                ->scalarNode('page_parameter_name')->defaultValue('page')->cannotBeEmpty()->info('The default name of the parameter handling the page number.')->end()
                                ->scalarNode('enabled_parameter_name')->defaultValue('pagination')->cannotBeEmpty()->info('The name of the query parameter to enable or disable pagination.')->end()
                                ->scalarNode('items_per_page_parameter_name')->defaultValue('itemsPerPage')->cannotBeEmpty()->info('The name of the query parameter to set the number of items per page.')->end()
                                ->scalarNode('partial_parameter_name')->defaultValue('partial')->cannotBeEmpty()->info('The name of the query parameter to enable or disable partial pagination.')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('mapping')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('paths')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('resource_class_directories')
                    ->prototype('scalar')->end()
                ->end()

                ->arrayNode('http_cache')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('etag')->defaultTrue()->info('Automatically generate etags for API responses.')->end()
                        ->integerNode('max_age')->defaultNull()->info('Default value for the response max age.')->end()
                        ->integerNode('shared_max_age')->defaultNull()->info('Default value for the response shared (proxy) max age.')->end()
                        ->arrayNode('vary')
                            ->defaultValue(['Accept'])
                            ->prototype('scalar')->end()
                            ->info('Default values of the "Vary" HTTP header.')
                        ->end()
                        ->booleanNode('public')->defaultNull()->info('To make all responses public by default.')->end()
                        ->arrayNode('invalidation')
                            ->info('Enable the tags-based cache invalidation system.')
                            ->canBeEnabled()
                            ->children()
                                ->arrayNode('varnish_urls')
                                    ->defaultValue([])
                                    ->prototype('scalar')->end()
                                    ->info('URLs of the Varnish servers to purge using cache tags when a resource is updated.')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()

            ->end();

        $this->addExceptionToStatusSection($rootNode);

        $this->addFormatSection($rootNode, 'formats', [
            'jsonld' => ['mime_types' => ['application/ld+json']],
            'json' => ['mime_types' => ['application/json']], // Swagger support
            'html' => ['mime_types' => ['text/html']], // Swagger UI support
        ]);
        $this->addFormatSection($rootNode, 'error_formats', [
            'jsonproblem' => ['mime_types' => ['application/problem+json']],
            'jsonld' => ['mime_types' => ['application/ld+json']],
        ]);

        return $treeBuilder;
    }

    /**
     * Adds an exception to status section.
     *
     * @param ArrayNodeDefinition $rootNode
     *
     * @throws InvalidConfigurationException
     */
    private function addExceptionToStatusSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('exception_to_status')
                    ->defaultValue([
                        ExceptionInterface::class => Response::HTTP_BAD_REQUEST,
                        InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
                    ])
                    ->info('The list of exceptions mapped to their HTTP status code.')
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('exception_class')
                    ->beforeNormalization()
                        ->ifArray()
                        ->then(function (array $exceptionToStatus) {
                            foreach ($exceptionToStatus as &$httpStatusCode) {
                                if (\is_int($httpStatusCode)) {
                                    continue;
                                }

                                if (\defined($httpStatusCodeConstant = sprintf('%s::%s', Response::class, $httpStatusCode))) {
                                    @trigger_error(sprintf('Using a string "%s" as a constant of the "%s" class is deprecated since API Platform 2.1 and will not be possible anymore in API Platform 3. Use the Symfony\'s custom YAML extension for PHP constants instead (i.e. "!php/const:%s").', $httpStatusCode, Response::class, $httpStatusCodeConstant), E_USER_DEPRECATED);

                                    $httpStatusCode = \constant($httpStatusCodeConstant);
                                }
                            }

                            return $exceptionToStatus;
                        })
                    ->end()
                    ->prototype('integer')->end()
                    ->validate()
                        ->ifArray()
                        ->then(function (array $exceptionToStatus) {
                            foreach ($exceptionToStatus as $httpStatusCode) {
                                if ($httpStatusCode < 100 || $httpStatusCode >= 600) {
                                    throw new InvalidConfigurationException(sprintf('The HTTP status code "%s" is not valid.', $httpStatusCode));
                                }
                            }

                            return $exceptionToStatus;
                        })
                    ->end()
                ->end()
            ->end();
    }

    /**
     * Adds a format section.
     *
     * @param ArrayNodeDefinition $rootNode
     * @param string              $key
     * @param array               $defaultValue
     */
    private function addFormatSection(ArrayNodeDefinition $rootNode, string $key, array $defaultValue)
    {
        $rootNode
            ->children()
                ->arrayNode($key)
                    ->defaultValue($defaultValue)
                    ->info('The list of enabled formats. The first one will be the default.')
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('format')
                    ->beforeNormalization()
                        ->ifArray()
                        ->then(function ($v) {
                            foreach ($v as $format => $value) {
                                if (isset($value['mime_types'])) {
                                    continue;
                                }

                                $v[$format] = ['mime_types' => $value];
                            }

                            return $v;
                        })
                    ->end()
                    ->prototype('array')
                        ->children()
                            ->arrayNode('mime_types')->prototype('scalar')->end()->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
