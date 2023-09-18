<?php
// ---
// slug: extend-openapi-documentation
// name: Extend OpenAPI Documentation
// position: 11
// executable: true
// tags: openapi, expert
// ---

namespace App\ApiResource {
    use ApiPlatform\Metadata\Post;
    use ApiPlatform\OpenApi\Model\Operation;
    use ApiPlatform\OpenApi\Model\Response;
    use ApiPlatform\OpenApi\Model\RequestBody;

    #[Post(
        openapi: new Operation(
            responses: [
                '200' => new Response(description: 'Ok')
            ],
            summary: 'Add a book to the library.',
            description: 'My awesome operation',
            requestBody: new RequestBody(
                content: new \ArrayObject(
                    [
                        'application/ld+json' => [
                            'schema' => [
                                'properties' => [
                                    'id' => ['type' => 'integer', 'required' => true, 'description' => 'id']
                                ]
                            ],
                            'example' => [
                                'id' => 12345
                            ]
                        ]
                    ]
                )
            )
        )
    )]
    class Book {
    }
}

namespace App\Tests {
    use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
    use ApiPlatform\Playground\Test\TestGuideTrait;

    final class BookTest extends ApiTestCase
    {
        use TestGuideTrait;

        public function testBookDoesNotExists(): void
        {
            $response = static::createClient()->request('GET', '/docs', options: ['headers' => ['accept' => 'application/vnd.openapi+json']]);
            $this->assertResponseStatusCodeSame(200);
            $this->assertJsonContains([
                'paths' => ['/books' => ['post' => ['summary' => 'Add a book to the library.', 'description' => 'My awesome operation']]]
            ]);
        }
    }
}

