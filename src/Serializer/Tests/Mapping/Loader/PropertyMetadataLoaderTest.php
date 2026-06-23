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

namespace ApiPlatform\Serializer\Tests\Mapping\Loader;

use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Serializer\Mapping\Loader\PropertyMetadataLoader;
use ApiPlatform\Serializer\Tests\Fixtures\Model\AbstractWithDiscriminator;
use ApiPlatform\Serializer\Tests\Fixtures\Model\HasRelation;
use ApiPlatform\Serializer\Tests\Fixtures\Model\Relation;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorMapping;
use Symfony\Component\Serializer\Mapping\ClassMetadata;

final class PropertyMetadataLoaderTest extends TestCase
{
    public function testCreateMappingForASetOfProperties(): void
    {
        $coll = $this->createStub(PropertyNameCollectionFactoryInterface::class);
        $coll->method('create')->willReturn(new PropertyNameCollection(['relation']));
        $loader = new PropertyMetadataLoader($coll);
        $classMetadata = new ClassMetadata(HasRelation::class);
        $loader->loadClassMetadata($classMetadata);
        if (method_exists($classMetadata, 'getAttributesMetadata')) { // @phpstan-ignore-line
            $attributesMetadata = $classMetadata->getAttributesMetadata();
        } else {
            $attributesMetadata = $classMetadata->attributesMetadata; // @phpstan-ignore-line
        }

        $this->assertArrayHasKey('relation', $attributesMetadata);
        $this->assertEquals(['read'], $attributesMetadata['relation']->getGroups());
    }

    public function testCreateMappingForAClass(): void
    {
        $coll = $this->createStub(PropertyNameCollectionFactoryInterface::class);
        $coll->method('create')->willReturn(new PropertyNameCollection(['name']));
        $loader = new PropertyMetadataLoader($coll);
        $classMetadata = new ClassMetadata(Relation::class);
        $loader->loadClassMetadata($classMetadata);
        if (method_exists($classMetadata, 'getAttributesMetadata')) { // @phpstan-ignore-line
            $attributesMetadata = $classMetadata->getAttributesMetadata();
        } else {
            $attributesMetadata = $classMetadata->attributesMetadata; // @phpstan-ignore-line
        }
        $this->assertArrayHasKey('name', $attributesMetadata);
        $this->assertEquals(['read'], $attributesMetadata['name']->getGroups());
    }

    public function testForwardsDiscriminatorDefaultType(): void
    {
        if (!method_exists(ClassDiscriminatorMapping::class, 'getDefaultType')) { // @phpstan-ignore-line
            $this->markTestSkipped('ClassDiscriminatorMapping::getDefaultType() requires symfony/serializer 7.1+.');
        }

        $coll = $this->createStub(PropertyNameCollectionFactoryInterface::class);
        $coll->method('create')->willReturn(new PropertyNameCollection([]));
        $loader = new PropertyMetadataLoader($coll);
        $classMetadata = new ClassMetadata(AbstractWithDiscriminator::class);
        $loader->loadClassMetadata($classMetadata);

        $mapping = $classMetadata->getClassDiscriminatorMapping();
        $this->assertNotNull($mapping);
        $this->assertSame('concrete', $mapping->getDefaultType());
    }
}
