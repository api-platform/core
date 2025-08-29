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

namespace ApiPlatform\Doctrine\Orm\State;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

interface LinksHandlerInterface
{
    /**
     * Handle Doctrine ORM links.
     *
     * @see LinksHandlerTrait
     *
     * @param array<string, mixed>                                                  $uriVariables
     * @param array{entityClass: string, operation: Operation}&array<string, mixed> $context
     */
    public function handleLinks(QueryBuilder $queryBuilder, array $uriVariables, QueryNameGeneratorInterface $queryNameGenerator, array $context): void;
}
