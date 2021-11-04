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

namespace ApiPlatform\Core\Tests\Operation;

use ApiPlatform\Core\Operation\DashPathSegmentNameGenerator;
use PHPUnit\Framework\TestCase;

class DashedPathSegmentNameGeneratorTest extends TestCase
{
    public function testCreateSegmentNameGeneration()
    {
        $generator = new DashPathSegmentNameGenerator();
        $this->assertSame('ordering-people', $generator->getSegmentName('orderingPerson'));
        $this->assertSame('some-person-names', $generator->getSegmentName('somePersonName'));
    }
}
