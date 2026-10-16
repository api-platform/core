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
use ApiPlatform\Metadata\Exception\PropertyNotFoundException;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use Symfony\Component\JsonSchema\Configuration;
use Symfony\Component\JsonSchema\DefinitionParent;
use Symfony\Component\JsonSchema\DefinitionPolicy\DefinitionPolicyInterface;
use Symfony\Component\JsonSchema\SchemaDocument;

final class ApiPlatformDefinitionPolicy implements DefinitionPolicyInterface
{
    use ResourceMetadataTrait;

    private const GLUE = '.';
    private const JSON_MERGE_PATCH_FORMAT = 'merge-patch+json';
    private const JSON_MERGE_PATCH_SCHEMA_POSTFIX = 'jsonMergePatch';

    /**
     * @var array<string, string|null>
     */
    private array $prefixCache = [];

    public function __construct(
        ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory,
        ?ResourceClassResolverInterface $resourceClassResolver,
        private readonly PropertyMetadataFactoryInterface $propertyMetadataFactory,
    ) {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->resourceClassResolver = $resourceClassResolver;
    }

    public function isJsonMergePatch(?Operation $operation, string $type, string $format): bool
    {
        return 'json' === $format && 'PATCH' === $this->resolveMethod($operation, $type) && Schema::TYPE_INPUT === $type;
    }

    public function isPartialUpdate(?Operation $operation, string $type, string $format): bool
    {
        $isNonStandardPut = Schema::TYPE_INPUT === $type && 'PUT' === $this->resolveMethod($operation, $type) && !($operation?->getExtraProperties()['standard_put'] ?? true);

        return $this->isJsonMergePatch($operation, $type, $format) || $isNonStandardPut;
    }

    public function isPartial(string $class, Configuration $config, ?DefinitionParent $parent): bool
    {
        if (null === $parent) {
            return $config->context[ConfigurationFactory::CONTEXT_PARTIAL] ?? false;
        }

        $operation = $this->findNestedOperation($class, $config);

        return null !== $operation && $this->isPartialUpdate($operation, $config->context[ConfigurationFactory::CONTEXT_SCHEMA_TYPE] ?? Schema::TYPE_OUTPUT, $config->context[ConfigurationFactory::CONTEXT_FORMAT] ?? 'json');
    }

    public function createRootPrefix(?Operation $operation, string $className, string $inputOrOutputClass): string
    {
        $prefix = $operation ? $this->createPrefixFromOperation($operation) : null;
        $prefix ??= $this->createPrefixFromClass($className);

        if ($className !== $inputOrOutputClass) {
            $prefix .= self::GLUE.$this->createPrefixFromClass($inputOrOutputClass);
        }

        return $prefix;
    }

    public function nameFor(string $class, Configuration $config, SchemaDocument $document, ?DefinitionParent $parent = null): string
    {
        $isPartialUpdate = $this->isPartial($class, $config, $parent);

        if (null === $parent) {
            $prefix = $config->definitionPrefix ?? $this->createPrefixFromClass($class);
            $isJsonMergePatch = self::JSON_MERGE_PATCH_FORMAT === $config->format;
        } else {
            $operation = $this->findNestedOperation($class, $config);
            $prefix = $this->createNestedPrefix($class, $operation);
            $type = $config->context[ConfigurationFactory::CONTEXT_SCHEMA_TYPE] ?? Schema::TYPE_OUTPUT;
            $format = $config->context[ConfigurationFactory::CONTEXT_FORMAT] ?? 'json';
            $isJsonMergePatch = null !== $operation && $this->isJsonMergePatch($operation, $type, $format);
        }

        $format = $config->format ?? 'json';
        if (!\in_array($format, ['json', self::JSON_MERGE_PATCH_FORMAT], true)) {
            $prefix .= self::GLUE.$format;
        }

        if (null !== $config->definitionName) {
            $name = \sprintf('%s%s', $prefix, '' !== $config->definitionName ? '-'.$config->definitionName : '');
        } else {
            $parts = [];

            if ($config->groups) {
                $parts[] = implode('_', $this->orderGroups($config->groups, $config));
            }

            if ($config->attributes) {
                $parts[] = $this->getAttributesAsString($config->attributes);
            }

            if (null !== $validationGroups = $config->getValidationGroupNames()) {
                $parts[] = 'validation'.self::GLUE.($validationGroups ? implode('_', $validationGroups) : 'none');
            }

            $name = $parts ? \sprintf('%s-%s', $prefix, implode('_', $parts)) : $prefix;
        }

        if ($isPartialUpdate && !$isJsonMergePatch) {
            $name .= self::GLUE.'partial';
        }

        if (false === $this->resolveGenId($parent)) {
            $name .= '_noid';
        }

        if ($isJsonMergePatch) {
            $name .= self::GLUE.self::JSON_MERGE_PATCH_SCHEMA_POSTFIX;
        }

        return preg_replace('/[^a-zA-Z0-9.\-_]/', self::GLUE, $name);
    }

