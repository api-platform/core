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

use ApiPlatform\Core\Security\ResourceAccessCheckerInterface;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\GraphQl\Resolver\Stage\SecurityPostValidationStage;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
class SecurityPostValidationStageTest extends TestCase
{
    use ProphecyTrait;

    /** @var SecurityPostValidationStage */
    private $securityPostValidationStage;
    private $resourceMetadataCollectionFactoryProphecy;
    private $resourceAccessCheckerProphecy;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $this->resourceAccessCheckerProphecy = $this->prophesize(ResourceAccessCheckerInterface::class);

        $this->securityPostValidationStage = new SecurityPostValidationStage(
            $this->resourceMetadataCollectionFactoryProphecy->reveal(),
            $this->resourceAccessCheckerProphecy->reveal()
        );
    }

    public function testNoSecurity(): void
    {
        $operationName = 'item_query';
        $resourceClass = 'myResource';
        $resourceMetadata = new ResourceMetadataCollection($resourceClass, [(new ApiResource())->withGraphQlOperations([$operationName => new Query()])]);
        $this->resourceMetadataCollectionFactoryProphecy->create($resourceClass)->willReturn($resourceMetadata);

        $this->resourceAccessCheckerProphecy->isGranted(Argument::cetera())->shouldNotBeCalled();

        ($this->securityPostValidationStage)($resourceClass, $operationName, []);
    }

    public function testGranted(): void
    {
        $operationName = 'item_query';
        $resourceClass = 'myResource';
        $isGranted = 'not_granted';
        $extraVariables = ['extra' => false];
        $resourceMetadata = new ResourceMetadataCollection($resourceClass, [(new ApiResource())->withGraphQlOperations([$operationName => (new Query())->withSecurityPostValidation($isGranted)])]);
        $this->resourceMetadataCollectionFactoryProphecy->create($resourceClass)->willReturn($resourceMetadata);

        $this->resourceAccessCheckerProphecy->isGranted($resourceClass, $isGranted, $extraVariables)->shouldBeCalled()->willReturn(true);

        ($this->securityPostValidationStage)($resourceClass, $operationName, ['extra_variables' => $extraVariables]);
    }

    public function testNotGranted(): void
    {
        $operationName = 'item_query';
        $resourceClass = 'myResource';
        $isGranted = 'not_granted';
        $extraVariables = ['extra' => false];
        $resourceMetadata = new ResourceMetadataCollection($resourceClass, [(new ApiResource())->withGraphQlOperations([$operationName => (new Query())->withSecurityPostValidation($isGranted)])]);
        $this->resourceMetadataCollectionFactoryProphecy->create($resourceClass)->willReturn($resourceMetadata);

        $this->resourceAccessCheckerProphecy->isGranted($resourceClass, $isGranted, $extraVariables)->shouldBeCalled()->willReturn(false);

        $info = $this->prophesize(ResolveInfo::class)->reveal();

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Access Denied.');

        ($this->securityPostValidationStage)($resourceClass, $operationName, [
            'info' => $info,
            'extra_variables' => $extraVariables,
        ]);
    }

    public function testNoSecurityBundleInstalled(): void
    {
        $this->securityPostValidationStage = new SecurityPostValidationStage($this->resourceMetadataCollectionFactoryProphecy->reveal(), null);

        $operationName = 'item_query';
        $resourceClass = 'myResource';
        $isGranted = 'not_granted';
        $resourceMetadata = new ResourceMetadataCollection($resourceClass, [(new ApiResource())->withGraphQlOperations([$operationName => (new Query())->withSecurityPostValidation($isGranted)])]);
        $this->resourceMetadataCollectionFactoryProphecy->create($resourceClass)->willReturn($resourceMetadata);

        $this->expectException(\LogicException::class);

        ($this->securityPostValidationStage)($resourceClass, $operationName, []);
    }

    public function testNoSecurityBundleInstalledNoExpression(): void
    {
        $this->securityPostValidationStage = new SecurityPostValidationStage($this->resourceMetadataCollectionFactoryProphecy->reveal(), null);

        $operationName = 'item_query';
        $resourceClass = 'myResource';
        $resourceMetadata = new ResourceMetadataCollection($resourceClass, [(new ApiResource())->withGraphQlOperations([$operationName => new Query()])]);
        $this->resourceMetadataCollectionFactoryProphecy->create($resourceClass)->willReturn($resourceMetadata);

        $this->resourceAccessCheckerProphecy->isGranted(Argument::any())->shouldNotBeCalled();

        ($this->securityPostValidationStage)($resourceClass, $operationName, []);
    }
}
