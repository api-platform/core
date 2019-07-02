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

namespace ApiPlatform\Core\Tests\Hydra\Serializer;

use ApiPlatform\Core\Api\FilterCollection;
use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Hydra\Serializer\CollectionFiltersNormalizer;
use ApiPlatform\Core\Hydra\Serializer\CollectionNormalizer;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\Foo;
use ApiPlatform\Core\Tests\Fixtures\NotAResource;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class CollectionFiltersNormalizerTest extends TestCase
{
    public function testSupportsNormalization()
    {
        $decoratedProphecy = $this->prophesize(NormalizerInterface::class);
        $decoratedProphecy->willImplement(CacheableSupportsMethodInterface::class);
        $decoratedProphecy->supportsNormalization('foo', 'abc')->willReturn(true)->shouldBeCalled();
        $decoratedProphecy->hasCacheableSupportsMethod()->willReturn(true)->shouldBeCalled();

        $normalizer = new CollectionFiltersNormalizer(
            $decoratedProphecy->reveal(),
            $this->prophesize(ResourceMetadataFactoryInterface::class)->reveal(),
            $this->prophesize(ResourceClassResolverInterface::class)->reveal(),
            $this->prophesize(ContainerInterface::class)->reveal()
        );

        $this->assertTrue($normalizer->supportsNormalization('foo', 'abc'));
        $this->assertTrue($normalizer->hasCacheableSupportsMethod());
    }

    public function testNormalizeNonResourceCollection()
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

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);

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

    public function testNormalizeSubLevelResourceCollection()
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

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);

        $filterLocatorProphecy = $this->prophesize(ContainerInterface::class);

        $normalizer = new CollectionFiltersNormalizer($decoratedProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), $resourceClassResolverProphecy->reveal(), $filterLocatorProphecy->reveal());

        $actual = $normalizer->normalize($data, CollectionNormalizer::FORMAT, [
            'collection_operation_name' => 'get',
            'operation_type' => OperationType::COLLECTION,
            'resource_class' => Foo::class,
            'api_sub_level' => true,
        ]);

        $this->assertEquals([
            $normalizedFooOne,
            $normalizedFooThree,
        ], $actual);
    }

    public function testNormalizeSubLevelNonResourceCollection()
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

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);

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

    public function testDoNothingIfNoFilter()
    {
        $dummy = new Dummy();

        $decoratedProphecy = $this->prophesize(NormalizerInterface::class);
        $decoratedProphecy->normalize($dummy, CollectionNormalizer::FORMAT, [
            'collection_operation_name' => 'get',
            'resource_class' => Dummy::class,
        ])->willReturn(['name' => 'foo']);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata('foo', '', null, [], ['get' => []]));

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, Dummy::class)->willReturn(Dummy::class);

        $normalizer = new CollectionFiltersNormalizer(
            $decoratedProphecy->reveal(),
            $resourceMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $this->prophesize(ContainerInterface::class)->reveal()
        );

        $this->assertEquals(['name' => 'foo'], $normalizer->normalize($dummy, CollectionNormalizer::FORMAT, [
            'collection_operation_name' => 'get',
            'resource_class' => Dummy::class,
        ]));
    }

    public function testDoNothingIfNoRequestUri()
    {
        $dummy = new Dummy();

        $decoratedProphecy = $this->prophesize(NormalizerInterface::class);
        $decoratedProphecy->normalize($dummy, CollectionNormalizer::FORMAT, [
            'resource_class' => Dummy::class,
        ])->willReturn(['name' => 'foo']);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata('foo', '', null, [], [], ['filters' => ['foo']]));

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, Dummy::class)->willReturn(Dummy::class);

        $normalizer = new CollectionFiltersNormalizer(
            $decoratedProphecy->reveal(),
            $resourceMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $this->prophesize(ContainerInterface::class)->reveal()
        );

        $this->assertEquals(['name' => 'foo'], $normalizer->normalize($dummy, CollectionNormalizer::FORMAT, [
            'resource_class' => Dummy::class,
        ]));
    }

    public function testNormalize()
    {
        $filterProphecy = $this->prophesize(FilterInterface::class);
        $filterProphecy->getDescription(Dummy::class)->willReturn(['a' => ['property' => 'name', 'required' => true]])->shouldBeCalled();

        $filterLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $filterLocatorProphecy->has('foo')->willReturn(true)->shouldBeCalled();
        $filterLocatorProphecy->get('foo')->willReturn($filterProphecy->reveal())->shouldBeCalled();

        $this->normalize($filterLocatorProphecy->reveal());
    }

    /**
     * @group legacy
     * @expectedDeprecation The ApiPlatform\Core\Api\FilterCollection class is deprecated since version 2.1 and will be removed in 3.0. Provide an implementation of Psr\Container\ContainerInterface instead.
     */
    public function testNormalizeWithDeprecatedFilterCollection()
    {
        $filterProphecy = $this->prophesize(FilterInterface::class);
        $filterProphecy->getDescription(Dummy::class)->willReturn(['a' => ['property' => 'name', 'required' => true]])->shouldBeCalled();

        $this->normalize(new FilterCollection(['foo' => $filterProphecy->reveal()]));
    }

    /**
     * @group legacy
     */
    public function testConstructWithInvalidFilterLocator()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "$filterLocator" argument is expected to be an implementation of the "Psr\\Container\\ContainerInterface" interface.');

        new CollectionFiltersNormalizer(
            $this->prophesize(NormalizerInterface::class)->reveal(),
            $this->prophesize(ResourceMetadataFactoryInterface::class)->reveal(),
            $this->prophesize(ResourceClassResolverInterface::class)->reveal(),
            new \ArrayObject()
        );
    }

    private function normalize($filterLocator)
    {
        $dummy = new Dummy();

        $decoratedProphecy = $this->prophesize(NormalizerInterface::class);
        $decoratedProphecy->normalize($dummy, CollectionNormalizer::FORMAT, [
            'request_uri' => '/foo?bar=baz',
            'resource_class' => Dummy::class,
        ])->willReturn(['name' => 'foo']);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata('foo', '', null, [], [], ['filters' => ['foo']]));

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, Dummy::class)->willReturn(Dummy::class);

        $normalizer = new CollectionFiltersNormalizer(
            $decoratedProphecy->reveal(),
            $resourceMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $filterLocator
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
        ]));
    }
}
