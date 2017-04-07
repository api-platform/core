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

namespace ApiPlatform\Core\tests\Hydra\Serializer;

use ApiPlatform\Core\Api\FilterCollection;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Core\Hydra\Serializer\CollectionFiltersNormalizer;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class CollectionFiltersNormalizerTest extends \PHPUnit_Framework_TestCase
{
    public function testSupportsNormalization()
    {
        $decorated = $this->prophesize(NormalizerInterface::class);
        $decorated->supportsNormalization('foo', 'abc')->willReturn(true)->shouldBeCalled();

        $resourceMetadataFactory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceClassResolver = $this->prophesize(ResourceClassResolverInterface::class);

        $normalizer = new CollectionFiltersNormalizer(
            $decorated->reveal(),
            $resourceMetadataFactory->reveal(),
            $resourceClassResolver->reveal(),
            new FilterCollection()
        );
        $this->assertTrue($normalizer->supportsNormalization('foo', 'abc'));
    }

    public function testDoNothingIfSubLevel()
    {
        $dummy = new Dummy();

        $decorated = $this->prophesize(NormalizerInterface::class);
        $decorated->normalize($dummy, null, ['api_sub_level' => true])->willReturn(['name' => 'foo'])->shouldBeCalled();

        $resourceMetadataFactory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceClassResolver = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolver->getResourceClass()->shouldNotBeCalled();

        $normalizer = new CollectionFiltersNormalizer(
            $decorated->reveal(),
            $resourceMetadataFactory->reveal(),
            $resourceClassResolver->reveal(),
            new FilterCollection()
        );
        $this->assertEquals(['name' => 'foo'], $normalizer->normalize($dummy, null, ['api_sub_level' => true]));
    }

    public function testDoNothingIfNoFilter()
    {
        $dummy = new Dummy();

        $decorated = $this->prophesize(NormalizerInterface::class);
        $decorated->normalize($dummy, null, ['collection_operation_name' => 'get'])->willReturn(['name' => 'foo'])->shouldBeCalled();

        $resourceMetadataFactory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactory->create(Dummy::class)->willReturn(new ResourceMetadata('foo', '', null, [], ['get' => []]));

        $resourceClassResolver = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolver->getResourceClass($dummy, null, true)->willReturn(Dummy::class)->shouldBeCalled();

        $normalizer = new CollectionFiltersNormalizer(
            $decorated->reveal(),
            $resourceMetadataFactory->reveal(),
            $resourceClassResolver->reveal(),
            new FilterCollection()
        );
        $this->assertEquals(['name' => 'foo'], $normalizer->normalize($dummy, null, ['collection_operation_name' => 'get']));
    }

    public function testDoNothingIfNoRequestUri()
    {
        $dummy = new Dummy();

        $decorated = $this->prophesize(NormalizerInterface::class);
        $decorated->normalize($dummy, null, [])->willReturn(['name' => 'foo'])->shouldBeCalled();

        $resourceMetadataFactory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactory->create(Dummy::class)->willReturn(new ResourceMetadata('foo', '', null, [], [], ['filters' => ['foo']]));

        $resourceClassResolver = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolver->getResourceClass($dummy, null, true)->willReturn(Dummy::class)->shouldBeCalled();

        $normalizer = new CollectionFiltersNormalizer(
            $decorated->reveal(),
            $resourceMetadataFactory->reveal(),
            $resourceClassResolver->reveal(),
            new FilterCollection()
        );
        $this->assertEquals(['name' => 'foo'], $normalizer->normalize($dummy, null, []));
    }

    public function testNormalize()
    {
        $dummy = new Dummy();

        $decorated = $this->prophesize(NormalizerInterface::class);
        $decorated->normalize($dummy, null, ['request_uri' => '/foo?bar=baz'])->willReturn(['name' => 'foo'])->shouldBeCalled();

        $resourceMetadataFactory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactory->create(Dummy::class)->willReturn(new ResourceMetadata('foo', '', null, [], [], ['filters' => ['foo']]));

        $resourceClassResolver = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolver->getResourceClass($dummy, null, true)->willReturn(Dummy::class)->shouldBeCalled();

        $filter = $this->prophesize(FilterInterface::class);
        $filter->getDescription(Dummy::class)->willReturn(['a' => ['property' => 'name', 'required' => true]])->shouldBeCalled();

        $normalizer = new CollectionFiltersNormalizer(
            $decorated->reveal(),
            $resourceMetadataFactory->reveal(),
            $resourceClassResolver->reveal(),
            new FilterCollection(['foo' => $filter->reveal()])
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
