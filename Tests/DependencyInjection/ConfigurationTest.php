<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Tests\DependencyInjection;

use Dunglas\ApiBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultConfig()
    {
        $configuration = new Configuration();
        $treeBuilder = $configuration->getConfigTreeBuilder();
        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, ['dunglas_api' => ['title' => 'title', 'description' => 'description']]);

        $this->assertInstanceOf(ConfigurationInterface::class, $configuration);
        $this->assertInstanceOf(TreeBuilder::class, $treeBuilder);
        $this->assertEquals([
            'title' => 'title',
            'description' => 'description',
            'supported_formats' => ['jsonld'],
            'cache' => false,
            'enable_fos_user' => false,
            'collection' => [
                'order' => null,
                'order_parameter_name' => 'order',
                'pagination' => [
                    'enabled' => true,
                    'client_enabled' => false,
                    'client_items_per_page' => false,
                    'items_per_page' => 30,
                    'page_parameter_name' => 'page',
                    'enabled_parameter_name' => 'pagination',
                    'items_per_page_parameter_name' => 'itemsPerPage',
                ],
            ],
        ], $config);
    }
}
