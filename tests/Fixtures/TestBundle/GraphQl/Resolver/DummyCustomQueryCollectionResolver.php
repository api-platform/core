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

use ApiPlatform\Core\GraphQl\Resolver\QueryCollectionResolverInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\DummyCustomQuery as DummyCustomQueryDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyCustomQuery;

/**
 * Resolver for dummy collection custom query.
 *
 * @author Lukas Lücke <lukas@luecke.me>
 */
class DummyCustomQueryCollectionResolver implements QueryCollectionResolverInterface
{
    /**
     * @param iterable<DummyCustomQuery|DummyCustomQueryDocument> $collection
     *
     * @return iterable<DummyCustomQuery|DummyCustomQueryDocument>
     */
    public function __invoke(iterable $collection, array $context): iterable
    {
        foreach ($collection as $dummy) {
            $dummy->message = 'Success!';
            $dummy->customArgs = $context['args'];
        }

        return $collection;
    }
}
