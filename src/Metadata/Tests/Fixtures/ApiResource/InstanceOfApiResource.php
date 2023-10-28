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

namespace ApiPlatform\Metadata\Tests\Fixtures\ApiResource;

use ApiPlatform\Metadata\Tests\Fixtures\Metadata\RestfulApi;

#[RestfulApi]
class InstanceOfApiResource
{
    private $id;

    public function __construct()
    {
    }

    public function getId()
    {
        return $this->id;
    }
}
