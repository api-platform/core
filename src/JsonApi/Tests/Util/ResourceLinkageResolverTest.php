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

namespace ApiPlatform\JsonApi\Tests\Util;

use ApiPlatform\JsonApi\Tests\Fixtures\Dummy;
use ApiPlatform\JsonApi\Tests\Fixtures\RelatedDummy;
use ApiPlatform\JsonApi\Util\ResourceLinkageResolver;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\TypeInfo\Type;

class ResourceLinkageResolverTest extends TestCase
{
    use ProphecyTrait;

    private ResourceLinkageResolver $resourceLinkageResolver;

    protected function setUp(): void
    {
        $resourceClassResolver = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolver->isResourceClass(RelatedDummy::class)->willReturn(true);
        $resourceClassResolver->isResourceClass(Dummy::class)->willReturn(true);
        $resourceClassResolver->isResourceClass(\ArrayObject::class)->willReturn(false);

        $this->resourceLinkageResolver = new ResourceLinkageResolver($resourceClassResolver->reveal());
    }

    public function testScalarPropertyIsNotARelationship(): void
    {
        $property = (new ApiProperty())->withNativeType(Type::string());

        $this->assertSame([], $this->resourceLinkageResolver->getRelationships($property));
    }

    public function testPropertyWithoutNativeTypeIsNotARelationship(): void
    {
        $this->assertSame([], $this->resourceLinkageResolver->getRelationships(new ApiProperty()));
    }

    public function testObjectThatIsNotAResourceIsNotARelationship(): void
    {
        $property = (new ApiProperty())->withNativeType(Type::object(\ArrayObject::class));

        $this->assertSame([], $this->resourceLinkageResolver->getRelationships($property));
    }

    public function testToOneRelationship(): void
    {
        $property = (new ApiProperty())->withNativeType(Type::object(RelatedDummy::class));

        $this->assertSame([[RelatedDummy::class, false]], $this->resourceLinkageResolver->getRelationships($property));
    }

    public function testNullableToOneRelationship(): void
    {
        $property = (new ApiProperty())->withNativeType(Type::nullable(Type::object(RelatedDummy::class)));

        $this->assertSame([[RelatedDummy::class, false]], $this->resourceLinkageResolver->getRelationships($property));
    }

    public function testToManyRelationship(): void
    {
        $property = (new ApiProperty())->withNativeType(Type::collection(Type::object(ArrayCollection::class), Type::object(RelatedDummy::class)));

        $this->assertSame([[RelatedDummy::class, true]], $this->resourceLinkageResolver->getRelationships($property));
    }
}
