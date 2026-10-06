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
use ApiPlatform\JsonSchema\SchemaFactory;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactoryInterface;
use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaContextGroups\IgnoredInputResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaContextGroups\ReadWriteGroupedItem;
use ApiPlatform\Tests\SetupClassResourcesTrait;

class JsonSchemaExplicitContextTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    private SchemaFactoryInterface $schemaFactory;
    private OperationMetadataFactoryInterface $operationMetadataFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->schemaFactory = self::getContainer()->get('api_platform.json_schema.schema_factory');
        $this->operationMetadataFactory = self::getContainer()->get('api_platform.metadata.operation.metadata_factory');
    }

    public static function getResources(): array
    {
        return [ReadWriteGroupedItem::class, IgnoredInputResource::class];
    }

    public function testExplicitContextWithoutGroupsKeepsOperationGroups(): void
    {
        $operation = $this->operationMetadataFactory->create('/json_schema_context_groups/read_write_grouped_items/{id}', ['resource_class' => ReadWriteGroupedItem::class, 'operation_name' => '_api_/json_schema_context_groups/read_write_grouped_items/{id}_get']);

        $withContext = $this->schemaFactory->buildSchema(ReadWriteGroupedItem::class, 'json', Schema::TYPE_OUTPUT, $operation, null, ['foo' => 'bar']);
        $withoutContext = $this->schemaFactory->buildSchema(ReadWriteGroupedItem::class, 'json', Schema::TYPE_OUTPUT, $operation);

        $this->assertEquals($withoutContext->getArrayCopy(), $withContext->getArrayCopy());
        $this->assertStringEndsWith('-rw.read', $withContext->getRootDefinitionKey());
        $properties = $withContext->getDefinitions()[$withContext->getRootDefinitionKey()]['properties'];
        $this->assertArrayNotHasKey('secretToken', $properties);
        $this->assertArrayHasKey('title', $properties);
        foreach ($properties as $property) {
            $this->assertArrayNotHasKey('readOnly', $property);
            $this->assertArrayNotHasKey('writeOnly', $property);
        }
    }

    public function testOpenApi30MatchesDefaultSpecForGroupedOperation(): void
    {
        $client = self::createClient();
        $json30 = $client->request('GET', '/docs.jsonopenapi?spec_version=3.0.0', ['headers' => ['Accept' => 'application/vnd.openapi+json']])->toArray();
        $json31 = $client->request('GET', '/docs.jsonopenapi', ['headers' => ['Accept' => 'application/vnd.openapi+json']])->toArray();

        $properties30 = $this->getItemResponseProperties($json30);
        $properties31 = $this->getItemResponseProperties($json31);

        $this->assertNotEmpty($properties31);
        $this->assertArrayNotHasKey('secretToken', $properties31);
        $this->assertArrayNotHasKey('secretToken', $properties30);
        $this->assertSame(array_keys($properties31), array_keys($properties30));
        $this->assertArrayNotHasKey('ReadWriteGroupedItem', $json31['components']['schemas']);
    }

    public function testExplicitOperationWithForceSubschemaKeepsOperationGroups(): void
    {
        $operation = $this->operationMetadataFactory->create('/json_schema_context_groups/read_write_grouped_items/{id}', ['resource_class' => ReadWriteGroupedItem::class, 'operation_name' => '_api_/json_schema_context_groups/read_write_grouped_items/{id}_get']);

        $schema = $this->schemaFactory->buildSchema(ReadWriteGroupedItem::class, 'json', Schema::TYPE_OUTPUT, $operation, null, [SchemaFactory::FORCE_SUBSCHEMA => true]);

        $this->assertStringEndsWith('-rw.read', $schema->getRootDefinitionKey());
        $properties = $schema->getDefinitions()[$schema->getRootDefinitionKey()]['properties'];
        $this->assertArrayNotHasKey('secretToken', $properties);
        foreach ($properties as $property) {
            $this->assertArrayNotHasKey('readOnly', $property);
            $this->assertArrayNotHasKey('writeOnly', $property);
        }
    }

    public function testIgnoredAttributesOnInput(): void
    {
        $schema = $this->schemaFactory->buildSchema(IgnoredInputResource::class, 'json', Schema::TYPE_INPUT);

        $properties = $schema->getDefinitions()[$schema->getRootDefinitionKey()]['properties'];
        $this->assertArrayHasKey('name', $properties);
        $this->assertArrayNotHasKey('internalNote', $properties);
    }

    private function getItemResponseProperties(array $json): array
    {
        $ref = $json['paths']['/json_schema_context_groups/read_write_grouped_items/{id}']['get']['responses']['200']['content']['application/json']['schema']['$ref'];
        $definition = $json['components']['schemas'][basename($ref)];

        return $definition['properties'] ?? [];
    }
}
