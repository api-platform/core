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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6355;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Uid\Uuid;

class OrderDto
{
    #[ApiProperty(identifier: false)]
    public ?int $id = null;

    #[ApiProperty(identifier: true)]
    public ?Uuid $uuid = null;
}
