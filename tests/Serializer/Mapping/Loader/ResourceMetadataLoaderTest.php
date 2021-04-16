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

namespace ApiPlatform\Core\Tests\Serializer\Mapping\Loader;

use ApiPlatform\Core\Serializer\Mapping\Loader\ResourceMetadataLoader;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy as DummyEntity;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Models\RelatedDummy;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Mapping\AttributeMetadata;
use Symfony\Component\Serializer\Mapping\ClassMetadata;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class ResourceMetadataLoaderTest extends TestCase
{
    use ProphecyTrait;

    private $resourceMetadataLoader;

    protected function setUp(): void
    {
        $this->resourceMetadataLoader = new ResourceMetadataLoader();
    }

    public function testLoadClassMetadataNoApiProperties(): void
    {
        self::assertFalse($this->resourceMetadataLoader->loadClassMetadata(new ClassMetadata(DummyEntity::class)));
    }

    public function testLoadClassMetadata(): void
    {
        $classMetadata = new ClassMetadata(RelatedDummy::class);
        $classMetadata->addAttributeMetadata(new AttributeMetadata('name'));

        $nameAttributeMetadata = new AttributeMetadata('name');
        $nameAttributeMetadata->addGroup('friends');

        $symfonyAttributeMetadata = new AttributeMetadata('symfony');
        $symfonyAttributeMetadata->addGroup('barcelona');
        $symfonyAttributeMetadata->addGroup('chicago');
        $symfonyAttributeMetadata->addGroup('friends');

        self::assertTrue($this->resourceMetadataLoader->loadClassMetadata($classMetadata));
        self::assertEquals($nameAttributeMetadata, $classMetadata->getAttributesMetadata()['name']);
        self::assertEquals($symfonyAttributeMetadata, $classMetadata->getAttributesMetadata()['symfony']);
    }
}
