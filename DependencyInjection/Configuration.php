<?php

/*
 * This file is part of the API Platform project.
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

        // We check if symfony's doctrine bridge is enabled or not to choose the defaultValue for `enable_doctrine_orm`.
        $enableDoctrineOrm = interface_exists('\Symfony\Bridge\Doctrine\RegistryInterface');

        $rootNode
            ->children()
                ->scalarNode('title')->cannotBeEmpty()->isRequired()->info('The title of the API.')->end()
                ->scalarNode('description')->cannotBeEmpty()->isRequired()->info('The description of the API.')->end()
                ->scalarNode('cache')->defaultFalse()->info('The caching service to use. Set to "dunglas_api.mapping.cache.apc" to enable APC metadata caching.')->end()
                ->booleanNode('enable_doctrine_orm')->defaultValue($enableDoctrineOrm)->info('Enable the Doctrine ORM integration.')->end()
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
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('page_parameter_name')->defaultValue('page')->cannotBeEmpty()->info('The name of the parameter handling the page number.')->end()
                                ->arrayNode('items_per_page')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->integerNode('number')->min(1)->defaultValue(30)->info('The default number of items perm page in collections.')->end()
                                        ->booleanNode('enable_client_request')->defaultValue(false)->info('Allow the client to change the number of elements by page.')->end()
                                        ->scalarNode('parameter_name')->defaultValue('itemsPerPage')->info('The name of the parameter to change the number of elements by page client side.')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
