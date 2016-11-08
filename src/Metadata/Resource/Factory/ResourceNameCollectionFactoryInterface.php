<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Metadata\Resource\Factory;

use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;

/**
 * Creates a resource name collection value object.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface ResourceNameCollectionFactoryInterface
{
    /**
     * Creates the resource name collection.
     *
     * @return ResourceNameCollection
     */
    public function create(): ResourceNameCollection;
}
