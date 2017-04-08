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

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

/**
 * Fixes filters on OneToMany associations
 * https://github.com/api-platform/core/issues/944.
 */
final class FilterEagerLoadingExtension implements QueryCollectionExtensionInterface
{
    private $resourceMetadataFactory;
    private $forceEager;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, $forceEager = true)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->forceEager = $forceEager;
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
        $em = $queryBuilder->getEntityManager();
        $classMetadata = $em->getClassMetadata($resourceClass);

        if (!$this->hasFetchEagerAssociation($em, $classMetadata) && (false === $this->forceEager || false === $this->isForceEager($resourceClass, ['collection_operation_name' => $operationName]))) {
            return;
        }

        //If no where part, nothing to do
        $wherePart = $queryBuilder->getDQLPart('where');

        if (!$wherePart) {
            return;
        }

        $joinParts = $queryBuilder->getDQLPart('join');
        $originAlias = 'o';

        if (!$joinParts || !isset($joinParts[$originAlias])) {
            return;
        }

        $queryBuilderClone = clone $queryBuilder;
        $queryBuilderClone->resetDQLPart('where');

        if (!$classMetadata->isIdentifierComposite) {
            $replacementAlias = $queryNameGenerator->generateJoinAlias($originAlias);
            $in = $this->getQueryBuilderWithNewAliases($queryBuilder, $queryNameGenerator, $originAlias, $replacementAlias);
            $in->select($replacementAlias);
            $queryBuilderClone->andWhere($queryBuilderClone->expr()->in($originAlias, $in->getDQL()));
        } else {
            // Because Doctrine doesn't support WHERE ( foo, bar ) IN () (https://github.com/doctrine/doctrine2/issues/5238), we are building as many subqueries as they are identifiers
            foreach ($classMetadata->identifier as $identifier) {
                $replacementAlias = $queryNameGenerator->generateJoinAlias($originAlias);
                $in = $this->getQueryBuilderWithNewAliases($queryBuilder, $queryNameGenerator, $originAlias, $replacementAlias);
                $in->select("IDENTITY($replacementAlias.$identifier)");
                $queryBuilderClone->andWhere($queryBuilderClone->expr()->in("$originAlias.$identifier", $in->getDQL()));
            }
        }

        $queryBuilder->resetDQLPart('where');
        $queryBuilder->add('where', $queryBuilderClone->getDQLPart('where'));
    }

    /**
     * Returns a clone of the given query builder where everything gets re-aliased.
     *
     * @param QueryBuilder                $queryBuilder
     * @param QueryNameGeneratorInterface $queryBuilder
     * @param string                      $originAlias  - the base alias
     * @param string                      $replacement  - the replacement for the base alias, will change the from alias
     */
    private function getQueryBuilderWithNewAliases(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $originAlias = 'o', string $replacement = 'o_2')
    {
        $queryBuilderClone = clone $queryBuilder;

        $joinParts = $queryBuilder->getDQLPart('join');
        $wherePart = $queryBuilder->getDQLPart('where');

        //reset parts
        $queryBuilderClone->resetDQLPart('join');
        $queryBuilderClone->resetDQLPart('where');
        $queryBuilderClone->resetDQLPart('orderBy');
        $queryBuilderClone->resetDQLPart('groupBy');
        $queryBuilderClone->resetDQLPart('having');

        //Change from alias
        $from = $queryBuilderClone->getDQLPart('from')[0];
        $queryBuilderClone->resetDQLPart('from');
        $queryBuilderClone->from($from->getFrom(), $replacement);

        $aliases = ["$originAlias."];
        $replacements = ["$replacement."];

        //Change join aliases
        foreach ($joinParts[$originAlias] as $joinPart) {
            $aliases[] = "{$joinPart->getAlias()}.";
            $alias = $queryNameGenerator->generateJoinAlias($joinPart->getAlias());
            $replacements[] = "$alias.";
            $join = new Join($joinPart->getJoinType(), str_replace($aliases, $replacements, $joinPart->getJoin()), $alias, $joinPart->getConditionType(), $joinPart->getCondition(), $joinPart->getIndexBy());

            $queryBuilderClone->add('join', [$join], true);
        }

        $queryBuilderClone->add('where', str_replace($aliases, $replacements, (string) $wherePart));

        return $queryBuilderClone;
    }

    /**
     * Does an operation force eager?
     *
     * @param string $resourceClass
     * @param array  $options
     *
     * @return bool
     */
    private function isForceEager(string $resourceClass, array $options): bool
    {
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

        if (isset($options['collection_operation_name'])) {
            $forceEager = $resourceMetadata->getCollectionOperationAttribute($options['collection_operation_name'], 'force_eager', null, true);
        } else {
            $forceEager = $resourceMetadata->getAttribute('force_eager');
        }

        return is_bool($forceEager) ? $forceEager : $this->forceEager;
    }

    private function hasFetchEagerAssociation(EntityManager $em, ClassMetadataInfo $classMetadata, &$checked = [])
    {
        $checked[] = $classMetadata->name;

        foreach ($classMetadata->associationMappings as $mapping) {
            if (ClassMetadataInfo::FETCH_EAGER === $mapping['fetch']) {
                return true;
            }

            $related = $em->getClassMetadata($mapping['targetEntity']);

            if (in_array($related->name, $checked, true)) {
                continue;
            }

            if (true === $this->hasFetchEagerAssociation($em, $related, $checked)) {
                return true;
            }
        }

        return false;
    }
}
