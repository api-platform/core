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

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Metadata\ApiResource;

/**
 * Resource linked to an external API.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
#[ApiResource]
class SerializableResource
{
    /**
     * @var int
     *
     * @ApiProperty(identifier=true)
     */
    public $id;
    /**
     * @var string
     */
    public $foo;
    /**
     * @var string
     */
    public $bar;
}
