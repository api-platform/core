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

namespace ApiPlatform\Tests\Fixtures;

/**
 * @author Maxime Veber <maxime.veber@nekland.fr>
 */
class DummyObjectWithConstructor
{
    // @phpstan-ignore-line

    public function __construct(private readonly string $foo, private readonly \stdClass $bar)
    {
    }
}
