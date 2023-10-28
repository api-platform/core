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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5896;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Get;

#[Get]
class Foo
{
    #[ApiProperty(readable: false, writable: false, identifier: true)]
    public ?int $id = null;
    public ?LocalDate $expiration;
}
