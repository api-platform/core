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

use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactory;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\PaginationDisabledEntity;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\ODM\MongoDB\MongoDBException;

final class PaginationDisabledTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected SchemaFactoryInterface $schemaFactory;
    private OperationMetadataFactory $operationMetadataFactory;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [PaginationDisabledEntity::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        if ($this->isMongoDB()) {
            return;
        }

        $this->recreateSchema(static::getResources());
        $this->loadFixtures();

        $this->schemaFactory = self::getContainer()->get('api_platform.json_schema.schema_factory');
        $this->operationMetadataFactory = self::getContainer()->get('api_platform.metadata.operation.metadata_factory');
    }

    /**
     * @throws \Throwable
     * @throws MongoDBException
     */
    private function loadFixtures(): void
    {
        $manager = $this->getManager();

        for ($i = 0; $i < 4; ++$i) {
            $manager->persist(new PaginationDisabledEntity());
        }

        $manager->flush();
    }

    public function testCollectionJsonApi(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('Not tested with mongodb.');
        }

        self::createClient()->request('GET', '/pagination_disabled_entities', ['headers' => ['Accept' => 'application/vnd.api+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonEquals([
            'links' => [
                'self' => '/pagination_disabled_entities',
            ],
            'meta' => [
                'totalItems' => 4,
            ],
            'data' => [
                [
                    'id' => '/pagination_disabled_entities/1',
                    'type' => 'PaginationDisabledEntity',
                    'attributes' => [
                        '_id' => 1,
                    ],
                ],
                [
                    'id' => '/pagination_disabled_entities/2',
                    'type' => 'PaginationDisabledEntity',
                    'attributes' => [
                        '_id' => 2,
                    ],
                ],
                [
                    'id' => '/pagination_disabled_entities/3',
                    'type' => 'PaginationDisabledEntity',
                    'attributes' => [
                        '_id' => 3,
                    ],
                ],
                [
                    'id' => '/pagination_disabled_entities/4',
                    'type' => 'PaginationDisabledEntity',
                    'attributes' => [
                        '_id' => 4,
                    ],
                ],
            ],
        ]);

        $this->assertMatchesResourceCollectionJsonSchema(PaginationDisabledEntity::class, format: 'jsonapi');
    }

    public function testSchemaCollectionJsonApi(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('Not tested with mongodb.');
        }

        $schema = $this->schemaFactory->buildSchema(PaginationDisabledEntity::class, 'jsonapi', operation: $this->operationMetadataFactory->create('_api_/pagination_disabled_entities{._format}_get_collection'));
        $this->assertArrayHasKey('definitions', $schema);
        $this->assertArrayHasKey('JsonApiCollectionBaseSchemaNoPagination', $schema['definitions']);
        $this->assertArrayHasKey('properties', $schema['definitions']['JsonApiCollectionBaseSchemaNoPagination']);
        $this->assertArrayHasKey('links', $schema['definitions']['JsonApiCollectionBaseSchemaNoPagination']['properties']);
        $this->assertSame(
            [
                'type' => 'object',
                'properties' => [
                    'self' => [
                        'type' => 'string',
                        'format' => 'iri-reference',
                    ],
                ],
                'example' => [
                    'self' => 'string',
                ],
            ],
            $schema['definitions']['JsonApiCollectionBaseSchemaNoPagination']['properties']['links']
        );
        $this->assertArrayHasKey('meta', $schema['definitions']['JsonApiCollectionBaseSchemaNoPagination']['properties']);
        $this->assertArrayHasKey('properties', $schema['definitions']['JsonApiCollectionBaseSchemaNoPagination']['properties']['meta']);
        $this->assertSame(
            [
                'totalItems' => [
                    'type' => 'integer',
                    'minimum' => 0,
                ],
            ],
            $schema['definitions']['JsonApiCollectionBaseSchemaNoPagination']['properties']['meta']['properties']
        );
    }

    public function testCollectionHal(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('Not tested with mongodb.');
        }

        self::createClient()->request('GET', '/pagination_disabled_entities', ['headers' => ['Accept' => 'application/hal+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonEquals([
            '_links' => [
                'self' => [
                    'href' => '/pagination_disabled_entities',
                ],
                'item' => [
                    ['href' => '/pagination_disabled_entities/1'],
                    ['href' => '/pagination_disabled_entities/2'],
                    ['href' => '/pagination_disabled_entities/3'],
                    ['href' => '/pagination_disabled_entities/4'],
                ],
            ],
            'totalItems' => 4,
            '_embedded' => [
                'item' => [
                    [
                        '_links' => [
                            'self' => ['href' => '/pagination_disabled_entities/1'],
                        ],
                        'id' => 1,
                    ],
                    [
                        '_links' => [
                            'self' => ['href' => '/pagination_disabled_entities/2'],
                        ],
                        'id' => 2,
                    ],
                    [
                        '_links' => [
                            'self' => ['href' => '/pagination_disabled_entities/3'],
                        ],
                        'id' => 3,
                    ],
                    [
                        '_links' => [
                            'self' => ['href' => '/pagination_disabled_entities/4'],
                        ],
                        'id' => 4,
                    ],
                ],
            ],
        ]);

        $this->assertMatchesResourceCollectionJsonSchema(PaginationDisabledEntity::class, format: 'jsonhal');
    }

    public function testSchemaCollectionHal(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('Not tested with mongodb.');
        }

        $schema = $this->schemaFactory->buildSchema(PaginationDisabledEntity::class, 'jsonhal', operation: $this->operationMetadataFactory->create('_api_/pagination_disabled_entities{._format}_get_collection'));

        $this->assertArrayHasKey('definitions', $schema);
        $this->assertArrayHasKey('HalCollectionBaseSchemaNoPagination', $schema['definitions']);
        $this->assertArrayHasKey('_links', $schema['definitions']['HalCollectionBaseSchemaNoPagination']['properties']);
        $this->assertSame(
            [
                'type' => 'object',
                'properties' => [
                    'self' => [
                        'type' => 'object',
                        'properties' => [
                            'href' => [
                                'type' => 'string',
                                'format' => 'iri-reference',
                            ],
                        ],
                    ],
                ],
            ],
            $schema['definitions']['HalCollectionBaseSchemaNoPagination']['properties']['_links']
        );
        $this->assertArrayNotHasKey('itemsPerPage', $schema['definitions']['HalCollectionBaseSchemaNoPagination']['properties']);
        $this->assertArrayHasKey('totalItems', $schema['definitions']['HalCollectionBaseSchemaNoPagination']['properties']);
        $this->assertSame(
            [
                'type' => 'integer',
                'minimum' => 0,
            ],
            $schema['definitions']['HalCollectionBaseSchemaNoPagination']['properties']['totalItems']
        );
    }

    public function testCollectionJsonLd(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('Not tested with mongodb.');
        }

        self::createClient()->request('GET', '/pagination_disabled_entities', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonEquals([
            '@context' => '/contexts/PaginationDisabledEntity',
            '@id' => '/pagination_disabled_entities',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 4,
            'hydra:member' => [
                [
                    '@id' => '/pagination_disabled_entities/1',
                    '@type' => 'PaginationDisabledEntity',
                    'id' => 1,
                ],
                [
                    '@id' => '/pagination_disabled_entities/2',
                    '@type' => 'PaginationDisabledEntity',
                    'id' => 2,
                ],
                [
                    '@id' => '/pagination_disabled_entities/3',
                    '@type' => 'PaginationDisabledEntity',
                    'id' => 3,
                ],

                [
                    '@id' => '/pagination_disabled_entities/4',
                    '@type' => 'PaginationDisabledEntity',
                    'id' => 4,
                ],
            ],
        ]);

        $this->assertMatchesResourceCollectionJsonSchema(PaginationDisabledEntity::class, format: 'jsonld');
    }

    public function testSchemaCollectionJsonLd(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('Not tested with mongodb.');
        }

        $schema = $this->schemaFactory->buildSchema(PaginationDisabledEntity::class, 'jsonld', operation: $this->operationMetadataFactory->create('_api_/pagination_disabled_entities{._format}_get_collection'));

        $this->assertArrayHasKey('definitions', $schema);
        $this->assertArrayHasKey('HydraCollectionBaseSchemaNoPagination', $schema['definitions']);
        $this->assertArrayNotHasKey('hydra:view', $schema['definitions']['HydraCollectionBaseSchemaNoPagination']['properties']);
        $this->assertArrayHasKey('hydra:totalItems', $schema['definitions']['HydraCollectionBaseSchemaNoPagination']['properties']);
        $this->assertSame(
            [
                'type' => 'integer',
                'minimum' => 0,
            ],
            $schema['definitions']['HydraCollectionBaseSchemaNoPagination']['properties']['hydra:totalItems']
        );
    }
}
