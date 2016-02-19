<?php

/*
 * This file is part of the API Platform Builder package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Builder\Bridge\Symfony\Bundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('api_builder');

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
                ->booleanNode('enable_fos_user')->defaultValue(false)->info('Enable the FOSUserBundle integration.')->end()
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
            ->end()
        ;

        return $treeBuilder;
    }
}
