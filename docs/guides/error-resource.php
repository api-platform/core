<?php
// ---
// slug: error-resource
// name: Error resource for domain exceptions
// position: 7
// executable: true
// tags: design, state
// ---

namespace App\ApiResource {
    use ApiPlatform\Metadata\ErrorResource;
    use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;

    // We create a `MyDomainException` marked as an `ErrorResource`
    // It implements ProblemExceptionInterface as we want to be compatible with the [JSON Problem rfc7807](https://datatracker.ietf.org/doc/rfc7807/)
    #[ErrorResource]
    class MyDomainException extends \Exception implements ProblemExceptionInterface
    {
        public function getType(): string
        {
            return '/errors/418';
        }

        public function getTitle(): ?string
        {
            return 'Teapot error';
        }

        public function getStatus(): ?int
        {
            return 418;
        }

        public function getDetail(): ?string
        {
            return $this->getMessage();
        }

        public function getInstance(): ?string
        {
            return null;
        }

        public string $myCustomField = 'I usually prefer coffee.';
    }

    use ApiPlatform\Metadata\ApiResource;
    use ApiPlatform\Metadata\Get;
    use ApiPlatform\Metadata\Operation;

    #[ApiResource(
        operations: [
            new Get(provider: Book::class.'::provide'),
        ],
    )]
    class Book
    {
        public function __construct(
            public readonly int $id = 1,
            public readonly string $name = 'Anon',
        ) {
        }

        public static function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
        {
            // We throw our domain exception
            throw new MyDomainException('I am teapot');
        }
    }
}

namespace App\Tests {
    use ApiPlatform\Playground\Test\TestGuideTrait;
    use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

    final class BookTest extends ApiTestCase
    {
        use TestGuideTrait;

        public function testBookDoesNotExists(): void
        {
            static::createClient()->request('GET', '/books/1', options: ['headers' => ['accept' => 'application/ld+json']]);
            // We expect the status code returned by our `getStatus` and the message inside `detail`
            // for security reasons 500 errors will get their "detail" changed by our Error Provider
            // you can override this by looking at the [Error Provider guide](/docs/guides/error-provider).
            $this->assertResponseStatusCodeSame(418);
            $this->assertJsonContains([
                '@id' => '/my_domain_exceptions',
                '@type' => 'MyDomainException',
                'type' => '/errors/418',
                'title' => 'Teapot error',
                'detail' => 'I am teapot',
                'myCustomField' => 'I usually prefer coffee.'
            ]);
        }
    }
}

namespace App\Playground {
    use Symfony\Component\HttpFoundation\Request;

    function request(): Request
    {
        return Request::create('/books/1.jsonld', 'GET');
    }
}
