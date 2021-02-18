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

namespace ApiPlatform\Core\Tests\Metadata\Property\Factory;

use ApiPlatform\Core\Exception\PropertyNotFoundException;
use ApiPlatform\Core\Metadata\Property\Factory\DefaultPropertyMetadataFactory;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyPropertyWithDefaultValue;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;

class DefaultPropertyMetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreate()
    {
        $factory = new DefaultPropertyMetadataFactory();
        $metadata = $factory->create(DummyPropertyWithDefaultValue::class, 'foo');

        $this->assertEquals($metadata->getDefault(), 'foo');
    }

    public function testClassDoesNotExist()
    {
        $factory = new DefaultPropertyMetadataFactory();
        $metadata = $factory->create('\DoNotExist', 'foo');

        $this->assertEquals(new PropertyMetadata(), $metadata);
    }

    public function testPropertyDoesNotExist()
    {
        $decoratedProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedProphecy->create(DummyPropertyWithDefaultValue::class, 'doNotExist', [])->willThrow(new PropertyNotFoundException());

        $factory = new DefaultPropertyMetadataFactory($decoratedProphecy->reveal());
        $metadata = $factory->create(DummyPropertyWithDefaultValue::class, 'doNotExist');

        $this->assertEquals(new PropertyMetadata(), $metadata);
    }
}
