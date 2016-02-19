<?php

/*
 * This file is part of the API Platform Builder package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Builder\Metadata\Resource\Factory;

use ApiPlatform\Builder\Metadata\Resource\CollectionMetadata;

/**
 * Creates a collection metadata value object.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface CollectionMetadataFactoryInterface
{
    /**
     * Creates the collection metadata.
     *
     * @return CollectionMetadata
     */
    public function create() : CollectionMetadata;
}
