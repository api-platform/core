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

namespace ApiPlatform\Core\DataProvider;

use ApiPlatform\Core\Exception\ItemNotFoundException;
use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;

/**
 * Retrieves items from a persistence layer.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface CollectionDataProviderInterface
{
    /**
     * Retrieves a collection.
     *
     * @throws ResourceClassNotSupportedException
     * @throws ItemNotFoundException              if the collection does not exist
     *
     * @return array|\Traversable
     */
    public function getCollection(string $resourceClass, string $operationName = null);
}
