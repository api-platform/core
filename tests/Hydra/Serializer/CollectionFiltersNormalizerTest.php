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

namespace ApiPlatform\Tests\Hydra\Serializer;

use ApiPlatform\Api\ResourceClassResolverInterface;
use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Hydra\Serializer\CollectionFiltersNormalizer;
use ApiPlatform\Hydra\Serializer\CollectionNormalizer;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Tests\Fixtures\Foo;
use ApiPlatform\Tests\Fixtures\NotAResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class CollectionFiltersNormalizerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @group legacy
     */
    public function testSupportsNormalization(): void
    {
        $decoratedProphecy = $this->prophesize(NormalizerInterface::class);
        $decoratedProphecy->supportsNormalization('foo', 'abc', Argument::type('array'))->willReturn(true)->shouldBeCalled();

        $normalizer = new CollectionFiltersNormalizer(
            $decoratedProphecy->reveal(),
            $this->prophesize(ResourceMetadataCollectionFactoryInterface::class)->reveal(),
            $this->prophesize(ResourceClassResolverInterface::class)->reveal(),
            $this->prophesize(ContainerInterface::class)->reveal(),
        );

        $this->assertTrue($normalizer->supportsNormalization('foo', 'abc'));
    }

    public function testNormalizeNonResourceCollection(): void
    {
        $notAResourceA = new NotAResource('A', 'buzz');
        $notAResourceB = new NotAResource('B', 'bzzt');

        $data = [$notAResourceA, $notAResourceB];

        $normalizedNotAResourceA = [
            'foo' => 'A',
            'bar' => 'buzz',
        ];

        $normalizedNotAResourceB = [
            'foo' => 'B',
            'bar' => 'bzzt',
        ];

        $decoratedProphecy = $this->prophesize(NormalizerInterface::class);
        $decoratedProphecy->normalize($data, CollectionNormalizer::FORMAT, Argument::any())->willReturn([
            $normalizedNotAResourceA,
            $normalizedNotAResourceB,
        ]);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($data, null)->willThrow(InvalidArgumentException::class);

        $filterLocatorProphecy = $this->prophesize(ContainerInterface::class);

        $normalizer = new CollectionFiltersNormalizer($decoratedProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), $resourceClassResolverProphecy->reveal(), $filterLocatorProphecy->reveal());

        $actual = $normalizer->normalize($data, CollectionNormalizer::FORMAT, [
        ]);

        $this->assertEquals([
            $normalizedNotAResourceA,
            $normalizedNotAResourceB,
        ], $actual);
    }

    public function testNormalizeSubLevelResourceCollection(): void
    {
        $fooOne = new Foo();
        $fooOne->id = 1;
        $fooOne->bar = 'baz';

        $fooThree = new Foo();
        $fooThree->id = 3;
        $fooThree->bar = 'bzz';

        $data = [$fooOne, $fooThree];

        $normalizedFooOne = [
            '@id' => '/foos/1',
            '@type' => 'Foo',
            'bar' => 'baz',
        ];

        $normalizedFooThree = [
            '@id' => '/foos/3',
            '@type' => 'Foo',
            'bar' => 'bzz',
        ];

        $decoratedProphecy = $this->prophesize(NormalizerInterface::class);
        $decoratedProphecy->normalize($data, CollectionNormalizer::FORMAT, Argument::allOf(
            Argument::withEntry('resource_class', Foo::class),
            Argument::withEntry('api_sub_level', true)
        ))->willReturn([
            $normalizedFooOne,
            $normalizedFooThree,
        ]);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);

        $filterLocatorProphecy = $this->prophesize(ContainerInterface::class);

        $normalizer = new CollectionFiltersNormalizer($decoratedProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), $resourceClassResolverProphecy->reveal(), $filterLocatorProphecy->reveal());

        $actual = $normalizer->normalize($data, CollectionNormalizer::FORMAT, [
            'operation_name' => 'get',
            'resource_class' => Foo::class,
            'api_sub_level' => true,
        ]);

        $this->assertEquals([
            $normalizedFooOne,
            $normalizedFooThree,
        ], $actual);
    }

    public function testNormalizeSubLevelNonResourceCollection(): void
    {
        $notAResourceA = new NotAResource('A', 'buzz');
        $notAResourceB = new NotAResource('B', 'bzzt');

        $data = [$notAResourceA, $notAResourceB];

        $normalizedNotAResourceA = [
            'foo' => 'A',
            'bar' => 'buzz',
        ];

        $normalizedNotAResourceB = [
            'foo' => 'B',
            'bar' => 'bzzt',
        ];

        $decoratedProphecy = $this->prophesize(NormalizerInterface::class);
        $decoratedProphecy->normalize($data, CollectionNormalizer::FORMAT, Argument::any())->willReturn([
            $normalizedNotAResourceA,
            $normalizedNotAResourceB,
        ]);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($data, null)->willThrow(InvalidArgumentException::class);

        $filterLocatorProphecy = $this->prophesize(ContainerInterface::class);

        $normalizer = new CollectionFiltersNormalizer($decoratedProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), $resourceClassResolverProphecy->reveal(), $filterLocatorProphecy->reveal());

        $actual = $normalizer->normalize($data, CollectionNormalizer::FORMAT, [
            'api_sub_level' => true,
        ]);

        $this->assertEquals([
            $normalizedNotAResourceA,
            $normalizedNotAResourceB,
        ], $actual);
    }

    public function testDoNothingIfNoFilter(): void
    {
        $dummy = new Dummy();

        $decoratedProphecy = $this->prophesize(NormalizerInterface::class);
        $decoratedProphecy->normalize($dummy, CollectionNormalizer::FORMAT, [
            'operation_name' => 'get',
            'resource_class' => Dummy::class,
        ])->willReturn(['name' => 'foo']);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource(Dummy::class))
                ->withShortName('Dummy')
                ->withOperations(new Operations([
                    'get' => (new GetCollection())->withShortName('Dummy'),
                ])),
        ]));

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, Dummy::class)->willReturn(Dummy::class);

        $normalizer = new CollectionFiltersNormalizer(
            $decoratedProphecy->reveal(),
            $resourceMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $this->prophesize(ContainerInterface::class)->reveal()
        );

        $this->assertEquals(['name' => 'foo'], $normalizer->normalize($dummy, CollectionNormalizer::FORMAT, [
            'operation_name' => 'get',
            'resource_class' => Dummy::class,
        ]));
    }

    public function testDoNothingIfNoRequestUri(): void
    {
        $dummy = new Dummy();

        $decoratedProphecy = $this->prophesize(NormalizerInterface::class);
        $decoratedProphecy->normalize($dummy, CollectionNormalizer::FORMAT, [
            'resource_class' => Dummy::class,
            'operation_name' => 'get',
        ])->willReturn(['name' => 'foo']);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource(Dummy::class))
                ->withShortName('Dummy')
                ->withOperations(new Operations([
                    'get' => (new GetCollection())->withShortName('Dummy')->withFilters(['foo']),
                ])),
        ]));

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, Dummy::class)->willReturn(Dummy::class);

        $filterLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $filterLocatorProphecy->has('foo')->willReturn(false);

        $normalizer = new CollectionFiltersNormalizer(
            $decoratedProphecy->reveal(),
            $resourceMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $filterLocatorProphecy->reveal()
        );

        $this->assertEquals(['name' => 'foo'], $normalizer->normalize($dummy, CollectionNormalizer::FORMAT, [
            'resource_class' => Dummy::class,
            'operation_name' => 'get',
        ]));
    }

    public function testNormalize(): void
    {
        $filterProphecy = $this->prophesize(FilterInterface::class);
        $filterProphecy->getDescription(Dummy::class)->willReturn(['a' => ['property' => 'name', 'required' => true]])->shouldBeCalled();

        $filterLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $filterLocatorProphecy->has('foo')->willReturn(true)->shouldBeCalled();
        $filterLocatorProphecy->get('foo')->willReturn($filterProphecy->reveal())->shouldBeCalled();

        $dummy = new Dummy();

        $decoratedProphecy = $this->prophesize(NormalizerInterface::class);
        $decoratedProphecy->normalize($dummy, CollectionNormalizer::FORMAT, [
            'request_uri' => '/foo?bar=baz',
            'resource_class' => Dummy::class,
            'operation_name' => 'get',
        ])->willReturn(['name' => 'foo']);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource(Dummy::class))
                ->withShortName('Dummy')
                ->withOperations(new Operations([
                    'get' => (new GetCollection())->withShortName('Dummy')->withFilters(['foo']),
                ])),
        ]));

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, Dummy::class)->willReturn(Dummy::class);

        $normalizer = new CollectionFiltersNormalizer(
            $decoratedProphecy->reveal(),
            $resourceMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $filterLocatorProphecy->reveal()
        );

        $this->assertEquals([
            'name' => 'foo',
            'hydra:search' => [
                '@type' => 'hydra:IriTemplate',
                'hydra:template' => '/foo{?a}',
                'hydra:variableRepresentation' => 'BasicRepresentation',
                'hydra:mapping' => [
                    [
                        '@type' => 'IriTemplateMapping',
                        'variable' => 'a',
                        'property' => 'name',
                        'required' => true,
                    ],
                ],
            ],
        ], $normalizer->normalize($dummy, CollectionNormalizer::FORMAT, [
            'request_uri' => '/foo?bar=baz',
            'resource_class' => Dummy::class,
            'operation_name' => 'get',
        ]));
    }

    public function testGetSupportedTypes(): void
    {
        if (!method_exists(Serializer::class, 'getSupportedTypes')) {
            $this->markTestSkipped('Symfony Serializer < 6.3');
        }

        // TODO: use prophecy when getSupportedTypes() will be added to the interface
        $normalizer = new CollectionFiltersNormalizer(
            new class() implements NormalizerInterface {
                public function normalize(mixed $object, ?string $format = null, array $context = []): \ArrayObject|array|string|int|float|bool|null
                {
                    return null;
                }

                public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
                {
                    return true;
                }

                public function getSupportedTypes(?string $format): array
                {
                    return ['*' => true];
                }
            },
            $this->prophesize(ResourceMetadataCollectionFactoryInterface::class)->reveal(),
            $this->prophesize(ResourceClassResolverInterface::class)->reveal(),
            $this->prophesize(ContainerInterface::class)->reveal(),
        );

        $this->assertSame(['*' => true], $normalizer->getSupportedTypes('jsonld'));
    }
}
