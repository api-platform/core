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
    private ?string $nullableByPhpType;

    #[Field(type: 'string', nullable: true)]
    private string $notNullableByPhpType;

    #[Field(type: 'string', nullable: true)]
    private $untypedNullableByMapping;

    #[ReferenceOne(targetDocument: DoctrineRelation::class)]
    private ?DoctrineRelation $nullableReference;
}
