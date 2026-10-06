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

namespace ApiPlatform\Tests\Symfony\Metadata\Resource\Factory;

use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Symfony\Metadata\Resource\Factory\ControllerApiOperationResourceNameCollectionFactory;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ControllerApiOperation\Checkout;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ControllerApiOperation\CheckoutInput;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ControllerApiOperation\CheckoutOutput;
use PHPUnit\Framework\TestCase;

final class ControllerApiOperationResourceNameCollectionFactoryTest extends TestCase
{
    public function testMergesControllerResourcesWithDecoratedOnes(): void
    {
        $decorated = $this->createStub(ResourceNameCollectionFactoryInterface::class);
        $decorated->method('create')->willReturn(new ResourceNameCollection([Checkout::class, CheckoutOutput::class]));

        $factory = new ControllerApiOperationResourceNameCollectionFactory($decorated, [CheckoutOutput::class => true, CheckoutInput::class => true]);

        $this->assertSame([Checkout::class, CheckoutOutput::class, CheckoutInput::class], iterator_to_array($factory->create(), false));
    }

    public function testReturnsDecoratedResourcesWhenNoControllerResource(): void
    {
        $decorated = $this->createStub(ResourceNameCollectionFactoryInterface::class);
        $decorated->method('create')->willReturn(new ResourceNameCollection([Checkout::class]));

        $factory = new ControllerApiOperationResourceNameCollectionFactory($decorated, []);

        $this->assertSame([Checkout::class], iterator_to_array($factory->create(), false));
    }
}
