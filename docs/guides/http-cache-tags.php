<?php
// ---
// slug: http-cache-tags
// name: Customize HTTP Cache Tags
// executable: true
// position: 21
// ---

// This guide describes how to customize HTTP Cache Tags included in the API response header by registering a TagCollector service. In this example, the Id 
// of a resource will be used to generate the cache tag instead of its IRI.
// 
// If you only want to add additional tags without changing them, it might be sufficient to follow the [standard documentation on http cache](https://api-platform.com/docs/core/performance/#extending-cache-tags-for-invalidation).
//
// First, we need our book resource. For the sake of this example, we assume the id to be unique across all resources (otherwise replacing the IRI with Id would make no sense).
namespace App\ApiResource {
    use ApiPlatform\Metadata\ApiResource;
    use ApiPlatform\Metadata\ApiProperty;
    use ApiPlatform\Metadata\Get;
    use ApiPlatform\Metadata\Operation;

    #[ApiResource(
        operations: [
            new Get(provider: Book::class.'::provide'),
        ],
    )]
    class Book
    {
        #[ApiProperty(identifier: true)]
        public string $id = "unique-id-1";

        public static function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
        {
            return (new self());
        }
    }
}

// Now, we define our TagCollector service. Check the PHPDoc in TagCollectorInterface to see the various information, which is available within `$context`.
//
// Add any tags you want to be included in the response to `$context['resources']`.

namespace App\Service {
    use ApiPlatform\Serializer\TagCollectorInterface;
    use App\ApiResource\Book;

    class TagCollector implements TagCollectorInterface
    {
        public function collect(array $context = []): void 
        {
            if (isset($context['property_metadata'])) {
                return;
            }

            $iri = $context['iri'] ?? null;
            $object = $context['object'] ?? null;

            if ($object && $object instanceof Book) {
                $iri = $object->id;
            }

            if (!$iri) {
                return;
            }

            $context['resources'][$iri] = $iri;
        }
    }
}

// Replace the service 'api_platform.http_cache.tag_collector' with your new TagCollector class.

namespace App\DependencyInjection {
    use App\Service\TagCollector;
    use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

    function configure(ContainerConfigurator $configurator): void
    {
        $services = $configurator->services();
        $services->set('api_platform.http_cache.tag_collector', TagCollector::class);
    }
}


// Request the book. When using Swagger UI, you should see that your 'Cache-Tags' header now includes the Id only instead of the full Iri.

namespace App\Playground {
    use Symfony\Component\HttpFoundation\Request;

    function request(): Request
    {
        return Request::create('/books/unique-id-1.jsonld', 'GET');
    }
}

// ...or verify directly in testing.

namespace App\Tests {
    use ApiPlatform\Playground\Test\TestGuideTrait;
    use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

    final class BookTest extends ApiTestCase
    {
        use TestGuideTrait;

        public function testAsAnonymousICanAccessTheDocumentation(): void
        {
            $response = static::createClient()->request('GET', '/books/unique-id-1.jsonld');

            $this->assertResponseIsSuccessful();
            $this->assertResponseHeaderSame('Cache-Tags', 'unique-id-1');
            $this->assertJsonContains([
                '@id' => '/books/unique-id-1',
                '@type' => 'Book',
                'id' => 'unique-id-1',
            ]);
        }
    }
}


// If you rely on invalidation from Api-Platform, don't forget that you'd also need to have your own implementation of `PurgeHttpCacheListener`. Otherwise the wrong tags will be purged.
