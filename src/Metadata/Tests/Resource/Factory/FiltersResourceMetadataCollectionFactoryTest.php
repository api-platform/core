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

namespace ApiPlatform\Metadata\Tests\Resource\Factory;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Resource\Factory\FiltersResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use PHPUnit\Framework\TestCase;

class FiltersResourceMetadataCollectionFactoryTest extends TestCase
{
    public function testResourceNotBeingAClass(): void
    {
        /** @var class-string $resourceClass resource identified by a name which is not a class */
        $resourceClass = 'DynamicFaq'; // @phpstan-ignore varTag.nativeType
        $resourceMetadataCollection = new ResourceMetadataCollection($resourceClass, [
            new ApiResource(class: $resourceClass, operations: ['dynamic_faq_collection' => new GetCollection(class: $resourceClass, name: 'dynamic_faq_collection')]),
        ]);
        $decorated = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $decorated->method('create')->willReturn($resourceMetadataCollection);

        $this->assertSame($resourceMetadataCollection, (new FiltersResourceMetadataCollectionFactory($decorated))->create($resourceClass));
    }

    public function testResourceNotFound(): void
    {
        $decorated = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $decorated->method('create')->willReturn(new ResourceMetadataCollection('NotFound'));

        $this->expectException(ResourceClassNotFoundException::class);
        $this->expectExceptionMessage('Resource "NotFound" not found.');

        (new FiltersResourceMetadataCollectionFactory($decorated))->create('NotFound');
    }
}
