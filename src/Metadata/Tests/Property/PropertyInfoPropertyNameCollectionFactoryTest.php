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

namespace ApiPlatform\Metadata\Tests\Property;

use ApiPlatform\Metadata\Property\Factory\PropertyInfoPropertyNameCollectionFactory;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\DummyIgnoreProperty;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\DummyObjectWithOnlyPrivateProperty;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\DummyObjectWithOnlyPublicProperty;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\DummyObjectWithoutProperty;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\DummyObjectWithPublicAndPrivateProperty;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\Extractor\SerializerExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
class PropertyInfoPropertyNameCollectionFactoryTest extends TestCase
{
    public function testCreateMethodReturnsEmptyPropertyNameCollectionForObjectWithOnlyPrivateProperty(): void
    {
        $factory = new PropertyInfoPropertyNameCollectionFactory(new PropertyInfoExtractor([
            new ReflectionExtractor(),
        ]));

        $collection = $factory->create(DummyObjectWithOnlyPrivateProperty::class);

        self::assertCount(0, $collection->getIterator());
    }

    public function testCreateMethodReturnsEmptyPropertyNameCollectionForObjectWithoutProperties(): void
    {
        $factory = new PropertyInfoPropertyNameCollectionFactory(new PropertyInfoExtractor([
            new ReflectionExtractor(),
        ]));

        $collection = $factory->create(DummyObjectWithoutProperty::class);

        self::assertCount(0, $collection->getIterator());
    }

    public function testCreateMethodReturnsProperPropertyNameCollectionForObjectWithPublicAndPrivateProperty(): void
    {
        $factory = new PropertyInfoPropertyNameCollectionFactory(new PropertyInfoExtractor([
            new ReflectionExtractor(),
        ]));

        $collection = $factory->create(DummyObjectWithPublicAndPrivateProperty::class);

        self::assertCount(1, $collection->getIterator());
    }

    public function testCreateMethodReturnsProperPropertyNameCollectionForObjectWithPublicProperty(): void
    {
        $factory = new PropertyInfoPropertyNameCollectionFactory(new PropertyInfoExtractor([
            new ReflectionExtractor(),
        ]));

        $collection = $factory->create(DummyObjectWithOnlyPublicProperty::class);

        self::assertCount(1, $collection->getIterator());
    }

    public function testCreateMethodReturnsProperPropertyNameCollectionForObjectWithIgnoredProperties(): void
    {
        $factory = new PropertyInfoPropertyNameCollectionFactory(
            new PropertyInfoExtractor([
                new SerializerExtractor(
                    new ClassMetadataFactory(
                        new AnnotationLoader(
                        )
                    )
                ),
            ])
        );

        $this->assertTrue((new \ReflectionObject(new DummyIgnoreProperty()))->hasProperty('ignored'));

        $collection = $factory->create(DummyIgnoreProperty::class, ['serializer_groups' => ['dummy']]);

        self::assertCount(1, $collection);
        self::assertNotContains('ignored', $collection);

        $collection = $factory->create(DummyIgnoreProperty::class);

        self::assertCount(2, $collection);
        self::assertNotContains('ignored', $collection);
    }
}
