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
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Core\Hydra\Serializer\CollectionFiltersNormalizer;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class CollectionFiltersNormalizerTest extends TestCase
{
    public function testSupportsNormalization()
    {
        $decoratedProphecy = $this->prophesize(NormalizerInterface::class);
        $decoratedProphecy->supportsNormalization('foo', 'abc')->willReturn(true)->shouldBeCalled();

        $normalizer = new CollectionFiltersNormalizer(
            $decoratedProphecy->reveal(),
            $this->prophesize(ResourceMetadataFactoryInterface::class)->reveal(),
            $this->prophesize(ResourceClassResolverInterface::class)->reveal(),
            $this->prophesize(ContainerInterface::class)->reveal()
        );

        $this->assertTrue($normalizer->supportsNormalization('foo', 'abc'));
    }

    public function testDoNothingIfSubLevel()
    {
        $dummy = new Dummy();

        $decoratedProphecy = $this->prophesize(NormalizerInterface::class);
        $decoratedProphecy->normalize($dummy, null, ['api_sub_level' => true])->willReturn(['name' => 'foo'])->shouldBeCalled();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass()->shouldNotBeCalled();

        $normalizer = new CollectionFiltersNormalizer(
            $decoratedProphecy->reveal(),
            $this->prophesize(ResourceMetadataFactoryInterface::class)->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $this->prophesize(ContainerInterface::class)->reveal()
        );

        $this->assertEquals(['name' => 'foo'], $normalizer->normalize($dummy, null, ['api_sub_level' => true]));
    }

    public function testDoNothingIfNoFilter()
    {
        $dummy = new Dummy();

        $decoratedProphecy = $this->prophesize(NormalizerInterface::class);
        $decoratedProphecy->normalize($dummy, null, ['collection_operation_name' => 'get'])->willReturn(['name' => 'foo'])->shouldBeCalled();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata('foo', '', null, [], ['get' => []]));

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, null, true)->willReturn(Dummy::class)->shouldBeCalled();

        $normalizer = new CollectionFiltersNormalizer(
            $decoratedProphecy->reveal(),
            $resourceMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $this->prophesize(ContainerInterface::class)->reveal()
        );

        $this->assertEquals(['name' => 'foo'], $normalizer->normalize($dummy, null, ['collection_operation_name' => 'get']));
    }

    public function testDoNothingIfNoRequestUri()
    {
        $dummy = new Dummy();

        $decoratedProphecy = $this->prophesize(NormalizerInterface::class);
        $decoratedProphecy->normalize($dummy, null, [])->willReturn(['name' => 'foo'])->shouldBeCalled();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata('foo', '', null, [], [], ['filters' => ['foo']]));

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, null, true)->willReturn(Dummy::class)->shouldBeCalled();

        $normalizer = new CollectionFiltersNormalizer(
            $decoratedProphecy->reveal(),
            $resourceMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $this->prophesize(ContainerInterface::class)->reveal()
        );

        $this->assertEquals(['name' => 'foo'], $normalizer->normalize($dummy, null, []));
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
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessage The "$filterLocator" argument is expected to be an implementation of the "Psr\Container\ContainerInterface" interface.
     */
    public function testConstructWithInvalidFilterLocator()
    {
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
        $decoratedProphecy->normalize($dummy, null, ['request_uri' => '/foo?bar=baz'])->willReturn(['name' => 'foo'])->shouldBeCalled();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata('foo', '', null, [], [], ['filters' => ['foo']]));

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, null, true)->willReturn(Dummy::class)->shouldBeCalled();

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
        ], $normalizer->normalize($dummy, null, ['request_uri' => '/foo?bar=baz']));
    }
}
