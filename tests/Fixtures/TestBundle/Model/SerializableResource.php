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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;

/**
 * Resource linked to an external API.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
#[ApiResource]
class SerializableResource
{
    #[ApiProperty(identifier: true)]
    public int $id;

    public string $foo;

    public string $bar;
}
