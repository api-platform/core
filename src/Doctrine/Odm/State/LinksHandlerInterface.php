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

namespace ApiPlatform\Doctrine\Odm\State;

use ApiPlatform\Metadata\Operation;
use Doctrine\ODM\MongoDB\Aggregation\Builder;

/**
 * @experimental
 */
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
    public function handleLinks(Builder $aggregationBuilder, array $uriVariables, array $context): void;
}
