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

namespace ApiPlatform\JsonApi\Filter;

use ApiPlatform\JsonApi\Util\ResourceLinkageResolver;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\State\ParameterProviderInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

final readonly class SparseFieldsetParameterProvider implements ParameterProviderInterface
{
    public function __construct(
        private ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory,
        private ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
        private PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory,
        private PropertyMetadataFactoryInterface $propertyMetadataFactory,
        private ResourceLinkageResolver $resourceLinkageResolver,
    ) {
    }

    public function provide(Parameter $parameter, array $parameters = [], array $context = []): ?Operation
    {
        if (!($operation = $context['operation'] ?? null)) {
            return null;
        }

        $allowedProperties = $parameter->getProperties() ?? [];
        $value = $parameter->getValue();
        $normalizationContext = $operation->getNormalizationContext();

        if (!\is_array($value)) {
            return null;
        }

        $properties = [];
        $shortName = strtolower($operation->getShortName());
        $relationPropertiesByKey = null;

        foreach ($value as $resource => $fields) {
            if (strtolower($resource) === $shortName) {
                $p = &$properties;
                $resourceAllowedProperties = $allowedProperties;
            } else {
                $resourceAllowedProperties = $this->getAllowedProperties((string) $resource);

                // fields[TYPE] sends the JSON:API resource type; resolve it to the host property name the serializer whitelists.
                $relationPropertiesByKey ??= $this->resolveRelationPropertiesByKey((string) $operation->getClass());
                $propertyName = $relationPropertiesByKey[strtolower((string) $resource)] ?? $resource;

                $properties[$propertyName] = [];
                $p = &$properties[$propertyName];
            }

            foreach (explode(',', $fields) as $f) {
                if (\in_array($f, $resourceAllowedProperties, true)) {
                    $p[] = $f;
                }
            }
        }

        if (isset($normalizationContext[AbstractNormalizer::ATTRIBUTES])) {
            $properties = array_merge_recursive((array) $normalizationContext[AbstractNormalizer::ATTRIBUTES], $properties);
        }

        $normalizationContext[AbstractNormalizer::ATTRIBUTES] = $properties;

        return $operation->withNormalizationContext($normalizationContext);
    }

    /**
     * @return list<string>
     */
    private function getAllowedProperties(string $type): array
    {
        if (null === $resourceClass = $this->resolveResourceClass($type)) {
            return [];
        }

        $allowedProperties = [];
        foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $property) {
            if ($this->propertyMetadataFactory->create($resourceClass, $property)->isReadable()) {
                $allowedProperties[] = $property;
            }
        }

        return $allowedProperties;
    }

    private function resolveResourceClass(string $type): ?string
    {
        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            foreach ($this->resourceMetadataCollectionFactory->create($resourceClass) as $resourceMetadata) {
                if (strtolower($resourceMetadata->getShortName()) === strtolower($type)) {
                    return $resourceClass;
                }
            }
        }

        return null;
    }

    /**
     * Maps both the relation property name and its target resource's short name (lowercased) to the property name.
     *
     * @return array<string, string>
     */
    private function resolveRelationPropertiesByKey(string $resourceClass): array
    {
        $map = [];
        foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $property) {
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $property);
            foreach ($this->resourceLinkageResolver->getRelationships($propertyMetadata) as [$relatedClass]) {
                $map[strtolower($property)] = $property;
                $map[strtolower($this->getShortName($relatedClass))] = $property;
                break;
            }
        }

        return $map;
    }

    private function getShortName(string $resourceClass): string
    {
        try {
            return $this->resourceMetadataCollectionFactory->create($resourceClass)->getOperation()->getShortName();
        } catch (\Throwable) {
            return (new \ReflectionClass($resourceClass))->getShortName();
        }
    }
}
