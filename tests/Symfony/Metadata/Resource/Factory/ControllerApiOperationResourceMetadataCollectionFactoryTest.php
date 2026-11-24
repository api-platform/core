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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
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
        $this->assertInstanceOf(HttpOperation::class, $operation);
        $this->assertSame('inner_route_name', $operation->getRouteName());
        $this->assertSame(ControllerApiOperationDefinitions::class.'::withInnerRouteName', $operation->getController());
        $this->assertSame(CheckoutOutput::class, $operation->getClass());
    }

    public function testGetDefaultsToNoRead(): void
    {
        $this->assertFalse($this->createFactory('withGet')->create(CheckoutOutput::class)->getOperation('get_route')->canRead());
    }

    public function testGetCollectionDefaultsToNoRead(): void
    {
        $this->assertFalse($this->createFactory('withGetCollection')->create(CheckoutOutput::class)->getOperation('get_collection_route')->canRead());
    }

    public function testExplicitReadIsKept(): void
    {
        $this->assertTrue($this->createFactory('withExplicitRead')->create(CheckoutOutput::class)->getOperation('get_explicit_read_route')->canRead());
    }

    public function testPatchKeepsReadUnset(): void
    {
        $this->assertNull($this->createFactory('withPatch')->create(CheckoutOutput::class)->getOperation('patch_route')->canRead());
    }

    public function testExplicitRouteNameWins(): void
    {
        $operation = $this->createFactory('withExplicitRouteName')->create(CheckoutOutput::class)->getOperation('explicit_route_name');

        $this->assertSame('explicit_route_name', $operation->getRouteName());
    }

    public function testInheritsExistingResource(): void
    {
        $existing = (new ApiResource(class: CheckoutOutput::class, shortName: 'CustomName', normalizationContext: ['groups' => ['custom']]))
            ->withOperations(new Operations(['existing' => new Get(name: 'existing', shortName: 'CustomName', class: CheckoutOutput::class)]));
        $decorated = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $decorated->method('create')->willReturn(new ResourceMetadataCollection(CheckoutOutput::class, [$existing]));

        $collection = $this->createFactory('withInnerRouteName', $decorated)->create(CheckoutOutput::class);

        $this->assertCount(1, $collection);
        $operations = iterator_to_array($collection[0]->getOperations());
        $this->assertArrayHasKey('existing', $operations);
        $operation = $collection->getOperation('inner_route_name');
        $this->assertSame('CustomName', $operation->getShortName());
        $this->assertSame(['groups' => ['custom']], $operation->getNormalizationContext());
    }

    public function testThrowsWhenRouteMethodsExcludeOperationMethod(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('GET');

        $this->createFactory('withMismatchedMethod')->create(CheckoutOutput::class);
    }

    private function createFactory(string $method, ?ResourceMetadataCollectionFactoryInterface $decorated = null): ControllerApiOperationResourceMetadataCollectionFactory
    {
        return new ControllerApiOperationResourceMetadataCollectionFactory([CheckoutOutput::class => [ControllerApiOperationDefinitions::class.'::'.$method]], $decorated);
    }
}
