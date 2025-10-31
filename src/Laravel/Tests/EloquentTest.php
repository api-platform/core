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

namespace ApiPlatform\Laravel\Tests;

use ApiPlatform\Laravel\Test\ApiTestAssertionsTrait;
use ApiPlatform\Laravel\workbench\app\Enums\BookStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Workbench\App\Http\Requests\StoreSlotRequest;
use Workbench\App\Models\PostWithMorphMany;
use Workbench\Database\Factories\AreaFactory;
use Workbench\Database\Factories\AuthorFactory;
use Workbench\Database\Factories\AuthorWithGroupFactory;
use Workbench\Database\Factories\BookFactory;
use Workbench\Database\Factories\BookWithRelationFactory;
use Workbench\Database\Factories\CommentMorphFactory;
use Workbench\Database\Factories\GrandSonFactory;
use Workbench\Database\Factories\PostWithMorphManyFactory;
use Workbench\Database\Factories\TimeSlotFactory;
use Workbench\Database\Factories\WithAccessorFactory;

class EloquentTest extends TestCase
{
    use ApiTestAssertionsTrait;
    use RefreshDatabase;
    use WithWorkbench;

    public function testBackedEnumsNormalization(): void
    {
        BookFactory::new([
            'status' => BookStatus::DRAFT,
        ])->has(AuthorFactory::new())->count(10)->create();

        $response = $this->get('/api/books', ['Accept' => ['application/ld+json']]);
        $book = $response->json()['member'][0];

        $this->assertArrayHasKey('status', $book);
        $this->assertSame('DRAFT', $book['status']);
    }

    public function testSearchFilter(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();

        $response = $this->get('/api/books', ['Accept' => ['application/ld+json']]);
        $book = $response->json()['member'][0];

        $response = $this->get('/api/books?isbn='.$book['isbn'], ['Accept' => ['application/ld+json']]);
        $this->assertSame($response->json()['member'][0], $book);
    }

    public function testValidateSearchFilter(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();

        $response = $this->get('/api/books?isbn=a', ['Accept' => ['application/ld+json']]);
        $this->assertSame($response->json()['detail'], 'The isbn field must be at least 2 characters.');
    }

    public function testSearchFilterRelation(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();

        $response = $this->get('/api/books?author=1', ['Accept' => ['application/ld+json']]);
        $this->assertSame($response->json()['member'][0]['author'], '/api/authors/1');
    }

    public function testPropertyFilter(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();

        $response = $this->get('/api/books', ['Accept' => ['application/ld+json']]);
        $book = $response->json()['member'][0];

        $response = $this->get(\sprintf('%s.jsonld?properties[]=author', $book['@id']));
        $book = $response->json();

        $this->assertArrayHasKey('@id', $book);
        $this->assertArrayHasKey('author', $book);
        $this->assertArrayNotHasKey('name', $book);
    }

    public function testPartialSearchFilter(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();

        $response = $this->get('/api/books', ['Accept' => ['application/ld+json']]);
        $book = $response->json()['member'][0];

        if (!isset($book['name'])) {
            throw new \UnexpectedValueException();
        }

        $end = strpos($book['name'], ' ') ?: 3;
        $name = substr($book['name'], 0, $end);

        $response = $this->get('/api/books?name='.$name, ['Accept' => ['application/ld+json']]);
        $this->assertSame($response->json()['member'][0], $book);
    }

    public function testDateFilterEqual(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();

        $response = $this->get('/api/books', ['Accept' => ['application/ld+json']]);
        $book = $response->json()['member'][0];
        $updated = $this->patchJson(
            $book['@id'],
            ['publicationDate' => '2024-02-18 00:00:00'],
            [
                'Accept' => ['application/ld+json'],
                'Content-Type' => ['application/merge-patch+json'],
            ]
        );

        $response = $this->get('/api/books?publicationDate[eq]='.$updated['publicationDate'], ['Accept' => ['application/ld+json']]);
        $this->assertSame($response->json()['member'][0]['@id'], $book['@id']);
    }

    public function testDateFilterIncludeNull(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();

        $response = $this->get('/api/books', ['Accept' => ['application/ld+json']]);
        $book = $response->json()['member'][0];
        $updated = $this->patchJson(
            $book['@id'],
            ['publicationDate' => null],
            [
                'Accept' => ['application/ld+json'],
                'Content-Type' => ['application/merge-patch+json'],
            ]
        );

        $response = $this->get('/api/books?publicationWithNulls[gt]=9999-12-31', ['Accept' => ['application/ld+json']]);
        $this->assertGreaterThan(0, $response->json()['totalItems']);
    }

