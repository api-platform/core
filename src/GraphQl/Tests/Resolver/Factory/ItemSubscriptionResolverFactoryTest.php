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

namespace ApiPlatform\GraphQl\Tests\Resolver\Factory;

use ApiPlatform\GraphQl\Resolver\Factory\ItemSubscriptionResolverFactory;
use ApiPlatform\GraphQl\Resolver\Stage\ReadStageInterface;
use ApiPlatform\GraphQl\Resolver\Stage\SecurityStageInterface;
use ApiPlatform\GraphQl\Resolver\Stage\SerializeStageInterface;
use ApiPlatform\GraphQl\Subscription\MercureSubscriptionIriGeneratorInterface;
use ApiPlatform\GraphQl\Subscription\SubscriptionManagerInterface;
use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Metadata\GraphQl\Subscription;
use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class ItemSubscriptionResolverFactoryTest extends TestCase
{
    use ProphecyTrait;

    private ItemSubscriptionResolverFactory $itemSubscriptionResolverFactory;
    private ObjectProphecy $readStageProphecy;
    private ObjectProphecy $securityStageProphecy;
    private ObjectProphecy $serializeStageProphecy;
    private ObjectProphecy $subscriptionManagerProphecy;
    private ObjectProphecy $mercureSubscriptionIriGeneratorProphecy;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->readStageProphecy = $this->prophesize(ReadStageInterface::class);
        $this->securityStageProphecy = $this->prophesize(SecurityStageInterface::class);
        $this->serializeStageProphecy = $this->prophesize(SerializeStageInterface::class);
        $this->subscriptionManagerProphecy = $this->prophesize(SubscriptionManagerInterface::class);
        $this->mercureSubscriptionIriGeneratorProphecy = $this->prophesize(MercureSubscriptionIriGeneratorInterface::class);

        $this->itemSubscriptionResolverFactory = new ItemSubscriptionResolverFactory(
            $this->readStageProphecy->reveal(),
            $this->securityStageProphecy->reveal(),
            $this->serializeStageProphecy->reveal(),
            $this->subscriptionManagerProphecy->reveal(),
            $this->mercureSubscriptionIriGeneratorProphecy->reveal()
        );
    }

    public function testResolve(): void
    {
        $resourceClass = \stdClass::class;
        $rootClass = 'rootClass';
        $operationName = 'update';
        $operation = (new Subscription())->withMercure(true)->withName($operationName);
        $source = ['source'];
        $args = ['args'];
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $resolverContext = ['source' => $source, 'args' => $args, 'info' => $info, 'is_collection' => false, 'is_mutation' => false, 'is_subscription' => true];
        $readStageItem = new \stdClass();
        $this->readStageProphecy->__invoke($resourceClass, $rootClass, $operation, $resolverContext)->shouldBeCalled()->willReturn($readStageItem);

        $this->securityStageProphecy->__invoke($resourceClass, $operation, $resolverContext + [
            'extra_variables' => [
                'object' => $readStageItem,
            ],
        ])->shouldBeCalled();

        $serializeStageData = ['serialized'];
        $this->serializeStageProphecy->__invoke($readStageItem, $resourceClass, $operation, $resolverContext)->shouldBeCalled()->willReturn($serializeStageData);

        $subscriptionId = 'subscriptionId';
        $this->subscriptionManagerProphecy->retrieveSubscriptionId($resolverContext, $serializeStageData)->shouldBeCalled()->willReturn($subscriptionId);

        $mercureUrl = 'mercure-url';
        $this->mercureSubscriptionIriGeneratorProphecy->generateMercureUrl($subscriptionId, null)->shouldBeCalled()->willReturn($mercureUrl);

        $this->assertSame($serializeStageData + ['mercureUrl' => $mercureUrl], ($this->itemSubscriptionResolverFactory)($resourceClass, $rootClass, $operation)($source, $args, null, $info));
    }

    public function testResolveNullResourceClass(): void
    {
        $resourceClass = null;
        $rootClass = 'rootClass';
        $operationName = 'update';
        $operation = (new Subscription())->withName($operationName);
        $source = ['source'];
        $args = ['args'];
        $info = $this->prophesize(ResolveInfo::class)->reveal();

        $this->assertNull(($this->itemSubscriptionResolverFactory)($resourceClass, $rootClass, $operation)($source, $args, null, $info));
    }

    public function testResolveNullOperationName(): void
    {
        $resourceClass = \stdClass::class;
        $rootClass = 'rootClass';
        $source = ['source'];
        $args = ['args'];
        $info = $this->prophesize(ResolveInfo::class)->reveal();

        $this->assertNull(($this->itemSubscriptionResolverFactory)($resourceClass, $rootClass, null)($source, $args, null, $info));
    }

    public function testResolveBadReadStageItem(): void
    {
        $resourceClass = \stdClass::class;
        $rootClass = 'rootClass';
        $operationName = 'update';
        $operation = (new Subscription())->withName($operationName);
        $source = ['source'];
        $args = ['args'];
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $resolverContext = ['source' => $source, 'args' => $args, 'info' => $info, 'is_collection' => false, 'is_mutation' => false, 'is_subscription' => true];

        $readStageItem = [];
        $this->readStageProphecy->__invoke($resourceClass, $rootClass, $operation, $resolverContext)->shouldBeCalled()->willReturn($readStageItem);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Item from read stage should be a nullable object.');

        ($this->itemSubscriptionResolverFactory)($resourceClass, $rootClass, $operation)($source, $args, null, $info);
    }

    public function testResolveNoSubscriptionId(): void
    {
        $resourceClass = \stdClass::class;
        $rootClass = 'rootClass';
        $operationName = 'update';
        $operation = (new Subscription())->withName($operationName)->withMercure(true);
        $source = ['source'];
        $args = ['args'];
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $resolverContext = ['source' => $source, 'args' => $args, 'info' => $info, 'is_collection' => false, 'is_mutation' => false, 'is_subscription' => true];

        $readStageItem = new \stdClass();
        $this->readStageProphecy->__invoke($resourceClass, $rootClass, $operation, $resolverContext)->willReturn($readStageItem);

        $serializeStageData = ['serialized'];
        $this->serializeStageProphecy->__invoke($readStageItem, $resourceClass, $operation, $resolverContext)->willReturn($serializeStageData);

        $this->subscriptionManagerProphecy->retrieveSubscriptionId($resolverContext, $serializeStageData)->willReturn(null);

        $this->mercureSubscriptionIriGeneratorProphecy->generateMercureUrl(Argument::any())->shouldNotBeCalled();

        $this->assertSame($serializeStageData, ($this->itemSubscriptionResolverFactory)($resourceClass, $rootClass, $operation)($source, $args, null, $info));
    }

    public function testResolveNoMercureSubscriptionIriGenerator(): void
    {
        $resourceClass = \stdClass::class;
        $rootClass = 'rootClass';
        $operationName = 'update';
        /** @var Operation $operation */
        $operation = (new Subscription())->withName($operationName)->withMercure(true);
        $source = ['source'];
        $args = ['args'];
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $resolverContext = ['source' => $source, 'args' => $args, 'info' => $info, 'is_collection' => false, 'is_mutation' => false, 'is_subscription' => true];

        $readStageItem = new \stdClass();
        $this->readStageProphecy->__invoke($resourceClass, $rootClass, $operation, $resolverContext)->willReturn($readStageItem);

        $serializeStageData = ['serialized'];
        $this->serializeStageProphecy->__invoke($readStageItem, $resourceClass, $operation, $resolverContext)->willReturn($serializeStageData);

        $subscriptionId = 'subscriptionId';
        $this->subscriptionManagerProphecy->retrieveSubscriptionId($resolverContext, $serializeStageData)->willReturn($subscriptionId);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot use Mercure for subscriptions when MercureBundle is not installed. Try running "composer require mercure".');

        $itemSubscriptionResolverFactory = new ItemSubscriptionResolverFactory(
            $this->readStageProphecy->reveal(),
            $this->securityStageProphecy->reveal(),
            $this->serializeStageProphecy->reveal(),
            $this->subscriptionManagerProphecy->reveal(),
            null
        );

        ($itemSubscriptionResolverFactory)($resourceClass, $rootClass, $operation)($source, $args, null, $info);
    }
}
