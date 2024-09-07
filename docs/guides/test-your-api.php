<?php
// ---
// slug: test-your-api
// name: Test your API
// executable: true
// position: 7
// tags: tests
// ---

namespace App\Tests {
    use ApiPlatform\Playground\Test\TestGuideTrait;
    use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
    use App\ApiResource\Book;

    // API Platform [testing utilities](/docs/core/testing/) provides an [ApiTestCase](/docs/reference/Symfony/Bundle/Test/ApiTestCase/)
    // that allows you to send an HTTP Request, and to perform assertions on the Response.
    final class BookTest extends ApiTestCase
    {
        use TestGuideTrait;

        public function testBookDoesNotExists(): void
        {
            // For starters we can get an [HTTP Client](/docs/reference/Symfony/Bundle/Test/Client/) with the `createClient` method.
            $client = static::createClient();
            // Then, issue an HTTP request via this client, as we didn't load any data we'd expect this to send a 404 Not found.
            $client->request(method: 'GET', url: '/books/1');
            $this->assertResponseStatusCodeSame(404);
            // Our API uses the JSON Problem specification on every thrown exception.
            $this->assertJsonContains([
                'detail' => 'Not Found',
            ]);
        }

        public function testGetCollection(): void
        {
            $response = static::createClient()->request(method: 'GET', url: '/books');

            // We provide assertions based on your resource's JSON Schema to save time asserting that the data
            // matches an expected format, for example here with a collection.
            $this->assertMatchesResourceCollectionJsonSchema(Book::class);
            // PHPUnit default assertions are also available.
            $this->assertCount(0, $response->toArray()['member']);
        }
    }
}

namespace App\ApiResource {
    use ApiPlatform\Metadata\ApiResource;
    use ApiPlatform\Metadata\CollectionOperationInterface;

    #[ApiResource(provider: [Book::class, 'provide'])]
    class Book
    {
        public string $id;

        public static function provide($operation)
        {
            return $operation instanceof CollectionOperationInterface ? [] : null;
        }
    }
}

// # Test your API

namespace App\Playground {
    use Symfony\Component\HttpFoundation\Request;

    function request(): Request
    {
        return Request::create(
            uri: '/books/1',
            method: 'GET',
            server: [
                'HTTP_ACCEPT' => 'application/ld+json',
            ]
        );
    }
}
