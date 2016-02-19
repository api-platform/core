<?php

/*
 * This file is part of the API Platform Builder package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Builder\Metadata\Property\Factory;

use ApiPlatform\Builder\Exception\ResourceClassNotFoundException;
use ApiPlatform\Builder\Metadata\Property\CollectionMetadata;

/**
 * Creates a collection metadata value object.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface CollectionMetadataFactoryInterface
{
    /**
     * Creates the collection metadata for the given class and options.
     *
     * @param string $resourceClass
     * @param array  $options
     *
     * @return CollectionMetadata
     *
     * @throws ResourceClassNotFoundException
     */
    public function create(string $resourceClass, array $options = []) : CollectionMetadata;
}
