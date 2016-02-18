<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Metadata\Resource\Factory;

use Dunglas\ApiBundle\Exception\ResourceClassNotFoundException;
use Dunglas\ApiBundle\Metadata\Resource\ItemMetadataInterface;

/**
 * Creates an item metadata value object.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface ItemMetadataFactoryInterface
{
    /**
     * Creates a resource item metadata.
     *
     * @param string $resourceClass
     *
     * @return ItemMetadataInterface
     *
     * @throws ResourceClassNotFoundException
     */
    public function create(string $resourceClass) : ItemMetadataInterface;
}
