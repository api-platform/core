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

namespace ApiPlatform\Doctrine\Odm;

use ApiPlatform\Metadata\Exception\RuntimeException;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\UnitOfWork;

final class PartialPaginator extends AbstractPaginator
{
    public function __construct(Iterator $mongoDbOdmIterator, UnitOfWork $unitOfWork, string $resourceClass)
    {
        $result = $mongoDbOdmIterator->toArray()[0];

        if (array_diff_key(['results' => 1, '__api_first_result__' => 1, '__api_max_results__' => 1], $result)) {
            throw new RuntimeException('The result of the query must contain only "__api_first_result__", "__api_max_results__" and "results" fields.');
        }

        parent::__construct($result);

        // The "results" facet contains the returned documents
        if ([] === $result['results']) {
            $this->count = 0;
            $this->iterator = new \ArrayIterator();
        } else {
            $this->count = \count($result['results']);
            $this->iterator = new \ArrayIterator(array_map(
                static fn ($result): object => $unitOfWork->getOrCreateDocument($resourceClass, $result),
                $result['results'],
            ));
        }
    }
}
