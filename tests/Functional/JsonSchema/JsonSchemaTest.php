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

namespace ApiPlatform\Tests\Functional\JsonSchema;

use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactoryInterface;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Constraint\MatchesJsonSchema;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\AggregateRating;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5452\Book;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5501\BrokenDocs;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5501\Related;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Product;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ResourceWithEnumProperty;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5793\BagOfTests;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5793\TestEntity;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue6212\Nest;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\JsonSchemaResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\JsonSchemaResourceRelated;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use JsonSchema\Validator;
use PHPUnit\Framework\Attributes\DataProvider;

class JsonSchemaTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected SchemaFactoryInterface $schemaFactory;
    protected OperationMetadataFactoryInterface $operationMetadataFactory;

    protected static ?bool $alwaysBootKernel = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->schemaFactory = self::getContainer()->get('api_platform.json_schema.schema_factory');
        $this->operationMetadataFactory = self::getContainer()->get('api_platform.metadata.operation.metadata_factory');
    }

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [
            BagOfTests::class,
            TestEntity::class,
            BrokenDocs::class,
            Related::class,
            Nest::class,
            ResourceWithEnumProperty::class,
            Book::class,
            JsonSchemaResource::class,
            JsonSchemaResourceRelated::class,
            Product::class,
            AggregateRating::class,
        ];
    }

    #[DataProvider('getInvalidSchemas')]
    public function testSchemaIsNotValid(string $json, array $args): void
    {
        $schema = $this->schemaFactory->buildSchema(...$args);
        $validator = new Validator();
        $json = json_decode($json, null, 512, \JSON_THROW_ON_ERROR);
        $validator->validate($json, $schema->getArrayCopy());
        $this->assertFalse($validator->isValid());
    }

    /**
     * @return array<string, array{string, array}>
     */
    public static function getInvalidSchemas(): array
    {
        return [
            'json-ld' => [
                '{"@context":"/contexts/BagOfTests","@id":"/bag_of_tests/1","@type":"BagOfTests","id":1,"description":"string","tests":"a string","nonResourceTests":[{"id":1,"nullableString":"string","nullableInt":0}],"type":{"@type":"TestEntity","id":1,"nullableString":"string","nullableInt":0}}',
                [BagOfTests::class, 'jsonld'],
            ],
        ];
    }

    #[DataProvider('getSchemas')]
    public function testSchemaIsValid(string $json, array $args): void
    {
        $schema = $this->schemaFactory->buildSchema(...$args);
        $validator = new Validator();
        $json = json_decode($json, null, 512, \JSON_THROW_ON_ERROR);
        $validator->validate($json, $schema->getArrayCopy());
        $this->assertTrue($validator->isValid());
    }

    /**
     * @return array<string, array{string, array}>
     */
    public static function getSchemas(): array
    {
        return [
            'json-ld' => [
                '{"@context":"/contexts/BagOfTests","@id":"/bag_of_tests/1","@type":"BagOfTests","id":1,"description":"string","tests":"a string","nonResourceTests":[{"id":1,"nullableString":"string","nullableInt":0}],"type":{"@id":"/test_entities/1","@type":"TestEntity","id":1,"nullableString":"string","nullableInt":0}}',
                [BagOfTests::class, 'jsonld'],
            ],
            'json-ld Collection' => [
                '{"@context":"/contexts/BagOfTests","@id":"/bag_of_tests","@type":"hydra:Collection","hydra:totalItems":1,"hydra:member":[{"@id":"/bag_of_tests/1","@type":"BagOfTests","id":1,"description":"string","nonResourceTests":[],"type":{"@id":"/test_entities/1","@type":"TestEntity","id":1,"nullableString":"string","nullableInt":0}}]}',
                [BagOfTests::class, 'jsonld', 'forceCollection' => true],
            ],
        ];
    }

    /**
     * Test issue #5501, the locations relation inside BrokenDocs is a Resource (named Related) but its only operation is a NotExposed.
     * Still, serializer groups are set, and therefore it is a "readableLink" so we actually want to compute the schema, even if it's not accessible
     * directly, it is accessible through that relation.
     */
    public function testExecuteWithNotExposedResourceAndReadableLink(): void
    {
        $schema = $this->schemaFactory->buildSchema(BrokenDocs::class, 'jsonld');

        $this->assertArrayHasKey('Related.jsonld-location.read_collection', $schema['definitions']);
    }

    public function testArraySchemaWithReference(): void
    {
        if ('mongodb' === self::getContainer()->getParameter('kernel.environment')) {
            $this->markTestSkipped();
        }

        $schema = $this->schemaFactory->buildSchema(BagOfTests::class, 'jsonld', Schema::TYPE_INPUT);

        $this->assertEquals($schema['definitions']['BagOfTests-write']['properties']['tests'], new \ArrayObject([
            'type' => 'string',
            'foo' => 'bar',
        ]));

        $this->assertEquals($schema['definitions']['BagOfTests-write']['properties']['nonResourceTests'], new \ArrayObject([
            'type' => 'array',
            'items' => [
                '$ref' => '#/definitions/NonResourceTestEntity-write',
            ],
        ]));

        $this->assertEquals($schema['definitions']['BagOfTests-write']['properties']['description'], new \ArrayObject([
            'maxLength' => 255,
        ]));

        $this->assertEquals($schema['definitions']['BagOfTests-write']['properties']['type'], new \ArrayObject([
            '$ref' => '#/definitions/TestEntity-write',
        ]));
    }

    public function testResourceWithEnumPropertiesSchema(): void
    {
        $json = $this->schemaFactory->buildSchema(ResourceWithEnumProperty::class, 'jsonld', Schema::TYPE_OUTPUT);
        $properties = $json['definitions']['ResourceWithEnumProperty.jsonld']['allOf'][1]['properties'];

        $this->assertEquals(
            new \ArrayObject([
                'type' => ['integer', 'null'],
                'enum' => [1, 2, 3, null],
            ]),
            $properties['intEnum']
        );
        $this->assertEquals(
            new \ArrayObject([
                'type' => 'array',
                'items' => [
                    'enum' => ['yes', 'no', 'maybe'],
                    'type' => 'string',
                ],
            ]),
            $properties['stringEnum']
        );
        $this->assertEquals(
            new \ArrayObject([
                'type' => ['string', 'null'],
                'enum' => ['male', 'female', null],
            ]),
            $properties['gender']
        );
        $this->assertEquals(
            new \ArrayObject([
                'type' => 'array',
                'items' => [
                    'enum' => ['male', 'female'],
                    'type' => 'string',
                ],
            ]),
            $properties['genders']
        );
    }

    public function testSchemaWithUnknownType(): void
    {
        $schema = $this->schemaFactory->buildSchema(Book::class, 'json', Schema::TYPE_OUTPUT, $this->operationMetadataFactory->create('_api_/issue-5452/books{._format}_get_collection'));
        $this->assertContains(['$ref' => '#/definitions/ActivableInterface'], $schema['definitions']['Book']['properties']['library']['anyOf']);
        $this->assertContains(['$ref' => '#/definitions/TimestampableInterface'], $schema['definitions']['Book']['properties']['library']['anyOf']);
    }

    public function testReadOnlySchema(): void
    {
        if ('mongodb' === self::getContainer()->getParameter('kernel.environment')) {
            $this->markTestSkipped();
        }

        $schema = $this->schemaFactory->buildSchema(JsonSchemaResource::class, 'json', Schema::TYPE_OUTPUT);
        $this->assertTrue($schema['definitions']['Resource']['properties']['resourceRelated']['readOnly'] ?? false);
    }

    public function testGenIdFalse()
    {
        $schema = $this->schemaFactory->buildSchema(Product::class, 'jsonld', Schema::TYPE_OUTPUT, $this->operationMetadataFactory->create('_api_/json-stream-products_get_collection'));
        $this->assertThat(['member' => [['aggregateRating' => ['ratingValue' => '1.0', 'reviewCount' => 1]]]], new MatchesJsonSchema($schema));
    }
}
