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

namespace ApiPlatform\Doctrine\Orm\Extension;

use ApiPlatform\Doctrine\Orm\Util\QueryBuilderHelper;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

/**
 * Applies selected ordering while querying resource collection.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class OrderExtension implements QueryCollectionExtensionInterface
{
    public function __construct(private readonly ?string $order = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, ?string $resourceClass = null, ?Operation $operation = null, array $context = []): void
    {
        if (null === $resourceClass) {
            throw new InvalidArgumentException('The "$resourceClass" parameter must not be null');
        }

        // Do not apply order if already defined on queryBuilder
        $orderByDqlPart = $queryBuilder->getDQLPart('orderBy');
        if (\is_array($orderByDqlPart) && \count($orderByDqlPart) > 0) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];

        $classMetaData = $queryBuilder->getEntityManager()->getClassMetadata($resourceClass);
        $identifiers = $classMetaData->getIdentifier();
        $defaultOrder = $operation?->getOrder() ?? [];

        if ([] !== $defaultOrder) {
            foreach ($defaultOrder as $field => $order) {
                if (\is_int($field)) {
                    // Default direction
                    $field = $order;
                    $order = 'ASC';
                }

                $pos = strpos($field, '.');
                if (false === $pos || isset($classMetaData->embeddedClasses[substr($field, 0, $pos)])) {
                    // Configure default filter with property
                    $field = "{$rootAlias}.{$field}";
                } else {
                    $alias = QueryBuilderHelper::addJoinOnce($queryBuilder, $queryNameGenerator, $rootAlias, substr($field, 0, $pos));
                    $field = \sprintf('%s.%s', $alias, substr($field, $pos + 1));
                }
                $queryBuilder->addOrderBy($field, $order);
            }

            return;
        }

        if (null !== $this->order) {
            // A foreign identifier cannot be used for ordering.
            if ($classMetaData->containsForeignIdentifier) {
                return;
            }

            foreach ($identifiers as $identifier) {
                $queryBuilder->addOrderBy("{$rootAlias}.{$identifier}", $this->order);
            }
        }
    }
}
