<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Doctrine\Util;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\IdentifierManagerTrait;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\ClassMetadata;

class IdentifierManagerTraitImpl
{
    use IdentifierManagerTrait;
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;

    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory)
    {
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
    }
}

class IdentifierManagerTraitTest extends \PHPUnit_Framework_TestCase
{
    private function getMetadataProphecies(array $identifiers, string $resourceClass)
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

        $propertyNameCollectionFactoryProphecy->create($resourceClass)->willReturn(new PropertyNameCollection($nameCollection))->shouldBeCalled();

        return [$propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal()];
    }

    private function getObjectManagerProphecy(array $output, string $resourceClass)
    {
        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getIdentifier()->willReturn($output)->shouldBeCalled();
        $managerProphecy = $this->prophesize(ObjectManager::class);
        $managerProphecy->getClassMetadata($resourceClass)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());

        return $managerProphecy->reveal();
    }

    public function testSingleIdentifier()
    {
        $identifiers = ['id'];
        list($propertyNameCollectionFactory, $propertyMetadataFactory) = $this->getMetadataProphecies($identifiers, Dummy::class);
        $objectManager = $this->getObjectManagerProphecy($identifiers, Dummy::class);

        $identifierManager = new IdentifierManagerTraitImpl($propertyNameCollectionFactory, $propertyMetadataFactory);

        $this->assertEquals($identifierManager->normalizeIdentifiers(1, $objectManager, Dummy::class), ['id' => 1]);
    }

    public function testCompositeIdentifier()
    {
        $identifiers = ['ida', 'idb'];
        list($propertyNameCollectionFactory, $propertyMetadataFactory) = $this->getMetadataProphecies($identifiers, Dummy::class);
        $objectManager = $this->getObjectManagerProphecy($identifiers, Dummy::class);

        $identifierManager = new IdentifierManagerTraitImpl($propertyNameCollectionFactory, $propertyMetadataFactory);

        $this->assertEquals($identifierManager->normalizeIdentifiers('ida=1;idb=2', $objectManager, Dummy::class), ['ida' => 1, 'idb' => 2]);
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\PropertyNotFoundException
     * @expectedExceptionMessage Invalid identifier "idbad=1;idb=2", "ida" has not been found.
     */
    public function testInvalidIdentifier()
    {
        $identifiers = ['ida', 'idb'];
        list($propertyNameCollectionFactory, $propertyMetadataFactory) = $this->getMetadataProphecies($identifiers, Dummy::class);
        $objectManager = $this->getObjectManagerProphecy($identifiers, Dummy::class);

        $identifierManager = new IdentifierManagerTraitImpl($propertyNameCollectionFactory, $propertyMetadataFactory);

        $identifierManager->normalizeIdentifiers('idbad=1;idb=2', $objectManager, Dummy::class);
    }
}
