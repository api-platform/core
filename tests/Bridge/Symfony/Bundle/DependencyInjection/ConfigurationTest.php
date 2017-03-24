<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Bundle\DependencyInjection;

use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Configuration;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var Processor
     */
    private $processor;

    public function setUp()
    {
        $this->configuration = new Configuration();
        $this->processor = new Processor();
    }

    public function testDefaultConfig()
    {
        $treeBuilder = $this->configuration->getConfigTreeBuilder();
        $config = $this->processor->processConfiguration($this->configuration, ['api_platform' => ['title' => 'title', 'description' => 'description', 'version' => '1.0.0']]);

        $this->assertInstanceOf(ConfigurationInterface::class, $this->configuration);
        $this->assertInstanceOf(TreeBuilder::class, $treeBuilder);
        $this->assertEquals([
            'title' => 'title',
            'description' => 'description',
            'version' => '1.0.0',
            'formats' => [
                'jsonld' => ['mime_types' => ['application/ld+json']],
                'json' => ['mime_types' => ['application/json']],
                'html' => ['mime_types' => ['text/html']],
            ],
            'error_formats' => [
                'jsonproblem' => ['mime_types' => ['application/problem+json']],
                'jsonld' => ['mime_types' => ['application/ld+json']],
            ],
            'exception_to_status' => [
                ExceptionInterface::class => Response::HTTP_BAD_REQUEST,
                InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
            ],
            'default_operation_path_resolver' => 'api_platform.operation_path_resolver.underscore',
            'name_converter' => null,
            'enable_fos_user' => false,
            'enable_nelmio_api_doc' => false,
            'enable_swagger' => true,
            'enable_swagger_ui' => true,
            'eager_loading' => [
                'enabled' => true,
                'max_joins' => 30,
                'force_eager' => true,
            ],
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
                    'maximum_items_per_page' => null,
                ],
            ],
        ], $config);
    }

    public function testExceptionToStatusConfig()
    {
        $config = $this->processor->processConfiguration($this->configuration, [
            'api_platform' => [
                'exception_to_status' => [
                    \InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
                    \RuntimeException::class => 'HTTP_INTERNAL_SERVER_ERROR',
                ],
            ],
        ]);

        $this->assertTrue(isset($config['exception_to_status']));
        $this->assertSame([
            \InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
            \RuntimeException::class => Response::HTTP_INTERNAL_SERVER_ERROR,
        ], $config['exception_to_status']);
    }

    public function invalidHttpStatusCodeProvider()
    {
        return [
            [0],
            [99],
            [700],
            [1000],
        ];
    }

    /**
     * @dataProvider invalidHttpStatusCodeProvider
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessageRegExp /The HTTP status code ".+" is not valid\./
     */
    public function testExceptionToStatusConfigWithInvalidHttpStatusCode($invalidHttpStatusCode)
    {
        $this->processor->processConfiguration($this->configuration, [
            'api_platform' => [
                'exception_to_status' => [
                    \Exception::class => $invalidHttpStatusCode,
                ],
            ],
        ]);
    }

    public function invalidHttpStatusCodeValueProvider()
    {
        return [
            [true],
            [null],
            [-INF],
            [40.4],
            ['foo'],
            ['HTTP_FOO_BAR'],
        ];
    }

    /**
     * @dataProvider invalidHttpStatusCodeValueProvider
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidTypeException
     * @expectedExceptionMessageRegExp /Invalid type for path "api_platform\.exception_to_status\.Exception". Expected int, but got .+\./
     */
    public function testExceptionToStatusConfigWithInvalidHttpStatusCodeValue($invalidHttpStatusCodeValue)
    {
        $this->processor->processConfiguration($this->configuration, [
            'api_platform' => [
                'exception_to_status' => [
                    \Exception::class => $invalidHttpStatusCodeValue,
                ],
            ],
        ]);
    }
}
