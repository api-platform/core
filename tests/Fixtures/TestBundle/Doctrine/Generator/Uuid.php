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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Doctrine\Generator;

class Uuid implements \JsonSerializable, \Stringable
{
    private string $id = 'foo';

    public function __construct()
    {
    }

    public function __toString(): string
    {
        return $this->id;
    }

    public function jsonSerialize(): mixed
    {
        return $this->id;
    }
}
