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

use ApiPlatform\Doctrine\Odm\State\LinksHandlerInterface;
use Doctrine\ODM\MongoDB\Aggregation\Builder;

class ODMLinkHandledDummyLinksHandler implements LinksHandlerInterface
{
    public function handleLinks(Builder $aggregationBuilder, array $uriVariables, array $context): void
    {
        $aggregationBuilder->match()->field('slug')->equals('foo');
    }
}
