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

namespace ApiPlatform\Doctrine\Odm\Metadata\Resource;

use ApiPlatform\Doctrine\Odm\State\Options;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\State\Util\StateOptionsTrait;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata as MongoDbOdmClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Enriches nested_property_info with ODM-specific mapping data (odm_segments)
 * so that filters don't need ManagerRegistry at runtime.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class DoctrineMongoDbOdmParameterResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    use StateOptionsTrait;

    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly ResourceMetadataCollectionFactoryInterface $decorated,
    ) {
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated->create($resourceClass);

        foreach ($resourceMetadataCollection as $i => $resourceMetadata) {
            $operations = $resourceMetadata->getOperations();

            if ($operations) {
                foreach ($operations as $operationName => $operation) {
                    $operation = $this->enrichOperation($operation, $resourceClass);
                    $operations->add($operationName, $operation);
                }

                $resourceMetadata = $resourceMetadata->withOperations($operations);
            }

            $graphQlOperations = $resourceMetadata->getGraphQlOperations();

            if ($graphQlOperations) {
                foreach ($graphQlOperations as $operationName => $graphQlOperation) {
                    $graphQlOperation = $this->enrichOperation($graphQlOperation, $resourceClass);
                    $graphQlOperations[$operationName] = $graphQlOperation;
                }

                $resourceMetadata = $resourceMetadata->withGraphQlOperations($graphQlOperations);
            }

            $resourceMetadataCollection[$i] = $resourceMetadata;
        }

        return $resourceMetadataCollection;
    }

    private function enrichOperation(Operation $operation, string $resourceClass): Operation
    {
        $parameters = $operation->getParameters();
        if (!$parameters) {
            return $operation;
        }

        $documentClass = $this->getStateOptionsClass($operation, $operation->getClass(), Options::class);
        if (!$this->managerRegistry->getManagerForClass($documentClass) instanceof DocumentManager) {
            return $operation;
        }

        $changed = false;

        foreach ($parameters as $key => $parameter) {
            $extraProperties = $parameter->getExtraProperties();

            // Handle singular nested_property_info
            $nestedInfo = $extraProperties['nested_property_info'] ?? null;
            if ($nestedInfo && !isset($nestedInfo['odm_segments'])) {
                $odmSegments = $this->buildOdmSegments($nestedInfo);
                if (null !== $odmSegments) {
                    $nestedInfo['odm_segments'] = $odmSegments;
                    $extraProperties['nested_property_info'] = $nestedInfo;
                    $changed = true;
                }
            }

            // Handle plural nested_properties_info (used by FreeTextQueryFilter)
            $nestedPropertiesInfo = $extraProperties['nested_properties_info'] ?? null;
            if ($nestedPropertiesInfo) {
                foreach ($nestedPropertiesInfo as $propPath => $propNestedInfo) {
                    if (!isset($propNestedInfo['odm_segments'])) {
                        $odmSegments = $this->buildOdmSegments($propNestedInfo);
                        if (null !== $odmSegments) {
                            $nestedPropertiesInfo[$propPath]['odm_segments'] = $odmSegments;
                            $changed = true;
                        }
                    }
                }

                $extraProperties['nested_properties_info'] = $nestedPropertiesInfo;
            }

            if ($changed) {
                $parameters->add($key, $parameter->withExtraProperties($extraProperties));
            }
        }

        if ($changed) {
            $operation = $operation->withParameters($parameters);
        }

        return $operation;
    }

    /**
     * @param array{relation_segments: list<string>, relation_classes: list<class-string>, leaf_property?: string, leaf_class?: class-string} $nestedInfo
     *
     * @throws MappingException
     *
     * @return list<array{type: string, target_document: class-string, is_owning_side?: bool, mapped_by?: string|null}>|null
     */
    private function buildOdmSegments(array $nestedInfo): ?array
    {
        $relationSegments = $nestedInfo['relation_segments'] ?? [];
        $relationClasses = $nestedInfo['relation_classes'] ?? [];

        if (!$relationSegments) {
            return null;
        }

        $odmSegments = [];

        foreach ($relationSegments as $i => $association) {
            $class = $relationClasses[$i] ?? null;
            if (!$class) {
                break;
            }

            $manager = $this->managerRegistry->getManagerForClass($class);
            if (!$manager) {
                break;
            }

            $classMetadata = $manager->getClassMetadata($class);
            if (!$classMetadata instanceof MongoDbOdmClassMetadata) {
                break;
            }

            if ($classMetadata->hasReference($association)) {
                $referenceMapping = $classMetadata->getFieldMapping($association);
                $isOwningSide = $referenceMapping['isOwningSide'];

                if ($isOwningSide && MongoDbOdmClassMetadata::REFERENCE_STORE_AS_ID !== $referenceMapping['storeAs']) {
                    throw MappingException::cannotLookupDbRefReference($classMetadata->getReflectionClass()->getShortName(), $association);
                }

                if (!$isOwningSide) {
                    if (isset($referenceMapping['repositoryMethod']) || !isset($referenceMapping['mappedBy'])) {
                        throw MappingException::repositoryMethodLookupNotAllowed($classMetadata->getReflectionClass()->getShortName(), $association);
                    }

                    $targetClassMetadata = $manager->getClassMetadata($referenceMapping['targetDocument']);
                    if ($targetClassMetadata instanceof MongoDbOdmClassMetadata && MongoDbOdmClassMetadata::REFERENCE_STORE_AS_ID !== $targetClassMetadata->getFieldMapping($referenceMapping['mappedBy'])['storeAs']) {
                        throw MappingException::cannotLookupDbRefReference($classMetadata->getReflectionClass()->getShortName(), $association);
                    }
                }

                $odmSegments[] = [
                    'type' => 'reference',
                    'target_document' => $classMetadata->getAssociationTargetClass($association),
                    'is_owning_side' => $isOwningSide,
                    'mapped_by' => $isOwningSide ? null : ($referenceMapping['mappedBy'] ?? null),
                ];
            } elseif ($classMetadata->hasEmbed($association)) {
                $odmSegments[] = [
                    'type' => 'embed',
                    'target_document' => $classMetadata->getAssociationTargetClass($association),
                ];
            }
        }

        return $odmSegments ?: null;
    }
}