    public function testDateFilterExcludeNull(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();

        $response = $this->get('/api/books', ['Accept' => ['application/ld+json']]);
        $book = $response->json()['member'][0];
        $updated = $this->patchJson(
            $book['@id'],
            ['publicationDate' => null],
            [
                'Accept' => ['application/ld+json'],
                'Content-Type' => ['application/merge-patch+json'],
            ]
        );

        $response = $this->get('/api/books?publicationDate[gt]=9999-12-31', ['Accept' => ['application/ld+json']]);
        $this->assertSame(0, $response->json()['totalItems']);
    }

    public function testDateFilterGreaterThan(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();

        $response = $this->get('/api/books', ['Accept' => ['application/ld+json']]);
        $bookBefore = $response->json()['member'][0];
        $updated = $this->patchJson(
            $bookBefore['@id'],
            ['publicationDate' => '9998-02-18 00:00:00'],
            [
                'Accept' => ['application/ld+json'],
                'Content-Type' => ['application/merge-patch+json'],
            ]
        );

        $bookAfter = $response->json()['member'][1];
        $this->patchJson(
            $bookAfter['@id'],
            ['publicationDate' => '9999-02-18 00:00:00'],
            [
                'Accept' => ['application/ld+json'],
                'Content-Type' => ['application/merge-patch+json'],
            ]
        );

        $response = $this->get('/api/books?publicationDate[gt]='.$updated['publicationDate'], ['Accept' => ['application/ld+json']]);
        $this->assertSame($response->json()['member'][0]['@id'], $bookAfter['@id']);
        $this->assertSame($response->json()['totalItems'], 1);
    }

    public function testDateFilterLowerThanEqual(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();
        $response = $this->get('/api/books', ['Accept' => ['application/ld+json']]);
        $bookBefore = $response->json()['member'][0];
        $this->patchJson(
            $bookBefore['@id'],
            ['publicationDate' => '0001-02-18 00:00:00'],
            [
                'Accept' => ['application/ld+json'],
                'Content-Type' => ['application/merge-patch+json'],
            ]
        );

        $bookAfter = $response->json()['member'][1];
        $this->patchJson(
            $bookAfter['@id'],
            ['publicationDate' => '0002-02-18 00:00:00'],
            [
                'Accept' => ['application/ld+json'],
                'Content-Type' => ['application/merge-patch+json'],
            ]
        );

        $response = $this->get('/api/books?publicationDate[lte]=0002-02-18', ['Accept' => ['application/ld+json']]);
        $this->assertSame($response->json()['member'][0]['@id'], $bookBefore['@id']);
        $this->assertSame($response->json()['member'][1]['@id'], $bookAfter['@id']);
        $this->assertSame($response->json()['totalItems'], 2);
    }

    public function testDateFilterBetween(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();
        $response = $this->get('/api/books', ['Accept' => ['application/ld+json']]);
        $book = $response->json()['member'][0];
        $updated = $this->patchJson(
            $book['@id'],
            ['publicationDate' => '0001-02-18 00:00:00'],
            [
                'Accept' => ['application/ld+json'],
                'Content-Type' => ['application/merge-patch+json'],
            ]
        );

        $book2 = $response->json()['member'][1];
        $this->patchJson(
            $book2['@id'],
            ['publicationDate' => '0002-02-18 00:00:00'],
            [
                'Accept' => ['application/ld+json'],
                'Content-Type' => ['application/merge-patch+json'],
            ]
        );

        $book3 = $response->json()['member'][2];
        $updated3 = $this->patchJson(
            $book3['@id'],
            ['publicationDate' => '0003-02-18 00:00:00'],
            [
                'Accept' => ['application/ld+json'],
                'Content-Type' => ['application/merge-patch+json'],
            ]
        );

        $response = $this->get('/api/books?publicationDate[gte]='.substr($updated['publicationDate'], 0, 10).'&publicationDate[lt]='.substr($updated3['publicationDate'], 0, 10), ['Accept' => ['application/ld+json']]);
        $this->assertSame($response->json()['member'][0]['@id'], $book['@id']);
        $this->assertSame($response->json()['member'][1]['@id'], $book2['@id']);
        $this->assertSame($response->json()['totalItems'], 2);
    }

