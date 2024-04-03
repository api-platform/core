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

namespace ApiPlatform\Tests\Fixtures\TestBundle\GraphQl\Resolver;

use ApiPlatform\GraphQl\Resolver\QueryItemResolverInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\MultiRelationsResolveDummy as MultiRelationsResolveDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MultiRelationsResolveDummy;

/**
 * Resolver for dummy item custom query.
 *
 * @author Lukas Lücke <lukas@luecke.me>
 */
class MultiRelationsResolveQueryItemResolver implements QueryItemResolverInterface
{
    public function __invoke(?object $item, array $context): MultiRelationsResolveDummy|MultiRelationsResolveDummyDocument
    {
        return $context['source']['manyToOneResolveRelation'];
    }
}
