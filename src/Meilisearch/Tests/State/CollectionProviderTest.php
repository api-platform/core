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

namespace ApiPlatform\Meilisearch\Tests\State;

use ApiPlatform\Meilisearch\Extension\RequestParametersCollectionExtensionInterface;
use ApiPlatform\Meilisearch\Paginator;
use ApiPlatform\Meilisearch\State\CollectionProvider;
use ApiPlatform\Meilisearch\State\Options;
use ApiPlatform\Meilisearch\Tests\Fixtures\Movie;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\State\Pagination\Pagination;
use Meilisearch\Client;
use Meilisearch\Contracts\Http;
use Meilisearch\Endpoints\Index;
use Meilisearch\Exceptions\ApiException;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Meilisearch\Endpoints\Index is final and can't be mocked directly, so
 * these tests construct a real Index wired to a mocked Http transport
 * (the thing that actually makes requests) and mock Client (not final)
 * to hand that Index back from index().
 */
class CollectionProviderTest extends TestCase
{
    use ProphecyTrait;

    public function testProvide(): void
    {
        $httpProphecy = $this->prophesize(Http::class);
        $httpProphecy->post('/indexes/movie/search', (object) [
            'q' => 'wars',
            'filter' => 'genre = "sci-fi"',
            'sort' => ['year:desc'],
            'offset' => 0,
            'limit' => 30,
        ])->willReturn([
            'hits' => [['id' => 1, 'title' => 'Star Wars', 'genre' => 'sci-fi', 'year' => 1977]],
            'processingTimeMs' => 1,
            'query' => 'wars',
            'offset' => 0,
            'limit' => 30,
            'estimatedTotalHits' => 1,
        ]);

        $index = new Index($httpProphecy->reveal(), 'movie');
        $clientProphecy = $this->prophesize(Client::class);
        $clientProphecy->index('movie')->willReturn($index);

        $extension = new class implements RequestParametersCollectionExtensionInterface {
            public function applyToCollection(array $parameters, string $resourceClass, ?\ApiPlatform\Metadata\Operation $operation = null, array $context = []): array
            {
                $parameters['q'] = 'wars';
                $parameters['filter'] = 'genre = "sci-fi"';
                $parameters['sort'] = ['year:desc'];

                return $parameters;
            }
        };

        $denormalizerProphecy = $this->prophesize(DenormalizerInterface::class);
        $denormalizerProphecy->denormalize([
            'id' => 1, 'title' => 'Star Wars', 'genre' => 'sci-fi', 'year' => 1977,
        ], Movie::class, 'array', [AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => true])->willReturn((static function (): Movie {
            $movie = new Movie();
            $movie->id = 1;
            $movie->title = 'Star Wars';

            return $movie;
        })());

        $pagination = new Pagination(['items_per_page' => 30]);

        $provider = new CollectionProvider(
            $clientProphecy->reveal(),
            $denormalizerProphecy->reveal(),
            $pagination,
            [$extension],
        );

        $operation = (new GetCollection())->withClass(Movie::class)->withStateOptions(new Options(index: 'movie'));
        $result = $provider->provide($operation);

        self::assertInstanceOf(Paginator::class, $result);
        self::assertSame(1., $result->getTotalItems());
    }

    public function testProvideWithoutExplicitOptionsFallsBackToInflectedShortName(): void
    {
        $httpProphecy = $this->prophesize(Http::class);
        $httpProphecy->post('/indexes/movie/search', (object) [
            'q' => '',
            'offset' => 0,
            'limit' => 30,
        ])->willReturn([
            'hits' => [],
            'processingTimeMs' => 1,
            'query' => '',
            'offset' => 0,
            'limit' => 30,
            'estimatedTotalHits' => 0,
        ]);

        $index = new Index($httpProphecy->reveal(), 'movie');
        $clientProphecy = $this->prophesize(Client::class);
        $clientProphecy->index('movie')->willReturn($index);

        $provider = new CollectionProvider(
            $clientProphecy->reveal(),
            $this->prophesize(DenormalizerInterface::class)->reveal(),
            new Pagination(['items_per_page' => 30]),
        );

        // Operation short name "Movie" tableizes to "movie" -- same as the fixture's
        // explicit index name, so this exercises the fallback path without needing
        // a second fixture class.
        $operation = (new GetCollection())->withClass(Movie::class)->withShortName('Movie');
        $result = $provider->provide($operation);

        self::assertSame(0., $result->getTotalItems());
    }

    public function testProvideWrapsApiException(): void
    {
        $response = $this->prophesize(ResponseInterface::class);
        $response->getStatusCode()->willReturn(404);
        $response->getReasonPhrase()->willReturn('Not Found');
        $response->getBody()->willReturn('{}');

        $httpProphecy = $this->prophesize(Http::class);
        $httpProphecy->post('/indexes/movie/search', (object) [
            'q' => '',
            'offset' => 0,
            'limit' => 30,
        ])->willThrow(new ApiException($response->reveal(), '{}'));

        $index = new Index($httpProphecy->reveal(), 'movie');
        $clientProphecy = $this->prophesize(Client::class);
        $clientProphecy->index('movie')->willReturn($index);

        $provider = new CollectionProvider(
            $clientProphecy->reveal(),
            $this->prophesize(DenormalizerInterface::class)->reveal(),
            new Pagination(['items_per_page' => 30]),
        );

        $this->expectException(\ApiPlatform\State\ApiResource\Error::class);

        $operation = (new GetCollection())->withClass(Movie::class)->withStateOptions(new Options(index: 'movie'));
        $provider->provide($operation);
    }
}
