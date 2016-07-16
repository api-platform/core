<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * The configuration of the bundle.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
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
                ->arrayNode('naming')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('resource_path_naming_strategy')->defaultValue('api_platform.naming.resource_path_naming_strategy.underscore')->info('Specify the strategy to use for generating resource paths.')->end()
                    ->end()
                ->end()
                ->scalarNode('name_converter')->defaultNull()->info('Specify a name converter to use.')->end()
                ->booleanNode('enable_fos_user')->defaultValue(false)->info('Enable the FOSUserBundle integration.')->end()
                ->booleanNode('enable_nelmio_api_doc')->defaultValue(false)->info('Enable the Nelmio Api doc integration.')->end()
                ->booleanNode('enable_swagger')->defaultValue(true)->info('Enable the Swagger documentation and export.')->end()

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
                                ->scalarNode('page_parameter_name')->defaultValue('page')->cannotBeEmpty()->info('The default name of the parameter handling the page number.')->end()
                                ->scalarNode('enabled_parameter_name')->defaultValue('pagination')->cannotBeEmpty()->info('The name of the query parameter to enable or disable pagination.')->end()
                                ->scalarNode('items_per_page_parameter_name')->defaultValue('itemsPerPage')->cannotBeEmpty()->info('The name of the query parameter to set the number of items per page.')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        $this->addFormatSection($rootNode, 'formats', ['jsonld' => ['mime_types' => ['application/ld+json']]]);
        $this->addFormatSection($rootNode, 'error_formats', ['jsonld' => ['mime_types' => ['application/ld+json']]]);

        return $treeBuilder;
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
