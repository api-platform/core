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

use ApiPlatform\GraphQl\Resolver\Factory\ItemMutationResolverFactory;
use ApiPlatform\GraphQl\Resolver\Stage\DeserializeStageInterface;
use ApiPlatform\GraphQl\Resolver\Stage\ReadStageInterface;
use ApiPlatform\GraphQl\Resolver\Stage\SecurityPostDenormalizeStageInterface;
use ApiPlatform\GraphQl\Resolver\Stage\SecurityPostValidationStageInterface;
use ApiPlatform\GraphQl\Resolver\Stage\SecurityStageInterface;
use ApiPlatform\GraphQl\Resolver\Stage\SerializeStageInterface;
use ApiPlatform\GraphQl\Resolver\Stage\ValidateStageInterface;
use ApiPlatform\GraphQl\Resolver\Stage\WriteStageInterface;
use ApiPlatform\GraphQl\Tests\Fixtures\ApiResource\Dummy;
use ApiPlatform\Metadata\GraphQl\Mutation;
use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class ItemMutationResolverFactoryTest extends TestCase
{
    use ProphecyTrait;

    private ItemMutationResolverFactory $itemMutationResolverFactory;
    private ObjectProphecy $readStageProphecy;
    private ObjectProphecy $securityStageProphecy;
    private ObjectProphecy $securityPostDenormalizeStageProphecy;
    private ObjectProphecy $serializeStageProphecy;
    private ObjectProphecy $deserializeStageProphecy;
    private ObjectProphecy $writeStageProphecy;
    private ObjectProphecy $validateStageProphecy;
    private ObjectProphecy $mutationResolverLocatorProphecy;
    private ObjectProphecy $securityPostValidationStageProphecy;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->readStageProphecy = $this->prophesize(ReadStageInterface::class);
        $this->securityStageProphecy = $this->prophesize(SecurityStageInterface::class);
        $this->securityPostDenormalizeStageProphecy = $this->prophesize(SecurityPostDenormalizeStageInterface::class);
        $this->serializeStageProphecy = $this->prophesize(SerializeStageInterface::class);
        $this->deserializeStageProphecy = $this->prophesize(DeserializeStageInterface::class);
        $this->writeStageProphecy = $this->prophesize(WriteStageInterface::class);
        $this->validateStageProphecy = $this->prophesize(ValidateStageInterface::class);
        $this->mutationResolverLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $this->securityPostValidationStageProphecy = $this->prophesize(SecurityPostValidationStageInterface::class);

        $this->itemMutationResolverFactory = new ItemMutationResolverFactory(
            $this->readStageProphecy->reveal(),
            $this->securityStageProphecy->reveal(),
            $this->securityPostDenormalizeStageProphecy->reveal(),
            $this->serializeStageProphecy->reveal(),
            $this->deserializeStageProphecy->reveal(),
            $this->writeStageProphecy->reveal(),
            $this->validateStageProphecy->reveal(),
            $this->mutationResolverLocatorProphecy->reveal(),
            $this->securityPostValidationStageProphecy->reveal()
        );
    }

    public function testResolve(): void
    {
        $resourceClass = \stdClass::class;
        $rootClass = 'rootClass';
        $operationName = 'create';
        $operation = (new Mutation())->withName($operationName);
        $source = ['source'];
        $args = ['args'];
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $resolverContext = ['source' => $source, 'args' => $args, 'info' => $info, 'is_collection' => false, 'is_mutation' => true, 'is_subscription' => false];

        $readStageItem = new \stdClass();
        $readStageItem->field = 'read';
        $this->readStageProphecy->__invoke($resourceClass, $rootClass, $operation, $resolverContext)->shouldBeCalled()->willReturn($readStageItem);

        $deserializeStageItem = new \stdClass();
        $deserializeStageItem->field = 'deserialize';
        $this->deserializeStageProphecy->__invoke($readStageItem, $resourceClass, $operation, $resolverContext)->shouldBeCalled()->willReturn($deserializeStageItem);

        $this->securityStageProphecy->__invoke($resourceClass, $operation, $resolverContext + [
            'extra_variables' => [
                'object' => $readStageItem,
            ],
        ])->shouldBeCalled();
        $this->securityPostDenormalizeStageProphecy->__invoke($resourceClass, $operation, $resolverContext + [
            'extra_variables' => [
                'object' => $deserializeStageItem,
                'previous_object' => $readStageItem,
            ],
        ])->shouldBeCalled();

        $this->validateStageProphecy->__invoke($deserializeStageItem, $resourceClass, $operation, $resolverContext)->shouldBeCalled();

        $writeStageItem = new \stdClass();
        $writeStageItem->field = 'write';
        $this->writeStageProphecy->__invoke($deserializeStageItem, $resourceClass, $operation, $resolverContext)->shouldBeCalled()->willReturn($writeStageItem);

        $serializeStageData = ['serialized'];
        $this->serializeStageProphecy->__invoke($writeStageItem, $resourceClass, $operation, $resolverContext)->shouldBeCalled()->willReturn($serializeStageData);

        $this->assertSame($serializeStageData, ($this->itemMutationResolverFactory)($resourceClass, $rootClass, $operation)($source, $args, null, $info));
    }

    public function testResolveNullResourceClass(): void
    {
        $resourceClass = null;
        $rootClass = 'rootClass';
        $operation = (new Mutation())->withName('create');
        $source = ['source'];
        $args = ['args'];
        $info = $this->prophesize(ResolveInfo::class)->reveal();

        $this->assertNull(($this->itemMutationResolverFactory)($resourceClass, $rootClass, $operation)($source, $args, null, $info));
    }

    public function testResolveNullOperation(): void
    {
        $resourceClass = \stdClass::class;
        $rootClass = 'rootClass';
        $source = ['source'];
        $args = ['args'];
        $info = $this->prophesize(ResolveInfo::class)->reveal();

        $this->assertNull(($this->itemMutationResolverFactory)($resourceClass, $rootClass, null)($source, $args, null, $info));
    }

    public function testResolveBadReadStageItem(): void
    {
        $resourceClass = \stdClass::class;
        $rootClass = 'rootClass';
        $operationName = 'create';
        $operation = (new Mutation())->withName($operationName);
        $source = ['source'];
        $args = ['args'];
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $resolverContext = ['source' => $source, 'args' => $args, 'info' => $info, 'is_collection' => false, 'is_mutation' => true, 'is_subscription' => false];

        $readStageItem = [];
        $this->readStageProphecy->__invoke($resourceClass, $rootClass, $operation, $resolverContext)->shouldBeCalled()->willReturn($readStageItem);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Item from read stage should be a nullable object.');

        ($this->itemMutationResolverFactory)($resourceClass, $rootClass, $operation)($source, $args, null, $info);
    }

    public function testResolveNullDeserializeStageItem(): void
    {
        $resourceClass = \stdClass::class;
        $rootClass = 'rootClass';
        $operationName = 'create';
        $operation = (new Mutation())->withName($operationName);
        $source = ['source'];
        $args = ['args'];
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $resolverContext = ['source' => $source, 'args' => $args, 'info' => $info, 'is_collection' => false, 'is_mutation' => true, 'is_subscription' => false];

        $readStageItem = new \stdClass();
        $readStageItem->field = 'read';
        $this->readStageProphecy->__invoke($resourceClass, $rootClass, $operation, $resolverContext)->shouldBeCalled()->willReturn($readStageItem);

        $deserializeStageItem = null;
        $this->deserializeStageProphecy->__invoke($readStageItem, $resourceClass, $operation, $resolverContext)->shouldBeCalled()->willReturn($deserializeStageItem);

        $this->securityStageProphecy->__invoke($resourceClass, $operation, $resolverContext + [
            'extra_variables' => [
                'object' => $readStageItem,
            ],
        ])->shouldBeCalled();
        $this->securityPostDenormalizeStageProphecy->__invoke($resourceClass, $operation, $resolverContext + [
            'extra_variables' => [
                'object' => $deserializeStageItem,
                'previous_object' => $readStageItem,
            ],
        ])->shouldBeCalled();

        $this->validateStageProphecy->__invoke(Argument::cetera())->shouldNotBeCalled();

        $this->writeStageProphecy->__invoke(Argument::cetera())->shouldNotBeCalled();

        $serializeStageData = null;
        $this->serializeStageProphecy->__invoke($deserializeStageItem, $resourceClass, $operation, $resolverContext)->shouldBeCalled()->willReturn($serializeStageData);

        $this->assertNull(($this->itemMutationResolverFactory)($resourceClass, $rootClass, $operation)($source, $args, null, $info));
    }

    public function testResolveDelete(): void
    {
        $resourceClass = \stdClass::class;
        $rootClass = 'rootClass';
        $operationName = 'delete';
        $operation = (new Mutation())->withName($operationName);
        $source = ['source'];
        $args = ['args'];
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $resolverContext = ['source' => $source, 'args' => $args, 'info' => $info, 'is_collection' => false, 'is_mutation' => true, 'is_subscription' => false];

        $readStageItem = new \stdClass();
        $readStageItem->field = 'read';
        $this->readStageProphecy->__invoke($resourceClass, $rootClass, $operation, $resolverContext)->shouldBeCalled()->willReturn($readStageItem);

        $this->deserializeStageProphecy->__invoke(Argument::cetera())->shouldNotBeCalled();

        $this->securityStageProphecy->__invoke($resourceClass, $operation, $resolverContext + [
            'extra_variables' => [
                'object' => $readStageItem,
            ],
        ])->shouldBeCalled();
        $this->securityPostDenormalizeStageProphecy->__invoke($resourceClass, $operation, $resolverContext + [
            'extra_variables' => [
                'object' => $readStageItem,
                'previous_object' => $readStageItem,
            ],
        ])->shouldBeCalled();

        $this->validateStageProphecy->__invoke(Argument::cetera())->shouldNotBeCalled();

        $writeStageItem = new \stdClass();
        $writeStageItem->field = 'write';
        $this->writeStageProphecy->__invoke($readStageItem, $resourceClass, $operation, $resolverContext)->shouldBeCalled()->willReturn($writeStageItem);

        $serializeStageData = ['serialized'];
        $this->serializeStageProphecy->__invoke($writeStageItem, $resourceClass, $operation, $resolverContext)->shouldBeCalled()->willReturn($serializeStageData);

        $this->assertSame($serializeStageData, ($this->itemMutationResolverFactory)($resourceClass, $rootClass, $operation)($source, $args, null, $info));
    }

    public function testResolveCustom(): void
    {
        $resourceClass = \stdClass::class;
        $rootClass = 'rootClass';
        $operationName = 'create';
        $operation = (new Mutation())->withResolver('query_resolver_id')->withName($operationName);
        $source = ['source'];
        $args = ['args'];
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $resolverContext = ['source' => $source, 'args' => $args, 'info' => $info, 'is_collection' => false, 'is_mutation' => true, 'is_subscription' => false];

        $readStageItem = new \stdClass();
        $readStageItem->field = 'read';
        $this->readStageProphecy->__invoke($resourceClass, $rootClass, $operation, $resolverContext)->shouldBeCalled()->willReturn($readStageItem);

        $deserializeStageItem = new \stdClass();
        $deserializeStageItem->field = 'deserialize';
        $this->deserializeStageProphecy->__invoke($readStageItem, $resourceClass, $operation, $resolverContext)->shouldBeCalled()->willReturn($deserializeStageItem);

        $customItem = new \stdClass();
        $customItem->field = 'foo';
        $this->mutationResolverLocatorProphecy->get('query_resolver_id')->shouldBeCalled()->willReturn(fn (): \stdClass => $customItem);

        $this->securityStageProphecy->__invoke($resourceClass, $operation, $resolverContext + [
            'extra_variables' => [
                'object' => $readStageItem,
            ],
        ])->shouldBeCalled();
        $this->securityPostDenormalizeStageProphecy->__invoke($resourceClass, $operation, $resolverContext + [
            'extra_variables' => [
                'object' => $customItem,
                'previous_object' => $readStageItem,
            ],
        ])->shouldBeCalled();

        $this->validateStageProphecy->__invoke($customItem, $resourceClass, $operation, $resolverContext)->shouldBeCalled();

        $writeStageItem = new \stdClass();
        $writeStageItem->field = 'write';
        $this->writeStageProphecy->__invoke($customItem, $resourceClass, $operation, $resolverContext)->shouldBeCalled()->willReturn($writeStageItem);

        $serializeStageData = ['serialized'];
        $this->serializeStageProphecy->__invoke($writeStageItem, $resourceClass, $operation, $resolverContext)->shouldBeCalled()->willReturn($serializeStageData);

        $this->assertSame($serializeStageData, ($this->itemMutationResolverFactory)($resourceClass, $rootClass, $operation)($source, $args, null, $info));
    }

    public function testResolveCustomBadItem(): void
    {
        $resourceClass = \stdClass::class;
        $rootClass = 'rootClass';
        $operationName = 'create';
        $operation = (new Mutation())->withResolver('query_resolver_id')->withName($operationName)->withShortName('shortName');
        $source = ['source'];
        $args = ['args'];
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $resolverContext = ['source' => $source, 'args' => $args, 'info' => $info, 'is_collection' => false, 'is_mutation' => true, 'is_subscription' => false];

        $readStageItem = new \stdClass();
        $readStageItem->field = 'read';
        $this->readStageProphecy->__invoke($resourceClass, $rootClass, $operation, $resolverContext)->shouldBeCalled()->willReturn($readStageItem);

        $deserializeStageItem = new \stdClass();
        $deserializeStageItem->field = 'deserialize';
        $this->deserializeStageProphecy->__invoke($readStageItem, $resourceClass, $operation, $resolverContext)->shouldBeCalled()->willReturn($deserializeStageItem);

        $customItem = new Dummy();
        $this->mutationResolverLocatorProphecy->get('query_resolver_id')->shouldBeCalled()->willReturn(fn (): Dummy => $customItem);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Custom mutation resolver "query_resolver_id" has to return an item of class shortName but returned an item of class Dummy.');

        ($this->itemMutationResolverFactory)($resourceClass, $rootClass, $operation)($source, $args, null, $info);
    }
}
