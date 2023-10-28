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

namespace ApiPlatform\GraphQl\Tests\Resolver\Stage;

use ApiPlatform\GraphQl\Resolver\Stage\SecurityStage;
use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Symfony\Security\ResourceAccessCheckerInterface;
use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
class SecurityStageTest extends TestCase
{
    use ProphecyTrait;

    private SecurityStage $securityStage;
    private ObjectProphecy $resourceAccessCheckerProphecy;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->resourceAccessCheckerProphecy = $this->prophesize(ResourceAccessCheckerInterface::class);

        $this->securityStage = new SecurityStage(
            $this->resourceAccessCheckerProphecy->reveal()
        );
    }

    public function testNoSecurity(): void
    {
        $operationName = 'item_query';
        $resourceClass = 'myResource';
        /** @var Operation $operation */
        $operation = (new Query())->withName($operationName)->withClass($resourceClass);

        $this->resourceAccessCheckerProphecy->isGranted(Argument::cetera())->shouldNotBeCalled();

        ($this->securityStage)($resourceClass, $operation, []);
    }

    public function testGranted(): void
    {
        $operationName = 'item_query';
        $resourceClass = 'myResource';
        $isGranted = 'not_granted';
        $extraVariables = ['extra' => false];
        /** @var Operation $operation */
        $operation = (new Query())->withName($operationName)->withClass($resourceClass)->withSecurity($isGranted);

        $this->resourceAccessCheckerProphecy->isGranted($resourceClass, $isGranted, $extraVariables)->shouldBeCalled()->willReturn(true);

        ($this->securityStage)($resourceClass, $operation, ['extra_variables' => $extraVariables]);
    }

    public function testNotGranted(): void
    {
        $operationName = 'item_query';
        $resourceClass = 'myResource';
        $isGranted = 'not_granted';
        $extraVariables = ['extra' => false];
        /** @var Operation $operation */
        $operation = (new Query())->withName($operationName)->withClass($resourceClass)->withSecurity($isGranted);

        $this->resourceAccessCheckerProphecy->isGranted($resourceClass, $isGranted, $extraVariables)->shouldBeCalled()->willReturn(false);

        $info = $this->prophesize(ResolveInfo::class)->reveal();

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Access Denied.');

        ($this->securityStage)($resourceClass, $operation, [
            'info' => $info,
            'extra_variables' => $extraVariables,
        ]);
    }

    public function testNoSecurityBundleInstalled(): void
    {
        $this->securityStage = new SecurityStage(null);

        $operationName = 'item_query';
        $resourceClass = 'myResource';
        $isGranted = 'not_granted';
        /** @var Operation $operation */
        $operation = (new Query())->withName($operationName)->withClass($resourceClass)->withSecurity($isGranted);

        $this->expectException(\LogicException::class);

        ($this->securityStage)($resourceClass, $operation, []);
    }

    public function testNoSecurityBundleInstalledNoExpression(): void
    {
        $this->securityStage = new SecurityStage(null);

        $resourceClass = 'myResource';
        $this->resourceAccessCheckerProphecy->isGranted(Argument::any())->shouldNotBeCalled();

        ($this->securityStage)($resourceClass, new Query(), []);
    }
}
