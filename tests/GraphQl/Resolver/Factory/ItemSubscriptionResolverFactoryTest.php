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

namespace ApiPlatform\Core\Tests\GraphQl\Resolver\Factory;

use ApiPlatform\Core\GraphQl\Resolver\Factory\ItemSubscriptionResolverFactory;
use ApiPlatform\Core\GraphQl\Resolver\Stage\ReadStageInterface;
use ApiPlatform\Core\GraphQl\Resolver\Stage\SecurityStageInterface;
use ApiPlatform\Core\GraphQl\Resolver\Stage\SerializeStageInterface;
use ApiPlatform\Core\GraphQl\Subscription\MercureSubscriptionIriGeneratorInterface;
use ApiPlatform\Core\GraphQl\Subscription\SubscriptionManagerInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\ProphecyTrait;
use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class ItemSubscriptionResolverFactoryTest extends TestCase
{
    use ProphecyTrait;

    private $itemSubscriptionResolverFactory;
    private $readStageProphecy;
    private $securityStageProphecy;
    private $serializeStageProphecy;
    private $resourceMetadataFactoryProphecy;
    private $subscriptionManagerProphecy;
    private $mercureSubscriptionIriGeneratorProphecy;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->readStageProphecy = $this->prophesize(ReadStageInterface::class);
        $this->securityStageProphecy = $this->prophesize(SecurityStageInterface::class);
        $this->serializeStageProphecy = $this->prophesize(SerializeStageInterface::class);
        $this->resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $this->subscriptionManagerProphecy = $this->prophesize(SubscriptionManagerInterface::class);
        $this->mercureSubscriptionIriGeneratorProphecy = $this->prophesize(MercureSubscriptionIriGeneratorInterface::class);

        $this->itemSubscriptionResolverFactory = new ItemSubscriptionResolverFactory(
            $this->readStageProphecy->reveal(),
            $this->securityStageProphecy->reveal(),
            $this->serializeStageProphecy->reveal(),
            $this->resourceMetadataFactoryProphecy->reveal(),
            $this->subscriptionManagerProphecy->reveal(),
            $this->mercureSubscriptionIriGeneratorProphecy->reveal()
        );
    }

    public function testResolve(): void
    {
        $resourceClass = 'stdClass';
        $rootClass = 'rootClass';
        $operationName = 'update';
        $source = ['source'];
        $args = ['args'];
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $resolverContext = ['source' => $source, 'args' => $args, 'info' => $info, 'is_collection' => false, 'is_mutation' => false, 'is_subscription' => true];

        $readStageItem = new \stdClass();
        $this->readStageProphecy->__invoke($resourceClass, $rootClass, $operationName, $resolverContext)->shouldBeCalled()->willReturn($readStageItem);

        $this->securityStageProphecy->__invoke($resourceClass, $operationName, $resolverContext + [
            'extra_variables' => [
                'object' => $readStageItem,
            ],
        ])->shouldBeCalled();

        $serializeStageData = ['serialized'];
        $this->serializeStageProphecy->__invoke($readStageItem, $resourceClass, $operationName, $resolverContext)->shouldBeCalled()->willReturn($serializeStageData);

        $subscriptionId = 'subscriptionId';
        $this->subscriptionManagerProphecy->retrieveSubscriptionId($resolverContext, $serializeStageData)->shouldBeCalled()->willReturn($subscriptionId);

        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn((new ResourceMetadata())->withAttributes(['mercure' => true]));

        $mercureUrl = 'mercure-url';
        $this->mercureSubscriptionIriGeneratorProphecy->generateMercureUrl($subscriptionId)->shouldBeCalled()->willReturn($mercureUrl);

        $this->assertSame($serializeStageData + ['mercureUrl' => $mercureUrl], ($this->itemSubscriptionResolverFactory)($resourceClass, $rootClass, $operationName)($source, $args, null, $info));
    }

    public function testResolveNullResourceClass(): void
    {
        $resourceClass = null;
        $rootClass = 'rootClass';
        $operationName = 'update';
        $source = ['source'];
        $args = ['args'];
        $info = $this->prophesize(ResolveInfo::class)->reveal();

        $this->assertNull(($this->itemSubscriptionResolverFactory)($resourceClass, $rootClass, $operationName)($source, $args, null, $info));
    }

    public function testResolveNullOperationName(): void
    {
        $resourceClass = 'stdClass';
        $rootClass = 'rootClass';
        $operationName = null;
        $source = ['source'];
        $args = ['args'];
        $info = $this->prophesize(ResolveInfo::class)->reveal();

        $this->assertNull(($this->itemSubscriptionResolverFactory)($resourceClass, $rootClass, $operationName)($source, $args, null, $info));
    }

    public function testResolveBadReadStageItem(): void
    {
        $resourceClass = 'stdClass';
        $rootClass = 'rootClass';
        $operationName = 'update';
        $source = ['source'];
        $args = ['args'];
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $resolverContext = ['source' => $source, 'args' => $args, 'info' => $info, 'is_collection' => false, 'is_mutation' => false, 'is_subscription' => true];

        $readStageItem = [];
        $this->readStageProphecy->__invoke($resourceClass, $rootClass, $operationName, $resolverContext)->shouldBeCalled()->willReturn($readStageItem);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Item from read stage should be a nullable object.');

        ($this->itemSubscriptionResolverFactory)($resourceClass, $rootClass, $operationName)($source, $args, null, $info);
    }

    public function testResolveNoSubscriptionId(): void
    {
        $resourceClass = 'stdClass';
        $rootClass = 'rootClass';
        $operationName = 'update';
        $source = ['source'];
        $args = ['args'];
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $resolverContext = ['source' => $source, 'args' => $args, 'info' => $info, 'is_collection' => false, 'is_mutation' => false, 'is_subscription' => true];

        $readStageItem = new \stdClass();
        $this->readStageProphecy->__invoke($resourceClass, $rootClass, $operationName, $resolverContext)->willReturn($readStageItem);

        $serializeStageData = ['serialized'];
        $this->serializeStageProphecy->__invoke($readStageItem, $resourceClass, $operationName, $resolverContext)->willReturn($serializeStageData);

        $this->subscriptionManagerProphecy->retrieveSubscriptionId($resolverContext, $serializeStageData)->willReturn(null);

        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn((new ResourceMetadata())->withAttributes(['mercure' => true]));

        $this->mercureSubscriptionIriGeneratorProphecy->generateMercureUrl(Argument::any())->shouldNotBeCalled();

        $this->assertSame($serializeStageData, ($this->itemSubscriptionResolverFactory)($resourceClass, $rootClass, $operationName)($source, $args, null, $info));
    }

    public function testResolveNoMercureSubscriptionIriGenerator(): void
    {
        $resourceClass = 'stdClass';
        $rootClass = 'rootClass';
        $operationName = 'update';
        $source = ['source'];
        $args = ['args'];
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $resolverContext = ['source' => $source, 'args' => $args, 'info' => $info, 'is_collection' => false, 'is_mutation' => false, 'is_subscription' => true];

        $readStageItem = new \stdClass();
        $this->readStageProphecy->__invoke($resourceClass, $rootClass, $operationName, $resolverContext)->willReturn($readStageItem);

        $serializeStageData = ['serialized'];
        $this->serializeStageProphecy->__invoke($readStageItem, $resourceClass, $operationName, $resolverContext)->willReturn($serializeStageData);

        $subscriptionId = 'subscriptionId';
        $this->subscriptionManagerProphecy->retrieveSubscriptionId($resolverContext, $serializeStageData)->willReturn($subscriptionId);

        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn((new ResourceMetadata())->withAttributes(['mercure' => true]));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot use Mercure for subscriptions when MercureBundle is not installed. Try running "composer require mercure".');

        $itemSubscriptionResolverFactory = new ItemSubscriptionResolverFactory(
            $this->readStageProphecy->reveal(),
            $this->securityStageProphecy->reveal(),
            $this->serializeStageProphecy->reveal(),
            $this->resourceMetadataFactoryProphecy->reveal(),
            $this->subscriptionManagerProphecy->reveal(),
            null
        );

        ($itemSubscriptionResolverFactory)($resourceClass, $rootClass, $operationName)($source, $args, null, $info);
    }
}
