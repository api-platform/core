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

use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Symfony\Metadata\Resource\Factory\ControllerApiOperationResourceMetadataCollectionFactory;
use ApiPlatform\Tests\Fixtures\ControllerApiOperation\ControllerApiOperationDefinitions;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ControllerApiOperation\CheckoutOutput;
use PHPUnit\Framework\TestCase;

final class ControllerApiOperationResourceMetadataCollectionFactoryTest extends TestCase
{
    public function testUriTemplateIsForbidden(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must not define a "uriTemplate"');

        $this->createFactory('withUriTemplate')->create(CheckoutOutput::class);
    }

    public function testNamedRouteIsRequired(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('requires a named');

        $this->createFactory('withUnnamedRoute')->create(CheckoutOutput::class);
    }

    public function testOperationIsBoundToTheRouteName(): void
    {
        $collection = $this->createFactory('withInnerRouteName')->create(CheckoutOutput::class);

        $this->assertCount(1, $collection);
        $operation = $collection->getOperation('inner_route_name');
        $this->assertSame('inner_route_name', $operation->getRouteName());
        $this->assertSame(ControllerApiOperationDefinitions::class.'::withInnerRouteName', $operation->getController());
        $this->assertSame(CheckoutOutput::class, $operation->getClass());
    }

    private function createFactory(string $method): ControllerApiOperationResourceMetadataCollectionFactory
    {
        return new ControllerApiOperationResourceMetadataCollectionFactory([CheckoutOutput::class => [ControllerApiOperationDefinitions::class.'::'.$method]]);
    }
}
