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

namespace ApiPlatform\Doctrine\Odm\Tests\PropertyInfo\Fixtures;

use Doctrine\ODM\MongoDB\Mapping\Annotations\Document;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Field;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Id;
use Doctrine\ODM\MongoDB\Mapping\Annotations\ReferenceOne;

#[Document]
class DoctrineTypedNullability
{
    #[Id]
    public $id;

    #[Field(type: 'string')]
    private ?string $nullableByPhpType; // @phpstan-ignore-line

    #[Field(type: 'string', nullable: true)]
    private string $notNullableByPhpType; // @phpstan-ignore-line

    #[Field(type: 'string', nullable: true)]
    private $untypedNullableByMapping; // @phpstan-ignore-line

    #[ReferenceOne(targetDocument: DoctrineRelation::class)]
    private ?DoctrineRelation $nullableReference; // @phpstan-ignore-line

    #[Field(enumType: EnumString::class)]
    private ?EnumString $nullableEnumByPhpType; // @phpstan-ignore-line
}
