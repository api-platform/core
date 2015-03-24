<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('dunglas_json_ld_api');

        $rootNode
            ->children()
                ->scalarNode('title')->cannotBeEmpty()->isRequired()->info('API\'s title.')->end()
                ->scalarNode('description')->cannotBeEmpty()->isRequired()->info('API\'s description.')->end()
                ->scalarNode('cache')->defaultFalse()->info('Cache service to use, for instance "dunglas_json_ld_api.mapping.cache.apc".')->end()
                ->booleanNode('enable_fos_user_event_subscriber')->defaultFalse()->end()
                ->arrayNode('default')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('elements_by_page')->min(1)->defaultValue(30)->cannotBeEmpty()->info('The default number of elements by page in collections.')->end()
                        ->enumNode('order')->values([null, 'ASC', 'DESC'])->defaultNull()->info('The default order of results.')->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
