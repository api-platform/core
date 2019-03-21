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

namespace ApiPlatform\Core\Tests\Metadata\Extractor;

use ApiPlatform\Core\Metadata\Extractor\ExtractorInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface as SymfonyContainerInterface;
use function is_a;

/**
 * @author Théo Fidry <theo.fidry@gmail.com>
 */
abstract class ExtractorTestCase extends TestCase
{
    protected $extractorClass;

    final public function testEmptyResources()
    {
        $resources = $this->createExtractor([$this->getEmptyResourcesFile()])->getResources();

        $this->assertEmpty($resources);
    }

    final public function testEmptyOperation()
    {
        $resources = $this->createExtractor([$this->getEmptyOperationFile()])->getResources();

        $this->assertSame(['filters' => ['greeting.search_filter']], $resources['App\Entity\Greeting']['collectionOperations']['get']);
        // There is a difference between XML & YAML here for example, one will parse `null` or the lack of value as `null`
        // whilst the other will parse it as an empty array. Since it doesn't affect the processing of those values, there is no
        // real need to fix this.
        $this->assertSame([], $resources['App\Entity\Greeting']['collectionOperations']['post']);
        $this->assertSame(['get' => [], 'put' => []], $resources['App\Entity\Greeting']['itemOperations']);
    }

    final public function testCorrectResources()
    {
        $resources = $this->createExtractor([$this->getCorrectResourceFile()])->getResources();

        $this->assertSame([
            \ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy::class => [
                'shortName' => null,
                'description' => null,
                'iri' => null,
                'itemOperations' => null,
                'collectionOperations' => null,
                'subresourceOperations' => null,
                'graphql' => null,
                'attributes' => null,
                'properties' => null,
            ],
            \ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\FileConfigDummy::class => [
                'shortName' => 'thedummyshortname',
                'description' => 'Dummy resource',
                'iri' => 'someirischema',
                'itemOperations' => [
                    'my_op_name' => [
                        'method' => 'GET',
                    ],
                    'my_other_op_name' => [
                        'method' => 'POST',
                    ],
                ],
                'collectionOperations' => [
                    'my_collection_op' => [
                        'method' => 'POST',
                        'path' => 'the/collection/path',
                    ],
                ],
                'subresourceOperations' => [
                    'my_collection_subresource' => [
                        'path' => 'the/subresource/path',
                    ],
                ],
                'graphql' => [
                    'query' => [
                        'normalization_context' => [
                            'groups' => [
                                'graphql',
                            ],
                        ],
                    ],
                ],
                'attributes' => [
                    'normalization_context' => [
                        'groups' => [
                            'default',
                        ],
                    ],
                    'denormalization_context' => [
                        'groups' => [
                            'default',
                        ],
                    ],
                    'hydra_context' => [
                        '@type' => 'hydra:Operation',
                        '@hydra:title' => 'File config Dummy',
                    ],
                ],
                'properties' => [
                    'foo' => [
                        'description' => 'The dummy foo',
                        'readable' => true,
                        'writable' => true,
                        'readableLink' => false,
                        'writableLink' => false,
                        'required' => true,
                        'identifier' => null,
                        'iri' => null,
                        'attributes' => [
                            'foo' => [
                                'Foo',
                            ],
                            'bar' => [
                                [
                                    'Bar',
                                ],
                                'baz' => 'Baz',
                            ],
                            'baz' => 'Baz',
                        ],
                        'subresource' => [
                            'collection' => true,
                            'resourceClass' => 'Foo',
                            'maxDepth' => 1,
                        ],
                    ],
                    'name' => [
                        'description' => 'The dummy name',
                        'readable' => null,
                        'writable' => null,
                        'readableLink' => null,
                        'writableLink' => null,
                        'required' => null,
                        'identifier' => null,
                        'iri' => null,
                        'attributes' => [],
                        'subresource' => null,
                    ],
                ],
            ],
        ], $resources);
    }

    final public function testResourcesParamatersResolution()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get('dummy_class')->willReturn(\ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy::class);
        $containerProphecy->get('file_config_dummy_class')->willReturn(\ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\FileConfigDummy::class);

        $resources = $this->createExtractor([$this->getResourceWithParametersFile()], $containerProphecy->reveal())->getResources();

