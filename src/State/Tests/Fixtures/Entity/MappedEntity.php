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

namespace ApiPlatform\State\Tests\Fixtures\Entity;

use ApiPlatform\State\Tests\Fixtures\ApiResource\Dummy;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(target: Dummy::class)]
class MappedEntity
{
    public int $id = 1;
}
