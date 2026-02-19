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

namespace ApiPlatform\Doctrine\Orm\Metadata\Resource;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\State\Util\StateOptionsTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Enriches nested_properties_info with ORM-specific leaf metadata (orm_leaf_metadata)
 * so that filters don't need to resolve association chains at runtime.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class DoctrineOrmParameterResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
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

        $entityClass = $this->getStateOptionsClass($operation, $operation->getClass(), Options::class);
        if (!$this->managerRegistry->getManagerForClass($entityClass) instanceof EntityManagerInterface) {
            return $operation;
        }

        $operationChanged = false;

        foreach ($parameters as $key => $parameter) {
            $extraProperties = $parameter->getExtraProperties();
            $parameterChanged = false;

            $nestedPropertiesInfo = $extraProperties['nested_properties_info'] ?? null;
            if ($nestedPropertiesInfo) {
                foreach ($nestedPropertiesInfo as $propPath => $propNestedInfo) {
                    if (!isset($propNestedInfo['orm_leaf_metadata'])) {
                        $ormLeafMetadata = $this->buildOrmLeafMetadata($propNestedInfo);
                        if (null !== $ormLeafMetadata) {
                            $nestedPropertiesInfo[$propPath]['orm_leaf_metadata'] = $ormLeafMetadata;
                            $parameterChanged = true;
                        }
                    }
                }

                if ($parameterChanged) {
                    $extraProperties['nested_properties_info'] = $nestedPropertiesInfo;
                }
            }

            if ($parameterChanged) {
                $parameters->add($key, $parameter->withExtraProperties($extraProperties));
                $operationChanged = true;
            }
        }

        if ($operationChanged) {
            $operation = $operation->withParameters($parameters);
        }

        return $operation;
    }

    /**
     * @param array{leaf_property?: string, leaf_class?: class-string} $nestedInfo
     *
     * @return array{has_field: bool, has_association: bool, is_collection_valued: bool, is_inverse_side: bool, association_target_class: ?string, identifier_field: ?string, identifier_type: ?string}|null
     */
    private function buildOrmLeafMetadata(array $nestedInfo): ?array
    {
        $leafClass = $nestedInfo['leaf_class'] ?? null;
        $leafProperty = $nestedInfo['leaf_property'] ?? null;

        if (!$leafClass || !$leafProperty) {
            return null;
        }

        $manager = $this->managerRegistry->getManagerForClass($leafClass);
        if (!$manager instanceof EntityManagerInterface) {
            return null;
        }

        $classMetadata = $manager->getClassMetadata($leafClass);

        $result = [
            'has_field' => $classMetadata->hasField($leafProperty),
            'has_association' => $classMetadata->hasAssociation($leafProperty),
            'is_collection_valued' => false,
            'is_inverse_side' => false,
            'association_target_class' => null,
            'identifier_field' => null,
            'identifier_type' => null,
        ];

        if (!$result['has_association']) {
            return $result;
        }

        $result['is_collection_valued'] = $classMetadata->isCollectionValuedAssociation($leafProperty);

        $associationMapping = $classMetadata->getAssociationMapping($leafProperty);
        $targetClass = $associationMapping['targetEntity'];
        $result['association_target_class'] = $targetClass;
        $result['is_inverse_side'] = !($associationMapping['isOwningSide'] ?? true);

        $targetMetadata = $manager->getClassMetadata($targetClass);
        $idFieldNames = $targetMetadata->getIdentifierFieldNames();
        if ($idFieldNames) {
            $result['identifier_field'] = $idFieldNames[0];
            $result['identifier_type'] = $targetMetadata->getTypeOfField($idFieldNames[0]);
        }

        return $result;
    }
}
