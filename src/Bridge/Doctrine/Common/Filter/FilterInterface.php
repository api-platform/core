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

namespace ApiPlatform\Core\Bridge\Doctrine\Common\Filter;

use ApiPlatform\Core\Api\FilterInterface as BaseFilterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Common\Util\QueryNameGeneratorInterface;

/**
 * Doctrine filter interface.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface FilterInterface extends BaseFilterInterface
{
    /**
     * Applies the filter.
     */
    public function apply($builder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null);
}
