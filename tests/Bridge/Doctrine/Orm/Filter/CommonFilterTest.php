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

namespace ApiPlatform\Core\Tests\Bridge\Doctrine\Orm\Filter;

use ApiPlatform\Core\Tests\Fixtures\TestBundle\Doctrine\Orm\Filter\DummyFilter;
use Doctrine\Common\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

/**
 * Regression test case on issue 1154.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class CommonFilterTest extends TestCase
{
    public function testSplitPropertiesWithoutResourceClass()
    {
        $managerRegistry = $this->prophesize(ManagerRegistry::class);

        $filter = new DummyFilter($managerRegistry->reveal());

        $this->assertEquals($filter->doSplitPropertiesWithoutResourceClass('foo.bar'), [
            'associations' => ['foo'],
            'field' => 'bar',
        ]);
    }
}
