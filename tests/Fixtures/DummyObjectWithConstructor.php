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

namespace ApiPlatform\Core\Tests\Fixtures;

/**
 * @author Maxime Veber <maxime.veber@nekland.fr>
 */
class DummyObjectWithConstructor
{
    private $foo;
    private $bar;

    public function __construct(string $foo, \stdClass $bar)
    {
        $this->foo = $foo;
        $this->bar = $bar;
    }
}
