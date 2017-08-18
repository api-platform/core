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
/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Bridge\Doctrine\MongoDB\Extension;

use Doctrine\ODM\MongoDB\Query\Builder;

/**
 * Interface of Doctrine ORM query extensions that supports result production
 * for specific cases such as pagination.
 *
 * @author Samuel ROZE <samuel.roze@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface QueryResultExtensionInterface extends QueryCollectionExtensionInterface
{
    /**
     * @param string      $resourceClass
     * @param string|null $operationName
     *
     * @return bool
     */
    public function supportsResult(string $resourceClass, string $operationName = null): bool;

    /**
     * @param Builder $queryBuilder
     *
     * @return mixed
     */
    public function getResult(Builder $queryBuilder);
}
