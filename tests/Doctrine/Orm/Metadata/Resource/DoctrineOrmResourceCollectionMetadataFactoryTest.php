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

namespace ApiPlatform\Tests\Doctrine\Orm\Metadata\Resource;

use ApiPlatform\Doctrine\Orm\Metadata\Resource\DoctrineOrmResourceCollectionMetadataFactory;
use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class DoctrineOrmResourceCollectionMetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    private function getResourceMetadataCollectionFactory(Operation $operation)
    {
        $resourceMetadataCollectionFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactory->create($operation->getClass())->willReturn(new ResourceMetadataCollection($operation->getClass(), [
            (new ApiResource())
                ->withOperations(
                    new Operations([$operation->getName() => $operation])
                )->withGraphQlOperations([
                    'graphql_'.$operation->getName() => $operation->withName('graphql_'.$operation->getName()),
                ]),
        ]));

        return $resourceMetadataCollectionFactory->reveal();
    }

    public function testWithoutManager(): void
    {
        $operation = (new Get())->withClass(Dummy::class)->withName('get');
        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(Dummy::class)->willReturn(null);

        $resourceMetadataCollectionFactory = new DoctrineOrmResourceCollectionMetadataFactory($managerRegistry->reveal(), $this->getResourceMetadataCollectionFactory($operation));
        $resourceMetadataCollection = $resourceMetadataCollectionFactory->create(Dummy::class);

        $this->assertNull($resourceMetadataCollection->getOperation('get')->getProvider());
        $this->assertNull($resourceMetadataCollection->getOperation('graphql_get')->getProvider());
    }

    /**
     * @dataProvider operationProvider
     */
    public function testWithProvider(Operation $operation, ?string $expectedProvider = null, ?string $expectedProcessor = null): void
    {
        $objectManager = $this->prophesize(EntityManagerInterface::class);
        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass($operation->getClass())->willReturn($objectManager->reveal());
        $resourceMetadataCollectionFactory = new DoctrineOrmResourceCollectionMetadataFactory($managerRegistry->reveal(), $this->getResourceMetadataCollectionFactory($operation));
        $resourceMetadataCollection = $resourceMetadataCollectionFactory->create($operation->getClass());
        $this->assertSame($expectedProvider, $resourceMetadataCollection->getOperation($operation->getName())->getProvider());
        $this->assertSame($expectedProvider, $resourceMetadataCollection->getOperation('graphql_'.$operation->getName())->getProvider());
        $this->assertSame($expectedProcessor, $resourceMetadataCollection->getOperation($operation->getName())->getProcessor());
        $this->assertSame($expectedProcessor, $resourceMetadataCollection->getOperation('graphql_'.$operation->getName())->getProcessor());
    }

    public static function operationProvider(): iterable
    {
        $default = (new Get())->withName('get')->withClass(Dummy::class);

        yield [(new Get())->withProvider('has a provider')->withProcessor('and a processor')->withOperation($default), 'has a provider', 'and a processor'];
        yield [(new Get())->withOperation($default), ItemProvider::class, 'api_platform.doctrine.orm.state.persist_processor'];
        yield [(new GetCollection())->withOperation($default), CollectionProvider::class, 'api_platform.doctrine.orm.state.persist_processor'];
        yield [(new Delete())->withOperation($default), ItemProvider::class, 'api_platform.doctrine.orm.state.remove_processor'];
    }
}
