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

use ApiPlatform\Hal\JsonSchema\SchemaFactory as HalSchemaFactory;
use ApiPlatform\Hydra\JsonSchema\ItemDefinitionProcessor;
use ApiPlatform\Hydra\JsonSchema\SchemaFactory as HydraSchemaFactory;
use ApiPlatform\JsonApi\JsonSchema\SchemaFactory as JsonApiSchemaFactory;
use ApiPlatform\JsonSchema\Generator\ApiPlatformDefinitionPolicy;
use ApiPlatform\JsonSchema\Generator\ConfigurationFactory;
use ApiPlatform\JsonSchema\Generator\GeneratorSchemaFactory;
use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactory;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Mcp\JsonSchema\SchemaFactory as McpSchemaFactory;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaGolden\AttributeSelectedAuthor;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaGolden\AttributeSelectedBook;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaGolden\BuiltinTypeQuirks;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaGolden\GoldenBook;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaGolden\GoldenDescribedOwner;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaGolden\GoldenDescribedRelated;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaGolden\GoldenGroupedItem;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaGolden\GoldenInputResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaGolden\GoldenIntersectionResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaGolden\GoldenMultiResourceEntity;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaGolden\GoldenMultiResourceOwner;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaGolden\GoldenNestedResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaGolden\GoldenNonStandardPutChild;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaGolden\GoldenOutputDtoOwner;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaGolden\GoldenOutputDtoResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaGolden\GoldenPartialChildrenOwner;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaGolden\GoldenPatchOnlyChild;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaGolden\GoldenPlainObject;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaGolden\GoldenQueryResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaGolden\GoldenRelated;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaGolden\GoldenRelationResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaGolden\GoldenStrictInputResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaGolden\GoldenValidatedResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\McpFormatTool;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\McpBookOutputDto;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\SearchDto;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\McpBook;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\JsonSchema\SchemaGenerator;

class GoldenSchemaTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    private SchemaFactoryInterface $schemaFactory;
    private ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->schemaFactory = self::getContainer()->get('api_platform.json_schema.schema_factory');
        $this->resourceMetadataCollectionFactory = self::getContainer()->get('api_platform.metadata.resource.metadata_collection_factory');
    }

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [
            AttributeSelectedAuthor::class,
            AttributeSelectedBook::class,
            BuiltinTypeQuirks::class,
            GoldenBook::class,
            GoldenDescribedOwner::class,
            GoldenDescribedRelated::class,
            GoldenGroupedItem::class,
            GoldenInputResource::class,
            GoldenIntersectionResource::class,
            GoldenMultiResourceEntity::class,
            GoldenMultiResourceOwner::class,
            GoldenNestedResource::class,
            GoldenNonStandardPutChild::class,
            GoldenOutputDtoOwner::class,
            GoldenOutputDtoResource::class,
            GoldenPartialChildrenOwner::class,
            GoldenPatchOnlyChild::class,
            GoldenQueryResource::class,
            GoldenRelated::class,
            GoldenRelationResource::class,
            GoldenStrictInputResource::class,
            GoldenValidatedResource::class,
            McpBook::class,
            McpFormatTool::class,
        ];
    }

    /**
     * @param array<string, mixed>|null $serializerContext
     * @param array<string, mixed>      $configuration
     */
    #[DataProvider('provideCases')]
    public function testBuildSchemaMatchesGolden(string $case, string $className, string $format, string $type, ?string $operationName, ?array $serializerContext, string $version, array $configuration): void
    {
        if ('output_intersection_type' === $case) {
            $this->markTestSkipped('The old path emits anyOf for intersection types, fixed on 5.0 by fix/json-schema-intersection-allof.');
        }

        $this->assertNotEmpty($configuration);

        $operation = null;
        if ('mcp' === ($configuration['schemaFactory'] ?? null)) {
            $operation = $this->resourceMetadataCollectionFactory->create($configuration['mcpResource'] ?? $className)[0]->getMcp()[$operationName];
        } elseif (null !== $operationName) {
            $operation = $this->resourceMetadataCollectionFactory->create($className)->getOperation($operationName);
        }

        $factory = 'mcp' === ($configuration['schemaFactory'] ?? null)
            ? self::getContainer()->get('api_platform.mcp.json_schema.schema_factory')
            : $this->schemaFactory;

        $schema = $factory->buildSchema($className, $format, $type, $operation, new Schema($version), $serializerContext);

        $actual = json_encode(self::normalize([
            'schema' => $schema->getArrayCopy(false),
            'definitions' => $schema->getDefinitions(),
        ]), \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR)."\n";

        $file = __DIR__.'/golden/'.$case.'.json';
        if ('1' === getenv('UPDATE_GOLDEN')) {
            file_put_contents($file, $actual);
            $this->markTestIncomplete(\sprintf('Golden "%s" written.', $case));
        }

        $this->assertJsonStringEqualsJsonFile($file, $actual);
    }

    /**
     * @param array<string, mixed>|null $serializerContext
     * @param array<string, mixed>      $configuration
     */
    #[DataProvider('provideCases')]
    public function testGenerateMatchesGolden(string $case, string $className, string $format, string $type, ?string $operationName, ?array $serializerContext, string $version, array $configuration): void
    {
        if (!class_exists(SchemaGenerator::class)) {
            $this->markTestSkipped('symfony/json-schema is not installed.');
        }

        if ('jsonld_nested_output_dto' === $case) {
            $this->markTestSkipped('The Hydra decorator re-resolves the nested operation by format, so jsonld describes the output DTO of an embedded resource while json does not: pending decision.');
        }

        if ('groups_explicit_context_split' === $case) {
            $this->markTestSkipped('Pins the explicit-context groups bug fixed in api-platform/core#8643.');
        }

        $isMcp = 'mcp' === ($configuration['schemaFactory'] ?? null);
        $operation = null;
        if ($isMcp) {
            $operation = $this->resourceMetadataCollectionFactory->create($configuration['mcpResource'] ?? $className)[0]->getMcp()[$operationName];
        } elseif (null !== $operationName) {
            $operation = $this->resourceMetadataCollectionFactory->create($className)->getOperation($operationName);
        }

        $container = self::getContainer();
        $metadataFactory = $container->get('api_platform.metadata.resource.metadata_collection_factory');
        $resourceClassResolver = $container->get('api_platform.resource_class_resolver');
        $propertyMetadataFactory = $container->get('api_platform.metadata.property.metadata_factory');
        $policy = new ApiPlatformDefinitionPolicy($metadataFactory, $resourceClassResolver, $propertyMetadataFactory);
        $factory = new GeneratorSchemaFactory(
            new ConfigurationFactory($metadataFactory, $resourceClassResolver, $policy),
            $policy,
            $container->get('api_platform.metadata.property.name_collection_factory'),
            $propertyMetadataFactory,
            $resourceClassResolver,
            $container->has('api_platform.name_converter') ? $container->get('api_platform.name_converter') : null,
            [new ItemDefinitionProcessor($metadataFactory, $policy)],
        );
        $definitionNameFactory = $container->get('api_platform.json_schema.definition_name_factory');
        $factory = new JsonApiSchemaFactory($factory, $propertyMetadataFactory, $resourceClassResolver, $metadataFactory, $definitionNameFactory, $container->get('api_platform.jsonapi.resource_linkage_resolver'));
        $factory = new HydraSchemaFactory($factory, $container->getParameter('api_platform.serializer.default_context'), $definitionNameFactory, $metadataFactory);
        $factory = new HalSchemaFactory($factory, $definitionNameFactory, $metadataFactory);
        if ($isMcp) {
            $factory = new McpSchemaFactory($factory);
        }

        $schema = $factory->buildSchema($className, $format, $type, $operation, new Schema($version), $serializerContext);

        $actual = json_encode(self::normalize([
            'schema' => $schema->getArrayCopy(false),
            'definitions' => $schema->getDefinitions(),
        ]), \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR)."\n";

        $this->assertJsonStringEqualsJsonFile(__DIR__.'/golden/'.$case.'.json', $actual);
    }

    /**
     * @return iterable<string, array{string, string, string, string, ?string, ?array<string, mixed>, string, array<string, mixed>}>
     */
    public static function provideCases(): iterable
    {
        $openApiGroups = ['groups' => ['golden:read']];

        yield 'output_item' => ['output_item', GoldenBook::class, 'json', Schema::TYPE_OUTPUT, 'golden_book_get', null, Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'json',
            'groups' => ['golden:read'],
            'description' => 'Retrieves a golden book.',
            'deprecated' => true,
            'namePrefix' => 'GoldenBook',
        ]];

        yield 'output_collection' => ['output_collection', GoldenBook::class, 'json', Schema::TYPE_OUTPUT, 'golden_book_get_collection', null, Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'json',
            'groups' => ['golden:read'],
            'collection' => true,
            'namePrefix' => 'GoldenBook',
        ]];

        yield 'input_post_groups' => ['input_post_groups', GoldenInputResource::class, 'json', Schema::TYPE_INPUT, 'golden_input_post', null, Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'json',
            'groups' => ['golden:write'],
            'namePrefix' => 'GoldenInput',
        ]];

        yield 'input_patch_merge' => ['input_patch_merge', GoldenInputResource::class, 'json', Schema::TYPE_INPUT, 'golden_input_patch', null, Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'json',
            'groups' => ['golden:write'],
            'namePrefix' => 'GoldenInput',
        ]];

        yield 'input_put_nonstandard' => ['input_put_nonstandard', GoldenInputResource::class, 'json', Schema::TYPE_INPUT, 'golden_input_put_nonstandard', null, Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'json',
            'groups' => ['golden:write'],
            'namePrefix' => 'GoldenInput',
        ]];

        yield 'input_validation_groups' => ['input_validation_groups', GoldenValidatedResource::class, 'json', Schema::TYPE_INPUT, 'golden_validated_post', null, Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'json',
            'groups' => ['golden:write'],
            'validationGroups' => ['golden:create'],
            'namePrefix' => 'GoldenValidated',
        ]];

        yield 'output_nested_attributes' => ['output_nested_attributes', GoldenNestedResource::class, 'json', Schema::TYPE_OUTPUT, 'golden_nested_get_attributes', null, Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'json',
            'attributes' => ['name', 'author' => ['name']],
            'namePrefix' => 'GoldenNested',
        ]];

        yield 'output_attribute_selected_relation' => ['output_attribute_selected_relation', AttributeSelectedBook::class, 'json', Schema::TYPE_OUTPUT, 'attribute_selected_book_get', null, Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'json',
            'attributes' => ['title', 'author' => ['name']],
            'namePrefix' => 'AttributeSelectedBook',
        ]];

        yield 'input_attribute_selected_relation' => ['input_attribute_selected_relation', AttributeSelectedBook::class, 'json', Schema::TYPE_INPUT, 'attribute_selected_book_post', null, Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'json',
            'attributes' => ['title', 'author' => ['name']],
            'namePrefix' => 'AttributeSelectedBook',
        ]];

        yield 'output_ignored_attributes' => ['output_ignored_attributes', GoldenNestedResource::class, 'json', Schema::TYPE_OUTPUT, 'golden_nested_get_ignored', null, Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'json',
            'ignoredAttributes' => ['secret'],
            'namePrefix' => 'GoldenNested',
        ]];

        yield 'input_no_extra_attributes' => ['input_no_extra_attributes', GoldenStrictInputResource::class, 'json', Schema::TYPE_INPUT, 'golden_strict_input_post', null, Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'json',
            'allowExtraAttributes' => false,
            'namePrefix' => 'GoldenStrictInput',
        ]];

        yield 'output_definition_name' => ['output_definition_name', GoldenBook::class, 'json', Schema::TYPE_OUTPUT, 'golden_book_get', $openApiGroups + [SchemaFactory::OPENAPI_DEFINITION_NAME => 'Custom'], Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'json',
            'groups' => ['golden:read'],
            'definitionName' => 'Custom',
            'description' => 'Retrieves a golden book.',
            'deprecated' => true,
            'namePrefix' => 'GoldenBook',
        ]];

        yield 'output_dto' => ['output_dto', GoldenOutputDtoResource::class, 'json', Schema::TYPE_OUTPUT, 'golden_output_dto_get', null, Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'json',
            'namePrefix' => 'GoldenOutputDtoResource',
        ]];

        yield 'output_relation_iri_vs_embed' => ['output_relation_iri_vs_embed', GoldenRelationResource::class, 'json', Schema::TYPE_OUTPUT, 'golden_relation_get', null, Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'json',
            'groups' => ['golden:read', 'golden:embed'],
            'namePrefix' => 'GoldenRelation',
        ]];

        yield 'output_gen_id_false' => ['output_gen_id_false', GoldenRelationResource::class, 'json', Schema::TYPE_OUTPUT, 'golden_relation_get_noid', null, Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'json',
            'groups' => ['golden:noid'],
            'namePrefix' => 'GoldenRelation',
        ]];

        yield 'non_resource_class' => ['non_resource_class', GoldenPlainObject::class, 'json', Schema::TYPE_OUTPUT, null, null, Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'json',
        ]];

        yield 'input_query' => ['input_query', GoldenQueryResource::class, 'json', Schema::TYPE_INPUT, 'golden_query', null, Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'json',
            'namePrefix' => 'GoldenQuery',
        ]];

        yield 'dialect_openapi' => ['dialect_openapi', GoldenBook::class, 'json', Schema::TYPE_OUTPUT, 'golden_book_get', null, Schema::VERSION_OPENAPI, [
            'dialect' => 'openApi31',
            'format' => 'json',
            'groups' => ['golden:read'],
            'description' => 'Retrieves a golden book.',
            'deprecated' => true,
            'namePrefix' => 'GoldenBook',
        ]];

        yield 'dialect_swagger' => ['dialect_swagger', GoldenBook::class, 'json', Schema::TYPE_OUTPUT, 'golden_book_get', null, Schema::VERSION_SWAGGER, [
            'dialect' => 'swagger20',
            'format' => 'json',
            'groups' => ['golden:read'],
            'description' => 'Retrieves a golden book.',
            'namePrefix' => 'GoldenBook',
        ]];

        yield 'groups_normal' => ['groups_normal', GoldenGroupedItem::class, 'json', Schema::TYPE_OUTPUT, 'golden_grouped_item_get', null, Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'json',
            'groups' => ['golden:read'],
            'namePrefix' => 'GoldenGroupedItem',
        ]];

        yield 'groups_explicit_context_split' => ['groups_explicit_context_split', GoldenGroupedItem::class, 'json', Schema::TYPE_OUTPUT, 'golden_grouped_item_get', ['foo' => 'bar'], Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'json',
            'readGroups' => ['golden:read'],
            'writeGroups' => ['golden:write'],
            'namePrefix' => 'GoldenGroupedItem',
        ]];

        yield 'jsonld_output_item' => ['jsonld_output_item', GoldenBook::class, 'jsonld', Schema::TYPE_OUTPUT, 'golden_book_get', null, Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'jsonld',
            'groups' => ['golden:read'],
            'namePrefix' => 'GoldenBook',
        ]];

        yield 'jsonld_output_collection' => ['jsonld_output_collection', GoldenBook::class, 'jsonld', Schema::TYPE_OUTPUT, 'golden_book_get_collection', null, Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'jsonld',
            'groups' => ['golden:read'],
            'collection' => true,
            'namePrefix' => 'GoldenBook',
        ]];

        yield 'jsonld_input_post' => ['jsonld_input_post', GoldenInputResource::class, 'jsonld', Schema::TYPE_INPUT, 'golden_input_post', null, Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'jsonld',
            'groups' => ['golden:write'],
            'namePrefix' => 'GoldenInput',
        ]];

        yield 'jsonld_relation_iri_vs_embed' => ['jsonld_relation_iri_vs_embed', GoldenRelationResource::class, 'jsonld', Schema::TYPE_OUTPUT, 'golden_relation_get', null, Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'jsonld',
            'groups' => ['golden:read', 'golden:embed'],
            'namePrefix' => 'GoldenRelation',
        ]];

        yield 'jsonld_gen_id_false' => ['jsonld_gen_id_false', GoldenRelationResource::class, 'jsonld', Schema::TYPE_OUTPUT, 'golden_relation_get_noid', null, Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'jsonld',
            'groups' => ['golden:noid'],
            'namePrefix' => 'GoldenRelation',
        ]];

        yield 'jsonhal_output_item' => ['jsonhal_output_item', GoldenBook::class, 'jsonhal', Schema::TYPE_OUTPUT, 'golden_book_get', null, Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'jsonhal',
            'groups' => ['golden:read'],
            'namePrefix' => 'GoldenBook',
        ]];

        yield 'jsonhal_relation_iri_vs_embed' => ['jsonhal_relation_iri_vs_embed', GoldenRelationResource::class, 'jsonhal', Schema::TYPE_OUTPUT, 'golden_relation_get', null, Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'jsonhal',
            'groups' => ['golden:read', 'golden:embed'],
            'namePrefix' => 'GoldenRelation',
        ]];

        yield 'jsonhal_output_collection' => ['jsonhal_output_collection', GoldenBook::class, 'jsonhal', Schema::TYPE_OUTPUT, 'golden_book_get_collection', null, Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'jsonhal',
            'groups' => ['golden:read'],
            'collection' => true,
            'namePrefix' => 'GoldenBook',
        ]];

        yield 'jsonapi_output_item' => ['jsonapi_output_item', GoldenBook::class, 'jsonapi', Schema::TYPE_OUTPUT, 'golden_book_get', null, Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'jsonapi',
            'groups' => ['golden:read'],
            'namePrefix' => 'GoldenBook',
        ]];

        yield 'jsonapi_relation_iri_vs_embed' => ['jsonapi_relation_iri_vs_embed', GoldenRelationResource::class, 'jsonapi', Schema::TYPE_OUTPUT, 'golden_relation_get', null, Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'jsonapi',
            'groups' => ['golden:read', 'golden:embed'],
            'namePrefix' => 'GoldenRelation',
        ]];

        yield 'jsonapi_input_post' => ['jsonapi_input_post', GoldenInputResource::class, 'jsonapi', Schema::TYPE_INPUT, 'golden_input_post', null, Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'jsonapi',
            'groups' => ['golden:write'],
            'namePrefix' => 'GoldenInput',
        ]];

        yield 'multi_resource_nested_output' => ['multi_resource_nested_output', GoldenMultiResourceOwner::class, 'json', Schema::TYPE_OUTPUT, 'golden_multi_resource_owner_get', null, Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'json',
            'groups' => ['golden:read'],
            'namePrefix' => 'GoldenMultiResourceOwner',
        ]];

        yield 'multi_resource_nested_input' => ['multi_resource_nested_input', GoldenMultiResourceOwner::class, 'json', Schema::TYPE_INPUT, 'golden_multi_resource_owner_post', null, Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'json',
            'groups' => ['golden:write'],
            'namePrefix' => 'GoldenMultiResourceOwner',
        ]];

        yield 'input_nested_partial_children' => ['input_nested_partial_children', GoldenPartialChildrenOwner::class, 'json', Schema::TYPE_INPUT, 'golden_partial_children_owner_post', null, Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'json',
            'groups' => ['golden:write'],
            'namePrefix' => 'GoldenPartialChildrenOwner',
        ]];

        yield 'output_builtin_type_quirks' => ['output_builtin_type_quirks', BuiltinTypeQuirks::class, 'json', Schema::TYPE_OUTPUT, 'builtin_type_quirks_get', null, Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'json',
            'namePrefix' => 'BuiltinTypeQuirks',
        ]];

        yield 'output_intersection_type' => ['output_intersection_type', GoldenIntersectionResource::class, 'json', Schema::TYPE_OUTPUT, 'golden_intersection_get', null, Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'json',
            'namePrefix' => 'GoldenIntersection',
        ]];

        yield 'nested_described_resource' => ['nested_described_resource', GoldenDescribedOwner::class, 'json', Schema::TYPE_OUTPUT, 'golden_described_owner_get', null, Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'json',
            'namePrefix' => 'GoldenDescribedOwner',
        ]];

        yield 'nested_output_dto' => ['nested_output_dto', GoldenOutputDtoOwner::class, 'json', Schema::TYPE_OUTPUT, 'golden_output_dto_owner_get', null, Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'json',
            'namePrefix' => 'GoldenOutputDtoOwner',
        ]];

        yield 'jsonld_nested_described_resource' => ['jsonld_nested_described_resource', GoldenDescribedOwner::class, 'jsonld', Schema::TYPE_OUTPUT, 'golden_described_owner_get', null, Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'jsonld',
            'namePrefix' => 'GoldenDescribedOwner',
        ]];

        yield 'jsonld_nested_output_dto' => ['jsonld_nested_output_dto', GoldenOutputDtoOwner::class, 'jsonld', Schema::TYPE_OUTPUT, 'golden_output_dto_owner_get', null, Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'jsonld',
            'namePrefix' => 'GoldenOutputDtoOwner',
        ]];

        yield 'mcp_tool_output' => ['mcp_tool_output', McpFormatTool::class, 'json', Schema::TYPE_OUTPUT, 'format_message', null, Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'json',
            'schemaFactory' => 'mcp',
            'namePrefix' => 'McpFormatTool',
        ]];

        yield 'mcp_loader_item_output' => ['mcp_loader_item_output', McpBook::class, 'jsonld', Schema::TYPE_OUTPUT, 'get_book_info', [SchemaFactory::FORCE_SUBSCHEMA => true], Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'jsonld',
            'schemaFactory' => 'mcp',
            'namePrefix' => 'McpBook',
        ]];

        yield 'mcp_loader_collection_output' => ['mcp_loader_collection_output', McpBook::class, 'jsonld', Schema::TYPE_OUTPUT, 'list_books', [SchemaFactory::FORCE_SUBSCHEMA => true], Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'jsonld',
            'schemaFactory' => 'mcp',
            'collection' => true,
            'namePrefix' => 'McpBook',
        ]];

        yield 'mcp_loader_output_dto' => ['mcp_loader_output_dto', McpBookOutputDto::class, 'jsonld', Schema::TYPE_OUTPUT, 'list_books_dto', [SchemaFactory::FORCE_SUBSCHEMA => true], Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'jsonld',
            'schemaFactory' => 'mcp',
            'mcpResource' => McpBook::class,
            'namePrefix' => 'McpBook',
        ]];

        yield 'mcp_loader_input' => ['mcp_loader_input', SearchDto::class, 'jsonld', Schema::TYPE_INPUT, 'list_books', [SchemaFactory::FORCE_SUBSCHEMA => true], Schema::VERSION_JSON_SCHEMA, [
            'dialect' => 'jsonSchema202012',
            'format' => 'json',
            'schemaFactory' => 'mcp',
            'mcpResource' => McpBook::class,
        ]];
    }

    private static function normalize(mixed $value): mixed
    {
        $value = json_decode(json_encode($value, \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR);

        return self::sortKeys($value);
    }

    private static function sortKeys(mixed $value): mixed
    {
        if (!\is_array($value)) {
            return $value;
        }

        $value = array_map(self::sortKeys(...), $value);
        if (!array_is_list($value)) {
            ksort($value);
        }

        return $value;
    }
}
