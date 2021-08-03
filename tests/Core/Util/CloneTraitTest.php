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

namespace ApiPlatform\Core\Tests\Util;

use ApiPlatform\Core\Util\CloneTrait;
use PHPUnit\Framework\TestCase;

/**
 * @author Quentin Barloy <quentin.barloy@gmail.com>
 */
class CloneTraitTest extends TestCase
{
    use CloneTrait;

    public function testScalarClone(): void
    {
        $this->assertSame(5, $this->clone(5));
    }

    public function testObjectClone(): void
    {
        $data = new \stdClass();
        $result = $this->clone($data);

        $this->assertNotSame($data, $result);
        $this->assertEquals($data, $result);
    }

    public function testGeneratorClone(): void
    {
        $this->assertNull($this->clone($this->generator()));
    }

    private function generator(): \Generator
    {
        yield 1;
    }
}
