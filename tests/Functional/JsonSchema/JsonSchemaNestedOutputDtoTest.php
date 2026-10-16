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
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaNestedOutputDto\AliasedRelatedWithOutput;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaNestedOutputDto\DeprecatedRelated;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaNestedOutputDto\OwnerOfAliasedRelated;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaNestedOutputDto\OwnerOfDeprecatedRelated;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaNestedOutputDto\OwnerOfRelatedWithOutput;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaNestedOutputDto\RelatedWithOutput;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class JsonSchemaNestedOutputDtoTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    private SchemaFactoryInterface $schemaFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->schemaFactory = self::getContainer()->get('api_platform.json_schema.schema_factory');
    }

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [
            OwnerOfRelatedWithOutput::class,
            RelatedWithOutput::class,
            OwnerOfAliasedRelated::class,
            AliasedRelatedWithOutput::class,
            OwnerOfDeprecatedRelated::class,
            DeprecatedRelated::class,
        ];
    }

    public function testJsonLdNestedRelatedDescribesResourceNotOutputDto(): void
    {
        $schema = $this->schemaFactory->buildSchema(OwnerOfRelatedWithOutput::class, 'jsonld');

        $this->assertSame(['id', 'internal'], $this->propertyNames($this->relatedDefinition($schema)));
    }

    public function testJsonNestedAliasedRelatedDescribesResourceNotOutputDto(): void
    {
        $schema = $this->schemaFactory->buildSchema(OwnerOfAliasedRelated::class, 'json');

        $this->assertSame(['id', 'internal'], $this->propertyNames($this->relatedDefinition($schema)));
    }

    public function testJsonLdNestedRelatedIsNotDeprecated(): void
    {
        $schema = $this->schemaFactory->buildSchema(OwnerOfDeprecatedRelated::class, 'jsonld');

        $this->assertArrayNotHasKey('deprecated', $this->objectPart($this->relatedDefinition($schema)));
    }

    public function testExplicitOperationWithForceSubschemaDescribesOutputDto(): void
    {
        /** @var ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory */
        $resourceMetadataCollectionFactory = self::getContainer()->get('api_platform.metadata.resource.metadata_collection_factory');
        $operation = $resourceMetadataCollectionFactory->create(RelatedWithOutput::class)->getOperation();

        $schema = $this->schemaFactory->buildSchema(RelatedWithOutput::class, 'json', Schema::TYPE_OUTPUT, $operation, null, [SchemaFactory::FORCE_SUBSCHEMA => true]);

        $this->assertSame(['label', 'total'], $this->propertyNames($schema->getDefinitions()[$schema->getRootDefinitionKey()]));
    }

    private function relatedDefinition(Schema $schema): array
    {
        $definitions = $schema->getDefinitions();
        $ownerProperties = $this->properties($definitions[$schema->getRootDefinitionKey()]);
        $ref = $ownerProperties['related']['$ref'];

        return (array) $definitions[substr($ref, \strlen('#/definitions/'))];
    }

    private function objectPart(\ArrayObject|array $definition): array
    {
        $definition = (array) $definition;
        foreach ($definition['allOf'] ?? [] as $part) {
            if (isset($part['properties'])) {
                return (array) $part;
            }
        }

        return $definition;
    }

    private function properties(\ArrayObject|array $definition): array
    {
        return (array) ($this->objectPart($definition)['properties'] ?? []);
    }

    private function propertyNames(\ArrayObject|array $definition): array
    {
        $names = array_keys($this->properties($definition));
        sort($names);

        return $names;
    }
}
