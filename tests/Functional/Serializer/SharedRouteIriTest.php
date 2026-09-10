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

namespace ApiPlatform\Tests\Functional\Serializer;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\SharedRouteIri\CategoryProjection;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\SharedRouteIri\CategoryResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\SharedRouteIri\TopicResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class SharedRouteIriTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [CategoryResource::class, CategoryProjection::class, TopicResource::class];
    }

    public function testBorrowedRouteIriCanBeWrittenBackToTheSameRelation(): void
    {
        $client = self::createClient();
        $response = $client->request('GET', '/shared_iri/topics/7', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        self::assertResponseIsSuccessful();
        $categoryIri = $response->toArray()['category'];
        self::assertSame('/shared_iri/categories/1', $categoryIri);

        $client->request('POST', '/shared_iri/topics', [
            'headers' => ['Content-Type' => 'application/ld+json', 'Accept' => 'application/ld+json'],
            'json' => ['title' => 'Hello', 'category' => $categoryIri],
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertJsonContains(['id' => 42, 'title' => 'Hello', 'category' => $categoryIri]);
    }

    public function testUnrelatedResourceIriIsRejected(): void
    {
        self::createClient()->request('POST', '/shared_iri/topics', [
            'headers' => ['Content-Type' => 'application/ld+json', 'Accept' => 'application/ld+json'],
            'json' => ['title' => 'Hello', 'category' => '/shared_iri/topics/1'],
        ]);

        self::assertResponseStatusCodeSame(400);
        self::assertJsonContains(['detail' => 'Invalid IRI "/shared_iri/topics/1".']);
    }
}
