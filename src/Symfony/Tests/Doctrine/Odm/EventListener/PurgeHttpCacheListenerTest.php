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

namespace ApiPlatform\Symfony\Tests\Doctrine\Odm\EventListener;

use ApiPlatform\HttpCache\PurgerInterface;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Symfony\Doctrine\Odm\EventListener\PurgeHttpCacheListener;
use ApiPlatform\Symfony\Tests\Fixtures\TestBundle\Document\Dummy;
use ApiPlatform\Symfony\Tests\Fixtures\TestBundle\Document\RelatedDummy;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Event\OnFlushEventArgs;
use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class PurgeHttpCacheListenerTest extends TestCase
{
    private DocumentManager $documentManager;

    protected function setUp(): void
    {
        if (!class_exists(DocumentManager::class)) {
            $this->markTestSkipped('doctrine/mongodb-odm is not installed.');
        }

        $config = new Configuration();
        $config->setProxyDir(sys_get_temp_dir());
        $config->setProxyNamespace('DoctrineProxies');
        $config->setHydratorDir(sys_get_temp_dir());
        $config->setHydratorNamespace('DoctrineHydrators');
        $config->setAutoGenerateProxyClasses(Configuration::AUTOGENERATE_EVAL);
        $config->setMetadataCache(new ArrayAdapter());
        $config->setMetadataDriverImpl(new AttributeDriver([\dirname((new \ReflectionClass(Dummy::class))->getFileName())]));

        $this->documentManager = DocumentManager::create(null, $config);
    }

    public function testOnFlushPurgesInsertedUpdatedAndDeletedDocuments(): void
    {
        $uow = $this->documentManager->getUnitOfWork();

        $inserted = new Dummy(1);
        $uow->scheduleForInsert($this->documentManager->getClassMetadata(Dummy::class), $inserted);

        $updated = new Dummy(2);
        $updated->relatedDummy = new RelatedDummy(10);
        $uow->registerManaged($updated, 2, []);
        $uow->scheduleForUpdate($updated);

        $deleted = new Dummy(3);
        $deleted->relatedDummies->add(new RelatedDummy(11));
        $uow->registerManaged($deleted, 3, []);
        $uow->scheduleForDelete($deleted);

        $purger = $this->createMock(PurgerInterface::class);
        $purger->expects($this->once())->method('purge')->with([
            '/dummies',
            '/dummies/2',
            '/related_dummies/10',
            '/dummies/3',
            '/related_dummies/11',
        ]);

        $listener = $this->createListener($purger);
        $listener->onFlush(new OnFlushEventArgs($this->documentManager));
        $listener->postFlush();
    }

    public function testChangedReferencePurgesPreviousAndCurrentRelatedDocuments(): void
    {
        $uow = $this->documentManager->getUnitOfWork();

        $previous = new RelatedDummy(10);
        $current = new RelatedDummy(11);
        $updated = new Dummy(2);
        $updated->relatedDummy = $current;
        $uow->registerManaged($updated, 2, []);
        $uow->scheduleForUpdate($updated);
        $uow->setDocumentChangeSet($updated, ['relatedDummy' => [$previous, $current]]);

        $purger = $this->createMock(PurgerInterface::class);
        $purger->expects($this->once())->method('purge')->with(['/dummies', '/dummies/2', '/related_dummies/10', '/related_dummies/11']);

        $listener = $this->createListener($purger);
        $listener->onFlush(new OnFlushEventArgs($this->documentManager));
        $listener->postFlush();
    }

    public function testChangedScalarFieldDoesNotPurgeRelatedDocuments(): void
    {
        $uow = $this->documentManager->getUnitOfWork();

        $updated = new Dummy(2);
        $uow->registerManaged($updated, 2, []);
        $uow->scheduleForUpdate($updated);
        $uow->setDocumentChangeSet($updated, ['name' => ['before', 'after']]);

        $purger = $this->createMock(PurgerInterface::class);
        $purger->expects($this->once())->method('purge')->with(['/dummies', '/dummies/2']);

        $listener = $this->createListener($purger);
        $listener->onFlush(new OnFlushEventArgs($this->documentManager));
        $listener->postFlush();
    }

    public function testRemovedCollectionMemberIsPurged(): void
    {
        $uow = $this->documentManager->getUnitOfWork();

        $owner = new Dummy(2);
        $uow->registerManaged($owner, 2, []);

        $collection = $this->createStub(PersistentCollectionInterface::class);
        $collection->method('getOwner')->willReturn($owner);
        $collection->method('getDeleteDiff')->willReturn([new RelatedDummy(12)]);
        $uow->scheduleCollectionDeletion($collection);

        $purger = $this->createMock(PurgerInterface::class);
        $purger->expects($this->once())->method('purge')->with(['/dummies', '/dummies/2', '/related_dummies/12']);

        $listener = $this->createListener($purger);
        $listener->onFlush(new OnFlushEventArgs($this->documentManager));
        $listener->postFlush();
    }

    public function testUpsertedDocumentPurgesItsItemAndCollection(): void
    {
        $uow = $this->documentManager->getUnitOfWork();

        $upserted = new Dummy(4);
        $upserted->relatedDummy = new RelatedDummy(10);
        $uow->scheduleForUpsert($this->documentManager->getClassMetadata(Dummy::class), $upserted);

        $purger = $this->createMock(PurgerInterface::class);
        $purger->expects($this->once())->method('purge')->with(['/related_dummies/10', '/dummies', '/dummies/4']);

        $listener = $this->createListener($purger);
        $listener->onFlush(new OnFlushEventArgs($this->documentManager));
        $listener->postFlush();
    }

    public function testInsertedDocumentPurgesCollectionOnly(): void
    {
        $uow = $this->documentManager->getUnitOfWork();
        $uow->scheduleForInsert($this->documentManager->getClassMetadata(Dummy::class), new Dummy(1));

        $purger = $this->createMock(PurgerInterface::class);
        $purger->expects($this->once())->method('purge')->with(['/dummies']);

        $listener = $this->createListener($purger);
        $listener->onFlush(new OnFlushEventArgs($this->documentManager));
        $listener->postFlush();
    }

    public function testReferencesToNonResourcesAreIgnored(): void
    {
        $uow = $this->documentManager->getUnitOfWork();

        $updated = new Dummy(2);
        $updated->relatedDummy = new RelatedDummy(10);
        $uow->registerManaged($updated, 2, []);
        $uow->scheduleForUpdate($updated);

        $purger = $this->createMock(PurgerInterface::class);
        $purger->expects($this->once())->method('purge')->with(['/dummies', '/dummies/2']);

        $listener = $this->createListener($purger, [Dummy::class]);
        $listener->onFlush(new OnFlushEventArgs($this->documentManager));
        $listener->postFlush();
    }

    public function testNothingToPurge(): void
    {
        $purger = $this->createMock(PurgerInterface::class);
        $purger->expects($this->never())->method('purge');

        $listener = $this->createListener($purger);
        $listener->onFlush(new OnFlushEventArgs($this->documentManager));
        $listener->postFlush();
    }

    public function testTagsAreClearedAfterPurge(): void
    {
        $uow = $this->documentManager->getUnitOfWork();

        $updated = new Dummy(2);
        $uow->registerManaged($updated, 2, []);
        $uow->scheduleForUpdate($updated);

        $purger = $this->createMock(PurgerInterface::class);
        $purger->expects($this->once())->method('purge');

        $listener = $this->createListener($purger);
        $listener->onFlush(new OnFlushEventArgs($this->documentManager));
        $listener->postFlush();
        $listener->postFlush();
    }

    /**
     * @param list<class-string> $resourceClasses
     */
    private function createListener(PurgerInterface $purger, array $resourceClasses = [Dummy::class, RelatedDummy::class]): PurgeHttpCacheListener
    {
        $iriConverter = $this->createStub(IriConverterInterface::class);
        $iriConverter->method('getIriFromResource')->willReturnCallback(
            static function (object $resource, int $referenceType = UrlGeneratorInterface::ABS_PATH, ?Operation $operation = null): string {
                $base = $resource instanceof Dummy ? '/dummies' : '/related_dummies';

                return $operation instanceof CollectionOperationInterface ? $base : \sprintf('%s/%d', $base, $resource->id);
            }
        );

        $resourceClassResolver = $this->createStub(ResourceClassResolverInterface::class);
        $resourceClassResolver->method('isResourceClass')->willReturnCallback(static fn (string $class): bool => \in_array($class, $resourceClasses, true));

        return new PurgeHttpCacheListener($purger, $iriConverter, $resourceClassResolver);
    }
}
