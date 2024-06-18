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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Metadata\ApiResource;

#[ApiResource]
enum BackedEnumIntegerResource: int
{
    case Yes = 1;
    case No = 2;
    case Maybe = 3;

    public function getDescription(): string
    {
        return match ($this) {
            self::Yes => 'We say yes',
            self::No => 'Computer says no',
            self::Maybe => 'Let me think about it',
        };
    }
}
