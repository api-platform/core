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

use ApiPlatform\Metadata\Get;

#[Get(name: 'a', priority: 1, uriTemplate: 'operation_priority/{id}', provider: [self::class, 'shouldNotBeCalled'])]
#[Get(name: 'b', priority: -1, uriTemplate: 'operation_priority/{id}', provider: [self::class, 'shouldBeCalled'])]
class OperationPriorities
{
    public int $id = 1;

    public static function shouldBeCalled(): self
    {
        return new self();
    }

    public static function shouldNotBeCalled(): self
    {
        throw new \Exception('fail');
    }
}
