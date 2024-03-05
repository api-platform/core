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

namespace ApiPlatform\Tests\Symfony\EventListener;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Symfony\EventListener\DenyAccessListener;
use ApiPlatform\Symfony\Security\ResourceAccessCheckerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DenyAccessListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testNoResourceClass(): void
    {
        $request = new Request();

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $event = $eventProphecy->reveal();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->shouldNotBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $listener = $this->getListener($resourceMetadataFactory);
        $listener->onSecurity($event);
    }

    public function testNoIsGrantedAttribute(): void
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_operation_name' => 'get']);

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $event = $eventProphecy->reveal();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->shouldBeCalled()->willReturn(new ResourceMetadataCollection('Foo', [
            new ApiResource(operations: [
                'get' => new Get(),
            ]),
        ]));

        $listener = $this->getListener($resourceMetadataFactoryProphecy->reveal());
        $listener->onSecurity($event);
    }

    public function testIsGranted(): void
    {
        $data = new \stdClass();
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_operation_name' => 'get', 'data' => $data]);

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $event = $eventProphecy->reveal();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->shouldBeCalled()->willReturn(new ResourceMetadataCollection('Foo', [
            new ApiResource(operations: [
                'get' => new Get(security: 'is_granted("ROLE_ADMIN")'),
            ]),
        ]));

        $resourceAccessCheckerProphecy = $this->prophesize(ResourceAccessCheckerInterface::class);
        $resourceAccessCheckerProphecy->isGranted('Foo', 'is_granted("ROLE_ADMIN")', Argument::type('array'))->willReturn(true)->shouldBeCalled();

        $listener = $this->getListener($resourceMetadataFactoryProphecy->reveal(), $resourceAccessCheckerProphecy->reveal());
        $listener->onSecurity($event);
    }

    public function testIsNotGranted(): void
    {
        $this->expectException(AccessDeniedException::class);

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_operation_name' => 'get']);

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $event = $eventProphecy->reveal();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->shouldBeCalled()->willReturn(new ResourceMetadataCollection('Foo', [
            new ApiResource(operations: [
                'get' => new Get(security: 'is_granted("ROLE_ADMIN")'),
            ]),
        ]));

        $resourceAccessCheckerProphecy = $this->prophesize(ResourceAccessCheckerInterface::class);
        $resourceAccessCheckerProphecy->isGranted('Foo', 'is_granted("ROLE_ADMIN")', Argument::type('array'))->willReturn(false)->shouldBeCalled();

        $listener = $this->getListener($resourceMetadataFactoryProphecy->reveal(), $resourceAccessCheckerProphecy->reveal());
        $listener->onSecurity($event);
    }

    public function testSecurityMessage(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('You are not admin.');

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_operation_name' => 'get']);

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $event = $eventProphecy->reveal();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->shouldBeCalled()->willReturn(new ResourceMetadataCollection('Foo', [
            new ApiResource(operations: [
                'get' => new Get(security: 'is_granted("ROLE_ADMIN")', securityMessage: 'You are not admin.'),
            ]),
        ]));

        $resourceAccessCheckerProphecy = $this->prophesize(ResourceAccessCheckerInterface::class);
        $resourceAccessCheckerProphecy->isGranted('Foo', 'is_granted("ROLE_ADMIN")', Argument::type('array'))->willReturn(false)->shouldBeCalled();

        $listener = $this->getListener($resourceMetadataFactoryProphecy->reveal(), $resourceAccessCheckerProphecy->reveal());
        $listener->onSecurity($event);
    }

    public function testSecurityComponentNotAvailable(): void
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_operation_name' => 'get']);

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $event = $eventProphecy->reveal();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->shouldNotBeCalled();

        $listener = new DenyAccessListener($resourceMetadataFactoryProphecy->reveal());
        $listener->onSecurity($event);
    }

    private function getListener(ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, ?ResourceAccessCheckerInterface $resourceAccessChecker = null): DenyAccessListener
    {
        if (null === $resourceAccessChecker) {
            $resourceAccessChecker = $this->prophesize(ResourceAccessCheckerInterface::class)->reveal();
        }

        return new DenyAccessListener($resourceMetadataCollectionFactory, $resourceAccessChecker);
    }
}
