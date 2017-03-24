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
use Doctrine\Common\Annotations\Reader;
use Prophecy\Argument;
use Prophecy\Prophecy\ProphecyInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AnnotationResourceMetadataFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getCreateDependencies
     */
    public function testCreate(ProphecyInterface $reader, ProphecyInterface $decorated = null, $expectedShortName, $expectedDescription)
    {
        $factory = new AnnotationResourceMetadataFactory($reader->reveal(), $decorated ? $decorated->reveal() : null);
        $metadata = $factory->create(Dummy::class);

        $this->assertEquals($expectedShortName, $metadata->getShortName());
        $this->assertEquals($expectedDescription, $metadata->getDescription());
        $this->assertEquals('http://example.com', $metadata->getIri());
        $this->assertEquals(['foo' => ['bar' => true]], $metadata->getItemOperations());
        $this->assertEquals(['baz' => ['tab' => false]], $metadata->getCollectionOperations());
        $this->assertEquals(['a' => 1], $metadata->getAttributes());
    }

    public function getCreateDependencies()
    {
        $annotation = new ApiResource();
        $annotation->shortName = 'shortName';
        $annotation->description = 'description';
        $annotation->iri = 'http://example.com';
        $annotation->itemOperations = ['foo' => ['bar' => true]];
        $annotation->collectionOperations = ['baz' => ['tab' => false]];
        $annotation->attributes = ['a' => 1];

        $reader = $this->prophesize(Reader::class);
        $reader->getClassAnnotation(Argument::type(\ReflectionClass::class), ApiResource::class)->willReturn($annotation)->shouldBeCalled();

        $decoratedThrow = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $decoratedThrow->create(Dummy::class)->willThrow(ResourceClassNotFoundException::class);

        $decoratedReturn = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $decoratedReturn->create(Dummy::class)->willReturn(new ResourceMetadata('hello', 'blabla'))->shouldBeCalled();

        return [
            [$reader, $decoratedThrow, 'shortName', 'description'],
            [$reader, null, 'shortName', 'description'],
            [$reader, $decoratedReturn, 'hello', 'blabla'],
        ];
    }
}
