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

namespace ApiPlatform\Doctrine\Orm\Filter;

use ApiPlatform\Doctrine\Common\Filter\LoggerAwareInterface;
use ApiPlatform\Doctrine\Common\Filter\LoggerAwareTrait;
use ApiPlatform\Doctrine\Common\Filter\ManagerRegistryAwareInterface;
use ApiPlatform\Doctrine\Common\Filter\ManagerRegistryAwareTrait;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\JsonSchemaFilterInterface;
use ApiPlatform\Metadata\OpenApiParameterFilterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;
use Doctrine\ORM\QueryBuilder;

/**
 * Decorates a filter to apply it to rows of a subtype (entity, mapped superclass or interface) of the queried
 * entity, whose properties DQL cannot reference from the parent class.
 *
 * With `strict: true` (default) only rows of the subtype matching the decorated filter are returned; with
 * `strict: false` rows of other types are returned as well. Inside an {@see OrFilter} it is always strict.
 */
final class SubtypeFilter implements FilterInterface, OpenApiParameterFilterInterface, JsonSchemaFilterInterface, ManagerRegistryAwareInterface, LoggerAwareInterface
{
    use BackwardCompatibleFilterDescriptionTrait;
    use LoggerAwareTrait;
    use ManagerRegistryAwareTrait;

    /**
     * @param class-string $subtype
     */
    public function __construct(
        private readonly string $subtype,
        private readonly FilterInterface $filter,
        private readonly bool $strict = true,
    ) {
    }

    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $entityManager = $queryBuilder->getEntityManager();
        $rootMetadata = $entityManager->getClassMetadata($resourceClass);
        $entities = $this->resolveEntities($rootMetadata->subClasses);

        if ([] === $entities) {
            throw new RuntimeException(\sprintf('The subtype "%s" does not match any mapped entity inheriting from "%s".', $this->subtype, $resourceClass));
        }

        if ($this->filter instanceof ManagerRegistryAwareInterface && !$this->filter->hasManagerRegistry() && $this->hasManagerRegistry()) {
            $this->filter->setManagerRegistry($this->getManagerRegistry());
        }

        if ($this->filter instanceof LoggerAwareInterface && !$this->filter->hasLogger() && $this->hasLogger()) {
            $this->filter->setLogger($this->getLogger());
        }

        // all the conditions of the decorated filter must hold inside each subquery, whatever clause the root query uses
        $isOr = 'orWhere' === ($context['whereClause'] ?? null);
        unset($context['whereClause']);

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $identifier = $rootMetadata->getSingleIdentifierFieldName();
        $expressions = [];
        $applied = [];

        foreach ($entities as $entity) {
            $subAlias = $queryNameGenerator->generateJoinAlias('subtype');
            $subQueryBuilder = $entityManager->createQueryBuilder()
                ->select(\sprintf('%s.%s', $subAlias, $identifier))
                ->from($entity, $subAlias);

            $this->filter->apply($subQueryBuilder, $queryNameGenerator, $entity, $operation, $context);

            if (null === $subQueryBuilder->getDQLPart('where')) {
                continue;
            }

            // INSTANCE OF is redundant with the subquery, but lets the database discard other types on the discriminator first
            $expressions[] = \sprintf('(%1$s INSTANCE OF %2$s AND %1$s.%3$s IN (%4$s))', $rootAlias, $entity, $identifier, $subQueryBuilder->getDQL());
            $applied[] = $entity;

            foreach ($subQueryBuilder->getParameters() as $parameter) {
                $queryBuilder->getParameters()->add($parameter);
            }
        }

        if ([] === $expressions) {
            return;
        }

        if (!$this->strict && !$isOr) {
            $expressions[] = \sprintf('%s NOT INSTANCE OF (%s)', $rootAlias, implode(', ', $applied));
        }

        $expression = $queryBuilder->expr()->orX(...$expressions);

        if ($isOr) {
            $queryBuilder->orWhere($expression);

            return;
        }

        $queryBuilder->andWhere($expression);
    }

    public function getOpenApiParameters(Parameter $parameter): OpenApiParameter|array|null
    {
        return $this->filter instanceof OpenApiParameterFilterInterface ? $this->filter->getOpenApiParameters($parameter) : null;
    }

    public function getSchema(Parameter $parameter): array
    {
        return $this->filter instanceof JsonSchemaFilterInterface ? $this->filter->getSchema($parameter) : [];
    }

    /**
     * @param list<class-string> $subClasses
     *
     * @return list<class-string>
     */
    private function resolveEntities(array $subClasses): array
    {
        $matching = array_values(array_filter($subClasses, fn (string $class): bool => is_a($class, $this->subtype, true)));

        return array_values(array_filter($matching, static function (string $class) use ($matching): bool {
            foreach ($matching as $other) {
                if (is_subclass_of($class, $other)) {
                    return false;
                }
            }

            return true;
        }));
    }
}
