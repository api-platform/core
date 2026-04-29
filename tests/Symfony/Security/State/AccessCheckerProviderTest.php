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

namespace ApiPlatform\Tests\Symfony\Security\State;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\ResourceAccessCheckerInterface;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Symfony\Security\Exception\AccessDeniedException;
use ApiPlatform\Symfony\Security\ObjectVariableCheckerInterface;
use ApiPlatform\Symfony\Security\State\AccessCheckerProvider;
use ApiPlatform\Tests\Fixtures\DummyEntity;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AccessCheckerProviderTest extends TestCase
{
    public function testCheckAccess(): void
    {
        $obj = new \stdClass();
        $operation = new Get(class: DummyEntity::class, security: 'hi');
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->method('provide')->willReturn($obj);
        $resourceAccessChecker = $this->createMock(ResourceAccessCheckerInterface::class);
        $resourceAccessChecker->expects($this->once())->method('isGranted')->with(DummyEntity::class, 'hi', ['object' => $obj, 'previous_object' => null, 'request' => null])->willReturn(true);
        $accessChecker = new AccessCheckerProvider($decorated, $resourceAccessChecker);
        $accessChecker->provide($operation, [], []);
    }

    public function testCheckAccessWithEvent(): void
    {
        $obj = new \stdClass();
        $operation = new Get(class: DummyEntity::class, securityPostDenormalize: 'hi');
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->method('provide')->willReturn($obj);
        $resourceAccessChecker = $this->createMock(ResourceAccessCheckerInterface::class);
        $resourceAccessChecker->expects($this->once())->method('isGranted')->with(DummyEntity::class, 'hi', ['object' => $obj, 'previous_object' => null, 'request' => null])->willReturn(true);
        $accessChecker = new AccessCheckerProvider($decorated, $resourceAccessChecker, 'post_denormalize');
        $accessChecker->provide($operation, [], []);
    }

    public function testCheckAccessWithEventPostValidate(): void
    {
        $obj = new \stdClass();
        $operation = new Get(class: DummyEntity::class, securityPostValidation: 'hi');
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->method('provide')->willReturn($obj);
        $resourceAccessChecker = $this->createMock(ResourceAccessCheckerInterface::class);
        $resourceAccessChecker->expects($this->once())->method('isGranted')->with(DummyEntity::class, 'hi', ['object' => $obj, 'previous_object' => null, 'request' => null])->willReturn(true);
        $accessChecker = new AccessCheckerProvider($decorated, $resourceAccessChecker, 'post_validate');
        $accessChecker->provide($operation, [], []);
    }

    public function testPreReadSkipsSecurityWhenResourceAccessCheckerIsDecorated(): void
    {
        $obj = new \stdClass();
        $operation = new Get(class: DummyEntity::class, security: 'is_granted("ROLE_ADMIN")');
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->expects($this->once())->method('provide')->willReturn($obj);
        $resourceAccessChecker = $this->createMock(ResourceAccessCheckerInterface::class);
        $resourceAccessChecker->expects($this->never())->method('isGranted');
        $accessChecker = new AccessCheckerProvider($decorated, $resourceAccessChecker, 'pre_read');
        $this->assertSame($obj, $accessChecker->provide($operation, [], []));
    }

    public function testPreReadChecksSecurityWhenObjectVariableIsNotUsed(): void
    {
        $obj = new \stdClass();
        $operation = new Get(class: DummyEntity::class, security: 'is_granted("ROLE_ADMIN")');
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->expects($this->once())->method('provide')->willReturn($obj);
        $resourceAccessChecker = $this->createMock(ResourceAccessCheckerWithObjectVariableInterface::class);
        $resourceAccessChecker->method('usesObjectVariable')->willReturn(false);
        $resourceAccessChecker->expects($this->once())->method('isGranted')->with(DummyEntity::class, 'is_granted("ROLE_ADMIN")', ['object' => null, 'previous_object' => null, 'request' => null])->willReturn(true);
        $accessChecker = new AccessCheckerProvider($decorated, $resourceAccessChecker, 'pre_read');
        $this->assertSame($obj, $accessChecker->provide($operation, [], []));
    }

    public function testPreReadSkipsSecurityWhenObjectVariableIsUsed(): void
    {
        $obj = new \stdClass();
        $operation = new Get(class: DummyEntity::class, security: 'is_granted("ROLE_ADMIN") and object.owner == user');
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->expects($this->once())->method('provide')->willReturn($obj);
        $resourceAccessChecker = $this->createMock(ResourceAccessCheckerWithObjectVariableInterface::class);
        $resourceAccessChecker->method('usesObjectVariable')->willReturn(true);
        $resourceAccessChecker->expects($this->never())->method('isGranted');
        $accessChecker = new AccessCheckerProvider($decorated, $resourceAccessChecker, 'pre_read');
        $this->assertSame($obj, $accessChecker->provide($operation, [], []));
    }

    public function testCheckAccessDenied(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('hello');

        $obj = new \stdClass();
        $operation = new Get(class: DummyEntity::class, security: 'hi', securityMessage: 'hello');
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->method('provide')->willReturn($obj);
        $resourceAccessChecker = $this->createMock(ResourceAccessCheckerInterface::class);
        $resourceAccessChecker->expects($this->once())->method('isGranted')->with(DummyEntity::class, 'hi', ['object' => $obj, 'previous_object' => null, 'request' => null])->willReturn(false);
        $accessChecker = new AccessCheckerProvider($decorated, $resourceAccessChecker);
        $accessChecker->provide($operation, [], []);
    }

    public function testCheckAccessDeniedWithGraphQl(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('hello');

        $obj = new \stdClass();
        $operation = new Query(class: DummyEntity::class, security: 'hi', securityMessage: 'hello');
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->method('provide')->willReturn($obj);
        $resourceAccessChecker = $this->createMock(ResourceAccessCheckerInterface::class);
        $resourceAccessChecker->expects($this->once())->method('isGranted')->with(DummyEntity::class, 'hi', ['object' => $obj, 'previous_object' => null])->willReturn(false);
        $accessChecker = new AccessCheckerProvider($decorated, $resourceAccessChecker);
        $accessChecker->provide($operation, [], []);
    }
}

interface ResourceAccessCheckerWithObjectVariableInterface extends ResourceAccessCheckerInterface, ObjectVariableCheckerInterface
{
}
