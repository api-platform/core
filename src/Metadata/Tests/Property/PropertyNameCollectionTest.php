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

namespace ApiPlatform\Metadata\Tests\Property;

use ApiPlatform\Metadata\Property\PropertyNameCollection;
use PHPUnit\Framework\TestCase;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class PropertyNameCollectionTest extends TestCase
{
    public function testValueObject(): void
    {
        $collection = new PropertyNameCollection(['foo', 'bar']);
        $this->assertInstanceOf(\Countable::class, $collection);
        $this->assertInstanceOf(\IteratorAggregate::class, $collection);
        $this->assertCount(2, $collection);
        $this->assertInstanceOf(\ArrayIterator::class, $collection->getIterator());
    }
}
