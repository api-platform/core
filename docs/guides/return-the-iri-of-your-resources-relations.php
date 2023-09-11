<?php
// ---
// slug: return-the-iri-of-your-resources-relations
// name: How to return an IRI instead of an object for your resources relations ?
// executable: true
// tags: serialization
// ---

// This guide shows you how to expose the IRI of a related (sub)ressource relation instead of an object.

namespace App\ApiResource {
    use ApiPlatform\Metadata\ApiProperty;
    use ApiPlatform\Metadata\ApiResource;
    use ApiPlatform\Metadata\Get;
    use ApiPlatform\Metadata\GetCollection;
    use ApiPlatform\Metadata\Link;
    use ApiPlatform\Metadata\Operation;

    #[ApiResource(
        operations: [
            new Get(provider: Brand::class.'::provide'),
        ],
    )]
    class Brand
    {
        public function __construct(
            #[ApiProperty(identifier: true)]
            public readonly int $id = 1,

            public readonly string $name = 'Anon',

            // Setting uriTemplate on a relation with a resource collection will try to find the related operation.
            // It is based on the uriTemplate set on the operation defined on the Car resource (see below).
            /**
             * @var array<int, Car> $cars
             */
            #[ApiProperty(uriTemplate: '/brands/{brandId}/cars')]
            private array $cars = [],

            // Setting uriTemplate on a relation with a resource item will try to find the related operation.
            // It is based on the uriTemplate set on the operation defined on the Address resource (see below).
            #[ApiProperty(uriTemplate: '/brands/{brandId}/addresses/{id}')]
            private ?Address $headQuarters = null
        )
        {
        }

        /**
         * @return array<int, Car>
         */
        public function getCars(): array
        {
            return $this->cars;
        }

        public function addCar(Car $car): self
        {
            $car->setBrand($this);
            $this->cars[] = $car;

            return $this;
        }

        public function getHeadQuarters(): ?Address
        {
            return $this->headQuarters;
        }

        public function setHeadQuarters(?Address $headQuarters): self
        {
            $headQuarters?->setBrand($this);
            $this->headQuarters = $headQuarters;

            return $this;
        }

        public static function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
        {
            return (new Brand(1, 'Ford'))
                    ->setHeadQuarters(new Address(1, 'One American Road near Michigan Avenue, Dearborn, Michigan'))
                    ->addCar(new Car(1, 'Torpedo Roadster'));
        }
    }

    #[ApiResource(
        operations: [
            new Get,
            // Without the use of uriTemplate on the property this would be used coming from the Brand resource, but not anymore.
            new GetCollection(uriTemplate: '/cars'),
            // This operation will be used to create the IRI instead since the uriTemplate matches.
            new GetCollection(
                uriTemplate: '/brands/{brandId}/cars',
                uriVariables: [
                    'brandId' => new Link(toProperty: 'brand', fromClass: Brand::class),
                ]
            ),
        ],
    )]
    class Car
    {
        public function __construct(
            #[ApiProperty(identifier: true)]
            public readonly int $id = 1,
            public readonly string $name = 'Anon',
            private ?Brand $brand = null
        )
        {
        }

        public function getBrand(): Brand
        {
            return $this->brand;
        }

        public function setBrand(Brand $brand): void
        {
            $this->brand = $brand;
        }
    }

    #[ApiResource(
        operations: [
            // Without the use of uriTemplate on the property this would be used coming from the Brand resource, but not anymore.
            new Get(uriTemplate: '/addresses/{id}'),
            // This operation will be used to create the IRI instead since the uriTemplate matches.
            new Get(
                uriTemplate: '/brands/{brandId}/addresses/{id}',
                uriVariables: [
                    'brandId' => new Link(toProperty: 'brand', fromClass: Brand::class),
                    'id' => new Link(fromClass: Address::class),
                ]
            )
        ],
    )]
    class Address
    {
        public function __construct(
            #[ApiProperty(identifier: true)]
            public readonly int $id = 1,
            public readonly string $name = 'Anon',
            private ?Brand $brand = null
        )
        {
        }

        public function getBrand(): Brand
        {
            return $this->brand;
        }

        public function setBrand(Brand $brand): void
        {
            $this->brand = $brand;
        }
    }
}

// If API Platform does not find any `GetCollection` operation on the target resource, it will result in a `NotFoundException`.
//
// The **OpenAPI** documentation will set the properties as `read-only` of type `string` in the format `iri-reference` for `JSON-LD`, `JSON:API` and `HAL` formats.
//
// The **Hydra** documentation will set the properties as `hydra:Link` with the right domain, with `hydra:readable` to `true` but `hydra:writable` to `false`.
//
// When using JSON:API or HAL formats, the IRI will be used and set links, embedded and relationship.
//
// *Additional Note:* If you are using the default doctrine provider, this will prevent unnecessary sql join and related processing.

namespace App\Playground {
    use Symfony\Component\HttpFoundation\Request;

    function request(): Request
    {
        return Request::create(uri: '/brands/1', method: 'GET', server: ['HTTP_ACCEPT' => 'application/ld+json']);
    }
}


namespace App\Tests {
    use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
    use App\ApiResource\Brand;

    final class BrandTest extends ApiTestCase
    {

        public function testResourceExposeIRI(): void
        {
            static::createClient()->request('GET', '/brands/1', ['headers' => [
                'Accept: application/ld+json'
            ]]);

            $this->assertResponseIsSuccessful();
            $this->assertMatchesResourceCollectionJsonSchema(Brand::class, '_api_/brands/{id}{._format}_get');
            $this->assertJsonContains([
                "@context" => "/contexts/Brand",
                "@id" => "/brands/1",
                "@type" => "Brand",
                "name"=> "Ford",
                "cars" => "/brands/1/cars",
                "headQuarters" => "/brands/1/addresses/1"
            ]);
        }
    }
}
