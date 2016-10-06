<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\JsonApi\Serializer;

use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\JsonApi\Serializer\CollectionNormalizer;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class CollectionNormalizerTest extends \PHPUnit_Framework_TestCase
{
    public function testSupportsNormalize()
    {
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceMetadataProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $propertyMetadataProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $normalizer = new CollectionNormalizer($resourceClassResolverProphecy->reveal(), $resourceMetadataProphecy->reveal(), $propertyMetadataProphecy->reveal(), 'page');

        $this->assertTrue($normalizer->supportsNormalization([], CollectionNormalizer::FORMAT));
        $this->assertTrue($normalizer->supportsNormalization(new \ArrayObject(), CollectionNormalizer::FORMAT));
        $this->assertFalse($normalizer->supportsNormalization([], 'xml'));
        $this->assertFalse($normalizer->supportsNormalization(new \ArrayObject(), 'xml'));
    }

    public function testNormalizeApiSubLevel()
    {
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass()->shouldNotBeCalled();

        $resourceMetadataProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $propertyMetadataProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $itemNormalizer = $this->prophesize(NormalizerInterface::class);
        $itemNormalizer->normalize('bar', null, ['api_sub_level' => true])->willReturn(22);

        $normalizer = new CollectionNormalizer($resourceClassResolverProphecy->reveal(), $resourceMetadataProphecy->reveal(), $propertyMetadataProphecy->reveal(), 'page');
        $normalizer->setNormalizer($itemNormalizer->reveal());

        $this->assertEquals(['data' => [['foo' => 22]]], $normalizer->normalize(['foo' => 'bar'], null, ['api_sub_level' => true]));
    }

    public function testNormalizePaginator()
    {
        $paginatorProphecy = $this->prophesize(PaginatorInterface::class);
        $paginatorProphecy->getCurrentPage()->willReturn(3);
        $paginatorProphecy->getLastPage()->willReturn(7);
        $paginatorProphecy->getItemsPerPage()->willReturn(12);
        $paginatorProphecy->getTotalItems()->willReturn(1312);
        $paginatorProphecy->rewind()->shouldBeCalled();
        $paginatorProphecy->valid()->willReturn(true, false)->shouldBeCalled();
        $paginatorProphecy->current()->willReturn('foo')->shouldBeCalled();
        $paginatorProphecy->next()->willReturn()->shouldBeCalled();
        $paginator = $paginatorProphecy->reveal();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($paginator, null, true)->willReturn('Foo')->shouldBeCalled();

        $resourceMetadataProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataProphecy->create('Foo')->willReturn(new ResourceMetadata('Foo', 'A foo', '/foos', null, null, ['id', 'name']));


        $propertyMetadataProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataProphecy->create('Foo', 'id')->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_INT), 'id', true, true, true, true, false, true, null, null, []))->shouldBeCalled(1);
        $propertyMetadataProphecy->create('Foo', 'name')->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), 'name', true, true, true, true, false, false, null, null, []))->shouldBeCalled(1);

        $itemNormalizer = $this->prophesize(NormalizerInterface::class);
        $itemNormalizer->normalize('foo', null, ['api_sub_level' => true, 'resource_class' => 'Foo'])->willReturn(['id' => 1, 'name' => 'Kévin']);

        $normalizer = new CollectionNormalizer($resourceClassResolverProphecy->reveal(), $resourceMetadataProphecy->reveal(), $propertyMetadataProphecy->reveal(), 'page');
        $normalizer->setNormalizer($itemNormalizer->reveal());

        $expected = [
            'links' => [
                'self' => '/?page=3',
                'first' => '/?page=1',
                'last' => '/?page=7',
                'prev' => '/?page=2',
                'next' => '/?page=4',
            ],
            'data' => [
                    [
                      'type' => 'Foo',
                      'id' => 1,
                      'attributes' => [
                          'id' => 1,
                          'name' => 'Kévin',
                      ],
                      'relationships' => [],
                    ],
            ],
            'meta' => [
                'totalItems' => 1312,
                'itemsPerPage' => 12,
            ],
        ];
        $this->assertEquals($expected, $normalizer->normalize($paginator));
    }
}