    public function testSearchFilterWithPropertyPlaceholder(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();
        $response = $this->get('/api/authors', ['Accept' => ['application/ld+json']])->json();
        $author = $response['member'][0];

        $test = $this->get('/api/authors?name='.explode(' ', $author['name'])[0], ['Accept' => ['application/ld+json']])->json();
        $this->assertSame($test['member'][0]['id'], $author['id']);

        $test = $this->get('/api/authors?id='.$author['id'], ['Accept' => ['application/ld+json']])->json();
        $this->assertSame($test['member'][0]['id'], $author['id']);
    }

    public function testOrderFilterWithPropertyPlaceholder(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();
        $res = $this->get('/api/authors?order[id]=desc', ['Accept' => ['application/ld+json']])->json();
        $this->assertSame($res['member'][0]['id'], 10);
    }

    public function testOrFilter(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();
        $response = $this->get('/api/books', ['Accept' => ['application/ld+json']])->json()['member'];
        $book = $response[0];
        $book2 = $response[1];

        $res = $this->get(\sprintf('/api/books?name2[]=%s&name2[]=%s', $book['name'], $book2['name']), ['Accept' => ['application/ld+json']])->json();
        $this->assertSame($res['totalItems'], 2);
    }

    public function testRangeLowerThanFilter(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();
        $response = $this->get('/api/books', ['Accept' => ['application/ld+json']]);
        $bookBefore = $response->json()['member'][0];
        $this->patchJson(
            $bookBefore['@id'],
            ['isbn' => '12'],
            [
                'Accept' => ['application/ld+json'],
                'Content-Type' => ['application/merge-patch+json'],
            ]
        );

        $bookAfter = $response->json()['member'][1];
        $updated = $this->patchJson(
            $bookAfter['@id'],
            ['isbn' => '15'],
            [
                'Accept' => ['application/ld+json'],
                'Content-Type' => ['application/merge-patch+json'],
            ]
        );

        $response = $this->get('api/books?isbn_range[lt]='.$updated['isbn'], ['Accept' => ['application/ld+json']]);
        $this->assertSame($response->json()['member'][0]['@id'], $bookBefore['@id']);
        $this->assertSame($response->json()['totalItems'], 1);
    }

    public function testRangeLowerThanEqualFilter(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();
        $response = $this->get('/api/books', ['Accept' => ['application/ld+json']]);
        $bookBefore = $response->json()['member'][0];
        $this->patchJson(
            $bookBefore['@id'],
            ['isbn' => '12'],
            [
                'Accept' => ['application/ld+json'],
                'Content-Type' => ['application/merge-patch+json'],
            ]
        );

        $bookAfter = $response->json()['member'][1];
        $updated = $this->patchJson(
            $bookAfter['@id'],
            ['isbn' => '15'],
            [
                'Accept' => ['application/ld+json'],
                'Content-Type' => ['application/merge-patch+json'],
            ]
        );

        $response = $this->get('api/books?isbn_range[lte]='.$updated['isbn'], ['Accept' => ['application/ld+json']]);
        $this->assertSame($response->json()['member'][0]['@id'], $bookBefore['@id']);
        $this->assertSame($response->json()['member'][1]['@id'], $bookAfter['@id']);
        $this->assertSame($response->json()['totalItems'], 2);
    }

    public function testRangeGreaterThanFilter(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();
        $response = $this->get('/api/books', ['Accept' => ['application/ld+json']]);
        $bookBefore = $response->json()['member'][0];
        $updated = $this->patchJson(
            $bookBefore['@id'],
            ['isbn' => '999999999999998'],
            [
                'Accept' => ['application/ld+json'],
                'Content-Type' => ['application/merge-patch+json'],
            ]
        );

        $bookAfter = $response->json()['member'][1];
        $this->patchJson(
            $bookAfter['@id'],
            ['isbn' => '999999999999999'],
            [
                'Accept' => ['application/ld+json'],
                'Content-Type' => ['application/merge-patch+json'],
            ]
        );

        $response = $this->get('api/books?isbn_range[gt]='.$updated['isbn'], ['Accept' => ['application/ld+json']]);
        $this->assertSame($response->json()['member'][0]['@id'], $bookAfter['@id']);
        $this->assertSame($response->json()['totalItems'], 1);
    }

