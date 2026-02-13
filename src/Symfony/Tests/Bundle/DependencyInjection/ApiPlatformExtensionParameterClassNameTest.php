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

namespace ApiPlatform\Symfony\Tests\Bundle\DependencyInjection;

use ApiPlatform\Metadata\HeaderParameter;
use ApiPlatform\Metadata\Parameters;
// use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Symfony\Bundle\DependencyInjection\ApiPlatformExtension;
use ApiPlatform\Tests\Fixtures\TestBundle\TestBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * Test that parameters can be defined using Parameter class names as keys.
 *
 * Example:
 * ```yaml
 * defaults:
 *     parameters:
 *         'ApiPlatform\Metadata\HeaderParameter':
 *             key: 'X-Api-Version'
 *             required: true
 * ```
 */
class ApiPlatformExtensionParameterClassNameTest extends TestCase
{
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $containerParameterBag = new ParameterBag([
            'kernel.bundles' => [
                'DoctrineBundle' => DoctrineBundle::class,
                'SecurityBundle' => SecurityBundle::class,
                'TwigBundle' => TwigBundle::class,
            ],
            'kernel.bundles_metadata' => [
                'TestBundle' => [
                    'parent' => null,
                    'path' => realpath(__DIR__.'/../../../Fixtures/TestBundle'),
                    'namespace' => TestBundle::class,
                ],
            ],
            'kernel.project_dir' => __DIR__.'/../../../Fixtures/app',
            'kernel.debug' => false,
            'kernel.environment' => 'test',
        ]);

        $this->container = new ContainerBuilder($containerParameterBag);
    }

    public function testParametersWithClassNameAsKey(): void
    {
        $config = [
            'api_platform' => [
                'title' => 'Test API',
                'description' => 'Test Description',
                'version' => '1.0.0',
                'formats' => ['json' => ['mime_types' => ['application/json']]],
                'error_formats' => [],
                'patch_formats' => [],
                'defaults' => [
                    'parameters' => [
                        'ApiPlatform\Metadata\HeaderParameter' => [
                            'key' => 'X-Api-Version',
                            'required' => true,
                            'description' => 'API Version',
                        ],
                        // 'ApiPlatform\Metadata\QueryParameter' => [
                        //     'key' => 'q',
                        //     'description' => 'Search query',
                        // ],
                    ],
                ],
            ],
        ];

        (new ApiPlatformExtension())->load($config, $this->container);

        $defaults = $this->container->getParameter('api_platform.defaults');
        $this->assertArrayHasKey('parameters', $defaults);

        /** @var Parameters $parameters */
        $parameters = $defaults['parameters'];
        $this->assertInstanceOf(Parameters::class, $parameters);

        $paramArray = iterator_to_array($parameters);
        $this->assertNotEmpty($paramArray);

        $this->assertArrayHasKey('X-Api-Version', $paramArray);
        $headerParam = $paramArray['X-Api-Version'];
        $this->assertInstanceOf(HeaderParameter::class, $headerParam);
        $this->assertTrue($headerParam->getRequired());

        // $this->assertArrayHasKey('q', $paramArray);
        // $queryParam = $paramArray['q'];
        // $this->assertInstanceOf(QueryParameter::class, $queryParam);
    }

    public function testMixedParameterDefinitions(): void
    {
        $config = [
            'api_platform' => [
                'title' => 'Test API',
                'description' => 'Test Description',
                'version' => '1.0.0',
                'formats' => ['json' => ['mime_types' => ['application/json']]],
                'error_formats' => [],
                'patch_formats' => [],
                'defaults' => [
                    'parameters' => [
                        'ApiPlatform\Metadata\HeaderParameter' => [
                            'key' => 'X-Api-Version',
                            'required' => true,
                        ],
                        // 'ApiPlatform\Metadata\QueryParameter' => [
                        //     'key' => 'q',
                        //     'description' => 'Search query',
                        // ],
                    ],
                ],
            ],
        ];

        (new ApiPlatformExtension())->load($config, $this->container);

        $defaults = $this->container->getParameter('api_platform.defaults');
        /** @var Parameters $parameters */
        $parameters = $defaults['parameters'];

        $paramArray = iterator_to_array($parameters);

        $this->assertArrayHasKey('X-Api-Version', $paramArray);
        $this->assertInstanceOf(HeaderParameter::class, $paramArray['X-Api-Version']);

        // $this->assertArrayHasKey('q', $paramArray);
        // $this->assertInstanceOf(QueryParameter::class, $paramArray['q']);
    }

    public function testMultipleHeaderParameters(): void
    {
        $config = [
            'api_platform' => [
                'title' => 'Test API',
                'description' => 'Test Description',
                'version' => '1.0.0',
                'formats' => ['json' => ['mime_types' => ['application/json']]],
                'error_formats' => [],
                'patch_formats' => [],
                'defaults' => [
                    'parameters' => [
                        'ApiPlatform\Metadata\HeaderParameter' => [
                            'key' => 'X-Api-Version',
                            'required' => true,
                        ],
                    ],
                ],
            ],
        ];

        (new ApiPlatformExtension())->load($config, $this->container);

        $defaults = $this->container->getParameter('api_platform.defaults');
        /** @var Parameters $parameters */
        $parameters = $defaults['parameters'];

        $paramArray = iterator_to_array($parameters);
        $this->assertNotEmpty($paramArray);
    }
}
