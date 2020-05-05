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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\PropertyInfo\Metadata\Property;

use ApiPlatform\Core\Bridge\Symfony\PropertyInfo\Metadata\Property\PropertyInfoPropertyNameCollectionFactory;
use ApiPlatform\Core\Tests\Fixtures\DummyObjectWithoutPublicProperties;
use ApiPlatform\Core\Tests\Fixtures\DummyObjectWithPublicProperties;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
class PropertyInfoPropertyNameCollectionFactoryTest extends TestCase
{
    public function testCreateMethodReturnsEmptyPropertyNameCollection()
    {
        $factory = new PropertyInfoPropertyNameCollectionFactory(new PropertyInfoExtractor());
        $collection = $factory->create(DummyObjectWithoutPublicProperties::class);

        self::assertCount(0, $collection->getIterator());
    }

    public function testCreateMethodReturnsProperPropertyNameCollection()
    {
        $factory = new PropertyInfoPropertyNameCollectionFactory(new PropertyInfoExtractor());
        $collection = $factory->create(DummyObjectWithPublicProperties::class);

        self::assertCount(2, $collection->getIterator());
    }
}
