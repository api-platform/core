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

namespace ApiPlatform\Core\Tests\Security\EventListener;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Security\EventListener\DenyAccessListener;
use ApiPlatform\Core\Security\ExpressionLanguage;
use ApiPlatform\Core\Security\ResourceAccessCheckerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Role\RoleHierarchy;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DenyAccessListenerTest extends TestCase
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

        $listener = $this->getListener($resourceMetadataFactory);
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

        $listener = $this->getListener($resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelRequest($event);
    }

    public function testIsGranted()
    {
        $data = new \stdClass();
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get', 'data' => $data]);

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $event = $eventProphecy->reveal();

        $resourceMetadata = new ResourceMetadata(null, null, null, null, null, ['access_control' => 'has_role("ROLE_ADMIN")']);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn($resourceMetadata)->shouldBeCalled();

        $resourceAccessCheckerProphecy = $this->prophesize(ResourceAccessCheckerInterface::class);
        $resourceAccessCheckerProphecy->isGranted('Foo', 'has_role("ROLE_ADMIN")', Argument::type('array'))->willReturn(true)->shouldBeCalled();

        $listener = $this->getListener($resourceMetadataFactoryProphecy->reveal(), $resourceAccessCheckerProphecy->reveal());
        $listener->onKernelRequest($event);
    }

    public function testIsNotGranted()
    {
        $this->expectException(AccessDeniedException::class);

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get']);

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $event = $eventProphecy->reveal();

        $resourceMetadata = new ResourceMetadata(null, null, null, null, null, ['access_control' => 'has_role("ROLE_ADMIN")']);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn($resourceMetadata)->shouldBeCalled();

        $resourceAccessCheckerProphecy = $this->prophesize(ResourceAccessCheckerInterface::class);
        $resourceAccessCheckerProphecy->isGranted('Foo', 'has_role("ROLE_ADMIN")', Argument::type('array'))->willReturn(false)->shouldBeCalled();

        $listener = $this->getListener($resourceMetadataFactoryProphecy->reveal(), $resourceAccessCheckerProphecy->reveal());
        $listener->onKernelRequest($event);
    }

    public function testAccessControlMessage()
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('You are not admin.');

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get']);

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $event = $eventProphecy->reveal();

        $resourceMetadata = new ResourceMetadata(null, null, null, null, null, ['access_control' => 'has_role("ROLE_ADMIN")', 'access_control_message' => 'You are not admin.']);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn($resourceMetadata)->shouldBeCalled();

        $resourceAccessCheckerProphecy = $this->prophesize(ResourceAccessCheckerInterface::class);
        $resourceAccessCheckerProphecy->isGranted('Foo', 'has_role("ROLE_ADMIN")', Argument::type('array'))->willReturn(false)->shouldBeCalled();

        $listener = $this->getListener($resourceMetadataFactoryProphecy->reveal(), $resourceAccessCheckerProphecy->reveal());
        $listener->onKernelRequest($event);
    }

    /**
     * @group legacy
     */
    public function testIsGrantedLegacy()
    {
        $data = new \stdClass();
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get', 'data' => $data]);

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $event = $eventProphecy->reveal();

        $resourceMetadata = new ResourceMetadata(null, null, null, null, null, ['access_control' => 'has_role("ROLE_ADMIN")']);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn($resourceMetadata)->shouldBeCalled();

        $expressionLanguageProphecy = $this->prophesize(ExpressionLanguage::class);
        $expressionLanguageProphecy->evaluate('has_role("ROLE_ADMIN")', Argument::type('array'))->willReturn(true)->shouldBeCalled();

        $listener = $this->getLegacyListener($resourceMetadataFactoryProphecy->reveal(), $expressionLanguageProphecy->reveal());
        $listener->onKernelRequest($event);
    }

    /**
     * @group legacy
     */
    public function testIsNotGrantedLegacy()
    {
        $this->expectException(AccessDeniedException::class);

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get']);

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $event = $eventProphecy->reveal();

        $resourceMetadata = new ResourceMetadata(null, null, null, null, null, ['access_control' => 'has_role("ROLE_ADMIN")']);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn($resourceMetadata)->shouldBeCalled();

        $expressionLanguageProphecy = $this->prophesize(ExpressionLanguage::class);
        $expressionLanguageProphecy->evaluate('has_role("ROLE_ADMIN")', Argument::type('array'))->willReturn(false)->shouldBeCalled();

        $listener = $this->getLegacyListener($resourceMetadataFactoryProphecy->reveal(), $expressionLanguageProphecy->reveal());
        $listener->onKernelRequest($event);
    }

    /**
     * @group legacy
     */
    public function testSecurityComponentNotAvailable()
    {
        $this->expectException(\LogicException::class);

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get']);

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $event = $eventProphecy->reveal();

        $resourceMetadata = new ResourceMetadata(null, null, null, null, null, ['access_control' => 'has_role("ROLE_ADMIN")']);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn($resourceMetadata)->shouldBeCalled();

        $listener = new DenyAccessListener($resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelRequest($event);
    }

    /**
     * @group legacy
     */
    public function testExpressionLanguageNotInstalled()
    {
        $this->expectException(\LogicException::class);

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get']);

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $event = $eventProphecy->reveal();

        $resourceMetadata = new ResourceMetadata(null, null, null, null, null, ['access_control' => 'has_role("ROLE_ADMIN")']);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn($resourceMetadata)->shouldBeCalled();

        $authenticationTrustResolverProphecy = $this->prophesize(AuthenticationTrustResolverInterface::class);
        $tokenStorageProphecy = $this->prophesize(TokenStorageInterface::class);
        $tokenStorageProphecy->getToken()->willReturn($this->prophesize(TokenInterface::class)->reveal());

        $listener = new DenyAccessListener($resourceMetadataFactoryProphecy->reveal(), null, $authenticationTrustResolverProphecy->reveal(), null, $tokenStorageProphecy->reveal());
        $listener->onKernelRequest($event);
    }

    /**
     * @group legacy
     */
    public function testNotBehindAFirewall()
    {
        $this->expectException(\LogicException::class);

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get']);

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $event = $eventProphecy->reveal();

        $resourceMetadata = new ResourceMetadata(null, null, null, null, null, ['access_control' => 'has_role("ROLE_ADMIN")']);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn($resourceMetadata)->shouldBeCalled();

        $authenticationTrustResolverProphecy = $this->prophesize(AuthenticationTrustResolverInterface::class);
        $tokenStorageProphecy = $this->prophesize(TokenStorageInterface::class);

        $listener = new DenyAccessListener($resourceMetadataFactoryProphecy->reveal(), null, $authenticationTrustResolverProphecy->reveal(), null, $tokenStorageProphecy->reveal());
        $listener->onKernelRequest($event);
    }

    private function getListener(ResourceMetadataFactoryInterface $resourceMetadataFactory, ResourceAccessCheckerInterface $resourceAccessChecker = null)
    {
        if (null === $resourceAccessChecker) {
            $resourceAccessChecker = $this->prophesize(ResourceAccessCheckerInterface::class)->reveal();
        }

        return new DenyAccessListener($resourceMetadataFactory, $resourceAccessChecker);
    }

    private function getLegacyListener(ResourceMetadataFactoryInterface $resourceMetadataFactory, ExpressionLanguage $expressionLanguage)
    {
        $authenticationTrustResolverProphecy = $this->prophesize(AuthenticationTrustResolverInterface::class);

        $roleHierarchyInterfaceProphecy = $this->prophesize(RoleHierarchyInterface::class);
        $roleHierarchyInterfaceProphecy->{method_exists(RoleHierarchy::class, 'getReachableRoleNames') ? 'getReachableRoleNames' : 'getReachableRoles'}(Argument::type('array'))->willReturn([]);

        $tokenProphecy = $this->prophesize(AbstractToken::class);
        $tokenProphecy->getUser()->willReturn('anon.');
        $tokenProphecy->{method_exists(AbstractToken::class, 'getRoleNames') ? 'getRoleNames' : 'getRoles'}()->willReturn([]);

        $tokenStorageProphecy = $this->prophesize(TokenStorageInterface::class);
        $tokenStorageProphecy->getToken()->willReturn($tokenProphecy)->shouldBeCalled();

        $authorizationCheckerInterface = $this->prophesize(AuthorizationCheckerInterface::class);

        return new DenyAccessListener(
            $resourceMetadataFactory,
            $expressionLanguage,
            $authenticationTrustResolverProphecy->reveal(),
            $roleHierarchyInterfaceProphecy->reveal(),
            $tokenStorageProphecy->reveal(),
            $authorizationCheckerInterface->reveal()
        );
    }
}
