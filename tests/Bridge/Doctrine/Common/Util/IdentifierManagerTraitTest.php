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

namespace ApiPlatform\Core\Tests\Bridge\Doctrine\Common\Util;

use ApiPlatform\Core\Bridge\Doctrine\Common\Util\IdentifierManagerTrait;
use ApiPlatform\Core\Exception\PropertyNotFoundException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\Dummy as DummyDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata as MongoDbOdmClassMetadata;
use Doctrine\ODM\MongoDB\Types\Type as MongoDbType;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class IdentifierManagerTraitTest extends TestCase
{
    private function getIdentifierManagerTraitImpl(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory)
    {
        return new class($propertyNameCollectionFactory, $propertyMetadataFactory) {
            use IdentifierManagerTrait {
                IdentifierManagerTrait::normalizeIdentifiers as public;
            }

            public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory)
            {
                $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
                $this->propertyMetadataFactory = $propertyMetadataFactory;
            }
        };
    }

    /**
     * @group legacy
     */
    public function testSingleIdentifier()
    {
        [$propertyNameCollectionFactory, $propertyMetadataFactory] = $this->getMetadataFactories(Dummy::class, [
            'id',
        ]);
        $objectManager = $this->getEntityManager(Dummy::class, [
            'id' => [
                'type' => DBALType::INTEGER,
            ],
        ]);

        $identifierManager = $this->getIdentifierManagerTraitImpl($propertyNameCollectionFactory, $propertyMetadataFactory);

        $this->assertEquals($identifierManager->normalizeIdentifiers(1, $objectManager, Dummy::class), ['id' => 1]);
    }

    /**
     * @group legacy
     * @group mongodb
     */
    public function testSingleDocumentIdentifier()
    {
        [$propertyNameCollectionFactory, $propertyMetadataFactory] = $this->getMetadataFactories(DummyDocument::class, [
            'id',
        ]);
        $objectManager = $this->getDocumentManager(DummyDocument::class, [
            'id' => [
                'type' => MongoDbType::INTEGER,
            ],
        ]);

        $identifierManager = $this->getIdentifierManagerTraitImpl($propertyNameCollectionFactory, $propertyMetadataFactory);

        $this->assertEquals($identifierManager->normalizeIdentifiers(1, $objectManager, DummyDocument::class), ['id' => 1]);
    }

    /**
     * @group legacy
     */
    public function testCompositeIdentifier()
    {
        [$propertyNameCollectionFactory, $propertyMetadataFactory] = $this->getMetadataFactories(Dummy::class, [
            'ida',
            'idb',
        ]);
        $objectManager = $this->getEntityManager(Dummy::class, [
            'ida' => [
                'type' => DBALType::INTEGER,
            ],
            'idb' => [
                'type' => DBALType::INTEGER,
            ],
        ]);

        $identifierManager = $this->getIdentifierManagerTraitImpl($propertyNameCollectionFactory, $propertyMetadataFactory);

        $this->assertEquals($identifierManager->normalizeIdentifiers('ida=1;idb=2', $objectManager, Dummy::class), ['ida' => 1, 'idb' => 2]);
    }

    /**
     * @group legacy
     */
    public function testInvalidIdentifier()
    {
        $this->expectException(PropertyNotFoundException::class);
        $this->expectExceptionMessage('Invalid identifier "idbad=1;idb=2", "ida" was not found.');

        [$propertyNameCollectionFactory, $propertyMetadataFactory] = $this->getMetadataFactories(Dummy::class, [
            'ida',
            'idb',
        ]);
        $objectManager = $this->getEntityManager(Dummy::class, [
            'ida' => [
                'type' => DBALType::INTEGER,
            ],
            'idb' => [
                'type' => DBALType::INTEGER,
            ],
        ]);

        $identifierManager = $this->getIdentifierManagerTraitImpl($propertyNameCollectionFactory, $propertyMetadataFactory);

        $identifierManager->normalizeIdentifiers('idbad=1;idb=2', $objectManager, Dummy::class);
    }

    /**
     * Gets mocked metadata factories.
     */
    private function getMetadataFactories(string $resourceClass, array $identifiers): array
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $nameCollection = ['foobar'];

        foreach ($identifiers as $identifier) {
            $metadata = new PropertyMetadata();
            $metadata = $metadata->withIdentifier(true);
            $propertyMetadataFactoryProphecy->create($resourceClass, $identifier)->willReturn($metadata);

            $nameCollection[] = $identifier;
        }

        //random property to prevent the use of non-identifiers metadata while looping
        $propertyMetadataFactoryProphecy->create($resourceClass, 'foobar')->willReturn(new PropertyMetadata());

        $propertyNameCollectionFactoryProphecy->create($resourceClass)->willReturn(new PropertyNameCollection($nameCollection));

        return [$propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal()];
    }

    /**
     * Gets a mocked entity manager.
     */
    private function getEntityManager(string $resourceClass, array $identifierFields): ObjectManager
    {
        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getIdentifier()->willReturn(array_keys($identifierFields));

        foreach ($identifierFields as $name => $field) {
            $classMetadataProphecy->getTypeOfField($name)->willReturn($field['type']);
        }

        $platformProphecy = $this->prophesize(AbstractPlatform::class);

        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->getDatabasePlatform()->willReturn($platformProphecy);

        $managerProphecy = $this->prophesize(EntityManagerInterface::class);
        $managerProphecy->getClassMetadata($resourceClass)->willReturn($classMetadataProphecy->reveal());
        $managerProphecy->getConnection()->willReturn($connectionProphecy);

        return $managerProphecy->reveal();
    }

    /**
     * Gets a mocked document manager.
     */
    private function getDocumentManager(string $resourceClass, array $identifierFields): ObjectManager
    {
        $classMetadataProphecy = $this->prophesize(MongoDbOdmClassMetadata::class);
        $classMetadataProphecy->getIdentifier()->willReturn(array_keys($identifierFields));

        foreach ($identifierFields as $name => $field) {
            $classMetadataProphecy->getTypeOfField($name)->willReturn($field['type']);
        }

        $managerProphecy = $this->prophesize(DocumentManager::class);
        $managerProphecy->getClassMetadata($resourceClass)->willReturn($classMetadataProphecy->reveal());

        return $managerProphecy->reveal();
    }
}
