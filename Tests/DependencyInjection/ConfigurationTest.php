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
use Symfony\Component\Config\Definition\Processor;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    private static $defaultConfig = [
        'title' => 'title',
        'description' => 'description',
        'supported_formats' => ['jsonld'],
        'cache' => false,
        'enable_fos_user' => false,
        'collection' => [
            'filter_name' => [
                'order' => 'order',
            ],
            'order' => null,
            'pagination' => [
                'items_per_page' => [
                    'default' => 30,
                    'client_can_change' => false,
                    'parameter' => 'itemsPerPage',
                ],
                'enabled' => true,
                'client_can_enable' => false,
                'enable_parameter' => 'enablePagination',
                'page_parameter' => 'page',
            ],
        ],
    ];

    public function testDefaultConfig()
    {
        $configuration = new Configuration();
        $treeBuilder = $configuration->getConfigTreeBuilder();
        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, ['dunglas_api' => ['title' => 'title', 'description' => 'description']]);

        $this->assertInstanceOf('Symfony\Component\Config\Definition\ConfigurationInterface', $configuration);
        $this->assertInstanceOf('Symfony\Component\Config\Definition\Builder\TreeBuilder', $treeBuilder);
        $this->assertEquals(self::$defaultConfig, $config);
    }
}
