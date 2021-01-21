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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\PropertyInfo\Metadata\Property;

use ApiPlatform\Core\Bridge\Symfony\PropertyInfo\Metadata\Property\PropertyInfoPropertyNameCollectionFactory;
use ApiPlatform\Core\Tests\Fixtures\DummyIgnoreProperty;
use ApiPlatform\Core\Tests\Fixtures\DummyObjectWithOnlyPrivateProperty;
use ApiPlatform\Core\Tests\Fixtures\DummyObjectWithOnlyPublicProperty;
use ApiPlatform\Core\Tests\Fixtures\DummyObjectWithoutProperty;
use ApiPlatform\Core\Tests\Fixtures\DummyObjectWithPublicAndPrivateProperty;
use Doctrine\Common\Annotations\AnnotationReader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\DependencyInjection\PropertyInfoConstructorPass;
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
    public function testCreateMethodReturnsEmptyPropertyNameCollectionForObjectWithOnlyPrivateProperty()
    {
        $factory = new PropertyInfoPropertyNameCollectionFactory(new PropertyInfoExtractor([
            new ReflectionExtractor(),
        ]));

        $collection = $factory->create(DummyObjectWithOnlyPrivateProperty::class);

        self::assertCount(0, $collection->getIterator());
    }

    public function testCreateMethodReturnsEmptyPropertyNameCollectionForObjectWithoutProperties()
    {
        $factory = new PropertyInfoPropertyNameCollectionFactory(new PropertyInfoExtractor([
            new ReflectionExtractor(),
        ]));

        $collection = $factory->create(DummyObjectWithoutProperty::class);

        self::assertCount(0, $collection->getIterator());
    }

    public function testCreateMethodReturnsProperPropertyNameCollectionForObjectWithPublicAndPrivateProperty()
    {
        $factory = new PropertyInfoPropertyNameCollectionFactory(new PropertyInfoExtractor([
            new ReflectionExtractor(),
        ]));

        $collection = $factory->create(DummyObjectWithPublicAndPrivateProperty::class);

        self::assertCount(1, $collection->getIterator());
    }

    public function testCreateMethodReturnsProperPropertyNameCollectionForObjectWithPublicProperty()
    {
        $factory = new PropertyInfoPropertyNameCollectionFactory(new PropertyInfoExtractor([
            new ReflectionExtractor(),
        ]));

        $collection = $factory->create(DummyObjectWithOnlyPublicProperty::class);

        self::assertCount(1, $collection->getIterator());
    }

    public function testCreateMethodReturnsProperPropertyNameCollectionForObjectWithIgnoredProperties(): void
    {
        // symfony/property-info < 5.2.1
        if (!class_exists(PropertyInfoConstructorPass::class)) {
            self::markTestSkipped();
        }

        $factory = new PropertyInfoPropertyNameCollectionFactory(
            new PropertyInfoExtractor([
                new SerializerExtractor(
                    new ClassMetadataFactory(
                        new AnnotationLoader(
                            new AnnotationReader()
                        )
                    )
                ),
            ])
        );

        self::assertObjectHasAttribute('ignored', new DummyIgnoreProperty());

        $collection = $factory->create(DummyIgnoreProperty::class, ['serializer_groups' => ['dummy']]);

        self::assertCount(1, $collection);
        self::assertNotContains('ignored', $collection);

        $collection = $factory->create(DummyIgnoreProperty::class);

        self::assertCount(2, $collection);
        self::assertNotContains('ignored', $collection);
    }
}
