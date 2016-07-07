<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Naming;

use ApiPlatform\Core\Naming\DashResourcePathNamingStrategy;

class DashResourcePathNamingStrategyTest extends \PHPUnit_Framework_TestCase
{
    public function testGenerateResourceBasePath()
    {
        $dashResourcePathGenerator = new DashResourcePathNamingStrategy();

        $this->assertSame('short-names', $dashResourcePathGenerator->generateResourceBasePath('ShortName'));
    }
}
