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

namespace ApiPlatform\Laravel\Tests\Unit\Routing;

use ApiPlatform\Laravel\Routing\IriConverter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\IdentifiersExtractorInterface;
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactoryInterface;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\State\ProviderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;
use Workbench\App\Models\Book;

class IriConverterTest extends TestCase
{
    public function testLocalCacheKeyDistinguishesItemAndCollectionForStringResource(): void
    {
        $collectionOpName = 'collection_op';
        $itemOpName = 'item_op';

        $collectionOp = (new GetCollection())->withName($collectionOpName)->withClass(Book::class);
        $itemOp = (new Get())->withName($itemOpName)->withClass(Book::class);

        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->exactly(2))
            ->method('generate')
            ->willReturnCallback(function (string $routeName, array $params) use ($collectionOpName, $itemOpName): string {
                if ($collectionOpName === $routeName) {
                    return '/api/books';
                }
                if ($itemOpName === $routeName) {
                    return '/api/books/'.$params['id'];
                }
                $this->fail(\sprintf('Unexpected route name "%s".', $routeName));
            });

        $metadataCollection = new ResourceMetadataCollection(Book::class, [
            (new ApiResource())->withOperations(new Operations([
                $collectionOpName => $collectionOp,
                $itemOpName => $itemOp,
            ])),
        ]);

        $resourceMetadataFactory = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactory->method('create')->willReturn($metadataCollection);

        $resourceClassResolver = $this->createStub(ResourceClassResolverInterface::class);
        $resourceClassResolver->method('isResourceClass')->willReturn(true);

        $iriConverter = new IriConverter(
            $this->createStub(ProviderInterface::class),
            $this->createStub(OperationMetadataFactoryInterface::class),
            $router,
            $this->createStub(IdentifiersExtractorInterface::class),
            $resourceClassResolver,
            $resourceMetadataFactory,
        );

        $this->assertSame('/api/books', $iriConverter->getIriFromResource(
            Book::class,
            UrlGeneratorInterface::ABS_PATH,
            new GetCollection(),
        ));

        $this->assertSame('/api/books/1', $iriConverter->getIriFromResource(
            Book::class,
            UrlGeneratorInterface::ABS_PATH,
            new Get(),
            ['uri_variables' => ['id' => 1]],
        ));
    }

    public function testGetIriFromResourceWithoutOperationUsesTheLocalOperationCache(): void
    {
        $itemOpName = 'item_op';
        $itemOp = (new Get())->withName($itemOpName)->withClass(Book::class);

        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->exactly(2))
            ->method('generate')
            ->with($itemOpName, ['id' => 1], UrlGeneratorInterface::ABS_PATH)
            ->willReturn('/api/books/1');

        $resourceMetadataFactory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactory->expects($this->once())
            ->method('create')
            ->with(Book::class)
            ->willReturn(new ResourceMetadataCollection(Book::class, [
                (new ApiResource())->withOperations(new Operations([$itemOpName => $itemOp])),
            ]));

        $iriConverter = $this->createIriConverter($router, $resourceMetadataFactory);

        $context = ['uri_variables' => ['id' => 1]];
        $this->assertSame('/api/books/1', $iriConverter->getIriFromResource(Book::class, UrlGeneratorInterface::ABS_PATH, null, $context));
        $this->assertSame('/api/books/1', $iriConverter->getIriFromResource(Book::class, UrlGeneratorInterface::ABS_PATH, null, $context));
    }

    public function testLocalOperationCacheDistinguishesItemAndCollectionAcrossCacheReads(): void
    {
        $collectionOpName = 'collection_op';
        $itemOpName = 'item_op';

        $collectionOp = (new GetCollection())->withName($collectionOpName)->withClass(Book::class);
        $itemOp = (new Get())->withName($itemOpName)->withClass(Book::class);

        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->exactly(4))
            ->method('generate')
            ->willReturnCallback(function (string $routeName) use ($collectionOpName, $itemOpName): string {
                if ($collectionOpName === $routeName) {
                    return '/api/books';
                }
                if ($itemOpName === $routeName) {
                    return '/api/books/1';
                }
                $this->fail(\sprintf('Unexpected route name "%s".', $routeName));
            });

        $resourceMetadataFactory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactory->expects($this->exactly(2))
            ->method('create')
            ->with(Book::class)
            ->willReturn(new ResourceMetadataCollection(Book::class, [
                (new ApiResource())->withOperations(new Operations([
                    $collectionOpName => $collectionOp,
                    $itemOpName => $itemOp,
                ])),
            ]));

        $iriConverter = $this->createIriConverter($router, $resourceMetadataFactory);

        // Both forms use a string resource, so only the item/collection part of the key differs.
        $context = ['uri_variables' => ['id' => 1]];
        $this->assertSame('/api/books/1', $iriConverter->getIriFromResource(Book::class, UrlGeneratorInterface::ABS_PATH, null, $context));
        $this->assertSame('/api/books', $iriConverter->getIriFromResource(Book::class, UrlGeneratorInterface::ABS_PATH, new GetCollection()));
        $this->assertSame('/api/books/1', $iriConverter->getIriFromResource(Book::class, UrlGeneratorInterface::ABS_PATH, null, $context));
        $this->assertSame('/api/books', $iriConverter->getIriFromResource(Book::class, UrlGeneratorInterface::ABS_PATH, new GetCollection()));
    }

    private function createIriConverter(RouterInterface $router, ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory): IriConverter
    {
        $resourceClassResolver = $this->createStub(ResourceClassResolverInterface::class);
        $resourceClassResolver->method('isResourceClass')->willReturn(true);

        return new IriConverter(
            $this->createStub(ProviderInterface::class),
            $this->createStub(OperationMetadataFactoryInterface::class),
            $router,
            $this->createStub(IdentifiersExtractorInterface::class),
            $resourceClassResolver,
            $resourceMetadataFactory,
        );
    }
}
