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
 * Multiple resources with an identifier and multiple collections.
 * A NotExposed operation with a valid path (e.g.: "/forks/{id}") is automatically added to the last resource.
 * This operation does not inherit from the resource uriTemplate as it's not intended to.
 *
 * @see NotExposedOperationResourceMetadataCollectionFactory::create()
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
class Fork
{
    public $id;
    public $owner;

    public function __construct($id, $owner)
    {
        $this->id = $id;
        $this->owner = $owner;
    }
}
