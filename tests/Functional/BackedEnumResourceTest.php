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

namespace ApiPlatform\Tests\Functional;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\BackedEnumIntegerResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\BackedEnumStringResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6264\Availability;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6264\AvailabilityStatus;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Symfony\Component\HttpClient\HttpOptions;

final class BackedEnumResourceTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Availability::class, AvailabilityStatus::class, BackedEnumIntegerResource::class, BackedEnumStringResource::class];
    }

    public static function providerEnumItemsJson(): iterable
    {
        // Integer cases
        foreach (Availability::cases() as $case) {
            yield ['/availabilities/'.$case->value, 'application/json', ['value' => $case->value]];

            yield ['/availabilities/'.$case->value, 'application/vnd.api+json', [
                'data' => [
                    'id' => '/availabilities/'.$case->value,
                    'type' => 'Availability',
                    'attributes' => [
                        'value' => $case->value,
                    ],
                ],
            ]];

            yield ['/availabilities/'.$case->value, 'application/hal+json', [
                '_links' => [
                    'self' => [
                        'href' => '/availabilities/'.$case->value,
                    ],
                ],
                'value' => $case->value,
            ]];

            yield ['/availabilities/'.$case->value, 'application/ld+json', [
                '@context' => '/contexts/Availability',
                '@id' => '/availabilities/'.$case->value,
                '@type' => 'Availability',
                'value' => $case->value,
            ]];
        }

        // String cases
        foreach (AvailabilityStatus::cases() as $case) {
            yield ['/availability_statuses/'.$case->value, 'application/json', ['value' => $case->value]];

            yield ['/availability_statuses/'.$case->value, 'application/vnd.api+json', [
                'data' => [
                    'id' => '/availability_statuses/'.$case->value,
                    'type' => 'AvailabilityStatus',
                    'attributes' => [
                        'value' => $case->value,
                    ],
                ],
            ]];

            yield ['/availability_statuses/'.$case->value, 'application/hal+json', [
                '_links' => [
                    'self' => [
                        'href' => '/availability_statuses/'.$case->value,
                    ],
                ],
                'value' => $case->value,
            ]];

            yield ['/availability_statuses/'.$case->value, 'application/ld+json', [
                '@context' => '/contexts/AvailabilityStatus',
                '@id' => '/availability_statuses/'.$case->value,
                '@type' => 'AvailabilityStatus',
                'value' => $case->value,
            ]];
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerEnumItemsJson')]
    public function testItemJson(string $uri, string $mimeType, array $expected): void
    {
        self::createClient()->request('GET', $uri, ['headers' => ['Accept' => $mimeType]]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonEquals($expected);
    }

    public function testCollectionJson(): void
    {
        self::createClient()->request('GET', '/availabilities', ['headers' => ['Accept' => 'application/json']]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonEquals([
            ['value' => 0],
            ['value' => 10],
            ['value' => 200],
        ]);
    }

    public function testCollectionJsonApi(): void
    {
        self::createClient()->request('GET', '/availabilities', ['headers' => ['Accept' => 'application/vnd.api+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonEquals([
            'links' => [
                'self' => '/availabilities',
            ],
            'meta' => [
                'totalItems' => 3,
            ],
            'data' => [
                [
                    'id' => '/availabilities/0',
                    'type' => 'Availability',
                    'attributes' => [
                        'value' => 0,
                    ],
                ],
                [
                    'id' => '/availabilities/10',
                    'type' => 'Availability',
                    'attributes' => [
                        'value' => 10,
                    ],
                ],
                [
                    'id' => '/availabilities/200',
                    'type' => 'Availability',
                    'attributes' => [
                        'value' => 200,
                    ],
                ],
            ],
        ]);
    }

    public function testCollectionHal(): void
    {
        self::createClient()->request('GET', '/availabilities', ['headers' => ['Accept' => 'application/hal+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonEquals([
            '_links' => [
                'self' => [
                    'href' => '/availabilities',
                ],
                'item' => [
                    ['href' => '/availabilities/0'],
                    ['href' => '/availabilities/10'],
                    ['href' => '/availabilities/200'],
                ],
            ],
            'totalItems' => 3,
            '_embedded' => [
                'item' => [
                    [
                        '_links' => [
                            'self' => ['href' => '/availabilities/0'],
                        ],
                        'value' => 0,
                    ],
                    [
                        '_links' => [
                            'self' => ['href' => '/availabilities/10'],
                        ],
                        'value' => 10,
                    ],
                    [
                        '_links' => [
                            'self' => ['href' => '/availabilities/200'],
                        ],
                        'value' => 200,
                    ],
                ],
            ],
        ]);
    }

    public function testCollectionJsonLd(): void
    {
        self::createClient()->request('GET', '/availabilities', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonEquals([
            '@context' => '/contexts/Availability',
            '@id' => '/availabilities',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 3,
            'hydra:member' => [
                [
                    '@id' => '/availabilities/0',
                    '@type' => 'Availability',
                    'value' => 0,
                ],
                [
                    '@id' => '/availabilities/10',
                    '@type' => 'Availability',
                    'value' => 10,
                ],
                [
                    '@id' => '/availabilities/200',
                    '@type' => 'Availability',
                    'value' => 200,
                ],
            ],
        ]);
    }

    public static function providerEnums(): iterable
    {
        yield 'Int enum collection' => [BackedEnumIntegerResource::class, GetCollection::class, '_api_/backed_enum_integer_resources{._format}_get_collection'];
        yield 'Int enum item' => [BackedEnumIntegerResource::class, Get::class, '_api_/backed_enum_integer_resources/{id}{._format}_get'];

        yield 'String enum collection' => [BackedEnumStringResource::class, GetCollection::class, '_api_/backed_enum_string_resources{._format}_get_collection'];
        yield 'String enum item' => [BackedEnumStringResource::class, Get::class, '_api_/backed_enum_string_resources/{id}{._format}_get'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerEnums')]
    public function testOnlyGetOperationsAddedWhenNonSpecified(string $resourceClass, string $operationClass, string $operationName): void
    {
        $factory = self::getContainer()->get('api_platform.metadata.resource.metadata_collection_factory');
        $resourceMetadata = $factory->create($resourceClass);

        $this->assertCount(1, $resourceMetadata);
        $resource = $resourceMetadata[0];
        $operations = iterator_to_array($resource->getOperations());
        $this->assertCount(2, $operations);

        $this->assertInstanceOf($operationClass, $operations[$operationName]);
    }

    public function testEnumsAreAssignedValuePropertyAsIdentifierByDefault(): void
    {
        $linkFactory = self::getContainer()->get('api_platform.metadata.resource.link_factory');
        $result = $linkFactory->completeLink(new Link(fromClass: BackedEnumIntegerResource::class));
        $identifiers = $result->getIdentifiers();

        $this->assertCount(1, $identifiers);
        $this->assertNotContains('id', $identifiers);
        $this->assertContains('value', $identifiers);
    }

    public static function providerCollection(): iterable
    {
        yield 'JSON' => ['application/json', [
            [
                'name' => 'Yes',
                'value' => 1,
                'description' => 'We say yes',
            ],
            [
                'name' => 'No',
                'value' => 2,
                'description' => 'Computer says no',
            ],
            [
                'name' => 'Maybe',
                'value' => 3,
                'description' => 'Let me think about it',
            ],
        ]];

        yield 'JSON:API' => ['application/vnd.api+json',  [
            'links' => [
                'self' => '/backed_enum_integer_resources',
            ],
            'meta' => [
                'totalItems' => 3,
            ],
            'data' => [
                [
                    'id' => '/backed_enum_integer_resources/1',
                    'type' => 'BackedEnumIntegerResource',
                    'attributes' => [
                        'name' => 'Yes',
                        'value' => 1,
                        'description' => 'We say yes',
                    ],
                ],
                [
                    'id' => '/backed_enum_integer_resources/2',
                    'type' => 'BackedEnumIntegerResource',
                    'attributes' => [
                        'name' => 'No',
                        'value' => 2,
                        'description' => 'Computer says no',
                    ],
                ],
                [
                    'id' => '/backed_enum_integer_resources/3',
                    'type' => 'BackedEnumIntegerResource',
                    'attributes' => [
                        'name' => 'Maybe',
                        'value' => 3,
                        'description' => 'Let me think about it',
                    ],
                ],
            ],
        ]];

        yield 'LD+JSON' => ['application/ld+json', [
            '@context' => '/contexts/BackedEnumIntegerResource',
            '@id' => '/backed_enum_integer_resources',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 3,
            'hydra:member' => [
                [
                    '@id' => '/backed_enum_integer_resources/1',
                    '@type' => 'BackedEnumIntegerResource',
                    'name' => 'Yes',
                    'value' => 1,
                    'description' => 'We say yes',
                ],
                [
                    '@id' => '/backed_enum_integer_resources/2',
                    '@type' => 'BackedEnumIntegerResource',
                    'name' => 'No',
                    'value' => 2,
                    'description' => 'Computer says no',
                ],
                [
                    '@id' => '/backed_enum_integer_resources/3',
                    '@type' => 'BackedEnumIntegerResource',
                    'name' => 'Maybe',
                    'value' => 3,
                    'description' => 'Let me think about it',
                ],
            ],
        ]];

        yield 'HAL+JSON' => ['application/hal+json',  [
            '_links' => [
                'self' => [
                    'href' => '/backed_enum_integer_resources',
                ],
                'item' => [
                    [
                        'href' => '/backed_enum_integer_resources/1',
                    ],
                    [
                        'href' => '/backed_enum_integer_resources/2',
                    ],
                    [
                        'href' => '/backed_enum_integer_resources/3',
                    ],
                ],
            ],
            'totalItems' => 3,
            '_embedded' => [
                'item' => [
                    [
                        '_links' => [
                            'self' => [
                                'href' => '/backed_enum_integer_resources/1',
                            ],
                        ],
                        'name' => 'Yes',
                        'value' => 1,
                        'description' => 'We say yes',
                    ],
                    [
                        '_links' => [
                            'self' => [
                                'href' => '/backed_enum_integer_resources/2',
                            ],
                        ],
                        'name' => 'No',
                        'value' => 2,
                        'description' => 'Computer says no',
                    ],
                    [
                        '_links' => [
                            'self' => [
                                'href' => '/backed_enum_integer_resources/3',
                            ],
                        ],
                        'name' => 'Maybe',
                        'value' => 3,
                        'description' => 'Let me think about it',
                    ],
                ],
            ],
        ]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerCollection')]
    public function testCollection(string $mimeType, array $expected): void
    {
        self::createClient()->request('GET', '/backed_enum_integer_resources', ['headers' => ['Accept' => $mimeType]]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonEquals($expected);
    }

    public static function providerItem(): iterable
    {
        yield 'JSON' => ['application/json', [
            'name' => 'Yes',
            'value' => 1,
            'description' => 'We say yes',
        ]];

        yield 'JSON:API' => ['application/vnd.api+json',  [
            'data' => [
                'id' => '/backed_enum_integer_resources/1',
                'type' => 'BackedEnumIntegerResource',
                'attributes' => [
                    'name' => 'Yes',
                    'value' => 1,
                    'description' => 'We say yes',
                ],
            ],
        ]];

        yield 'JSON:HAL' => ['application/hal+json',  [
            '_links' => [
                'self' => [
                    'href' => '/backed_enum_integer_resources/1',
                ],
            ],
            'name' => 'Yes',
            'value' => 1,
            'description' => 'We say yes',
        ]];

        yield 'LD+JSON' => ['application/ld+json',  [
            '@context' => '/contexts/BackedEnumIntegerResource',
            '@id' => '/backed_enum_integer_resources/1',
            '@type' => 'BackedEnumIntegerResource',
            'name' => 'Yes',
            'value' => 1,
            'description' => 'We say yes',
        ]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerItem')]
    public function testItem(string $mimeType, array $expected): void
    {
        self::createClient()->request('GET', '/backed_enum_integer_resources/1', ['headers' => ['Accept' => $mimeType]]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonEquals($expected);
    }

    public static function provider404s(): iterable
    {
        yield ['/backed_enum_integer_resources/42'];
        yield ['/backed_enum_integer_resources/fortytwo'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provider404s')]
    public function testItem404(string $uri): void
    {
        self::createClient()->request('GET', $uri);

        $this->assertResponseStatusCodeSame(404);
    }

    public static function providerEnumItemsGraphQl(): iterable
    {
        // Integer cases
        $query = <<<'GRAPHQL'
query GetAvailability($identifier: ID!) {
    availability(id: $identifier) {
        value
    }
}
GRAPHQL;
        foreach (Availability::cases() as $case) {
            yield [$query, ['identifier' => '/availabilities/'.$case->value], ['data' => ['availability' => ['value' => $case->value]]]];
        }

        // String cases
        $query = <<<'GRAPHQL'
query GetAvailabilityStatus($identifier: ID!) {
    availabilityStatus(id: $identifier) {
        value
    }
}
GRAPHQL;
        foreach (AvailabilityStatus::cases() as $case) {
            yield [$query, ['identifier' => '/availability_statuses/'.$case->value], ['data' => ['availabilityStatus' => ['value' => $case->value]]]];
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerEnumItemsGraphQl')]
    #[\PHPUnit\Framework\Attributes\Group('legacy')]
    public function testItemGraphql(string $query, array $variables, array $expected): void
    {
        $options = (new HttpOptions())
            ->setJson(['query' => $query, 'variables' => $variables])
            ->setHeaders(['Content-Type' => 'application/json']);
        self::createClient()->request('POST', '/graphql', $options->toArray());

        $this->assertResponseIsSuccessful();
        $this->assertJsonEquals($expected);
    }

    #[\PHPUnit\Framework\Attributes\Group('legacy')]
    public function testCollectionGraphQl(): void
    {
        $query = <<<'GRAPHQL'
query {
  backedEnumIntegerResources {
    value
  }
}
GRAPHQL;
        $options = (new HttpOptions())
            ->setJson(['query' => $query, 'variables' => []])
            ->setHeaders(['Content-Type' => 'application/json']);
        self::createClient()->request('POST', '/graphql', $options->toArray());

        $this->assertResponseIsSuccessful();
        $this->assertJsonEquals([
            'data' => [
                'backedEnumIntegerResources' => [
                    ['value' => 1],
                    ['value' => 2],
                    ['value' => 3],
                ],
            ],
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Group('legacy')]
    public function testItemGraphQlInteger(): void
    {
        $query = <<<'GRAPHQL'
query GetBackedEnumIntegerResource($identifier: ID!) {
    backedEnumIntegerResource(id: $identifier) {
        name
        value
        description
    }
}
GRAPHQL;
        $options = (new HttpOptions())
            ->setJson(['query' => $query, 'variables' => ['identifier' => '/backed_enum_integer_resources/1']])
            ->setHeaders(['Content-Type' => 'application/json']);
        self::createClient()->request('POST', '/graphql', $options->toArray());

        $this->assertResponseIsSuccessful();
        $this->assertJsonEquals([
            'data' => [
                'backedEnumIntegerResource' => [
                    'description' => 'We say yes',
                    'name' => 'Yes',
                    'value' => 1,
                ],
            ],
        ]);
    }
}
