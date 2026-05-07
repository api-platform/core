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

namespace ApiPlatform\Tests\Functional\JsonLd;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\UriTemplateCar;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CollectionReferencingItem;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5662\Book;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5662\Review;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ItemReferencedInCollection;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class ItemUriTemplateHydraTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [
            UriTemplateCar::class,
            CollectionReferencingItem::class,
            ItemReferencedInCollection::class,
            Book::class,
            Review::class,
        ];
    }

    public function testGetCollectionDerivesItemIriFromFirstGetOperation(): void
    {
        $response = self::createClient()->request('GET', '/jsonld_uri_template_cars', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('/contexts/JsonLdUriTemplateCar', $body['@context']);
        $this->assertSame('/jsonld_uri_template_cars', $body['@id']);
        $this->assertSame('hydra:Collection', $body['@type']);
        $this->assertCount(2, $body['hydra:member']);
        foreach ($body['hydra:member'] as $member) {
            $this->assertMatchesRegularExpression('#^/jsonld_uri_template_cars/.+$#', $member['@id']);
            $this->assertSame('JsonLdUriTemplateCar', $member['@type']);
        }
    }

    public function testGetCollectionWithItemUriTemplateUsesIt(): void
    {
        $response = self::createClient()->request('GET', '/jsonld_uri_template_brands/renault/cars', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('/jsonld_uri_template_brands/renault/cars', $body['@id']);
        foreach ($body['hydra:member'] as $member) {
            $this->assertMatchesRegularExpression('#^/jsonld_uri_template_brands/renault/cars/.+$#', $member['@id']);
        }
    }

    public function testPostWithoutItemUriTemplateUsesFirstGetOperation(): void
    {
        $response = self::createClient()->request('POST', '/jsonld_uri_template_cars', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/json',
            ],
            'json' => ['owner' => 'Vincent'],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $body = $response->toArray();
        $this->assertMatchesRegularExpression('#^/jsonld_uri_template_cars/.+$#', $body['@id']);
        $this->assertSame('JsonLdUriTemplateCar', $body['@type']);
    }

    public function testPostWithItemUriTemplateUsesIt(): void
    {
        $response = self::createClient()->request('POST', '/jsonld_uri_template_brands/renault/cars', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/json',
            ],
            'json' => ['owner' => 'Vincent'],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $body = $response->toArray();
        $this->assertMatchesRegularExpression('#^/jsonld_uri_template_brands/renault/cars/.+$#', $body['@id']);
    }

    public function testCollectionReferencingAnotherResource(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $response = self::createClient()->request('GET', '/item_referenced_in_collection', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/CollectionReferencingItem',
            '@id' => '/item_referenced_in_collection',
            '@type' => 'hydra:Collection',
            'hydra:member' => [
                ['@id' => '/item_referenced_in_collection/a', '@type' => 'ItemReferencedInCollection', 'id' => 'a', 'name' => 'hello'],
                ['@id' => '/item_referenced_in_collection/b', '@type' => 'ItemReferencedInCollection', 'id' => 'b', 'name' => 'you'],
            ],
            'hydra:totalItems' => 2,
        ]);
    }

    public function testCollectionReferencingItemUriTemplate(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $response = self::createClient()->request('GET', '/issue5662/books/a/reviews', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('/contexts/Review', $body['@context']);
        $this->assertSame('/issue5662/books/a/reviews', $body['@id']);
        $this->assertSame('hydra:Collection', $body['@type']);
        $this->assertSame(2, $body['hydra:totalItems']);
        $this->assertSame('/issue5662/books/a/reviews/1', $body['hydra:member'][0]['@id']);
        $this->assertSame('/issue5662/books/b/reviews/2', $body['hydra:member'][1]['@id']);
    }

    public function testCollectionReferencingInvalidItemUriTemplateFallsBackToCollectionUri(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $response = self::createClient()->request('GET', '/issue5662/admin/reviews', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $this->assertSame('/issue5662/admin/reviews', $body['@id']);
        $this->assertSame('/issue5662/admin/reviews/1', $body['hydra:member'][0]['@id']);
        $this->assertSame('/issue5662/admin/reviews/2', $body['hydra:member'][1]['@id']);
    }

    public function testPostWithItemUriTemplateGeneratesIriFromTemplate(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $response = self::createClient()->request('POST', '/issue5662/books/a/reviews', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'json' => ['body' => 'Good book'],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $body = $response->toArray();
        $this->assertSame('/issue5662/books/a/reviews/0', $body['@id']);
    }
}
