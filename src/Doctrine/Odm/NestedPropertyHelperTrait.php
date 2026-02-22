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

namespace ApiPlatform\Doctrine\Odm;

use ApiPlatform\Metadata\Parameter;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata as MongoDbOdmClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Helper trait for handling nested properties in parameter-based filters.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
trait NestedPropertyHelperTrait
{
    abstract protected function getManagerRegistry(): ManagerRegistry;

    /**
     * Adds the necessary lookups for a nested property using parameter metadata.
     *
     * @throws MappingException
     *
     * @return string The aliased field name to use in match/sort expressions
     */
    protected function addNestedParameterLookups(string $property, Builder $aggregationBuilder, Parameter $parameter, bool $preserveNullAndEmptyArrays = false): string
    {
        $extraProperties = $parameter->getExtraProperties();
        $nestedInfo = $extraProperties['nested_property_info'] ?? null;

        if (!$nestedInfo) {
            return $property;
        }

        $relationSegments = $nestedInfo['relation_segments'] ?? [];
        $relationClasses = $nestedInfo['relation_classes'] ?? [];
        $leafProperty = $nestedInfo['leaf_property'] ?? $property;

        if (!$relationSegments) {
            return $property;
        }

        $alias = '';

        foreach ($relationSegments as $i => $association) {
            $class = $relationClasses[$i] ?? null;
            if (!$class) {
                break;
            }

            $manager = $this->getManagerRegistry()->getManagerForClass($class);
            if (!$manager) {
                break;
            }

            $classMetadata = $manager->getClassMetadata($class);

            if (!$classMetadata instanceof MongoDbOdmClassMetadata) {
                break;
            }

            if ($classMetadata->hasReference($association)) {
                $propertyAlias = "{$association}_lkup";
                $localField = "$alias$association";
                $alias .= $propertyAlias;
                $referenceMapping = $classMetadata->getFieldMapping($association);

                if (($isOwningSide = $referenceMapping['isOwningSide']) && MongoDbOdmClassMetadata::REFERENCE_STORE_AS_ID !== $referenceMapping['storeAs']) {
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

                $aggregationBuilder->lookup($classMetadata->getAssociationTargetClass($association))
                    ->localField($isOwningSide ? $localField : '_id')
                    ->foreignField($isOwningSide ? '_id' : $referenceMapping['mappedBy'])
                    ->alias($alias);
                $aggregationBuilder->unwind("\$$alias")
                    ->preserveNullAndEmptyArrays($preserveNullAndEmptyArrays);

                $alias .= '.';
            } elseif ($classMetadata->hasEmbed($association)) {
                $alias = "$alias$association.";
            }
        }

        return "$alias$leafProperty";
    }
}
