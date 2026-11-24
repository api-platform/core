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

namespace ApiPlatform\State\Tests;

use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\State\ResourceObjectResolver;
use ApiPlatform\State\Tests\Fixtures\ApiResource\Dummy;
use ApiPlatform\State\Tests\Fixtures\Entity\MappedEntity;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ObjectMapper\Metadata\Mapping;
use Symfony\Component\ObjectMapper\Metadata\ObjectMapperMetadataFactoryInterface;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

class ResourceObjectResolverTest extends TestCase
{
    public function testResolveResourceObject(): void
    {
        $resourceClassResolver = $this->createMock(ResourceClassResolverInterface::class);
        $resourceClassResolver->expects($this->once())->method('isResourceClass')->with(Dummy::class)->willReturn(true);
        $objectMapper = $this->createMock(ObjectMapperInterface::class);
        $objectMapper->expects($this->never())->method('map');

        $dummy = new Dummy();
        $resolver = new ResourceObjectResolver($resourceClassResolver, $objectMapper);

        $this->assertSame([$dummy], iterator_to_array($resolver->resolve($dummy), false));
    }

    public function testResolveMappedObjectWithMetadataFactory(): void
    {
        $resourceClassResolver = $this->createStub(ResourceClassResolverInterface::class);
        $resourceClassResolver->method('isResourceClass')->willReturn(false);

        $entity = new MappedEntity();
        $dummy = new Dummy();
        $objectMapper = $this->createMock(ObjectMapperInterface::class);
        $objectMapper->expects($this->once())->method('map')->with($entity, Dummy::class)->willReturn($dummy);
        $metadataFactory = $this->createMock(ObjectMapperMetadataFactoryInterface::class);
        $metadataFactory->expects($this->once())->method('create')->with($entity)->willReturn([new Mapping(target: Dummy::class)]);

        $resolver = new ResourceObjectResolver($resourceClassResolver, $objectMapper, $metadataFactory);

        $this->assertSame([$dummy], iterator_to_array($resolver->resolve($entity), false));
    }

    public function testResolveMappedObjectWithMapAttribute(): void
    {
        $resourceClassResolver = $this->createStub(ResourceClassResolverInterface::class);
        $resourceClassResolver->method('isResourceClass')->willReturn(false);

        $entity = new MappedEntity();
        $dummy = new Dummy();
        $objectMapper = $this->createMock(ObjectMapperInterface::class);
        $objectMapper->expects($this->once())->method('map')->with($entity, Dummy::class)->willReturn($dummy);

        $resolver = new ResourceObjectResolver($resourceClassResolver, $objectMapper);

        $this->assertSame([$dummy], iterator_to_array($resolver->resolve($entity), false));
    }

    public function testResolveNonResourceWithoutObjectMapper(): void
    {
        $resourceClassResolver = $this->createStub(ResourceClassResolverInterface::class);
        $resourceClassResolver->method('isResourceClass')->willReturn(false);

        $resolver = new ResourceObjectResolver($resourceClassResolver);

        $this->assertSame([], iterator_to_array($resolver->resolve(new MappedEntity()), false));
    }
}
