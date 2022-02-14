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

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Util;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\QueryBuilder;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 *
 * @internal
 */
trait EagerLoadingTrait
{
    private $forceEager;
    private $fetchPartial;
    private $resourceMetadataFactory;

    /**
     * Checks if an operation has a `force_eager` attribute.
     */
    private function shouldOperationForceEager(string $resourceClass, array $options): bool
    {
        return $this->getBooleanOperationAttribute($resourceClass, $options, 'force_eager', $this->forceEager);
    }

    /**
     * Checks if an operation has a `fetch_partial` attribute.
     */
    private function shouldOperationFetchPartial(string $resourceClass, array $options): bool
    {
        return $this->getBooleanOperationAttribute($resourceClass, $options, 'fetch_partial', $this->fetchPartial);
    }

    /**
     * Get the boolean attribute of an operation or the resource metadata.
     */
    private function getBooleanOperationAttribute(string $resourceClass, array $options, string $attributeName, bool $default): bool
    {
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

        if (isset($options['collection_operation_name'])) {
            $attribute = $resourceMetadata->getCollectionOperationAttribute($options['collection_operation_name'], $attributeName, null, true);
        } elseif (isset($options['item_operation_name'])) {
            $attribute = $resourceMetadata->getItemOperationAttribute($options['item_operation_name'], $attributeName, null, true);
        } else {
            $attribute = $resourceMetadata->getAttribute($attributeName);
        }

        return \is_bool($attribute) ? $attribute : $default;
    }

    /**
     * Checks if the class has a one-to-many associationMapping with FETCH=EAGER in the where clause.
     */
    private function hasFetchEagerOneToManyInWhere(QueryBuilder $queryBuilder, ClassMetadataInfo $classMetadata): bool
    {
        $em = $queryBuilder->getEntityManager();
        $wherePart = $queryBuilder->getDQLPart('where');
        $aliasMapping = $this->createAliasMapping($queryBuilder);
        $checked = [];

        foreach ($wherePart->getParts() as $part) {
            foreach ($aliasMapping as $alias => $fieldPath) {
                if ('' === $fieldPath) {
                    // Root alias
                    continue;
                }

                if (false !== strpos((string) $part, $alias.'.')) {
                    $fields = explode('.', $fieldPath);
                    $clonedClassMetadata = clone $classMetadata;
                    foreach ($fields as $field) {
                        $mapping = $clonedClassMetadata->getAssociationMapping($field);
                        $fieldId = $field.'-'.$mapping['targetEntity'];
                        if (\in_array($fieldId, $checked, true)) {
                            $clonedClassMetadata = $em->getClassMetadata($mapping['targetEntity']);
                            continue;
                        }
                        $checked[] = $fieldId;
                        if (
                            ClassMetadataInfo::FETCH_EAGER === $mapping['fetch']
                            && ClassMetadataInfo::ONE_TO_MANY === $mapping['type']
                        ) {
                            return true;
                        }
                        $clonedClassMetadata = $em->getClassMetadata($mapping['targetEntity']);
                    }
                }
            }
        }

        return false;
    }

    private function createAliasMapping(QueryBuilder $queryBuilder): array
    {
        if (\count($queryBuilder->getRootAliases()) > 1) {
            throw new \UnexpectedValueException('Expected 1 root alias');
        }
        $rootAlias = $queryBuilder->getRootAliases()[0];
        $aliasMapping = [$rootAlias => ''];
        foreach ($queryBuilder->getDQLPart('join') as $joins) {
            foreach ($joins as $join) {
                $joinTargetField = $this->getJoinTargetField($join);
                if ($joinTargetField && isset($aliasMapping[$joinTargetField[0]])) {
                    $aliasMapping[$join->getAlias()] = trim($aliasMapping[$joinTargetField[0]].'.'.$joinTargetField[1], '.');
                }
            }
        }

        return $aliasMapping;
    }

    private function getJoinTargetField($join): array
    {
        $targetFile = explode('.', $join->getJoin());
        if ($join->getConditionType()) {
            $condition = $join->getCondition();
            $selfAlias = $join->getAlias();
            if (1 === preg_match('/(.+)(=)(.+)/', $condition, $matches)) {
                // It can be replaced with str_starts_with once upgrade to PHP8
                $operand = 0 === strpos(trim($matches[1]), $selfAlias.'.') ? $matches[3] : $matches[1];
                $targetFile = explode('.', trim($operand));
            }
        }

        return $targetFile;
    }
}
