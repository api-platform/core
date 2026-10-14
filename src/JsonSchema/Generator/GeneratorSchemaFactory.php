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

use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use Symfony\Component\JsonSchema\ClassSchemaResolver\NativeClassSchemaResolver;
use Symfony\Component\JsonSchema\DefinitionProcessor\DefinitionProcessorInterface;
use Symfony\Component\JsonSchema\SchemaGenerator;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

final class GeneratorSchemaFactory implements SchemaFactoryInterface
{
    private readonly SchemaGenerator $generator;

    /**
     * @param iterable<DefinitionProcessorInterface> $definitionProcessors
     */
    public function __construct(
        private readonly ConfigurationFactory $configurationFactory,
        ApiPlatformDefinitionPolicy $definitionPolicy,
        PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory,
        PropertyMetadataFactoryInterface $propertyMetadataFactory,
        ResourceClassResolverInterface $resourceClassResolver,
        ?NameConverterInterface $nameConverter = null,
        iterable $definitionProcessors = [],
    ) {
        $accessor = new PropertyMetadataAccessor($propertyNameCollectionFactory, $propertyMetadataFactory);
        $this->generator = new SchemaGenerator(
            new ApiPlatformPropertyInfoExtractor($accessor),
            $definitionPolicy,
            [new ResourceIriClassSchemaResolver($accessor, $resourceClassResolver), new NativeClassSchemaResolver()],
            [new ApiPropertySchemaProvider($accessor)],
            [new ApiPropertySchemaEnricher($accessor, $resourceClassResolver)],
            [new BuiltinTypeDefinitionProcessor($accessor, $nameConverter), new PartialUpdateDefinitionProcessor($definitionPolicy), new NestedOperationDefinitionProcessor($definitionPolicy), ...$definitionProcessors],
            $nameConverter,
        );
    }

    public function buildSchema(string $className, string $format = 'json', string $type = Schema::TYPE_OUTPUT, ?Operation $operation = null, ?Schema $schema = null, ?array $serializerContext = null, bool $forceCollection = false): Schema
    {
        $schema = $schema ? clone $schema : new Schema();

        if (null === $created = $this->configurationFactory->create($className, $format, $type, $operation, $serializerContext, $schema->getVersion(), $forceCollection)) {
            return $schema;
        }

        [$rootType, $configuration] = $created;
        $context = $configuration->context;

        $generated = $this->generator->generate($rootType, $configuration);
        $refPath = $configuration->dialect->refPath;

        $root = $generated->getRoot();
        $rootName = $root['items']['$ref'] ?? $root['$ref'] ?? null;
        $rootName = \is_string($rootName) && str_starts_with($rootName, $refPath) ? substr($rootName, \strlen($refPath)) : null;

        foreach ($root as $keyword => $value) {
            $schema[$keyword] = $value;
        }

        $definitions = $schema->getDefinitions();
        foreach ($generated->getDefinitions() as $name => $definition) {
            if ($name === $rootName) {
                $definition += array_filter([
                    'description' => $context[ConfigurationFactory::CONTEXT_DESCRIPTION],
                    'deprecated' => $context[ConfigurationFactory::CONTEXT_DEPRECATED] ?: null,
                    'externalDocs' => $context[ConfigurationFactory::CONTEXT_EXTERNAL_DOCS] ? ['url' => $context[ConfigurationFactory::CONTEXT_EXTERNAL_DOCS]] : null,
                ]);
            }

            foreach ($definition['allOf'] ?? [] as $key => $member) {
                if (isset($member['properties'])) {
                    $definition['allOf'][$key] = self::toDefinitionObject($member);
                }
            }

            $definitions[$name] = self::toDefinitionObject($definition);
        }

        return $schema;
    }

    /**
     * @param array<string, mixed> $definition
     */
    private static function toDefinitionObject(array $definition): \ArrayObject
    {
        foreach ($definition['properties'] ?? [] as $property => $propertySchema) {
            $definition['properties'][$property] = new \ArrayObject($propertySchema);
        }

        return new \ArrayObject($definition);
    }
}
