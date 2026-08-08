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

use ApiPlatform\Meilisearch\State\ItemProvider;
use ApiPlatform\Meilisearch\State\Options;
use ApiPlatform\Meilisearch\Tests\Fixtures\Movie;
use ApiPlatform\Metadata\Get;
use Meilisearch\Client;
use Meilisearch\Contracts\Http;
use Meilisearch\Endpoints\Index;
use Meilisearch\Exceptions\ApiException;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class ItemProviderTest extends TestCase
{
    use ProphecyTrait;

    public function testProvide(): void
    {
        $httpProphecy = $this->prophesize(Http::class);
        $httpProphecy->get('/indexes/movie/documents/1', [])->willReturn([
            'id' => 1, 'title' => 'Star Wars', 'genre' => 'sci-fi', 'year' => 1977,
        ]);

        $index = new Index($httpProphecy->reveal(), 'movie');
        $clientProphecy = $this->prophesize(Client::class);
        $clientProphecy->index('movie')->willReturn($index);

        $movie = new Movie();
        $movie->id = 1;
        $movie->title = 'Star Wars';

        $denormalizerProphecy = $this->prophesize(DenormalizerInterface::class);
        $denormalizerProphecy->denormalize([
            'id' => 1, 'title' => 'Star Wars', 'genre' => 'sci-fi', 'year' => 1977,
        ], Movie::class, 'array', [AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => true])->willReturn($movie);

        $provider = new ItemProvider($clientProphecy->reveal(), $denormalizerProphecy->reveal());

        $operation = (new Get())->withClass(Movie::class)->withStateOptions(new Options(index: 'movie'));
        $result = $provider->provide($operation, ['id' => 1]);

        self::assertSame($movie, $result);
    }

    public function testProvideReturnsNullOn404(): void
    {
        $response = $this->prophesize(ResponseInterface::class);
        $response->getStatusCode()->willReturn(404);
        $response->getReasonPhrase()->willReturn('Not Found');
        $response->getBody()->willReturn('{}');

        $httpProphecy = $this->prophesize(Http::class);
        $httpProphecy->get('/indexes/movie/documents/999', [])->willThrow(new ApiException($response->reveal(), '{}'));

        $index = new Index($httpProphecy->reveal(), 'movie');
        $clientProphecy = $this->prophesize(Client::class);
        $clientProphecy->index('movie')->willReturn($index);

        $provider = new ItemProvider($clientProphecy->reveal(), $this->prophesize(DenormalizerInterface::class)->reveal());

        $operation = (new Get())->withClass(Movie::class)->withStateOptions(new Options(index: 'movie'));
        $result = $provider->provide($operation, ['id' => 999]);

        self::assertNull($result);
    }

    public function testProvideWrapsNon404ApiException(): void
    {
        $response = $this->prophesize(ResponseInterface::class);
        $response->getStatusCode()->willReturn(500);
        $response->getReasonPhrase()->willReturn('Internal Server Error');
        $response->getBody()->willReturn('{}');

        $httpProphecy = $this->prophesize(Http::class);
        $httpProphecy->get('/indexes/movie/documents/1', [])->willThrow(new ApiException($response->reveal(), '{}'));

        $index = new Index($httpProphecy->reveal(), 'movie');
        $clientProphecy = $this->prophesize(Client::class);
        $clientProphecy->index('movie')->willReturn($index);

        $provider = new ItemProvider($clientProphecy->reveal(), $this->prophesize(DenormalizerInterface::class)->reveal());

        $this->expectException(\ApiPlatform\State\ApiResource\Error::class);

        $operation = (new Get())->withClass(Movie::class)->withStateOptions(new Options(index: 'movie'));
        $provider->provide($operation, ['id' => 1]);
    }
}