        $this->assertSame([
            \ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy::class => [
                'shortName' => null,
                'description' => null,
                'iri' => null,
                'itemOperations' => null,
                'collectionOperations' => null,
                'subresourceOperations' => null,
                'graphql' => null,
                'attributes' => null,
                'properties' => null,
            ],
            '\ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyBis' => [
                'shortName' => null,
                'description' => null,
                'iri' => null,
                'itemOperations' => null,
                'collectionOperations' => null,
                'subresourceOperations' => null,
                'graphql' => null,
                'attributes' => null,
                'properties' => null,
            ],
            \ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\FileConfigDummy::class => [
                'shortName' => 'thedummyshortname',
                'description' => 'Dummy resource',
                'iri' => 'someirischema',
                'itemOperations' => [
                    'my_op_name' => [
                        'method' => 'GET',
                    ],
                    'my_other_op_name' => [
                        'method' => 'POST',
                    ],
                ],
                'collectionOperations' => [
                    'my_collection_op' => [
                        'method' => 'POST',
                        'path' => 'the/collection/path',
                    ],
                ],
                'subresourceOperations' => [
                    'my_collection_subresource' => [
                        'path' => 'the/subresource/path',
                    ],
                ],
                'graphql' => [
                    'query' => [
                        'normalization_context' => [
                            'groups' => [
                                'graphql',
                            ],
                        ],
                    ],
                ],
                'attributes' => [
                    'normalization_context' => [
                        'groups' => [
                            'default',
                        ],
                    ],
                    'denormalization_context' => [
                        'groups' => [
                            'default',
                        ],
                    ],
                    'hydra_context' => [
                        '@type' => 'hydra:Operation',
                        '@hydra:title' => 'File config Dummy',
                    ],
                ],
                'properties' => [
                    'foo' => [
                        'description' => 'The dummy foo',
                        'readable' => true,
                        'writable' => true,
                        'readableLink' => false,
                        'writableLink' => false,
                        'required' => true,
                        'identifier' => null,
                        'iri' => null,
                        'attributes' => [
                            'foo' => [
                                'Foo',
                            ],
                            'bar' => [
                                [
                                    'Bar',
                                ],
                                'baz' => 'Baz',
                            ],
                            'baz' => 'Baz',
                        ],
                        'subresource' => [
                            'collection' => true,
                            'resourceClass' => 'Foo',
                            'maxDepth' => 1,
                        ],
                    ],
                    'name' => [
                        'description' => 'The dummy name',
                        'readable' => null,
                        'writable' => null,
                        'readableLink' => null,
                        'writableLink' => null,
                        'required' => null,
                        'identifier' => null,
                        'iri' => null,
                        'attributes' => [],
                        'subresource' => null,
                    ],
                ],
            ],
        ], $resources);

