<?php

/*
 * This file is part of the API Platform Builder package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Metadata\Property\Factory;

use ApiPlatform\Core\Exception\PropertyNotFoundException;
use ApiPlatform\Core\Metadata\Property\ItemMetadata;

/**
 * Creates an item metadata value object.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface ItemMetadataFactoryInterface
{
    /**
     * Creates a property item metadata.
     *
     * @param string $resourceClass
     * @param string $property
     * @param array  $options
     *
     * @return ItemMetadata
     *
     * @throws PropertyNotFoundException
     */
    public function create(string $resourceClass, string $property, array $options = []) : ItemMetadata;
}
