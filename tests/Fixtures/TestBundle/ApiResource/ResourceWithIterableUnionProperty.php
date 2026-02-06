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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\NonResourceClass;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\GenericType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\Type\UnionType;
use Symfony\Component\TypeInfo\TypeIdentifier;

#[ApiResource]
final class ResourceWithIterableUnionProperty
{
    /**
     * @param array<int, Species|NonResourceClass|string|int> $unionItems
     */
    public function __construct(
        public int $id,
        #[ApiProperty(
            nativeType: new CollectionType(
                new GenericType(
                    new BuiltinType(TypeIdentifier::ARRAY),
                    new BuiltinType(TypeIdentifier::INT),
                    new UnionType(
                        new ObjectType(Species::class),
                        new ObjectType(NonResourceClass::class),
                        new BuiltinType(TypeIdentifier::INT),
                        new BuiltinType(TypeIdentifier::STRING),
                    ),
                ),
                true,
            ),
        )]
        public array $unionItems = [],
    ) {
    }
}
