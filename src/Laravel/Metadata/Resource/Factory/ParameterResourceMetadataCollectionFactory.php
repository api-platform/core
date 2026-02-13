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

namespace ApiPlatform\Laravel\Metadata\Resource\Factory;

use ApiPlatform\Laravel\Eloquent\Metadata\ModelMetadata;
use ApiPlatform\Laravel\Eloquent\State\Options;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Decorates the resource metadata factory to enhance nested property parameters
 * with Laravel Eloquent relationship information.
 */
final class ParameterResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    public function __construct(
        private readonly ResourceMetadataCollectionFactoryInterface $decorated,
        private readonly ModelMetadata $modelMetadata,
        private readonly NameConverterInterface $nameConverter,
    ) {
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated->create($resourceClass);

        foreach ($resourceMetadataCollection as $i => $resource) {
            $stateOptions = $resource->getStateOptions();
            $modelClass = $stateOptions instanceof Options ? $stateOptions->getModelClass() : null;

            $operations = $resource->getOperations();

            foreach ($operations as $operationName => $operation) {
                $parameters = $operation->getParameters();
                if (!$parameters) {
                    continue;
                }

                $modified = false;

                foreach ($parameters as $key => $parameter) {
                    $property = str_contains($key, '.') ? $key : $parameter->getProperty();
                    if (!$property || !str_contains($property, '.')) {
                        continue;
                    }

                    $extraProperties = $parameter->getExtraProperties();
                    $nestedInfo = $extraProperties['nested_property_info'] ?? null;

                    if (!$nestedInfo && $modelClass) {
                        $nestedInfo = $this->buildNestedPropertyInfo($property, $modelClass);
                        if (!$nestedInfo) {
                            continue;
                        }

                        $extraProperties['nested_property_info'] = $nestedInfo;
                        $parameters = $parameters->add(
                            $key,
                            $parameter->withProperty($property)->withExtraProperties($extraProperties)
                        );
                        $modified = true;
                        continue;
                    }

                    if (!$nestedInfo) {
                        continue;
                    }

                    $nestedInfo = $this->fillMissingRelationClasses($nestedInfo);
                    if (!$nestedInfo) {
                        continue;
                    }

                    $extraProperties['nested_property_info'] = $nestedInfo;
                    $parameters = $parameters->add($key, $parameter->withExtraProperties($extraProperties));
                    $modified = true;
                }

                if ($modified) {
                    $operations = $operations->add($operationName, $operation->withParameters($parameters));
                }
            }

            if ($operations !== $resource->getOperations()) {
                $resourceMetadataCollection[$i] = $resource->withOperations($operations);
            }
        }

        return $resourceMetadataCollection;
    }

    /**
     * Traverses a nested property path using Laravel Eloquent relationships.
     *
     * @param string $property   The nested property path (e.g., "product.productVariations.variantName")
     * @param string $modelClass The starting model class
     *
     * @return array<string, mixed>|null The nested_property_info array, or null if traversal fails
     */
    private function buildNestedPropertyInfo(string $property, string $modelClass): ?array
    {
        $parts = explode('.', $property);
        $currentClass = $modelClass;
        $relationSegments = [];
        $relationClasses = [];

        for ($i = 0, $last = \count($parts) - 1; $i < $last; ++$i) {
            $part = $parts[$i];
            $relationSegments[] = $part;
            $relationClasses[] = $currentClass;

            /** @var class-string<\Illuminate\Database\Eloquent\Model> $currentClass */
            $nextClass = $this->modelMetadata->getRelatedModelClass($currentClass, $part);
            if (!$nextClass) {
                return null;
            }

            $currentClass = $nextClass;
        }

        $leafProperty = $this->nameConverter->normalize($parts[\count($parts) - 1]);

        return [
            'relation_segments' => $relationSegments,
            'converted_relation_segments' => $relationSegments,
            'relation_classes' => $relationClasses,
            'leaf_property' => $leafProperty,
            'leaf_class' => $currentClass,
        ];
    }

    /**
     * Fills in missing relation_classes entries using Eloquent relationship resolution.
     *
     * @param array<string, mixed> $nestedInfo
     *
     * @return array<string, mixed>|null Updated nested info, or null if no changes were needed
     */
    private function fillMissingRelationClasses(array $nestedInfo): ?array
    {
        $relationSegments = $nestedInfo['relation_segments'] ?? [];
        $relationClasses = $nestedInfo['relation_classes'] ?? [];
        $needsUpdate = false;

        for ($idx = 0, $count = \count($relationSegments); $idx < $count; ++$idx) {
            $fromClass = $relationClasses[$idx] ?? null;
            if (!$fromClass) {
                continue;
            }

            $nextIdx = $idx + 1;
            if ($nextIdx < $count && !isset($relationClasses[$nextIdx])) {
                $targetClass = $this->modelMetadata->getRelatedModelClass($fromClass, $relationSegments[$idx]);
                if ($targetClass) {
                    $relationClasses[$nextIdx] = $targetClass;
                    $needsUpdate = true;
                }
            }
        }

        if (!$needsUpdate) {
            return null;
        }

        $lastIdx = \count($relationSegments) - 1;
        if ($lastIdx >= 0 && isset($relationClasses[$lastIdx])) {
            $leafClass = $this->modelMetadata->getRelatedModelClass($relationClasses[$lastIdx], $relationSegments[$lastIdx]);
            if ($leafClass) {
                $nestedInfo['leaf_class'] = $leafClass;
            }
        }

        $nestedInfo['relation_classes'] = $relationClasses;

        return $nestedInfo;
    }
}
