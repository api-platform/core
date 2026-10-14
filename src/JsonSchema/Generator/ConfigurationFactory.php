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

use ApiPlatform\JsonSchema\ResourceMetadataTrait;
use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use Symfony\Component\JsonSchema\Configuration;
use Symfony\Component\JsonSchema\Dialect;
use Symfony\Component\JsonSchema\NullSyntax;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\Validator\Constraints\GroupSequence;

final class ConfigurationFactory
{
    use ResourceMetadataTrait;

    public const CONTEXT_SCHEMA_TYPE = 'api_platform.schema_type';
    public const CONTEXT_FORMAT = 'api_platform.format';
    public const CONTEXT_PARTIAL = 'api_platform.partial';
    public const CONTEXT_VERSION = 'api_platform.version';
    public const CONTEXT_PROPERTY_OPTIONS = 'api_platform.property_options';
    public const CONTEXT_GROUPS = 'api_platform.groups';
    public const CONTEXT_DESCRIPTION = 'api_platform.description';
    public const CONTEXT_DEPRECATED = 'api_platform.deprecated';
    public const CONTEXT_EXTERNAL_DOCS = 'api_platform.external_docs';

    private const OPENAPI_DEFINITION_NAME = 'openapi_definition_name';

    public function __construct(
        ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory,
        ?ResourceClassResolverInterface $resourceClassResolver,
        private readonly ApiPlatformDefinitionPolicy $definitionPolicy,
    ) {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->resourceClassResolver = $resourceClassResolver;
    }

    /**
     * @param array<string, mixed>|null $serializerContext
     *
     * @return array{Type, Configuration}|null
     */
    public function create(string $className, string $format, string $type, ?Operation $operation, ?array $serializerContext, string $version, bool $forceCollection = false): ?array
    {
        $operationWasProvided = null !== $operation;

        if (!$this->isResourceClass($className)) {
            $serializerContext ??= $operation ? $this->getSerializerContext($operation, $type) : [];
            $operation = null;
            $inputOrOutputClass = $className;
        } else {
            $operation = $this->findOperation($className, $type, $operation, $serializerContext, $format);
            $inputOrOutputClass = $this->findOutputClass($className, $type, $operation, $serializerContext);
            if ($operationWasProvided || !($serializerContext[SchemaFactoryInterface::FORCE_SUBSCHEMA] ?? false)) {
                $serializerContext = ($serializerContext ?? []) + $this->getSerializerContext($operation, $type);
            }
        }

        if (null === $inputOrOutputClass) {
            return null;
        }

        $method = $operation instanceof HttpOperation ? $operation->getMethod() : 'GET';
        if (!$operation) {
            $method = Schema::TYPE_INPUT === $type ? 'POST' : 'GET';
        }

        if (!($serializerContext[SchemaFactoryInterface::FORCE_SUBSCHEMA] ?? false) && Schema::TYPE_OUTPUT !== $type && !\in_array($method, ['POST', 'PATCH', 'PUT', 'QUERY'], true) && true !== $operation?->canDeserialize()) {
            return null;
        }

        $isJsonMergePatch = $this->definitionPolicy->isJsonMergePatch($operation, $type, $format);

        $validationGroups = $operation?->getValidationContext()['groups'] ?? null;
        $attributes = $serializerContext[AbstractNormalizer::ATTRIBUTES] ?? null;

        $groups = array_values((array) ($serializerContext[AbstractNormalizer::GROUPS] ?? []));

        $propertyOptions = ['schema_type' => $type, 'enable_getter_setter_extraction' => true];
        if (null !== $validationGroups) {
            $propertyOptions['validation_groups'] = \is_array($validationGroups) ? $validationGroups : [$validationGroups];
        }
        if ($ignoredAttributes = $serializerContext['ignored_attributes'] ?? null) {
            $propertyOptions['ignored_attributes'] = $ignoredAttributes;
        }

        $configuration = new Configuration(
            dialect: $this->createDialect($version),
            groups: $groups,
            attributes: $attributes ? (array) $attributes : null,
            ignoredAttributes: array_values((array) ($serializerContext['ignored_attributes'] ?? [])),
            allowExtraAttributes: false !== ($serializerContext[AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES] ?? true),
            validationGroups: $this->normalizeValidationGroups($validationGroups),
            definitionName: $serializerContext[self::OPENAPI_DEFINITION_NAME] ?? null,
            definitionPrefix: $this->definitionPolicy->createRootPrefix($operation, $className, $inputOrOutputClass),
            format: $isJsonMergePatch ? 'merge-patch+json' : $format,
            context: [
                self::CONTEXT_SCHEMA_TYPE => $type,
                self::CONTEXT_FORMAT => $format,
                self::CONTEXT_PARTIAL => $this->definitionPolicy->isPartialUpdate($operation, $type, $format),
                self::CONTEXT_VERSION => $version,
                self::CONTEXT_PROPERTY_OPTIONS => $propertyOptions,
                self::CONTEXT_GROUPS => $groups,
                self::CONTEXT_DESCRIPTION => $operation?->getDescription() ?: null,
                self::CONTEXT_DEPRECATED => Schema::VERSION_SWAGGER !== $version && $operation && $operation->getDeprecationReason() && ($operationWasProvided || !($serializerContext[SchemaFactoryInterface::FORCE_SUBSCHEMA] ?? false)),
                self::CONTEXT_EXTERNAL_DOCS => $operation instanceof HttpOperation ? ($operation->getTypes()[0] ?? null) : null,
            ],
        );

        $objectType = Type::object($inputOrOutputClass);
        $isCollection = $forceCollection || (!\in_array($method, ['POST', 'QUERY'], true) && $operation instanceof CollectionOperationInterface);

        return [$isCollection ? Type::list($objectType) : $objectType, $configuration];
    }

    private function createDialect(string $version): Dialect
    {
        return match ($version) {
            Schema::VERSION_OPENAPI => new Dialect('#/components/schemas/', NullSyntax::Union),
            Schema::VERSION_SWAGGER => new Dialect('#/definitions/', NullSyntax::Union),
            default => new Dialect('#/definitions/', NullSyntax::Union, schemaUri: 'http://json-schema.org/draft-07/schema#'),
        };
    }

    /**
     * @return array<mixed>|GroupSequence|null
     */
    private function normalizeValidationGroups(mixed $groups): array|GroupSequence|null
    {
        return match (true) {
            null === $groups, $groups instanceof \Closure, \is_array($groups) && \is_callable($groups) => null,
            $groups instanceof GroupSequence => $groups,
            \is_array($groups) => $groups,
            default => [$groups],
        };
    }
}
