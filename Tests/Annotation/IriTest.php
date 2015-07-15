<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Tests\Annotation;

use Dunglas\ApiBundle\Annotation\Iri;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class IriTest extends \PHPUnit_Framework_TestCase
{
    public function testValue()
    {
        $iri = new Iri();
        $iri->value = 'http://les-tilleuls.coop';

        $this->assertEquals('http://les-tilleuls.coop', $iri->value);
    }
}
