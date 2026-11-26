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

namespace ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource\Orm;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RuntimeResource\EntityRow;
use ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource\RuntimeMapper;
use ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource\RuntimeObject;
use ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource\RuntimeResources;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

final class EavProvider implements ProviderInterface
{
    public function __construct(
        private readonly ProviderInterface $itemProvider,
        private readonly ProviderInterface $collectionProvider,
        private readonly ManagerRegistry $registry,
        private readonly RuntimeMapper $mapper,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $name = RuntimeResources::nameOf((string) $operation->getClass());
        if (null === $name) {
            return null;
        }

        $operation = $operation->withStateOptions(new Options(entityClass: EntityRow::class, handleLinks: [self::class, 'handleLinks']));
        if (!$operation instanceof CollectionOperationInterface) {
            $row = $this->itemProvider->provide($operation, $uriVariables, $context);

            return $row instanceof EntityRow ? $this->find($name, (int) $row->getId()) : null;
        }

        $rows = $this->collectionProvider->provide($operation, $uriVariables, $context);
        $objects = $this->mapper->hydrate($name, array_map(static fn (EntityRow $row): int => (int) $row->getId(), iterator_to_array($rows, false)), $this->load(...));

        return $rows instanceof PaginatorInterface ? new TraversablePaginator(new \ArrayIterator($objects), $rows->getCurrentPage(), $rows->getItemsPerPage(), $rows->getTotalItems()) : $objects;
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public static function handleLinks(QueryBuilder $queryBuilder, array $uriVariables, QueryNameGeneratorInterface $queryNameGenerator, array $context): void
    {
        $alias = $queryBuilder->getRootAliases()[0];
        $definition = $queryNameGenerator->generateJoinAlias('definition');
        $name = $queryNameGenerator->generateParameterName('resource');
        $queryBuilder->innerJoin("$alias.definition", $definition)->andWhere("$definition.name = :$name")->setParameter($name, RuntimeResources::nameOf((string) $context['operation']->getClass()));

        if (isset($uriVariables['id'])) {
            $id = $queryNameGenerator->generateParameterName('id');
            $queryBuilder->andWhere("$alias.id = :$id")->setParameter($id, $uriVariables['id']);
        }
    }

    public function find(string $name, int $id): ?RuntimeObject
    {
        return $this->mapper->hydrate($name, [$id], $this->load(...))[0] ?? null;
    }

    /**
     * @param list<int> $ids
     *
     * @return array<int, array<string, mixed>>
     */
    private function load(string $name, array $ids): array
    {
        $manager = $this->registry->getManagerForClass(EntityRow::class);
        \assert($manager instanceof EntityManagerInterface);

        $rows = $manager->createQueryBuilder()
            ->select('r', 'v', 'a')
            ->from(EntityRow::class, 'r')
            ->innerJoin('r.definition', 'd')
            ->leftJoin('r.values', 'v')
            ->leftJoin('v.attribute', 'a')
            ->where('d.name = :name AND r.id IN (:ids)')
            ->orderBy('v.position')
            ->setParameter('name', $name)
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        $values = [];
        foreach ($rows as $row) {
            $values[$row->getId()] = [];
            foreach ($row->getValues() as $value) {
                $attribute = $value->getAttribute();
                if ($attribute->isCollection()) {
                    $values[$row->getId()][$attribute->getName()][] = $value->getValue();
                } else {
                    $values[$row->getId()][$attribute->getName()] = $value->getValue();
                }
            }
        }

        return $values;
    }
}
