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

namespace ApiPlatform\Doctrine\Orm\Tests\State;

use ApiPlatform\Doctrine\Common\State\ManagedEntityTransform;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity\Dummy;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\IdentifiersExtractorInterface;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class ManagedEntityTransformTest extends TestCase
{
    use ProphecyTrait;

    public function testItResolvesTheRelatedResourceToItsManagedEntity(): void
    {
        $resource = new ManagedEntityTransformTestResource();
        $resource->id = 1;
        $entity = new Dummy();

        $objectManagerProphecy = $this->prophesize(ObjectManager::class);
        $objectManagerProphecy->find(Dummy::class, 1)->willReturn($entity)->shouldBeCalled();

        $transform = $this->transform($objectManagerProphecy->reveal(), ['id' => 1]);

        $this->assertSame($entity, $transform($resource, new \stdClass(), null));
    }

    /**
     * A to-many arrives as an iterable of resources; every item needs resolving, or Doctrine is
     * handed resource objects for an association.
     */
    public function testItResolvesEveryItemOfACollection(): void
    {
        $resource = new ManagedEntityTransformTestResource();
        $resource->id = 1;
        $entity = new Dummy();

        $objectManagerProphecy = $this->prophesize(ObjectManager::class);
        $objectManagerProphecy->find(Dummy::class, 1)->willReturn($entity);

        $transform = $this->transform($objectManagerProphecy->reveal(), ['id' => 1]);

        $this->assertSame([$entity, $entity], $transform([$resource, $resource], new \stdClass(), null));
    }

    /**
     * The identifier is not assumed to be called `id`: a resource keyed on a natural code is just
     * as valid, and hardcoding `id` would silently resolve nothing there.
     */
    public function testItReadsAnIdentifierThatIsNotCalledId(): void
    {
        $resource = new ManagedEntityTransformTestResource();
        $entity = new Dummy();

        $objectManagerProphecy = $this->prophesize(ObjectManager::class);
        $objectManagerProphecy->find(Dummy::class, 'FR')->willReturn($entity)->shouldBeCalled();

        $transform = $this->transform($objectManagerProphecy->reveal(), ['code' => 'FR']);

        $this->assertSame($entity, $transform($resource, new \stdClass(), null));
    }

    public function testItLeavesAValueThatIsNotAResourceUntouched(): void
    {
        $value = new \stdClass();

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Argument::any())->shouldNotBeCalled();

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy->create(\stdClass::class)->willThrow(new ResourceClassNotFoundException());

        $transform = new ManagedEntityTransform(
            $managerRegistryProphecy->reveal(),
            $resourceMetadataCollectionFactoryProphecy->reveal(),
            $this->prophesize(IdentifiersExtractorInterface::class)->reveal(),
        );

        $this->assertSame($value, $transform($value, new \stdClass(), null));
    }

    /**
     * A resource without a complete identifier stands for no row: hand it back rather than guess.
     */
    public function testItLeavesAResourceWithoutIdentifierUntouched(): void
    {
        $resource = new ManagedEntityTransformTestResource();

        $objectManagerProphecy = $this->prophesize(ObjectManager::class);
        $objectManagerProphecy->find(Argument::cetera())->shouldNotBeCalled();

        $transform = $this->transform($objectManagerProphecy->reveal(), ['id' => null]);

        $this->assertSame($resource, $transform($resource, new \stdClass(), null));
    }

    public function testItLeavesAScalarUntouched(): void
    {
        $transform = new ManagedEntityTransform(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $this->prophesize(ResourceMetadataCollectionFactoryInterface::class)->reveal(),
            $this->prophesize(IdentifiersExtractorInterface::class)->reveal(),
        );

        $this->assertSame('a string', $transform('a string', new \stdClass(), null));
    }

    /**
     * @param array<string, mixed> $identifiers
     */
    private function transform(ObjectManager $objectManager, array $identifiers): ManagedEntityTransform
    {
        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($objectManager);

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy->create(ManagedEntityTransformTestResource::class)->willReturn(
            new ResourceMetadataCollection(ManagedEntityTransformTestResource::class, [
                (new ApiResource())->withOperations(new Operations([
                    'get' => (new Get())->withStateOptions(new Options(entityClass: Dummy::class)),
                ])),
            ])
        );

        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractorProphecy->getIdentifiersFromItem(Argument::type(ManagedEntityTransformTestResource::class))->willReturn($identifiers);

        return new ManagedEntityTransform(
            $managerRegistryProphecy->reveal(),
            $resourceMetadataCollectionFactoryProphecy->reveal(),
            $identifiersExtractorProphecy->reveal(),
        );
    }
}

class ManagedEntityTransformTestResource
{
    public ?int $id = null;
}
