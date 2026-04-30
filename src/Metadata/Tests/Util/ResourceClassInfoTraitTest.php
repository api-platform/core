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

namespace ApiPlatform\Metadata\Tests\Util;

use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\Dummy;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\RelatedDummy;
use ApiPlatform\Metadata\Util\ResourceClassInfoTrait;
use PHPUnit\Framework\TestCase;

class ResourceClassInfoTraitTest extends TestCase
{
    private function getResourceClassInfoTraitImplementation(
        ?ResourceClassResolverInterface $resourceClassResolver = null,
        ?ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory = null,
    ) {
        return new class($resourceClassResolver, $resourceMetadataFactory) {
            use ResourceClassInfoTrait {
                ResourceClassInfoTrait::isResourceClass as public;
                ResourceClassInfoTrait::getResourceClass as public;
            }

            public function __construct(
                ?ResourceClassResolverInterface $resourceClassResolver = null,
                ?ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory = null,
            ) {
                $this->resourceClassResolver = $resourceClassResolver;
                $this->resourceMetadataFactory = $resourceMetadataFactory;
            }
        };
    }

    public function testIsResourceClassWithResolver(): void
    {
        $resourceClassResolver = $this->createStub(ResourceClassResolverInterface::class);
        $resourceClassResolver->method('isResourceClass')
            ->willReturnMap([
                [Dummy::class, true],
                [RelatedDummy::class, false],
            ]);

        $classInfo = $this->getResourceClassInfoTraitImplementation($resourceClassResolver);

        $this->assertTrue($classInfo->isResourceClass(Dummy::class));
        $this->assertFalse($classInfo->isResourceClass(RelatedDummy::class));
    }

    public function testIsResourceClassWithMetadataFactoryWhenNoResolver(): void
    {
        $resourceMetadataFactory = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $dummyMetadata = new ResourceMetadataCollection(Dummy::class, [new \stdClass()]);
        $emptyMetadata = new ResourceMetadataCollection(RelatedDummy::class, []);

        $resourceMetadataFactory->method('create')
            ->willReturnMap([
                [Dummy::class, $dummyMetadata],
                [RelatedDummy::class, $emptyMetadata],
            ]);

        $classInfo = $this->getResourceClassInfoTraitImplementation(null, $resourceMetadataFactory);

        $this->assertTrue($classInfo->isResourceClass(Dummy::class));
        $this->assertFalse($classInfo->isResourceClass(RelatedDummy::class));
    }

    public function testIsResourceClassWithoutResolverOrFactoryReturnsFalse(): void
    {
        $classInfo = $this->getResourceClassInfoTraitImplementation();

        $this->assertFalse($classInfo->isResourceClass(Dummy::class));
        $this->assertFalse($classInfo->isResourceClass(RelatedDummy::class));
    }

    public function testResourceClassResolverTakesPrecedenceOverResourceMetadataFactory(): void
    {
        $resourceClassResolver = $this->createStub(ResourceClassResolverInterface::class);
        $resourceClassResolver->method('isResourceClass')->willReturn(true);

        $resourceMetadataFactory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactory->expects($this->never())->method('create');

        $classInfo = $this->getResourceClassInfoTraitImplementation($resourceClassResolver, $resourceMetadataFactory);

        $this->assertTrue($classInfo->isResourceClass(Dummy::class));
    }

    public function testGetResourceClassWhenStrictFalseAndIsResourceClassFalse(): void
    {
        $dummy = new Dummy();

        $resourceClassResolver = $this->createMock(ResourceClassResolverInterface::class);
        $resourceClassResolver->method('isResourceClass')->willReturn(false);
        $resourceClassResolver->expects($this->never())->method('getResourceClass');

        $classInfo = $this->getResourceClassInfoTraitImplementation($resourceClassResolver);

        $result = $classInfo->getResourceClass($dummy);
        $this->assertNull($result);
    }

    public function testGetResourceClassWhenStrictFalseAndIsResourceClassTrue(): void
    {
        $dummy = new Dummy();

        $resourceClassResolver = $this->createStub(ResourceClassResolverInterface::class);
        $resourceClassResolver->method('isResourceClass')->willReturn(true);

        $resourceClassResolver->method('getResourceClass')->willReturn(Dummy::class);

        $classInfo = $this->getResourceClassInfoTraitImplementation($resourceClassResolver);

        $result = $classInfo->getResourceClass($dummy);
        $this->assertSame(Dummy::class, $result);
    }

    public function testGetResourceClassWhenStrictTrueAndIsResourceClassTrue(): void
    {
        $dummy = new Dummy();

        $resourceClassResolver = $this->createStub(ResourceClassResolverInterface::class);
        $resourceClassResolver->method('isResourceClass')->willReturn(true);

        $resourceClassResolver->method('getResourceClass')->willReturn(Dummy::class);

        $classInfo = $this->getResourceClassInfoTraitImplementation($resourceClassResolver);

        $result = $classInfo->getResourceClass($dummy, true);
        $this->assertSame(Dummy::class, $result);
    }

    public function testGetResourceClassWhenStrictTrueAndIsResourceClassFalse(): void
    {
        $dummy = new Dummy();

        $resourceClassResolver = $this->createStub(ResourceClassResolverInterface::class);
        $resourceClassResolver->method('isResourceClass')->willReturn(false);

        $resourceClassResolver->method('getResourceClass')->willReturn(Dummy::class);

        $classInfo = $this->getResourceClassInfoTraitImplementation($resourceClassResolver);

        $result = $classInfo->getResourceClass($dummy, true);
        $this->assertSame(Dummy::class, $result);
    }

    public function testGetResourceClassWithoutResolver(): void
    {
        $dummy = new Dummy();

        $classInfo = $this->getResourceClassInfoTraitImplementation();

        $result = $classInfo->getResourceClass($dummy);
        $this->assertSame(Dummy::class, $result);
    }
}
