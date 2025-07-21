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

namespace ApiPlatform\State\Tests\Provider;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\ResourceAccessCheckerInterface;
use ApiPlatform\State\Provider\SecurityParameterProvider;
use ApiPlatform\State\ProviderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class SecurityParameterProviderTest extends TestCase
{
    public function testIsGrantedLink(): void
    {
        $obj = new \stdClass();
        $barObj = new \stdClass();
        $operation = new GetCollection(uriVariables: [
            'barId' => new Link(toProperty: 'bar', fromClass: 'Bar', security: 'is_granted("some_voter", "bar")'),
        ], class: \stdClass::class);
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->method('provide')->willReturn($obj);
        $request = $this->createMock(Request::class);
        $parameterBag = new ParameterBag();
        $request->attributes = $parameterBag;
        $request->attributes->set('bar', $barObj);
        $resourceAccessChecker = $this->createMock(ResourceAccessCheckerInterface::class);
        $resourceAccessChecker->expects($this->once())->method('isGranted')->with('Bar', 'is_granted("some_voter", "bar")', ['object' => $obj, 'previous_object' => null, 'request' => $request, 'bar' => $barObj, 'barId' => 1, 'operation' => $operation])->willReturn(true);
        $accessChecker = new SecurityParameterProvider($decorated, $resourceAccessChecker);
        $accessChecker->provide($operation, ['barId' => 1], ['request' => $request]);
    }

    public function testIsNotGrantedLink(): void
    {
        $this->expectException(AccessDeniedHttpException::class);

        $obj = new \stdClass();
        $barObj = new \stdClass();
        $operation = new GetCollection(uriVariables: [
            'barId' => new Link(toProperty: 'bar', fromClass: 'Bar', security: 'is_granted("some_voter", "bar")'),
        ], class: \stdClass::class);
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->method('provide')->willReturn($obj);
        $request = $this->createMock(Request::class);
        $parameterBag = new ParameterBag();
        $request->attributes = $parameterBag;
        $request->attributes->set('bar', $barObj);
        $resourceAccessChecker = $this->createMock(ResourceAccessCheckerInterface::class);
        $resourceAccessChecker->expects($this->once())->method('isGranted')->with('Bar', 'is_granted("some_voter", "bar")', ['object' => $obj, 'previous_object' => null, 'request' => $request, 'bar' => $barObj, 'barId' => 1, 'operation' => $operation])->willReturn(false);
        $accessChecker = new SecurityParameterProvider($decorated, $resourceAccessChecker);
        $accessChecker->provide($operation, ['barId' => 1], ['request' => $request]);
    }

    public function testSecurityMessageLink(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not admin.');

        $obj = new \stdClass();
        $barObj = new \stdClass();
        $operation = new GetCollection(uriVariables: [
            'barId' => new Link(toProperty: 'bar', fromClass: 'Bar', security: 'is_granted("some_voter", "bar")', securityMessage: 'You are not admin.'),
        ], class: \stdClass::class);
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->method('provide')->willReturn($obj);
        $request = $this->createMock(Request::class);
        $parameterBag = new ParameterBag();
        $request->attributes = $parameterBag;
        $request->attributes->set('bar', $barObj);
        $resourceAccessChecker = $this->createMock(ResourceAccessCheckerInterface::class);
        $resourceAccessChecker->expects($this->once())->method('isGranted')->with('Bar', 'is_granted("some_voter", "bar")', ['object' => $obj, 'previous_object' => null, 'request' => $request, 'bar' => $barObj, 'barId' => 1, 'operation' => $operation])->willReturn(false);
        $accessChecker = new SecurityParameterProvider($decorated, $resourceAccessChecker);
        $accessChecker->provide($operation, ['barId' => 1], ['request' => $request]);
    }
}
