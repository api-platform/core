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

namespace ApiPlatform\Tests\Metadata\Resource\Factory;

use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\LegacyResourceMetadataResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use PHPUnit\Framework\TestCase;

/**
 * @author Pierre Escobar <p4ee5r@gmail.com>
 */
final class CustomPostCollectionOperationTest extends TestCase
{
    public function testPostOperationDefinedWithCustomPostOperationWithPath(): void
    {
        $resourceMetadataCollectionFactory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactory = $this->createMock(ResourceMetadataFactoryInterface::class);
        $propertyNameCollectionFactory = $this->createMock(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactory = $this->createMock(PropertyMetadataFactoryInterface::class);

        $resourceMetadataCollection = new ResourceMetadataCollection('some_resource_class');
        $resourceMetadataCollectionFactory
            ->method('create')
            ->willReturn($resourceMetadataCollection);

        $resourceMetadataFactory
            ->method('create')
            ->willReturn($this->getResourceMetadata());

        $propertyNameCollection = new PropertyNameCollection();
        $propertyNameCollectionFactory
            ->method('create')
            ->willReturn($propertyNameCollection);

        $legacyResourceMetadataResourceMetadataCollectionFactory = new LegacyResourceMetadataResourceMetadataCollectionFactory(
            $resourceMetadataCollectionFactory,
            $resourceMetadataFactory,
            $propertyNameCollectionFactory,
            $propertyMetadataFactory,
            []
        );

        $resourceMetadataCollection = $legacyResourceMetadataResourceMetadataCollectionFactory->create('some_resource_class');

        self::assertInstanceOf(
            Post::class,
            $resourceMetadataCollection->getOperation('api_webbies_post_collection')
        );
        self::assertInstanceOf(
            Post::class,
            $resourceMetadataCollection->getOperation('api_webbies_post_for_customer_collection')
        );
        self::assertInstanceOf(
            GetCollection::class,
            $resourceMetadataCollection->getOperation('api_webbies_get_collection')
        );
        self::assertInstanceOf(
            GetCollection::class,
            $resourceMetadataCollection->getOperation('api_webbies_get_for_customer_collection')
        );
    }

    private function getResourceMetadata(): ResourceMetadata
    {
        return new ResourceMetadata(
            'Webby',
            '',
            '',
            ['get' => ['method' => 'GET']],
            [
                'get' => ['method' => 'GET'],
                'get_for_customer' => [
                    'method' => 'GET',
                    'path' => '/customer/{customerId}/webbies',
                ],
                'post' => ['method' => 'POST'],
                'post_for_customer' => [
                    'method' => 'POST',
                    'path' => '/customer/{customerId}/webbies',
                ],
            ],
            null,
            null,
            []
        );
    }
}
