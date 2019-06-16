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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\GraphQl\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\QueryItemResolverInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\DummyCustomQuery as DummyCustomQueryDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyCustomQuery;

/**
 * Resolver for dummy item custom query.
 *
 * @author Lukas Lücke <lukas@luecke.me>
 */
class DummyCustomQueryItemResolver implements QueryItemResolverInterface
{
    /**
     * @param DummyCustomQuery|DummyCustomQueryDocument|null $item
     *
     * @return DummyCustomQuery|DummyCustomQueryDocument
     */
    public function __invoke($item, array $context)
    {
        $item->message = 'Success!';
        $item->customArgs = $context['args'];

        return $item;
    }
}
