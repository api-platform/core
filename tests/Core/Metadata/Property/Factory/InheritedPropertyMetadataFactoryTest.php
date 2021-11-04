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

namespace ApiPlatform\Core\Tests\Metadata\Property\Factory;

use ApiPlatform\Core\Metadata\Property\Factory\InheritedPropertyMetadataFactory;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyTableInheritance;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyTableInheritanceChild;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Type;

/**
 * @group legacy
 */
class InheritedPropertyMetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreate()
    {
        $resourceNameCollectionFactory = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactory->create()->willReturn(new ResourceNameCollection([DummyTableInheritance::class, DummyTableInheritanceChild::class]))->shouldBeCalled();

        $type = new Type(Type::BUILTIN_TYPE_STRING);
        $nicknameMetadata = (new ApiProperty())->withBuiltinTypes([$type])->withDescription('nickname')->withReadable(true)->withWritable(true)->withWritableLink(false)->withReadableLink(false)->withRequired(true)->withIdentifier(false)->withTypes(['http://example.com/foo'])->withExtraProperties(['foo' => 'bar']);
        $propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->create(DummyTableInheritance::class, 'nickname', [])->willReturn($nicknameMetadata)->shouldBeCalled();
        $propertyMetadataFactory->create(DummyTableInheritanceChild::class, 'nickname', [])->willReturn($nicknameMetadata)->shouldBeCalled();

        $factory = new InheritedPropertyMetadataFactory($resourceNameCollectionFactory->reveal(), $propertyMetadataFactory->reveal());
        $metadata = $factory->create(DummyTableInheritance::class, 'nickname');

        $this->assertEquals($metadata, $nicknameMetadata->withChildInherited(DummyTableInheritanceChild::class));
    }
}
