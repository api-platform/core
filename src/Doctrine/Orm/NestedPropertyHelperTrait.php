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

namespace ApiPlatform\Doctrine\Orm;

use ApiPlatform\Doctrine\Orm\Util\QueryBuilderHelper;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Parameter;
use Doctrine\ORM\QueryBuilder;

/**
 * Helper trait for handling nested properties.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
trait NestedPropertyHelperTrait
{
    /**
     * Adds the necessary join for a nested property.
     *
     * @return array An array where the first element is the join $alias of the leaf entity,
     *               the second element is the leaf property
     */
    protected function addNestedParameterJoins(
        string $property,
        string $alias,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        Parameter $parameter,
        ?string $joinType = null,
    ): array {
        $extraProperties = $parameter->getExtraProperties();
        $nestedInfo = ($extraProperties['nested_properties_info'] ?? []) ? reset($extraProperties['nested_properties_info']) : null;

        if (!$nestedInfo) {
            return [$alias, $property];
        }

        $relationClasses = $nestedInfo['relation_classes'] ?? [];
        $association = null;
        $embedded = false;

        foreach ($nestedInfo['converted_relation_segments'] as $id => $association) {
            $entityClass = $relationClasses[$id] ?? null;
            if (!$entityClass) {
                continue;
            }

            $embedded = !$queryBuilder->getEntityManager()->getClassMetadata($entityClass)->hasAssociation($association);

            if (!$embedded) {
                $alias = QueryBuilderHelper::addJoinOnce(
                    $queryBuilder,
                    $queryNameGenerator,
                    $alias,
                    $association,
                    $joinType
                );
            }
        }

        $leafProperty = $nestedInfo['leaf_property'];

        return [$alias, $embedded && $association ? $association.'.'.$leafProperty : $leafProperty];
    }
}
