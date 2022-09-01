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

/**
 * Class with multiple resources, each with a GetCollection, a Get and a Post operations.
 * Using itemUriTemplate on GetCollection and Post operations should specify which operation to use to generate the IRI.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
class Car
{
    public $id;
    public $owner;

    public function __construct($id = null, $owner = null)
    {
        $this->id = $id;
        $this->owner = $owner;
    }
}
