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

namespace ApiPlatform\Core\Tests\Api;

use ApiPlatform\Core\Api\FilterCollection;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class FilterCollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testIsArrayObject()
    {
        $filterCollection = new FilterCollection();
        $this->assertInstanceOf(\ArrayObject::class, $filterCollection);
    }
}
