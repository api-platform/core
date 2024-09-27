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
use ApiPlatform\Serializer\Tests\Fixtures\Model\HasRelation;
use ApiPlatform\Serializer\Tests\Fixtures\Model\Relation;
use PHPUnit\Framework\TestCase;
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
        $this->assertArrayHasKey('relation', $classMetadata->attributesMetadata);
        $this->assertEquals(['read'], $classMetadata->attributesMetadata['relation']->getGroups());
    }

    public function testCreateMappingForAClass(): void
    {
        $coll = $this->createStub(PropertyNameCollectionFactoryInterface::class);
        $coll->method('create')->willReturn(new PropertyNameCollection(['name']));
        $loader = new PropertyMetadataLoader($coll);
        $classMetadata = new ClassMetadata(Relation::class);
        $loader->loadClassMetadata($classMetadata);
        $this->assertArrayHasKey('name', $classMetadata->attributesMetadata);
        $this->assertEquals(['read'], $classMetadata->attributesMetadata['name']->getGroups());
    }
}
