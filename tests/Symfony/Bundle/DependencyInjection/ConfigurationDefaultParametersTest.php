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

namespace ApiPlatform\Tests\Symfony\Bundle\DependencyInjection;

use ApiPlatform\Symfony\Bundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

/**
 * Tests the defaults.parameters configuration option.
 *
 * @author Maxence Castel <maxence.castel59@gmail.com>
 */
final class ConfigurationDefaultParametersTest extends TestCase
{
    private Configuration $configuration;

    private Processor $processor;

    protected function setUp(): void
    {
        $this->configuration = new Configuration();
        $this->processor = new Processor();
    }

    public function testDefaultHeaderParameterConfiguration(): void
    {
        $config = $this->processor->processConfiguration($this->configuration, [
            'api_platform' => [
                'defaults' => [
                    'parameters' => [
                        'ApiPlatform\Metadata\HeaderParameter' => [
                            'key' => 'X-API-Version',
                            'required' => true,
                            'description' => 'API Version',
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertIsArray($config['defaults']['parameters']);
        $this->assertArrayHasKey('ApiPlatform\Metadata\HeaderParameter', $config['defaults']['parameters']);
        $this->assertSame('X-API-Version', $config['defaults']['parameters']['ApiPlatform\Metadata\HeaderParameter']['key']);
        $this->assertTrue($config['defaults']['parameters']['ApiPlatform\Metadata\HeaderParameter']['required']);
        $this->assertSame('API Version', $config['defaults']['parameters']['ApiPlatform\Metadata\HeaderParameter']['description']);
    }

    public function testMultipleDefaultParametersConfiguration(): void
    {
        $config = $this->processor->processConfiguration($this->configuration, [
            'api_platform' => [
                'defaults' => [
                    'parameters' => [
                        'ApiPlatform\Metadata\HeaderParameter' => [
                            'key' => 'X-API-Version',
                            'required' => true,
                        ],
                        'ApiPlatform\Metadata\QueryParameter' => [
                            'key' => 'sort',
                            'required' => false,
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertCount(2, $config['defaults']['parameters']);
        $this->assertArrayHasKey('ApiPlatform\Metadata\HeaderParameter', $config['defaults']['parameters']);
        $this->assertArrayHasKey('ApiPlatform\Metadata\QueryParameter', $config['defaults']['parameters']);
    }

    public function testDefaultParametersWithAllOptions(): void
    {
        $config = $this->processor->processConfiguration($this->configuration, [
            'api_platform' => [
                'defaults' => [
                    'parameters' => [
                        'ApiPlatform\Metadata\HeaderParameter' => [
                            'key' => 'X-API-Version',
                            'required' => true,
                            'description' => 'API Version',
                            'property' => 'version',
                            'default' => '1.0',
                            'filter' => 'api_platform.filter.version',
                            'priority' => 10,
                            'hydra' => true,
                            'constraints' => ['NotNull'],
                            'security' => 'is_granted("ROLE_ADMIN")',
                            'security_message' => 'Access denied',
                        ],
                    ],
                ],
            ],
        ]);

        $params = $config['defaults']['parameters']['ApiPlatform\Metadata\HeaderParameter'];
        $this->assertSame('X-API-Version', $params['key']);
        $this->assertTrue($params['required']);
        $this->assertSame('API Version', $params['description']);
        $this->assertSame('version', $params['property']);
        $this->assertSame('1.0', $params['default']);
        $this->assertSame('api_platform.filter.version', $params['filter']);
        $this->assertSame(10, $params['priority']);
        $this->assertTrue($params['hydra']);
        $this->assertSame(['NotNull'], $params['constraints']);
        $this->assertSame('is_granted("ROLE_ADMIN")', $params['security']);
        $this->assertSame('Access denied', $params['security_message']);
    }

    public function testEmptyDefaultParameters(): void
    {
        $config = $this->processor->processConfiguration($this->configuration, [
            'api_platform' => [
                'defaults' => [
                    'parameters' => [],
                ],
            ],
        ]);

        $this->assertIsArray($config['defaults']['parameters']);
        $this->assertEmpty($config['defaults']['parameters']);
    }

    public function testDefaultParametersDoesNotAffectOtherDefaults(): void
    {
        $config = $this->processor->processConfiguration($this->configuration, [
            'api_platform' => [
                'defaults' => [
                    'parameters' => [
                        'ApiPlatform\Metadata\HeaderParameter' => [
                            'key' => 'X-API-Version',
                            'required' => true,
                        ],
                    ],
                    'pagination_items_per_page' => 50,
                ],
            ],
        ]);

        $this->assertSame(50, $config['defaults']['pagination_items_per_page']);
        $this->assertArrayHasKey('ApiPlatform\Metadata\HeaderParameter', $config['defaults']['parameters']);
    }
}
