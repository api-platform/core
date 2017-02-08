<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\EventListener;

use ApiPlatform\Core\EventListener\DenyAccessListener;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Prophecy\Argument;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DenyAccessListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testNoResourceClass()
    {
        $request = new Request();

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $event = $eventProphecy->reveal();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create()->shouldNotBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $authorizationCheckerProphecy = $this->prophesize(AuthorizationCheckerInterface::class);
        $authorizationCheckerProphecy->isGranted()->shouldNotBeCalled();
        $authorizationChecker = $authorizationCheckerProphecy->reveal();

        $listener = new DenyAccessListener($resourceMetadataFactory, $authorizationChecker);
        $listener->onKernelRequest($event);
    }

    public function testNoIsGrantedAttribute()
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get']);

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $event = $eventProphecy->reveal();

        $resourceMetadata = new ResourceMetadata();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn($resourceMetadata)->shouldBeCalled();

        $authorizationCheckerProphecy = $this->prophesize(AuthorizationCheckerInterface::class);
        $authorizationCheckerProphecy->isGranted()->shouldNotBeCalled();

        $listener = new DenyAccessListener($resourceMetadataFactoryProphecy->reveal(), $authorizationCheckerProphecy->reveal());
        $listener->onKernelRequest($event);
    }

    public function testIsGranted()
    {
        $data = new \stdClass();
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get', 'data' => $data]);

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $event = $eventProphecy->reveal();

        $resourceMetadata = new ResourceMetadata(null, null, null, null, null, ['is_granted' => 'has_role("ROLE_ADMIN")']);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn($resourceMetadata)->shouldBeCalled();

        $authorizationCheckerProphecy = $this->prophesize(AuthorizationCheckerInterface::class);
        $authorizationCheckerProphecy->isGranted(Argument::type(Expression::class), $data)->willReturn(true)->shouldBeCalled();

        $listener = new DenyAccessListener($resourceMetadataFactoryProphecy->reveal(), $authorizationCheckerProphecy->reveal());
        $listener->onKernelRequest($event);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function testIsNotGranted()
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get']);

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $event = $eventProphecy->reveal();

        $resourceMetadata = new ResourceMetadata(null, null, null, null, null, ['is_granted' => 'has_role("ROLE_ADMIN")']);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn($resourceMetadata)->shouldBeCalled();

        $authorizationCheckerProphecy = $this->prophesize(AuthorizationCheckerInterface::class);
        $authorizationCheckerProphecy->isGranted(Argument::type(Expression::class), null)->willReturn(false)->shouldBeCalled();

        $listener = new DenyAccessListener($resourceMetadataFactoryProphecy->reveal(), $authorizationCheckerProphecy->reveal());
        $listener->onKernelRequest($event);
    }

    /**
     * @expectedException \LogicException
     */
    public function testAuthorizationCheckerNotAvailable()
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get']);

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $event = $eventProphecy->reveal();

        $resourceMetadata = new ResourceMetadata(null, null, null, null, null, ['is_granted' => 'has_role("ROLE_ADMIN")']);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn($resourceMetadata)->shouldBeCalled();

        $listener = new DenyAccessListener($resourceMetadataFactoryProphecy->reveal(), null);
        $listener->onKernelRequest($event);
    }
}
