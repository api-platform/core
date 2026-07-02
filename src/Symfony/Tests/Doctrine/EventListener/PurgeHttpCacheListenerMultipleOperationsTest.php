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

namespace ApiPlatform\Symfony\Tests\Doctrine\EventListener;

use ApiPlatform\HttpCache\PurgerInterface;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Symfony\Doctrine\EventListener\PurgeHttpCacheListener;
use ApiPlatform\Symfony\Tests\Fixtures\TestBundle\Entity\Dummy;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\TestCase;

/**
 * Non-regression tests for the multi-operation purge fix.
 *
 * Uses native PHPUnit mocks (no Prophecy) to keep expectations close to the
 * actual call shape.
 */
final class PurgeHttpCacheListenerMultipleOperationsTest extends TestCase
{
    /**
     * (b) When several #[GetCollection] are declared on a resource, every
     * collection URI must be added to the purge tags.
     */
    public function testPurgesAllCollectionUrisOnInsert(): void
    {
        $entity = new Dummy();

        $getCollection1 = new GetCollection(uriTemplate: '/dummies');
        $getCollection2 = new GetCollection(uriTemplate: '/dummies/featured');

        $purger = $this->createMock(PurgerInterface::class);
        $purger->expects(self::once())->method('purge')
            ->with(self::callback(function (array $iris) {
                self::assertContains('/dummies', $iris);
                self::assertContains('/dummies/featured', $iris);

                return true;
            }));

        $iriConverter = $this->createStub(IriConverterInterface::class);
        $iriConverter->method('getIriFromResource')
            ->willReturnCallback(static function (object|string $resource, int $referenceType, ?Operation $operation): string {
                self::assertSame(UrlGeneratorInterface::ABS_PATH, $referenceType);
                self::assertInstanceOf(GetCollection::class, $operation);

                return $operation->getUriTemplate();
            });

        $listener = new PurgeHttpCacheListener(
            $purger,
            $iriConverter,
            $this->mockResourceClassResolver(),
            null,
            null,
            null,
            $this->mockMetadataFactory([$getCollection1, $getCollection2]),
        );

        $listener->onFlush($this->onFlushArgs([$entity], [], []));
        $listener->postFlush();
    }

    /**
     * (b) When several #[Get] are declared on a resource, every item URI must
     * be added to the purge tags on update.
     */
    public function testPurgesAllItemUrisOnUpdate(): void
    {
        $entity = new Dummy();
        $entity->setId(7);

        $get1 = new Get(uriTemplate: '/dummies/{id}');
        $get2 = new Get(uriTemplate: '/dummies/{id}/details');

        $purger = $this->createMock(PurgerInterface::class);
        $purger->expects(self::once())->method('purge')
            ->with(self::callback(function (array $iris) {
                self::assertContains('/dummies/7', $iris);
                self::assertContains('/dummies/7/details', $iris);

                return true;
            }));

        $iriConverter = $this->createStub(IriConverterInterface::class);
        $iriConverter->method('getIriFromResource')
            ->willReturnCallback(static function (object|string $resource, int $referenceType, ?Operation $operation): string {
                self::assertSame(UrlGeneratorInterface::ABS_PATH, $referenceType);
                self::assertNotNull($operation);

                return str_replace('{id}', '7', (string) $operation->getUriTemplate());
            });

        $listener = new PurgeHttpCacheListener(
            $purger,
            $iriConverter,
            $this->mockResourceClassResolver(),
            null,
            null,
            null,
            $this->mockMetadataFactory([new GetCollection(uriTemplate: '/dummies'), $get1, $get2]),
        );

        $listener->onFlush($this->onFlushArgs([], [$entity], []));
        $listener->postFlush();
    }

    /**
     * (a) BC regression: when the optional ResourceMetadataCollectionFactory
     * is not injected (legacy wiring or manual instantiation), the listener
     * must still emit item tags alongside the collection tag — the previous
     * iteration of the fix lost item tags in the null-factory fallback.
     */
    public function testBcFallbackStillEmitsItemTagsOnUpdate(): void
    {
        $entity = new Dummy();
        $entity->setId(11);

        $purger = $this->createMock(PurgerInterface::class);
        $purger->expects(self::once())->method('purge')
            ->with(self::callback(function (array $iris) {
                self::assertContains('/dummies', $iris, 'collection IRI must be present in BC fallback');
                self::assertContains('/dummies/11', $iris, 'item IRI must still be emitted when no metadata factory is wired');

                return true;
            }));

        $iriConverter = $this->createStub(IriConverterInterface::class);
        $iriConverter->method('getIriFromResource')
            ->willReturnCallback(static function (object|string $resource, int $referenceType = UrlGeneratorInterface::ABS_PATH, ?Operation $operation = null): string {
                if ($operation instanceof GetCollection) {
                    return '/dummies';
                }

                return '/dummies/11';
            });

        $listener = new PurgeHttpCacheListener(
            $purger,
            $iriConverter,
            $this->mockResourceClassResolver(),
            null,
            null,
            null,
            null, // no metadata factory → BC path
        );

        $listener->onFlush($this->onFlushArgs([], [$entity], []));
        $listener->postFlush();
    }

    private function mockResourceClassResolver(): ResourceClassResolverInterface
    {
        $resolver = $this->createStub(ResourceClassResolverInterface::class);
        $resolver->method('isResourceClass')->willReturn(true);
        $resolver->method('getResourceClass')->willReturn(Dummy::class);

        return $resolver;
    }

    /**
     * @param Operation[] $operations
     */
    private function mockMetadataFactory(array $operations): ResourceMetadataCollectionFactoryInterface
    {
        $factory = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $factory->method('create')->willReturn(
            new ResourceMetadataCollection(Dummy::class, [new ApiResource(operations: $operations)])
        );

        return $factory;
    }

    /**
     * @param array<int, object> $insertions
     * @param array<int, object> $updates
     * @param array<int, object> $deletions
     */
    private function onFlushArgs(array $insertions, array $updates, array $deletions): OnFlushEventArgs
    {
        $uow = $this->createStub(UnitOfWork::class);
        $uow->method('getScheduledEntityInsertions')->willReturn($insertions);
        $uow->method('getScheduledEntityUpdates')->willReturn($updates);
        $uow->method('getScheduledEntityDeletions')->willReturn($deletions);

        $classMetadata = new ClassMetadata(Dummy::class);
        // @phpstan-ignore-next-line
        $classMetadata->associationMappings = [];

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getUnitOfWork')->willReturn($uow);
        $em->method('getClassMetadata')->willReturn($classMetadata);

        return new OnFlushEventArgs($em);
    }
}
