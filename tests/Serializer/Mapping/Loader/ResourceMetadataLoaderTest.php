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

namespace ApiPlatform\Core\Tests\Serializer\Mapping\Loader;

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Serializer\Mapping\Loader\ResourceMetadataLoader;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Mapping\AttributeMetadata;
use Symfony\Component\Serializer\Mapping\ClassMetadata;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class ResourceMetadataLoaderTest extends TestCase
{
    use ProphecyTrait;

    private $resourceMetadataFactoryProphecy;
    private $resourceMetadataLoader;

    protected function setUp(): void
    {
        $this->resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $this->resourceMetadataLoader = new ResourceMetadataLoader($this->resourceMetadataFactoryProphecy->reveal());
    }

    public function testLoadClassMetadataResourceClassNotFound(): void
    {
        $this->resourceMetadataFactoryProphecy->create(Dummy::class)->willThrow(new ResourceClassNotFoundException());

        self::assertFalse($this->resourceMetadataLoader->loadClassMetadata(new ClassMetadata(Dummy::class)));
    }

    public function testLoadClassMetadataNoProperties(): void
    {
        $this->resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata());

        self::assertFalse($this->resourceMetadataLoader->loadClassMetadata(new ClassMetadata(Dummy::class)));
    }

    public function testLoadClassMetadata(): void
    {
        $resourceMetadata = (new ResourceMetadata())->withAttributes(['properties' => [
            'foo',
            'bar' => ['baz'],
            'withGroups' => ['groups' => ['one']],
        ]]);

        $this->resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn($resourceMetadata);

        $classMetadata = new ClassMetadata(Dummy::class);
        $classMetadata->addAttributeMetadata(new AttributeMetadata('bar'));

        $withGroupsAttributeMetadata = new AttributeMetadata('withGroups');
        $withGroupsAttributeMetadata->addGroup('one');

        self::assertTrue($this->resourceMetadataLoader->loadClassMetadata($classMetadata));
        self::assertEquals([
            'bar' => new AttributeMetadata('bar'),
            'foo' => new AttributeMetadata('foo'),
            'withGroups' => $withGroupsAttributeMetadata,
        ], $classMetadata->getAttributesMetadata());
    }
}
