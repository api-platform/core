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
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Symfony\Security\Exception\AccessDeniedException;
use ApiPlatform\Symfony\Security\ResourceAccessCheckerInterface;
use ApiPlatform\Symfony\Security\State\AccessCheckerProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AccessCheckerProviderTest extends TestCase
{
    public function testCheckAccess(): void
    {
        $obj = new \stdClass();
        $operation = new Get(class: 'foo', security: 'hi');
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->method('provide')->willReturn($obj);
        $resourceAccessChecker = $this->createMock(ResourceAccessCheckerInterface::class);
        $resourceAccessChecker->expects($this->once())->method('isGranted')->with('foo', 'hi', ['object' => $obj, 'previous_object' => null, 'request' => null])->willReturn(true);
        $accessChecker = new AccessCheckerProvider($decorated, $resourceAccessChecker);
        $accessChecker->provide($operation, [], []);
    }

    public function testCheckAccessWithEvent(): void
    {
        $obj = new \stdClass();
        $operation = new Get(class: 'foo', securityPostDenormalize: 'hi');
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->method('provide')->willReturn($obj);
        $resourceAccessChecker = $this->createMock(ResourceAccessCheckerInterface::class);
        $resourceAccessChecker->expects($this->once())->method('isGranted')->with('foo', 'hi', ['object' => $obj, 'previous_object' => null, 'request' => null])->willReturn(true);
        $accessChecker = new AccessCheckerProvider($decorated, $resourceAccessChecker, 'post_denormalize');
        $accessChecker->provide($operation, [], []);
    }

    public function testCheckAccessWithEventPostValidate(): void
    {
        $obj = new \stdClass();
        $operation = new Get(class: 'foo', securityPostValidation: 'hi');
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->method('provide')->willReturn($obj);
        $resourceAccessChecker = $this->createMock(ResourceAccessCheckerInterface::class);
        $resourceAccessChecker->expects($this->once())->method('isGranted')->with('foo', 'hi', ['object' => $obj, 'previous_object' => null, 'request' => null])->willReturn(true);
        $accessChecker = new AccessCheckerProvider($decorated, $resourceAccessChecker, 'post_validate');
        $accessChecker->provide($operation, [], []);
    }

    public function testCheckAccessDenied(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('hello');

        $obj = new \stdClass();
        $operation = new Get(class: 'foo', security: 'hi', securityMessage: 'hello');
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->method('provide')->willReturn($obj);
        $resourceAccessChecker = $this->createMock(ResourceAccessCheckerInterface::class);
        $resourceAccessChecker->expects($this->once())->method('isGranted')->with('foo', 'hi', ['object' => $obj, 'previous_object' => null, 'request' => null])->willReturn(false);
        $accessChecker = new AccessCheckerProvider($decorated, $resourceAccessChecker);
        $accessChecker->provide($operation, [], []);
    }

    public function testCheckAccessDeniedWithGraphQl(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('hello');

        $obj = new \stdClass();
        $operation = new Query(class: 'foo', security: 'hi', securityMessage: 'hello');
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->method('provide')->willReturn($obj);
        $resourceAccessChecker = $this->createMock(ResourceAccessCheckerInterface::class);
        $resourceAccessChecker->expects($this->once())->method('isGranted')->with('foo', 'hi', ['object' => $obj, 'previous_object' => null])->willReturn(false);
        $accessChecker = new AccessCheckerProvider($decorated, $resourceAccessChecker);
        $accessChecker->provide($operation, [], []);
    }
}
