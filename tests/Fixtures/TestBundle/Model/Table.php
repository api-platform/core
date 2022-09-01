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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Model;

use ApiPlatform\Metadata\Resource\Factory\NotExposedOperationResourceMetadataCollectionFactory;

/**
 * Single resource with an identifier and a single collection.
 * A NotExposed operation with a valid path (e.g.: "/tables/{id}") is automatically added to this resource.
 *
 * @see NotExposedOperationResourceMetadataCollectionFactory::create()
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
class Table
{
    public $id;
    public $owner;

    public function __construct($id, $owner)
    {
        $this->id = $id;
        $this->owner = $owner;
    }
}
