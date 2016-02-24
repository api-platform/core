<?php

/*
 * This file is part of the API Platform project.
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
        'cache' => false,
        'enable_doctrine_orm' => true,
        'enable_fos_user' => false,
        'collection' => [
            'filter_name' => [
                'order' => 'order',
            ],
            'order' => null,
            'pagination' => [
                'page_parameter_name' => 'page',
                'items_per_page' => [
                    'number' => 30,
                    'enable_client_request' => false,
                    'parameter_name' => 'itemsPerPage',
                ],
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
