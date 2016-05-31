<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Routing;

use ApiPlatform\Core\Routing\DashResourcePathGenerator;

class DashResourcePathGeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function testGenerateResourceBasePath()
    {
        $dashResourcePathGenerator = new DashResourcePathGenerator();

        $this->assertSame('short-names', $dashResourcePathGenerator->generateResourceBasePath('ShortName'));
    }
}
