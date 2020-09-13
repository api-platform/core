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

namespace ApiPlatform\Core\Tests\Metadata\Resource\Factory;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\Factory\AnnotationResourceMetadataFactory;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\ProphecyTrait;
use Doctrine\Common\Annotations\Reader;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AnnotationResourceMetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @dataProvider getCreateDependencies
     */
    public function testCreate($reader, $decorated, string $expectedShortName, ?string $expectedDescription)
    {
        $factory = new AnnotationResourceMetadataFactory($reader->reveal(), $decorated ? $decorated->reveal() : null);
        $metadata = $factory->create(Dummy::class);

        $this->assertEquals($expectedShortName, $metadata->getShortName());
        $this->assertEquals($expectedDescription, $metadata->getDescription());
        $this->assertEquals('http://example.com', $metadata->getIri());
        $this->assertEquals(['foo' => ['bar' => true]], $metadata->getItemOperations());
        $this->assertEquals(['baz' => ['tab' => false]], $metadata->getCollectionOperations());
        $this->assertEquals(['sub' => ['bus' => false]], $metadata->getSubresourceOperations());
        $this->assertEquals(['a' => 1, 'route_prefix' => '/foobar'], $metadata->getAttributes());
        $this->assertEquals(['foo' => 'bar'], $metadata->getGraphql());
    }

    public function testCreateWithDefaults()
    {
        $defaults = [
            'shortName' => 'Default shortname should not be ignored',
            'description' => 'CHANGEME!',
            'collection_operations' => ['get'],
            'item_operations' => ['get', 'put'],
            'attributes' => [
                'pagination_items_per_page' => 4,
                'pagination_maximum_items_per_page' => 6,
            ],
        ];

        $annotation = new ApiResource([
            'itemOperations' => ['get', 'delete'],
            'attributes' => [
                'pagination_client_enabled' => true,
                'pagination_maximum_items_per_page' => 10,
            ],
        ]);
        $reader = $this->prophesize(Reader::class);
        $reader->getClassAnnotation(Argument::type(\ReflectionClass::class), ApiResource::class)->willReturn($annotation)->shouldBeCalled();
        $factory = new AnnotationResourceMetadataFactory($reader->reveal(), null, $defaults);
        $metadata = $factory->create(Dummy::class);

        $this->assertNull($metadata->getShortName());
        $this->assertEquals('CHANGEME!', $metadata->getDescription());
        $this->assertEquals(['get'], $metadata->getCollectionOperations());
        $this->assertEquals(['get', 'delete'], $metadata->getItemOperations());
        $this->assertTrue($metadata->getAttribute('pagination_client_enabled'));
        $this->assertEquals(4, $metadata->getAttribute('pagination_items_per_page'));
        $this->assertEquals(10, $metadata->getAttribute('pagination_maximum_items_per_page'));
    }

    public function testCreateWithoutAttributes()
    {
        $annotation = new ApiResource([]);
        $reader = $this->prophesize(Reader::class);
        $reader->getClassAnnotation(Argument::type(\ReflectionClass::class), ApiResource::class)->willReturn($annotation)->shouldBeCalled();
        $factory = new AnnotationResourceMetadataFactory($reader->reveal(), null);
        $metadata = $factory->create(Dummy::class);

        $this->assertNull($metadata->getAttributes());
    }

    public function getCreateDependencies()
    {
        $resourceData = [
            'shortName' => 'shortName',
            'description' => 'description',
            'iri' => 'http://example.com',
            'itemOperations' => ['foo' => ['bar' => true]],
            'collectionOperations' => ['baz' => ['tab' => false]],
            'subresourceOperations' => ['sub' => ['bus' => false]],
            'attributes' => ['a' => 1, 'route_prefix' => '/foobar'],
            'graphql' => ['foo' => 'bar'],
        ];
        $annotationFull = new ApiResource($resourceData);

        $reader = $this->prophesize(Reader::class);
        $reader->getClassAnnotation(Argument::type(\ReflectionClass::class), ApiResource::class)->willReturn($annotationFull)->shouldBeCalled();

        $decoratedThrow = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $decoratedThrow->create(Dummy::class)->willThrow(ResourceClassNotFoundException::class);

        $decoratedReturn = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $decoratedReturn->create(Dummy::class)->willReturn(new ResourceMetadata('hello', 'blabla'))->shouldBeCalled();

        $resourceData['description'] = null;
        $annotationWithNull = new ApiResource($resourceData);

        $decoratedReturnWithNull = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $decoratedReturnWithNull->create(Dummy::class)->willReturn(new ResourceMetadata('hello'))->shouldBeCalled();

        $readerWithNull = $this->prophesize(Reader::class);
        $readerWithNull->getClassAnnotation(Argument::type(\ReflectionClass::class), ApiResource::class)->willReturn($annotationWithNull)->shouldBeCalled();

        return [
            [$reader, $decoratedThrow, 'shortName', 'description'],
            [$reader, null, 'shortName', 'description'],
            [$reader, $decoratedReturn, 'hello', 'blabla'],
            [$readerWithNull, $decoratedReturnWithNull, 'hello', null],
        ];
    }
}
