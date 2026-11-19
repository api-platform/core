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

namespace ApiPlatform\Mcp\Tests\Security;

use ApiPlatform\Mcp\Security\PolicyAccessChecker;
use ApiPlatform\Metadata\McpTool;
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactoryInterface;
use ApiPlatform\Metadata\ResourceAccessCheckerInterface;
use PHPUnit\Framework\TestCase;

class PolicyAccessCheckerTest extends TestCase
{
    public function testGrantedWhenOperationIsNotFound(): void
    {
        $operationMetadataFactory = $this->createMock(OperationMetadataFactoryInterface::class);
        $operationMetadataFactory->method('create')->willReturn(null);

        $checker = new PolicyAccessChecker($operationMetadataFactory, $this->createMock(ResourceAccessCheckerInterface::class));

        $this->assertTrue($checker->isGranted('unknown'));
    }

    public function testGrantedWhenOperationHasNoPolicy(): void
    {
        $operation = new McpTool(name: 'public', description: 'Public', structuredContent: false, class: \stdClass::class);

        $operationMetadataFactory = $this->createMock(OperationMetadataFactoryInterface::class);
        $operationMetadataFactory->method('create')->willReturn($operation);

        $checker = new PolicyAccessChecker($operationMetadataFactory, $this->createMock(ResourceAccessCheckerInterface::class));

        $this->assertTrue($checker->isGranted('public'));
    }

    public function testGrantedWhenAccessCheckerGrants(): void
    {
        $operation = new McpTool(name: 'secured', description: 'Secured', structuredContent: false, class: \stdClass::class, policy: 'view');

        $operationMetadataFactory = $this->createMock(OperationMetadataFactoryInterface::class);
        $operationMetadataFactory->method('create')->willReturn($operation);

        $resourceAccessChecker = $this->createMock(ResourceAccessCheckerInterface::class);
        $resourceAccessChecker->expects($this->once())->method('isGranted')->with(\stdClass::class, 'view', [])->willReturn(true);

        $checker = new PolicyAccessChecker($operationMetadataFactory, $resourceAccessChecker);

        $this->assertTrue($checker->isGranted('secured'));
    }

    public function testDeniedWhenAccessCheckerDenies(): void
    {
        $operation = new McpTool(name: 'secured', description: 'Secured', structuredContent: false, class: \stdClass::class, policy: 'view');

        $operationMetadataFactory = $this->createMock(OperationMetadataFactoryInterface::class);
        $operationMetadataFactory->method('create')->willReturn($operation);

        $resourceAccessChecker = $this->createMock(ResourceAccessCheckerInterface::class);
        $resourceAccessChecker->method('isGranted')->willReturn(false);

        $checker = new PolicyAccessChecker($operationMetadataFactory, $resourceAccessChecker);

        $this->assertFalse($checker->isGranted('secured'));
    }

    public function testGrantedWhenAccessCheckerThrowsArgumentCountError(): void
    {
        $operation = new McpTool(name: 'secured', description: 'Secured', structuredContent: false, class: \stdClass::class, policy: 'view');

        $operationMetadataFactory = $this->createMock(OperationMetadataFactoryInterface::class);
        $operationMetadataFactory->method('create')->willReturn($operation);

        $resourceAccessChecker = $this->createMock(ResourceAccessCheckerInterface::class);
        $resourceAccessChecker->method('isGranted')->willThrowException(new \ArgumentCountError('Too few arguments'));

        $checker = new PolicyAccessChecker($operationMetadataFactory, $resourceAccessChecker);

        $this->assertTrue($checker->isGranted('secured'));
    }

    public function testSecurityIsIgnoredWhenPolicyIsAbsent(): void
    {
        $operation = new McpTool(name: 'secured', description: 'Secured', structuredContent: false, class: \stdClass::class, security: "is_granted('ROLE_ADMIN')");

        $operationMetadataFactory = $this->createMock(OperationMetadataFactoryInterface::class);
        $operationMetadataFactory->method('create')->willReturn($operation);

        $resourceAccessChecker = $this->createMock(ResourceAccessCheckerInterface::class);
        $resourceAccessChecker->expects($this->never())->method('isGranted');

        $checker = new PolicyAccessChecker($operationMetadataFactory, $resourceAccessChecker);

        $this->assertTrue($checker->isGranted('secured'));
    }
}
