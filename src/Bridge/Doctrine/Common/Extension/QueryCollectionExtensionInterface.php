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

namespace ApiPlatform\Core\Bridge\Doctrine\Common\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Common\Util\QueryNameGeneratorInterface;

/**
 * Interface of Doctrine query extensions for collection queries.
 *
 * @author Samuel ROZE <samuel.roze@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface QueryCollectionExtensionInterface
{
    public function applyToCollection($builder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null);
}