        $containerProphecy->get(Argument::cetera())->shouldHaveBeenCalledTimes(2);
    }

    final public function testResourcesParamatersResolutionWithTheSymfonyContainer()
    {
        $containerProphecy = $this->prophesize(SymfonyContainerInterface::class);
        $containerProphecy->getParameter('dummy_class')->willReturn(\ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy::class);
        $containerProphecy->getParameter('file_config_dummy_class')->willReturn(\ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\FileConfigDummy::class);

        $resources = $this->createExtractor([$this->getResourceWithParametersFile()], $containerProphecy->reveal())->getResources();

        $this->assertSame([
            \ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy::class => [
                'shortName' => null,
                'description' => null,
                'iri' => null,
                'itemOperations' => null,
                'collectionOperations' => null,
                'subresourceOperations' => null,
                'graphql' => null,
                'attributes' => null,
                'properties' => null,
            ],
            '\ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyBis' => [
                'shortName' => null,
                'description' => null,
                'iri' => null,
                'itemOperations' => null,
                'collectionOperations' => null,
                'subresourceOperations' => null,
                'graphql' => null,
                'attributes' => null,
                'properties' => null,
            ],
            \ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\FileConfigDummy::class => [
                'shortName' => 'thedummyshortname',
                'description' => 'Dummy resource',
                'iri' => 'someirischema',
                'itemOperations' => [
                    'my_op_name' => [
                        'method' => 'GET',
                    ],
                    'my_other_op_name' => [
                        'method' => 'POST',
                    ],
                ],
                'collectionOperations' => [
                    'my_collection_op' => [
                        'method' => 'POST',
                        'path' => 'the/collection/path',
                    ],
                ],
                'subresourceOperations' => [
                    'my_collection_subresource' => [
                        'path' => 'the/subresource/path',
                    ],
                ],
                'graphql' => [
                    'query' => [
                        'normalization_context' => [
                            'groups' => [
                                'graphql',
                            ],
                        ],
                    ],
                ],
                'attributes' => [
                    'normalization_context' => [
                        'groups' => [
                            'default',
                        ],
                    ],
                    'denormalization_context' => [
                        'groups' => [
                            'default',
                        ],
                    ],
                    'hydra_context' => [
                        '@type' => 'hydra:Operation',
                        '@hydra:title' => 'File config Dummy',
                    ],
                ],
                'properties' => [
                    'foo' => [
                        'description' => 'The dummy foo',
                        'readable' => true,
                        'writable' => true,
                        'readableLink' => false,
                        'writableLink' => false,
                        'required' => true,
                        'identifier' => null,
                        'iri' => null,
                        'attributes' => [
                            'foo' => [
                                'Foo',
                            ],
                            'bar' => [
                                [
                                    'Bar',
                                ],
                                'baz' => 'Baz',
                            ],
                            'baz' => 'Baz',
                        ],
                        'subresource' => [
                            'collection' => true,
                            'resourceClass' => 'Foo',
                            'maxDepth' => 1,
                        ],
                    ],
                    'name' => [
                        'description' => 'The dummy name',
                        'readable' => null,
                        'writable' => null,
                        'readableLink' => null,
                        'writableLink' => null,
                        'required' => null,
                        'identifier' => null,
                        'iri' => null,
                        'attributes' => [],
                        'subresource' => null,
                    ],
                ],
            ],
        ], $resources);

        $containerProphecy->getParameter(Argument::cetera())->shouldHaveBeenCalledTimes(2);
    }

    final public function testResourcesParamatersResolutionWithoutAContainer()
    {
        $resources = $this->createExtractor([$this->getResourceWithParametersFile()])->getResources();

        $this->assertSame([
            '%dummy_class%' => [
                'shortName' => null,
                'description' => null,
                'iri' => null,
                'itemOperations' => null,
                'collectionOperations' => null,
                'subresourceOperations' => null,
                'graphql' => null,
                'attributes' => null,
                'properties' => null,
            ],
            '%dummy_class%Bis' => [
                'shortName' => null,
                'description' => null,
                'iri' => null,
                'itemOperations' => null,
                'collectionOperations' => null,
                'subresourceOperations' => null,
                'graphql' => null,
                'attributes' => null,
                'properties' => null,
            ],
            '%file_config_dummy_class%' => [
                'shortName' => 'thedummyshortname',
                'description' => 'Dummy resource',
                'iri' => 'someirischema',
                'itemOperations' => [
                    'my_op_name' => [
                        'method' => 'GET',
                    ],
                    'my_other_op_name' => [
                        'method' => 'POST',
                    ],
                ],
                'collectionOperations' => [
                    'my_collection_op' => [
                        'method' => 'POST',
                        'path' => 'the/collection/path',
                    ],
                ],
                'subresourceOperations' => [
                    'my_collection_subresource' => [
                        'path' => 'the/subresource/path',
                    ],
                ],
                'graphql' => [
                    'query' => [
                        'normalization_context' => [
                            'groups' => [
                                'graphql',
                            ],
                        ],
                    ],
                ],
                'attributes' => [
                    'normalization_context' => [
                        'groups' => [
                            'default',
                        ],
                    ],
                    'denormalization_context' => [
                        'groups' => [
                            'default',
                        ],
                    ],
                    'hydra_context' => [
                        '@type' => 'hydra:Operation',
                        '@hydra:title' => 'File config Dummy',
                    ],
                ],
                'properties' => [
                    'foo' => [
                        'description' => 'The dummy foo',
                        'readable' => true,
                        'writable' => true,
                        'readableLink' => false,
                        'writableLink' => false,
                        'required' => true,
                        'identifier' => null,
                        'iri' => null,
                        'attributes' => [
                            'foo' => [
                                'Foo',
                            ],
                            'bar' => [
                                [
                                    'Bar',
                                ],
                                'baz' => 'Baz',
                            ],
                            'baz' => 'Baz',
                        ],
                        'subresource' => [
                            'collection' => true,
                            'resourceClass' => 'Foo',
                            'maxDepth' => 1,
                        ],
                    ],
                    'name' => [
                        'description' => 'The dummy name',
                        'readable' => null,
                        'writable' => null,
                        'readableLink' => null,
                        'writableLink' => null,
                        'required' => null,
                        'identifier' => null,
                        'iri' => null,
                        'attributes' => [],
                        'subresource' => null,
                    ],
                ],
            ],
        ], $resources);
    }

    /**
     * @param string[] $paths
     */
    final protected function createExtractor(array $paths, ContainerInterface $container = null): ExtractorInterface
    {
        $extractorClass = $this->getExtractorClass();

        $this->assertTrue(is_a($extractorClass, ExtractorInterface::class, true));

        return new $extractorClass($paths, $container);
    }

    abstract protected function getExtractorClass(): string;

    abstract protected function getEmptyResourcesFile(): string;

    abstract protected function getEmptyOperationFile(): string;

    abstract protected function getCorrectResourceFile(): string;

    abstract protected function getResourceWithParametersFile(): string;
}
