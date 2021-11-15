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

use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\GraphQl\Resolver\QueryCollectionResolverInterface;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyCustomQuery as DummyCustomQueryDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyCustomQuery;

/**
 * Resolver for dummy collection custom query (collection not read and no result serialized).
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class DummyCustomQueryNoReadAndSerializeCollectionResolver implements QueryCollectionResolverInterface
{
    /**
     * @param iterable<DummyCustomQuery|DummyCustomQueryDocument> $collection
     *
     * @return ArrayPaginator
     */
    public function __invoke(iterable $collection, array $context): iterable
    {
        if (!empty($collection)) {
            throw new RuntimeException('Collection should be empty');
        }

        return new ArrayPaginator(['Foo', 'Bar'], 0, 2); // Should not ne normalized.
    }
}
