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

namespace ApiPlatform\JsonSchema\Generator;

use ApiPlatform\JsonSchema\Metadata\Property\Factory\SchemaPropertyMetadataFactory;
use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use Symfony\Component\JsonSchema\Configuration;
use Symfony\Component\JsonSchema\PropertySchemaEnricher\PropertySchema;
use Symfony\Component\JsonSchema\PropertySchemaEnricher\PropertySchemaEnricherInterface;
use Symfony\Component\JsonSchema\SchemaDocument;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\ObjectType;

final class ApiPropertySchemaEnricher implements PropertySchemaEnricherInterface
{
    private const STRUCTURAL_KEYWORDS = ['type', 'items', 'additionalProperties', 'anyOf', 'allOf', 'oneOf', '$ref', 'enum', 'format', 'const'];
    private const OWNED_KEYWORDS = ['type', 'items', 'additionalProperties', 'anyOf', 'allOf', 'oneOf', '$ref', 'readOnly', 'writeOnly', 'description', 'deprecated', 'externalDocs', 'default', 'example'];

    public function __construct(
        private readonly PropertyMetadataAccessor $accessor,
        private readonly ResourceClassResolverInterface $resourceClassResolver,
        private readonly string $version,
    ) {
    }

    public function enrich(PropertySchema $property, Configuration $config, SchemaDocument $document): PropertySchema
    {
        $metadata = $this->accessor->get($property->class, $property->property, PropertyMetadataAccessor::contextFromConfiguration($config));
        $required = true === $metadata->isRequired();

        if (true === ($metadata->getExtraProperties()[SchemaPropertyMetadataFactory::JSON_SCHEMA_USER_DEFINED] ?? false)) {
            return $property->withRequired($required);
        }

        $contextSchema = Schema::VERSION_JSON_SCHEMA === $this->version ? $metadata->getJsonSchemaContext() : $metadata->getOpenapiContext();
        $metadataSchema = $metadata->getSchema() ?? [];
        $schema = [...array_diff_key($property->schema, ['default' => true]), ...array_diff_key($metadataSchema, array_flip(self::OWNED_KEYWORDS)), ...$this->getDocumentation($metadata)];
        $schema = array_replace(array_intersect_key($metadataSchema, $schema), $schema);

        if ($contextSchema) {
            if (array_intersect_key($contextSchema, array_flip(self::STRUCTURAL_KEYWORDS))) {
                $schema = array_diff_key($schema, array_flip(self::STRUCTURAL_KEYWORDS));
            }
            $schema = [...$schema, ...$contextSchema];
        }

        return $property->withSchema($schema)->withRequired($required);
    }

    /**
     * @return array<string, mixed>
     */
    private function getDocumentation(ApiProperty $metadata): array
    {
        $documentation = [];

        if (null !== $metadata->getUriTemplate() || (false === $metadata->isWritable() && !$metadata->isInitializable())) {
            $documentation['readOnly'] = true;
        }

        if (false === $metadata->isReadable()) {
            $documentation['writeOnly'] = true;
        }

        if (null !== $description = $metadata->getDescription()) {
            $documentation['description'] = $description;
        }

        if (null !== $metadata->getDeprecationReason()) {
            $documentation['deprecated'] = true;
        }

        if (null !== $iri = $metadata->getTypes()[0] ?? null) {
            $documentation['externalDocs'] = ['url' => $iri];
        }

        $isResourceClass = $metadata->getNativeType()?->isSatisfiedBy(fn (Type $type): bool => $type instanceof ObjectType && $this->resourceClassResolver->isResourceClass($type->getClassName()));
        if (null !== ($default = $metadata->getDefault()) && [] !== $default && !$isResourceClass) {
            $documentation['default'] = $default instanceof \BackedEnum ? $default->value : $default;
        }

        if (null !== ($example = $metadata->getExample()) && [] !== $example) {
            $documentation['example'] = $example;
        }

        return $documentation;
    }
}
