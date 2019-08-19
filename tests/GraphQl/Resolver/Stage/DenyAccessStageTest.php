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

use ApiPlatform\Core\GraphQl\Resolver\Stage\DenyAccessStage;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Security\ResourceAccessCheckerInterface;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class DenyAccessStageTest extends TestCase
{
    /** @var DenyAccessStage */
    private $denyAccessStage;
    private $resourceMetadataFactoryProphecy;
    private $resourceAccessCheckerProphecy;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $this->resourceAccessCheckerProphecy = $this->prophesize(ResourceAccessCheckerInterface::class);

        $this->denyAccessStage = new DenyAccessStage(
            $this->resourceMetadataFactoryProphecy->reveal(),
            $this->resourceAccessCheckerProphecy->reveal()
        );
    }

    public function testNoAccessControl(): void
    {
        $resourceClass = 'myResource';
        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn(new ResourceMetadata());

        $this->resourceAccessCheckerProphecy->isGranted(Argument::cetera())->shouldNotBeCalled();

        ($this->denyAccessStage)($resourceClass, 'query', []);
    }

    public function testGranted(): void
    {
        $operationName = 'query';
        $resourceClass = 'myResource';
        $isGranted = 'not_granted';
        $extraVariables = ['extra' => false];
        $resourceMetadata = (new ResourceMetadata())->withGraphql([
            $operationName => ['access_control' => $isGranted],
        ]);
        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn($resourceMetadata);

        $this->resourceAccessCheckerProphecy->isGranted($resourceClass, $isGranted, $extraVariables)->shouldBeCalled()->willReturn(true);

        ($this->denyAccessStage)($resourceClass, 'query', ['extra_variables' => $extraVariables]);
    }

    public function testNotGranted(): void
    {
        $operationName = 'query';
        $resourceClass = 'myResource';
        $isGranted = 'not_granted';
        $extraVariables = ['extra' => false];
        $resourceMetadata = (new ResourceMetadata())->withGraphql([
            $operationName => ['access_control' => $isGranted],
        ]);
        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn($resourceMetadata);

        $this->resourceAccessCheckerProphecy->isGranted($resourceClass, $isGranted, $extraVariables)->shouldBeCalled()->willReturn(false);

        $info = $this->prophesize(ResolveInfo::class)->reveal();

        $this->expectException(Error::class);
        $this->expectExceptionMessage('Access Denied.');

        ($this->denyAccessStage)($resourceClass, 'query', [
            'info' => $info,
            'extra_variables' => $extraVariables,
        ]);
    }
}
