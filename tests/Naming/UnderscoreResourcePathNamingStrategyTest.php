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

use ApiPlatform\Core\Naming\UnderscoreResourcePathNamingStrategy;

class UnderscoreResourcePathNamingStrategyTest extends \PHPUnit_Framework_TestCase
{
    public function testGenerateResourceBasePath()
    {
        $underscoreResourcePathGenerator = new UnderscoreResourcePathNamingStrategy();

        $this->assertSame('short_names', $underscoreResourcePathGenerator->generateResourceBasePath('ShortName'));
    }
}
