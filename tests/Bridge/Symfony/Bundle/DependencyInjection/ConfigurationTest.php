<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Symfony\Bridge\Bundle\DependencyInjection;

use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Configuration;
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
        $config = $processor->processConfiguration($configuration, ['api_platform' => ['title' => 'title', 'description' => 'description', 'version' => '1.0.0']]);

        $this->assertInstanceOf(ConfigurationInterface::class, $configuration);
        $this->assertInstanceOf(TreeBuilder::class, $treeBuilder);
        $this->assertEquals([
            'title' => 'title',
            'description' => 'description',
            'version' => '1.0.0',
            'formats' => ['jsonld' => ['mime_types' => ['application/ld+json']]],
            'naming' => [
                'resource_path_naming_strategy' => 'api_platform.naming.resource_path_naming_strategy.underscore',
            ],
            'name_converter' => null,
            'enable_fos_user' => false,
            'enable_nelmio_api_doc' => true,
            'enable_swagger' => true,
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