    public function testRangeGreaterThanEqualFilter(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();
        $response = $this->get('/api/books', ['Accept' => ['application/ld+json']]);
        $bookBefore = $response->json()['member'][0];
        $updated = $this->patchJson(
            $bookBefore['@id'],
            ['isbn' => '999999999999998'],
            [
                'Accept' => ['application/ld+json'],
                'Content-Type' => ['application/merge-patch+json'],
            ]
        );

        $bookAfter = $response->json()['member'][1];
        $this->patchJson(
            $bookAfter['@id'],
            ['isbn' => '999999999999999'],
            [
                'Accept' => ['application/ld+json'],
                'Content-Type' => ['application/merge-patch+json'],
            ]
        );
        $response = $this->get('api/books?isbn_range[gte]='.$updated['isbn'], ['Accept' => ['application/ld+json']]);
        $json = $response->json();
        $this->assertSame($json['member'][0]['@id'], $bookBefore['@id']);
        $this->assertSame($json['member'][1]['@id'], $bookAfter['@id']);
        $this->assertSame($json['totalItems'], 2);
    }

    public function testWrongOrderFilter(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();
        $res = $this->get('/api/authors?order[name]=something', ['Accept' => ['application/ld+json']]);
        $this->assertEquals($res->getStatusCode(), 422);
    }

    public function testWithAccessor(): void
    {
        WithAccessorFactory::new()->create();
        $res = $this->get('/api/with_accessors/1', ['Accept' => ['application/ld+json']]);
        $this->assertArraySubset(['name' => 'test'], $res->json());
    }

    public function testBooleanFilter(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();
        $res = $this->get('/api/books?published=notabool', ['Accept' => ['application/ld+json']]);
        $this->assertEquals($res->getStatusCode(), 422);

        $res = $this->get('/api/books?published=0', ['Accept' => ['application/ld+json']]);
        $this->assertEquals($res->getStatusCode(), 200);
        $this->assertEquals($res->json()['totalItems'], 0);
    }

    public function testBelongsTo(): void
    {
        GrandSonFactory::new()->count(1)->create();

        $res = $this->get('/api/grand_sons/1/grand_father', ['Accept' => ['application/ld+json']]);
        $json = $res->json();
        $this->assertEquals($json['@id'], '/api/grand_sons/1/grand_father');
        $this->assertEquals($json['sons'][0], '/api/grand_sons/1');
    }

    public function testHasMany(): void
    {
        GrandSonFactory::new()->count(1)->create();

        $res = $this->get('/api/grand_fathers/1/grand_sons', ['Accept' => ['application/ld+json']]);
        $json = $res->json();
        $this->assertEquals($json['@id'], '/api/grand_fathers/1/grand_sons');
        $this->assertEquals($json['totalItems'], 1);
        $this->assertEquals($json['member'][0]['@id'], '/api/grand_sons/1');
    }

    public function testRelationIsHandledOnCreateWithNestedData(): void
    {
        $cartData = [
            'productSku' => 'SKU_TEST_001',
            'quantity' => 2,
            'priceAtAddition' => '19.99',
            'shoppingCart' => [
                'userIdentifier' => 'user-'.Str::uuid()->toString(),
                'status' => 'active',
            ],
        ];

        $response = $this->postJson('/api/cart_items', $cartData, ['accept' => 'application/ld+json', 'content-type' => 'application/ld+json']);
        $response->assertStatus(201);

        $response
            ->assertJson([
                '@context' => '/api/contexts/CartItem',
                '@id' => '/api/cart_items/1',
                '@type' => 'CartItem',
                'id' => 1,
                'productSku' => 'SKU_TEST_001',
                'quantity' => 2,
                'priceAtAddition' => 19.99,
                'shoppingCart' => [
                    '@id' => '/api/shopping_carts/1',
                    '@type' => 'ShoppingCart',
                    'userIdentifier' => $cartData['shoppingCart']['userIdentifier'],
                    'status' => 'active',
                ],
            ]);
    }

    public function testRelationIsHandledOnCreateWithNestedDataToMany(): void
    {
        $cartData = [
            'userIdentifier' => 'user-'.Str::uuid()->toString(),
            'status' => 'active',
            'cartItems' => [
                [
                    'productSku' => 'SKU_TEST_001',
                    'quantity' => 2,
                    'priceAtAddition' => '19.99',
                ],
                [
                    'productSku' => 'SKU_TEST_002',
                    'quantity' => 1,
                    'priceAtAddition' => '25.50',
                ],
            ],
        ];

        $response = $this->postJson('/api/shopping_carts', $cartData, ['accept' => 'application/ld+json', 'content-type' => 'application/ld+json']);
        $response->assertStatus(201);
        $response->assertJson([
            '@context' => '/api/contexts/ShoppingCart',
            '@id' => '/api/shopping_carts/1',
            '@type' => 'ShoppingCart',
            'id' => 1,
            'userIdentifier' => $cartData['userIdentifier'],
            'status' => 'active',
            'cartItems' => [
                [
                    '@id' => '/api/cart_items/1',
                    '@type' => 'CartItem',
                    'productSku' => 'SKU_TEST_001',
                    'quantity' => 2,
                    'priceAtAddition' => '19.99',
                ],
                [
                    '@id' => '/api/cart_items/2',
                    '@type' => 'CartItem',
                    'productSku' => 'SKU_TEST_002',
                    'quantity' => 1,
                    'priceAtAddition' => '25.50',
                ],
            ],
        ]);
    }

