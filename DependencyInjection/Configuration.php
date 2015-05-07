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
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('dunglas_api');

        $rootNode
            ->children()
                ->scalarNode('title')->cannotBeEmpty()->isRequired()->info('The title of the API.')->end()
                ->scalarNode('description')->cannotBeEmpty()->isRequired()->info('The description of the API.')->end()
                ->scalarNode('cache')->defaultFalse()->info('The caching service to use. Set to "dunglas_api.mapping.cache.apc" to enable APC metadata caching.')->end()
                ->booleanNode('enable_fos_user')->defaultValue(false)->info('Enable the FOSUserBundle integration.')->end()
                ->arrayNode('collection')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('order')->defaultNull()->info('The default order of results.')->end()
                        ->arrayNode('pagination')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('page_parameter_name')->defaultValue('page')->cannotBeEmpty()->info('The name of the parameter handling the page number.')->end()
                                ->arrayNode('items_per_page')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->integerNode('number')->min(1)->defaultValue(30)->cannotBeEmpty()->info('The default number of items perm page in collections.')->end()
                                        ->booleanNode('enable_client_request')->defaultValue(false)->info('Allow the client to change the number of elements by page.')->end()
                                        ->scalarNode('parameter_name')->defaultValue('itemsPerPage')->info('The name of the parameter to change the number of elements by page client side.')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
