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

use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5793\BagOfTests;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5793\TestEntity;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use JsonSchema\Validator;
use PHPUnit\Framework\Attributes\DataProvider;

class SchemaTest extends ApiTestCase
{
    use SetupClassResourcesTrait;
    private static ?SchemaFactoryInterface $schemaFactory = null;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [BagOfTests::class, TestEntity::class];
    }

    private static function getSchemaFactory(): SchemaFactoryInterface
    {
        if (static::$schemaFactory) {
            return static::$schemaFactory;
        }

        $container = static::getContainer();
        /** @var SchemaFactoryInterface $schemaFactory */
        $schemaFactory = $container->get('api_platform.json_schema.schema_factory');

        return static::$schemaFactory = $schemaFactory;
    }

    #[DataProvider('getInvalidSchemas')]
    public function testSchemaIsNotValid(string $json, Schema $schema): void
    {
        $validator = new Validator();
        $json = json_decode($json, null, 512, \JSON_THROW_ON_ERROR);
        $validator->validate($json, $schema->getArrayCopy());
        $this->assertFalse($validator->isValid());
    }


    #[DataProvider('getSchemas')]
    public function testSchemaIsValid(string $json, Schema $schema): void
    {
        $validator = new Validator();
        $json = json_decode($json, null, 512, \JSON_THROW_ON_ERROR);
        $validator->validate($json, $schema->getArrayCopy());
        $this->assertTrue($validator->isValid());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function getSchemas(): array
    {
        return [
            'json-ld' => [
                '{"@context":"/contexts/BagOfTests","@id":"/bag_of_tests/1","@type":"BagOfTests","id":1,"description":"string","tests":"a string","nonResourceTests":[{"id":1,"nullableString":"string","nullableInt":0}],"type":{"@id":"/test_entities/1","@type":"TestEntity","id":1,"nullableString":"string","nullableInt":0}}',
                static::getSchemaFactory()->buildSchema(BagOfTests::class, 'jsonld'),
            ],
            'json-ld Collection' => [
                '{"@context":"/contexts/BagOfTests","@id":"/bag_of_tests","@type":"hydra:Collection","hydra:totalItems":1,"hydra:member":[{"@id":"/bag_of_tests/1","@type":"BagOfTests","id":1,"description":"string","nonResourceTests":[],"type":{"@id":"/test_entities/1","@type":"TestEntity","id":1,"nullableString":"string","nullableInt":0}}]}',
                static::getSchemaFactory()->buildSchema(BagOfTests::class, 'jsonld', forceCollection: true),
            ]
        ];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function getInvalidSchemas(): array
    {
        return [
            'json-ld' => [
                '{"@context":"/contexts/BagOfTests","@id":"/bag_of_tests/1","@type":"BagOfTests","id":1,"description":"string","tests":"a string","nonResourceTests":[{"id":1,"nullableString":"string","nullableInt":0}],"type":{"@type":"TestEntity","id":1,"nullableString":"string","nullableInt":0}}',
                static::getSchemaFactory()->buildSchema(BagOfTests::class, 'jsonld'),
            ],
        ];
    }
}
