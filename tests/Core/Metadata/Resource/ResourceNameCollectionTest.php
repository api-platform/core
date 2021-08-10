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

namespace ApiPlatform\Core\Tests\Metadata\Resource;

use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use PHPUnit\Framework\TestCase;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ResourceNameCollectionTest extends TestCase
{
    public function testValueObject()
    {
        $collection = new ResourceNameCollection(['foo', 'bar']);
        $this->assertInstanceOf(\Countable::class, $collection);
        $this->assertInstanceOf(\IteratorAggregate::class, $collection);
        $this->assertCount(2, $collection);
        $this->assertInstanceOf(\ArrayIterator::class, $collection->getIterator());
    }
}
