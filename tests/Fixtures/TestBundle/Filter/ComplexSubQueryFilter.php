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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

final class ComplexSubQueryFilter extends AbstractFilter
{
    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if ('complex_sub_query_filter' !== $property) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];

        $relatedDummyAlias = $queryNameGenerator->generateJoinAlias('related_dummy');
        $relatedToDummyFriendAlias = $queryNameGenerator->generateJoinAlias('related_to_dummy_friend');

        $nameParameterName = $queryNameGenerator->generateParameterName('name');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->managerRegistry->getManager();

        $subQueryBuilder = $entityManager->createQueryBuilder()
            ->select("{$relatedDummyAlias}.id")
            ->from(RelatedDummy::class, $relatedDummyAlias)
            ->innerJoin(
                "{$relatedDummyAlias}.relatedToDummyFriend",
                $relatedToDummyFriendAlias,
                Join::WITH,
                "{$relatedToDummyFriendAlias}.name = :{$nameParameterName}"
            );

        $queryBuilder
            ->andWhere("{$rootAlias}.id IN ({$subQueryBuilder->getDQL()})")
            ->setParameter($nameParameterName, 'foo');
    }

    public function getDescription(string $resourceClass): array
    {
        return [];
    }
}
