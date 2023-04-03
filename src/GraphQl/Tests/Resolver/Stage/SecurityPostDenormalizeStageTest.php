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

use ApiPlatform\GraphQl\Resolver\Stage\SecurityPostDenormalizeStage;
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
class SecurityPostDenormalizeStageTest extends TestCase
{
    use ProphecyTrait;

    private SecurityPostDenormalizeStage $securityPostDenormalizeStage;
    private ObjectProphecy $resourceAccessCheckerProphecy;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->resourceAccessCheckerProphecy = $this->prophesize(ResourceAccessCheckerInterface::class);

        $this->securityPostDenormalizeStage = new SecurityPostDenormalizeStage(
            $this->resourceAccessCheckerProphecy->reveal()
        );
    }

    public function testNoSecurity(): void
    {
        $resourceClass = 'myResource';
        $operation = new Query();

        $this->resourceAccessCheckerProphecy->isGranted(Argument::cetera())->shouldNotBeCalled();

        ($this->securityPostDenormalizeStage)($resourceClass, $operation, []);
    }

    public function testGranted(): void
    {
        $operationName = 'item_query';
        $resourceClass = 'myResource';
        $isGranted = 'not_granted';
        $extraVariables = ['extra' => false];
        /** @var Operation $operation */
        $operation = (new Query())->withSecurityPostDenormalize($isGranted)->withName($operationName);

        $this->resourceAccessCheckerProphecy->isGranted($resourceClass, $isGranted, $extraVariables)->shouldBeCalled()->willReturn(true);

        ($this->securityPostDenormalizeStage)($resourceClass, $operation, ['extra_variables' => $extraVariables]);
    }

    public function testNotGranted(): void
    {
        $operationName = 'item_query';
        $resourceClass = 'myResource';
        $isGranted = 'not_granted';
        $extraVariables = ['extra' => false];
        /** @var Operation $operation */
        $operation = (new Query())->withSecurityPostDenormalize($isGranted)->withName($operationName);

        $this->resourceAccessCheckerProphecy->isGranted($resourceClass, $isGranted, $extraVariables)->shouldBeCalled()->willReturn(false);

        $info = $this->prophesize(ResolveInfo::class)->reveal();

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Access Denied.');

        ($this->securityPostDenormalizeStage)($resourceClass, $operation, [
            'info' => $info,
            'extra_variables' => $extraVariables,
        ]);
    }

    public function testNoSecurityBundleInstalled(): void
    {
        $this->securityPostDenormalizeStage = new SecurityPostDenormalizeStage(null);

        $operationName = 'item_query';
        $resourceClass = 'myResource';
        $isGranted = 'not_granted';
        /** @var Operation $operation */
        $operation = (new Query())->withSecurityPostDenormalize($isGranted)->withName($operationName);

        $this->expectException(\LogicException::class);

        ($this->securityPostDenormalizeStage)($resourceClass, $operation, []);
    }

    public function testNoSecurityBundleInstalledNoExpression(): void
    {
        $this->securityPostDenormalizeStage = new SecurityPostDenormalizeStage(null);

        $operationName = 'item_query';
        $resourceClass = 'myResource';
        /** @var Operation $operation */
        $operation = (new Query())->withName($operationName);

        $this->resourceAccessCheckerProphecy->isGranted(Argument::any())->shouldNotBeCalled();

        ($this->securityPostDenormalizeStage)($resourceClass, $operation, []);
    }
}
