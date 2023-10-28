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

namespace ApiPlatform\Tests\Fixtures\TestBundle\State;

use ApiPlatform\Doctrine\Orm\State\LinksHandlerInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;

class LinkHandledDummyLinksHandler implements LinksHandlerInterface
{
    public function handleLinks(QueryBuilder $queryBuilder, array $uriVariables, QueryNameGeneratorInterface $queryNameGenerator, array $context): void
    {
        $queryBuilder->andWhere($queryBuilder->getRootAliases()[0].'.slug = :slug')->setParameter('slug', 'foo');
    }
}
