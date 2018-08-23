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
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Doctrine\Generator\Uuid;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

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

        $identifiersExtractor = new IdentifiersExtractor($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), null, $this->getResourceClassResolver());

        $this->assertSame(['id'], $identifiersExtractor->getIdentifiersFromResourceClass(Dummy::class));
    }

    public function testGetCompositeIdentifiersFromResourceClass()
    {
        list($propertyNameCollectionFactoryProphecy, $propertyMetadataFactoryProphecy) = $this->getMetadataFactoryProphecies(Dummy::class, ['id', 'name']);

        $identifiersExtractor = new IdentifiersExtractor($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), null, $this->getResourceClassResolver());

        $this->assertSame(['id', 'name'], $identifiersExtractor->getIdentifiersFromResourceClass(Dummy::class));
    }

    public function itemProvider()
    {
        $dummy = new Dummy();
        $dummy->setId(1);
        yield [$dummy, ['id' => 1]];

        $uuid = new Uuid();
        $dummy = new Dummy();
        $dummy->setId($uuid);
        yield [$dummy, ['id' => $uuid]];
    }

    /**
     * @dataProvider itemProvider
     */
    public function testGetIdentifiersFromItem($item, $expected)
    {
        list($propertyNameCollectionFactoryProphecy, $propertyMetadataFactoryProphecy) = $this->getMetadataFactoryProphecies(Dummy::class, ['id']);

        $identifiersExtractor = new IdentifiersExtractor($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), null, $this->getResourceClassResolver());

        $this->assertSame($expected, $identifiersExtractor->getIdentifiersFromItem($item));
    }

    public function itemProviderComposite()
    {
        $dummy = new Dummy();
        $dummy->setId(1);
        $dummy->setName('foo');
        yield [$dummy, ['id' => 1, 'name' => 'foo']];

        $dummy = new Dummy();
        $dummy->setId($uuid = new Uuid());
        $dummy->setName('foo');
        yield [$dummy, ['id' => $uuid, 'name' => 'foo']];
    }

    /**
     * @dataProvider itemProviderComposite
     */
    public function testGetCompositeIdentifiersFromItem($item, $expected)
    {
        list($propertyNameCollectionFactoryProphecy, $propertyMetadataFactoryProphecy) = $this->getMetadataFactoryProphecies(Dummy::class, ['id', 'name']);

        $identifiersExtractor = new IdentifiersExtractor($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), null, $this->getResourceClassResolver());

        $this->assertSame($expected, $identifiersExtractor->getIdentifiersFromItem($item));
    }

    public function itemProviderRelated()
    {
        $related = new RelatedDummy();
        $related->setId(2);

        $dummy = new Dummy();
        $dummy->setId(1);
        $dummy->setRelatedDummy($related);
        yield [$dummy, ['id' => 1, 'relatedDummy' => 2]];

        $uuid2 = new Uuid();
        $related = new RelatedDummy();
        $related->setId($uuid2);

        $uuid = new Uuid();
        $dummy = new Dummy();
        $dummy->setId($uuid);
        $dummy->setRelatedDummy($related);
        yield [$dummy, ['id' => $uuid, 'relatedDummy' => $uuid2]];
    }

    /**
     * @dataProvider itemProviderRelated
     */
    public function testGetRelatedIdentifiersFromItem($item, $expected)
    {
        $prophecies = $this->getMetadataFactoryProphecies(Dummy::class, ['id', 'relatedDummy']);
        list($propertyNameCollectionFactoryProphecy, $propertyMetadataFactoryProphecy) = $this->getMetadataFactoryProphecies(RelatedDummy::class, ['id'], $prophecies);

        $identifiersExtractor = new IdentifiersExtractor($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), null, $this->getResourceClassResolver());

        $this->assertSame($expected, $identifiersExtractor->getIdentifiersFromItem($item));
    }

    public function testThrowNoIdentifierFromItem()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No identifier found in "ApiPlatform\\Core\\Tests\\Fixtures\\TestBundle\\Entity\\RelatedDummy" through relation "relatedDummy" of "ApiPlatform\\Core\\Tests\\Fixtures\\TestBundle\\Entity\\Dummy" used as identifier');

        $prophecies = $this->getMetadataFactoryProphecies(Dummy::class, ['id', 'relatedDummy']);
        list($propertyNameCollectionFactoryProphecy, $propertyMetadataFactoryProphecy) = $this->getMetadataFactoryProphecies(RelatedDummy::class, [], $prophecies);

        $identifiersExtractor = new IdentifiersExtractor($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), null, $this->getResourceClassResolver());

        $related = new RelatedDummy();
        $related->setId(2);

        $dummy = new Dummy();
        $dummy->setId(1);
        $dummy->setRelatedDummy($related);

        $identifiersExtractor->getIdentifiersFromItem($dummy);
    }

    private function getResourceClassResolver()
    {
        $resourceClassResolver = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolver->isResourceClass(Argument::type('string'))->will(function ($args) {
            if (Uuid::class === $args[0]) {
                return false;
            }

            return true;
        });

        return $resourceClassResolver->reveal();
    }

    /**
     * @group legacy
     * @expectedDeprecation Not injecting ApiPlatform\Core\Api\ResourceClassResolverInterface in the CachedIdentifiersExtractor might introduce cache issues with object identifiers.
     */
    public function testLegacyGetIdentifiersFromItem()
    {
        list($propertyNameCollectionFactoryProphecy, $propertyMetadataFactoryProphecy) = $this->getMetadataFactoryProphecies(Dummy::class, ['id']);

        $identifiersExtractor = new IdentifiersExtractor($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal());

        $dummy = new Dummy();
        $dummy->setId(1);

        $this->assertSame(['id' => 1], $identifiersExtractor->getIdentifiersFromItem($dummy));
    }
}
