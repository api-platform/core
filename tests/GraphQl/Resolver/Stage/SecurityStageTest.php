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

namespace ApiPlatform\Core\Tests\GraphQl\Resolver\Stage;

use ApiPlatform\Core\GraphQl\Resolver\Stage\SecurityStage;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Security\ResourceAccessCheckerInterface;
use ApiPlatform\Core\Tests\ProphecyTrait;
use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
class SecurityStageTest extends TestCase
{
    use ProphecyTrait;

    /** @var SecurityStage */
    private $securityStage;
    private $resourceMetadataFactoryProphecy;
    private $resourceAccessCheckerProphecy;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $this->resourceAccessCheckerProphecy = $this->prophesize(ResourceAccessCheckerInterface::class);

        $this->securityStage = new SecurityStage(
            $this->resourceMetadataFactoryProphecy->reveal(),
            $this->resourceAccessCheckerProphecy->reveal()
        );
    }

    public function testNoSecurity(): void
    {
        $resourceClass = 'myResource';
        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn(new ResourceMetadata());

        $this->resourceAccessCheckerProphecy->isGranted(Argument::cetera())->shouldNotBeCalled();

        ($this->securityStage)($resourceClass, 'item_query', []);
    }

    public function testGranted(): void
    {
        $operationName = 'item_query';
        $resourceClass = 'myResource';
        $isGranted = 'not_granted';
        $extraVariables = ['extra' => false];
        $resourceMetadata = (new ResourceMetadata())->withGraphql([
            $operationName => ['security' => $isGranted],
        ]);
        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn($resourceMetadata);

        $this->resourceAccessCheckerProphecy->isGranted($resourceClass, $isGranted, $extraVariables)->shouldBeCalled()->willReturn(true);

        ($this->securityStage)($resourceClass, 'item_query', ['extra_variables' => $extraVariables]);
    }

    public function testNotGranted(): void
    {
        $operationName = 'item_query';
        $resourceClass = 'myResource';
        $isGranted = 'not_granted';
        $extraVariables = ['extra' => false];
        $resourceMetadata = (new ResourceMetadata())->withGraphql([
            $operationName => ['security' => $isGranted],
        ]);
        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn($resourceMetadata);

        $this->resourceAccessCheckerProphecy->isGranted($resourceClass, $isGranted, $extraVariables)->shouldBeCalled()->willReturn(false);

        $info = $this->prophesize(ResolveInfo::class)->reveal();

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Access Denied.');

        ($this->securityStage)($resourceClass, 'item_query', [
            'info' => $info,
            'extra_variables' => $extraVariables,
        ]);
    }

    public function testNoSecurityBundleInstalled(): void
    {
        $this->securityStage = new SecurityStage($this->resourceMetadataFactoryProphecy->reveal(), null);

        $operationName = 'item_query';
        $resourceClass = 'myResource';
        $isGranted = 'not_granted';
        $resourceMetadata = (new ResourceMetadata())->withGraphql([
            $operationName => ['security' => $isGranted],
        ]);
        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn($resourceMetadata);

        $this->expectException(\LogicException::class);

        ($this->securityStage)($resourceClass, 'item_query', []);
    }

    public function testNoSecurityBundleInstalledNoExpression(): void
    {
        $this->securityStage = new SecurityStage($this->resourceMetadataFactoryProphecy->reveal(), null);

        $resourceClass = 'myResource';
        $resourceMetadata = new ResourceMetadata();
        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn($resourceMetadata);

        $this->resourceAccessCheckerProphecy->isGranted(Argument::any())->shouldNotBeCalled();

        ($this->securityStage)($resourceClass, 'item_query', []);
    }
}
