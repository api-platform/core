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

use ApiPlatform\Mcp\Security\ExpressionAccessChecker;
use ApiPlatform\Metadata\McpTool;
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactoryInterface;
use ApiPlatform\Metadata\ResourceAccessCheckerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\SyntaxError;

class ExpressionAccessCheckerTest extends TestCase
{
    public function testGrantedWhenOperationIsNotFound(): void
    {
        $operationMetadataFactory = $this->createMock(OperationMetadataFactoryInterface::class);
        $operationMetadataFactory->method('create')->willReturn(null);

        $checker = new ExpressionAccessChecker($operationMetadataFactory, $this->createMock(ResourceAccessCheckerInterface::class));

        $this->assertTrue($checker->isGranted('unknown'));
    }

    public function testGrantedWhenOperationHasNoSecurity(): void
    {
        $operation = new McpTool(name: 'public', description: 'Public', structuredContent: false, class: \stdClass::class);

        $operationMetadataFactory = $this->createMock(OperationMetadataFactoryInterface::class);
        $operationMetadataFactory->method('create')->willReturn($operation);

        $checker = new ExpressionAccessChecker($operationMetadataFactory, $this->createMock(ResourceAccessCheckerInterface::class));

        $this->assertTrue($checker->isGranted('public'));
    }

    public function testGrantedWhenAccessCheckerGrants(): void
    {
        $operation = new McpTool(name: 'secured', description: 'Secured', structuredContent: false, class: \stdClass::class, security: "is_granted('ROLE_ADMIN')");

        $operationMetadataFactory = $this->createMock(OperationMetadataFactoryInterface::class);
        $operationMetadataFactory->method('create')->willReturn($operation);

        $resourceAccessChecker = $this->createMock(ResourceAccessCheckerInterface::class);
        $resourceAccessChecker->expects($this->once())->method('isGranted')->with(\stdClass::class, "is_granted('ROLE_ADMIN')")->willReturn(true);

        $checker = new ExpressionAccessChecker($operationMetadataFactory, $resourceAccessChecker);

        $this->assertTrue($checker->isGranted('secured'));
    }

    public function testDeniedWhenAccessCheckerDenies(): void
    {
        $operation = new McpTool(name: 'secured', description: 'Secured', structuredContent: false, class: \stdClass::class, security: "is_granted('ROLE_ADMIN')");

        $operationMetadataFactory = $this->createMock(OperationMetadataFactoryInterface::class);
        $operationMetadataFactory->method('create')->willReturn($operation);

        $resourceAccessChecker = $this->createMock(ResourceAccessCheckerInterface::class);
        $resourceAccessChecker->method('isGranted')->willReturn(false);

        $checker = new ExpressionAccessChecker($operationMetadataFactory, $resourceAccessChecker);

        $this->assertFalse($checker->isGranted('secured'));
    }

    public function testGrantedWhenAccessCheckerThrowsSyntaxError(): void
    {
        $operation = new McpTool(name: 'secured', description: 'Secured', structuredContent: false, class: \stdClass::class, security: 'object.owner == user');

        $operationMetadataFactory = $this->createMock(OperationMetadataFactoryInterface::class);
        $operationMetadataFactory->method('create')->willReturn($operation);

        $resourceAccessChecker = $this->createMock(ResourceAccessCheckerInterface::class);
        $resourceAccessChecker->method('isGranted')->willThrowException(new SyntaxError('Variable "object" is not valid'));

        $checker = new ExpressionAccessChecker($operationMetadataFactory, $resourceAccessChecker);

        $this->assertTrue($checker->isGranted('secured'));
    }
}
