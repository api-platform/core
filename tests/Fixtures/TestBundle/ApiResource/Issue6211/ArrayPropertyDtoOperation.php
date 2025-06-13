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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6211;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\ArrayPropertyDto;

#[Get(provider: [ArrayPropertyDtoOperation::class, 'provide'], output: ArrayPropertyDto::class, openapi: false)]
class ArrayPropertyDtoOperation
{
    public static function provide(): ArrayPropertyDto
    {
        $d = new ArrayPropertyDto();
        $d->name = 'test';
        $c = new ArrayPropertyDto();
        $c->name = 'test2';
        $d->greetings = [$c];

        return $d;
    }
}
