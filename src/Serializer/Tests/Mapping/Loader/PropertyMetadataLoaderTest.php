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
use ApiPlatform\Serializer\Tests\Fixtures\Model\AbstractWithOtherDiscriminator;
use ApiPlatform\Serializer\Tests\Fixtures\Model\ConcreteWithDiscriminator;
use ApiPlatform\Serializer\Tests\Fixtures\Model\HasClassAttributes;
use ApiPlatform\Serializer\Tests\Fixtures\Model\HasRelation;
use ApiPlatform\Serializer\Tests\Fixtures\Model\HasSerializerAttributes;
use ApiPlatform\Serializer\Tests\Fixtures\Model\Relation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Mapping\AttributeMetadataInterface;
use Symfony\Component\Serializer\Mapping\ClassMetadata;

final class PropertyMetadataLoaderTest extends TestCase
{
    public function testCreateMappingForASetOfProperties(): void
    {
        $attributesMetadata = $this->loadAttributesMetadata(['relation'], HasRelation::class);

        $this->assertArrayHasKey('relation', $attributesMetadata);
        $this->assertEquals(['read'], $attributesMetadata['relation']->getGroups());
    }

    public function testCreateMappingForAClass(): void
    {
        $attributesMetadata = $this->loadAttributesMetadata(['name'], Relation::class);

        $this->assertArrayHasKey('name', $attributesMetadata);
        $this->assertEquals(['read'], $attributesMetadata['name']->getGroups());
    }

    public function testForwardsMaxDepth(): void
    {
        $attributesMetadata = $this->loadAttributesMetadata(['shallow', 'deep']);

        $this->assertSame(2, $attributesMetadata['shallow']->getMaxDepth());
        $this->assertSame(7, $attributesMetadata['deep']->getMaxDepth());
    }

    public function testForwardsSerializedName(): void
    {
        $attributesMetadata = $this->loadAttributesMetadata(['first', 'second']);

        $this->assertSame('renamed', $attributesMetadata['first']->getSerializedName());
        $this->assertSame('otherName', $attributesMetadata['second']->getSerializedName());
    }

    public function testForwardsSerializedPath(): void
    {
        $attributesMetadata = $this->loadAttributesMetadata(['nested', 'elsewhere']);

        $this->assertSame('[nested][path]', (string) $attributesMetadata['nested']->getSerializedPath());
        $this->assertSame('[other][spot]', (string) $attributesMetadata['elsewhere']->getSerializedPath());
    }

    public function testForwardsPropertyGroups(): void
    {
        $attributesMetadata = $this->loadAttributesMetadata(['readable', 'writable']);

        $this->assertSame(['read'], $attributesMetadata['readable']->getGroups());
        $this->assertSame(['write', 'admin'], $attributesMetadata['writable']->getGroups());
    }

    public function testForwardsIgnore(): void
    {
        $attributesMetadata = $this->loadAttributesMetadata(['hidden', 'plain']);

        $this->assertTrue($attributesMetadata['hidden']->isIgnored());
        $this->assertFalse($attributesMetadata['plain']->isIgnored());
    }

    public function testForwardsSplitContextForGroups(): void
    {
        $attributesMetadata = $this->loadAttributesMetadata(['splitContext']);

        $this->assertSame(['norm' => 'n'], $attributesMetadata['splitContext']->getNormalizationContextForGroups(['split']));
        $this->assertSame(['denorm' => 'd'], $attributesMetadata['splitContext']->getDenormalizationContextForGroups(['split']));
    }

    public function testForwardsSharedContextForGroups(): void
    {
        $attributesMetadata = $this->loadAttributesMetadata(['sharedContext']);

        $this->assertSame(['shared' => 's'], $attributesMetadata['sharedContext']->getNormalizationContextForGroups(['both']));
        $this->assertSame(['shared' => 's'], $attributesMetadata['sharedContext']->getDenormalizationContextForGroups(['both']));
    }

    public function testForwardsClassGroupsToEveryProperty(): void
    {
        $attributesMetadata = $this->loadAttributesMetadata(['any', 'other'], HasClassAttributes::class);

        $this->assertSame(['classA', 'classB'], $attributesMetadata['any']->getGroups());
        $this->assertSame(['classA', 'classB'], $attributesMetadata['other']->getGroups());
    }

    public function testForwardsClassContextToEveryProperty(): void
    {
        $attributesMetadata = $this->loadAttributesMetadata(['any', 'other'], HasClassAttributes::class);

        foreach (['any', 'other'] as $property) {
            $this->assertSame(['classNorm' => 'cn'], $attributesMetadata[$property]->getNormalizationContextForGroups(['classGroup']));
            $this->assertSame(['classDenorm' => 'cd'], $attributesMetadata[$property]->getDenormalizationContextForGroups(['classGroup']));
        }
    }

    /**
     * @param class-string         $class
     * @param array<string, mixed> $expectedMapping
     */
    #[DataProvider('provideDiscriminatorCases')]
    public function testForwardsDiscriminatorMapping(string $class, string $expectedTypeProperty, array $expectedMapping, string $expectedDefaultType): void
    {
        $coll = $this->createStub(PropertyNameCollectionFactoryInterface::class);
        $coll->method('create')->willReturn(new PropertyNameCollection([]));
        $loader = new PropertyMetadataLoader($coll);
        $classMetadata = new ClassMetadata($class);
        $loader->loadClassMetadata($classMetadata);

        $mapping = $classMetadata->getClassDiscriminatorMapping();
        $this->assertNotNull($mapping);
        $this->assertSame($expectedTypeProperty, $mapping->getTypeProperty());
        $this->assertSame($expectedMapping, $mapping->getTypesMapping());
        $this->assertSame($expectedDefaultType, $mapping->getDefaultType());
    }

    /**
     * @return iterable<string, array{class-string, string, array<string, mixed>, string}>
     */
    public static function provideDiscriminatorCases(): iterable
    {
        yield 'discr' => [AbstractWithDiscriminator::class, 'discr', ['concrete' => ConcreteWithDiscriminator::class], 'concrete'];
        yield 'kind' => [AbstractWithOtherDiscriminator::class, 'kind', ['other' => ConcreteWithDiscriminator::class], 'other'];
    }

    /**
     * @param list<string>      $properties
     * @param class-string|null $class
     *
     * @return array<string, AttributeMetadataInterface>
     */
    private function loadAttributesMetadata(array $properties, ?string $class = null): array
    {
        $coll = $this->createStub(PropertyNameCollectionFactoryInterface::class);
        $coll->method('create')->willReturn(new PropertyNameCollection($properties));
        $loader = new PropertyMetadataLoader($coll);
        $classMetadata = new ClassMetadata($class ?? HasSerializerAttributes::class);
        $loader->loadClassMetadata($classMetadata);

        return $classMetadata->getAttributesMetadata();
    }
}
