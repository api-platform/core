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
                ->scalarNode('title')->defaultValue('')->info('The title of the API.')->end()
                ->scalarNode('description')->defaultValue('')->info('The description of the API.')->end()
                ->scalarNode('version')->defaultValue('0.0.0')->info('The version of the API.')->end()
                ->scalarNode('default_operation_path_resolver')->defaultValue('api_platform.operation_path_resolver.underscore')->info('Specify the default operation path resolver to use for generating resources operations path.')->end()
                ->scalarNode('name_converter')->defaultNull()->info('Specify a name converter to use.')->end()
                ->arrayNode('eager_loading')
                    ->canBeDisabled()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultTrue()->info('To enable or disable eager loading')->end()
                        ->integerNode('max_joins')->defaultValue(30)->info('Max number of joined relations before EagerLoading throws a RuntimeException')->end()
                        ->booleanNode('force_eager')->defaultTrue()->info('Force join on every relation. If disabled, it will only join relations having the EAGER fetch mode.')->end()
                    ->end()
                ->end()
                ->booleanNode('enable_fos_user')->defaultValue(false)->info('Enable the FOSUserBundle integration.')->end()
                ->booleanNode('enable_nelmio_api_doc')->defaultValue(false)->info('Enable the Nelmio Api doc integration.')->end()
                ->booleanNode('enable_swagger')->defaultValue(true)->info('Enable the Swagger documentation and export.')->end()
                ->booleanNode('enable_swagger_ui')->defaultValue(true)->info('Enable Swagger ui.')->end()

                ->arrayNode('collection')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('order')->defaultNull()->info('The default order of results.')->end()
                        ->scalarNode('order_parameter_name')->defaultValue('order')->cannotBeEmpty()->info('The name of the query parameter to order results.')->end()
                        ->arrayNode('pagination')
                            ->canBeDisabled()
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('enabled')->defaultTrue()->info('To enable or disable pagination for all resource collections by default.')->end()
                                ->booleanNode('client_enabled')->defaultFalse()->info('To allow the client to enable or disable the pagination.')->end()
                                ->booleanNode('client_items_per_page')->defaultFalse()->info('To allow the client to set the number of items per page.')->end()
                                ->integerNode('items_per_page')->defaultValue(30)->info('The default number of items per page.')->end()
                                ->integerNode('maximum_items_per_page')->defaultNull()->info('The maximum number of items per page.')->end()
                                ->scalarNode('page_parameter_name')->defaultValue('page')->cannotBeEmpty()->info('The default name of the parameter handling the page number.')->end()
                                ->scalarNode('enabled_parameter_name')->defaultValue('pagination')->cannotBeEmpty()->info('The name of the query parameter to enable or disable pagination.')->end()
                                ->scalarNode('items_per_page_parameter_name')->defaultValue('itemsPerPage')->cannotBeEmpty()->info('The name of the query parameter to set the number of items per page.')->end()
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
                                if (is_int($httpStatusCode)) {
                                    continue;
                                }

                                if (defined($httpStatusCodeConstant = sprintf('%s::%s', Response::class, $httpStatusCode))) {
                                    $httpStatusCode = constant($httpStatusCodeConstant);
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