    public function resolveGenId(?DefinitionParent $parent): bool
    {
        for (; null !== $parent; $parent = $parent->parent) {
            try {
                $genId = $this->propertyMetadataFactory->create($parent->class, $parent->property)->getGenId();
            } catch (PropertyNotFoundException) {
                continue;
            }

            if (null !== $genId) {
                return $genId;
            }
        }

        return true;
    }

    public function findNestedOperation(string $class, Configuration $config): ?Operation
    {
        if (!$this->isResourceClass($class)) {
            return null;
        }

        return $this->findOperation($class, $config->context[ConfigurationFactory::CONTEXT_SCHEMA_TYPE] ?? Schema::TYPE_OUTPUT, null, [SchemaFactoryInterface::FORCE_SUBSCHEMA => true]);
    }

    private function createNestedPrefix(string $class, ?Operation $operation): string
    {
        if (null === $operation) {
            return $this->createPrefixFromClass($class);
        }

        return $this->createPrefixFromOperation($operation) ?? $this->createPrefixFromClass($class);
    }

    private function resolveMethod(?Operation $operation, string $type): string
    {
        if ($operation instanceof HttpOperation) {
            return $operation->getMethod();
        }

        return !$operation && Schema::TYPE_INPUT === $type ? 'POST' : 'GET';
    }

    private function createPrefixFromOperation(Operation $operation): ?string
    {
        $name = $operation->getShortName();

        if (null === $name) {
            return null;
        }

        if (!isset($this->prefixCache[$name])) {
            $this->prefixCache[$name] = $operation->getClass();

            return $name;
        }

        if ($this->prefixCache[$name] === $operation->getClass()) {
            return $name;
        }

        return null;
    }

    private function createPrefixFromClass(string $fullyQualifiedClassName, int $namespaceParts = 1): string
    {
        $parts = explode('\\', $fullyQualifiedClassName);
        $name = implode(self::GLUE, \array_slice($parts, -$namespaceParts));

        if (!isset($this->prefixCache[$name])) {
            $this->prefixCache[$name] = $fullyQualifiedClassName;

            return $name;
        }

        if ($this->prefixCache[$name] !== $fullyQualifiedClassName) {
            $name = $this->createPrefixFromClass($fullyQualifiedClassName, ++$namespaceParts);
        }

        return $name;
    }

    /**
     * @param array<int|string, mixed> $attributes
     */
    private function getAttributesAsString(array $attributes): string
    {
        $parts = [];

        foreach ($attributes as $key => $value) {
            if (\is_array($value)) {
                foreach (explode('_', $this->getAttributesAsString($value)) as $child) {
                    $parts[] = $key.self::GLUE.$child;
                }
            } elseif (\is_string($key)) {
                $parts[] = $key;
            } else {
                $parts[] = $value;
            }
        }

        return implode('_', $parts);
    }

    /**
     * @param list<string> $groups
     *
     * @return list<string>
     */
    private function orderGroups(array $groups, Configuration $config): array
    {
        $declared = $config->context[ConfigurationFactory::CONTEXT_GROUPS] ?? [];

        return array_values([...array_intersect($declared, $groups), ...array_diff($groups, $declared)]);
    }
}
