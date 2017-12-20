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

namespace ApiPlatform\Core\Tests\Api;

use ApiPlatform\Core\Api\IdentifiersExtractor;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use PHPUnit\Framework\TestCase;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class IdentifiersExtractorTest extends TestCase
{
    private function getMetadataFactoryProphecies($class, $identifiers, array $prophecies = null)
    {
        //adds a random property that is not an identifier
        $properties = array_merge(['foo'], $identifiers);

        if (!$prophecies) {
            $prophecies = [$this->prophesize(PropertyNameCollectionFactoryInterface::class), $this->prophesize(PropertyMetadataFactoryInterface::class)];
        }

        list($propertyNameCollectionFactoryProphecy, $propertyMetadataFactoryProphecy) = $prophecies;

        $propertyNameCollectionFactoryProphecy->create($class)->shouldBeCalled()->willReturn(new PropertyNameCollection($properties));

        foreach ($properties as $prop) {
            $metadata = new PropertyMetadata();
            $propertyMetadataFactoryProphecy->create($class, $prop)->shouldBeCalled()->willReturn($metadata->withIdentifier(\in_array($prop, $identifiers, true)));
        }

        return [$propertyNameCollectionFactoryProphecy, $propertyMetadataFactoryProphecy];
    }

    public function testGetIdentifiersFromResourceClass()
    {
        list($propertyNameCollectionFactoryProphecy, $propertyMetadataFactoryProphecy) = $this->getMetadataFactoryProphecies(Dummy::class, ['id']);

        $identifiersExtractor = new IdentifiersExtractor($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal());

        $this->assertEquals(['id'], $identifiersExtractor->getIdentifiersFromResourceClass(Dummy::class));
    }

    public function testGetCompositeIdentifiersFromResourceClass()
    {
        list($propertyNameCollectionFactoryProphecy, $propertyMetadataFactoryProphecy) = $this->getMetadataFactoryProphecies(Dummy::class, ['id', 'name']);

        $identifiersExtractor = new IdentifiersExtractor($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal());

        $this->assertEquals(['id', 'name'], $identifiersExtractor->getIdentifiersFromResourceClass(Dummy::class));
    }

    public function testGetIdentifiersFromItem()
    {
        list($propertyNameCollectionFactoryProphecy, $propertyMetadataFactoryProphecy) = $this->getMetadataFactoryProphecies(Dummy::class, ['id']);

        $identifiersExtractor = new IdentifiersExtractor($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal());

        $dummy = new Dummy();
        $dummy->setId(1);

        $this->assertEquals(['id' => 1], $identifiersExtractor->getIdentifiersFromItem($dummy));
    }

    public function testGetCompositeIdentifiersFromItem()
    {
        list($propertyNameCollectionFactoryProphecy, $propertyMetadataFactoryProphecy) = $this->getMetadataFactoryProphecies(Dummy::class, ['id', 'name']);

        $identifiersExtractor = new IdentifiersExtractor($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal());

        $dummy = new Dummy();
        $dummy->setId(1);
        $dummy->setName('foo');

        $this->assertEquals(['id' => 1, 'name' => 'foo'], $identifiersExtractor->getIdentifiersFromItem($dummy));
    }

    public function testGetRelatedIdentifiersFromItem()
    {
        $prophecies = $this->getMetadataFactoryProphecies(Dummy::class, ['id', 'relatedDummy']);
        list($propertyNameCollectionFactoryProphecy, $propertyMetadataFactoryProphecy) = $this->getMetadataFactoryProphecies(RelatedDummy::class, ['id'], $prophecies);

        $identifiersExtractor = new IdentifiersExtractor($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal());

        $related = new RelatedDummy();
        $related->setId(2);

        $dummy = new Dummy();
        $dummy->setId(1);
        $dummy->setRelatedDummy($related);

        $this->assertEquals(['id' => 1, 'relatedDummy' => 2], $identifiersExtractor->getIdentifiersFromItem($dummy));
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\RuntimeException
     * @expectedMessage No identifier found in "ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy" through relation "relatedDummy" of "ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy" used as identifier
     */
    public function testThrowNoIdentifierFromItem()
    {
        $prophecies = $this->getMetadataFactoryProphecies(Dummy::class, ['id', 'relatedDummy']);
        list($propertyNameCollectionFactoryProphecy, $propertyMetadataFactoryProphecy) = $this->getMetadataFactoryProphecies(RelatedDummy::class, [], $prophecies);

        $identifiersExtractor = new IdentifiersExtractor($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal());

        $related = new RelatedDummy();
        $related->setId(2);

        $dummy = new Dummy();
        $dummy->setId(1);
        $dummy->setRelatedDummy($related);

        $identifiersExtractor->getIdentifiersFromItem($dummy);
    }
}
