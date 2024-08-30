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

namespace ApiPlatform\Metadata\Tests\Util;

use ApiPlatform\Metadata\Util\Reflection;
use PHPUnit\Framework\TestCase;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class ReflectionTest extends TestCase
{
    public function testWithGoodMethodName(): void
    {
        $methodName = 'addGerard';
        $reflection = new Reflection();
        $return = $reflection->getProperty($methodName);
        $this->assertSame($return, 'Gerard');
    }

    public function testWithBadMethodName(): void
    {
        $methodName = 'delGerard';
        $reflection = new Reflection();
        $return = $reflection->getProperty($methodName);
        $this->assertNotEquals($return, 'Gerard');
        $this->assertSame($return, null);
    }
}
