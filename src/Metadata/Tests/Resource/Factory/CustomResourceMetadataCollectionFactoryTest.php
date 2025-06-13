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
use ApiPlatform\Metadata\Extractor\ClosureExtractorInterface;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\CustomResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use PHPUnit\Framework\TestCase;

final class CustomResourceMetadataCollectionFactoryTest extends TestCase
{
    public function testCustomizeApiResource(): void
    {
        $decorated = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $resourceClosureExtractor = $this->createMock(ClosureExtractorInterface::class);
        $resourceClass = \stdClass::class;
        $resourceMetadataCollection = new ResourceMetadataCollection($resourceClass);
        $resourceMetadataCollection[] = (new ApiResource())->withClass($resourceClass);
        $customResourceMetadataCollectionFactory = new CustomResourceMetadataCollectionFactory($resourceClosureExtractor, $decorated);

        $decorated->expects($this->once())->method('create')->with($resourceClass)->willReturn(
            $resourceMetadataCollection,
        );
        $resourceClosureExtractor->expects($this->once())->method('getClosures')->willReturn([
            static function (ApiResource $resource): ApiResource {
                if (\stdClass::class !== $resource->getClass()) {
                    return $resource;
                }

                return $resource->withShortName('dummy');
            },
        ]);

        $resourceMetadataCollection = $customResourceMetadataCollectionFactory->create($resourceClass);

        $resource = $resourceMetadataCollection->getIterator()->current();
        $this->assertInstanceOf(ApiResource::class, $resource);
        $this->assertSame('dummy', $resource->getShortName());
    }

    public function testAddOperation(): void
    {
        $decorated = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $resourceClosureExtractor = $this->createMock(ClosureExtractorInterface::class);
        $resourceClass = \stdClass::class;
        $resourceMetadataCollection = new ResourceMetadataCollection($resourceClass);
        $resourceMetadataCollection[] = (new ApiResource(shortName: 'Speaker'))->withClass($resourceClass);
        $customResourceMetadataCollectionFactory = new CustomResourceMetadataCollectionFactory($resourceClosureExtractor, $decorated);

        $decorated->expects($this->once())->method('create')->with($resourceClass)->willReturn(
            $resourceMetadataCollection,
        );
        $resourceClosureExtractor->expects($this->once())->method('getClosures')->willReturn([
            static function (ApiResource $resource): ApiResource {
                if (\stdClass::class !== $resource->getClass()) {
                    return $resource;
                }

                $operations = $resource->getOperations() ?? new Operations();
                $operations->add('_api_Speaker_put', new Put());

                return $resource->withOperations($operations);
            },
        ]);

        $resourceMetadataCollection = $customResourceMetadataCollectionFactory->create($resourceClass);

        $resource = $resourceMetadataCollection->getIterator()->current();
        $this->assertInstanceOf(ApiResource::class, $resource);
        $this->assertTrue($resource->getOperations()->has('_api_Speaker_put'));

        // Check the defaults have been applied
        $putOperation = $resourceMetadataCollection->getOperation('_api_Speaker_put');
        $this->assertSame($resource->getClass(), $putOperation->getClass());
    }

    public function testRemoveOperation(): void
    {
        $decorated = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $resourceClosureExtractor = $this->createMock(ClosureExtractorInterface::class);
        $resourceClass = \stdClass::class;
        $resourceMetadataCollection = new ResourceMetadataCollection($resourceClass);

        $operations = new Operations();
        $operations->add('_api_Speaker_post', new Post());

        $resourceMetadataCollection[] = (new ApiResource(shortName: 'Speaker'))
            ->withClass($resourceClass)
            ->withOperations($operations);

        $customResourceMetadataCollectionFactory = new CustomResourceMetadataCollectionFactory($resourceClosureExtractor, $decorated);

        $decorated->expects($this->once())->method('create')->with($resourceClass)->willReturn(
            $resourceMetadataCollection,
        );
        $resourceClosureExtractor->expects($this->once())->method('getClosures')->willReturn([
            static function (ApiResource $resource): ApiResource {
                if (\stdClass::class !== $resource->getClass()) {
                    return $resource;
                }

                $operations = $resource->getOperations() ?? new Operations();
                $operations->remove('_api_Speaker_post');

                return $resource->withOperations($operations);
            },
        ]);

        $resourceMetadataCollection = $customResourceMetadataCollectionFactory->create($resourceClass);

        $resource = $resourceMetadataCollection->getIterator()->current();
        $this->assertInstanceOf(ApiResource::class, $resource);
        $this->assertFalse($resource->getOperations()->has('_api_Speaker_post'));
    }
}