    public function testPostWithEmptyMorphMany(): void
    {
        $response = $this->postJson('/api/post_with_morph_manies', [
            'title' => 'My first post',
            'content' => 'This is the content of my first post.',
            'comments' => [['content' => 'hello']],
        ], ['accept' => 'application/ld+json', 'content-type' => 'application/ld+json']);
        $response->assertStatus(201);
        $response->assertJson([
            'title' => 'My first post',
            'content' => 'This is the content of my first post.',
            'comments' => [['content' => 'hello']],
        ]);
    }

    public function testPostCommentsCollectionFromMorphMany(): void
    {
        PostWithMorphManyFactory::new()->create();

        CommentMorphFactory::new()->count(5)->create([
            'commentable_id' => 1,
            'commentable_type' => PostWithMorphMany::class,
        ]);

        $response = $this->getJson('/api/post_with_morph_manies/1/comments', [
            'accept' => 'application/ld+json',
        ]);
        $response->assertStatus(200);
        $response->assertJsonCount(5, 'member');
    }

    public function testPostCommentItemFromMorphMany(): void
    {
        PostWithMorphManyFactory::new()->create();

        CommentMorphFactory::new()->count(5)->create([
            'commentable_id' => 1,
            'commentable_type' => PostWithMorphMany::class,
        ])->first();

        $response = $this->getJson('/api/post_with_morph_manies/1/comments/1', [
            'accept' => 'application/ld+json',
        ]);
        $response->assertStatus(200);
        $response->assertJson([
            '@context' => '/api/contexts/CommentMorph',
            '@id' => '/api/post_with_morph_manies/1/comments/1',
            '@type' => 'CommentMorph',
            'id' => 1,
        ]);
    }

    public function testCreateDeliveryRequestWithPickupSlot(): void
    {
        $pickupTimeSlot = TimeSlotFactory::new()->create(['note' => 'Morning slot']);

        $response = $this->postJson('/api/delivery_requests', [
            'pickupTimeSlot' => '/api/time_slots/'.$pickupTimeSlot->id, // @phpstan-ignore-line
            'note' => 'This is a test note.',
        ], ['accept' => 'application/ld+json', 'content-type' => 'application/ld+json']);

        $response->assertStatus(201);
        $response->assertJson([
            '@context' => '/api/contexts/DeliveryRequest',
            '@id' => '/api/delivery_requests/1',
            '@type' => 'DeliveryRequest',
            'pickupTimeSlot' => [
                '@id' => '/api/time_slots/'.$pickupTimeSlot->id, // @phpstan-ignore-line
                '@type' => 'TimeSlot',
                'name' => $pickupTimeSlot->name, // @phpstan-ignore-line
                'note' => $pickupTimeSlot->note, // @phpstan-ignore-line
            ],
            'note' => 'This is a test note.',
        ]);
    }

    public function testIriIsNotDenormalizedBeforeFormRequestValidation(): void
    {
        $area = AreaFactory::new()->create();

        $this->postJson(
            '/api/slots',
            [
                'name' => 'Morning Slot',
                'area' => '/api/areas/'.$area->id, // @phpstan-ignore-line
            ],
            ['accept' => 'application/ld+json', 'content-type' => 'application/ld+json']
        )->assertStatus(201);

        $this->assertSame(StoreSlotRequest::$receivedArea->name, $area->name); // @phpstan-ignore-line
    }

    public function testSerializationGroupsOnRelationMethod(): void
    {
        $author = AuthorWithGroupFactory::new()->create();
        $book = BookWithRelationFactory::new(['author_with_group_id' => $author->id])->create(); // @phpstan-ignore-line

        $response = $this->get('/api/book_with_relations/'.$book->id, ['Accept' => ['application/ld+json']]); // @phpstan-ignore-line
        $response->assertStatus(200);
        $json = $response->json();

        $this->assertArrayHasKey('authorWithGroup', $json);
        $this->assertSame('/api/author_with_groups/'.$author->id, $json['authorWithGroup']['@id']); // @phpstan-ignore-line
    }
}
