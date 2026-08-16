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

namespace ApiPlatform\Meilisearch\Tests\Metadata\Resource\Factory;

use ApiPlatform\Meilisearch\Metadata\Resource\Factory\MeilisearchProviderResourceMetadataCollectionFactory;
use ApiPlatform\Meilisearch\State\CollectionProvider;
use ApiPlatform\Meilisearch\State\ItemProvider;
use ApiPlatform\Meilisearch\State\Options;
use ApiPlatform\Meilisearch\Tests\Fixtures\Movie;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class MeilisearchProviderResourceMetadataCollectionFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testConstruct(): void
    {
        self::assertInstanceOf(
            ResourceMetadataCollectionFactoryInterface::class,
            new MeilisearchProviderResourceMetadataCollectionFactory(
                $this->prophesize(ResourceMetadataCollectionFactoryInterface::class)->reveal()
            )
        );
    }

    public function testAssignsCollectionAndItemProvidersWhenOptionsIsMeilisearch(): void
    {
        $decorated = $this->decoratedFactoryReturning(new ApiResource(
            operations: [
                'get' => new Get(class: Movie::class, stateOptions: new Options(index: 'movie')),
                'get_collection' => new GetCollection(class: Movie::class, stateOptions: new Options(index: 'movie')),
            ],
        ));

        $factory = new MeilisearchProviderResourceMetadataCollectionFactory($decorated);
        $operations = $factory->create(Movie::class)[0]->getOperations();

        self::assertSame(ItemProvider::class, iterator_to_array($operations)['get']->getProvider());
        self::assertSame(CollectionProvider::class, iterator_to_array($operations)['get_collection']->getProvider());
    }

    public function testDoesNotOverrideAnExplicitProvider(): void
    {
        $decorated = $this->decoratedFactoryReturning(new ApiResource(
            operations: [
                'get_collection' => new GetCollection(
                    class: Movie::class,
                    stateOptions: new Options(index: 'movie'),
                    provider: 'app.custom_provider',
                ),
            ],
        ));

        $factory = new MeilisearchProviderResourceMetadataCollectionFactory($decorated);
        $operations = $factory->create(Movie::class)[0]->getOperations();

        self::assertSame('app.custom_provider', iterator_to_array($operations)['get_collection']->getProvider());
    }

    public function testLeavesNonMeilisearchOperationsUntouched(): void
    {
        $decorated = $this->decoratedFactoryReturning(new ApiResource(
            operations: [
                'get_collection' => new GetCollection(class: Movie::class),
            ],
        ));

        $factory = new MeilisearchProviderResourceMetadataCollectionFactory($decorated);
        $operations = $factory->create(Movie::class)[0]->getOperations();

        self::assertNull(iterator_to_array($operations)['get_collection']->getProvider());
    }

    private function decoratedFactoryReturning(ApiResource $resource): ResourceMetadataCollectionFactoryInterface
    {
        $decoratedProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $decoratedProphecy->create(Movie::class)->willReturn(new ResourceMetadataCollection(Movie::class, [$resource]));

        return $decoratedProphecy->reveal();
    }
}
