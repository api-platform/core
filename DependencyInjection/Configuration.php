<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * The configuration of the bundle.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('dunglas_api');

        $rootNode
            ->children()
                ->scalarNode('title')->cannotBeEmpty()->isRequired()->info('The title of the API.')->end()
                ->scalarNode('description')->cannotBeEmpty()->isRequired()->info('The description of the API.')->end()
                ->arrayNode('supported_formats')
                    ->defaultValue(['jsonld'])
                    ->cannotBeEmpty()
                    ->info('The list of enabled formats. The first one will be the default.')
                    ->prototype('scalar')->end()
                ->end()
                ->scalarNode('cache')->defaultFalse()->info('The caching service to use. Set to "dunglas_api.mapping.cache.apc" to enable APC metadata caching.')->end()
                ->booleanNode('enable_fos_user')->defaultValue(false)->info('Enable the FOSUserBundle integration.')->end()
                ->arrayNode('collection')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('filter_name')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('order')->defaultValue('order')->cannotBeEmpty()->info('The name of the keyword for the order filter.')->end()
                            ->end()
                        ->end()
                        ->scalarNode('order')->defaultNull()->info('The default order of results.')->end()
                        ->arrayNode('pagination')
                            ->canBeDisabled()
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('client_can_enable')->defaultFalse()->info('To allow the client to enable or disable the pagination.')->end()
                                ->scalarNode('enable_parameter')->defaultValue('enablePagination')->cannotBeEmpty()->info('The name of the query parameter to enable or disable pagination.')->end()
                                ->scalarNode('page_parameter')->defaultValue('page')->cannotBeEmpty()->info('The name of the parameter handling the page number.')->end()
                                ->arrayNode('items_per_page')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->integerNode('default')->min(1)->defaultValue(30)->cannotBeEmpty()->info('The default number of items perm page in collections.')->end()
                                        ->booleanNode('client_can_change')->defaultValue(false)->info('Allow the client to change the number of elements by page.')->end()
                                        ->scalarNode('parameter')->defaultValue('itemsPerPage')->info('The name of the parameter to change the number of elements by page client side.')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        $this->addResourceSection($rootNode);

        return $treeBuilder;
    }

    /**
     * add the resource section.
     *
     * @param Node $rootNode
     */
    private function addResourceSection($rootNode)
    {
        $rootNode
            ->fixXmlConfig('resource')
            ->children()
                ->arrayNode('resources')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('resource_class')
                                ->cannotBeEmpty()
                                ->defaultValue('Dunglas\ApiBundle\Api\Resource')
                                ->info('The resource class name')
                            ->end()
                            ->scalarNode('entry_class')->cannotBeEmpty()->isRequired()->info('The entity class name')->end()
                            ->scalarNode('short_name')->info('The resource short name')->end()

                            // operations
                            ->arrayNode('collection_operations')
                                ->info('The collections operation to allow')
                                    ->prototype('scalar')->end()
                                    ->defaultValue(['GET', 'POST'])
                            ->end()
                            ->arrayNode('item_operations')
                                ->info('The items operation to allow')
                                    ->prototype('scalar')->end()
                                    ->defaultValue(['GET', 'PUT', 'DELETE'])
                            ->end()

                            // custom operations
                            ->arrayNode('collection_custom_operations')
                                ->info('The custom collections operation to add')
                                ->prototype('array')
                                    ->children()
                                        ->arrayNode('methods')
                                            ->prototype('scalar')->end()
                                        ->end()
                                        ->scalarNode('path')->defaultNull()->end()
                                        ->scalarNode('route')->defaultNull()->end()
                                        ->scalarNode('controller')->isRequired()->end()
                                        ->variableNode('context')->defaultValue(['hydra:title' => null])->end()
                                    ->end()
                                ->end()
                            ->end()

                            ->arrayNode('item_custom_operations')
                                ->info('The custom items operation to add')
                                ->prototype('array')
                                    ->children()
                                        ->arrayNode('methods')
                                            ->prototype('scalar')->end()
                                        ->end()
                                        ->scalarNode('path')->defaultNull()->end()
                                        ->scalarNode('route')->defaultNull()->end()
                                        ->scalarNode('controller')->isRequired()->end()
                                        ->variableNode('context')->defaultValue(['hydra:title' => null])->end()
                                    ->end()
                                ->end()
                            ->end()

                            // (de)normalization context
                            ->booleanNode('jsonld_context_embedded')
                                ->info('Embed the context in the response')
                                ->defaultFalse()
                            ->end()
                            ->arrayNode('normalization_groups')
                                ->info('The normalization groups')
                                ->prototype('scalar')->end()
                            ->end()
                            ->arrayNode('denormalization_groups')
                                ->info('The denormalization groups')
                                ->prototype('scalar')->end()
                            ->end()

                            // validation groups
                            ->arrayNode('validation_groups')
                                ->info('The validation groups')
                                ->prototype('scalar')->end()
                            ->end()

                            // filters
                            ->arrayNode('filters')
                                ->info('The search filters')
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }
}
